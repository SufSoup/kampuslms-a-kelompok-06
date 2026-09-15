<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');

Route::get('/courses/create', function () {
    return 'Ini halaman tambah mata kuliah';
})->name('courses.create');

Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/   /{course}', [CourseController::class, 'show'])->name('courses.show');