# Catatan Minggu 9 Pemrograman Web

**Nama:** Muhammad Yusuf Pratama
**NIM:** 10241053
**Kelompok:** Kelompok 06 (Kelas A)
**Topik:** Lifecycle Berkas Fisik, Cleanup Event Model, dan Uji Tembus IDOR
**Branch:** `feat/w09-file-cleanup-idor-test`

---

## 1. READ — Analisis Berkas dan Sistem Penyimpanan

### 1. Di mana berkas submission dan materi disimpan?

Berkas yang diunggah disimpan pada disk `local`, yang root-nya dikonfigurasi di `lms/config/filesystems.php` ke `storage/app/private`. Database hanya menyimpan path relatif seperti `submissions/12/acak.pdf` atau `materials/acak.pdf` pada kolom `file_path`.

### 2. Apa perbedaan disk `local` dan `public`?

Disk `local` menyimpan berkas di luar web root sehingga berkas tidak dapat diakses sebagai berkas statis melalui URL. Akses dilakukan melalui controller yang memeriksa autentikasi dan otorisasi sebelum mengirimkan unduhan.

Disk `public` memakai `storage/app/public` dan dapat dipublikasikan melalui symlink `public/storage`. Disk tersebut hanya sesuai untuk berkas yang memang boleh diakses publik; submission dan materi privat tidak boleh disimpan di sana.

### 3. Mengapa nama fisik berkas dibuat acak?

`UploadedFile::store()` membuat nama fisik yang dibuat oleh aplikasi, bukan memakai nama asli dari klien. Ini mencegah nama seperti `../../berkas` mengendalikan lokasi penyimpanan. Nama asli tetap disimpan terpisah di `original_name` untuk ditampilkan atau menjadi nama unduhan.

### 4. Apa perbedaan validasi `mimes` dan `extensions`?

Rule `mimes` memeriksa MIME yang terdeteksi dari isi berkas dan membandingkannya dengan jenis berkas yang diizinkan. Rule `extensions` memeriksa ekstensi nama berkas yang diberikan pengguna. Ekstensi saja tidak cukup untuk memastikan isi berkas sesuai, sehingga validasi submission menggunakan `mimes:pdf,doc,docx`.

### 5. Bagaimana batas `max:5120` berhubungan dengan batas server?

Rule ukuran Laravel untuk berkas memakai kilobyte, sehingga `max:5120` membatasi berkas hingga sekitar 5 MB. PHP dan web server juga memiliki batas, misalnya `upload_max_filesize` dan `post_max_size`. Jika batas server lebih rendah, request dapat ditolak sebelum validasi aplikasi dapat memproses berkas.

### 6. Mengapa penghapusan cascade perlu diproses sebelum parent dihapus?

Event Eloquent `deleting` dijalankan ketika record dihapus melalui instance model. Foreign key `ON DELETE CASCADE` di database menghapus record anak langsung di database dan tidak memanggil event Eloquent pada setiap anak. Karena itu controller menghapus model berkas anak lebih dahulu, lalu menghapus model parent.

---

## 2. BREAK — Delapan Skenario Kerusakan dan Verifikasi

Pengujian destruktif dilakukan menggunakan database testing dan `Storage::fake('local')`; bukan pada berkas atau database pengguna. Skenario bertanda **batasan** menjelaskan operasi yang tidak boleh dipakai untuk model berkas.

