# Catatan Praktikum Minggu 13 — Kolaborasi, Version Control, dan Peer Review

- **Nama:** Muhammad Ikhsyan
- **NIM:** 10241049
- **Kelompok:** Kelompok 06 (Kelas A)
- **Topik:** Kolaborasi Git, Audit Riwayat Commit, Eksperimen Keamanan Alur Kerja, dan Peer Review
- **Branch:** `ikhsyan`

---

## 1. READ — Bedah Riwayat Git & Evaluasi Personal

### 1.1 Data Kontribusi Commit Berdasarkan Git Log

Pemeriksaan riwayat commit dijalankan pada branch `dev` dan seluruh cabang menggunakan perintah Git resmi:

```bash
git shortlog -sn dev
git shortlog -sn --all
```

**Tabel 1: Rekap Jumlah Commit Anggota Kelompok 06**

| Nama Anggota / Akun Git | NIM | Commit pada `dev` | Commit Seluruh Cabang | Fokus Kontribusi Utama |
|---|---|---|---|---|
| **Muhammad Ikhsyan** (`Ikhsyann` / `Muhammad Ikhsyan`) | `10241049` | **60** | **109** | Fitur 17 (Kurasi Sumber Belajar & Penanda Baca), Auth & Route Peran, Strict Lazy Loading & Optimasi Rekap Nilai, Docker & Deployment Produksi |
| **Muhammad Syahid Ridho** (`syahidrdho`) | `10241051` | 44 | 72 | Model & Migrasi Inti, Otorisasi IDOR Assignment/Submission, Modul Penilaian Berbasis Web, Dashboard Caching & Invalidasi |
| **Muhammad Farraz Kanz** (`Farras` / `Kanzuuu` / `Arrass1`) | `10241047` | 49 | 78 | Validasi Form Request, Pengelolaan Berkas Privat & Anti-IDOR Unduhan, Index Database Performa, Security Audit Produksi & Redesain UI |
| **Muhammad Yusuf Pratama** (`SufSoup`) | `10241053` | 31 | 52 | Course Enrollment, Seeder Akun Demo, Keandalan Queue Worker Notifikasi, Standardisasi Pagination & Benchmark |

### 1.2 Evaluasi Kepatuhan Conventional Commits

Dari total 60 commit atas nama Muhammad Ikhsyan pada branch `dev`:
- **Commit berformat Conventional Commits:** 29 commit (48.3%).
- **Commit format bebas:** 31 commit (51.7%).

**Analisis Pola:**
Pada minggu-minggu awal sprint (Minggu 1 hingga Minggu 4 di bulan September), pesan commit masih menggunakan format deskriptif personal informal seperti `ikhsyan : commit build (tambah kerangka halaman mata kuliah)` atau `Ikhsyan: remove macOS metadata files`. 

Namun, sejak integrasi fitur tingkat lanjut (Minggu 5 hingga Minggu 12), seluruh pesan commit telah beralih 100% disiplin mematuhi standar *Conventional Commits* dengan tipe yang tepat (`feat:`, `fix:`, `docs:`, `perf:`, `test:`, `chore:`). Perubahan ini membuat riwayat Git tim jauh lebih mudah ditelusuri (*traceable*) saat pelacakan bug dan penggabungan PR.

### 1.3 Audit Pekan Tanpa Commit

Analisis distribusi mingguan commit (`git log --date=format:%Y-W%V`) menunjukkan data sebagai berikut:

| Kalender Pekan (ISO) | Rentang Waktu | Jumlah Commit Ikhsyan | Keterangan |
|---|---|---|---|
| 2026-W36 s.d. W41 | Awal Sep – Pertengahan Okt 2026 | 39 commit | Pengerjaan Minggu 1 hingga Minggu 6 (Fitur 17, Form, IDOR) |
| **2026-W42** | **19 – 25 Oktober 2026** | **0 commit** | **Pekan Ujian Tengah Semester (UTS) — Codebase dibekukan untuk asesmen** |
| 2026-W43 s.d. W47 | Akhir Okt – Akhir Nov 2026 | 21 commit | Pengerjaan Minggu 9 hingga Minggu 12 (File Upload, Notifikasi, Performa, Deploy) |

