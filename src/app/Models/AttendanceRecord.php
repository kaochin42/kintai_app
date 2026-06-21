<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Models\User;

#[Fillable(['user_id', 'date', 'clock_in', 'clock_out', 'comment'])]
class AttendanceRecord extends Model
{
    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function stampCorrectionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }

    public function attendanceBreaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
