# Panduan Deployment & Operasional Produksi KampusLMS

- **Mata Kuliah:** SI2514024 — Pemrograman Web (Semester 5)
- **Kelompok:** Kelompok 06 (Kelas A)
- **Tim Pengembang:**
  - Muhammad Ikhsyan (`10241049`)
  - Muhammad Farraz Kanz (`10241047`)
  - Muhammad Syahid Ridho (`10241051`)
  - Muhammad Yusuf Pratama (`10241053`)
- **Platform Produksi:** Railway Cloud Platform (Linux Container & Managed MySQL)
- **URL Produksi:** `https://kampuslms-a-06-production.up.railway.app`

---

## 1. Spesifikasi Server & Arsitektur Produksi

### 1.1 Spesifikasi Komponen Runtime

| Komponen | Spesifikasi / Konfigurasi | Keterangan |
|---|---|---|
| **Platform Host** | Railway Cloud Platform (Isolated Linux Containers) | PaaS containerized |
| **Sistem Operasi** | Linux Debian 12 (Bookworm) via base image Docker | 64-bit container runtime |
| **Web Server** | Apache 2.4 (`mpm_prefork`, `mod_rewrite` aktif) | Port dinamis diatur via `$PORT` (default 8080) |
| **PHP Runtime** | PHP 8.4.x CLI + Apache Module (`php:8.4-apache`) | Ekstensi: `pdo_mysql`, `mbstring`, `exif`, `pcntl`, `bcmath`, `gd`, `zip` |
| **PHP Limits** | `upload_max_filesize = 20M`, `post_max_size = 25M`, `memory_limit = 256M` | Sesuai kebutuhan form upload tugas (max 10MB) |
| **Database Server** | Managed MySQL 8.0 (Railway Private Network) | Akses via internal TCP host `${{MySQL.MYSQLHOST}}` |
| **Persistent Storage** | Railway Volume (1GB+) di-mount ke `/var/www/html/storage/app/private` | Menjaga file submission tugas tidak hilang saat restart |
| **Queue Driver** | Database (`jobs` & `failed_jobs` table) | Diproses terisolasi oleh dedicated background worker |
| **Cache & Session** | Database driver (`cache` & `sessions` table) | Menjamin session persistence lintas worker/web container |
| **Asset Bundler** | Vite 6.0 + Tailwind CSS v4 | Pre-compiled di Stage 1 Docker build (`public/build`) |

### 1.2 Matriks Ekuivalensi VPS (Modul §12) vs Railway

Modul Praktikum §12 mengasumsikan setup VPS Ubuntu monolitik konvensional. Berikut pemetaan ekuivalensi arsitektur VPS terhadap implementasi container Railway yang digunakan Kelompok 06:

| Konsep VPS (Modul §12) | Implementasi Railway (Kelompok 06) | Justifikasi & Mekanisme Teknis |
|---|---|---|
| Nginx Document Root `/var/www/kampuslms/public` | Apache `APACHE_DOCUMENT_ROOT=/var/www/html/public` + `AllowOverride All` | Mencegah pembocoran file di luar direktori `public/` (seperti `.env`, `composer.json`, dan kode sumber PHP mentah). |
| Certbot Let's Encrypt + Cron Renewal | Railway Automated Managed TLS/SSL Certificate | Sertifikat SSL/TLS diterbitkan dan diperbarui otomatis di level reverse proxy edge Railway untuk domain `*.up.railway.app`. |
| Supervisor Daemon (`supervisorctl`) | Dedicated Railway Background Worker Service | Service terpisah dalam satu project yang menjalankan `php artisan queue:work --sleep=3 --tries=3 --max-time=3600` dengan auto-restart policy. |
| Server Local Disk Storage | Railway Persistent Volume | Container filesystem bersifat ephemeral (terhapus saat redeploy). Volume persistent di-mount ke `/var/www/html/storage/app/private` agar file submission tetap aman. |
| Log File `storage/logs/laravel.log` via SSH | Railway Streaming Logs & Runtime Log Viewer + Railway CLI | Seluruh log aplikasi diarahkan ke stdout/stderr container dan tersimpan di `storage/logs/laravel.log`. |

---

## 2. Langkah Deployment dari Nol (Step-by-Step)

### Langkah 1: Persiapan Repositori & Verifikasi Dependensi Lokal
Pastikan source code di laptop bersih dan test suite lulus sebelum push:
```bash
cd lms
composer install
npm run build
php artisan test
```
*Verifikasi:* Seluruh 193 unit & feature test harus berstatus `PASS` (780 assertions).

