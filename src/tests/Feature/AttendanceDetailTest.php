<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    // 1. 名前がログインユーザーの名前になっている
    public function test_name_matches_logged_in_user()
    {
        $user = User::factory()->create(['admin_status' => false, 'name' => 'テスト太郎']);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get("/attendance/detail/{$attendanceRecord->id}");

        $response->assertOk();
        $response->assertSee('テスト太郎');
    }

    // 2. 日付が選択した日付になっている
    public function test_date_matches_selected_record()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-07-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get("/attendance/detail/{$attendanceRecord->id}");

        $response->assertOk();
        $response->assertSee('2026年');
        $response->assertSee('7月10日');
    }

    // 3. 出勤・退勤の時間が打刻と一致している
    public function test_clock_in_and_out_match_record()
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
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);
    }

    // 4. 出勤時間が退勤時間より後の場合、エラーメッセージが表示される
    public function test_clock_in_after_clock_out_shows_error()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->put("/attendance/detail/{$attendanceRecord->id}", [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'comment' => '備考です',
            'breaks' => [
                ['break_in' => '', 'break_out' => ''],
            ],
        ]);

        $response->assertSessionHasErrors(['clock_out' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    // 5. 休憩開始時間が出勤時間より前の場合、エラーメッセージが表示される
    public function test_break_in_before_clock_in_shows_error()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->put("/attendance/detail/{$attendanceRecord->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '備考です',
            'breaks' => [
                ['break_in' => '08:00', 'break_out' => '12:00'],
            ],
        ]);

        $response->assertSessionHasErrors(['breaks.0.break_in' => '休憩時間が不適切な値です']);
    }

    // 6. 休憩終了時間が退勤時間より後の場合、エラーメッセージが表示される
    public function test_break_out_after_clock_out_shows_error()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->put("/attendance/detail/{$attendanceRecord->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '備考です',
            'breaks' => [
                ['break_in' => '12:00', 'break_out' => '19:00'],
            ],
        ]);

        $response->assertSessionHasErrors(['breaks.0.break_out' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    // 7. 備考が空っぽの場合、エラーメッセージが表示される
    public function test_comment_is_required()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->put("/attendance/detail/{$attendanceRecord->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '',
            'breaks' => [
                ['break_in' => '', 'break_out' => ''],
            ],
        ]);

        $response->assertSessionHasErrors(['comment' => '備考を記入してください']);
    }

    // 8. 修正申請を送るとちゃんと登録される
    public function test_correction_request_is_created()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->put("/attendance/detail/{$attendanceRecord->id}", [
            'clock_in' => '09:30',
            'clock_out' => '18:00',
            'comment' => '電車遅延のため遅刻しました',
            'breaks' => [
                ['break_in' => '', 'break_out' => ''],
            ],
        ]);

        $response->assertRedirect('/stamp_correction_request/list');
        $this->assertDatabaseHas('stamp_correction_requests', [
            'attendance_record_id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'new_comment' => '電車遅延のため遅刻しました',
        ]);
    }

    // 9. 承認待ちの場合は修正できず、メッセージが表示される
    public function test_pending_request_cannot_be_edited()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        $attendanceRecord->stampCorrectionRequests()->create([
            'user_id' => $user->id,
            'new_clock_in' => '09:30:00',
            'new_clock_out' => '18:00:00',
            'new_comment' => '修正申請中',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($user)->get("/attendance/detail/{$attendanceRecord->id}");

        $response->assertOk();
        $response->assertSee('承認待ちのため修正はできません。');
    }
}