| No. | Yang dicoba | Prediksi jika mekanisme rusak | Hasil verifikasi |
|---|---|---|---|
| 1 | Hapus record `Submission` melalui `$submission->delete()` | Berkas submission tertinggal sebagai orphan | Lulus: `FileLifecycleCleanupTest` memastikan berkas dan record terhapus. |
| 2 | Hapus record `Material` melalui `$material->delete()` | Berkas materi tertinggal sebagai orphan | Lulus: `FileLifecycleCleanupTest` memastikan berkas dan record terhapus. |
| 3 | Hapus tugas melalui halaman dosen | Cascade database menghapus submission tanpa menjalankan cleanup berkas | Lulus: controller menghapus model submission lebih dahulu; test memastikan berkas ikut terhapus. |
| 4 | Hapus tugas melalui API | Endpoint API melewati cleanup yang hanya ada di controller web | Lulus: test API memverifikasi submission dan berkas terhapus sebelum cascade. |
| 5 | Hapus mata kuliah yang memiliki materi dan submission | Cascade database meninggalkan berkas materi dan submission | Lulus: controller memproses kedua jenis anak sebelum menghapus mata kuliah. |
| 6 | Hapus record yang path fisiknya sudah tidak ada | Operasi gagal atau record tidak dapat dihapus | Lulus: event melewati penghapusan fisik jika berkas memang tidak ditemukan. |
| 7 | Hapus massal menggunakan query builder, misalnya `Submission::where(...)->delete()` | Event `deleting` tidak berjalan dan berkas dapat tertinggal | Batasan Eloquent: operasi massal tidak memuat instance model; gunakan penghapusan melalui model satu per satu. Jalur controller yang dibahas tidak memakai bulk delete. |
| 8 | Akses unduhan tanpa sesi dan unduh submission pengguna lain | Berkas terbuka tanpa autentikasi atau terjadi IDOR | Feature test memverifikasi guest diarahkan ke login dan mahasiswa lain menerima 403. Skrip cURL juga menambahkan pemeriksaan ini menggunakan akun seed khusus. |

---

## 3. FIX — Perbaikan Lifecycle Berkas

1. Model `Submission` dan `Material` menjalankan cleanup pada event `deleting` serta melempar error jika storage melaporkan kegagalan penghapusan.
2. Penghapusan manual berkas materi pada controller dihapus karena tanggung jawab tersebut kini berada di event model; cleanup berkas lama saat materi diperbarui tetap dipertahankan.
3. Controller tugas menghapus submission sebagai instance model sebelum menghapus tugas, baik pada endpoint web maupun API.
4. Controller mata kuliah menghapus materi dan submission terkait sebagai instance model sebelum foreign key cascade menghapus record turunannya.
5. Operasi berelasi diproses per 100 record agar penghapusan kelas besar tidak memuat semua anak sekaligus.
6. `scripts/test-authz.sh` kini mencari submission milik mahasiswa demo, memeriksa penolakan guest (302), serta mencoba unduhan sebagai mahasiswa kedua (403).
7. Seeder menyediakan akun `mahasiswa2@kampuslms.test` / `password` untuk uji IDOR dan memastikan mahasiswa demo memiliki submission seed yang bisa dipakai oleh skrip.

---

## 4. BUILD — Kontribusi Muhammad Yusuf

### Implementasi

- Menambahkan event cleanup untuk submission dan materi di `lms/app/Models/Submission.php` dan `lms/app/Models/Material.php`.
- Menangani cleanup berkas turunan saat tugas atau mata kuliah dihapus melalui controller web dan endpoint API yang relevan.
- Menambahkan `lms/tests/Feature/FileLifecycleCleanupTest.php`, mencakup penghapusan record langsung, penghapusan tugas web/API, penghapusan mata kuliah, dan berkas yang sudah hilang.
- Memperluas `scripts/test-authz.sh` untuk pemeriksaan download guest dan IDOR antar mahasiswa.

### Verifikasi

Perintah pengujian yang dijalankan:

```text
php artisan test --compact --filter='FileLifecycleCleanupTest|SubmissionDownloadSecurityTest|StudentSubmissionUploadTest|MaterialWebCrudTest'
30 passed (120 assertions)

php artisan test --compact
144 passed (513 assertions)

php artisan migrate:fresh --seed --force
Selesai pada APP_ENV=testing dengan SQLite in-memory

bash -n scripts/test-authz.sh
Sintaks valid

bash scripts/test-authz.sh
Semua pemeriksaan RBAC lulus; guest mendapat 302 dan mahasiswa kedua mendapat 403
```

---
