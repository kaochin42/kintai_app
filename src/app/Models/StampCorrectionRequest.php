<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attendance_record_id', 'user_id', 'new_clock_in', 'new_clock_out', 'new_comment', 'is_approved'])]
class StampCorrectionRequest extends Model
{
    public function correctionBreaks(): HasMany
    {
        return $this->hasMany(CorrectionBreak::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceRecord(): BelongsTo
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
