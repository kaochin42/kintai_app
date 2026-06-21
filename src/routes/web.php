<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StampCorrectionRequestController as AdminStampCorrectionRequestController;

// 一般ユーザー（認証不要）
Route::get('/register', [RegisterController::class, 'index']);
Route::post('/register', [RegisterController::class, 'store']);
Route::get('/login', [LoginController::class, 'index'])->name('register');
Route::post('/login', [LoginController::class, 'store'])->name('login');

// 一般ユーザー（認証あり）
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy']);
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/list', [AttendanceController::class, 'list']);
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show']);
    Route::put('/attendance/detail/{id}', [AttendanceController::class, 'update']);
    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index']);
});


//管理者（認証なし）
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'index']);
    Route::post('/login', [AdminLoginController::class, 'store'])->name('admin.login');
});

// 管理者（認証あり）
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::post('/logout', [AdminLoginController::class, 'destroy']);
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index']);
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show']);
    Route::put('/attendance/{id}', [AdminAttendanceController::class, 'update']);
    Route::get('/staff/list', [StaffController::class, 'index']);
    Route::get('/attendance/staff/{id}', [StaffController::class, 'show']);
    Route::get('/stamp_correction_request/list', [AdminStampCorrectionRequestController::class, 'index']);
    Route::get('/stamp_correction_request/approve/{id}', [AdminStampCorrectionRequestController::class, 'show']);
    Route::post('/stamp_correction_request/approve/{id}', [AdminStampCorrectionRequestController::class, 'update']);
});
