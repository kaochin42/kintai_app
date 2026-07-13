<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    // 1. 一般ユーザーが正常にログアウトできる
    public function test_general_user_can_logout()
    {
        $user = User::factory()->create([
            'admin_status' => false,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    // 2. 管理者が正常にログアウトできる
    public function test_admin_can_logout()
    {
        $admin = User::factory()->create([
            'admin_status' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post('/admin/logout');

        $response->assertRedirect('/admin/login');
        $this->assertGuest();
    }
}
