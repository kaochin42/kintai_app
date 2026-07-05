<?php

namespace App\Providers;

use App\Models\AttendanceRecord;
use App\Policies\AttendanceRecordPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(AttendanceRecord::class, AttendanceRecordPolicy::class);
    }
}
