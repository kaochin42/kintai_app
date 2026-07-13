<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampCorrectionRequestListTest extends TestCase
{
    use RefreshDatabase;

    // 1. 承認待ちに自分がやった申請が全部表示される
    public function test_pending_requests_are_displayed()
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
            'new_comment' => '電車遅延のため',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        $response->assertOk();
        $response->assertSee('電車遅延のため');
    }

    // 2. 承認済みの申請が全部表示される
    public function test_approved_requests_are_displayed()
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
            'new_comment' => '承認済みの申請です',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list?tab=approved');

        $response->assertOk();
        $response->assertSee('承認済みの申請です');
    }

    // 3. 詳細を押したら勤怠詳細画面に飛べる
    public function test_can_access_attendance_detail_from_request_list()
    {
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

        $response = $this->actingAs($user)->get("/stamp_correction_request/approve/{$stampCorrectionRequest->id}");

        $response->assertRedirect("/attendance/detail/{$attendanceRecord->id}");
    }
}
