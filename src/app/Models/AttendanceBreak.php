<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attendance_record_id', 'break_in', 'break_out'])]
class AttendanceBreak extends Model
{
    protected function casts(): array
    {
        return [
            'break_in' => 'datetime',
            'break_out' => 'datetime',
        ];
    }
    
    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }
}
