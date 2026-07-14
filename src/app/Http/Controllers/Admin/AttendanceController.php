<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use App\Traits\CalculatesAttendance;
use Illuminate\Http\Request;
use App\Http\Requests\AttendanceUpdateRequest;
use App\Models\StampCorrectionRequest;

class AttendanceController extends Controller
{
    use CalculatesAttendance;
    /**
     * 管理者向け：指定した日付の全ユーザーの勤怠一覧を表示する
     *
     * @param Request $request リクエストパラメータ（date: Y-m-d形式）
     */
    public function index(Request $request)
    {
        // URLパラメータから日付を取得（指定がなければ今日の日付）
        $date = $request->input('date', now()->format('Y-m-d'));

        // 指定日の勤怠データを取得（ユーザー情報と休憩情報も一緒に取得してN+1問題を防ぐ）
        $attendanceRecords = AttendanceRecord::whereDate('date', $date)
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

    /**
     * 勤怠詳細を表示する（管理者）
     * 承認待ちの修正申請が存在する場合は承認画面へリダイレクトする
     *
     * @param int $id 勤怠レコードID
     */
    public function show($id)
    {
        $attendanceRecord = AttendanceRecord::where('id', $id)
            ->with('attendanceBreaks')
            ->firstOrFail();

        $stampCorrectionRequest = StampCorrectionRequest::where('attendance_record_id', $attendanceRecord->id)
            ->where('is_approved', false)
            ->with('correctionBreaks')
            ->first();

        if (!is_null($stampCorrectionRequest)) {
            return redirect("/stamp_correction_request/approve/{$stampCorrectionRequest->id}");
        }

        return view('attendance.detail', [
            'attendanceRecord' => $attendanceRecord,
            'hasPendingRequest' => !is_null($stampCorrectionRequest),
            'stampCorrectionRequest' => $stampCorrectionRequest,
        ]);
    }

    /**
     * 勤怠情報を管理者が直接修正する
     *
     * @param AttendanceUpdateRequest $request バリデーション済みの修正内容
     * @param int $id 勤怠レコードID
     */
    public function update(AttendanceUpdateRequest $request, $id)
    {
        $attendanceRecord = AttendanceRecord::where('id', $id)
            ->firstOrFail();

        $attendanceRecord->update([
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
            'comment' => $request->comment,
        ]);

        $attendanceRecord->attendanceBreaks()->delete();

        collect($request->breaks)
            ->filter(function ($break) {
                return $break['break_in'] || $break['break_out'];
            })
            ->each(function ($break) use ($attendanceRecord) {
                $attendanceRecord->attendanceBreaks()->create([
                    'break_in' => $break['break_in'],
                    'break_out' => $break['break_out'],
                ]);
            });

        return redirect("/admin/attendance/{$id}");
    }

}
