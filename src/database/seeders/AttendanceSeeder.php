<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\AttendanceBreak;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user1 = User::where('email', 'user1@example.com')->first();
        $user2 = User::where('email', 'user2@example.com')->first();

        // user2：シンプルな通常勤務（過去2ヶ月〜今月末）
        $startDate = Carbon::now()->subMonths(2)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        $date = $startDate->copy();

        while ($date->lte($endDate)) {
            if (!$date->isWeekend()) {
                $record = AttendanceRecord::create([
                    'user_id' => $user2->id,
                    'date' => $date->format('Y-m-d'),
                    'clock_in' => '09:00:00',
                    'clock_out' => '18:00:00',
                    'comment' => null,
                ]);
                AttendanceBreak::create([
                    'attendance_record_id' => $record->id,
                    'break_in' => '12:00:00',
                    'break_out' => '13:00:00',
                ]);
            }
            $date->addDay();
        }

        // user1：過去5ヶ月（各月平日15日の通常勤務）
        for ($m = 5; $m >= 1; $m--) {
            $monthStart = Carbon::now()->subMonths($m)->startOfMonth();
            $count = 0;
            $d = $monthStart->copy();

            while ($count < 15) {
                if (!$d->isWeekend()) {
                    $record = AttendanceRecord::create([
                        'user_id' => $user1->id,
                        'date' => $d->format('Y-m-d'),
                        'clock_in' => '09:00:00',
                        'clock_out' => '18:00:00',
                        'comment' => null,
                    ]);
                    AttendanceBreak::create([
                        'attendance_record_id' => $record->id,
                        'break_in' => '12:00:00',
                        'break_out' => '13:00:00',
                    ]);
                    $count++;
                }
                $d->addDay();
            }
        }

        // user1：当月17日分のパターン
        $patterns = [
            ['clock_in' => '09:00:00', 'clock_out' => '18:00:00', 'count' => 10], // 通常
            ['clock_in' => '09:00:00', 'clock_out' => '20:00:00', 'count' => 3],  // 残業
            ['clock_in' => '09:30:00', 'clock_out' => '18:00:00', 'count' => 2],  // 遅刻
            ['clock_in' => '09:00:00', 'clock_out' => '17:00:00', 'count' => 1],  // 早退
            ['clock_in' => '08:00:00', 'clock_out' => '21:00:00', 'count' => 1],  // 長時間労働
        ];

        $currentMonthStart = Carbon::now()->startOfMonth();
        $today = Carbon::today();
        $d = $currentMonthStart->copy();

        foreach ($patterns as $pattern) {
            $count = 0;
            while ($count < $pattern['count']) {
                if (!$d->isWeekend() && !$d->isSameDay($today)) {
                    $record = AttendanceRecord::create([
                        'user_id' => $user1->id,
                        'date' => $d->format('Y-m-d'),
                        'clock_in' => $pattern['clock_in'],
                        'clock_out' => $pattern['clock_out'],
                        'comment' => null,
                    ]);
                    AttendanceBreak::create([
                        'attendance_record_id' => $record->id,
                        'break_in' => '12:00:00',
                        'break_out' => '13:00:00',
                    ]);
                    $count++;
                }
                $d->addDay();
            }
        }
    }
}
