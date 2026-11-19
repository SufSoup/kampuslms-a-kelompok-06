# Panduan Deployment dan Operasional Produksi KampusLMS

**Mata Kuliah:** SI2514024 – Pemrograman Web Semester 5  
**Kelompok:** 06 Kelas A

**Anggota Kelompok:**
- Muhammad Ikhsyan (10241049)
- Muhammad Farraz Kanz (10241047)
- Muhammad Syahid Ridho (10241051)
- Muhammad Yusuf Pratama (10241053)

---

## 1. Deskripsi Aplikasi

KampusLMS merupakan aplikasi Learning Management System (LMS) berbasis Laravel yang digunakan untuk mendukung aktivitas pembelajaran antara admin, dosen, dan mahasiswa.

Aplikasi menyediakan fitur pengelolaan mata kuliah, materi pembelajaran, tugas, pengumpulan tugas, penilaian, serta notifikasi.

Aplikasi produksi dapat diakses melalui:

**URL:** https://kampuslms-a-kelompok-06-production.up.railway.app

Deployment menggunakan Railway dengan Dockerfile sebagai konfigurasi build dan runtime aplikasi.

## 2. Teknologi yang Digunakan

| Teknologi | Versi/Keterangan |
|---|---|
| Laravel | Laravel 12 |
| PHP | 8.4 |
| Apache | Apache HTTP Server |
| Node.js | 20 Alpine |
| Composer | Composer 2 |
| Vite | Build frontend |
| Database | MySQL 8 (konfigurasi CI menggunakan MySQL 8.0) |
| Queue | Laravel Database Queue |
| Container | Docker |
| Hosting | Railway |
| CI | GitHub Actions |

## 3. Persiapan Project

Struktur repository:

```text
kampuslms-a-kelompok-06/
├── .github/
│   └── workflows/
│       └── ci.yml
├── docs/
├── lms/
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── tests/
│   ├── Dockerfile
│   └── deploy.sh
├── scripts/
└── README.md
```

Persiapan lingkungan pengembangan:

1. Clone repository.
2. Masuk ke direktori `lms`.
3. Install dependency Composer.
4. Install dependency Node.js.
5. Salin `.env.example` menjadi `.env`.
6. Generate application key.
7. Konfigurasikan database lokal.
8. Jalankan migrasi dan pengujian.

Perintah instalasi:

```bash
cd lms
composer install
npm ci
npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan test
```

## 4. Konfigurasi Dockerfile

Deployment menggunakan Docker multi-stage build.

### Stage 1 – Frontend

Menggunakan image `node:20-alpine` untuk menginstal dependency frontend dan mengompilasi aset Vite.

```dockerfile
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build
```

### Stage 2 – PHP Application

Menggunakan image `php:8.4-apache`.

Ekstensi PHP yang dipasang meliputi:

- pdo_mysql
- mbstring
- exif
- pcntl
- bcmath
- gd
- zip

Konfigurasi batas PHP dalam Dockerfile:

| Parameter | Nilai |
|---|---:|
| upload_max_filesize | 20M |
| post_max_size | 25M |
| memory_limit | 256M |

Apache menggunakan `mpm_prefork` dan `mod_rewrite`. Document root diarahkan ke direktori `public` Laravel.

Dependency produksi dipasang menggunakan:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

Aset frontend hasil Stage 1 disalin ke `public/build`.

## 5. Konfigurasi Environment Railway

Aplikasi menggunakan environment variable untuk mengatur konfigurasi produksi.

Parameter utama yang perlu disediakan pada Railway:

| Variable | Fungsi |
|---|---|
| APP_NAME | Nama aplikasi |
| APP_ENV | Lingkungan aplikasi |
| APP_KEY | Kunci enkripsi Laravel |
| APP_DEBUG | Mengatur tampilan debug |
| APP_URL | URL produksi |
| DB_CONNECTION | Driver database |
| DB_HOST | Host database |
| DB_PORT | Port database |
| DB_DATABASE | Nama database |
| DB_USERNAME | Username database |
| DB_PASSWORD | Password database |
| QUEUE_CONNECTION | Driver antrean |
| CACHE_STORE | Driver cache |
| SESSION_DRIVER | Driver session |
| SESSION_SECURE_COOKIE | Pengaturan cookie HTTPS |

