## Notes Minggu 5 Pemrograman Web

### READ — Peta route aplikasi sendiri

#### 1. Jalankan `php artisan route:list --except-vendor`. Tandai setiap route yang menerima parameter model.

Output yang saya dapatkan:

```
GET|HEAD    /                               home
GET|HEAD    tentang                         tentang
GET|HEAD    dashboard                       dashboard

GET|HEAD    admin/courses                   admin.courses.index   › CourseController@index
POST        admin/courses                   admin.courses.store   › CourseController@store
GET|HEAD    admin/courses/create            admin.courses.create  › CourseController@create
GET|HEAD    admin/courses/{course}          admin.courses.show    › CourseController@show      ← parameter model
PUT|PATCH   admin/courses/{course}          admin.courses.update  › CourseController@update    ← parameter model
DELETE      admin/courses/{course}          admin.courses.destroy › CourseController@destroy   ← parameter model
GET|HEAD    admin/courses/{course}/edit     admin.courses.edit    › CourseController@edit      ← parameter model
GET|HEAD    admin/users                     admin.users.index     › UserController@index
POST        admin/users                     admin.users.store     › UserController@store
GET|HEAD    admin/users/create              admin.users.create    › UserController@create
GET|HEAD    admin/users/{user}              admin.users.show      › UserController@show        ← parameter model
PUT|PATCH   admin/users/{user}              admin.users.update    › UserController@update      ← parameter model
DELETE      admin/users/{user}              admin.users.destroy   › UserController@destroy     ← parameter model
GET|HEAD    admin/users/{user}/edit         admin.users.edit      › UserController@edit        ← parameter model
GET|HEAD    dosen/courses                   dosen.courses.index   › CourseController@index
GET|HEAD    dosen/courses/{course}          dosen.courses.show    › CourseController@show      ← parameter model
GET|HEAD    mahasiswa/courses               mahasiswa.courses.index › CourseController@index
GET|HEAD    mahasiswa/courses/{course}      mahasiswa.courses.show  › CourseController@show    ← parameter model
```

Total 21 route. Yang menerima parameter model ada **10 route** (ditandai `← parameter model`).

#### 2. Untuk setiap route bertanda — siapa yang boleh akses, dan apa yang saat ini mencegah orang lain?

| Route | Parameter | Siapa boleh akses | Pelindung saat ini |
|-------|-----------|-------------------|--------------------|
| `admin/courses/{course}` (GET/PUT/DELETE) | `{course}` | Admin saja | Middleware `role:admin` ✅ |
| `admin/users/{user}` (GET/PUT/DELETE) | `{user}` | Admin saja | Middleware `role:admin` ✅ |
| `dosen/courses/{course}` | `{course}` | Dosen pengampu MK ini | Middleware `role:dosen` — tapi **belum cek dosen ini pengampunya** ⚠️ |
| `mahasiswa/courses/{course}` | `{course}` | Mahasiswa yang terdaftar di MK ini | Middleware `role:mahasiswa` — tapi **belum cek mahasiswa ini terdaftar** ⚠️ |

#### 3. Daftar Titik Rawan IDOR

> Kolom "Status" diisi berdasarkan kondisi kode minggu ini.

| # | Route | Skenario serangan | Pelindung | Status |
|---|-------|-------------------|-----------|--------|
| 1 | `dosen/courses/{course}` | Dosen A buka `/dosen/courses/99` padahal MK 99 diampu dosen B | Hanya `role:dosen`, tidak cek `lecturer_id` | ⚠️ IDOR terbuka |
| 2 | `mahasiswa/courses/{course}` | Mahasiswa A buka `/mahasiswa/courses/5` padahal tidak pernah mendaftar MK 5 | Hanya `role:mahasiswa`, tidak cek enrollment | ⚠️ IDOR terbuka |
| 3 | `admin/courses/{course}` (DELETE) | Dosen/mahasiswa akses langsung URL DELETE | Middleware `role:admin` blokir | ✅ Aman |
| 4 | `admin/users/{user}` (PUT) | Mahasiswa coba edit data pengguna lain | Middleware `role:admin` blokir | ✅ Aman |
| 5 | `dosen/courses/{course}/assignments/{assignment}` (belum ada) | Dosen A edit tugas milik dosen B | Belum ada route ini | 🔴 Belum dibuat |
| 6 | Submission (belum ada) | Mahasiswa A buka submission milik mahasiswa B dengan ubah angka URL | Belum ada route ini | 🔴 Belum dibuat |

