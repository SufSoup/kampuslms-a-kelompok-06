# Catatan Praktikum Minggu 14 — Debugging dan Testing

- **Nama:** Muhammad Ikhsyan
- **NIM:** 10241049
- **Kelompok:** Kelompok 06 (Kelas A)
- **Topik:** Debugging Sistematis, Audit Kualitas Test, & Penutupan Celah Matriks Keamanan
- **Branch:** `ikhsyan`

---

## 1. READ — Membaca Kegagalan, Log, & Query

### 1.1 Hasil Awal Test Suite
Sebelum penambahan test Minggu 14, test suite berstatus **202 passed (803 assertions)**. Setelah penambahan `SecurityMatrixCoverageTest` (5 test, 24 assertions), seluruh suite berjalan **207 passed (827 assertions)**.

### 1.2 Stack Trace Saat Test Sengaja Dibuat Gagal
Saat `Gate::authorize('view', $course)` di `lms/app/Http/Controllers/CourseController.php` dinonaktifkan sementara untuk uji mutasi, eksekusi `php artisan test --filter="WeekFiveIdorTest|SecurityMatrixCoverageTest"` gagal dengan output:

```text
   FAILED  Tests\Feature\SecurityMatrixCoverageTest > unenrolled student cannot view another course detail
  Expected response status code [403] but received 200.
Failed asserting that 200 is identical to 403.

  at tests/Feature/SecurityMatrixCoverageTest.php:40
     38▕             ->get(route('mahasiswa.courses.show', $otherCourse));
  ➜  40▕         $response->assertForbidden()
```

Baris pertama yang relevan untuk ditelusuri pada kegagalan pengujian adalah berkas proyek (`tests/Feature/SecurityMatrixCoverageTest.php:40` dan `tests/Feature/WeekFiveIdorTest.php:98`), bukan baris internal framework di `vendor/`.

### 1.3 Pembacaan `lms/storage/logs/laravel.log`
Pemeriksaan terhadap log aplikasi nyata menemukan catatan error sebelumnya, antara lain:
- Baris `10235`: `local.ERROR: SQLSTATE[HY000] [2002] Connection refused` saat MySQL lokal belum aktif.
- Baris `10316`: `local.ERROR: The "intl" PHP extension is required to use the [format] method.` (ketika memanggil formatter angka tanpa ekstensi `php-intl`).
- Baris `10388`: `testing.ERROR: Simulated queue failure. at .../lms/tests/Feature/QueueReliabilityTest.php:32` diikuti frame stack trace `#0` s.d. `#13` yang mayoritas berada di `vendor/laravel/framework/...`, sedangkan akar pemanggilnya langsung terbaca di baris header exception (`QueueReliabilityTest.php:32`).

### 1.4 Pengamatan `DB::listen()` pada Rekap Nilai
Pengukuran dijalankan secara langsung melalui `DB::listen()` pada runtime SQLite in-memory (tanpa meninggalkan perubahan kode di `AppServiceProvider.php`) untuk rute `GET /dosen/courses/{course}/grades` (`SubmissionController@courseGrades`):
- **Total query tercatat:** **12 query SQL** (sudah termasuk query session dan counter notifikasi navbar).
- Query inti rekap nilai (`courses`, `users` pengampu, `count(*)` pagination `submissions`, `submissions`, eager loading `users` mahasiswa, `assignments`, dan `grades`) berjalan berurutan tanpa N+1 (`with(['student', 'assignment', 'grade.grader'])`).

---

## 2. BREAK — Tujuh Skenario Kerusakan Test & Proteksi

Sesuai prinsip kejujuran bukti, skenario yang **benar-benar dijalankan** dibedakan dari skenario **analisis teknis**:

