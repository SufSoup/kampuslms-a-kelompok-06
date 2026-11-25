# Catatan Minggu 14 — Debugging & Testing

**Nama:** Muhammad Farras Kanz  
**NIM:** 10241047  
**Kelompok:** Kelompok 06 (Kelas A)  
**Branch:** `parass` (dibuat dari `origin/dev`)  
**Topik:** READ, BREAK, dan penguatan test CRUD mata kuliah admin

---

## 1. Ringkasan pekerjaan Minggu 14

Pekerjaan utama saya minggu ini:

1. Menambah `lms/tests/Feature/CourseAdminCrudTest.php` untuk menguji CRUD mata kuliah oleh admin.
2. Memperkuat `lms/tests/Feature/ExampleTest.php` agar tidak hanya mengecek HTTP 200.
3. Menyiapkan bahan Bug #1 untuk Yusuf, yaitu kasus rollback index foreign key `courses.lecturer_id` pada commit `8c6f228`.

### 1.1 `CourseAdminCrudTest.php`

File ini berisi 5 test:

| Test | Yang dipastikan |
|---|---|
| `test_admin_can_create_a_course` | Admin berhasil membuat mata kuliah, redirect ke `admin.courses.index`, dan data `code`, `name`, `lecturer_id` benar-benar tersimpan. |
| `test_admin_can_update_a_course` | Admin berhasil mengubah mata kuliah dan perubahan benar-benar masuk database, bukan hanya redirect. |
| `test_course_code_must_be_unique` | Kode mata kuliah duplikat ditolak dan data baru tidak tersimpan. |
| `test_course_sks_must_be_within_the_allowed_range` | `sks = 7` ditolak karena aturan aplikasi 1–6. |
| `test_course_lecturer_must_exist` | `lecturer_id` yang tidak ada ditolak dan data tidak tersimpan. |

Test memakai `RefreshDatabase`, `User::factory()`, `Course::factory()`, route, dan Form Request yang sudah ada. Penolakan non-admin tidak diduplikasi karena sudah diuji di `SecurityMatrixCoverageTest`.

### 1.2 `ExampleTest.php`

Test `test_the_application_returns_a_successful_response()` sebelumnya hanya memakai `assertStatus(200)`. Sekarang test juga memastikan:

- halaman menampilkan teks `KampusLMS`;
- halaman memiliki tautan login (`route('login')`).

Saya juga menambahkan `$this->withoutVite()` mengikuti pola test lain di proyek. Tujuannya agar test isi halaman tidak bergantung pada hasil build Vite.

### 1.3 Hasil test

- `php artisan test --filter="CourseAdminCrudTest|ExampleTest"` → **8 passed, 24 assertions**
- `php artisan test` → **215 passed, 875 assertions**

Catatan: di lingkungan lokal, `npm run build` perlu dijalankan lebih dulu jika `public/build/manifest.json` belum ada. Tanpa build, beberapa test yang merender halaman akan gagal karena Vite manifest tidak ditemukan.

---

## 2. READ — Membaca kegagalan

### 2.1 Menjalankan seluruh test

Perintah:

```bash
cd lms
php artisan test
```

Hasil terakhir:

- **215 test**
- **875 assertions**
- **Semua PASS**

Sebelum aset Vite dibangun, percobaan pertama sempat menghasilkan **73 failed, 142 passed**. Penyebabnya bukan logika aplikasi, tetapi `Vite manifest not found`. Setelah `npm run build`, seluruh test lulus.

### 2.2 Membuat satu test sengaja gagal

Saya membuat test sementara:

```php
public function test_intentional_failure_for_read_exercise(): void
{
    $this->assertTrue(false, 'Kegagalan sengaja untuk latihan READ.');
}
```

Hasil:

```text
FAILED Tests\Feature\TempReadFailureTest > intentional failure for read exercise
Kegagalan sengaja untuk latihan READ.
Failed asserting that false is true.

at tests\Feature\TempReadFailureTest.php:11
```

