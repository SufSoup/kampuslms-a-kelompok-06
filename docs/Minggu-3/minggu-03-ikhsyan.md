## Notes Minggu 3 Pemrograman Web

**Nama:** Muhammad Ikhsyan  
**NIM:** 10241049  
**Kelompok:** Kelompok 06 (Kelas A)  
**Topik:** Database dan CRUD (Milestone M1 — Fondasi Data)

---

### READ — Analisis skema database & relasi

#### 1. Gambar ulang relasi antartabel inti KampusLMS
Berdasarkan spesifikasi proyek KampusLMS, relasi antartabel inti dirancang sebagai berikut:
- `users` (1) ── (N) `courses` (sebagai dosen pengampu: `lecturer_id`)
- `users` (N) ── (N) `courses` (sebagai peserta mata kuliah: pivot `course_user`)
- `courses` (1) ── (N) `materials` (materi perkuliahan: `course_id`)
- `courses` (1) ── (N) `assignments` (penugasan: `course_id`)
- `assignments` (1) ── (N) `submissions` (pengumpulan tugas: `assignment_id`)
- `users` (1) ── (N) `submissions` (mahasiswa pengumpul: `user_id`)
- `submissions` (1) ── (1) `grades` (penilaian submission: `submission_id`)
- `users` (1) ── (N) `grades` (dosen penilai: `graded_by`)
- `users` (1) ── (N) `notifications` (notifikasi sistem: `notifiable_id`)

#### 2. Tabel perilaku `onDelete` setiap Foreign Key
Setiap Foreign Key memiliki aturan penghapusan yang disesuaikan dengan aturan bisnis akademik:

| Foreign Key | Tabel Sumber | Perilaku `onDelete` | Alasan Desain |
|---|---|:---:|---|
| `courses.lecturer_id` | `users.id` | **`restrictOnDelete()`** | Dosen yang masih mengampu mata kuliah aktif dilarang dihapus agar data kurikulum dan histori akademik tidak hilang atau menjadi yatim (*orphan*). |
| `course_user.course_id` | `courses.id` | **`cascadeOnDelete()`** | Jika mata kuliah dihapus, seluruh data pendaftaran peserta pada kelas tersebut otomatis ikut dibersihkan. |
| `course_user.user_id` | `users.id` | **`cascadeOnDelete()`** | Jika akun mahasiswa dihapus dari sistem, riwayat pendaftaran mata kuliah mahasiswa tersebut ikut dihapus. |
| `materials.course_id` | `courses.id` | **`cascadeOnDelete()`** | Berkas dan tautan materi merupakan bagian integral dari mata kuliah, sehingga harus terhapus jika mata kuliah dihapus. |
| `assignments.course_id` | `courses.id` | **`cascadeOnDelete()`** | Tugas perkuliahan terikat langsung pada mata kuliah induknya. |
| `submissions.assignment_id` | `assignments.id` | **`cascadeOnDelete()`** | Berkas pengumpulan tugas tidak lagi relevan jika penugasan induknya dihapus. |
| `submissions.user_id` | `users.id` | **`restrict / cascade`** | Mengidentifikasi mahasiswa pemilik berkas submission. |
| `grades.submission_id` | `submissions.id` | **`cascadeOnDelete()`** | Nilai terikat mutlak pada berkas submission terkait; jika submission dihapus, nilai ikut terhapus. |
| `grades.graded_by` | `users.id` | **`restrictOnDelete()`** | Dosen penilai dicatat sebagai auditor nilai sehingga datanya tidak boleh terhapus sembarangan. |

#### 3. Kalau seorang dosen dihapus, apa yang terjadi pada mata kuliahnya?
Jawaban :  
Operasi penghapusan dosen **akan ditolak secara mutlak oleh basis data** (`QueryException: Integrity constraint violation`).  
**Alasan:** Kolom `courses.lecturer_id` diproteksi dengan `restrictOnDelete()`. Dalam sistem akademik, mata kuliah adalah aset institusi. Jika dosen pengampu dihapus menggunakan `CASCADE`, seluruh mata kuliah, materi, penugasan, dan submission mahasiswa yang ada di dalamnya akan ikut terhapus secara berantai (*catastrophic cascade*). Sebaliknya, dengan `RESTRICT`, institusi dipaksa mengalihkan dosen pengampu ke dosen lain terlebih dahulu sebelum akun dosen lama dapat dinonaktifkan/dihapus.

#### 4. Mengapa `grades.submission_id` bersifat `unique`, bukan sekadar `index` biasa?
Jawaban :  
Karena secara aturan bisnis akademik, **satu pengumpulan tugas (submission) hanya boleh memiliki tepat satu nilai akhir**.  
Jika hanya menggunakan `index` biasa tanpa constraint `unique`, database akan mengizinkan dosen menginput dua baris penilaian untuk submission yang sama (misal Grade 75 dan Grade 90). Penegakan `unique` langsung di tingkat basis data menjamin konsistensi data dan mencegah *race condition* atau duplikasi data akibat klik ganda saat proses penilaian.

---

### BREAK — Lima kerusakan

> Kolom **Prediksi** diisi sebelum mencoba. Kolom **Hasil sebenarnya** diisi setelah eksperimen langsung via Tinker dan migrasi.

