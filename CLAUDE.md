# CLAUDE.md — Panduan AI Agent untuk KampusLMS

Petunjuk operasional, arsitektur, dan kontrak kerja untuk Claude Code dan AI Agent pada repositori **KampusLMS Kelompok 06 (Kelas A)**. Disarikan dari [`modul.md`](./modul.md).

---

## 1. Ringkasan Proyek

- **Mata Kuliah:** SI2514024 — Pemrograman Web (Semester 5, Ganjil 2026/2027)
- **Tim (Kelompok 06 - Kelas A):**
  - Muhammad Farraz Kanz (`10241047`)
  - Muhammad Ikhsyan (`10241049`)
  - Muhammad Syahid Ridho (`10241051`)
  - Muhammad Yusuf Pratama (`10241053`)
- **Aplikasi:** Berada di subdirektori [`lms/`](lms/). Seluruh perintah artisan, composer, npm dijalankan di dalam `lms/`.
- **Tech Stack:** PHP 8.3+, Laravel 12.x, MySQL/MariaDB (SQLite in-memory untuk testing), Blade Components, Tailwind CSS, Vite.
- **Akun Demo Wajib:**
  - Admin: `admin@kampuslms.test` / `password`
  - Dosen: `dosen@kampuslms.test` / `password`
  - Mahasiswa: `mahasiswa@kampuslms.test` / `password`

---

## 2. Perintah Pengembangan Penting

Selalu jalankan perintah dari dalam direktori `lms/`:

```bash
cd lms

# Menjalankan seluruh test (PHPUnit / Pest)
php artisan test

# Menjalankan test tertentu
php artisan test --filter=WeekFiveIdorTest
php artisan test --filter=SearchAndPaginationTest

# Reset database & seed data lengkap
php artisan migrate:fresh --seed

# Memeriksa daftar rute
php artisan route:list

# Asset bundling
npm run dev     # Development
npm run build   # Production
```

---

## 3. Aturan Ketat Arsitektur Laravel 12

Model AI sering keliru menerapkan konvensi Laravel 10 ke bawah. **Wajib patuhi standar Laravel 12:**

1. **Bootstrap & Middleware:**
   - **TIDAK ADA** `app/Http/Kernel.php` atau `app/Console/Kernel.php`.
   - Middleware didaftarkan di [`lms/bootstrap/app.php`](lms/bootstrap/app.php) via `$middleware->alias([...])`.
   - Scheduler terdaftar di [`lms/routes/console.php`](lms/routes/console.php).
2. **Eloquent Model Casting:**
   - **TIDAK MENGGUNAKAN** properti `protected $casts = [...];`.
   - **WAJIB** menggunakan method:
     ```php
     protected function casts(): array
     {
         return [
             'due_at' => 'datetime',
             'read_at' => 'datetime',
             'password' => 'hashed',
             'is_recommended' => 'boolean',
         ];
     }
     ```
3. **Proteksi Mass Assignment:**
   - **DILARANG KERAS** `protected $guarded = [];`.
   - Definisikan `$fillable` secara eksplisit hanya untuk kolom aman.
   - Kolom sensitif (`role`, `lecturer_id`, `user_id`, `is_admin`) tidak boleh diisi via `$fillable` liar; isi eksplisit di controller dari `auth()->id()`.
4. **Validasi & Form Request:**
   - Gunakan Form Request terpisah (`StoreCourseRequest`, dll.).
   - Ambil data hanya melalui `$request->validated()`. **JANGAN** gunakan `$request->all()`.
   - Foreign key wajib divalidasi `exists:table,column`, enum wajib `in:val1,val2`.
5. **Pola PRG (Post-Redirect-Get):**
   - Setelah operasi POST/PUT/PATCH/DELETE berhasil, **wajib** redirect dengan flash session:
     ```php
     return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil disimpan.');
     ```
   - Dilarang mengembalikan view langsung dari request mutasi.
6. **Blade & Keamanan XSS:**
   - Gunakan komponen Blade: `<x-layout title="...">`.
   - Selalu escape output dengan `{{ $variable }}`. **DILARANG** `{!! $variable !!}` tanpa justifikasi keamanan eksplisit.
   - Setiap form wajib memiliki `@csrf`.
   - Tautan navigasi wajib memakai helper `route('name', $params)`, dilarang URL hardcoded.
7. **Pencarian & Pagination:**
   - Parameter filter harus disimpan di query string (`?q=...`), bukan session.
   - Pagination dengan filter wajib menyertakan `->withQueryString()`.
   - Eager loading (`with(...)`) wajib digunakan untuk mencegah N+1 query.

---

## 4. Keamanan & Proteksi IDOR (Fokus UTS & UAS)

- **Route Model Binding:** `show(Course $course)` hanya memverifikasi data ada di DB, **bukan** user berhak mengakses.
- **Otorisasi Backend:**
  - Mahasiswa **hanya** dapat melihat materi, tugas, dan sumber belajar dari mata kuliah yang diikutinya (`course_user`).
  - Mahasiswa **dilarang** melihat/mengunduh submission milik mahasiswa lain (`abort_unless($submission->user_id === auth()->id(), 403)`).
  - Dosen **hanya** dapat mengelola mata kuliah miliknya sendiri (`$course->lecturer_id === auth()->id()`).
  - Cek `@can` di Blade hanya untuk visual UI; otorisasi mutlak ditegakkan di Controller/Policy/Middleware.

