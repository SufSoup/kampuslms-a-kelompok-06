# Dokumentasi Minggu 5 — Ikhsyan Azhary Akhmad

**Mata Kuliah:** SI2514024 — Pemrograman Web  
**Nama:** Ikhsyan Azhary Akhmad  
**NIM:** (isi NIM)  
**Minggu:** 5 — Routing Lanjutan, IDOR, & Middleware  
**Tanggal:** 29 September 2026  
**Branch:** `ikhsyan-week5`

---

## 📋 Ringkasan Pekerjaan

Minggu ini saya mengerjakan fondasi keamanan routing KampusLMS:

1. **Refactor `routes/web.php`** — Struktur route group per peran (admin/dosen/mahasiswa) dengan prefix, name prefix, dan middleware.
2. **Buat Middleware `EnsureUserHasRole`** — Middleware multi-role yang membedakan 401 vs 403.
3. **Daftarkan alias `role`** di `bootstrap/app.php` sesuai Laravel 12.
4. **Siapkan `scopeBindings()`** untuk route bersarang materi & tugas.
5. **Buat halaman 403 kustom** yang tidak membocorkan informasi.

---

## 🗺️ Struktur Route Setelah Refactor

Output `php artisan route:list --except-vendor`:

```
GET|HEAD    /                               home
GET|HEAD    tentang                         tentang
GET|HEAD    dashboard                       dashboard

GET|HEAD    admin/courses                   admin.courses.index   → CourseController@index
POST        admin/courses                   admin.courses.store   → CourseController@store
GET|HEAD    admin/courses/create            admin.courses.create  → CourseController@create
GET|HEAD    admin/courses/{course}          admin.courses.show    → CourseController@show
PUT|PATCH   admin/courses/{course}          admin.courses.update  → CourseController@update
DELETE      admin/courses/{course}          admin.courses.destroy → CourseController@destroy
GET|HEAD    admin/courses/{course}/edit     admin.courses.edit    → CourseController@edit
GET|HEAD    admin/users                     admin.users.index     → UserController@index
POST        admin/users                     admin.users.store     → UserController@store
GET|HEAD    admin/users/create              admin.users.create    → UserController@create
GET|HEAD    admin/users/{user}              admin.users.show      → UserController@show
PUT|PATCH   admin/users/{user}              admin.users.update    → UserController@update
DELETE      admin/users/{user}              admin.users.destroy   → UserController@destroy
GET|HEAD    admin/users/{user}/edit         admin.users.edit      → UserController@edit

GET|HEAD    dosen/courses                   dosen.courses.index   → CourseController@index
GET|HEAD    dosen/courses/{course}          dosen.courses.show    → CourseController@show

GET|HEAD    mahasiswa/courses               mahasiswa.courses.index → CourseController@index
GET|HEAD    mahasiswa/courses/{course}      mahasiswa.courses.show  → CourseController@show
```

---

## 🔴 Daftar Titik Rawan IDOR

> Tabel ini dibuat dari analisis `route:list` — setiap route yang menerima parameter model (`{course}`, `{user}`, dst.) adalah kandidat IDOR.  
> Kolom "Pelindung Saat Ini" mengacu pada kondisi setelah refactor minggu ini.

| # | Method | URI | Nama Route | Parameter | Siapa Boleh Akses | Pelindung Saat Ini | Status |
|---|--------|-----|-----------|-----------|-------------------|-------------------|--------|
| 1 | GET | `/admin/courses/{course}` | `admin.courses.show` | `{course}` | Admin saja | Middleware `role:admin` | ✅ Terlindungi |
| 2 | PUT/PATCH | `/admin/courses/{course}` | `admin.courses.update` | `{course}` | Admin saja | Middleware `role:admin` | ✅ Terlindungi |
| 3 | DELETE | `/admin/courses/{course}` | `admin.courses.destroy` | `{course}` | Admin saja | Middleware `role:admin` | ✅ Terlindungi |
| 4 | GET | `/admin/users/{user}` | `admin.users.show` | `{user}` | Admin saja | Middleware `role:admin` | ✅ Terlindungi |
| 5 | PUT/PATCH | `/admin/users/{user}` | `admin.users.update` | `{user}` | Admin saja | Middleware `role:admin` | ✅ Terlindungi |
| 6 | DELETE | `/admin/users/{user}` | `admin.users.destroy` | `{user}` | Admin saja | Middleware `role:admin` | ✅ Terlindungi |
| 7 | GET | `/dosen/courses/{course}` | `dosen.courses.show` | `{course}` | Dosen pengampu MK ini saja | Middleware `role:dosen` ✅ — tapi **belum cek kepemilikan** | ⚠️ IDOR Partial |
| 8 | GET | `/mahasiswa/courses/{course}` | `mahasiswa.courses.show` | `{course}` | Mahasiswa yang terdaftar di MK ini | Middleware `role:mahasiswa` ✅ — tapi **belum cek enrollment** | ⚠️ IDOR Partial |
| 9 | (akan ditambah Syahid) | `/dosen/courses/{course}/assignments/{assignment}` | `dosen.assignments.*` | `{course}`, `{assignment}` | Dosen pengampu MK ini | Belum ada | 🔴 Belum Dibuat |
| 10 | (akan ditambah Syahid) | `/mahasiswa/.../submissions/{submission}` | `mahasiswa.submissions.*` | `{submission}` | Mahasiswa pemilik submission ini | Belum ada | 🔴 Belum Dibuat |
| 11 | (akan ditambah Yusuf) | `/dosen/courses/{course}/materials/{material}` | `dosen.materials.*` | `{course}`, `{material}` | Dosen pengampu MK ini | Belum ada | 🔴 Belum Dibuat |