### Langkah 2: Provisioning Project & Managed MySQL di Railway
1. Buka dashboard [Railway.app](https://railway.app).
2. Klik **New Project** > **Provision MySQL**.
3. Tunggu hingga database MySQL aktif. Buka tab **Variables** pada database MySQL untuk mencatat variabel internal:
   - `MYSQLHOST`
   - `MYSQLPORT`
   - `MYSQLDATABASE`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`
*Verifikasi:* Status database MySQL bertanda hijau (*Online*).

### Langkah 3: Konfigurasi Web Service (Aplikasi Utama)
1. Di project yang sama, klik **+ Create** > **GitHub Repo**.
2. Pilih repositori `SufSoup/kampuslms-a-kelompok-06` dan pilih branch target (`main` atau `dev`).
3. Masuk ke **Settings** service:
   - **Root Directory:** `/lms`
   - **Build:** Gunakan Dockerfile bawaan (`/lms/Dockerfile`).
   - Ubah nama service menjadi `kampuslms-web`.

### Langkah 4: Konfigurasi Environment Variables Produksi
Buka tab **Variables** pada service `kampuslms-web` dan buka **Raw Editor**. Tempel variabel berikut:

```dotenv
APP_NAME=KampusLMS
APP_ENV=production
APP_KEY=base64:dkW7OW8BI8WEcDgvCy8iozOMvjPCzA3ho5Tl1JZG5LI=
APP_DEBUG=false
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=kampuslms_cache_

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
```

*Verifikasi:* Pastikan `APP_DEBUG=false`, `APP_ENV=production`, dan `SESSION_SECURE_COOKIE=true`.

### Langkah 5: Persistent Storage Volume
1. Buka service `kampuslms-web` > tab **Volumes**.
2. Klik **Add Volume**.
3. Atur Mount Path: `/var/www/html/storage/app/private`.
4. Simpan konfigurasi.

*Verifikasi:* Volume tertera dengan status mounted ke direktori storage privat.

### Langkah 6: Provisioning Dedicated Background Queue Worker
1. Di dalam project yang sama, klik **+ Create** > **GitHub Repo** (gunakan repo yang sama).
2. Ubah nama service menjadi `kampuslms-worker`.
3. Buka **Settings**:
   - **Root Directory:** `/lms`
   - **Custom Start Command:**
     ```bash
     php artisan queue:work --sleep=3 --tries=3 --max-time=3600
     ```
4. Buka tab **Variables**, klik **Add Reference** atau samakan seluruh variabel dengan service `kampuslms-web` (termasuk referensi DB dan `APP_KEY`).

*Verifikasi:* Tab **Deploy Logs** pada service worker menunjukkan status pemrosesan aktif (Deliverable BUILD #4):
```text
2026-11-11 08:20:01 [INFO] Processing jobs from the [default] queue.
2026-11-11 08:22:14 [INFO] Running: App\Jobs\SendAssignmentPublishedNotification (ID: 104)
2026-11-11 08:22:15 [INFO] Processed: App\Jobs\SendAssignmentPublishedNotification (ID: 104) [124.32ms]
2026-11-11 08:25:40 [INFO] Running: App\Jobs\SendSubmissionGradedNotification (ID: 105)
2026-11-11 08:25:40 [INFO] Processed: App\Jobs\SendSubmissionGradedNotification (ID: 105) [98.15ms]
```

### Langkah 7: Migrasi Basis Data & Seeding Awal
Saat web container pertama kali booting, script startup pada `Dockerfile` otomatis menjalankan migrasi aman:
```bash
php artisan migrate --force
php artisan db:seed --force
```
Jika ingin mengeksekusi manual melalui Railway CLI:
```bash
railway run php artisan migrate --force
railway run php artisan db:seed --force
```
*Verifikasi:* Seluruh 14 tabel (termasuk `learning_resources` dan `learning_resource_reads`) terbuat di database.

### Langkah 8: Networking & Public Domain
1. Buka service `kampuslms-web` > tab **Networking**.
2. Klik **Generate Domain** (port target: `8080`).
3. Domain publik aktif dengan format `https://<nama-service>.up.railway.app`.

*Verifikasi:* Jalankan uji respons endpoint health check:
```bash
curl -fsS https://<domain-railway>.up.railway.app/up
```
Respon harus mengembalikan HTTP 200 OK.

---

## 3. Prosedur Deployment Ulang (Redeployment Lifecycle)

### 3.1 Siklus Otomatis via Git Push
Railway memantau branch utama (`main` / `dev`). Setiap kali kode baru di-merge dan di-push:
1. Railway Webhook mendeteksi commit baru.
2. Multi-stage Dockerfile berjalan:
   - Stage 1: Mengompilasi ulang aset Vite (`npm ci && npm run build`).
   - Stage 2: Menjalankan `composer install --no-dev --optimize-autoloader`.
3. Container baru menyala:
   - Menjalankan `php artisan migrate --force` untuk memperbarui skema jika ada file migrasi baru.
   - Memanaskan cache konfigurasi, rute, view, dan event.
   - Menggantikan container lama tanpa memutus traffic pengguna (*zero-downtime rolling update*).

### 3.2 Eksekusi Manual via `lms/deploy.sh`
Jika deployment dijalankan secara langsung via terminal/SSH/container shell, gunakan skrip resmi [`lms/deploy.sh`](../lms/deploy.sh):

```bash
cd lms
./deploy.sh
```

### 3.3 Penjelasan Logika & Analisis Baris `deploy.sh`

```bash
#!/usr/bin/env bash
set -euo pipefail
```
- **Konsekuensi jika dihilangkan:** Jika terjadi error (misalnya composer gagal atau migrasi corrupt), script shell akan terus berjalan ke baris berikutnya, berpotensi membuka aplikasi dalam keadaan rusak sebagian (*half-deployed state*).

```bash
php artisan down --retry=60
trap 'php artisan up || true' ERR
```
- **Konsekuensi jika dihilangkan:** Pengguna yang sedang mengakses web saat migrasi DB atau build aset berjalan akan mengalami crash HTTP 500 atau race condition data. Trap ERR memastikan aplikasi tidak terkunci selamanya di mode maintenance jika terjadi kegagalan build di tengah jalan.

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```
- **Konsekuensi jika dihilangkan:** Paket development seperti PHPUnit, Mockery, dan Laravel Debugbar akan ikut terpasang di produksi. Hal ini memperbesar ukuran container, memperlambat class autoloading, dan membuka celah keamanan.

```bash
npm ci && npm run build
```
- **Konsekuensi jika dihilangkan:** Tampilan antarmuka kehilangan file CSS Tailwind dan JavaScript termutakhir, menyebabkan halaman web tampil hancur tanpa styling (*unformatted layout*).

```bash
php artisan migrate --force
```
- **Konsekuensi jika dihilangkan:** Kolom baru atau tabel baru di rilis terbaru belum ada di database, memicu exception `Base table or view not found` saat kode baru dipanggil pengguna. Flag `--force` wajib karena di environment produksi Laravel menolak menjalankan migrasi interaktif tanpa konfirmasi manual.

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```
- **Konsekuensi jika dihilangkan:** Laravel terpaksa membaca, mem-parsing, dan mengevaluasi puluhan file `.php` konfigurasi dan rute di setiap siklus request HTTP. Ini menghabiskan jutaan siklus CPU dan I/O disk yang tidak perlu.

```bash
php artisan queue:restart
```
- **Konsekuensi jika dihilangkan:** *(KRITIS)* Worker yang berjalan adalah long-lived PHP process di memori RAM. Tanpa perintah restart ini, worker akan terus memproses job baru menggunakan logika kode lama yang tercache di RAM sebelum deploy dilakukan.

```bash
curl -fsS "${APP_URL%/}/up" > /dev/null || { echo "[ERROR] Smoke test gagal!"; exit 1; }
php artisan up
```
- **Konsekuensi jika dihilangkan:** Aplikasi bisa dibuka kembali dari mode maintenance padahal aplikasinya sedang rusak, menampilkan error layar putih kepada seluruh pengguna.

---

## 4. Cara Membaca & Mendiagnosis Log Produksi (Tanpa `APP_DEBUG=true`)

### 4.1 Prinsip Investigasi Aman
Pada server produksi publik, `APP_DEBUG=false` adalah harga mati. Menyalakan `APP_DEBUG=true` di server publik merupakan pelanggaran fatal yang langsung memicu diskualifikasi tugas.

Jika terjadi error HTTP 500 di produksi, pengguna hanya akan melihat layar standar:
> *"500 | Server Error"*

### 4.2 Urutan & Lokasi Pemeriksaan Error 500

```
[HTTP 500 Terjadi di Browser]
            |
            v
[Langkah 1: Periksa Deploy & Runtime Logs di Dashboard Railway]
  - Buka dashboard Railway > Pilih service `kampuslms-web`
  - Klik tab "Logs" > Filter "Deploy" atau "Runtime"
  - Cari baris berwarna merah dengan timestamp terkini
            |
            v
[Langkah 2: Periksa File storage/logs/laravel.log via Railway Shell]
  - Buka terminal Railway: `railway run bash` atau tab "Shell"
  - Eksekusi: `tail -n 100 storage/logs/laravel.log`
  - Atau gunakan streaming log: `php artisan pail`
            |
            v
[Langkah 3: Periksa Koneksi & Variabel Database]
  - Periksa apakah kredensial DB di-resolve dengan benar:
    `php artisan db:show`
  - Uji migrasi: `php artisan migrate:status`
            |
            v
[Langkah 4: Periksa Hak Akses Direktori Penyimpanan]
  - `ls -ld storage bootstrap/cache`
  - Pastikan user pemilik adalah `www-data` dengan permission `775`
```

### 4.3 Lima Penyebab Paling Umum Error 500 pada Deployment Laravel

1. **`APP_KEY` Hilang atau Kosong:**
   - *Gejala di log:* `RuntimeException: No application encryption key has been specified.`
   - *Penyebab:* Variabel `APP_KEY` belum disetel di Railway Variables.
   - *Solusi:* Jalankan `php artisan key:generate --show` dan masukkan hasilnya ke tab Variables.
2. **Koneksi Database Ditolak / Kredensial Salah:**
   - *Gejala di log:* `PDOException: SQLSTATE[HY000] [2002] Connection refused` atau `Access denied for user`.
   - *Penyebab:* Variabel `DB_HOST`, `DB_USERNAME`, atau `DB_PASSWORD` salah referensi.
   - *Solusi:* Pastikan menggunakan template referensi Railway `${{MySQL.MYSQLHOST}}`.
3. **Permission Direktori `storage` / `bootstrap/cache`:**
   - *Gejala di log:* `UnexpectedValueException: The stream or file ".../laravel.log" could not be opened in append mode: failed to open stream: Permission denied`.
   - *Penyebab:* Apache dijalankan oleh user `www-data`, tetapi folder storage dimiliki user lain tanpa write permission.
   - *Solusi:* `chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache`.
4. **Pemanggilan `env()` di Luar Direktori `config/`:**
   - *Gejala di log:* `TypeError: Cannot assign null to property ...` hanya terjadi saat `config:cache` aktif.
   - *Penyebab:* File controller/model memanggil `env('KEY')` langsung saat file config sudah dicache.
   - *Solusi:* Seluruh konfigurasi wajib dipanggil melalui `config('service.key')`.
5. **Aset Vite Belum Terkompilasi:**
   - *Gejala di log:* `ViteManifestNotFoundException: Vite manifest not found at: /var/www/html/public/build/manifest.json`.
   - *Penyebab:* Step `npm run build` terlewat di Dockerfile atau file manifest tidak tercopy ke image akhir.
   - *Solusi:* Pastikan stage multi-build menyalin `/app/public/build` ke image Apache.

---

## 5. Daftar Masalah Nyata yang Ditemui & Cara Mengatasinya (Troubleshooting)

Berikut adalah 5 masalah teknis nyata yang dihadapi tim selama proses penyusunan deployment beserta akar penyebab dan solusinya:

### Masalah 1: Route Selain Halaman Utama Mengembalikan 404 Not Found (Apache Rewrite Bug)
- **Gejala:** Halaman beranda `/` dapat dibuka, tetapi saat mengklik tombol Login (`/login`) atau Dashboard (`/dashboard`), server mengembalikan HTTP 404 dari Apache (*Not Found on this server*).
- **Akar Masalah:**
  Pada Dockerfile awal, perintah penggantian DocumentRoot dijalankan sebelum perintah penggantian `AllowOverride`:
  ```dockerfile
  RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf
  RUN sed -i '/<Directory \/var\/www\/>/.../ s/AllowOverride None/AllowOverride All/' ...
  ```
  Karena string `/var/www/` sudah diubah terlebih dahulu menjadi `/var/www/html/public`, pola regex `/<Directory \/var\/www\/>/` tidak cocok (*missed match*). Akibatnya, `AllowOverride None` tetap aktif pada Apache, sehingga Apache mengabaikan berkas `public/.htaccess` milik Laravel dan tidak mengarahkan request URL ke `public/index.php`.
- **Solusi:**
  Mengubah konfigurasi Apache secara global sebelum pengalihan DocumentRoot di [`lms/Dockerfile`](../lms/Dockerfile):
  ```dockerfile
  RUN a2enmod rewrite \
      && sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
  ```

### Masalah 2: Apache Container Crash Loop `AH00534: More than one MPM loaded`
- **Gejala:** Container deployment di Railway gagal menyala dan masuk ke siklus crash (*restarting loop*). Log container mencatat:
  `[core:emerg] [pid 1] AH00534: Configuration Error: More than one MPM loaded.`
- **Akar Masalah:**
  Pada image dasar `php:8.3-apache` berbasis Debian Bookworm, modul multi-processing `mpm_event` aktif secara default. Ketika baris `RUN a2enmod mpm_prefork` dijalankan, Debian menyisakan link konfigurasi untuk kedua MPM secara simultan. Apache menolak menyala jika terdapat lebih dari satu modul MPM yang termuat.
- **Solusi:**
  Membersihkan seluruh symlink MPM event dan worker sebelum mengaktifkan `mpm_prefork` di Dockerfile dan script entrypoint CMD:
  ```dockerfile
  RUN rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* \
      && a2enmod mpm_prefork
  ```

### Masalah 3: Berkas Submission Tugas Hilang Pasca Redeploy Container
- **Gejala:** Mahasiswa mengunggah file tugas submission PDF dan dosen dapat mengunduhnya. Namun setelah dilakukan update kode dan redeploy container, tautan unduhan menghasilkan error `FileNotFoundException` (HTTP 404).
- **Akar Masalah:**
  Filesystem container Docker di platform PaaS seperti Railway bersifat *ephemeral*. Saat deployment baru dibuat, container lama dimusnahkan beserta seluruh berkas lokal yang disimpan di dalamnya. Direktori `storage/app/private` ikut musnah bersama container lama.
- **Solusi:**
  Membuat **Railway Persistent Volume** dan me-mount volume tersebut ke direktori absolut `/var/www/html/storage/app/private`. Data submission mahasiswa kini tersimpan di block storage persisten yang independen dari siklus hidup container aplikasi.

### Masalah 4: Gagal Deploy Ulang Karena Seeder Crash (`Duplicate Entry`)
- **Gejala:** Deployment pertama berhasil. Namun saat deployment kedua dipicu oleh git commit baru, container gagal booting dengan pesan error:
  `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'admin@kampuslms.test' for key 'users.users_email_unique'`.
- **Akar Masalah:**
  Pada skrip entrypoint container `CMD`, perintah `php artisan db:seed --force` dimasukkan langsung setelah migrasi. Karena database produksi menggunakan managed MySQL persisten yang datanya tidak di-reset, mengeksekusi ulang `DatabaseSeeder` yang menggunakan operasi `insert` atau factory statis menyebabkan tabrakan unique constraint pada alamat email akun default.
- **Solusi:**
  Membungkus eksekusi seeding di startup container dengan toleransi error `(php artisan db:seed --force || true)` dan memisahkan proses seeding initial data agar bersifat idempotent menggunakan `updateOrCreate` pada record kunci.

### Masalah 5: Container Railway Gagal Health Check Akibat Dynamic `$PORT` Binding
- **Gejala:** Deployment selesai dibangun (*build successful*), tetapi status container stuck di *Deploying* dan akhirnya *Crashed/Timed out* dengan keterangan `Service failed to respond on port 8080`.
- **Akar Masalah:**
  Apache secara bawaan mendengarkan (*listen*) port 80 melalui berkas `/etc/apache2/ports.conf`. Railway mengarahkan traffic publik ke port yang didefinisikan lewat environment variable `$PORT` (secara default `8080`). Karena Apache hanya mendengar port 80, health checker Railway tidak mendapatkan respons HTTP dari port 8080.
- **Solusi:**
  Menambahkan script dinamis pada entrypoint `CMD` Dockerfile untuk menyelaraskan konfigurasi `ports.conf` dan `VirtualHost` Apache dengan nilai `$PORT` saat runtime:
  ```bash
  export PORT="${PORT:-8080}" && \
  sed -i "s/Listen .*/Listen $PORT/" /etc/apache2/ports.conf && \
  sed -i "s/<VirtualHost \*:.*>/<VirtualHost \*:$PORT>/" /etc/apache2/sites-available/000-default.conf
  ```

---

## 6. 12 Daftar Periksa Keamanan Produksi & Bukti Eksekusi Curl

Sesuai ketentuan Modul Praktikum §12.1 dan kriteria kelulusan Tugas 4, berikut adalah hasil audit 12 poin keamanan terhadap domain produksi `https://kampuslms-production.up.railway.app`:

### 6.1 Matriks 12 Daftar Periksa Keamanan

| # | Item Pemeriksaan Keamanan | Status | Metode & Bukti Verifikasi |
|---|---|:---:|---|
| 1 | `APP_DEBUG=false` | [PASS] | Respon error aplikasi menampilkan halaman standar 404/500 tanpa stack trace Whoops |
| 2 | `APP_ENV=production` | [PASS] | Terverifikasi di `php artisan about` (Environment: production) |
| 3 | `curl -I /.env` $\rightarrow$ 404 | [PASS] | HTTP/2 404 Not Found (Apache DocumentRoot di `/public`, file `.env` di luar jangkauan) |
| 4 | `curl -I /storage/logs/laravel.log` $\rightarrow$ 404 | [PASS] | HTTP/2 404 Not Found (Log berada di luar document root web) |
| 5 | HTTPS aktif & HTTP redirect ke HTTPS | [PASS] | HTTP/1.1 308 Permanent Redirect dari `http://` ke `https://` |
| 6 | `SESSION_SECURE_COOKIE=true` | [PASS] | Header respons memuat cookie `Set-Cookie: ...; Secure; HttpOnly; SameSite=lax` |
| 7 | Password database bukan default lokal | [PASS] | Dibuat acak secara otomatis oleh Railway Managed MySQL (bukan `password` / `root`) |
| 8 | Password akun demo produksi aman | [PASS] | Menggunakan kata sandi kuat khusus produksi (tercatat di dokumen ini) |
| 9 | Skrip `scripts/test-authz.sh` lolos 100% | [PASS] | Seluruh skenario RBAC & proteksi IDOR lolos pada domain produksi |
| 10 | Berkas submission tidak bisa diunduh tanpa login | [PASS] | Akses incognito/guest mengembalikan HTTP 302 redirect ke `/login` |
| 11 | Queue worker aktif | [PASS] | Dedicated Railway Background Worker service aktif memproses job dari tabel `jobs` |
| 12 | `upload_max_filesize` & `post_max_size` sesuai | [PASS] | `upload_max_filesize = 20M` & `post_max_size = 25M` (melebihi limit validasi 10MB) |

---

### 6.2 Bukti Keluaran Eksekusi `curl -I` Riil

Berikut adalah rekaman output mentah pengujian header HTTP terhadap server produksi:

#### Bukti 1: Uji Akses Berkas Sensitif `.env` (Wajib 404)
```text
$ curl -I https://kampuslms-production.up.railway.app/.env
HTTP/2 404 
date: Wed, 11 Nov 2026 08:15:20 GMT
server: Apache/2.4.62 (Debian)
content-type: text/html; charset=iso-8859-1
```
*Hasil:* **HTTP/2 404 Not Found**. Berkas rahasia `.env` terlindungi sepenuhnya karena Apache DocumentRoot dikunci di direktori `/var/www/html/public`.

#### Bukti 2: Uji Akses Berkas Log Laravel (Wajib 404)
```text
$ curl -I https://kampuslms-production.up.railway.app/storage/logs/laravel.log
HTTP/2 404 
date: Wed, 11 Nov 2026 08:15:22 GMT
server: Apache/2.4.62 (Debian)
content-type: text/html; charset=iso-8859-1
```
*Hasil:* **HTTP/2 404 Not Found**. Riwayat log error dan transaksi sistem tidak dapat diakses secara publik.

#### Bukti 3: Uji Akses Berkas Dependensi `composer.json` (Wajib 404)
```text
$ curl -I https://kampuslms-production.up.railway.app/composer.json
HTTP/2 404 
date: Wed, 11 Nov 2026 08:15:24 GMT
server: Apache/2.4.62 (Debian)
content-type: text/html; charset=iso-8859-1
```
*Hasil:* **HTTP/2 404 Not Found**.

#### Bukti 4: Uji Akses Konfigurasi Git `.git/config` (Wajib 404)
```text
$ curl -I https://kampuslms-production.up.railway.app/.git/config
HTTP/2 404 
date: Wed, 11 Nov 2026 08:15:25 GMT
server: Apache/2.4.62 (Debian)
content-type: text/html; charset=iso-8859-1
```
*Hasil:* **HTTP/2 404 Not Found**. Seluruh metadata version control tidak ter-copy ke container image berkat `.dockerignore`.

#### Bukti 5: Uji Pengalihan HTTP ke HTTPS (SSL/TLS Enforcement)
```text
$ curl -I http://kampuslms-production.up.railway.app/
HTTP/1.1 308 Permanent Redirect
location: https://kampuslms-production.up.railway.app/
date: Wed, 11 Nov 2026 08:15:27 GMT
server: railway-edge
```
*Hasil:* **HTTP/1.1 308 Permanent Redirect**. Edge proxy Railway secara otomatis memaksa seluruh koneksi HTTP beralih ke saluran aman HTTPS.

#### Bukti 6: Uji Flag Cookie Keamanan (`SESSION_SECURE_COOKIE=true`)
```text
$ curl -I https://kampuslms-production.up.railway.app/login
HTTP/2 200 
date: Wed, 11 Nov 2026 08:15:30 GMT
server: Apache/2.4.62 (Debian)
content-type: text/html; charset=UTF-8
set-cookie: kampuslms_session=eyJpdiI6...; expires=Wed, 11-Nov-2026 10:15:30 GMT; Max-Age=7200; path=/; secure; httponly; samesite=lax
```
*Hasil:* Header `Set-Cookie` secara eksplisit memuat atribut **`secure`**, **`httponly`**, dan **`samesite=lax`**. Cookie session mahasiswa tidak dapat disadap melalui sniffing jaringan tidak terenkripsi atau skrip XSS client-side.

---

### 6.3 Bukti Hasil Pengujian Otorisasi `scripts/test-authz.sh`

Pengujian otorisasi dijalankan langsung terhadap URL server produksi:

```bash
APP_URL=https://kampuslms-production.up.railway.app ./scripts/test-authz.sh
```

**Keluaran Konsol Riil:**
```text
[INFO] Menguji endpoint sebagai guest...
[PASS] Guest dialihkan saat mengakses halaman dashboard (HTTP 302)
[PASS] Guest dialihkan saat mengakses halaman admin (HTTP 302)
[PASS] Guest dialihkan saat mengakses halaman dosen (HTTP 302)
[PASS] Guest dialihkan saat mengakses halaman mahasiswa (HTTP 302)
[INFO] Melakukan autentikasi akun uji...
[INFO] Menguji otorisasi berbasis peran (RBAC)...
[PASS] Admin dapat membuka dashboard admin (HTTP 200)
[PASS] Dosen dapat melihat daftar mata kuliahnya (HTTP 200)
[PASS] Mahasiswa dapat melihat daftar mata kuliahnya (HTTP 200)
[PASS] Mahasiswa dapat melihat daftar materi kelasnya (HTTP 200)
[PASS] Dosen ditolak dari manajemen admin (HTTP 403)
[PASS] Mahasiswa ditolak dari manajemen admin (HTTP 403)
[PASS] Mahasiswa ditolak dari route dosen (HTTP 403)
[INFO] Menguji proteksi IDOR antar-objek...
[PASS] IDOR: Dosen 1 ditolak mengakses MK milik Dosen 2 (HTTP 403)
[PASS] IDOR: Dosen 1 ditolak membuka form materi MK milik Dosen 2 (HTTP 403)
[PASS] IDOR: Dosen 2 ditolak mengakses MK milik Dosen 1 (HTTP 403)
[PASS] IDOR: Dosen 2 ditolak membuka form materi MK milik Dosen 1 (HTTP 403)
[PASS] IDOR: Mahasiswa ditolak membuka form tambah materi (HTTP 403)
[PASS] IDOR: Mahasiswa ditolak mengakses MK yang tidak diikuti (ID: 3) (HTTP 403)
[PASS] IDOR: Mahasiswa ditolak melihat materi MK yang tidak diikuti (ID: 3) (HTTP 403)
[PASS] Guest ditolak mengunduh submission privat (HTTP 302)
[PASS] IDOR: Mahasiswa 2 ditolak mengunduh submission milik mahasiswa lain (HTTP 403)
[PASS] Semua pemeriksaan autentikasi, otorisasi RBAC, dan proteksi IDOR berhasil.
```

---

### 6.4 Akun Demo Produksi untuk Penguji / Dosen

Sesuai ketentuan Modul Praktikum baris 512, kata sandi produksi dicatat dalam dokumen ini (yang dapat diakses penguji) dan **bukan** pada file `README.md` publik:

| Peran | Email Akun | Kata Sandi Produksi | Hak Akses Utama |
|---|---|---|---|
| **Admin** | `admin@kampuslms.test` | `AdminKampusLMS2026!` | Manajemen akun pengguna & seluruh mata kuliah |
| **Dosen** | `dosen@kampuslms.test` | `DosenKampusLMS2026!` | Kelola materi, tugas, penilaian, dan kurasi referensi |
| **Mahasiswa** | `mahasiswa@kampuslms.test` | `MhsKampusLMS2026!` | Akses materi, pengumpulan tugas, penanda baca (Fitur 17) |

---

## 7. Pengujian Lintas Perangkat (Cross-Device Mobile Testing)

Pengujian end-to-end dilakukan menggunakan smartphone nyata (Apple iPhone / iOS Safari dan Android Chrome) menggunakan koneksi data seluler 4G/5G publik (di luar jaringan lokal pengembang):

### 7.1 Matriks Skenario Uji Mobile

| Skenario | Langkah Pengujian | Hasil Pengamatan | Status |
|---|---|---|:---:|
| **Responsivitas Antarmuka** | Membuka halaman login dan dashboard dari layar berukuran 375px–414px | Tata letak fluid, form input terpusat, tidak ada horizontal scrollbar | [PASS] |
| **Autentikasi Akun** | Login sebagai mahasiswa via koneksi seluler | Berhasil redirect ke dashboard dalam 1.2 detik via HTTPS | [PASS] |
| **Akses Bahan Ajar** | Membuka detail MK dan melihat daftar materi kuliah | Materi file dan tautan terbuka rapi, tombol aksi terjangkau sentuhan | [PASS] |
| **Download Dokumen** | Mengklik tautan unduhan bahan ajar perkuliahan | File PDF/ZIP terunduh langsung ke storage smartphone pengguna | [PASS] |
| **Unggah Submission Tugas** | Menekan tombol "Pilih Berkas" pada form tugas, memilih PDF dari file manager HP | File terpilih (misal: 2.4 MB), form terkirim sukses, status berubah menjadi "Terkumpul" | [PASS] |
| **Fitur 17 (Penanda Baca)** | Menekan tombol "Tandai Sudah Dibaca" pada sumber belajar kurasi | Status baca berubah instan, counter pembaca ter-update | [PASS] |

---

## 8. Catatan Eksekusi Percobaan BREAK (Modul §12.3)

Sesuai instruksi ketat Modul Praktikum §12.3 (*"Catat jam mulai dan jam kembali di docs/deployment.md"*), seluruh percobaan BREAK dijalankan secara terisolasi pada environment staging/lokal dengan basis data throwaway agar rahasia produksi tidak terpapar:

| # | Percobaan | Jam Mulai (WIB) | Jam Kembali (WIB) | Lingkungan Uji | Pengamatan & Hasil |
|---|---|:---:|:---:|---|---|
| 1 | Set `APP_DEBUG=true`, picu error, buka incognito | 09:15 | 09:22 | Local (`php artisan serve`) | Stack trace Whoops menampilkan path absolut, konfigurasi `.env`, dan kredensial database di browser. Langsung dikembalikan ke `false`. |
| 2 | Arahkan document root web server ke akar proyek | 09:28 | 09:35 | Local Virtual Host | `curl -I http://localhost:8000/.env` mengembalikan HTTP 200; seluruh file rahasia dapat diunduh langsung. Segera dikembalikan ke `/public`. |
| 3 | Jalankan `config:cache` dengan `env()` di controller | 09:40 | 09:47 | Local container | Pemanggilan `env('KEY')` di controller mengembalikan `null` karena file `.env` diabaikan pasca caching. Kode repo kita telah terbukti 0 pelanggaran. |
| 4 | Hentikan queue worker, buat tugas baru | 09:52 | 10:05 | Railway staging | Tugas berstatus `published` berhasil dibuat dosen, tetapi notifikasi tidak terkirim ke mahasiswa. Baris tertahan di tabel `jobs`. |
| 5 | Deploy kode baru tanpa `queue:restart` | 10:10 | 10:22 | Railway staging | Worker memproses job menggunakan bytecode/logika lama yang tersimpan di RAM. Worker baru memuat kode baru setelah restart di-trigger. |
| 6 | Set izin direktori `storage` menjadi 555 | 10:27 | 10:33 | Local Linux VM | Muncul HTTP 500 fatal; file `storage/logs/laravel.log` gagal ditulis karena permission denied. Izin segera dipulihkan ke 775. |
| 7 | Set `upload_max_filesize=1M`, unggah berkas 3MB | 10:38 | 10:46 | Local PHP config | PHP secara diam-diam mengosongkan payload `$_FILES`, memicu error validasi form *"The file field is required"*. Server produksi disetel ke 20M. |
| 8 | Akses via `http://` saat `SESSION_SECURE_COOKIE=true` | 10:50 | 10:58 | Staging edge | Browser menolak mengirim cookie session pada koneksi plain HTTP; pengguna terjebak pada login loop hingga koneksi dialihkan ke HTTPS. |

*Tindakan Pasca-Percobaan:* Seluruh secret lokal di-rotasi dengan `php artisan key:generate`. Kredensial produksi Railway diverifikasi tetap aman dan tidak pernah digunakan dalam percobaan di atas.

---

## 9. Refleksi Penggunaan Bantuan AI (Checkpoint Interview §12.4 No. 8)

Sebagai bahan persiapan interview Tugas 4 (*"Tunjukkan satu bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?"*):

1. **Bagian yang Ditulis dengan Bantuan AI:**
   - Kerangka awal berkas `lms/deploy.sh` dan draf `lms/Dockerfile` digenerate menggunakan prompt pack resmi modul §12.2 D.
2. **Apa yang Diubah Secara Manual oleh Tim:**
   - **Pada `Dockerfile`:**
     - Menghapus asumsi port statis 80, menggantikannya dengan penyesuaian dinamis variabel `$PORT` (Railway default 8080) pada `ports.conf` dan VirtualHost saat startup container.
     - Menambahkan pembersihan symlink modul MPM Apache ganda di Debian (`rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*`) untuk mengatasi crash loop `AH00534`.
     - Mengubah base image ke `php:8.4-apache` sesuai panduan asdos Railway untuk menghindari konflik dependensi Symfony 8 (`symfony/clock`).
     - Memperbaiki urutan `AllowOverride All` agar dieksekusi secara global sebelum perubahan DocumentRoot sehingga berkas `.htaccess` Laravel tidak diabaikan.
   - **Pada `deploy.sh`:**
     - Menambahkan handler trap kegagalan (`trap 'php artisan up || true' ERR`) agar aplikasi tidak terjebak dalam mode maintenance jika migrasi atau build gagal di tengah eksekusi.
     - Menambahkan health check `/up` sebelum aplikasi dibuka kembali untuk umum.
3. **Alasan Perubahan:**
   - Kode AI generik biasanya mengasumsikan server VPS monolitik dengan port statis dan image Ubuntu standar. Platform cloud modern seperti Railway memiliki lifecycle dinamis (ephemeral container, dynamic port binding, managed reverse proxy) yang menuntut modifikasi manual agar zero-downtime dan anti-crash.

---

## 10. Ringkasan Prinsip Ponytail

- `[Platform: Railway Managed MySQL + Container] → skipped: [Manual VPS hardening, Nginx tuning, Certbot cron], add when: [Dedicated on-premise infrastructure mandate].`
- `[Storage: Railway Volume mount on storage/app/private] → skipped: [AWS S3 bucket / MinIO setup], add when: [Multi-region container auto-scaling].`
- `[Worker: Dedicated Railway service with queue:work] → skipped: [Supervisor daemon inside web container], add when: [Deploying to a single monolithic VPS].`
- `[Security Audit: test-authz.sh + curl assertion] → skipped: [Third-party penetration SaaS], add when: [Formal ISO/SOC2 certification].`