Konfigurasi produksi yang ditargetkan:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://kampuslms-a-kelompok-06-production.up.railway.app
QUEUE_CONNECTION=database
SESSION_SECURE_COOKIE=true
```

Nilai rahasia seperti `APP_KEY`, `DB_PASSWORD`, dan password akun demo disimpan melalui environment variables Railway dan tidak dicantumkan dalam repository.

## 6. Konfigurasi Database

Aplikasi menggunakan koneksi MySQL pada lingkungan produksi.

Dockerfile memasang ekstensi `pdo_mysql` agar Laravel dapat berkomunikasi dengan database MySQL.

Migrasi database dijalankan melalui perintah:

```bash
php artisan migrate --force
```

Perintah tersebut dijalankan otomatis saat container mulai berjalan.

Proses startup tidak menjalankan `db:seed` secara otomatis. Seeding dilakukan secara manual oleh pengelola ketika diperlukan dan setelah database siap digunakan.

Pengujian CI menggunakan MySQL 8.0 dengan database `kampuslms_test`.

## 7. Konfigurasi Penyimpanan File

Konfigurasi filesystem Laravel terdapat pada:

```text
lms/config/filesystems.php
```

Disk `local` menggunakan lokasi:

```text
storage/app/private
```

Disk tersebut digunakan untuk penyimpanan file privat, termasuk file pengumpulan tugas mahasiswa.

Disk `public` menggunakan:

```text
storage/app/public
```

Symbolic link `public/storage` diarahkan ke penyimpanan public, bukan penyimpanan privat.

File submission tidak boleh diakses langsung sebagai file publik. Pengunduhan dilakukan melalui mekanisme aplikasi yang memeriksa autentikasi dan otorisasi pengguna.

**Catatan:** penggunaan Railway Volume dan lokasi mount produksi perlu dikonfirmasi oleh pengelola Railway sebelum dokumentasi menyatakan persistensi file telah terjamin.

## 8. Konfigurasi Queue

Konfigurasi queue terdapat pada:

```text
lms/config/queue.php
```

Driver default:

```php
'default' => env('QUEUE_CONNECTION', 'database'),
```

Database queue menggunakan tabel:

- `jobs` untuk antrean pekerjaan.
- `failed_jobs` untuk pekerjaan yang gagal.

Aplikasi juga menyediakan driver `sync`, tetapi konfigurasi produksi yang ditargetkan menggunakan `database`.

Worker harus dijalankan agar pekerjaan asynchronous seperti notifikasi dapat diproses.

**Status worker Railway:** belum diverifikasi secara langsung. Pengelola deployment perlu memastikan service worker aktif dan menjalankan perintah:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

## 9. GitHub Actions – Continuous Integration

Konfigurasi CI berada di:

```text
.github/workflows/ci.yml
```

Workflow berjalan ketika terdapat push ke branch:

- `main`
- `dev`
- `ikhsyan`

Workflow juga berjalan ketika terdapat pull request menuju `main` atau `dev`.

Lingkungan CI menggunakan:

- Ubuntu Latest
- PHP 8.4
- Node.js 20
- MySQL 8.0

Tahapan CI:

1. Checkout repository.
2. Memastikan file `lms/.env` tidak ter-commit.
3. Setup PHP dan ekstensi yang dibutuhkan.
4. Install dependency Composer.
5. Setup Node.js.
6. Install dependency npm dan build frontend.
7. Menyiapkan environment testing.
8. Menjalankan `migrate:fresh --seed`.
9. Menjalankan `migrate:refresh`.
10. Menjalankan `php artisan test`.

Workflow ini merupakan pengujian otomatis dan bukan proses deployment otomatis ke Railway.

## 10. Proses Deployment di Railway

Deployment aplikasi dilakukan menggunakan Dockerfile pada direktori aplikasi `lms`.

URL produksi:

```text
https://kampuslms-a-kelompok-06-production.up.railway.app
```

Dockerfile mengatur proses build dan startup container.

Ketika container dimulai, proses yang dijalankan meliputi:

1. Menyesuaikan port Apache berdasarkan variabel `$PORT`.
2. Membersihkan cache konfigurasi, route, dan view sebelumnya.
3. Menjalankan migrasi database.
4. Membuat cache konfigurasi.
5. Membuat cache route.
6. Membuat cache view.
7. Membuat cache event.
8. Menjalankan Apache.

Perintah migrasi menggunakan `--force` karena aplikasi berjalan pada lingkungan produksi.

Seeding tidak dilakukan otomatis saat startup untuk menghindari pengulangan data.

## 11. Prosedur Deployment Ulang

Repository menyediakan skrip:

```text
lms/deploy.sh
```

Skrip ini digunakan untuk prosedur redeployment manual.

Tahapan yang dilakukan:

1. Mengaktifkan maintenance mode.
2. Memperbarui dependency Composer.
3. Mengompilasi aset frontend jika npm tersedia.
4. Menjalankan migrasi database.
5. Memperbarui cache konfigurasi.
6. Memperbarui cache route, view, dan event.
7. Meminta queue worker melakukan restart.
8. Memeriksa endpoint health check `/up`.
9. Menonaktifkan maintenance mode.

Skrip menggunakan `set -euo pipefail` agar proses berhenti ketika terjadi kesalahan.

Contoh penggunaan:

```bash
APP_URL=https://kampuslms-a-kelompok-06-production.up.railway.app bash deploy.sh
```

Skrip tersebut tidak boleh dianggap otomatis dijalankan oleh Railway. Dockerfile memiliki mekanisme startup tersendiri.

## 12. Verifikasi Deployment

Verifikasi dilakukan dengan mengakses URL produksi melalui HTTPS.

Hasil pemeriksaan yang telah dilakukan:

| Pemeriksaan | Hasil |
|---|---|
| Aplikasi dapat diakses melalui HTTPS | Berhasil |
| HTTP diarahkan ke HTTPS | HTTP 301 |
| Akses `/.env` | HTTP 404 |
| Akses `/storage/logs/laravel.log` | HTTP 404 |
| Cookie session memiliki atribut Secure | Teramati |
| Cookie session memiliki atribut HttpOnly | Teramati |
| Cookie session memiliki SameSite=Lax | Teramati |

Contoh perintah pemeriksaan:

```bash
curl -I https://kampuslms-a-kelompok-06-production.up.railway.app/.env
```

```bash
curl -I https://kampuslms-a-kelompok-06-production.up.railway.app/storage/logs/laravel.log
```

```bash
curl -I http://kampuslms-a-kelompok-06-production.up.railway.app
```

Hasil HTTP 404 menunjukkan file sensitif tidak tersedia melalui URL yang diuji.

## 13. Pengujian API Production

Pengujian API dilakukan menggunakan:

```text
scripts/test-api.sh
```

Script diarahkan ke URL Railway produksi.

Pengujian mencakup:

- Request tanpa token ditolak.
- Mahasiswa tidak dapat membuat tugas dosen.
- Dosen tidak dapat menilai submission milik dosen lain.
- Dosen dengan token valid dapat mengakses profil sendiri.

Hasil pengujian produksi:

| Skenario | HTTP | Hasil |
|---|---:|---|
| Request tanpa token | 401 | PASS |
| Mahasiswa membuat tugas dosen | 403 | PASS |
| Dosen menilai submission dosen lain | 403 | PASS |
| Dosen membuka profil sendiri | 200 | PASS |

**Hasil: 4 dari 4 skenario berhasil.**

Pengujian IDOR grading menggunakan submission yang sudah ada pada lingkungan produksi. Pengujian tersebut berhasil ditolak dengan HTTP 403. Untuk pengujian berikutnya, gunakan data khusus pengujian yang tidak berisiko mengubah data pengguna.

## 14. Pengujian Authentication dan Authorization

Pengujian authentication dan authorization dilakukan menggunakan:

```text
scripts/test-authz.sh
```

Script menguji akses berdasarkan peran admin, dosen, dan mahasiswa.

Akun pengujian digunakan untuk memeriksa:

- Login dan akses dashboard.
- Akses manajemen mata kuliah.
- Akses manajemen pengguna.
- Akses daftar mata kuliah dosen.
- Akses materi pembelajaran.
- Pembatasan akses berdasarkan role.
- Proteksi IDOR antarobjek.
- Proteksi pengunduhan file submission.

Password akun demo tidak dicantumkan dalam dokumentasi. Kredensial pengujian disediakan secara terpisah oleh pengelola aplikasi.

## 15. Hasil Pengujian Authorization Production

Hasil pengujian `scripts/test-authz.sh` pada aplikasi Railway:

| Skenario | Hasil |
|---|---|
| Login dan dashboard admin | PASS |
| Login dan dashboard dosen | PASS |
| Login dan dashboard dosen kedua | PASS |
| Login dan dashboard mahasiswa | PASS |
| Login dan dashboard mahasiswa kedua | PASS |
| Guest mengakses notifikasi API | PASS – HTTP 401 |
| Admin mengakses manajemen mata kuliah | PASS |
| Admin mengakses manajemen pengguna | PASS |
| Dosen mengakses mata kuliah miliknya | PASS |
| Mahasiswa mengakses materi kelasnya | PASS |
| Dosen mengakses manajemen admin | Ditolak – HTTP 403 |
| Mahasiswa mengakses manajemen admin | Ditolak – HTTP 403 |
| Mahasiswa mengakses route dosen | Ditolak – HTTP 403 |
| Dosen mengakses mata kuliah dosen lain | Ditolak – HTTP 403 |
| Mahasiswa mengunduh submission privat tanpa login | Ditolak – HTTP 302 |
| Mahasiswa mengunduh submission mahasiswa lain | Ditolak – HTTP 403 |

**Catatan:** pengujian IDOR untuk notifikasi berstatus SKIP karena tidak tersedia data notifikasi yang sesuai untuk pengujian. Skenario tersebut tidak dinyatakan lulus.

## 16. Pengujian Akses File Submission Privat

Pengujian dilakukan dengan membuka URL pengunduhan file submission menggunakan browser Incognito tanpa login.

Hasil yang diperoleh:

- Pengguna tanpa autentikasi diarahkan ke halaman login.
- Pengguna tidak dapat mengunduh file submission secara langsung tanpa autentikasi.
- Script pengujian authorization juga mendapatkan HTTP 302 untuk akses guest.
- Pengujian akses submission milik mahasiswa lain menghasilkan HTTP 403.

Hasil ini menunjukkan mekanisme autentikasi dan otorisasi pengunduhan file berjalan pada skenario yang diuji.

## 17. Hasil Pengujian Otomatis Lokal

Pengujian otomatis Laravel dijalankan melalui:

```bash
cd lms
php artisan test
```

Hasil pengujian terakhir:

```text
202 passed
803 assertions
0 failed
```

Pengujian mencakup fitur aplikasi, autentikasi, authorization, API, pengumpulan tugas, penilaian, notifikasi, serta keamanan file.

Hasil tersebut merupakan hasil pengujian lokal, bukan hasil CI GitHub Actions atau pengujian produksi.

## 18. Temuan dan Catatan Operasional

Beberapa hal yang perlu diperhatikan selama operasional produksi:

1. File `.env` dan log aplikasi tidak boleh dapat diakses melalui web.
2. `APP_DEBUG` harus bernilai `false` pada lingkungan produksi.
3. File submission harus tetap berada pada penyimpanan privat.
4. Database dan password akun demo tidak boleh dibagikan melalui repository.
5. Database queue memerlukan worker aktif agar pekerjaan asynchronous dapat diproses.
6. Migrasi dijalankan otomatis saat container startup.
7. Seeding tidak dijalankan otomatis saat container startup.
8. Batas upload pada Dockerfile adalah 20 MB dan batas POST adalah 25 MB.
9. Worker Railway, environment variables, persistensi volume, serta nilai batas upload runtime masih perlu dikonfirmasi oleh pengelola deployment.
10. Pengujian yang berpotensi mengubah data produksi harus menggunakan data khusus pengujian.

### Pengujian lintas perangkat

Pengujian lintas perangkat dan skenario tampilan mobile perlu dilengkapi dengan bukti pengujian aktual oleh anggota kelompok yang bertanggung jawab.

**Status:** menunggu pelengkapan bukti pengujian.

### Eksperimen BREAK

Eksperimen BREAK dilakukan pada lingkungan lokal atau staging yang aman, bukan pada aplikasi produksi.

Hasil, waktu pelaksanaan, dan pemulihan setiap eksperimen harus dicatat berdasarkan pelaksanaan aktual. Eksperimen yang belum dilakukan tidak dinyatakan berhasil.

## 19. Status Deployment

| Komponen | Status |
|---|---|
| Aplikasi Railway | Berhasil diakses |
| HTTPS | Aktif |
| HTTP redirect HTTPS | HTTP 301 |
| Proteksi file `.env` | HTTP 404 |
| Proteksi log Laravel | HTTP 404 |
| API production test | 4/4 PASS |
| Authorization production test | Skenario yang dijalankan PASS, satu SKIP |
| Private submission access | Guest ditolak |
| Automated test lokal | 202 passed, 803 assertions |
| GitHub Actions CI | Workflow tersedia |
| Queue worker Railway | Belum dikonfirmasi |
| Railway Volume | Belum dikonfirmasi |
| Verifikasi seluruh environment variable | Belum dikonfirmasi |
| Pengujian lintas perangkat | Menunggu bukti |
| Eksperimen BREAK | Perlu dilengkapi berdasarkan hasil aktual |

Dengan konfigurasi Docker, pengujian otomatis, serta pemeriksaan keamanan yang telah dilakukan, KampusLMS telah berhasil dijalankan pada lingkungan produksi Railway. Beberapa aspek operasional masih memerlukan konfirmasi dari pengelola deployment sebelum seluruh checklist produksi dapat dinyatakan selesai.

---

**Catatan keamanan:** Jangan menyimpan password akun demo, token, cookie, APP_KEY, maupun kredensial database di dalam dokumentasi atau repository.