<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['stamp_correction_request_id', 'new_break_in', 'new_break_out'])]
class CorrectionBreak extends Model
{
    public function stampCorrectionRequest(): BelongsTo
    {
        return $this->belongsTo(StampCorrectionRequest::class);
    }

    protected function casts(): array
    {
        return [
            'new_break_in' => 'datetime',
            'new_break_out' => 'datetime',
        ];
    }
}