Baris 1–2 adalah IDOR yang ada sekarang dan akan ditutup dengan `abort_unless` di controller. Baris 5–6 menunggu Syahid & Yusuf menambahkan controllernya.

---

### BREAK — Enam kerusakan

> Kolom **Prediksi** diisi sebelum mencoba. Kolom **Hasil sebenarnya** diisi setelah eksperimen.

| # | Yang dicoba | Prediksi sebelum mencoba | Hasil sebenarnya |
|---|-------------|--------------------------|-----------------|
| 1 | Login sebagai mahasiswa, buka submission milik mahasiswa lain dengan ubah angka di URL | Seharusnya 403, tapi mungkin langsung tampil karena belum ada pengecekan | Belum bisa diuji — route submission belum dibuat minggu ini (dikerjakan Syahid) |
| 2 | Buka `/courses/1/assignments/99` di mana tugas 99 milik mata kuliah lain | Laravel akan menemukan tugas 99 meskipun bukan milik MK 1 | Belum bisa diuji — route assignment belum dibuat minggu ini |
| 3 | Aktifkan `Route::scopeBindings()`, ulangi nomor 2 | Harus 404 karena Laravel memvalidasi relasi parent-child | `scopeBindings()` sudah dipasang di grup dosen dan mahasiswa di `routes/web.php`. Efeknya baru terasa saat route nested assignment/material ditambahkan |
| 4 | Daftarkan middleware di `app/Http/Kernel.php` seperti tutorial lama | File tidak ada, pasti error | Benar — file `app/Http/Kernel.php` tidak ada di project ini. Error `Class not found` muncul saat dicoba. Di Laravel 12 pendaftaran ada di `bootstrap/app.php` |
| 5 | Pasang `role:admin` pada grup, lalu akses sebagai dosen | Harus 403 | Halaman 403 kustom (`resources/views/errors/403.blade.php`) muncul dengan pesan "Akses Ditolak" — middleware bekerja benar |
| 6 | Sebagai dosen A, akses `dosen/courses/{course_milik_dosenB}` | Middleware `role:dosen` lolos, IDOR terjadi | Benar — middleware hanya memeriksa peran, tidak memeriksa kepemilikan. Halaman course milik dosen B tampil tanpa hambatan. Konfirmasi: middleware saja tidak cukup |

---

### Catatan Uji Coba BREAK Nomor 4 (Kernel.php tidak ada):

Dicoba menambahkan baris berikut ke `app/Http/Kernel.php`:
```php
protected $routeMiddleware = [
    'role' => \App\Http\Middleware\EnsureUserHasRole::class,
];
```
**Temuan:** File `app/Http/Kernel.php` tidak ada sama sekali di Laravel 12. Hampir semua tutorial lama dan sebagian besar jawaban AI masih mengarahkan ke file ini. Di Laravel 12, alias middleware didaftarkan di `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\EnsureUserHasRole::class,
    ]);
})
```

---

### Catatan Uji Coba BREAK Nomor 6 (Middleware tidak cukup):

Skenario: dua akun dosen (dosen_a dan dosen_b), masing-masing mengampu mata kuliah berbeda.
- Dosen A login → buka `/dosen/courses/1` (MK miliknya) → berhasil ✅
- Dosen A ganti URL → buka `/dosen/courses/2` (MK milik dosen B) → **juga berhasil** ❌

