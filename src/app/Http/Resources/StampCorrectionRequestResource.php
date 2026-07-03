<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StampCorrectionRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attendance_record_id' => $this->attendance_record_id,
            'new_clock_in' => $this->new_clock_in?->format('H:i:s'),
            'new_clock_out' => $this->new_clock_out?->format('H:i:s'),
            'new_comment' => $this->new_comment,
            'is_approved' => $this->is_approved,
        ];
    }
}
