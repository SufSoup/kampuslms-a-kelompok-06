# Notes Minggu 11 Pemrograman Web

**Nama:** Muhammad Ikhsyan  
**NIM:** 10241049  
**Kelompok:** Kelompok 06 (Kelas A)  
**Topik:** Optimisasi Performa — Pencegahan N+1, Eager Loading, & Audit `env()`  
**Branch:** `ikhsyan`

---

## 1. READ — Ukur Aplikasi Sebelum Optimisasi

Sebelum `Model::preventLazyLoading()` dipasang, pengujian manual dengan Laravel Debugbar menunjukkan:

| Halaman | Jumlah Query (estimasi) | Catatan |
|---------|:-----------------------:|---------|
| Daftar mata kuliah | ~3–4 | `with('lecturer')` sudah ada |
| Detail mata kuliah | ~5–8 | Relasi students, assignments dimuat |
| Daftar tugas | ~4–6 | `with(['course', 'creator'])` sudah ada |
| Daftar pengumpulan (dosen) | ~5–10 | `with('grade')` sudah ada per student |
| **Rekap nilai** | **~8–12** | `with(['student', 'assignment', 'grade.grader'])` sudah terpasang |
| Dashboard mahasiswa | ~3–5 | Minimal relasi |
| Dashboard dosen | ~4–8 | Statistik per course |

Setelah `preventLazyLoading` dipasang dan test suite dijalankan: **nol violation**. Codebase sudah bersih dari lazy loading tersembunyi.

Audit `env()`:
```bash
grep -rn "env(" lms/app lms/routes lms/database --include="*.php"
# Output: (kosong) — zero violations
```

---

## 2. BREAK — Tujuh Percobaan Kerusakan Performa

| # | Yang Dicoba | Prediksi | Hasil Sebenarnya |
|---|-------------|----------|------------------|
| **1** | Pasang `Model::preventLazyLoading()`, jalankan seluruh halaman | N+1 tersembunyi muncul sebagai exception | **Nol violation** — seluruh relasi sudah di-eager load dengan benar sejak minggu-minggu sebelumnya berkat kebiasaan `with()` |
| **2** | Hapus `with(['student', 'assignment', 'grade.grader'])` dari `courseGrades`, ukur ulang | Query melonjak drastis | Dengan 5 submission: dari ~8 query menjadi ~23 query (1 + N untuk student + N untuk assignment + N untuk grade). Dengan 100 submission: >300 query |
| **3** | Ganti `withCount('students')` menjadi `count($course->students)` | Seluruh baris student dimuat ke memori hanya untuk dihitung | `count($course->students)`: memuat semua kolom semua mahasiswa ke PHP memory, lalu PHP menghitung. `withCount('students')`: satu subquery `SELECT COUNT(*)`, tidak ada data yang dimuat. Dengan 30 mahasiswa: ~30x lebih boros memori |
| **4** | Hapus index dari `assignments.course_id`, jalankan `EXPLAIN SELECT * FROM assignments WHERE course_id = 5` | `type = ALL` — full table scan | **Terbukti:** Kolom `type` berubah dari `ref` (pakai index) menjadi `ALL`. Laravel sudah membuat index FK otomatis via `foreignId()->constrained()`, jadi ini harus dihapus manual untuk tes |
| **5** | Naikkan seeder menjadi 300 mahasiswa dan 500 submission, buka rekap nilai | Masalah yang tadinya tidak terlihat menjadi jelas | Tanpa eager loading: dari 12 query menjadi 1502 query. Response time dari ~80ms menjadi >3 detik. Dengan eager loading tetap stabil di ~10 query |
| **6** | Jalankan `php artisan config:cache`, lalu panggil `env('APP_NAME')` dari controller | Mengembalikan `null` alih-alih nama aplikasi | **Terbukti:** `env()` membaca langsung dari `.env` file. Setelah `config:cache`, Laravel tidak membaca `.env` lagi — hanya file cache. `env()` di luar `config/` menjadi `null`. Solusi: gunakan `config('app.name')` |
| **7** | Pasang `Cache::remember` 10 menit pada nilai mahasiswa, ubah nilainya, muat ulang | Data basi muncul ke mahasiswa | **Terbukti:** Nilai lama masih muncul selama 10 menit setelah diubah dosen. Ini adalah alasan nilai (`Grade`), feedback, dan status pengumpulan real-time **dilarang di-cache** — data basi langsung berdampak pada pengguna |

---

## 3. FIX — Perbaikan Repo Cacat Minggu 11 (`w11`)

Enam masalah pada repo `kampuslms-broken` branch `w11` dianalisis dan diperbaiki:

1. **N+1 Tiga Tingkat Relasi (Rekap Nilai):**
   - *Cacat:* `$submissions = Submission::whereHas(...)->get()` tanpa `with()`, lalu view mengakses `$submission->student->name`, `$submission->assignment->title`, `$submission->grade->score` — memicu 3 query N+1 per baris.
   - *Perbaikan:* `->with(['student', 'assignment', 'grade.grader'])` pada query utama.

2. **`count()` yang Memuat Seluruh Koleksi:**
   - *Cacat:* `count($course->students)` — memuat semua baris mahasiswa ke memori PHP hanya untuk menghitung.
   - *Perbaikan:* `$course->students()->count()` — satu query `COUNT(*)` di database.

3. **`SELECT *` Padahal Hanya Dua Kolom Dipakai:**
   - *Cacat:* `$course->students` mengambil semua kolom termasuk `password`, `remember_token`.
   - *Perbaikan:* `$course->students()->select('users.id', 'users.name', 'users.nim_nip')->get()`.

4. **Index Hilang pada Kolom Filter:**
   - *Cacat:* Query `WHERE course_id = ? ORDER BY submitted_at` tanpa index composite.
   - *Perbaikan:* Migrasi index `['assignment_id', 'submitted_at']` pada tabel `submissions`.

5. **Data Dimuat Tanpa Pagination:**
   - *Cacat:* `->get()` pada query rekap nilai — memuat seluruh submission ke memori.
   - *Perbaikan:* `->paginate(25)` dengan `{{ $submissions->links() }}` di view.

6. **`env()` Dipanggil di Luar `config/`:**
   - *Cacat:* `$appName = env('APP_NAME')` di dalam sebuah controller.
   - *Perbaikan:* `$appName = config('app.name')` — aman setelah `config:cache`.

---

## 4. BUILD — Kontribusi Muhammad Ikhsyan (`10241049`)

Fokus pengerjaan: **Pencegahan N+1 Otomatis & Audit `env()`**

### 1. Aktivasi `Model::preventLazyLoading()`

**File:** `lms/app/Providers/AppServiceProvider.php`

Menambahkan satu baris di `boot()`:
```php
Model::preventLazyLoading(! app()->isProduction());
```
Di lingkungan non-produksi (development, testing), setiap akses relasi tanpa eager loading langsung melempar `LazyLoadingViolationException`. Di produksi tetap jalan normal. Memaksa seluruh tim menulis `with()` eksplisit sejak awal, bukan menunggu profiler.

### 2. Verifikasi Query Rekap Nilai < 15

`SubmissionController@courseGrades` sudah optimal:
- `with(['student', 'assignment', 'grade.grader'])` — eager load 3 relasi bersarang dalam 4 query (1 submissions + 1 students + 1 assignments + 1 grades dengan grader)
- `paginate(25)` — tidak memuat seluruh tabel
- `whereHas` diganti langsung join via Eloquent scoping

Hasil: **~8–10 query** per halaman (jauh di bawah target 15).

### 3. Audit Zero `env()` di Luar `config/`

Scan seluruh `app/`, `routes/`, `database/`:
```bash
grep -rn "env(" lms/app lms/routes lms/database --include="*.php"
# Hasil: (kosong)
```
Zero violations. Seluruh konfigurasi sudah menggunakan `config()` helper.

### 4. Feature Test: `tests/Feature/PerformanceEagerLoadingTest.php`

Enam test yang ditulis:
1. `test_prevent_lazy_loading_is_active_outside_production` — verifikasi `Model::preventsLazyLoading()` true
2. `test_course_grades_page_has_no_lazy_loading_violations` — halaman rekap dosen tidak trigger exception
3. `test_student_grades_page_has_no_lazy_loading_violations` — halaman rekap mahasiswa tidak trigger exception
4. `test_course_grades_runs_under_15_queries` — query count < 15 via `DB::enableQueryLog()`
5. `test_student_grades_runs_under_15_queries` — query count < 15
6. `test_no_env_calls_outside_config_directory` — scan filesystem, assert zero violations

**Hasil:** 182 tests passed (653 assertions) — 100% PASS.

### Ringkasan Perubahan File

| File | Tindakan |
|------|----------|
| `lms/app/Providers/AppServiceProvider.php` | Edit — tambah `Model::preventLazyLoading()` |
| `lms/tests/Feature/PerformanceEagerLoadingTest.php` | Baru — 6 feature tests |
| `docs/Minggu-11/minggu-11-ikhsyan.md` | Baru — catatan mingguan |
