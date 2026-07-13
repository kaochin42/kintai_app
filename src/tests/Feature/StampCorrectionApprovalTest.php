<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampCorrectionApprovalTest extends TestCase
{
    use RefreshDatabase;

    // 1. 申請詳細画面の内容が動線上選んだ情報と一致している
    public function test_approval_detail_matches_selected_request()
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
            'new_clock_out' => '19:00:00',
            'new_comment' => '電車遅延のため',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($admin)->get("/stamp_correction_request/approve/{$stampCorrectionRequest->id}");

        $response->assertOk();
        $response->assertSee('電車遅延のため');
        $response->assertSee('09:30');
        $response->assertSee('19:00');
    }

    // 2. 承認処理を行うと、勤怠情報が申請内容の通りに更新される
    public function test_approval_updates_attendance_record()
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
            'new_clock_out' => '19:00:00',
            'new_comment' => '電車遅延のため',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($admin)->post("/stamp_correction_request/approve/{$stampCorrectionRequest->id}");

        $response->assertRedirect('/stamp_correction_request/list');
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'comment' => '電車遅延のため',
        ]);
    }

    // 3. 承認処理を行うと、申請が「承認済み」に変わる
    public function test_approval_marks_request_as_approved()
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
            'new_clock_out' => '19:00:00',
            'new_comment' => '電車遅延のため',
            'is_approved' => false,
        ]);

        $this->actingAs($admin)->post("/stamp_correction_request/approve/{$stampCorrectionRequest->id}");

        $this->assertDatabaseHas('stamp_correction_requests', [
            'id' => $stampCorrectionRequest->id,
            'is_approved' => true,
        ]);
    }

    // 4. 承認すると、一般ユーザーの申請一覧でも「承認済み」に移動している
    public function test_approved_request_moves_to_approved_tab_for_general_user()
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
            'new_clock_out' => '19:00:00',
            'new_comment' => '電車遅延のため',
            'is_approved' => false,
        ]);

        $this->actingAs($admin)->post("/stamp_correction_request/approve/{$stampCorrectionRequest->id}");

        $response = $this->actingAs($user)->get('/stamp_correction_request/list?tab=approved');

        $response->assertOk();
        $response->assertSee('電車遅延のため');
    }
}
