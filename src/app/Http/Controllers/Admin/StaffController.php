<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\CalculatesAttendance;
use Carbon\Carbon;
use App\Models\AttendanceRecord;


class StaffController extends Controller
{
    use CalculatesAttendance;
    
    public function index()
    {
        $users = User::where('admin_status', false)->get();

        return view('admin.staff.list', [
            'users' => $users,
        ]);
    }

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
}