Baris yang menyebut berkas saya adalah:

```text
tests\Feature\TempReadFailureTest.php:11
```

Artinya stack trace menunjuk langsung ke baris assertion yang sengaja dibuat gagal. File test sementara ini sudah dihapus setelah latihan.

### 2.3 Membaca exception di `storage/logs/laravel.log`

Saya membuka:

```text
lms/storage/logs/laravel.log
```

Salah satu exception yang tercatat:

```text
testing.ERROR: Vite manifest not found at: .../lms/public/build/manifest.json
```

Pada stack trace-nya, baris pertama yang menunjuk ke folder `app/` adalah:

```text
lms/app/Http/Middleware/EnsureUserHasRole.php(32)
```

Dari sini terlihat bahwa error bisa berasal dari middleware, bukan hanya dari controller. Exception tersebut muncul saat test dijalankan sebelum aset Vite dibangun.

### 2.4 Memasang `DB::listen()` dan membuka halaman rekap nilai

Saya memasang listener sementara di `AppServiceProvider`:

```php
\Illuminate\Support\Facades\DB::listen(function ($query) {
    \Illuminate\Support\Facades\Log::info('SQL_DEBUG', [
        'sql' => $query->sql,
        'bindings' => $query->bindings,
        'time' => $query->time,
    ]);
});
```

Lalu saya menjalankan test yang membuka halaman rekap nilai dosen:

```bash
php artisan test --filter=test_course_grades_page_has_no_lazy_loading_violations
```

Hasil test: **PASS (1 test, 1 assertion)**.

Query yang terlihat di log antara lain:

```sql
select * from "courses" where "id" = ? limit 1
select * from "users" where "users"."id" in (1)
select count(*) as aggregate from "submissions"
  where exists (select * from "assignments" where ...)
select * from "submissions"
  where exists (select * from "assignments" where ...)
  order by "submitted_at" desc limit 25 offset 0
```

Dari log ini terlihat bahwa halaman rekap nilai mengambil data course, user/dosen, jumlah submission, lalu daftar submission dengan pagination. Listener `DB::listen()` sudah dihapus kembali setelah latihan.

---

## 3. BREAK — Tujuh percobaan

Semua perubahan sengaja di bawah ini dilakukan sementara di lokal, diamati hasilnya, lalu dikembalikan. Tidak ada kode rusak yang di-commit.

| # | Percobaan | Hasil aktual | Pelajaran |
|---:|---|---|---|
| 1 | Test hanya `assertStatus(200)`, lalu isi `CourseController@index` dihapus/diganti `return response('', 200)` | **PASS (1 test, 1 assertion)** | Test yang hanya mengecek status bisa tetap hijau walaupun isi halaman sudah hilang. Assertion isi halaman penting. |
| 2a | Hapus `Gate::authorize('create', Course::class)` dari `CourseController@store` | `CourseAdminCrudTest` + `SecurityMatrixCoverageTest` tetap **PASS (10 test, 39 assertions)** | Rute admin masih dilindungi middleware `role:admin`, jadi menghapus Gate di controller belum tentu membuat test merah. |
| 2b | Hapus `Gate::authorize('view', $course)` dari `CourseController@show` | `WeekFiveIdorTest` **FAIL**: expected 403, received 200 di `tests/Feature/WeekFiveIdorTest.php:98` | Di rute dosen, policy adalah pertahanan utama untuk kepemilikan course. Menghapusnya langsung membuat test keamanan merah. |
| 3 | Hapus `RefreshDatabase` dari test sementara, jalankan bersama test lain | **FAIL**: `no such table: users` (2 failed, 5 passed) | Di proyek ini test memakai SQLite `:memory:`. Tanpa `RefreshDatabase`, skema database belum dibuat, sehingga test gagal sebelum sempat mengotori data. |
| 4 | Test yang mengharapkan data seeder, dijalankan tanpa seeding | **FAIL**: `Failed asserting that a row in the table [users] matches ... admin@kampuslms.test` di `ZTempSeederTest.php:14` | Test yang bergantung pada data seeder rapuh. Di CI, hasilnya bergantung pada apakah CI menjalankan seeder yang sama. |
| 5 | Hapus `Storage::fake('local')` dari `StudentSubmissionUploadTest`, lalu jalankan test upload | Test tetap **PASS (1 test, 3 assertions)**, tetapi file sungguhan muncul di `storage/app/private/submissions/1/goHPM...pdf` | Tanpa `Storage::fake()`, file asli menumpuk di storage. File hasil latihan sudah dihapus manual. |
| 6 | Rusakkan `down()` migration performance index dengan `throw new RuntimeException(...)` | `PerformanceIndexTest` **FAIL**: error di `database/migrations/2026_11_02_000001_add_performance_indexes.php:37` (1 failed, 1 passed) | Test rollback memang menangkap migrasi `down()` yang rusak. Ini juga alasan Bug #1 tentang index FK perlu dijaga. |
| 7 | Tinggalkan `dd()` di awal `CourseController@index`, lalu jalankan test pencarian course | Output: `"Latihan BREAK 7: dd() sengaja ditinggalkan di controller." // app\Http\Controllers\CourseController.php:20` dan proses test berhenti | `dd()` menghentikan proses test, sehingga test setelahnya tidak dijalankan. Jangan pernah membawa `dd()` ke PR. |

