<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;


class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'ユーザー1（一般）',
                'email' => 'user1@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'admin_status' => false,
            ],
            [
                'name' => 'ユーザー2（一般）',
                'email' => 'user2@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'admin_status' => false,
            ],
            [
                'name' => 'ユーザー3（管理者）',
                'email' => 'user3@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'admin_status' => true,
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        $this->call(AttendanceSeeder::class);
    }
}
