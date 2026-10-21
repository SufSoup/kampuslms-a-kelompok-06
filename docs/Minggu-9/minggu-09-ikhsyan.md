# Notes Minggu 9 Pemrograman Web

**Nama:** Muhammad Ikhsyan  
**NIM:** 10241049  
**Kelompok:** Kelompok 06 (Kelas A)  
**Topik:** Pengelolaan Unggahan Berkas dan Data Dinamis (Disk Privat, Anti-Path Traversal, dan Form Request)  
**Branch:** `feat/w09-submission-upload` / `ikhsyan`

---

## 1. READ — Analisis Berkas & Sistem Penyimpanan (Filesystem)

### 1. Temukan berkas fisiknya di sistem berkas. Path lengkapnya apa?
**Jawaban:**  
Berkas submission tugas mahasiswa disimpan pada disk privat `local` menggunakan Laravel Storage:
```
storage/app/private/submissions/{assignment_id}/{hash_acak}.pdf
```
Secara absolut di lingkungan server pengembang, path lengkapnya adalah:
`/Users/muhammadikhsyan/Documents/Kuliah/Akademik/Semester 5/Proweb/kampuslms-a-kelompok-06/lms/storage/app/private/submissions/1/aBcD123xyz.pdf`.

### 2. Buka baris database yang bersangkutan. Bandingkan `file_path` dengan lokasi fisiknya.
**Jawaban:**  
Pada tabel `submissions`, kolom `file_path` hanya mencatat path relatif terhadap *root* disk `local`:
- Nilai di basis data (`file_path`): `submissions/1/aBcD123xyz.pdf`
- Lokasi fisik sebenarnya: `storage/app/private/submissions/1/aBcD123xyz.pdf`
- Nilai `original_name`: `Tugas_Praktikum_Muhammad_Ikhsyan.pdf`

Pemisahan ini memisahkan representasi metadata pengguna dari letak fisik berkas di server, sehingga struktur internal filesystem tidak bocor ke klien dan aman dari manipulasi path.

### 3. Coba akses berkas itu langsung lewat browser dengan menebak URL-nya. Berhasil atau tidak? Kenapa?
**Jawaban:**  
**Gagal (HTTP 404 Not Found)**.  
Direktori `storage/app/private` terletak di luar direktori web root (`public/`). Web server (Apache/Nginx/PHP Built-in Server) dikonfigurasi untuk hanya melayani berkas statis yang berada di dalam folder `public/`. Karena berkas privat tidak berada di `public/` dan tidak di-symlink, request langsung seperti `http://localhost:8000/storage/app/private/submissions/1/aBcD123xyz.pdf` langsung ditolak atau dianggap sebagai route Laravel yang tidak terdaftar (404). Berkas privat hanya dapat diakses melalui controller yang mengembalikan `Storage::download()` setelah melalui autentikasi dan otorisasi ketat.

### 4. Buka `config/filesystems.php`. Identifikasi disk `local` dan `public` — apa persis bedanya?
**Jawaban:**  
Berdasarkan `config/filesystems.php` di Laravel 12:
- **Disk `local`:** Root direktori mengarah ke `storage_path('app/private')`. Berkas di disk ini tidak memiliki URL publik dan tidak dapat diakses langsung oleh peramban tanpa perantara script backend. Digunakan untuk data sensitif seperti tugas mahasiswa, materi kuliah berhak cipta, dan dokumen transaksi.
- **Disk `public`:** Root direktori mengarah ke `storage_path('app/public')` dengan `'url' => env('APP_URL').'/storage'`. Berkas di disk ini ditujukan untuk aset statis yang boleh diakses siapa saja (misal foto avatar publik, logo aplikasi) setelah dibuat tautan simbolis (`storage:link`).

### 5. Jalankan `php artisan storage:link`. Apa yang dibuatnya? Kenapa tugas/materi Anda TIDAK BOLEH ada di sana?
**Jawaban:**  
Perintah `php artisan storage:link` membuat sebuah *symbolic link* (symlink) dari `public/storage` menuju `storage/app/public`.  
**Alasan Berkas Tugas Dilarang di sana:**  
Jika berkas tugas ditaruh di `storage/app/public`, web server akan menyajikan berkas tersebut secara langsung (*direct static file serving*) melewati Laravel sepenuhnya. Akibatnya, siapapun di internet (termasuk tanpa login / tamu tak dikenal) dapat mengunduh berkas tugas mahasiswa lain hanya dengan mengetahui atau menebak URL-nya, tanpa pernah melewati pemeriksaan `Auth` maupun `Gate::authorize()`.

---

## 2. BREAK — Delapan Kerusakan Berkas (Tabel Eksperimen & Analisis)

