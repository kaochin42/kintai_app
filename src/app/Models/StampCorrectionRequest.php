<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Models\User;

#[Fillable(['new_clock_in', 'new_clock_out', 'new_comment'])]
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
}
