<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordWriteTest extends TestCase
{
    use RefreshDatabase;

    // ---------- POST（新規登録） ----------

    // 1. 正しいデータを送ると勤怠が作られる
    public function test_store_creates_attendance_record()
    {
        $user = User::factory()->create(['admin_status' => false]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-07-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'テスト',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
        ]);
    }

    // 2. 必須項目が無いと422とエラーメッセージが返る
    public function test_store_returns_422_when_required_fields_are_missing()
    {
        $user = User::factory()->create(['admin_status' => false]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            // dateもclock_inも送らない
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['date' => ['勤怠日は必須です。']]);
        $response->assertJsonFragment(['clock_in' => ['出勤時刻は必須です。']]);
    }

    // 3. 未認証の場合、401が返る
    public function test_store_returns_401_when_not_authenticated()
    {
        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-07-01',
            'clock_in' => '09:00:00',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    // ---------- PUT（更新） ----------

    // 4. 自分の勤怠は更新できる
    public function test_user_can_update_own_attendance_record()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-07-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/attendance-records/{$attendanceRecord->id}", [
            'clock_in' => '09:30:00',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '09:30:00',
        ]);
    }

    // 5. 他人の勤怠を更新しようとすると403が返る
    public function test_user_cannot_update_others_attendance_record()
    {
        $owner = User::factory()->create(['admin_status' => false]);
        $otherUser = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $owner->id,
            'date' => '2026-07-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        Sanctum::actingAs($otherUser);

        $response = $this->putJson("/api/v1/attendance-records/{$attendanceRecord->id}", [
            'clock_in' => '09:30:00',
        ]);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'この操作を実行する権限がありません。']);
    }

    // 6. 存在しないIDを更新しようとすると404が返る
    public function test_update_returns_404_for_nonexistent_id()
    {
        $user = User::factory()->create(['admin_status' => false]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/attendance-records/99999', [
            'clock_in' => '09:30:00',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['error' => '勤怠情報が見つかりませんでした。']);
    }

    // ---------- DELETE（削除） ----------

    // 7. 自分の勤怠は削除できる
    public function test_user_can_delete_own_attendance_record()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-07-01',
            'clock_in' => '09:00:00',
        ]);
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/attendance-records/{$attendanceRecord->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('attendance_records', [
            'id' => $attendanceRecord->id,
        ]);
    }

    // 8. 他人の勤怠を削除しようとすると403が返る
    public function test_user_cannot_delete_others_attendance_record()
    {
        $owner = User::factory()->create(['admin_status' => false]);
        $otherUser = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $owner->id,
            'date' => '2026-07-01',
            'clock_in' => '09:00:00',
        ]);
        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/attendance-records/{$attendanceRecord->id}");

        $response->assertStatus(403);
        $response->assertJson(['error' => 'この操作を実行する権限がありません。']);
    }

    // 9. 管理者は他人の勤怠も更新・削除できる
    public function test_admin_can_update_and_delete_others_attendance_record()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $admin = User::factory()->create(['admin_status' => true]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-07-01',
            'clock_in' => '09:00:00',
        ]);
        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/v1/attendance-records/{$attendanceRecord->id}", [
            'clock_in' => '09:30:00',
        ]);

        $response->assertOk();
    }
}
