<?php

namespace App\Traits;

use Carbon\Carbon;

trait CalculatesAttendance
{
    /**
     * 休憩時間の合計を「分」で計算する
     * （複数回休憩した場合は全部合計する。休憩中で終了してない場合は0として扱う）
     */
    private function calculateBreakMinutes($attendanceRecord)
    {
        return $attendanceRecord->attendanceBreaks->sum(function ($break) {
            if (!$break->break_out) {
                return 0;
            }
            return Carbon::parse($break->break_in)->diffInMinutes(Carbon::parse($break->break_out));
        });
    }

    /**
     * 実働時間を「分」で計算する（出勤〜退勤の時間から、休憩時間を引く）
     */
    private function calculateWorkMinutes($attendanceRecord)
    {
        if (!$attendanceRecord->clock_out) {
            return 0;
        }

        $totalMinutes = Carbon::parse($attendanceRecord->clock_in)->diffInMinutes(Carbon::parse($attendanceRecord->clock_out));
        $breakMinutes = $this->calculateBreakMinutes($attendanceRecord);

        return $totalMinutes - $breakMinutes;
    }

    /**
     * 「分」を「時:分」の文字列に変換する（例: 90分 → "1:30"）
     */
    private function formatMinutesToTime($minutes, $format = 'colon')
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($format === 'h') {
            return $hours . 'h ' . $remainingMinutes . 'm';
        }

        return $hours . ':' . str_pad($remainingMinutes, 2, '0', STR_PAD_LEFT);
    }
}
