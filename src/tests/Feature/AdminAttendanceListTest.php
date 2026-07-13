<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    // 1. その日の全ユーザーの勤怠情報が正確に表示される
    public function test_all_users_attendance_is_displayed()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false, 'name' => 'テスト太郎']);
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertOk();
        $response->assertSee('テスト太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    // 2. 遷移した時に今の日付が表示される
    public function test_current_date_is_displayed_by_default()
    {
        $admin = User::factory()->create(['admin_status' => true]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertOk();
        $response->assertSee(today()->format('Y/m/d'));
    }

    // 3. 前日ボタンを押したら前の日の情報が出る
    public function test_previous_day_is_displayed()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false, 'name' => 'テスト太郎']);
        $yesterday = today()->subDay();
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $yesterday,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=' . $yesterday->format('Y-m-d'));

        $response->assertOk();
        $response->assertSee($yesterday->format('Y/m/d'));
        $response->assertSee('テスト太郎');
    }

    // 4. 翌日ボタンを押したら次の日の情報が出る
    public function test_next_day_is_displayed()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false, 'name' => 'テスト太郎']);
        $tomorrow = today()->addDay();
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $tomorrow,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=' . $tomorrow->format('Y-m-d'));

        $response->assertOk();
        $response->assertSee($tomorrow->format('Y/m/d'));
        $response->assertSee('テスト太郎');
    }

    // 5. 詳細を押したら勤怠詳細画面に飛べる
    public function test_can_access_detail_page_from_list()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->get("/admin/attendance/{$attendanceRecord->id}");

        $response->assertOk();
    }
}
