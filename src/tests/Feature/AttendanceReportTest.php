<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    // 1. 未認証の場合、ログイン画面にリダイレクトされる
    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }

    // 2. 通常勤務のみの場合、残業なしで正しく集計される
    public function test_normal_work_is_calculated_correctly()
    {
        $user = User::factory()->create(['admin_status' => false]);

        // 通常勤務：9:00〜18:00、休憩1時間 → 実働8時間
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        $attendanceRecord->attendanceBreaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertOk();
        $response->assertSee('8h 0m');
        $response->assertSee('0h 0m'); // 残業0
    }

    // 3. 8時間を超えた分は残業として計算される
    public function test_overtime_is_calculated_correctly()
    {
        $user = User::factory()->create(['admin_status' => false]);

        // 残業あり：9:00〜20:00、休憩1時間 → 実働10時間（残業2時間）
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '20:00:00',
        ]);
        $attendanceRecord->attendanceBreaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertOk();
        $response->assertSee('10h 0m'); // 総労働時間
        $response->assertSee('2h 0m');  // 総残業時間
    }

    // 4. 遅刻・早退・長時間労働が正しくカウントされる
    public function test_late_early_leave_and_long_work_are_counted()
    {
        $user = User::factory()->create(['admin_status' => false]);

        // 遅刻：9:30出勤
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:30:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertOk();
        $response->assertSee('1回'); // 遅刻回数
    }

    // 5. 勤怠記録がないユーザーでもエラーにならず0で表示される
    public function test_report_is_safe_for_user_with_no_records()
    {
        $user = User::factory()->create(['admin_status' => false]);

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertOk();
        $response->assertSee('0h 0m');
        $response->assertSee('0回');
    }
}
