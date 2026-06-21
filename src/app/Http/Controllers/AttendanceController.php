<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\CalculatesAttendance;
use Carbon\Carbon;

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

        return view('attendance.detail', [
            'attendanceRecord' => $attendanceRecord,
        ]);
    }
}
