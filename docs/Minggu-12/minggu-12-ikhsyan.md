# Notes Minggu 12 Pemrograman Web

- **Nama:** Muhammad Ikhsyan
- **NIM:** 10241049
- **Kelompok:** Kelompok 06 (Kelas A)
- **Topik:** Optimisasi Produksi, Queue Engine & Otomasi Deployment
- **Branch:** `ikhsyan`
- **Status verifikasi produksi:** `[PENDING]` — service Railway belum dibuat dan domain live belum aktif.

---

## 1. READ — Bedah Server & Optimisasi Produksi

### 1.1 Perbandingan `php artisan about` (Lokal vs Target Produksi)

Kolom produksi di bawah adalah target konfigurasi, bukan hasil verifikasi. Deployment Railway belum berjalan, sehingga `php artisan about` di Railway Shell belum dapat dijalankan. Setelah deploy, kolom produksi wajib diisi dengan output nyata.

| Parameter | Lokal (terukur 12 Nov 2026) | Target Produksi (belum diverifikasi) | Alasan Perbedaan |
|---|---|---|---|
| `Environment` | `local` | `production` | Mengaktifkan perlindungan ketat Laravel di produksi |
| `Debug Mode` | `ENABLED` | `false` | Mencegah Whoops membocorkan stack trace, query SQL, session cookies, dan path server |
| `URL` | `http://localhost` | `https://*.up.railway.app` | Wajib HTTPS dengan sertifikat SSL otomatis |
| `Maintenance Mode` | `OFF` | Dikendalikan via `deploy.sh` | Mencegah race condition saat migrasi/build aset |
| `Config Cache` | `NOT CACHED` | `CACHED` | Menghilangkan I/O disk untuk parsing file config |
| `Route Cache` | `NOT CACHED` | `CACHED` | Menghindari regex parsing rute berulang per request |
| `View Cache` | `NOT CACHED` | `CACHED` | Template Blade terkompilasi statis di awal |
| `Event Cache` | `NOT CACHED` | `CACHED` | Listener mapping langsung terbaca dari array cache |
| `Queue Connection` | `database` | `database` | Menggunakan tabel `jobs` tanpa dependensi Redis |

### 1.2 Bukti Audit Cache Lokal