| # | Yang Dicoba | Prediksi | Hasil Sebenarnya |
|---|-------------|----------|-------------------|
| **1** | Pindahkan berkas submission ke `storage/app/public`, akses URL lewat incognito browser tanpa login | Berkas dapat dibuka langsung oleh publik tanpa proses login | **TERBUKTI KERUSAKAN FATAL:** URL `http://127.0.0.1:8000/storage/submissions/jawaban.pdf` dapat diunduh tanpa sesi login sama sekali. Seluruh tugas kelas bocor ke publik. |
| **2** | Hapus pemanggilan `Gate::authorize('view', $submission)` dari endpoint download, minta Mahasiswa B mengunduh submission milik Mahasiswa A via ID URL | Terjadi IDOR (Insecure Direct Object Reference) antar mahasiswa | **TERBUKTI:** Mahasiswa B berhasil mengunduh berkas jawaban Mahasiswa A hanya dengan mengganti parameter ID submission pada URL. |
| **3** | Simpan berkas menggunakan `storeAs()` memakai nama asli klien, lalu unggah berkas bernama `../../test.txt` | File tersimpan di luar direktori tujuan (Path Traversal Vulnerability) | **TERBUKTI:** Berkas berhasil menembus hirarki folder dan tertulis di direktori induk. Hal ini membuktikan bahaya mempercayai `getClientOriginalName()` untuk penamaan fisik. Solusinya wajib memakai nama acak `$file->store()`. |
| **4** | Unggah berkas PHP `shell.php` yang diubah namanya menjadi `shell.pdf` dengan validasi `extensions:pdf` vs `mimes:pdf` | Validasi `extensions` meloloskan file PHP berbahaya, sedangkan `mimes` menolaknya berdasarkan *magic bytes* | **TERBUKTI:** Rule `extensions:pdf` hanya memeriksa akhiran string nama file sehingga backdoor PHP lolos. Sedangkan `mimes:pdf` membaca *MIME content-type/magic bytes* berkas sebenarnya dan langsung melempar pesan validasi gagal (HTTP 422). |
| **5** | Ubah rule validasi dari `max:5120` menjadi `max:5`, lalu unggah berkas berukuran 1 MB | File 1 MB ditolak karena satuan validasi Laravel adalah Kilobyte (KB) | **TERBUKTI:** Unggahan ditolak dengan pesan bahwa berkas tidak boleh melebihi 5 kilobyte. Nilai `max:5120` tepat bernilai 5 MB. |
| **6** | Set `upload_max_filesize = 1M` di `php.ini`, lalu unggah berkas 3 MB dengan validasi Laravel `max:5120` | Server membatalkan request sebelum kode validasi Form Request Laravel berjalan | **TERBUKTI:** Request HTTP POST tiba dengan payload kosong (`$_FILES` kosong atau berisi error code `UPLOAD_ERR_INI_SIZE`), menghasilkan kegagalan tanpa pesan validasi yang jelas dari Laravel. Batasan PHP/server mengalahkan aturan aplikasi. |
| **7** | Hapus `updateOrCreate`, ubah menjadi `Submission::create()`, lalu kumpulkan tugas dua kali untuk assignment yang sama | Terjadi error fatal database violation karena composite key `(assignment_id, user_id)` bersifat UNIQUE | **TERBUKTI:** Laravel melempar `QueryException: Integrity constraint violation (UNIQUE constraint failed: submissions.assignment_id, submissions.user_id)`. Penggunaan `updateOrCreate` wajib untuk menjamin idempotensi pengumpulan ulang. |
| **8** | Hapus satu baris data submission langsung melalui query SQL `DELETE FROM submissions WHERE id = 1;`, lalu periksa folder `storage/app/private/` | Berkas fisik tertinggal di disk tanpa referensi database (*orphan file*) | **TERBUKTI:** Berkas fisik tetap berada di hard drive dan menjadi sampah storage selamanya karena penghapusan langsung via SQL bypass event Eloquent `deleting`. |

---

## 3. FIX — Perbaikan Repo Cacat Minggu 9 (`w09`)

Tujuh cacat utama pada kode awal diperbaiki dengan standar Laravel 12:

1. **Penyimpanan Publik ke Privat:**
   - *Cacat:* Berkas disimpan ke disk `'public'`.
   - *Perbaikan:* Dialihkan ke disk privat `'local'` (`$file->store("submissions/{$assignment->id}", 'local')`) yang tersimpan di `storage/app/private/`.
2. **Pemberian Proteksi Otorisasi Anti-IDOR:**
   - *Cacat:* Endpoint download tidak memeriksa kepemilikan berkas.
   - *Perbaikan:* Menambahkan `Gate::authorize('view', $submission)` di `SubmissionController@download` dan `Gate::authorize('create', [Submission::class, $assignment])` di `SubmissionController@store`.
3. **Pemberantasan Path Traversal:**
   - *Cacat:* Menggunakan `$file->storeAs('submissions', $file->getClientOriginalName())`.
   - *Perbaikan:* Menggunakan `$file->store(...)` yang secara otomatis meng-generate nama acak sha1/uuid, sedangkan nama asli disimpan terpisah di kolom `original_name`.
4. **Validasi MIME Content-Type Ketat:**
   - *Cacat:* Validasi hanya menggunakan rule `extensions:pdf,docx` yang rentan manipulasi ekstensi palsu.
   - *Perbaikan:* Menggunakan Form Request `StoreSubmissionRequest` dengan rule `mimes:pdf,doc,docx` untuk memvalidasi isi asli berkas (*magic bytes*).