---

## 5. Skema Database Inti (Bagian 4 modul.md — PATEN)

> ⚠️ **Dilarang mengubah atau menambah kolom pada 8 tabel inti berikut:**

- `users` — `id`, `name`, `email` (UK), `password`, `role` (admin|dosen|mahasiswa), `nim_nip` (UK nullable), timestamps, `deleted_at`.
- `courses` — `id`, `code` (UK), `name`, `description`, `sks`, `lecturer_id` (FK users restrict), `status` (draft|active|archived), timestamps.
- `course_user` — `id`, `course_id` (FK), `user_id` (FK), `enrolled_at`, timestamps. **Unique composite:** `['course_id', 'user_id']`.
- `materials` — `id`, `course_id` (FK cascade), `uploaded_by` (FK users), `title`, `description`, `type` (file|link), `file_path`, `original_name`, `file_size`, `mime_type`, `external_url`, timestamps.
- `assignments` — `id`, `course_id` (FK cascade), `created_by` (FK users), `title`, `instructions`, `due_at`, `max_score`, `allow_late`, `status`, timestamps. Index: `['course_id', 'due_at']`.
- `submissions` — `id`, `assignment_id` (FK), `user_id` (FK), `file_path`, `original_name`, `file_size`, `note`, `submitted_at`, `is_late`, timestamps. **Unique composite:** `['assignment_id', 'user_id']`.
- `grades` — `id`, `submission_id` (FK, UNIQUE), `graded_by` (FK users), `score`, `feedback`, `graded_at`, timestamps.
- `notifications` — `id` (UUID), `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, timestamps.

Setiap migrasi wajib **reversible** (method `down()` mengembalikan keadaan semula).

---

## 6. Fitur Diferensiasi: Fitur 17 (Kurasi Sumber Belajar Tambahan & Penanda Baca)

Kelompok 06 memilih **Fitur 17**: *Kurasi sumber belajar tambahan per mata kuliah, dengan penanda "sudah dibaca" per mahasiswa*.

### 6.1 Ketentuan Modul
- Menambah tabel baru di luar skema inti.
- **TIDAK MENGUBAH** tabel inti `courses` atau `users`.

### 6.2 Skema Tabel Baru
1. **`learning_resources`** (Daftar referensi kurasi dosen):
   - `id`: PK bigint unsigned
   - `course_id`: FK `courses.id` (cascadeOnDelete)
   - `added_by`: FK `users.id` (restrictOnDelete)
   - `title`: string (150)
   - `description`: text nullable
   - `type`: enum (`article`, `video`, `book`, `documentation`, `other`)
   - `url`: string (500)
   - `source_platform`: string (100) nullable (contoh: YouTube, MDN, Laravel Docs)
   - `is_recommended`: boolean (default false)
   - `timestamps`
2. **`learning_resource_reads`** (Pivot penanda baca mahasiswa):
   - `id`: PK bigint unsigned
   - `learning_resource_id`: FK `learning_resources.id` (cascadeOnDelete)
   - `user_id`: FK `users.id` (cascadeOnDelete)
   - `read_at`: timestamp (default now)
   - `timestamps`
   - **Constraint Wajib:** `unique(['learning_resource_id', 'user_id'])` (mencegah duplikasi tanda baca).

### 6.3 Relasi Model
- `Course` -> `hasMany(LearningResource::class)`
- `User` -> `belongsToMany(LearningResource::class, 'learning_resource_reads')->withPivot('read_at')->withTimestamps()`
- `LearningResource` -> `belongsTo(Course::class)`, `belongsTo(User::class, 'added_by')`, `belongsToMany(User::class, 'learning_resource_reads')`

### 6.4 Hak Akses & Proteksi IDOR Fitur 17
- **Dosen:** Hanya dapat CRUD sumber belajar pada mata kuliah yang dia ampu (`lecturer_id === auth()->id()`). Dapat melihat rekap/statistik pembaca.
- **Mahasiswa:** Hanya dapat melihat sumber belajar pada MK yang di-enroll (`course_user`).
- **Anti-IDOR:** Aksi toggle/mark read **wajib** mengikat ke `auth()->id()`. Mahasiswa A tidak dapat menandai status baca Mahasiswa B.

---

## 7. Definition of Done (DoD)

Kode atau PR dianggap selesai bila:
1. Migrasi dan seeder berjalan lancar (`php artisan migrate:fresh --seed`).
2. Validasi server-side lengkap via Form Request.
3. Proteksi IDOR aktif dan teruji via controller/policy.
4. Tidak ada query N+1 (eager loading diterapkan).
5. Minimal 1 feature test jalur sukses dan 1 feature test jalur gagal/ditolak (403/422).
6. Menggunakan Conventional Commits (`feat:`, `fix:`, `docs:`, `test:`, `refactor:`).
7. Pengembang memahami kode secara mendalam untuk keperluan code walkthrough.
