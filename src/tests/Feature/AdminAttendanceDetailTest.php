<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    // 1. 詳細画面の内容が選択したものと一致している
    public function test_detail_matches_selected_record()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false, 'name' => 'テスト太郎']);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->get("/admin/attendance/{$attendanceRecord->id}");

        $response->assertOk();
        $response->assertSee('テスト太郎');
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);
    }

    // 2. 出勤時間が退勤時間より後の場合、エラーメッセージが表示される
    public function test_clock_in_after_clock_out_shows_error()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->put("/admin/attendance/{$attendanceRecord->id}", [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'comment' => '備考です',
            'breaks' => [
                ['break_in' => '', 'break_out' => ''],
            ],
        ]);

        $response->assertSessionHasErrors(['clock_out' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    // 3. 休憩開始時間が出勤時間より前の場合、エラーメッセージが表示される
    public function test_break_in_before_clock_in_shows_error()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->put("/admin/attendance/{$attendanceRecord->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '備考です',
            'breaks' => [
                ['break_in' => '08:00', 'break_out' => '12:00'],
            ],
        ]);

        $response->assertSessionHasErrors(['breaks.0.break_in' => '休憩時間が不適切な値です']);
    }

    // 4. 休憩終了時間が退勤時間より後の場合、エラーメッセージが表示される
    public function test_break_out_after_clock_out_shows_error()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->put("/admin/attendance/{$attendanceRecord->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '備考です',
            'breaks' => [
                ['break_in' => '12:00', 'break_out' => '19:00'],
            ],
        ]);

        $response->assertSessionHasErrors(['breaks.0.break_out' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    // 5. 備考が空っぽの場合、エラーメッセージが表示される
    public function test_comment_is_required()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->put("/admin/attendance/{$attendanceRecord->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '',
            'breaks' => [
                ['break_in' => '', 'break_out' => ''],
            ],
        ]);

        $response->assertSessionHasErrors(['comment' => '備考を記入してください']);
    }

    // 6. 修正すると、一般ユーザーの勤怠情報にも反映される
    public function test_direct_correction_updates_attendance_record()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->put("/admin/attendance/{$attendanceRecord->id}", [
            'clock_in' => '09:30',
            'clock_out' => '19:00',
            'comment' => '管理者が直接修正しました',
            'breaks' => [
                ['break_in' => '12:00', 'break_out' => '13:00'],
            ],
        ]);

        $response->assertRedirect("/admin/attendance/{$attendanceRecord->id}");
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'comment' => '管理者が直接修正しました',
        ]);
    }
}