### 3.1 Catatan penting dari BREAK nomor 2

Nomor 2 adalah inti latihan. Hasilnya tidak selalu “hapus Gate langsung merah”:

- Pada rute admin, masih ada middleware `role:admin`, jadi test tetap hijau saat Gate di controller dihapus.
- Pada rute dosen, `CoursePolicy::view` adalah satu-satunya pengecekan kepemilikan course. Saat Gate dihapus, dosen lain bisa membuka course orang lain dan test langsung merah.

Kesimpulannya: test keamanan harus menguji jalur yang benar-benar hanya dilindungi oleh policy, bukan hanya jalur yang masih punya middleware tambahan.

---

## 4. Bahan Bug #1 untuk Yusuf

Kasus yang saya pilih: **Rollback index FK `courses.lecturer_id` (`8c6f228`)**.

Ringkasnya:

- **Gejala:** `down()` migration performance index menghapus index komposit `courses_lecturer_id_status_index` tanpa memastikan index tunggal `lecturer_id` tetap ada. Pada MySQL, rollback berisiko gagal karena index yang mendukung foreign key ikut terdampak.
- **Cara mempersempit:** membandingkan `up()` dan `down()`, memeriksa foreign key di migration `create_courses_table`, lalu memeriksa commit `8c6f228` dan `PerformanceIndexTest`.
- **Root cause:** rollback tidak mempertimbangkan kebutuhan index oleh foreign key.
- **Perbaikan:** commit `8c6f228` menambahkan guard `Schema::hasIndex('courses', ['lecturer_id'])` sebelum index komposit dihapus.
- **Test pencegah:** `PerformanceIndexTest::test_index_migration_is_present_and_rollback_is_reversible`.

Catatan: verifikasi manual di MySQL tetap disarankan karena test lokal memakai SQLite.

---

## 5. Kesimpulan

- Test CRUD admin sudah ditambahkan dan seluruh suite lulus.
- `ExampleTest` sekarang mengecek isi halaman, bukan hanya status HTTP.
- READ menunjukkan bahwa stack trace dan log query bisa membantu menemukan sumber masalah.
- BREAK menunjukkan bahwa test status-only lemah, otorisasi harus diuji pada jalur yang benar-benar bergantung pada policy, dan `Storage::fake()`, `RefreshDatabase`, serta migrasi `down()` bukan formalitas.
- Semua perubahan sementara untuk latihan sudah dikembalikan. Tidak ada kode sengaja dirusak yang di-commit.
