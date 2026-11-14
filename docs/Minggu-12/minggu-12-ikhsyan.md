# Notes Minggu 12 Pemrograman Web

- **Nama:** Muhammad Ikhsyan
- **NIM:** 10241049
- **Kelompok:** Kelompok 06 (Kelas A)
- **Topik:** Optimisasi Produksi, Queue Engine & Otomasi Deployment
- **Branch:** `ikhsyan`

---

## 1. READ — Bedah Server & Optimisasi Produksi

Analisis konfigurasi produksi Laravel 12 pada repositori KampusLMS:

### 1.1 Perbandingan `php artisan about` (Lokal vs Produksi)

| Parameter | Lokal (Development) | Produksi (Railway Container) | Alasan Perbedaan |
|---|---|---|---|
| `Environment` | `local` | `production` | Mengaktifkan perlindungan ketat Laravel di produksi |
| `Debug Mode` | `true` | `false` | Mencegah Whoops membocorkan stack trace, query SQL, session cookies, dan path server |
| `URL` | `http://127.0.0.1:8000` | `https://*.up.railway.app` | Wajib HTTPS dengan sertifikat SSL otomatis |
| `Maintenance Mode` | `OFF` | Dikendalikan via `deploy.sh` | Mencegah race condition saat migrasi/build aset |
| `Config Cache` | `NOT CACHED` | `CACHED` | Menghilangkan I/O disk untuk parsing file config |
| `Route Cache` | `NOT CACHED` | `CACHED` | Menghindari regex parsing rute berulang per request |
| `View Cache` | `NOT CACHED` | `CACHED` | Template Blade terkompilasi statis di awal |
| `Event Cache` | `NOT CACHED` | `CACHED` | Listener mapping langsung terbaca dari array cache |
| `Queue Connection` | `database` | `database` | Menggunakan tabel `jobs` tanpa dependensi Redis |

### 1.2 Audit Keamanan `env()` Pasca `config:cache`

Ketika `php artisan config:cache` dieksekusi, Laravel menggabungkan seluruh konfigurasi ke file statis `bootstrap/cache/config.php` dan menghentikan pembacaan berkas `.env`.

Akibatnya: seluruh helper `env('KEY')` yang dipanggil di luar direktori `config/` akan mengembalikan `null`. Pada audit Minggu 11, repositori kita telah terverifikasi memiliki **0 pelanggaran** pemanggilan `env()` di `app/`, `routes/`, dan `database/` melalui `PerformanceEagerLoadingTest::test_no_env_calls_outside_config_directory`. Hal ini menjamin aplikasi aman dan tidak crash saat `config:cache` aktif di server Railway.

---

## 2. BREAK — Percobaan Queue & Siklus Deployment

### 2.1 BREAK #4: Hentikan Background Worker, Buat Tugas Baru
- **Tindakan:** Menghentikan service queue worker (atau mematikan proses `queue:work`), lalu login sebagai dosen dan menerbitkan tugas baru berstatus `published`.
- **Pengamatan:**
  1. Dosen berhasil membuat tugas tanpa error (HTTP 302 redirect dengan pesan sukses).
  2. Mahasiswa yang membuka halaman notifikasi **tidak menerima notifikasi apa pun**.
  3. Di database, baris baru masuk dan tertahan di tabel `jobs` dengan kolom `attempts = 0`.
- **Kesimpulan:** Background queue memisahkan eksekusi request web dari pengiriman notifikasi. Jika worker mati, aplikasi web tetap tampak normal, tetapi tugas latar belakang gagal terkirim secara diam-diam (*silent failure*). Pemantauan status worker adalah hal kritis.

