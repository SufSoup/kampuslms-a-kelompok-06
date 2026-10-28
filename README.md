# KampusLMS — Kelompok 06 (Kelas A)

Sistem Informasi Manajemen Pembelajaran (*Learning Management System*) berbasis **Laravel 12**, dibangun secara kolaboratif untuk mata kuliah **Pemrograman Web (SI2514024)**, Semester Ganjil 2026/2027.

---

## Tim Pengembang (Kelompok 06 — Kelas A)

| Nama Lengkap | NIM | Peran Utama |
| :--- | :---: | :--- |
| **Muhammad Farraz Kanz** | `10241047` | CRUD Materi, CoursePolicy, MaterialPolicy, Dashboard Peran |
| **Muhammad Ikhsyan** | `10241049` | Autentikasi, Session Hardening, Fitur 17 (LearningResourcePolicy), Test Auth |
| **Muhammad Syahid Ridho** | `10241051` | CRUD Tugas, AssignmentPolicy, SubmissionPolicy, GradePolicy, Authz Test |
| **Muhammad Yusuf Pratama** | `10241053` | Enrollment Mahasiswa, Query Filtering Mata Kuliah |

---

## Fitur Diferensiasi: Fitur 17

Kelompok 06 mengimplementasikan **Fitur 17: Kurasi Sumber Belajar Tambahan & Penanda Baca**:
- **Kurasi Referensi:** Dosen pengampu dan Admin dapat mengkurasi referensi belajar tambahan (artikel, video, buku, dokumentasi) per mata kuliah.
- **Penanda Baca Anti-IDOR:** Mahasiswa terdaftar dapat menandai baca (*toggle mark read*). Rekam baca diikat mutlak ke `auth()->id()` sehingga tidak dapat memanipulasi status baca mahasiswa lain.

---

## Akun Demo Pengujian

Aplikasi telah dilengkapi seeder akun untuk setiap peran dengan kata sandi default `password`:

| Peran | Email | Kata Sandi | Keterangan |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@kampuslms.test` | `password` | Manajemen MK, Pengguna, dan Enrollment |
| **Dosen 1** | `dosen@kampuslms.test` | `password` | Pengampu Mata Kuliah 1 (Pemrograman Web) |
| **Dosen 2** | `budi.dosen@kampuslms.test` | `password` | Pengampu Mata Kuliah 2 (Basis Data) |
| **Mahasiswa** | `mahasiswa@kampuslms.test` | `password` | Mahasiswa terdaftar di kelas |

---

## Panduan Instalasi Lokal

Seluruh kode aplikasi berada di subdirektori `lms/`.

### 1. Clone Repositori
```bash
git clone https://github.com/SufSoup/kampuslms-a-kelompok-06.git
cd kampuslms-a-kelompok-06/lms
```

### 2. Instalasi Dependensi
```bash
# Dependensi PHP (Kompatibel PHP 8.3+)
composer install

# Dependensi Frontend
npm install
npm run build
```

### 3. Konfigurasi Environment & Database
```bash
cp .env.example .env
php artisan key:generate

# Migrasi dan seeding database lengkap
php artisan migrate:fresh --seed
```

### 4. Menjalankan Server
```bash
php artisan serve
```
Buka browser pada alamat [http://127.0.0.1:8000](http://127.0.0.1:8000).

---

## Pengujian & Verifikasi Keamanan

### 1. Automated Test Suite (Feature & Unit Tests)
```bash
cd lms
php artisan test
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