Perintah cache dijalankan di direktori `lms/` pada 12 Nov 2026:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan about
```

Potongan output `php artisan about` setelah keempat perintah:

```text
Cache
Config .............................................................. CACHED
Events .............................................................. CACHED
Routes .............................................................. CACHED
Views ............................................................... CACHED
```

Setelah audit, cache lokal dibersihkan dengan `php artisan optimize:clear` agar environment development kembali membaca `.env`. Verifikasi cache di Railway Shell masih `[PENDING]` sampai deployment berjalan.

### 1.3 Audit Keamanan `env()` Pasca `config:cache`

Ketika `php artisan config:cache` dieksekusi, Laravel menggabungkan seluruh konfigurasi ke file statis `bootstrap/cache/config.php` dan menghentikan pembacaan berkas `.env`.

Akibatnya: seluruh helper `env('KEY')` yang dipanggil di luar direktori `config/` akan mengembalikan `null`. Repositori telah diaudit dan memiliki **0 pelanggaran** pemanggilan `env()` di `app/`, `routes/`, dan `database/` melalui `PerformanceEagerLoadingTest::test_no_env_calls_outside_config_directory`. Audit ini berjalan pada test suite lokal; verifikasi ulang di produksi dilakukan setelah deploy.

---

## 2. BREAK — Percobaan Queue & Siklus Deployment

Percobaan di bawah dijalankan pada worker lokal. Verifikasi ulang di Railway, termasuk isi tabel `jobs`, notifikasi mahasiswa, dan screenshot service worker, masih `[PENDING]`.

### 2.1 BREAK #4: Hentikan Background Worker, Buat Tugas Baru

- **Tindakan:** Menghentikan proses `php artisan queue:work` lokal, lalu login sebagai dosen dan menerbitkan tugas baru berstatus `published`.
- **Pengamatan:**
  1. Dosen berhasil membuat tugas tanpa error (HTTP 302 redirect dengan pesan sukses).
  2. Mahasiswa yang membuka halaman notifikasi tidak menerima notifikasi apa pun.
  3. Di database, baris baru masuk dan tertahan di tabel `jobs` dengan kolom `attempts = 0`.
- **Kesimpulan:** Background queue memisahkan eksekusi request web dari pengiriman notifikasi. Jika worker mati, aplikasi web tetap tampak normal, tetapi tugas latar belakang gagal terkirim secara diam-diam (*silent failure*). Pemantauan status worker adalah hal kritis.

### 2.2 BREAK #5: Deploy Kode Baru Tanpa `queue:restart`

- **Tindakan:** Mengubah logika pemrosesan notifikasi atau format pesan, lalu menjalankan worker tanpa `php artisan queue:restart`.
- **Pengamatan:** Worker yang sedang aktif terus memproses job yang masuk menggunakan logika kode lama yang masih tersimpan di RAM proses PHP worker. Perubahan kode baru aktif setelah worker dimatikan dan di-spawn ulang.
- **Kesimpulan:** `php artisan queue:restart` wajib dijalankan di akhir setiap script deployment agar worker melakukan restart graceful dan memuat kode versi terbaru dari disk.

---

## 3. FIX — Otomasi & Perlindungan Deployment

Perbaikan yang diterapkan untuk menjamin deployment berjalan deterministik:

1. **`lms/deploy.sh`:**
   - Menerapkan `set -euo pipefail` agar skrip berhenti di kesalahan pertama.
   - Mengaktifkan mode maintenance (`php artisan down --retry=60`) dengan `trap 'php artisan up || true' ERR` agar aplikasi tidak terkunci saat proses gagal.
   - Menjalankan `composer install --no-dev --optimize-autoloader --no-interaction` dan berhenti jika Composer tidak tersedia.
   - Menjalankan `npm ci && npm run build` jika Node.js tersedia; jika tidak, memverifikasi `public/build/manifest.json` hasil Docker Stage 1.
   - Menjalankan migrasi otomatis berproteksi (`php artisan migrate --force`).
   - Mengompilasi ulang seluruh cache: `config:cache`, `route:cache`, `view:cache`, `event:cache`.
   - Menjalankan `php artisan queue:restart`.
   - Membuka kembali aplikasi dengan `php artisan up`, lalu menjalankan smoke test `curl -fsS "$APP_URL/up"` dan keluar dengan status non-zero jika gagal.
   - Setiap blok diberi komentar konsekuensi jika perintah dihilangkan.

2. **Pembersihan Emoticon/Emoji pada Tampilan Web:**
   - Menghapus karakter emoji di `layout.blade.php`, `dashboard.blade.php`, `courses/show.blade.php`, dan `login.blade.php`.
   - Mengganti icon emoji dengan icon SVG inline berbasis path dan badge teks minimalis (ADMIN, DOSEN, MHS) agar tampilan konsisten lintas sistem operasi.

---

## 4. BUILD — Kontribusi Muhammad Ikhsyan (`10241049`)

### 4.1 Berkas yang Dibuat & Dimodifikasi

| Berkas | Aksi | Deskripsi |
|---|---|---|
| `lms/deploy.sh` | Dibuat | Skrip deployment otomatis lengkap dengan komentar konsekuensi tiap blok |
| `docs/Minggu-12/minggu-12-ikhsyan.md` | Dibuat | Laporan individual READ-BREAK-FIX-BUILD Minggu 12 |
| `lms/resources/views/components/layout.blade.php` | Diubah | Mengganti icon emoji lonceng dengan SVG icon native |
| `lms/resources/views/dashboard.blade.php` | Diubah | Mengganti icon emoji mahasiswa, materi, dan tugas dengan SVG |
| `lms/resources/views/courses/show.blade.php` | Diubah | Mengganti icon emoji dosen, buku, bintang, dan checklist dengan SVG |
| `lms/resources/views/auth/login.blade.php` | Diubah | Mengganti icon emoji demo account dengan tag badge teks bersih |
| `lms/.gitignore` | Diubah | Mengabaikan cache `/storage/debugbar` agar tidak mengotori repositori |

### 4.2 Queue Worker Service (Railway)

Konfigurasi service yang disiapkan:

- **Nama service:** `kampuslms-worker`
- **Source:** repositori yang sama, root directory `/lms`
- **Start command:** `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`
- **Environment:** variabel yang sama dengan `kampuslms-web`, terutama `APP_KEY`, kredensial MySQL, dan `QUEUE_CONNECTION=database`.

Prosedur verifikasi setelah deploy:

1. Terbitkan tugas berstatus `published` dari akun dosen.
2. Pastikan baris di tabel `jobs` berkurang dan notifikasi masuk ke akun mahasiswa terdaftar.
3. Ambil screenshot status service worker di dashboard Railway.

Status: `[PENDING]` — service belum dibuat karena deployment Railway belum berjalan.

### 4.3 Prinsip Ponytail yang Diterapkan

- `[Queue: Database driver + Railway worker service] → skipped: [Redis queue cluster / Horizon], add when: [Queue throughput exceeds database I/O limits].`
- `[Worker supervision: Railway native container restart policy] → skipped: [Supervisor daemon inside web container], add when: [Deploying to a single monolithic VPS].`
- `[Deployment: Shell script with set -euo pipefail + curl healthcheck] → skipped: [Ansible / GitHub Actions Deploy Agent], add when: [Multi-server fleet deployment needed].`
- `[UI Icons: Inline SVG + Tailwind text badge] → skipped: [Heavy icon libraries like FontAwesome / Lucide NPM packages], add when: [Application design system standardizes 100+ icons].`

### 4.4 Hasil Pengujian

- Seluruh unit & feature test pada branch `ikhsyan`: **195 passed (784 assertions)**.
- Validasi `test_no_env_calls_outside_config_directory`: **Passed**.
- Validasi `NotificationEngineTest` & `QueueReliabilityTest`: **Passed**.
- Validasi sintaks `lms/deploy.sh` dengan `bash -n`: **Passed**.

### 4.5 Status Deliverable

| Deliverable | Status | Bukti |
|---|---|---|
| `lms/deploy.sh` dengan flow maintenance, cache, queue restart, health check | `[PASS]` | Berkas tersedia dan lolos `bash -n` |
| Dokumentasi kontribusi Ikhsyan | `[PASS]` | Berkas ini |
| Audit cache lokal (`config`, `route`, `view`, `event`) | `[PASS]` | Output `php artisan about` pada §1.2 |
| Queue worker service di Railway | `[PENDING]` | Service belum dibuat |
| Verifikasi `php artisan about` di Railway Shell | `[PENDING]` | Deployment belum berjalan |
| Uji queue live dan screenshot worker | `[PENDING]` | Deployment belum berjalan |
| `curl` keamanan dan `test-authz.sh` live | `[PENDING]` | Domain belum aktif |

### 4.6 Catatan Status Live

Domain `https://kampuslms-a-06-production.up.railway.app` belum melayani aplikasi karena service Railway belum dibuat. Klaim live dan 12 daftar periksa keamanan di `docs/deployment.md` serta status live di `README.md` belum sah sampai deployment dijalankan dan diuji ulang. PIC dokumentasi dan keamanan perlu memperbarui bagian tersebut setelah verifikasi live.
