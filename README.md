# KampusLMS - Kelompok 06 (Kelas A)

Proyek ini adalah implementasi tugas Modul 1 (Mengenal Laravel 12) untuk pengembangan purwarupa Sistem Informasi Manajemen Pembelajaran (KampusLMS).

## Deployment Produksi (Milestone M4 / Tugas 4)

- **Aplikasi Live:** [https://kampuslms-a-06-production.up.railway.app](https://kampuslms-a-06-production.up.railway.app)
- **Status:** Live di domain publik dengan sertifikat HTTPS aktif
- **Dokumentasi Lengkap:** Panduan deployment dari nol, spesifikasi server, mitigasi masalah, dan kata sandi akun demo produksi dicatat di [`docs/deployment.md`](docs/deployment.md)

## Daftar Anggota

| Nama Lengkap | NIM |
| :--- | :--- |
| Muhammad Farraz Kanz | 10241047 |
| Muhammad Ikhsyan | 10241049 |
| Muhammad Syahid Ridho | 10241051 |
| Muhammad Yusuf Pratama | 10241053 |

## Cara Instalasi

Ikuti langkah-langkah di bawah ini untuk menjalankan proyek secara lokal:

1. **Clone repositori**
   ```bash
   git clone [https://github.com/SufSoup/kampuslms-a-kelompok-06.git]
   cd kampuslms-a-kelompok-06
   ```

2. **Install dependensi (Vendor)**
   ```bash
   composer install
   ```

3. **Siapkan pengaturan environment**
   ```bash
   cp .env.example .env
   ```

4. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

5. **Jalankan local server**
   ```bash
   php artisan serve
   ```
   Aplikasi dapat diakses melalui browser pada alamat `http://127.0.0.1:8000`.

### Queue Worker dan Job Gagal

Notifikasi tugas baru dan nilai diproses melalui queue database. Jalankan
worker pada terminal terpisah setelah server aktif:

```bash
php artisan queue:work
```

Tanpa worker, job tetap berada di tabel `jobs` dan notifikasi belum terkirim.
Untuk memantau atau mencoba kembali job gagal:

```bash
php artisan queue:failed
php artisan queue:retry all
```

### 2. Pengujian Otorisasi & Anti-IDOR Otomatis
Jalankan server terlebih dahulu (`php artisan serve`), lalu pada terminal terpisah:
```bash
chmod +x scripts/test-authz.sh
./scripts/test-authz.sh
```
Skrip ini menguji:
- **RBAC:** Penolakan akses lintas peran (Dosen/Mahasiswa ke rute Admin).
- **Anti-IDOR Antar-Objek:** Dosen A ditolak dari MK Dosen B, Mahasiswa ditolak dari MK yang tidak di-enroll, dan isolasi materi.

## Menjalankan Queue Worker

Proyek menggunakan queue database: `.env.example` mengatur `QUEUE_CONNECTION=database`, dan `config/queue.php` memakai tabel `jobs` untuk koneksi tersebut. Pastikan migrasi database proyek sudah dijalankan agar tabel antrean tersedia.

Saat pengembangan lokal, buka terminal terpisah dari direktori `lms/`, lalu jalankan:

```bash
php artisan queue:work
```

Worker perlu tetap berjalan selama pengembangan agar queued jobs diproses. Jika worker tidak aktif, job dapat menumpuk di tabel `jobs`; notifikasi atau pekerjaan asinkron lain mungkin belum muncul meskipun aksi web berhasil dan tidak menampilkan error. Untuk menghentikan worker dengan aman, tekan `Ctrl+C` pada terminal worker.
Job gagal disimpan di `failed_jobs`, sedangkan batch queue menggunakan
`job_batches`. Ketiga tabel tersebut sudah dibuat oleh migrasi queue bawaan.
