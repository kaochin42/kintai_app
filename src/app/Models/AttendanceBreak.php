<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['attendance_record_id', 'break_in', 'break_out'])]
class AttendanceBreak extends Model
{
    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }
}
