<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\LearningResourceController;
use App\Http\Controllers\Api\MaterialController;
use Illuminate\Support\Facades\Route;

// Modul Autentikasi API
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Endpoint yang membutuhkan token Sanctum
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    Route::get('/courses/{course}/materials', [MaterialController::class, 'index']);

    // Fitur 17: Kurasi Sumber Belajar Tambahan & Penanda Baca
    Route::get('/courses/{course}/resources', [LearningResourceController::class, 'index']);
    Route::post('/courses/{course}/resources', [LearningResourceController::class, 'store']);
    Route::get('/resources/{resource}', [LearningResourceController::class, 'show']);
    Route::post('/resources/{resource}/toggle-read', [LearningResourceController::class, 'toggleRead']);
});
