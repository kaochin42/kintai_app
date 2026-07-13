<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStampCorrectionRequestListTest extends TestCase
{
    use RefreshDatabase;

    // 1. 全ユーザーの未承認の修正申請が表示される
    public function test_all_pending_requests_are_displayed()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false, 'name' => 'テスト太郎']);
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
            'new_comment' => '電車遅延のため',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list');

        $response->assertOk();
        $response->assertSee('テスト太郎');
        $response->assertSee('電車遅延のため');
    }

    // 2. 全ユーザーの承認済みの修正申請が表示される
    public function test_all_approved_requests_are_displayed()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false, 'name' => 'テスト太郎']);
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
            'new_comment' => '承認済みの申請です',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list?tab=approved');

        $response->assertOk();
        $response->assertSee('テスト太郎');
        $response->assertSee('承認済みの申請です');
    }

    // 3. 詳細を押すと申請詳細画面に遷移する
    public function test_can_access_approval_detail_from_request_list()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        $stampCorrectionRequest = $attendanceRecord->stampCorrectionRequests()->create([
            'user_id' => $user->id,
            'new_clock_in' => '09:30:00',
            'new_clock_out' => '18:00:00',
            'new_comment' => '電車遅延のため',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($admin)->get("/stamp_correction_request/approve/{$stampCorrectionRequest->id}");

        $response->assertOk();
        $response->assertSee('電車遅延のため');
    }
}
