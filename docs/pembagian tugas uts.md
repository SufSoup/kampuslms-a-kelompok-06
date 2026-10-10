# Rencana Eksekusi Komprehensif: Tugas 2 (Milestone M2 Minggu 7) & Kesiapan Penuh UTS Minggu 8

## 1. Audit Mendalam Kondisi Kode Saat Ini (Blind Spot Analysis)

Setelah penelusuran menyeluruh ke dalam direktori `lms/`, ditemukan bahwa kondisi saat ini baru siap di level **REST API (Week 6)**, sedangkan untuk **Web UI (Blade & Auth Session)** yang akan diuji pada **Demo UTS & Milestone M2**, terdapat gap signifikan:

| Komponen | Status Saat Ini di Kode | Dampak pada Demo UTS |
|---|---|---|
| **Web Authentication** | `routes/web.php` route `/login` masih me-return `view('welcome')`. Tidak ada form login, tidak ada proses `session()->regenerate()`. | **Aplikasi tidak bisa di-demo sama sekali** dari browser jika di-seed bersih. |
| **Dashboard** | Route `/dashboard` me-return `view('dashboard')`, tetapi file tersebut **belum ada**. | Error 500 saat redirect setelah login. |
| **Policies (`app/Policies/`)** | **Direktori `app/Policies/` belum ada sama sekali.** | Tidak ada satupun Policy model. Celah IDOR masih terbuka atau hanya dilindungi `abort_unless` parsial. |
| **CRUD Materi (Web)** | `MaterialController` hanya punya method `show()`. Tidak ada `create()`, `store()`, `edit()`, `update()`, `destroy()`. | Dosen tidak bisa membuat materi di web (gagal Demo UTS Bagian 1.b). |
| **CRUD Tugas (Web)** | `AssignmentController` hanya punya method `show()`. Bahkan di method `show()`, terdapat bug: mahasiswa terdaftar justru di-`abort_unless` (403)! | Dosen tidak bisa membuat tugas; mahasiswa tidak bisa melihat tugas. |
| **Enrollment Mahasiswa** | Tidak ada controller & view untuk mendaftarkan mahasiswa ke mata kuliah (`course_user`). | Admin tidak bisa mendaftarkan mahasiswa (gagal Demo UTS Bagian 1.a). |
| **Views yang Hilang** | Folder `resources/views/assignments/`, `resources/views/auth/`, dan view `dashboard.blade.php` **belum ada sama sekali**. Di `materials/` hanya ada `show.blade.php`. Di `courses/show.blade.php` tidak menampilkan daftar materi, tugas, dan enrollment. | Halaman-halaman inti kosong/error saat diklik. |
| **Fitur 17 (Diferensiasi)** | Controller & View web untuk Sumber Belajar Tambahan belum ada (baru ada di level API). | Fitur diferensiasi kelompok belum bisa didemonstrasikan di web. |
| **Uji Keamanan & Skrip** | `docs/keamanan.md` dan `scripts/test-authz.sh` **belum dibuat**. | Gagal di Bagian 3 Uji Keamanan UTS. |
| **Test Suite** | `SearchAndPaginationTest` gagal 7 assertions karena route name bentrok. | CI tidak hijau. |

---

## 2. Pemetaan Lengkap: Kebutuhan UTS vs Solusi Kode

### A. Bagian 1 — Demonstrasi Alur Aplikasi (7 Menit)
1. **Prakondisi (`php artisan migrate:fresh --seed`):**
   - Seeder `DatabaseSeeder.php` sudah memuat 1 Admin, 3 Dosen, 30 Mahasiswa, 5 Course aktif, relasi enrollment, materials, assignments, submissions, dan learning resources. Dipastikan bersih dan idempotent.
2. **Skenario a (Admin):**
   - Admin login (`admin@kampuslms.test` / `password`).
   - Admin membuat mata kuliah baru (`admin/courses/create`), menetapkan dosen pengampu.
   - Admin membuka detail mata kuliah tersebut, masuk ke seksi **Mahasiswa Terdaftar (Enrollment)**, memilih mahasiswa dari dropdown/checkbox, lalu submit (`POST /admin/courses/{course}/enrollments`). Data tersimpan di `course_user`.
