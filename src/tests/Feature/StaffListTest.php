<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffListTest extends TestCase
{
    use RefreshDatabase;

    // 1. 全一般ユーザーの氏名とメールアドレスが表示される
    public function test_all_general_users_name_and_email_are_displayed()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create([
            'admin_status' => false,
            'name' => 'テスト太郎',
            'email' => 'taro@test.com',
        ]);

        $response = $this->actingAs($admin)->get('/admin/staff/list');

        $response->assertOk();
        $response->assertSee('テスト太郎');
        $response->assertSee('taro@test.com');
    }

    // 2. 選択したユーザーの勤怠情報が正しく表示される
    public function test_selected_user_attendance_is_displayed()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false]);
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}");

        $response->assertOk();
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    // 3. 前月ボタンを押したら前の月の情報が出る
    public function test_previous_month_is_displayed()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false]);
        $prevMonth = now()->subMonth();
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $prevMonth->copy()->day(1),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}?month=" . $prevMonth->format('Y-m'));

        $response->assertOk();
        $response->assertSee($prevMonth->format('Y/m'));
    }

    // 4. 翌月ボタンを押したら次の月の情報が出る
    public function test_next_month_is_displayed()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false]);
        $nextMonth = now()->addMonth();
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $nextMonth->copy()->day(1),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}?month=" . $nextMonth->format('Y-m'));

        $response->assertOk();
        $response->assertSee($nextMonth->format('Y/m'));
    }

    // 5. CSV出力を押すと選択した月の勤怠一覧がダウンロードできる
    public function test_csv_export_downloads_attendance_data()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false]);
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}/csv");

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }
}
