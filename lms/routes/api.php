<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LearningResourceController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\SubmissionController;
use Illuminate\Support\Facades\Route;

// Modul Autentikasi API
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Endpoint yang membutuhkan token Sanctum
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/courses/{course}/assignments', [AssignmentController::class, 'index']);
    Route::post('/assignments', [AssignmentController::class, 'store']);
    Route::match(['put', 'patch'], '/assignments/{assignment}', [AssignmentController::class, 'update']);
    Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy']);
    Route::get('/assignments/{assignment}/submissions', [SubmissionController::class, 'index']);
    Route::post('/assignments/{assignment}/submissions', [SubmissionController::class, 'store']);
    Route::put('/submissions/{submission}/grade', [SubmissionController::class, 'grade']);

    // Fitur 17: Kurasi Sumber Belajar Tambahan & Penanda Baca
    Route::get('/courses/{course}/resources', [LearningResourceController::class, 'index']);
    Route::post('/courses/{course}/resources', [LearningResourceController::class, 'store']);
    Route::get('/resources/{resource}', [LearningResourceController::class, 'show']);
    Route::post('/resources/{resource}/toggle-read', [LearningResourceController::class, 'toggleRead']);
});