3. **Skenario b (Dosen):**
   - Dosen login (`dosen@kampuslms.test` / `password`).
   - Dashboard & index hanya menampilkan mata kuliah yang diampu dosen tersebut (`$user->taughtCourses()`).
   - Masuk ke detail MK:
     - Klik **"Tambah Materi"** → form judul, deskripsi, tipe (link/file), url/keterangan → simpan.
     - Klik **"Tambah Tugas"** → form judul, instruksi, `due_at`, `max_score`, `allow_late` → simpan.
     - (Fitur 17): Klik **"Tambah Sumber Belajar"** → simpan referensi belajar kurasi.
4. **Skenario c (Mahasiswa):**
   - Mahasiswa login (`mahasiswa@kampuslms.test` / `password`).
   - Dashboard mahasiswa hanya menampilkan mata kuliah yang di-enroll (`$user->courses()`).
   - Buka mata kuliah: melihat materi dan tugas yang baru dibuat dosen.
   - Buka Sumber Belajar Tambahan (Fitur 17) dan klik **"Tandai Sudah Dibaca"** (toggle-read).
   - **Simulasi IDOR Langsung:** Mahasiswa mencoba mengubah URL browser ke ID mata kuliah lain yang tidak diikutinya → Sistem merespons dengan halaman **403 Akses Ditolak**.

---

### B. Bagian 2 — Code Walkthrough (8 Menit, 12 Bank Soal UTS)
Matriks kesiapan anggota kelompok untuk menjawab 12 bank soal UTS:

| No | Soal Walkthrough | Penanggung Jawab | Lokasi File & Penjelasan Teknis |
|---|---|---|---|
| 1 | Constraint migrasi & tujuannya | **Yusuf** | `database/migrations/*_create_course_user_table.php`: foreign key cascade, unique composite. |
| 2 | Mengapa `course_user` punya unique composite? | **Yusuf** | `table->unique(['course_id', 'user_id'])`. Mencegah mahasiswa terdaftar ganda yang merusak absensi & rekap nilai. |
| 3 | Apa itu mass assignment & pencegahannya? | **Syahid** | Pengisian kolom otomatis dari payload. Dicegah dengan `$fillable` eksplisit, hindari `$guarded = []`. |
| 4 | Mengapa `role` tidak ada di `$fillable`? | **Ikhsyan** | Mencegah eskalasi hak akses (attacker menyisipkan `role=admin`). Kolom `role` diisi manual di server. |
| 5 | Tunjukkan satu Policy & jelaskan tiap baris | **Farraz** | `app/Policies/MaterialPolicy.php` atau `CoursePolicy.php`: menjelaskan logika `lecturer_id === $user->id` & relasi `exists()`. |
| 6 | Mengapa `@can` di Blade tidak cukup? Peragakan | **Farraz** | `@can` hanya menyembunyikan tombol UI di HTML. URL tetap bisa ditembak via `curl`/Postman bila controller tidak dilindungi `Gate::authorize()`. |
| 7 | Perbedaan HTTP 401 dan 403 | **Syahid** | 401 = *Unauthenticated* (belum login). 403 = *Forbidden* (sudah login tapi tidak berhak atas data objek tersebut). |
| 8 | Mengapa `$request->validated()` lebih aman dari `$request->all()`? | **Syahid** | `validated()` hanya mengambil field yang lolos validasi Form Request; `all()` menyertakan input liar tak tervalidasi. |
| 9 | Filter pencarian di query string vs session | **Yusuf** | Query string (`?q=...&status=...`) bersifat stateless, dapat dibagikan (shareable), bookmarkable, dan tidak merusak tab lain. |
| 10 | Mengapa `session()->regenerate()` dipanggil saat login? | **Ikhsyan** | Menghindari serangan *Session Fixation* (penyerang menanamkan session ID sebelum korban login). |
| 11 | Di mana middleware didaftarkan di Laravel 12? | **Ikhsyan** | Di `bootstrap/app.php` via `$middleware->alias([...])` karena `Kernel.php` sudah dihapus di Laravel 11/12. |
| 12 | Bagian yang ditulis dengan bantuan AI & apa yang diubah | **Semua** | Menjelaskan perbaikan sintaks usang AI: mengganti `$this->authorize()` (Laravel 10) menjadi `Gate::authorize()` (Laravel 12). |

