<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    // 1. 会員登録後、認証メールが送信される
    public function test_verification_email_is_sent_after_register()
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'test@test.com')->first();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    // 2. メール認証誘導画面が表示される（未認証ユーザーがアクセスした場合）
    public function test_verification_notice_screen_is_displayed()
    {
        $user = User::factory()->create([
            'admin_status' => false,
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertOk();
    }

    // 3. メール認証を完了すると、勤怠登録画面に遷移する
    public function test_email_can_be_verified_and_redirects_to_attendance()
    {
        $user = User::factory()->create([
            'admin_status' => false,
            'email_verified_at' => null,
        ]);

        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect('/attendance?verified=1');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
