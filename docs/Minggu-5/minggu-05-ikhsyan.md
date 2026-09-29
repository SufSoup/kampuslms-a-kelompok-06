## Notes Minggu 5 Pemrograman Web

### READ — Peta route aplikasi sendiri

#### 1. Jalankan `php artisan route:list --except-vendor`. Salin keluarannya ke catatan.

Jawaban :
Output `php artisan route:list --except-vendor`:

```text
GET|HEAD    /                               home
GET|HEAD    tentang                         tentang
GET|HEAD    dashboard                       dashboard

GET|HEAD    admin/courses                   admin.courses.index   › CourseController@index
POST        admin/courses                   admin.courses.store   › CourseController@store
GET|HEAD    admin/courses/create            admin.courses.create  › CourseController@create
GET|HEAD    admin/courses/{course}          admin.courses.show    › CourseController@show
PUT|PATCH   admin/courses/{course}          admin.courses.update  › CourseController@update
DELETE      admin/courses/{course}          admin.courses.destroy › CourseController@destroy
GET|HEAD    admin/courses/{course}/edit     admin.courses.edit    › CourseController@edit
GET|HEAD    admin/users                     admin.users.index     › UserController@index
POST        admin/users                     admin.users.store     › UserController@store
GET|HEAD    admin/users/create              admin.users.create    › UserController@create
GET|HEAD    admin/users/{user}              admin.users.show      › UserController@show
PUT|PATCH   admin/users/{user}              admin.users.update    › UserController@update
DELETE      admin/users/{user}              admin.users.destroy   › UserController@destroy
GET|HEAD    admin/users/{user}/edit         admin.users.edit      › UserController@edit
GET|HEAD    dosen/courses                   dosen.courses.index   › CourseController@index
GET|HEAD    dosen/courses/{course}          dosen.courses.show    › CourseController@show
GET|HEAD    dosen/courses/{course}/assignments dosen.courses.assignments.index › AssignmentController@index
POST        dosen/courses/{course}/assignments dosen.courses.assignments.store › AssignmentController@store
GET|HEAD    dosen/courses/{course}/assignments/create dosen.courses.assignments.create › AssignmentController@create
GET|HEAD    dosen/courses/{course}/materials dosen.courses.materials.index › MaterialController@index
POST        dosen/courses/{course}/materials dosen.courses.materials.store › MaterialController@store
GET|HEAD    dosen/courses/{course}/materials/create dosen.courses.materials.create › MaterialController@create
GET|HEAD    assignments/{assignment}        dosen.assignments.show    › AssignmentController@show
PUT|PATCH   assignments/{assignment}        dosen.assignments.update  › AssignmentController@update
DELETE      assignments/{assignment}        dosen.assignments.destroy › AssignmentController@destroy
GET|HEAD    assignments/{assignment}/edit   dosen.assignments.edit    › AssignmentController@edit
GET|HEAD    materials/{material}            dosen.materials.show    › MaterialController@show
PUT|PATCH   materials/{material}            dosen.materials.update  › MaterialController@update
DELETE      materials/{material}            dosen.materials.destroy › MaterialController@destroy
GET|HEAD    materials/{material}/edit       dosen.materials.edit    › MaterialController@edit
GET|HEAD    mahasiswa/courses               mahasiswa.courses.index › CourseController@index
GET|HEAD    mahasiswa/courses/{course}      mahasiswa.courses.show  › CourseController@show
GET|HEAD    mahasiswa/courses/{course}/assignments/{assignment}/submissions mahasiswa.submissions.index › SubmissionController@index
POST        mahasiswa/courses/{course}/assignments/{assignment}/submissions mahasiswa.submissions.store › SubmissionController@store
GET|HEAD    submissions/{submission}        mahasiswa.submissions.show › SubmissionController@show
```

#### 2. Tandai setiap route yang menerima parameter model, jawab siapa yang boleh mengaksesnya dan apa yang mencegah orang lain.

Jawaban :

| Route | Parameter | Siapa boleh akses | Yang mencegah orang lain |
|-------|-----------|-------------------|--------------------------|
| `admin/courses/{course}` | `{course}` | Admin saja | Middleware `role:admin` |
| `admin/users/{user}` | `{user}` | Admin saja | Middleware `role:admin` |
| `dosen/courses/{course}` | `{course}` | Dosen pengampu MK ini | Middleware `role:dosen` + `abort_unless` cek `lecturer_id` di `show()` |
| `assignments/{assignment}` | `{assignment}` | Dosen pengampu MK dari tugas ini | Middleware `role:dosen` + `abort_unless` cek kepemilikan di controller |
| `materials/{material}` | `{material}` | Dosen pengampu MK dari materi ini | Middleware `role:dosen` + `abort_unless` cek kepemilikan di controller |
| `mahasiswa/courses/{course}` | `{course}` | Mahasiswa yang terdaftar di MK ini | Middleware `role:mahasiswa` + `abort_unless` cek enrollment di `show()` |
| `submissions/{submission}` | `{submission}` | Mahasiswa pemilik, dosen pengampu, admin | Middleware + `abort_unless` multi-kondisi di controller |

