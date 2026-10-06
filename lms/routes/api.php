<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LearningResourceController;
use Illuminate\Support\Facades\Route;

// Modul Autentikasi API
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Endpoint yang membutuhkan token Sanctum
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    // Fitur 17: Kurasi Sumber Belajar Tambahan & Penanda Baca
    Route::get('/courses/{course}/resources', [LearningResourceController::class, 'index']);
    Route::post('/courses/{course}/resources', [LearningResourceController::class, 'store']);
    Route::get('/resources/{resource}', [LearningResourceController::class, 'show']);
    Route::post('/resources/{resource}/toggle-read', [LearningResourceController::class, 'toggleRead']);
});
