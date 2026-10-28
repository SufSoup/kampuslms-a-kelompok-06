# KampusLMS - Kelompok 06 (Kelas A)

Proyek ini adalah implementasi tugas Modul 1 (Mengenal Laravel 12) untuk pengembangan purwarupa Sistem Informasi Manajemen Pembelajaran (KampusLMS).

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

Job gagal disimpan di `failed_jobs`, sedangkan batch queue menggunakan
`job_batches`. Ketiga tabel tersebut sudah dibuat oleh migrasi queue bawaan.