#### 3. Daftar Titik Rawan IDOR

| # | Route | Skenario serangan | Pelindung | Status |
|---|-------|-------------------|-----------|--------|
| 1 | `dosen/courses/{course}` | Dosen A buka MK milik dosen B | `role:dosen` + `abort_unless($course->lecturer_id === auth()->id(), 403)` | Ditutup |
| 2 | `mahasiswa/courses/{course}` | Mahasiswa buka MK yang tidak ia ikuti | `role:mahasiswa` + `abort_unless` cek enrollment | Ditutup |
| 3 | `assignments/{assignment}` | Dosen A edit tugas milik dosen B | `role:dosen` + `abort_unless` kepemilikan via relasi course | Ditutup |
| 4 | `materials/{material}` | Dosen A hapus materi milik dosen B | `role:dosen` + `abort_unless` kepemilikan via relasi course | Ditutup |
| 5 | `submissions/{submission}` | Mahasiswa A buka submission mahasiswa B | `role:mahasiswa` + `abort_unless` multi-kondisi | Ditutup |
| 6 | `admin/courses/{course}` (DELETE) | Dosen/mahasiswa akses URL destroy langsung | Middleware `role:admin` blokir | Aman |
| 7 | `admin/users/{user}` (PUT) | Mahasiswa coba edit data pengguna lain | Middleware `role:admin` blokir | Aman |

---

### BREAK — Enam kerusakan

> Kolom **Prediksi** diisi sebelum mencoba. Kolom **Hasil sebenarnya** diisi setelah eksperimen.

| # | Yang dicoba | Prediksi sebelum mencoba | Hasil sebenarnya |
|---|-------------|--------------------------|-----------------|
| 1 | Login sebagai mahasiswa A, buka submission milik mahasiswa B dengan ubah angka di URL | Seharusnya 403, tapi mungkin langsung tampil jika tidak ada pengecekan | 403 dari halaman kustom. `abort_unless` di `SubmissionController::show()` menangkap sebelum query data dikembalikan ke view |
| 2 | Buka `/courses/1/assignments/99` di mana tugas 99 milik mata kuliah lain | Laravel akan menemukan tugas 99 meski bukan milik MK 1 | 404 otomatis dari Laravel karena `scopeBindings()` aktif. Laravel menambahkan kondisi `WHERE course_id = 1` saat mencari assignment |
| 3 | Matikan `scopeBindings()`, ulangi nomor 2 | Harusnya tugas 99 ditemukan meski bukan milik MK 1 | Benar, tugas 99 berhasil diakses meski dibuka lewat MK 1. Ini membuktikan `scopeBindings()` perlu selalu aktif untuk nested route |
| 4 | Daftarkan middleware di `app/Http/Kernel.php` seperti tutorial lama | File tidak ada, pasti error | Benar, file `app/Http/Kernel.php` tidak ada. Muncul error saat Laravel bootstrap. Di Laravel 12 pendaftaran ada di `bootstrap/app.php` dalam `withMiddleware()` |
| 5 | Pasang `role:admin` pada grup, lalu akses sebagai dosen | Harus 403 | Halaman 403 kustom muncul. Middleware membaca `auth()->user()->role` dan memanggil `abort(403)` karena dosen tidak ada di daftar `['admin']` |
| 6 | Sebagai dosen A, akses `dosen/courses/{course_milik_dosenB}` | Middleware `role:dosen` lolos, IDOR terjadi | Awalnya lolos sebelum `abort_unless` ditambahkan. Setelah ditambahkan, muncul 403. Ini membuktikan middleware saja tidak cukup — perlu pengecekan kepemilikan di controller |

---

### Catatan uji coba BREAK nomor 4 (Kernel.php tidak ada):

Dicoba menambahkan baris berikut ke `app/Http/Kernel.php`:
```php
protected $routeMiddleware = [
    'role' => \App\Http\Middleware\EnsureUserHasRole::class,
];
```