| # | Skenario BREAK (`modul.md §14.3`) | Status Pelaksanaan | Hasil Aktual / Analisis |
|---|---|---|---|
| **1** | Test hanya `assertStatus(200)`, hapus isi controller | **Analisis kode** | Pada kondisi awal di `ExampleTest::test_the_application_returns_a_successful_response()`, test hanya memeriksa `$this->get('/')->assertStatus(200)`. Test semacam ini akan tetap hijau meski view dikosongkan selama HTTP 200 dikembalikan. |
| **2** | Hapus `Gate::authorize()` dari controller, jalankan test | **Dijalankan nyata (mutasi sementara + revert)** | 1) Menghapus sementara `Gate::authorize('view', $course)` di `CourseController::show`: `SecurityMatrixCoverageTest` & `WeekFiveIdorTest` langsung **FAIL** (`200` alih-alih `403`, 2 failed / 8 passed).<br>2) Menghapus sementara `Gate::authorize('toggleRead', $resource)` di `LearningResourceController::toggleRead`: `LearningResourceWebTest::test_unenrolled_student_cannot_toggle_read_status_anti_idor` langsung **FAIL** (`302` alih-alih `403`, 1 failed / 12 passed). |
| **3** | Hapus `RefreshDatabase`, jalankan test dua kali | **Analisis teknis** | Tanpa `RefreshDatabase`, record dari test pertama tetap bertahan di database dan memicu tabrakan unique constraint (`email`, `courses.code`, atau `(course_id, user_id)`) pada eksekusi berikutnya. |
| **4** | Test bergantung pada data seeder, jalankan di CI | **Analisis teknis** | CI di `.github/workflows/ci.yml` memang menjalankan `migrate:fresh --seed` sebelum `php artisan test`, namun setiap feature test yang memakai `RefreshDatabase` me-reset tabel per test case sehingga test yang tidak memanggil `$this->seed()` atau factory sendiri akan gagal menemukan data seeder. |
| **5** | Hapus `Storage::fake()`, jalankan test upload | **Analisis teknis** | Tanpa `Storage::fake('local')`, test upload seperti `StudentSubmissionUploadTest` dan `MaterialWebCrudTest` akan menulis berkas fisik permanen ke `lms/storage/app/private/`. |
| **6** | Rusak satu migrasi `down()`, jalankan test | **Diverifikasi via CI workflow** | `.github/workflows/ci.yml` secara eksplisit menjalankan `php artisan migrate:refresh --force` setelah `migrate:fresh --seed` untuk membuktikan seluruh migrasi (termasuk `learning_resources` dan `add_performance_indexes`) dapat di-rollback bersih. |
| **7** | Tinggalkan `dd()` di controller, jalankan test | **Audit kode bersih** | Pemindaian `grep -rn -E "dd\(|dump\(" lms/app lms/resources/views` menghasilkan **0 temuan**. Secara perilaku PHPUnit, `dd()` memanggil `exit(1)` di tengah proses sehingga memutus eksekusi suite test berikutnya. |

---

## 3. FIX — Analisis Repo Cacat Minggu 14 (`w14`)

> **Catatan status:** Perbaikan langsung pada repository eksternal `kampuslms-broken` branch `w14` **belum dikerjakan** dari ruang kerja ini (koordinasi sinkronisasi `w14` berada pada pembagian tugas kelompok). Berikut analisis perbaikan terhadap 8 pola test cacat yang dijelaskan pada `modul.md §14.3`:

1. **Tiga test hanya memeriksa status (`assertStatus(200)` / `assertOk()`):** Harus diperkuat dengan `assertSee(...)` pada data spesifik yang dibuat factory dan `assertDontSee(...)` terhadap data pihak lain.
2. **Dua test tanpa assertion bermakna:** Wajib menambahkan `assertDatabaseHas(...)` / `assertDatabaseMissing(...)` atau `Notification::assertSentTo(...)` untuk membuktikan efek samping sebenarnya.
3. **Satu test bergantung pada seeder:** Ganti ketergantungan seeder dengan data terisolasi yang dibuat langsung lewat `Factory` di dalam method test.
4. **Satu test bergantung pada urutan test sebelumnya:** Tambahkan trait `RefreshDatabase` dan buat seluruh fixture awal di dalam masing-masing method test agar independen.
5. **Satu test otorisasi hanya memeriksa "tidak 200":** Ubah menjadi `assertForbidden()` (`403`) secara eksplisit agar redirect `302` atau error server `500` tidak dianggap lolos otorisasi.

---

## 4. BUILD — Kontribusi Muhammad Ikhsyan (`10241049`)

Menggunakan prinsip **Ponytail** (tidak menduplikasi 202 test yang sudah berjalan baik dan tidak membuat class helper test baru), kontribusi difokuskan hanya pada penutupan celah cakupan keamanan dan sinkronisasi dokumentasi matriks keamanan:

### 4.1 Penambahan Feature Test: `lms/tests/Feature/SecurityMatrixCoverageTest.php`
Menutup 3 celah pada `docs/keamanan.md` dengan 5 test case (24 assertions):
1. `test_enrolled_student_can_view_course_detail` — mahasiswa terdaftar dapat membuka `mahasiswa.courses.show` dan melihat nama serta kode mata kuliah (jalur sukses).
2. `test_unenrolled_student_cannot_view_another_course_detail` — mahasiswa tidak terdaftar ditolak `403` saat membuka `mahasiswa.courses.show` MK lain (**Baris 2 `docs/keamanan.md`**).
3. `test_admin_can_access_admin_users_and_courses_management` — admin dapat mengakses `admin.users.index` dan `admin.courses.index` serta melihat data nyata.
4. `test_non_admin_cannot_access_admin_users_or_courses_routes` — dosen dan mahasiswa ditolak `403` pada `GET admin.users.index`, `GET admin.courses.index`, `POST admin.courses.store`, dan `PUT admin.users.update`, tanpa perubahan DB (**Baris 7 `docs/keamanan.md`**).
5. `test_user_role_cannot_be_changed_via_mass_assignment` — memastikan atribut `role` tidak berada di `User::$fillable` sehingga `$user->fill(['role' => 'admin'])` tidak mengubah kolom `role` di database (**Baris 8 `docs/keamanan.md`**).

### 4.2 Pembaruan `docs/keamanan.md`
- Menambahkan kolom **`Test`** pada seluruh 8 baris tabel *Titik Rawan IDOR & Matriks Pertahanan*, memetakan setiap endpoint rawan ke nama class dan method test aktual yang mengujinya.
- Meluruskan penulisan Baris 1 dari `GET / PUT /dosen/courses/{course}` menjadi `GET /dosen/courses/{course}` agar sesuai dengan `lms/routes/web.php:94` (rute `PUT` course hanya tersedia di `/admin/courses/{course}`).

### 4.3 Verifikasi Pengujian
- `php artisan test --filter=SecurityMatrixCoverageTest`: **5 passed (24 assertions)**.
- `php artisan test` (full suite): **207 passed (827 assertions)**, 0 failed.

---

## 5. Checkpoint Minggu 14 — Panduan Interview

1. **Peragakan: hapus satu fitur, tunjukkan test yang menjadi merah karenanya.**
   - Nonaktifkan `Gate::authorize('view', $course);` pada `CourseController::show()` atau `Gate::authorize('toggleRead', $resource);` pada `LearningResourceController::toggleRead()`. Jalankan `php artisan test --filter=SecurityMatrixCoverageTest` atau `LearningResourceWebTest` — test langsung gagal karena respons berubah menjadi `200`/`302` alih-alih `403`.
2. **Kenapa test yang hanya `assertStatus(200)` sering tidak bermakna?**
   - Karena status `200` hanya membuktikan route merespons tanpa fatal error 500, bukan bahwa otorisasi bekerja, data yang benar tampil, atau aksi berhasil tersimpan di database. Controller yang dikosongkan dan hanya me-return view kosong tetap memberi status `200`.
3. **Kenapa test jalur ditolak lebih penting daripada test jalur sukses di aplikasi ini?**
   - Karena kegagalan jalur sukses langsung terlihat oleh pengguna saat aplikasi dipakai, sedangkan celah pada jalur ditolak (misal lupa memasang `Gate::authorize()`, sehingga mahasiswa lain bisa mengunduh submission atau mengubah status baca) bekerja diam-diam tanpa pesan error dan menjadi kerentanan IDOR.
4. **Apa fungsi `RefreshDatabase`? Kenapa migrasi harus reversible agar ia bekerja?**
   - `RefreshDatabase` memastikan setiap test dijalankan pada kondisi skema database bersih dan dibungkus transaksi/rollback agar satu test tidak mengotori data test berikutnya. Saat pertama kali menyiapkan database uji (atau `migrate:refresh`), Laravel bergantung pada method `up()` dan `down()` migrasi agar dapat membangun ulang dan mengembalikan skema tanpa error constraint.
5. **Buka `docs/debugging.md`. Ceritakan satu bug: bagaimana Anda mempersempit penyebabnya?**
   - *(Catatan: berkas `docs/debugging.md` dikompilasi oleh Yusuf sesuai pembagian tugas kelompok; dari sisi saya, kasus yang siap diceritakan saat interview adalah celah `env()` pasca `config:cache` atau kegagalan silent queue worker tanpa proses aktif — dimulai dari reproduksi dengan `config:cache` / mematikan worker, memeriksa konfigurasi cache vs `.env`, dan menguncinya dengan `PerformanceEagerLoadingTest::test_no_env_calls_outside_config_directory` / `QueueReliabilityTest`.)*
6. **Kenapa `dd()` tidak boleh masuk PR, sementara `Log::info()` boleh?**
   - `dd()` menghentikan eksekusi aplikasi (`exit`) dan mencetak struktur variabel internal langsung ke respons HTTP pengguna (serta memutus test suite), sedangkan `Log::info()` menulis informasi diagnostik ke berkas log server (`storage/logs/laravel.log`) tanpa mengganggu alur respons pengguna maupun mengekspos data internal ke browser.