5. **Koreksi Satuan Ukuran Berkas:**
   - *Cacat:* Menggunakan angka salah anggapan (misal mengira satuan bytes atau megabytes mentah).
   - *Perbaikan:* Menggunakan `max:5120` (5120 KB = 5 Megabytes) sesuai spesifikasi Laravel.
6. **Pencegahan Berkas Yatim Saat Kumpul Ulang:**
   - *Cacat:* Mengunggah ulang berkas baru meninggalkan berkas lama menumpuk di storage.
   - *Perbaikan:* Memeriksa berkas lama pengguna pada tugas terkait, jika ada maka dihapus dari storage fisik (`Storage::disk('local')->delete(...)`) sebelum menyimpan berkas baru via `updateOrCreate`.
7. **Penegakan Server-Side Authorization:**
   - *Cacat:* Pengecekan hanya di Blade UI dengan menyembunyikan tombol, sehingga bypass via Postman/cURL berhasil.
   - *Perbaikan:* Otorisasi dipusatkan di `SubmissionPolicy` dan dipanggil di Controller menggunakan `Gate::authorize()`.

---

## 4. BUILD — Kontribusi Fitur Muhammad Ikhsyan (`10241049`)

### 1. Form Request: `app/Http/Requests/StoreSubmissionRequest.php`
- Otorisasi request mengunci role pengguna hanya untuk `'mahasiswa'`.
- Validasi berkas: wajib, berupa file, format aman `mimes:pdf,doc,docx`, ukuran maksimal `max:5120` (5 MB).
- Validasi catatan: opsional, string, maksimal 1000 karakter.
- Pesan validasi ramah pengguna dalam Bahasa Indonesia.

### 2. Controller Pengumpulan: `app/Http/Controllers/SubmissionController.php`
- `store(StoreSubmissionRequest $request, Assignment $assignment)`:
  - Memeriksa otorisasi via `Gate::authorize('create', [Submission::class, $assignment])`.
  - Memeriksa batas waktu: jika melewati `due_at` dan `$assignment->allow_late === false`, pengumpulan ditolak dengan flash validation error.
  - Menghitung flag keterlambatan `is_late` di sisi server: `now()->gt($assignment->due_at)`.
  - Menyimpan berkas ke disk privat `local` pada folder `submissions/{assignment_id}/`.
  - Membersihkan berkas fisik lama sebelum `updateOrCreate` dijalankan agar tidak terjadi duplikasi baris atau penumpukan berkas yatim di disk.
- `download(Submission $submission)`:
  - Memeriksa izin via `Gate::authorize('view', $submission)`.
  - Mengirim berkas privat secara aman melalui `Storage::disk('local')->download()`.

### 3. Antarmuka Pengumpulan Mahasiswa: `resources/views/assignments/show.blade.php`
- Menampilkan status pengumpulan mahasiswa (Sudah Mengumpulkan / Belum Mengumpulkan).
- Menampilkan detail berkas yang sudah diunggah: nama asli berkas, ukuran format KB, waktu kirim, badge tepat waktu atau terlambat, serta tombol unduh berkas sendiri.
- Form interaktif dengan validasi CSRF, atribut input `accept=".pdf,.doc,.docx"`, textarea catatan, dan tombol dinamis ("Kirim Tugas" atau "Kumpulkan Ulang").
- Peringatan jika pengumpulan telah ditutup saat melewati deadline dan `allow_late` bernilai `false`.

### 4. Pengujian Otomatis (Feature Test): `tests/Feature/StudentSubmissionUploadTest.php`
Telah dibuat 9 skenario pengujian komprehensif yang berjalan **100% lolos (PASS)**:
1. `test_enrolled_student_can_upload_pdf_submission`: Mahasiswa terdaftar berhasil mengunggah berkas PDF dan tersimpan di database serta disk lokal.
2. `test_resubmission_updates_existing_record_and_replaces_physical_file`: Pengumpulan ulang memperbarui data tanpa duplikasi baris dan menghapus berkas fisik lama dari disk.
3. `test_non_enrolled_student_cannot_upload_submission`: Mahasiswa yang tidak mengambil MK ditolak (HTTP 403).
4. `test_draft_assignment_cannot_receive_submissions`: Tugas berstatus draft tidak menerima submission (HTTP 403).
5. `test_disallowed_file_extension_or_mime_is_rejected`: Ekstensi/MIME terlarang (seperti PHP/Script) ditolak validasi (HTTP 422).
6. `test_file_exceeding_max_size_is_rejected`: Berkas berukuran lebih dari 5 MB ditolak validasi.
7. `test_late_submission_is_rejected_when_allow_late_is_false`: Pengumpulan lewat deadline ditolak jika dosen melarang keterlambatan.
8. `test_late_submission_is_accepted_and_marked_late_when_allow_late_is_true`: Pengumpulan lewat deadline diterima dan ditandai `is_late = true` jika dosen mengizinkan keterlambatan.
9. `test_student_owner_and_lecturer_can_download_submission`: Pemilik berkas dan dosen pengampu dapat mengunduh berkas, sementara mahasiswa lain ditolak (HTTP 403).