Temuan: file `app/Http/Kernel.php` tidak ada di Laravel 12. Di Laravel 12, alias middleware didaftarkan di `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\EnsureUserHasRole::class,
    ]);
})
```

Hampir semua tutorial lama dan jawaban AI masih mengarahkan ke `Kernel.php` — berkas itu sudah tidak ada.

---

### Catatan uji coba BREAK nomor 6 (middleware tidak cukup):

Skenario: dua akun dosen, masing-masing mengampu mata kuliah berbeda.

- Dosen A login, buka `/dosen/courses/1` (MK miliknya) — berhasil.
- Dosen A ganti URL menjadi `/dosen/courses/2` (MK milik dosen B).

Sebelum `abort_unless` ditambahkan: halaman MK dosen B tampil tanpa hambatan. Middleware `role:dosen` hanya memeriksa apakah pengguna adalah dosen, tidak memeriksa apakah dosen ini pengampu MK yang diakses.

Setelah `abort_unless($course->lecturer_id === auth()->id(), 403)` ditambahkan di `CourseController::show()`: muncul 403.

---

### BUILD — Struktur route KampusLMS

#### 1. Refactor `routes/web.php`

Seluruh route disusun ulang menjadi grup peran. Route publik dipisah di luar grup `auth`.

```php
Route::middleware('auth')->group(function () {

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('courses', CourseController::class);
    });

    Route::middleware('role:dosen')->prefix('dosen')->name('dosen.')->group(function () {
        Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

        Route::scopeBindings()->group(function () {
            Route::resource('courses.assignments', AssignmentController::class)->shallow();
            Route::resource('courses.materials', MaterialController::class)->shallow();
        });
    });

    Route::middleware('role:mahasiswa')->prefix('mahasiswa')->name('mahasiswa.')->group(function () {
        Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

        Route::scopeBindings()->group(function () {
            Route::resource('courses.assignments.submissions', SubmissionController::class)->shallow();
        });
    });

});
```

`->shallow()` menghasilkan URL pendek untuk aksi yang tidak membutuhkan parent: `/assignments/12` untuk detail, bukan `/courses/5/assignments/12`. `scopeBindings()` memastikan ID child benar-benar milik parent.

#### 2. Middleware `EnsureUserHasRole`

Berkas baru: `app/Http/Middleware/EnsureUserHasRole.php`

```php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    if (! $request->user()) {
        abort(401);
    }

    if (! in_array($request->user()->role, $roles)) {
        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }

    return $next($request);
}
```

Pengguna belum login menghasilkan 401. Pengguna login tapi peran tidak cocok menghasilkan 403. Dua kode berbeda karena artinya berbeda: 401 berarti "siapa Anda?", 403 berarti "saya tahu siapa Anda, tapi tidak boleh".

Alias didaftarkan di `bootstrap/app.php`:

```php
$middleware->alias([
    'role' => \App\Http\Middleware\EnsureUserHasRole::class,
]);
```

#### 3. Route model binding

Tidak ada `findOrFail($id)` manual di seluruh controller. Semua controller menerima objek model langsung dari type-hint parameter:

```php
public function show(Course $course)   // Laravel sudah mencarikan, 404 otomatis jika tidak ada
```

#### 4. Pemeriksaan kepemilikan sementara (`abort_unless`)

Ditambahkan di setiap controller sesuai titik rawan yang tercatat di READ. Contoh di `CourseController::show()`:

```php
$user = auth()->user();

if ($user->role === 'dosen') {
    abort_unless($course->lecturer_id === $user->id, 403);
}

if ($user->role === 'mahasiswa') {
    abort_unless(
        $course->students()->where('user_id', $user->id)->exists(),
        403
    );
}
```

Versi ini akan dirapikan menjadi Policy di minggu 7. Sengaja ditulis panjang minggu ini supaya aturannya terlihat jelas sebelum disembunyikan di dalam Policy.

#### 5. Halaman 403 kustom

Berkas baru: `resources/views/errors/403.blade.php`

```blade
<x-layout title="403 - Akses Ditolak">
    <h1>403</h1>
    <h2>Akses Ditolak</h2>
    <p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>
    <a href="{{ url('/') }}">Kembali ke Beranda</a>
</x-layout>
```

Pesan tidak menyebutkan data spesifik seperti "submission ini milik Budi" — hanya memberi tahu pengguna bahwa akses ditolak.

#### Hasil `php artisan route:list --except-vendor` setelah build selesai

