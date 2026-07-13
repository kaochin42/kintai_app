<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    // 1. メールアドレス未入力
    public function test_email_is_required()
    {
        User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'admin_status' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    // 2. パスワード未入力
    public function test_password_is_required()
    {
        User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'admin_status' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    // 3. 登録内容と不一致
    public function test_login_fails_with_wrong_credentials()
    {
        User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'admin_status' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'wrong@test.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['password' => 'ログイン情報が登録されていません。']);
    }

    // 4. 正しい情報でログインできる
    public function test_login_success()
    {
        User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'admin_status' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/attendance/list');
    }
}
