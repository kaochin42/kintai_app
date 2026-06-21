<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use App\Traits\CalculatesAttendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use CalculatesAttendance;
    /**
     * 管理者向け：指定した日付の全ユーザーの勤怠一覧を表示する
     */
    public function index(Request $request)
    {
        // URLパラメータから日付を取得（指定がなければ今日の日付）
        $date = $request->input('date', now()->format('Y-m-d'));

        // 指定日の勤怠データを取得（ユーザー情報と休憩情報も一緒に取得してN+1問題を防ぐ）
        $attendanceRecords = AttendanceRecord::where('date', $date)
            ->with(['user', 'attendanceBreaks'])
            ->get();

        // 各レコードに「休憩時間」「実働時間」の表示用データを追加する
        $attendanceRecords = $attendanceRecords->map(function ($record) {
            $record->break_time = $this->formatMinutesToTime($this->calculateBreakMinutes($record));
            $record->work_time = $this->formatMinutesToTime($this->calculateWorkMinutes($record));
            return $record;
        });

        // 前日・翌日のリンク用に日付を計算
        $carbonDate = Carbon::parse($date);
        $prevDate = $carbonDate->copy()->subDay()->format('Y-m-d');
        $nextDate = $carbonDate->copy()->addDay()->format('Y-m-d');

        return view('admin.attendance.list', [
            'attendanceRecords' => $attendanceRecords,
            'date' => $carbonDate,
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
        ]);
    }

}