```text
GET|HEAD    /                               home
GET|HEAD    tentang                         tentang
GET|HEAD    dashboard                       dashboard
GET|HEAD    admin/courses                   admin.courses.index
POST        admin/courses                   admin.courses.store
GET|HEAD    admin/courses/create            admin.courses.create
GET|HEAD    admin/courses/{course}          admin.courses.show
PUT|PATCH   admin/courses/{course}          admin.courses.update
DELETE      admin/courses/{course}          admin.courses.destroy
GET|HEAD    admin/courses/{course}/edit     admin.courses.edit
GET|HEAD    admin/users                     admin.users.index
...
GET|HEAD    dosen/courses                   dosen.courses.index
GET|HEAD    dosen/courses/{course}          dosen.courses.show
GET|HEAD    dosen/courses/{course}/assignments  dosen.courses.assignments.index
POST        dosen/courses/{course}/assignments  dosen.courses.assignments.store
GET|HEAD    assignments/{assignment}        dosen.assignments.show
...
GET|HEAD    mahasiswa/courses               mahasiswa.courses.index
GET|HEAD    mahasiswa/courses/{course}      mahasiswa.courses.show
GET|HEAD    submissions/{submission}        mahasiswa.submissions.show
```

Tidak ada satu pun route di luar middleware `auth` selain `/` dan `/tentang`.

---

### CHECKPOINT MINGGU 5

#### 1. Apa itu IDOR? Peragakan satu contoh di aplikasi ini, lalu tunjukkan perbaikannya.
Jawaban :
IDOR (*Insecure Direct Object Reference*) adalah kerentanan di mana pengguna mengakses data milik orang lain cukup dengan mengganti angka ID di URL, tanpa alat khusus. Contoh di aplikasi ini: dosen A buka `/dosen/courses/2` padahal MK 2 diampu dosen B. Middleware `role:dosen` tidak memblokir karena hanya memeriksa peran, tidak kepemilikan. Perbaikannya di `CourseController::show()`:

```php
if ($user->role === 'dosen') {
    abort_unless($course->lecturer_id === $user->id, 403);
}
```

#### 2. Kenapa mengganti ID berurutan dengan UUID bukan perbaikan IDOR?
Jawaban :
UUID hanya membuat ID lebih sulit ditebak secara acak. Jika UUID pernah dibagikan, muncul di log, atau bocor lewat sumber lain, pengguna yang memiliki UUID tersebut tetap bisa mengaksesnya karena controller tidak memeriksa kepemilikan. Akar masalah IDOR adalah tidak adanya otorisasi di server, bukan format ID.

#### 3. Route model binding menjamin apa, dan tidak menjamin apa?
Jawaban :
- Menjamin: data dengan ID tersebut ada di database. Jika tidak ada, Laravel otomatis mengembalikan 404 tanpa perlu `findOrFail($id)` manual.
- Tidak menjamin: pengguna yang sedang login berhak mengakses data tersebut. `CourseController::show(Course $course)` menerima objek apapun ID-nya — pengecekan kepemilikan adalah tanggung jawab kode di dalam controller.

#### 4. Apa yang dilakukan `Route::scopeBindings()`? Beri contoh URL yang lolos tanpa itu.
Jawaban :
`scopeBindings()` memastikan parameter nested route benar-benar milik parent-nya. Tanpa itu, `/courses/1/assignments/99` berhasil meski tugas 99 sebenarnya milik MK 7 — Laravel hanya mencari `assignments` dengan `id = 99` dari seluruh tabel. Dengan `scopeBindings()`, Laravel menambahkan kondisi `WHERE course_id = 1` sehingga jika tidak cocok otomatis 404.

#### 5. Di berkas mana middleware didaftarkan pada Laravel 12? Kenapa berbeda dari kebanyakan tutorial?
Jawaban :
Di `bootstrap/app.php`, dalam closure `withMiddleware()`:

```php
$middleware->alias([
    'role' => \App\Http\Middleware\EnsureUserHasRole::class,
]);
```

Berbeda karena Laravel 12 menghapus `app/Http/Kernel.php`. Kebanyakan tutorial ditulis untuk Laravel 9–10 ke bawah dan belum diperbarui, termasuk sebagian besar jawaban AI.

#### 6. Kenapa middleware `role:dosen` tidak cukup untuk mencegah dosen A mengedit mata kuliah dosen B?
Jawaban :
Middleware `role:dosen` hanya menjawab "apakah pengguna ini seorang dosen?" — semua dosen lolos. Ia tidak memeriksa apakah dosen ini pengampu mata kuliah yang sedang diakses. Untuk itu dibutuhkan pengecekan kepemilikan di level controller dengan `abort_unless`, atau dirapikan menjadi Policy di minggu 7.
