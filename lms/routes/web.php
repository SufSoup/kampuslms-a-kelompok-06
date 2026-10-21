<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseEnrollmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\LearningResourceController;

/*
|--------------------------------------------------------------------------
| Route Publik & Otentikasi
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');


/*
|--------------------------------------------------------------------------
| Route Terproteksi (wajib login)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/submissions/{submission}/download', [SubmissionController::class, 'download'])->name('submissions.download');

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
            Route::post('courses/{course}/enrollments', [CourseEnrollmentController::class, 'store'])
                ->name('courses.enrollments.store');
            Route::delete('courses/{course}/enrollments/{student}', [CourseEnrollmentController::class, 'destroy'])
                ->name('courses.enrollments.destroy');

            Route::scopeBindings()
                ->resource('courses.materials', MaterialController::class)
                ->shallow();
            Route::get('materials/{material}/download', [MaterialController::class, 'download'])
                ->name('materials.download');
        });

    /*
    |--------------------------------------------------------------------------
    | Grup Dosen — prefix: /dosen, name prefix: dosen.
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:dosen')
        ->prefix('dosen')
        ->name('dosen.')
        ->group(function () {
            // Dosen melihat mata kuliah yang ia ampu
            Route::get('/courses', [CourseController::class, 'index'])
                ->name('courses.index');

            Route::get('/courses/{course}', [CourseController::class, 'show'])
                ->name('courses.show');

            // CRUD assignment dan materi
            Route::scopeBindings()->group(function () {
                Route::resource('courses.assignments', AssignmentController::class)
                    ->except(['index'])
                    ->shallow();

                Route::resource('courses.materials', MaterialController::class)
                    ->shallow();
                Route::get('materials/{material}/download', [MaterialController::class, 'download'])
                    ->name('materials.download');
            });

            // Daftar assignment dosen
            Route::get('/assignments', [AssignmentController::class, 'index'])
                ->name('assignments.index');

            Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])
                ->name('submissions.show');
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
                Route::resource('courses.materials', MaterialController::class)->only(['index', 'show'])->shallow();
                Route::get('materials/{material}/download', [MaterialController::class, 'download'])
                    ->name('materials.download');
            });

            Route::get('/assignments', [AssignmentController::class, 'index'])
                ->name('assignments.index');

            Route::get('/assignments/{assignment}', [AssignmentController::class, 'show'])
                ->name('assignments.show');

            Route::post('/assignments/{assignment}/submissions', [SubmissionController::class, 'store'])
                ->name('assignments.submissions.store');
        });

    /*
    |----------------------------------------------------------------------
    | Fitur 17: Kurasi Sumber Belajar Tambahan & Penanda Baca
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin,dosen')->group(function () {
        Route::get('/courses/{course}/resources/create', [LearningResourceController::class, 'create'])->name('learning-resources.create');
        Route::post('/courses/{course}/resources', [LearningResourceController::class, 'store'])->name('learning-resources.store');
        Route::get('/courses/{course}/resources/{resource}/edit', [LearningResourceController::class, 'edit'])->name('learning-resources.edit');
        Route::put('/courses/{course}/resources/{resource}', [LearningResourceController::class, 'update'])->name('learning-resources.update');
        Route::delete('/courses/{course}/resources/{resource}', [LearningResourceController::class, 'destroy'])->name('learning-resources.destroy');
    });

    // Toggle Penanda Baca (Mahasiswa) — Anti IDOR (mengikat ke auth()->id())
    Route::post('/resources/{resource}/toggle-read', [LearningResourceController::class, 'toggleRead'])->name('learning-resources.toggle-read');

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