> **Catatan baris 7 & 8:** Middleware `role:dosen` hanya memastikan *dosen mana saja* bisa masuk. Dosen A masih bisa membuka `/dosen/courses/99` meski ia tidak mengampu MK 99. Perbaikan berupa `abort_unless` akan ditambahkan di controller (Yusuf) dan Policy di minggu 7.

---

## 🛡️ Middleware `EnsureUserHasRole`

**Lokasi:** `app/Http/Middleware/EnsureUserHasRole.php`

**Cara kerja:**
- Pengguna **belum login** → `abort(401)` — Unauthorized
- Pengguna login tapi **role tidak cocok** → `abort(403)` — Forbidden
- Mendukung **multi-role**: `role:admin,dosen`

**Pendaftaran alias** di `bootstrap/app.php` (bukan `Kernel.php`):
```php
$middleware->alias([
    'role' => \App\Http\Middleware\EnsureUserHasRole::class,
]);
```

> ⚠️ **Kenapa `bootstrap/app.php`?**  
> Laravel 12 menghapus `app/Http/Kernel.php`. Hampir semua tutorial lama dan jawaban AI masih menunjuk ke `Kernel.php` — berkas itu tidak ada lagi.

---

## 🔬 Pengujian BREAK (dari modul §5.3)

### BREAK #4 — Middleware di file salah
> Dicoba: menambahkan alias di `app/Http/Kernel.php`  
> **Hasil:** File tidak ada → error `Class not found`. Konfirmasi: di Laravel 12, pendaftaran ada di `bootstrap/app.php`.

### BREAK #5 — Akses sebagai role yang salah
> Dicoba: akses `/admin/users` sebagai dosen  
> **Yang diharapkan:** `403 Forbidden` dari middleware `role:admin`  
> **Hasil:** *(diisi setelah auth system tersedia)*

### BREAK #6 — Dosen A edit mata kuliah dosen B
> Dicoba: dosen A akses `/dosen/courses/{course_milik_dosenB}`  
> **Yang diharapkan:** middleware `role:dosen` meloloskan → IDOR!  
> **Kesimpulan:** Middleware saja tidak cukup. Perlu `abort_unless` di controller (minggu ini) dan Policy (minggu 7).

---

## 💡 Jawaban Checkpoint Minggu 5

**1. Apa itu IDOR? Peragakan satu contoh di aplikasi Anda.**  
IDOR (*Insecure Direct Object Reference*) adalah kerentanan di mana pengguna dapat mengakses data milik orang lain cukup dengan mengganti angka ID di URL. Contoh: mahasiswa A membuka `/submissions/41` lalu mengubah ke `/submissions/42` dan bisa melihat submission mahasiswa B.  
Perbaikan: `abort_unless($submission->user_id === auth()->id(), 403)`.

**2. Kenapa mengganti ID berurutan dengan UUID bukan perbaikan IDOR?**  
UUID hanya membuat ID lebih sulit ditebak, tapi jika tidak ada pengecekan kepemilikan di controller, pengguna yang mengetahui UUID orang lain (dari log, share link, dll.) tetap bisa mengaksesnya. Akar masalahnya adalah *tidak ada otorisasi*, bukan *ID mudah ditebak*.

**3. Route model binding menjamin apa, dan tidak menjamin apa?**  
- **Menjamin:** Data dengan ID tersebut *ada* di database, atau otomatis 404.  
- **Tidak menjamin:** Pengguna yang sedang login *berhak* mengakses data tersebut. Itu tugas otorisasi (`abort_unless` / Policy).

**4. Apa yang dilakukan `Route::scopeBindings()`?**  
Memastikan parameter nested route benar-benar milik parent-nya. Contoh: tanpa `scopeBindings()`, URL `/courses/1/assignments/99` akan berhasil meski tugas 99 milik mata kuliah 7. Dengan `scopeBindings()`, Laravel menambahkan kondisi `WHERE course_id = 1` saat mencari assignment — jika tidak cocok, otomatis 404.

**5. Di berkas mana middleware didaftarkan pada Laravel 12?**  
Di `bootstrap/app.php`, dalam closure `withMiddleware()`:  
```php
$middleware->alias(['role' => EnsureUserHasRole::class]);
```
`app/Http/Kernel.php` **tidak ada** di Laravel 12 — hampir semua tutorial lama dan jawaban AI masih menunjuk ke sana.

**6. Kenapa middleware `role:dosen` tidak cukup untuk mencegah dosen A mengedit mata kuliah dosen B?**  
Middleware `role:dosen` hanya memeriksa *apakah pengguna adalah dosen* — semua dosen lolos. Ia tidak memeriksa *apakah dosen ini pengampu mata kuliah spesifik yang sedang diakses*. Untuk itu dibutuhkan pengecekan kepemilikan di level controller (`abort_unless`) atau Policy (minggu 7).

---

## 📁 File yang Dibuat/Diubah

| File | Status | Keterangan |
|------|--------|-----------|
| `routes/web.php` | ✏️ Diubah | Refactor ke route group per peran |
| `app/Http/Middleware/EnsureUserHasRole.php` | 🆕 Baru | Middleware role-based |
| `bootstrap/app.php` | ✏️ Diubah | Daftarkan alias `role` |
| `resources/views/errors/403.blade.php` | 🆕 Baru | Halaman 403 kustom |
| `docs/Minggu-5/minggu-05-ikhsyan.md` | 🆕 Baru | Dokumentasi ini |