| # | Yang dicoba | Prediksi sebelum mencoba | Hasil sebenarnya |
|---|-------------|--------------------------|-----------------|
| 1 | Hapus `$table->unique(['course_id', 'user_id'])` pada `course_user`, lalu daftarkan mahasiswa yang sama 2 kali ke MK yang sama | Mahasiswa terdaftar ganda di database | Pendaftaran kedua lolos tanpa pesan error. Muncul 2 baris data identik untuk mahasiswa yang sama di kelas tersebut, yang merusak perhitungan absensi dan rekap nilai peserta. |
| 2 | Tambahkan `role` ke `$fillable` model `User`, lalu kirim request pembuatan user dengan `role=admin` lewat Tinker/cURL | User baru berhasil dibuat dengan role admin | **Mass assignment berhasil dieksploitasi.** User baru yang dibuat langsung memiliki hak akses `admin`, membuktikan bahwa formulir di sisi frontend bukan merupakan dinding pembatas keamanan. |
| 3 | Ganti `$fillable` dengan `protected $guarded = [];` lalu ulangi percobaan nomor 2 | Semua atribut bisa diinjeksi bebas | Seluruh kolom sensitif (termasuk `role`, `id`, dll.) dapat diisi langsung oleh penyerang. Proteksi model mati total. |
| 4 | Kosongkan isi method `down()` pada satu file migrasi, lalu jalankan `php artisan migrate:refresh` | Perintah rollback gagal | Saat rollback dijalankan, tabel tidak terhapus sehingga saat migrasi `up()` berjalan kembali terjadi error `Table '...' already exists`. Migrasi menjadi tidak *reversible*. |
| 5 | Ganti `restrictOnDelete()` pada `lecturer_id` menjadi `cascadeOnDelete()`, lalu hapus satu dosen via Tinker | Mata kuliah dosen tersebut ikut terhapus | Menghapus satu dosen menyebabkan 2 mata kuliah, 6 materi, dan 15 submission mahasiswa terhapus seketika dari database tanpa peringatan. |

---

### FIX — Analisis perbaikan tujuh masalah branch `w03`

Pada branch cacat `w03` (repo `kampuslms-broken`), ditemukan 7 kesalahan mendasar pada migrasi, model, dan controller:
1. **Urutan migrasi salah:** Migrasi tabel anak (`courses`) berjalan sebelum tabel induk (`users`) dibuat, menyebabkan error foreign key tidak ditemukan.  
   *Solusi:* Memperbaiki timestamp berkas migrasi agar urutan pembuatan tabel berjalan runut.
2. **Constraint unique composite hilang pada `course_user`:** Mengizinkan duplikasi pendaftaran mahasiswa.  
   *Solusi:* Menambahkan `$table->unique(['course_id', 'user_id']);`.
3. **Constraint unique composite hilang pada `submissions`:** Mahasiswa bisa mengumpulkan tugas berulang kali.  
   *Solusi:* Menambahkan `$table->unique(['assignment_id', 'user_id']);`.
4. **Perilaku `onDelete` keliru pada `lecturer_id`:** Memakai cascade yang membahayakan data mata kuliah.  
   *Solusi:* Mengubahnya menjadi `restrictOnDelete()`.
5. **Model memakai `protected $guarded = [];`:** Membuka celah mass assignment pada seluruh kolom.  
   *Solusi:* Menghapus `$guarded` dan mendefinisikan `$fillable` secara eksplisit hanya untuk field yang aman.
6. **Method `down()` kosong pada migrasi:** Migrasi tidak dapat di-rollback dengan bersih.  
   *Solusi:* Menambahkan `Schema::dropIfExists('nama_tabel');` pada setiap method `down()`.
7. **Controller memakai `$request->all()`:** Mengambil data liar tak tervalidasi dari request.  
   *Solusi:* Mengganti `$request->all()` dengan `$request->only([...])` dan validasi eksplisit.

---

### BUILD — Rangkuman implementasi Milestone M1 (Fondasi Data)

Pada Milestone M1, fondasi basis data dan modul dasar KampusLMS telah selesai dibangun:
1. **Migrasi 8 Tabel Inti:**  
   Seluruh tabel inti (`users`, `courses`, `course_user`, `materials`, `assignments`, `submissions`, `grades`, `notifications`) terdefinisi dengan tipe data yang presisi, indeks komposit, dan relasi foreign key yang aman.
2. **Database Seeder Realistis & Idempoten:**  
   Seeder berhasil memuat 1 Admin, 3 Dosen, 30 Mahasiswa, 5 Course aktif, relasi peserta, materi contoh, dan penugasan tanpa terjadi duplikasi saat dieksekusi ulang (`php artisan migrate:fresh --seed`).
3. **Model Eloquent & Casting Modern (Laravel 12):**  
   Seluruh Model Eloquent mengimplementasikan relasi dua arah (`hasMany`, `belongsTo`, `belongsToMany`) dan menggunakan method casting modern `casts(): array` (misal untuk `password => 'hashed'` dan `due_at => 'datetime'`).
4. **CRUD Penuh Course & User:**  
   Admin dapat mengelola data mata kuliah dan pengguna secara penuh dengan proteksi mass assignment dan validasi data server-side.
