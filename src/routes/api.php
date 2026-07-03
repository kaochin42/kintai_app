<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AttendanceRecordController;

// 認証不要（GET）
Route::prefix('v1')->group(function () {
    Route::get('/attendance-records', [AttendanceRecordController::class, 'index']);
    Route::get('/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'show']);
});

// 認証必要（POST/PUT/DELETE）
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('/attendance-records', [AttendanceRecordController::class, 'store']);
    Route::put('/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'update']);
    Route::delete('/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'destroy']);
});
