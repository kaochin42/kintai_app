<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        $users = User::where('admin_status', false)->get();
        $startDate = Carbon::now()->subMonths(2)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        foreach ($users as $user) {
            $date = $startDate->copy();
            while ($date->lte($endDate)) {
                // 土日はスキップ
                if (!$date->isWeekend()) {
                    $record = AttendanceRecord::create([
                        'user_id' => $user->id,
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
        }
    }
}