---

### C. Bagian 3 — Uji Keamanan Langsung (5 Menit, `docs/keamanan.md`)
Penguji akan membuka `docs/keamanan.md`, memilih satu baris dari tabel Titik Rawan IDOR, dan mengujinya melalui `curl` atau browser.

**8 Baris Tabel Titik Rawan IDOR di `docs/keamanan.md`:**
1. **Dosen A akses/edit mata kuliah Dosen B** (`GET/PUT /dosen/courses/{id_B}`) → Dilindungi `CoursePolicy::view/update` → **HTTP 403**.
2. **Mahasiswa buka mata kuliah yang tidak di-enroll** (`GET /mahasiswa/courses/{id_lain}`) → Dilindungi `CoursePolicy::view` via `exists()` → **HTTP 403**.
3. **Mahasiswa A buka submission Mahasiswa B** (`GET /submissions/{id_B}`) → Dilindungi `SubmissionPolicy::view` → **HTTP 403**.
4. **Dosen A edit/hapus materi Dosen B** (`PUT/DELETE /materials/{id_B}`) → Dilindungi `MaterialPolicy::update/delete` → **HTTP 403**.
5. **Dosen A edit/hapus tugas Dosen B** (`PUT/DELETE /assignments/{id_B}`) → Dilindungi `AssignmentPolicy::update/delete` → **HTTP 403**.
6. **Mahasiswa A toggle read sumber belajar atas nama Mahasiswa B** (`POST /resources/{id}/toggle-read`) → Aksi diikat ke `auth()->id()` → **Anti-IDOR**.
7. **Dosen/Mahasiswa akses manajemen pengguna Admin** (`GET /admin/users`) → Dilindungi middleware `role:admin` → **HTTP 403**.
8. **Injeksi `role=admin` pada form update pengguna** → Proteksi Form Request tanpa `role` di `$fillable` → **Role tidak berubah**.

---

## 3. Rincian Pembagian Tugas Tim (4 Anggota)

Pembagian tugas ini dirancang agar **seluruh kode yang kurang tertutupi secara tuntas** dan **setiap anggota memiliki commit yang seimbang di `git log`**:

### 1. Muhammad Ikhsyan (`10241049`)
* **Web Authentication:**
  - Controller `AuthController` (`showLoginForm`, `login` dengan `session()->regenerate()`, `logout` dengan `session()->invalidate()`).
  - Form Request `LoginRequest`.
  - View `resources/views/auth/login.blade.php`.
  - Update `resources/views/components/layout.blade.php` (indikator user login, role badge, tombol form logout `@csrf`).
* **Fitur 17 (Diferensiasi Kelompok 06):**
  - Policy `LearningResourcePolicy` (view, create, update, delete, toggleRead).
  - Controller `LearningResourceController` (web CRUD dosen & toggle-read mahasiswa anti-IDOR).
  - Form Request `StoreLearningResourceRequest` & `UpdateLearningResourceRequest`.
  - Integrasi tampilan Sumber Belajar Tambahan di detail mata kuliah.
* **Dokumentasi Keamanan:**
  - Penulisan berkas `docs/keamanan.md` (Tabel Titik Rawan IDOR + skenario curl).

### 2. Muhammad Farraz Kanz (`10241047`)
* **Policies Course & Material:**
  - `CoursePolicy`: viewAny, view, create, update, delete.
  - `MaterialPolicy`: view, create, update, delete.
