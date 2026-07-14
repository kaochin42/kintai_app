<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\CalculatesAttendance;
use Carbon\Carbon;
use App\Models\AttendanceRecord;
use Symfony\Component\HttpFoundation\StreamedResponse;


class StaffController extends Controller
{
    use CalculatesAttendance;

    /**
     * 一般ユーザーのスタッフ一覧を表示する
     */
    public function index()
    {
        $users = User::where('admin_status', false)->get();

        return view('admin.staff.list', [
            'users' => $users,
        ]);
    }

    /**
     * 指定スタッフの月次勤怠一覧を表示する
     *
     * @param Request $request リクエストパラメータ（month: Y-m形式）
     * @param int $id 対象ユーザーID
     */
    public function show(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $month = $request->input('month', now()->format('Y-m'));
        $currentMonth = Carbon::createFromFormat('Y-m', $month);

        $attendanceRecords = AttendanceRecord::where('user_id', $id)
            ->where('date', 'like', $month . '%')
            ->with('attendanceBreaks')
            ->get()
            ->keyBy(function ($record) {
                return $record->date->format('Y-m-d');
            })
            ->map(function ($record) {
                $record->break_time = $this->formatMinutesToTime($this->calculateBreakMinutes($record));
                $record->work_time = $this->formatMinutesToTime($this->calculateWorkMinutes($record));
                return $record;
            });

        $daysInMonth = $currentMonth->daysInMonth;
        $dates = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dates[] = $currentMonth->copy()->day($day)->format('Y-m-d');
        }

        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        return view('admin.staff.show', [
            'user' => $user,
            'dates' => $dates,
            'attendanceRecords' => $attendanceRecords,
            'currentMonth' => $currentMonth,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
        ]);
    }

    /**
     * 指定スタッフの月次勤怠一覧をCSVでダウンロードする
     *
     * @param Request $request リクエストパラメータ（month: Y-m形式）
     * @param int $id 対象ユーザーID
     */
    public function export(Request $request, $id): StreamedResponse
    {
        $user = User::findOrFail($id);
        $month = $request->input('month', now()->format('Y-m'));
        $currentMonth = Carbon::createFromFormat('Y-m', $month);

        $attendanceRecords = AttendanceRecord::where('user_id', $id)
            ->where('date', 'like', $month . '%')
            ->with('attendanceBreaks')
            ->get()
            ->keyBy(function ($record) {
                return $record->date->format('Y-m-d');
            })
            ->map(function ($record) {
                $record->break_time = $this->formatMinutesToTime($this->calculateBreakMinutes($record));
                $record->work_time = $this->formatMinutesToTime($this->calculateWorkMinutes($record));
                return $record;
            });

        $daysInMonth = $currentMonth->daysInMonth;
        $dates = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dates[] = $currentMonth->copy()->day($day)->format('Y-m-d');
        }

        $fileName = $user->name . '_' . $currentMonth->format('Y-m') . '.csv';

        return response()->streamDownload(function () use ($dates, $attendanceRecords) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($dates as $date) {
                $row = [
                    Carbon::parse($date)->locale('ja')->isoFormat('MM/DD(ddd)'),
                    $attendanceRecords->get($date)?->clock_in?->format('H:i') ?? '',
                    $attendanceRecords->get($date)?->clock_out?->format('H:i') ?? '',
                    $attendanceRecords->get($date)?->break_time ?? '',
                    $attendanceRecords->get($date)?->work_time ?? '',
                ];
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $fileName);
    }
}
