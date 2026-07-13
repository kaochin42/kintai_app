<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStampTest extends TestCase
{
    use RefreshDatabase;

    // 1. 勤務外の場合、ステータスが「勤務外」と表示される
    public function test_status_is_off_duty_when_no_record()
    {
        $user = User::factory()->create(['admin_status' => false]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('勤務外');
    }

    // 2. 出勤中の場合、ステータスが「出勤中」と表示される
    public function test_status_is_working_after_clock_in()
    {
        $user = User::factory()->create(['admin_status' => false]);
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤中');
    }

    // 3. 休憩中の場合、ステータスが「休憩中」と表示される
    public function test_status_is_on_break_after_break_in()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
        ]);
        $attendanceRecord->attendanceBreaks()->create([
            'break_in' => '12:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩中');
    }

    // 4. 退勤済の場合、ステータスが「退勤済」と表示される
    public function test_status_is_finished_after_clock_out()
    {
        $user = User::factory()->create(['admin_status' => false]);
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('退勤済');
    }

    // 5. 出勤ボタンを押すとステータスが「出勤中」になり、出勤時刻が記録される
    public function test_clock_in_creates_attendance_record()
    {
        $user = User::factory()->create(['admin_status' => false]);

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        $response->assertRedirect('/attendance');
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => today()->format('Y-m-d') . ' 00:00:00',
        ]);
    }

    // 6. 休憩入ボタンを押すとステータスが「休憩中」になり、休憩開始時刻が記録される
    public function test_break_in_creates_attendance_break()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        $response->assertRedirect('/attendance');
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_record_id' => $attendanceRecord->id,
        ]);
    }

    // 7. 休憩戻ボタンを押すとステータスが「出勤中」に戻り、休憩終了時刻が記録される
    public function test_break_out_updates_attendance_break()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
        ]);
        $attendanceBreak = $attendanceRecord->attendanceBreaks()->create([
            'break_in' => '12:00:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'break_out',
        ]);

        $response->assertRedirect('/attendance');
        $this->assertDatabaseHas('attendance_breaks', [
            'id' => $attendanceBreak->id,
        ]);
        $this->assertNotNull($attendanceBreak->fresh()->break_out);
    }

    // 8. 休憩は1日に何回でもできる
    public function test_break_can_be_taken_multiple_times()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
        ]);

        // 1回目の休憩入・休憩戻
        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);
        $this->actingAs($user)->post('/attendance', ['action' => 'break_out']);

        // 2回目の休憩入
        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);

        $this->assertEquals(2, $attendanceRecord->attendanceBreaks()->count());
    }

    // 9. 退勤ボタンを押すとステータスが「退勤済」になり、退勤時刻が記録される
    public function test_clock_out_updates_attendance_record()
    {
        $user = User::factory()->create(['admin_status' => false]);
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_out',
        ]);

        $response->assertRedirect('/attendance');
        $this->assertNotNull($attendanceRecord->fresh()->clock_out);
    }
}
