<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
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
}
