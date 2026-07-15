<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\CalculatesAttendance;
use Carbon\Carbon;
use App\Models\StampCorrectionRequest;
use App\Models\CorrectionBreak;
use App\Http\Requests\AttendanceUpdateRequest;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    use CalculatesAttendance;

    /**
     * 勤怠打刻画面を表示する
     * 当日の勤怠レコードとステータス（勤務外/出勤中/休憩中/退勤済）を取得して表示する
     */
    public function index()
    {
        $today = today();

        $attendanceRecord = AttendanceRecord::where('user_id', Auth::id())
            ->where('date', $today)
            ->first();

        $status = $this->getStatus($attendanceRecord);

        return view('attendance.index', [
            'attendanceRecord' => $attendanceRecord,
            'status' => $status,
            'today' => $today,
        ]);
    }

    /**
     * 打刻処理を行う（出勤・休憩入・休憩戻・退勤）
     *
     * @param Request $request リクエストパラメータ（action: clock_in/break_in/break_out/clock_out）
     */
    public function store(Request $request)
    {
        $action = $request->input('action');

        if ($action === 'clock_in') {
            $existingRecord = AttendanceRecord::where('user_id', Auth::id())
                ->where('date', today())
                ->first();

            if ($existingRecord) {
                return redirect('/attendance');
            }

            AttendanceRecord::create([
                'user_id' => Auth::id(),
                'date' => today(),
                'clock_in' => now(),
            ]);
        } elseif ($action === 'break_in') {
            $attendanceRecord = AttendanceRecord::where('user_id', Auth::id())
                ->where('date', today())
                ->first();

            $attendanceRecord->attendanceBreaks()->create([
                'break_in' => now(),
            ]);
        } elseif ($action === 'break_out') {
            $attendanceRecord = AttendanceRecord::where('user_id', Auth::id())
                ->where('date', today())
                ->first();

            $latestBreak = $attendanceRecord->attendanceBreaks()->latest()->first();

            $latestBreak->update(['break_out' => now()]);
        } elseif ($action === 'clock_out') {
            $attendanceRecord = AttendanceRecord::where('user_id', Auth::id())
                ->where('date', today())
                ->first();

            $attendanceRecord->update(['clock_out' => now()]);
        }

        return redirect('/attendance');
    }

    /**
     * 勤怠レコードの状態から現在のステータス文字列を判定する
     *
     * @param AttendanceRecord|null $attendanceRecord 当日の勤怠レコード
     * @return string 勤務外・出勤中・休憩中・退勤済のいずれか
     */
    private function getStatus($attendanceRecord)
    {
        if (!$attendanceRecord) {
            return '勤務外';
        }

        if ($attendanceRecord->clock_out) {
            return '退勤済';
        }

        $latestBreak = $attendanceRecord->attendanceBreaks()->latest()->first();

        if ($latestBreak && !$latestBreak->break_out) {
            return '休憩中';
        }

        return '出勤中';
    }

    /**
     * 指定月の勤怠一覧を表示する（一般ユーザー）
     *
     * @param Request $request リクエストパラメータ（month: Y-m形式）
     */
    public function list(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $currentMonth = Carbon::createFromFormat('Y-m', $month);

        $attendanceRecords = AttendanceRecord::where('user_id', Auth::id())
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

        return view('attendance.list', [
            'dates' => $dates,
            'attendanceRecords' => $attendanceRecords,
            'currentMonth' => $currentMonth,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
        ]);
    }

    /**
     * 勤怠詳細画面を表示する（一般ユーザー）
     *
     * @param int $id 勤怠レコードID
     */
    public function show($id)
    {
        $attendanceRecord = AttendanceRecord::where('id', $id)
            ->where('user_id', Auth::id())
            ->with('attendanceBreaks')
            ->firstOrFail();

        $stampCorrectionRequest = StampCorrectionRequest::where('attendance_record_id', $attendanceRecord->id)
            ->where('is_approved', false)
            ->with('correctionBreaks')
            ->first();

        return view('attendance.detail', [
            'attendanceRecord' => $attendanceRecord,
            'hasPendingRequest' => !is_null($stampCorrectionRequest),
            'stampCorrectionRequest' => $stampCorrectionRequest,
        ]);
    }

    /**
     * 勤怠の修正申請を作成する
     *
     * @param AttendanceUpdateRequest $request バリデーション済みの修正内容
     * @param int $id 勤怠レコードID
     */
    public function update(AttendanceUpdateRequest $request, $id)
    {
        $attendanceRecord = AttendanceRecord::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $stampCorrectionRequest = StampCorrectionRequest::create([
            'attendance_record_id' => $attendanceRecord->id,
            'user_id' => Auth::id(),
            'new_clock_in' => $request->clock_in,
            'new_clock_out' => $request->clock_out,
            'new_comment' => $request->comment,
        ]);

        collect($request->breaks)
            ->filter(function ($break) {
                return $break['break_in'] || $break['break_out'];
            })
            ->each(function ($break) use ($stampCorrectionRequest) {
                CorrectionBreak::create([
                    'stamp_correction_request_id' => $stampCorrectionRequest->id,
                    'new_break_in' => $break['break_in'],
                    'new_break_out' => $break['break_out'],
                ]);
            });

        return redirect('/stamp_correction_request/list');
    }

    /**
     * マイ勤怠レポートを表示する
     * 過去6ヶ月分の総労働時間・総残業時間・平均労働時間、月次推移、当月の異常検知（遅刻・早退・長時間労働）を集計する
     */
    public function report(): View
    {
        // 過去6ヶ月（当月含む）の期間を設定
        $startMonth = now()->subMonths(5)->startOfMonth();
        $endMonth = now()->endOfMonth();

        // 期間内のレコードを取得
        $attendanceRecords = AttendanceRecord::where('user_id', Auth::id())
            ->whereBetween('date', [$startMonth, $endMonth])
            ->with('attendanceBreaks')
            ->get();

        // 基本サマリー
        $totalWorkMinutes = $attendanceRecords->sum(
            fn($record) => $this->calculateWorkMinutes($record)
        );

        $totalOvertimeMinutes = $attendanceRecords->sum(function ($record) {
            $workMinutes = $this->calculateWorkMinutes($record);
            return $workMinutes > 480 ? $workMinutes - 480 : 0;
        });

        $workedDays = $attendanceRecords
            ->filter(fn($record) => $record->clock_in !== null)
            ->count();

        $averageWorkMinutes = $workedDays > 0
            ? intdiv($totalWorkMinutes, $workedDays)
            : 0;

        // 当月の異常検知
        $currentMonthRecords = $attendanceRecords->filter(
            fn($record) => $record->date->format('Y-m') === now()->format('Y-m')
        );

        $lateCount = $currentMonthRecords->filter(function ($record) {
            if (!$record->clock_in) {
                return false;
            }
            return $record->clock_in->gt(Carbon::parse('09:00'));
        })->count();

        $earlyLeaveCount = $currentMonthRecords->filter(function ($record) {
            if (!$record->clock_out) {
                return false;
            }
            return $record->clock_out->lt(Carbon::parse('18:00'));
        })->count();

        $longWorkCount = $currentMonthRecords->filter(
            fn($record) => $this->calculateWorkMinutes($record) > 600
        )->count();

        // 月次推移
        $monthlyRecords = $attendanceRecords->groupBy(
            fn($record) => $record->date->format('Y-m')
        );

        $monthlyData = collect(range(0, 5))->map(function ($i) use ($startMonth, $monthlyRecords) {
            $monthKey = $startMonth->copy()->addMonths($i)->format('Y-m');
            $records = $monthlyRecords->get($monthKey, collect());

            $workMinutes = $records->sum(
                fn($record) => $this->calculateWorkMinutes($record)
            );

            $overtimeMinutes = $records->sum(function ($record) {
                $work = $this->calculateWorkMinutes($record);
                return $work > 480 ? $work - 480 : 0;
            });

            return [
                'month' => $monthKey,
                'work_time' => $this->formatMinutesToTime($workMinutes, 'h'),
                'overtime' => $this->formatMinutesToTime($overtimeMinutes, 'h'),
            ];
        });

        return view('attendance.report', [
            'totalWorkTime' => $this->formatMinutesToTime($totalWorkMinutes, 'h'),
            'totalOvertime' => $this->formatMinutesToTime($totalOvertimeMinutes, 'h'),
            'averageWorkTime' => $this->formatMinutesToTime($averageWorkMinutes, 'h'),
            'lateCount' => $lateCount,
            'earlyLeaveCount' => $earlyLeaveCount,
            'longWorkCount' => $longWorkCount,
            'monthlyData' => $monthlyData,
        ]);
    }
}