### 2.2 BREAK #5: Deploy Kode Baru Tanpa `queue:restart`
- **Tindakan:** Mengubah logika pemrosesan notifikasi atau format pesan, lalu menjalankan deploy ulang tanpa menyertakan perintah `php artisan queue:restart`.
- **Pengamatan:** Worker yang sedang aktif terus memproses job yang masuk menggunakan logika kode lama yang masih tersimpan di RAM proses PHP worker. Perubahan kode baru baru aktif jika worker mati dan di-spawn ulang.
- **Kesimpulan:** `php artisan queue:restart` wajib dijalankan di akhir setiap script deployment agar worker melakukan restart graceful dan memuat kode versi terbaru dari disk.

---

## 3. FIX — Otomasi & Perlindungan Deployment

Perbaikan yang diterapkan untuk menjamin deployment berjalan deterministik:

1. **Membuat `lms/deploy.sh`:**
   - Menerapkan `set -euo pipefail` agar skrip berhenti di kesalahan pertama.
   - Mengaktifkan mode maintenance (`php artisan down --retry=60`) dengan trap pemulihan jika terjadi error di tengah proses.
   - Menjalankan `composer install --no-dev --optimize-autoloader` agar dependensi debugger lokal tidak membebani produksi.
   - Menjalankan migrasi otomatis berproteksi (`php artisan migrate --force`).
   - Membersihkan dan mengompilasi ulang seluruh cache: config, route, view, event.
   - Menjalankan `php artisan queue:restart`.
   - Menguji ketersediaan aplikasi via smoke test `/up` sebelum membuka kembali mode maintenance (`php artisan up`).

2. **Pembersihan Emoticon/Emoji pada Tampilan Web:**
   - Menghapus seluruh karakter emoji di `layout.blade.php`, `dashboard.blade.php`, `courses/show.blade.php`, dan `login.blade.php`.
   - Mengganti icon emoji dengan icon SVG inline berbasis path dan badge teks minimalis (ADMIN, DOSEN, MHS) demi tampilan profesional dan konsisten lintas sistem operasi.

---

## 4. BUILD — Kontribusi Muhammad Ikhsyan (`10241049`)

### 4.1 Berkas yang Dibuat & Dimodifikasi

| Berkas | Aksi | Deskripsi |
|---|---|---|
| `lms/deploy.sh` | Dibuat | Skrip deployment otomatis lengkap dengan komentar konsekuensi tiap baris |
| `docs/Minggu-12/minggu-12-ikhsyan.md` | Dibuat | Laporan individual READ-BREAK-FIX-BUILD Minggu 12 |
| `lms/resources/views/components/layout.blade.php` | Diubah | Mengganti icon emoji lonceng dengan SVG icon native |
| `lms/resources/views/dashboard.blade.php` | Diubah | Mengganti icon emoji mahasiswa, materi, dan tugas dengan SVG |
| `lms/resources/views/courses/show.blade.php` | Diubah | Mengganti icon emoji dosen, buku, bintang, dan checklist dengan SVG |
| `lms/resources/views/auth/login.blade.php` | Diubah | Mengganti icon emoji demo account dengan tag badge teks bersih |
| `lms/.gitignore` | Diubah | Mengabaikan cache `/storage/debugbar` agar tidak mengotori repositori |

### 4.2 Prinsip Ponytail yang Diterapkan
- `[Queue: Database driver + Railway worker service] → skipped: [Redis queue cluster / Horizon], add when: [Queue throughput exceeds database I/O limits].`
- `[Worker supervision: Railway native container restart policy] → skipped: [Supervisor daemon inside web container], add when: [Deploying to a single monolithic VPS].`
- `[Deployment: Shell script with set -euo pipefail + curl healthcheck] → skipped: [Ansible / GitHub Actions Deploy Agent], add when: [Multi-server fleet deployment needed].`
- `[UI Icons: Inline SVG + Tailwind text badge] → skipped: [Heavy icon libraries like FontAwesome / Lucide NPM packages], add when: [Application design system standardizes 100+ icons].`

### 4.3 Hasil Pengujian
- Seluruh unit & feature test: **193 passed (780 assertions)**.
- Validasi `test_no_env_calls_outside_config_directory`: **Passed**.
- Validasi `NotificationEngineTest` & `QueueReliabilityTest`: **Passed**.
