<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecordApiTest extends TestCase
{
    use RefreshDatabase;

    // 1. 勤怠一覧がJSONで取得できる
    public function test_index_returns_attendance_records_with_pagination()
    {
        $user = User::factory()->create(['admin_status' => false]);
        AttendanceRecord::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/v1/attendance-records');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    // 2. user_idで絞り込みができる
    public function test_index_can_filter_by_user_id()
    {
        $user1 = User::factory()->create(['admin_status' => false]);
        $user2 = User::factory()->create(['admin_status' => false]);
        AttendanceRecord::factory()->create(['user_id' => $user1->id]);
        AttendanceRecord::factory()->create(['user_id' => $user2->id]);

        $response = $this->getJson('/api/v1/attendance-records?user_id=' . $user1->id);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    // 3. monthで絞り込みができる
    public function test_index_can_filter_by_month()
    {
        $user = User::factory()->create(['admin_status' => false]);
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-05-01',
            'clock_in' => '09:00:00',
        ]);
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-06-01',
            'clock_in' => '09:00:00',
        ]);

        $response = $this->getJson('/api/v1/attendance-records?month=2026-05');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    // 4. 勤怠詳細がJSONで取得できる
    public function test_show_returns_attendance_detail()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->getJson("/api/v1/attendance-records/{$attendanceRecord->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $attendanceRecord->id);
    }

    // 5. 存在しないIDの場合、404とエラーメッセージが返る
    public function test_show_returns_404_for_nonexistent_id()
    {
        $response = $this->getJson('/api/v1/attendance-records/99999');

        $response->assertStatus(404);
        $response->assertJson(['error' => '勤怠情報が見つかりませんでした。']);
    }
}
