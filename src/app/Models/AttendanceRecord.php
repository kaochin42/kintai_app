<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'date', 'clock_in', 'clock_out', 'comment'])]
class AttendanceRecord extends Model
{
    use HasFactory;
    
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'datetime:H:i',
            'clock_out' => 'datetime:H:i',
        ];
    }

    public function stampCorrectionRequests(): HasMany
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }

    public function attendanceBreaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
