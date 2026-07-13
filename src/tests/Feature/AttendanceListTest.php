<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    // 1. 自分の勤怠情報が全部表示される
    public function test_own_attendance_records_are_displayed()
    {
        $user = User::factory()->create(['admin_status' => false]);
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertOk();
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    // 2. ページを開いたら今の月が表示される
    public function test_current_month_is_displayed_by_default()
    {
        $user = User::factory()->create(['admin_status' => false]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertOk();
        $response->assertSee(now()->format('Y/m'));
    }

    // 3. 前月ボタンを押したら前の月の情報が出る
    public function test_previous_month_is_displayed()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $prevMonth = now()->subMonth();
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $prevMonth->copy()->day(1),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=' . $prevMonth->format('Y-m'));

        $response->assertOk();
        $response->assertSee($prevMonth->format('Y/m'));
    }

    // 4. 翌月ボタンを押したら次の月の情報が出る
    public function test_next_month_is_displayed()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $nextMonth = now()->addMonth();
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $nextMonth->copy()->day(1),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=' . $nextMonth->format('Y-m'));

        $response->assertOk();
        $response->assertSee($nextMonth->format('Y/m'));
    }

    // 5. 詳細ボタンを押したら詳細ページに飛べる
    public function test_can_access_detail_page_from_list()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get("/attendance/detail/{$attendanceRecord->id}");

        $response->assertOk();
    }
}