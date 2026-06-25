<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Models\User;

#[Fillable(['attendance_record_id', 'user_id', 'new_clock_in', 'new_clock_out', 'new_comment', 'is_approved'])]
class StampCorrectionRequest extends Model
{
    public function correctionBreaks()
    {
        return $this->hasMany(CorrectionBreak::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    protected function casts(): array
    {
        return [
            'new_clock_in' => 'datetime',
            'new_clock_out' => 'datetime',
        ];
    }
}
