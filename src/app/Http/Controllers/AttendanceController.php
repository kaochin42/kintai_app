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

class AttendanceController extends Controller
{
    use CalculatesAttendance;

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

    public function store(Request $request)
    {
        $action = $request->input('action');

        if ($action === 'clock_in') {
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

    public function show($id)
    {
        $attendanceRecord = AttendanceRecord::where('id', $id)
            ->where('user_id', Auth::id())
            ->with('attendanceBreaks')
            ->firstOrFail();

        $pendingRequest = StampCorrectionRequest::where('attendance_record_id', $attendanceRecord->id)
            ->where('is_approved', false)
            ->with('correctionBreaks')
            ->first();

        return view('attendance.detail', [
            'attendanceRecord' => $attendanceRecord,
            'hasPendingRequest' => !is_null($pendingRequest),
            'pendingRequest' => $pendingRequest,
        ]);
    }

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

        foreach ($request->breaks as $break) {
            if (!$break['break_in'] && !$break['break_out']) {
                continue;
            }

            CorrectionBreak::create([
                'stamp_correction_request_id' => $stampCorrectionRequest->id,
                'new_break_in' => $break['break_in'],
                'new_break_out' => $break['break_out'],
            ]);
        }

        return redirect('/stamp_correction_request/list');
    }

    public function report()
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
        $totalWorkMinutes = $attendanceRecords->sum(function ($record) {
            return $this->calculateWorkMinutes($record);
        });

        $totalOvertimeMinutes = $attendanceRecords->sum(function ($record) {
            $workMinutes = $this->calculateWorkMinutes($record);
            return $workMinutes > 480 ? $workMinutes - 480 : 0;
        });

        $workedDays = $attendanceRecords->filter(function ($record) {
            return $record->clock_in !== null;
        })->count();

        $averageWorkMinutes = $workedDays > 0
            ? intdiv($totalWorkMinutes, $workedDays)
            : 0;

        // 当月の異常検知
        $currentMonthRecords = $attendanceRecords->filter(function ($record) {
            return $record->date->format('Y-m') === now()->format('Y-m');
        });

        $lateCount = $currentMonthRecords->filter(function ($record) {
            if (!$record->clock_in) return false;
            return $record->clock_in->gt(Carbon::parse('09:00'));
        })->count();

        $earlyLeaveCount = $currentMonthRecords->filter(function ($record) {
            if (!$record->clock_out) return false;
            return $record->clock_out->lt(Carbon::parse('18:00'));
        })->count();

        $longWorkCount = $currentMonthRecords->filter(function ($record) {
            return $this->calculateWorkMinutes($record) > 600;
        })->count();

        // 月次推移
        $monthlyRecords = $attendanceRecords->groupBy(function ($record) {
            return $record->date->format('Y-m');
        });

        $monthlyData = [];
        $currentMonth = now()->subMonths(5)->startOfMonth();

        for ($i = 0; $i < 6; $i++) {
            $monthKey = $currentMonth->format('Y-m');
            $records = $monthlyRecords->get($monthKey, collect());

            $workMinutes = $records->sum(function ($record) {
                return $this->calculateWorkMinutes($record);
            });

            $overtimeMinutes = $records->sum(function ($record) {
                $work = $this->calculateWorkMinutes($record);
                return $work > 480 ? $work - 480 : 0;
            });

            $monthlyData[] = [
                'month' => $monthKey,
                'work_time' => $this->formatMinutesToTime($workMinutes, 'h'),
                'overtime' => $this->formatMinutesToTime($overtimeMinutes, 'h'),
            ];

            $currentMonth->addMonth();
        }

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
