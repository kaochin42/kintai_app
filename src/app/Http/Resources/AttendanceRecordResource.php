<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\AttendanceBreakResource;
use App\Http\Resources\StampCorrectionRequestResource;

class AttendanceRecordResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn() => $this->user->name),
            'date' => $this->date->format('Y-m-d'),
            'clock_in' => $this->clock_in?->format('H:i:s'),
            'clock_out' => $this->clock_out?->format('H:i:s'),
            'total_time' => $this->calculateTotalTime(),
            'total_break_time' => $this->calculateTotalBreakTime(),
            'comment' => $this->comment,
            'breaks' => AttendanceBreakResource::collection($this->whenLoaded('attendanceBreaks')),
            'applications' => StampCorrectionRequestResource::collection($this->whenLoaded('stampCorrectionRequests')),
        ];
    }

    /**
     * 出勤〜退勤時間から休憩時間を差し引いた実働時間を計算する。
     *
     * @return string|null 実働時間（HH:MM形式）。出勤または退勤が未打刻の場合はnull
     */
    private function calculateTotalTime(): ?string
    {
        if (!$this->clock_in || !$this->clock_out) return null;
        $breakMinutes = $this->calculateTotalBreakMinutes();
        $totalMinutes = $this->clock_in->diffInMinutes($this->clock_out) - $breakMinutes;
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        return str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT);
    }

    /**
     * 休憩時間の合計を計算する。
     *
     * @return string 休憩時間の合計（HH:MM形式）
     */
    private function calculateTotalBreakTime(): ?string
    {
        $minutes = $this->calculateTotalBreakMinutes();
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        return str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($mins, 2, '0', STR_PAD_LEFT);
    }

    /**
     * 紐づく休憩レコード（attendanceBreaks）の合計時間を分単位で計算する。
     * リレーションがeager loadされていない場合は0を返す。
     *
     * @return int 休憩時間の合計（分）
     */
    private function calculateTotalBreakMinutes(): int
    {
        if (!$this->relationLoaded('attendanceBreaks')) return 0;
        return $this->attendanceBreaks->sum(function ($break) {
            if (!$break->break_in || !$break->break_out) return 0;
            return $break->break_in->diffInMinutes($break->break_out);
        });
    }
}