* **Web CRUD Materi Dosen:**
  - Update `MaterialController`: implementasikan `create()`, `store()`, `edit()`, `update()`, `destroy()` dengan `Gate::authorize()`.
  - Form Request `StoreMaterialRequest` & `UpdateMaterialRequest`.
  - Views `resources/views/materials/create.blade.php` dan `edit.blade.php`.
* **Dashboard Terpadu:**
  - View `resources/views/dashboard.blade.php`: widget dinamis per peran (Admin: statistik & aksi cepat, Dosen: daftar MK diampu, Mahasiswa: daftar MK & tugas aktif).
* **Skrip Pengujian:**
  - Pembuatan skrip otomatis `scripts/test-authz.sh` (pengujian curl multi-role dengan exit code 0).

### 3. Muhammad Yusuf Pratama (`10241053`)
* **Admin Course Enrollment Management:**
  - Controller `CourseEnrollmentController` (`store` dan `destroy` mahasiswa pada `course_user`).
  - View integrasi pada `courses/show.blade.php` (khusus admin: tabel mahasiswa terdaftar + form dropdown mendaftarkan mahasiswa baru).
* **Penyaringan Query-Level & Navigasi:**
  - Refactor `CourseController::index()` menggunakan query filter `match($user->role)` agar tidak bocor.
  - Penataan route web & perbaikan `tests/Feature/SearchAndPaginationTest.php` agar 7 test gagal menjadi **PASS 100%**.
  - Update view `courses/index.blade.php` dan `courses/show.blade.php` agar tombol Aksi dilindungi `@can`.

### 4. Muhammad Syahid Ridho (`10241051`)
* **Policies Assignment, Submission, & Grade:**
  - `AssignmentPolicy`: view, create, update, delete.
  - `SubmissionPolicy`: view, create, grade.
  - `GradePolicy`: grade.
* **Web CRUD Tugas Dosen & View Mahasiswa:**
  - Update `AssignmentController`: implementasikan `create()`, `store()`, `edit()`, `update()`, `destroy()`, dan perbaiki method `show()` agar mahasiswa ter-enroll bisa mengakses tugas.
  - Form Request `StoreAssignmentRequest` & `UpdateAssignmentRequest`.
  - Views `resources/views/assignments/create.blade.php`, `edit.blade.php`, dan `show.blade.php`.
  - Update `SubmissionController`: pastikan view submission hanya bisa dibuka oleh pemiliknya atau dosen pengampu.
* **Feature Testing Otorisasi M2:**
  - Pembuatan test suite `tests/Feature/MilestoneTwoAuthzTest.php` untuk memvalidasi proteksi IDOR, session fixation, dan role middleware secara otomatis.

---

## 4. Checklist Definition of Done (DoD)

- [ ] `php artisan migrate:fresh --seed` berjalan mulus dan mengisi database tanpa error.
- [ ] Login web berfungsi dengan 3 akun demo (`admin@kampuslms.test`, `dosen@kampuslms.test`, `mahasiswa@kampuslms.test` / password: `password`).
- [ ] Admin dapat membuat mata kuliah, memilih dosen, dan mendaftarkan mahasiswa (`course_user`).
- [ ] Dosen dapat membuat materi dan tugas pada mata kuliah yang diampunya.
- [ ] Mahasiswa hanya melihat mata kuliah yang diikutinya beserta materi dan tugasnya.
- [ ] Sumber belajar Fitur 17 berfungsi (dosen menambah kurasi, mahasiswa menandai sudah dibaca).
- [ ] Seluruh Policy aktif di `app/Policies/` dan dipanggil via `Gate::authorize()` di controller.
- [ ] Celah IDOR tertutup (akses URL acak menghasilkan HTTP 403 Forbidden).
- [ ] `docs/keamanan.md` terisi lengkap dan siap diuji penguji UTS.
- [ ] `scripts/test-authz.sh` lolos 100%.
- [ ] `php artisan test` 100% hijau (seluruh test pass).
- [ ] Kompilasi aset `npm run build` sukses.
- [ ] Branch `main` memiliki riwayat commit dari keempat anggota tim melalui PR.