Pekan W42 adalah satu-satunya pekan kosong karena adanya instruksi pembekuan repository saat pelaksanaan demonstrasi dan interview UTS.

### 1.4 Refaktor Pesan Commit yang Buruk

- **Commit Lama:** `755bd55`
- **Pesan Asli:** `ikhsyan : commit build (tambah kerangka halaman mata kuliah)`
- **Kelemahan Pesan Asli:** Menyertakan nama penulis di pesan (redundant dengan metadata author), menggunakan kata tidak baku "commit build", dan tidak mencerminkan aksi teknis yang jelas.
- **Pesan Seharusnya (Conventional Commits):**
  ```text
  feat(courses): add initial course index and detail view scaffolding
  ```

### 1.5 Analisis Ukuran Commit Terbesar

- **Commit Terbesar:** `8d566b6` (*feat(w10): implementasi engine notifikasi asinkron, event, listener, dan sanitasi payload*)
- **Dampak Perubahan:** 12 berkas diubah, 594 penambahan baris (`12 files changed, 594 insertions(+)`).
- **Evaluasi Pemecahan:** Commit ini menggabungkan pembuatan event listener antrean, perombakan layout notifikasi, sanitasi HTML, dan penambahan feature test sekaligus. Seharusnya dipecah menjadi 2 commit terpisah:
  1. `feat(notifications): implement queued event listener and database notification delivery` (hanya layer backend event/listener).
  2. `feat(notifications): add notification bell UI, sanitation, and feature tests` (layer UI dan pengujian).

---

## 2. BREAK — Analisis Eksperimen Keamanan Alur Git

Sesuai instruksi `modul.md §13.3 (BREAK)`, berikut hasil analisis atas enam skenario percobaan alur kerja kolaboratif dan keamanan repository:

### Percobaan 1: Commit `.env` Palsu dan Penolakan CI
- **Skenario:** Mengisi `.env` dengan kredensial palsu latihan (`APP_KEY=base64:PALSU`, `DB_PASSWORD=palsu`), menambahkannya ke staging, dan mencoba push ke branch yang diawasi CI.
- **Pengamatan:** Workflow GitHub Actions ([.github/workflows/ci.yml](file:///Users/muhammadikhsyan/Documents/Kuliah/Akademik/Semester%205/Proweb/kampuslms-a-kelompok-06/.github/workflows/ci.yml)) menjalankan step pemeriksaan keberadaan `.env` di riwayat tracking. CI langsung **gagal (merah)** dan membatalkan pipeline. Ini membuktikan CI berfungsi sebagai gerbang pencegahan kebocoran secret.

### Percobaan 2: Menghapus `.env` dengan `git rm --cached`
- **Skenario:** Menjalankan `git rm --cached .env` lalu melakukan commit perbaikan.
- **Pengamatan:** Berkas `.env` memang hilang dari status `HEAD` (tidak terlihat di commit terbaru), **tetapi seluruh isi berkas dan kredensialnya tetap tersimpan utuh di dalam objek commit sebelumnya (Git blob history)**. Siapa pun yang meng-clone repository atau melihat commit diff lama tetap dapat membaca kredensial tersebut secara telanjang.
- **Pelajaran Utama:** Begitu secret ter-push ke repository publik, menghapusnya di commit terbaru **sama sekali tidak menyelesaikan masalah**. Kredensial tersebut harus dianggap sudah disusupi (*compromised*) dan wajib diganti/di-rotate langsung di server produksi.

### Percobaan 3: Konflik Merge Baris Serupa pada Rute
- **Skenario:** Dua cabang secara bersamaan memodifikasi baris rute courses di `routes/web.php`.
- **Pengamatan:** Git menghentikan proses merge otomatis dan menghasilkan *conflict marker* (`<<<<<<< HEAD`, `=======`, `>>>>>>>`). Resolusi yang benar adalah membaca maksud kedua fitur (misalnya satu cabang menambahkan filter status, cabang lain menambahkan pagination/role guard), lalu menggabungkan kedua logika secara terintegrasi, bukan memilih salah satu pihak secara membabi-buta.

### Percobaan 4: Push Langsung ke Branch `main`
- **Skenario:** Mencoba melakukan `git push origin main` secara langsung dari lokal.
- **Pengamatan:** Remote menolak push dengan error `protected branch hook declined`. Branch protection rule mewajibkan setiap perubahan menuju `main` melalui alur Pull Request dengan review minimal 1 approval dan CI wajib berstatus hijau.

### Percobaan 5: Penggabungan PR yang Gagal CI
- **Skenario:** Membuka PR dengan status workflow CI gagal (*Checks failing*).
- **Pengamatan:** Tombol *Merge pull request* pada antarmuka GitHub terkunci (*disabled*) dengan keterangan bahwa syarat checks belum terpenuhi. Proteksi ini mengamankan branch utama dari regresi kode atau kebocoran kredensial.

### Percobaan 6: Pemalsuan Kepenulisan Commit
- **Skenario:** Menjalankan perintah `git commit --author="Dosen Penguji <dosen@kampus.ac.id>"`.
- **Pengamatan:** Git secara lokal mengizinkan nama dan email diubah tanpa verifikasi kepemilikan akun. Commit log akan mencatat nama dosen tersebut sebagai author.
- **Kesimpulan:** Hal ini menjelaskan mengapa penilaian kontribusi proyek tidak boleh semata-mata dihitung dari statistik `git shortlog` atau jumlah commit mentah, melainkan wajib diverifikasi melalui pemahaman kode saat sesi interview (*code walkthrough*).

---

## 3. FIX — Penanganan Repo Cacat & Prosedur Mitigasi Rahasia

Pada evaluasi studi kasus alur kerja cacat (*broken workflow*):

### 3.1 Enam Masalah Alur Kerja yang Diidentifikasi
1. **Penyimpanan `.env` di Riwayat Git:** Kredensial sensitif ter-commit dan terekspos ke publik.
2. **Ketiadaan Aturan Branch Protection:** Anggota tim bebas melakukan *force-push* atau push langsung ke `main` tanpa proses review.
3. **Ukuran Pull Request Raksasa (*Mega PR*):** Satu PR menggabungkan 5 fitur besar sekaligus dengan ribuan baris diff, membuat reviewer mustahil menemukan bug atau celah IDOR.
4. **Pesan Commit Tidak Berstruktur:** Pesan commit tidak deskriptif ("update", "fix bug", "coba"), mengaburkan riwayat perubahan.
5. **Debug Statements Tertinggal:** Pemanggilan `dd()`, `dump()`, atau `var_dump()` tertinggal di controller, berisiko menghentikan aplikasi dan membocorkan data jika lolos ke produksi.
6. **Folder Vendor / `node_modules` Ter-commit:** Menggelembungkan ukuran repository dan memicu konflik dependensi antar mesin pengembang.

### 3.2 Prosedur Mitigasi Kredensial Bocor (Incident Response)
Jika secret/kredensial terlanjur ter-push ke repository publik:
1. **Segera Rotate Kredensial di Server:** Ganti kata sandi basis data MySQL di server produksi, jalankan `php artisan key:generate` untuk meregenerasi `APP_KEY`, dan terbitkan ulang seluruh token API eksternal. Jangan menunggu riwayat dibersihkan.
2. **Pembersihan Riwayat Git:** Gunakan alat seperti `git-filter-repo` atau BFG Repo-Cleaner untuk menghapus file sensitif dari seluruh rantai commit riwayat Git:
   ```bash
   git filter-repo --invert-paths --path .env
   git push origin --force --all
   ```
3. **Pemberitahuan Tim & Invalidasi Session:** Informasikan ke seluruh anggota kelompok untuk melakukan *re-clone* repository bersih agar blob lama tidak terdorong kembali, serta bersihkan session dan cache aplikasi (`php artisan cache:clear && php artisan config:clear`).

---

## 4. BUILD — Peer Review & Tindak Lanjut Kolaborasi

### 4.1 Rekap Peer Review Resmi ke Kelompok A07

Sebagai pemenuhan **Komponen Nilai Review Individu (Bobot 10%)**, saya (**Muhammad Ikhsyan / `Ikhsyann`**) telah menguji dan memberikan tinjauan mendalam pada 2 Pull Request di repository **Kelompok A07** ([`LvynePaz/kampuslms-kelompok-A07`](https://github.com/LvynePaz/kampuslms-kelompok-A07)).

#### A. Review pada PR #61 (Nabil Barok — Konfigurasi MySQL & Supervisor Queue Worker)
- **Tautan Review:** [LvynePaz/kampuslms-kelompok-A07#61 (Comment 6986152408)](https://github.com/LvynePaz/kampuslms-kelompok-A07/pull/61#issuecomment-6986152408)
- **Temuan yang Diberikan:**
  1. **[Keamanan] Password Database Produksi Tertulis Plain Text di Berkas SQL**
     - *Lokasi:* `deploy/mysql/setup_production_db.sql:18-19`
     - *Risiko:* Password `'K4mpusLMS#Pr0d_9x8v2qW1z@2026!'` ter-push ke repo publik. Jika port MySQL terbuka atau server disusupi, database langsung dapat diakses. Menghapus di commit berikutnya tidak cukup karena tersimpan di riwayat Git.
     - *Saran:* Gunakan placeholder environment variable `${DB_PASSWORD}` dan rotasi password di server.
  2. **[Keamanan] Password Terekspos Lewat Argumen Proses dan Output Terminal**
     - *Lokasi:* `scripts/setup-mysql-production.sh:34` dan `:41-46`
     - *Risiko:* Argumen `mysql -p"${DB_PASSWORD}"` dapat dibaca oleh pengguna non-root lain di server via perintah `ps aux` atau `/proc/<pid>/cmdline`.
     - *Saran:* Gunakan file opsi temporer `my.cnf` dengan permission `600` untuk passing password MySQL secara aman.
  3. **[Struktur / Keandalan] Direktori Kerja Supervisor Tidak Memuat Subdirektori `lms/`**
     - *Lokasi:* `deploy/supervisor/laravel-worker.conf:6`
     - *Risiko:* Konfigurasi `directory=/var/www/html` menyebabkan perintah `artisan queue:work` gagal dijalankan (*silent crash*) karena struktur Laravel kelompok berada di dalam subfolder `/var/www/html/lms/`.
     - *Saran:* Koreksi path direktori kerja menjadi `/var/www/html/lms`.

#### B. Review pada PR #57 (Nabil Barok — Cache Dashboard & Verifikasi M11)
- **Tautan Review:** [LvynePaz/kampuslms-kelompok-A07#57 (Comment 6986156187)](https://github.com/LvynePaz/kampuslms-kelompok-A07/pull/57#issuecomment-6986156187)
- **Temuan yang Diberikan:**
  1. **[Struktur] Model Memanggil Controller untuk Invalidasi Cache**
     - *Lokasi:* `app/Models/Course.php:5` dan `app/Models/User.php:5`
     - *Risiko:* Pelanggaran arsitektur (*tight coupling*). Layer model bergantung pada layer HTTP controller.
     - *Saran:* Pindahkan logika invalidasi ke Eloquent Observer atau Service Class terpisah.
  2. **[Performa] Method `invalidateUserStats()` Tidak Pernah Dipanggil**
     - *Lokasi:* `app/Http/Controllers/DashboardController.php:96`
     - *Risiko:* Data statistik counter mata kuliah mahasiswa pada dashboard berisiko usang (*stale*) hingga TTL 10 menit berakhir saat terjadi mutasi enrollment.
     - *Saran:* Panggil method invalidasi pada titik mutasi attach/detach enrollment mahasiswa.

---

### 4.2 Rekap Review yang Masuk dari Kelompok Lain & Tanggapan Resmi

Pada Pull Request sinkronisasi saya (**PR #56** di repository `SufSoup/kampuslms-a-kelompok-06`), reviewer eksternal **Linggar Pramudya (`Ling9ar`)** memberikan catatan review struktur kode.

#### Catatan Review Linggar (`Ling9ar`):
- **Kategori:** Code Structure / Maintainability
- **Lokasi:** `lms/resources/views/courses/index.blade.php` (baris 2–12)
- **Temuan:** Pemilihan route dinamis berdasarkan peran pengguna (`match (auth()->user()->role)`) dilakukan langsung di dalam template Blade. Seharusnya Blade hanya berfokus pada presentasi visual, sedangkan penentuan rute diserahkan kepada Controller.

#### Tanggapan Resmi Ikhsyan pada Komentar PR #56:
> *"Terima kasih banyak atas review dan sarannya, Linggar (@Ling9ar). Kami setuju dengan temuan kategori struktur kode tersebut. Logika penentuan rute dinamis `$courseIndexRoute` dan `$courseShowRoute` telah kami pindahkan ke `CourseController@index` dan dikirimkan ke view melalui data controller, sehingga file Blade `courses/index.blade.php` kini bersih dari blok logika `@php`. Perbaikan telah diverifikasi dengan seluruh unit dan feature test mata kuliah tetap berstatus PASS."*

---

### 4.3 Tindak Lanjut Perbaikan Kode Nyata

Sesuai saran Linggar pada review PR #56, perubahan diterapkan pada dua berkas:

1. **[CourseController.php](file:///Users/muhammadikhsyan/Documents/Kuliah/Akademik/Semester%205/Proweb/kampuslms-a-kelompok-06/lms/app/Http/Controllers/CourseController.php):**
   Memindahkan logika rute ke dalam method `index(Request $request)`:
   ```php
   $courseIndexRoute = match ($user->role) {
       'admin' => 'admin.courses.index',
       'dosen' => 'dosen.courses.index',
       'mahasiswa' => 'mahasiswa.courses.index',
   };

   $courseShowRoute = match ($user->role) {
       'admin' => 'admin.courses.show',
       'dosen' => 'dosen.courses.show',
       'mahasiswa' => 'mahasiswa.courses.show',
   };

   return view('courses.index', compact('courses', 'courseIndexRoute', 'courseShowRoute'));
   ```

2. **[courses/index.blade.php](file:///Users/muhammadikhsyan/Documents/Kuliah/Akademik/Semester%205/Proweb/kampuslms-a-kelompok-06/lms/resources/views/courses/index.blade.php):**
   Menghapus seluruh blok `@php ... @endphp` deklarasi rute pada baris awal template, memanfaatkan variabel yang diinjeksi controller.

**Hasil Verifikasi:**
Perintah `cd lms && php artisan test --filter=Course` dijalankan:
```text
Tests: 57 passed (263 assertions)
Duration: 5.36s
```
Semua test lulus tanpa ada regresi tampilan atau otorisasi.

---

### 4.4 Perapian Standar Kolaborasi Repositori

1. **Pemasangan Template Pull Request:**
   Berkas [.github/pull_request_template.md](file:///Users/muhammadikhsyan/Documents/Kuliah/Akademik/Semester%205/Proweb/kampuslms-a-kelompok-06/.github/pull_request_template.md) telah dibuat sesuai spesifikasi Modul 13 untuk mewajibkan setiap PR memuat deskripsi perubahan, motivasi, cara uji, daftar periksa keamanan/test/N+1, dan transparansi bagian yang dibantu AI.

2. **Pembaruan Tabel Peran Kontribusi di README:**
   Tabel pada [README.md](file:///Users/muhammadikhsyan/Documents/Kuliah/Akademik/Semester%205/Proweb/kampuslms-a-kelompok-06/README.md) diperbarui agar transparan dan akurat mencerminkan fokus arsitektur dan riwayat `git shortlog` dari masing-masing anggota.

---

## 5. Checkpoint Minggu 13 — Panduan Interview Asesmen

Berikut rangkuman jawaban untuk enam pertanyaan gerbang interview evaluasi Minggu 13:

### 1. Berapa commit atas nama Anda? Tunjukkan `git shortlog -sn`.
- **Jawaban:** Pada branch integrasi `dev`, terdapat **60 commit** atas nama saya (`Ikhsyann` 42 commit dan `Muhammad Ikhsyan` 18 commit). Pada seluruh branch tercatat 109 commit. Kontribusi tersebar konsisten dari Minggu 1 hingga Minggu 12 pada area modul inti Fitur 17, otorisasi peran, strict lazy loading, optimasi query nilai, dan deployment produksi Docker.

### 2. Kenapa `git rm --cached .env` tidak cukup kalau `.env` sudah pernah ter-push?
- **Jawaban:** Karena `git rm --cached` hanya menghapus file dari area staging dan commit *tree* terbaru (HEAD). Riwayat komit sebelumnya di repository Git tetap menyimpan berkas `.env` tersebut sebagai objek Git blob yang immutable. Siapa pun dapat melakukan checkout ke commit lama atau melihat riwayat diff untuk membaca password database dan `APP_KEY`. Oleh karena itu, rahasia harus dianggap bocor dan wajib di-rotate langsung di server.

### 3. Tunjukkan satu temuan review yang Anda tulis. Jelaskan risikonya tanpa membaca catatan!
- **Jawaban:** Pada PR #61 Kelompok A07 (`deploy/mysql/setup_production_db.sql`), saya menemukan password root/user database produksi ditulis secara plain text di dalam script SQL repository publik. Risikonya, siapa pun yang membaca repository dapat menggunakan kredensial tersebut untuk mengakses atau memanipulasi database produksi jika port database terekspos ke internet. Solusinya adalah menggunakan placeholder environment variable dan segera melakukan rotasi password di server produksi.

### 4. Tunjukkan satu temuan yang masuk ke kelompok Anda. Anda terima atau tolak? Kenapa?
- **Jawaban:** Temuan dari Linggar (`Ling9ar`) pada PR #56 menyoroti penempatan logika rute dinamis (`match(auth()->user()->role)`) di dalam file Blade `courses/index.blade.php`. Saya **menerima** temuan tersebut karena file Blade seharusnya murni menangani layer presentasi (tampilan). Menaruh logika penentuan rute di view menyulitkan pemeliharaan kode dan melanggar prinsip *separation of concerns*. Saya menindaklanjutinya dengan memindahkan logika tersebut ke `CourseController@index`.

### 5. Kenapa PR sebaiknya kecil dan hanya mencakup satu fitur?
- **Jawaban:** PR kecil (*small atomic PR*) membuat peninjauan kode (*code review*) menjadi fokus dan efektif. Celah keamanan (seperti IDOR), query N+1, atau bug logika mudah dideteksi oleh reviewer. Sebaliknya, PR raksasa yang menggabungkan banyak fitur akan melelahkan reviewer (*review fatigue*), sehingga reviewer cenderung melakukan approval asal-asalan tanpa memeriksa secara mendalam. Selain itu, PR kecil memperkecil risiko konflik merge yang rumit.

### 6. Kenapa penilaian kontribusi tidak bisa hanya berdasarkan jumlah commit?
- **Jawaban:** Jumlah commit sangat mudah dimanipulasi (*gelembungkan*), misalnya dengan melakukan puluhan commit kecil untuk sekadar memperbaiki typo, spasi, atau formatting. Selain itu, metadata author Git dapat dipalsukan secara bebas menggunakan opsi `git commit --author`. Penilaian sejati harus didasarkan pada substansi arsitektur kode yang dibangun, kemampuan menjelaskan kode saat *code walkthrough*, kualitas pengujian (test suite), dan kepatuhan terhadap proteksi keamanan aplikasi.