Middleware `role:dosen` hanya memastikan "ini seorang dosen" — bukan "ini dosen yang mengampu MK ini". Perbaikan perlu `abort_unless` di controller:
```php
abort_unless($course->lecturer_id === auth()->id(), 403);
```
Atau dengan Policy di minggu 7.

---

### CHECKPOINT MINGGU 5

#### 1. Apa itu IDOR? Peragakan satu contoh di aplikasi ini, lalu tunjukkan perbaikannya.
IDOR (*Insecure Direct Object Reference*) adalah kerentanan di mana pengguna mengakses data milik orang lain cukup dengan mengganti angka ID di URL — tanpa perlu alat khusus. Contoh nyata di aplikasi ini: dosen A buka `/dosen/courses/2` padahal MK 2 diampu dosen B. Middleware `role:dosen` tidak memblokir karena ia hanya memeriksa peran, bukan kepemilikan. Perbaikannya di controller:
```php
public function show(Course $course)
{
    abort_unless($course->lecturer_id === auth()->id(), 403);
    return view('courses.show', compact('course'));
}
```

#### 2. Kenapa mengganti ID berurutan dengan UUID bukan perbaikan IDOR?
UUID hanya membuat ID lebih sulit ditebak secara acak. Tapi jika link pernah dibagikan, muncul di log, atau bocor lewat sumber lain, pengguna yang memiliki UUID orang lain tetap bisa mengaksesnya karena controller tidak memeriksa kepemilikan. Akar masalah IDOR adalah tidak adanya pengecekan otorisasi di server — bukan soal format ID.

#### 3. Route model binding menjamin apa, dan tidak menjamin apa?
- **Menjamin:** data dengan ID tersebut ada di database. Jika tidak ada, Laravel otomatis mengembalikan 404. Tidak perlu `findOrFail($id)` manual.
- **Tidak menjamin:** pengguna yang sedang login berhak mengakses data tersebut. `CourseController::show(Course $course)` akan menyerahkan objek `$course` apapun ID-nya — pengecekan kepemilikan adalah tanggung jawab kode di dalam controller, bukan binding.

#### 4. Apa yang dilakukan `Route::scopeBindings()`? Beri contoh URL yang lolos tanpa itu.
`scopeBindings()` memastikan parameter nested route benar-benar milik parent-nya. Tanpa itu, route `/courses/1/assignments/99` akan berhasil meski tugas 99 sebenarnya milik mata kuliah 7 — Laravel hanya mencari `assignments` dengan `id = 99` dari seluruh tabel. Dengan `scopeBindings()`, Laravel menambahkan kondisi `WHERE course_id = 1` saat mencari, sehingga jika tidak cocok otomatis 404. Di `routes/web.php` saya sudah memasangnya di grup dosen dan mahasiswa:
```php
Route::scopeBindings()->group(function () {
    // Route::resource('courses.assignments', AssignmentController::class)->shallow();
});
```

#### 5. Di berkas mana middleware didaftarkan pada Laravel 12? Kenapa berbeda dari kebanyakan tutorial?
Di `bootstrap/app.php`, di dalam closure `withMiddleware()`:
```php
$middleware->alias([
    'role' => \App\Http\Middleware\EnsureUserHasRole::class,
]);
```
Berbeda karena Laravel 12 menghapus `app/Http/Kernel.php` sebagai bagian dari penyederhanaan arsitektur. Kebanyakan tutorial ditulis untuk Laravel 9 atau 10 ke bawah dan belum diperbarui — termasuk sebagian besar jawaban AI.

#### 6. Kenapa middleware `role:dosen` tidak cukup untuk mencegah dosen A mengedit mata kuliah dosen B?
Middleware `role:dosen` hanya menjawab "apakah pengguna ini seorang dosen?" — semua dosen lolos. Ia tidak tahu dan tidak memeriksa "apakah dosen ini pengampu mata kuliah yang sedang diakses?". Untuk itu dibutuhkan pengecekan di level controller menggunakan `abort_unless($course->lecturer_id === auth()->id(), 403)` — atau dirapikan menjadi Policy di minggu 7.
