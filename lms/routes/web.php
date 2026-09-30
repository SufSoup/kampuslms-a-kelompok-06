<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\SubmissionController;
/*
|--------------------------------------------------------------------------
| Route Publik (tidak butuh login)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');

/*
|--------------------------------------------------------------------------
| Route Terproteksi (wajib login)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Grup Admin — prefix: /admin, name prefix: admin.
    | Middleware: auth (sudah di luar) + role:admin
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            // Manajemen Pengguna (hanya admin)
            Route::resource('users', UserController::class);

            // Manajemen Mata Kuliah (hanya admin)
            Route::resource('courses', CourseController::class);
        });

    /*
    |----------------------------------------------------------------------
    | Grup Dosen — prefix: /dosen, name prefix: dosen.
    | Middleware: auth + role:dosen
    |----------------------------------------------------------------------
    */
    Route::middleware('role:dosen')
        ->prefix('dosen')
        ->name('dosen.')
        ->group(function () {
            // Dosen melihat mata kuliah yang ia ampu
            Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
            Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

            // Route bersarang: materi & tugas per mata kuliah
            // scopeBindings() → pastikan {assignment}/{material} benar-benar milik {course}
            Route::scopeBindings()->group(function () {
                Route::resource('courses.assignments', AssignmentController::class)->only(['show'])->shallow();
                // AssignmentController & MaterialController akan ditambahkan Syahid & Yusuf
                // Route::resource('courses.assignments', AssignmentController::class)->shallow();
                // Route::resource('courses.materials',  MaterialController::class)->shallow();
            });
        });

    /*
    |----------------------------------------------------------------------
    | Grup Mahasiswa — prefix: /mahasiswa, name prefix: mahasiswa.
    | Middleware: auth + role:mahasiswa
    |----------------------------------------------------------------------
    */
    Route::middleware('role:mahasiswa')
        ->prefix('mahasiswa')
        ->name('mahasiswa.')
        ->group(function () {
            // Mahasiswa melihat mata kuliah yang ia ikuti
            Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
            Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

            // Route bersarang: submission mahasiswa
            // scopeBindings() → pastikan {submission} benar-benar milik resource parent
            Route::scopeBindings()->group(function () {
                Route::resource('courses.assignments.submissions', SubmissionController::class)->only(['show'])->shallow();
                // SubmissionController akan ditambahkan Syahid
                // Route::resource('courses.assignments.submissions', SubmissionController::class)->shallow();
            });
        });

    /*
    |----------------------------------------------------------------------
    | Grup Admin + Dosen (shared) — bisa diakses keduanya
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin,dosen')
        ->name('shared.')
        ->group(function () {
            // Placeholder untuk route yang diakses admin maupun dosen
            // Contoh: laporan, notifikasi, dll.
        });

});