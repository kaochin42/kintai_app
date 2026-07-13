<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    // 1. 名前未入力
    public function test_name_is_required()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    // 2. メールアドレス未入力
    public function test_email_is_required()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    // 3. パスワード未入力
    public function test_password_is_required()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@test.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    // 4. パスワード7文字以下
    public function test_password_min_length()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@test.com',
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    // 5. 確認用パスワード不一致
    public function test_password_confirmation_mismatch()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@test.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors(['password_confirmation' => 'パスワードと一致しません']);
    }

    // 6. 全項目正しく入力
    public function test_register_success()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/email/verify');
    }
}
