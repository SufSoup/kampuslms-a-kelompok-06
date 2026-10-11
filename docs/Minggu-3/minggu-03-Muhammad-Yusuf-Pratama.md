# Dokumentasi Praktik Minggu 3 — Database dan CRUD

## Nama: Muhammad Yusuf Pratama

## NIM: 10241053

## READ — Skema dan relasi KampusLMS

Sebelum membuat tabel, saya memetakan relasi inti dari spesifikasi. Tabel `users` menjadi induk untuk dosen, mahasiswa, pembuat tugas, pengunggah materi, dan pemberi nilai. `courses` terhubung ke dosen melalui `lecturer_id`, sementara mahasiswa terhubung ke mata kuliah lewat tabel pivot `course_user`.

| Relasi | Bentuk | Aturan penting |
|---|---|---|
| `users` → `courses` | Satu dosen mengampu banyak mata kuliah | `courses.lecturer_id` memakai `restrictOnDelete` |
| `courses` → `materials` | Satu mata kuliah memiliki banyak materi | Materi ikut terhapus saat mata kuliah dihapus |
| `courses` ↔ `users` | Mahasiswa mengambil banyak mata kuliah | Pasangan `course_id` dan `user_id` harus unik |
| `courses` → `assignments` | Satu mata kuliah memiliki banyak tugas | Tugas terikat pada mata kuliah |
| `assignments` → `submissions` | Satu tugas memiliki pengumpulan mahasiswa | Pasangan `assignment_id` dan `user_id` harus unik |
| `submissions` → `grades` | Satu pengumpulan mendapat paling banyak satu nilai | `grades.submission_id` harus unik |

`restrictOnDelete` pada dosen menjaga agar mata kuliah tidak kehilangan pengampu tanpa keputusan eksplisit. Sebaliknya, materi dan tugas merupakan bagian dari mata kuliah sehingga penghapusan mata kuliah dapat menghapus data turunannya sesuai aturan skema.

## BREAK — Dampak jika constraint dilemahkan

| Perubahan yang diuji | Dampak yang diperkirakan |
|---|---|
| Menghilangkan unique composite pada `course_user` | Satu mahasiswa dapat terdaftar lebih dari sekali di mata kuliah yang sama. |
| Mengizinkan `role` sebagai mass-assignment tanpa pembatasan | Payload tambahan dapat mencoba menaikkan hak pengguna ketika endpoint menerima atribut tersebut. |
| Mengosongkan `$fillable` lewat `$guarded = []` | Perlindungan mass assignment dinonaktifkan secara luas. |
| Mengosongkan `down()` | Perubahan skema tidak dapat dipulihkan dengan baik saat rollback atau refresh. |
| Mengganti `restrictOnDelete` dosen menjadi cascade | Menghapus dosen dapat menghapus mata kuliah dan data turunannya secara tidak sengaja. |

Pemeriksaan mass assignment perlu membedakan dua lapisan: token CSRF melindungi asal request browser, sedangkan `$fillable` dan data tervalidasi menentukan atribut yang boleh disimpan. CSRF bukan pengganti validasi maupun perlindungan kolom sensitif.

## FIX / BUILD — Catatan implementasi

Pemeriksaan migrasi pada repository menunjukkan beberapa aturan integritas sudah dipasang:

- `course_user` mempunyai unique composite `course_id` dan `user_id`, dan kedua foreign key memakai cascade.
- `courses.lecturer_id` mempunyai foreign key ke `users` dengan `restrictOnDelete`.
- `grades.submission_id` dan pasangan pada `submissions` menggunakan constraint unik.
- Migrasi inti yang diperiksa memiliki `down()` yang menghapus tabel terkait.
- Model `User` memakai daftar `$fillable` eksplisit yang tidak memuat `role`, dan casting password didefinisikan melalui method `casts()`.

Ada detail implementasi yang perlu dicocokkan dengan spesifikasi sebelum menyatakan skema sepenuhnya identik: kolom `courses.description` saat ini tidak nullable, dan `sks` didefinisikan sebagai `tinyInteger`. Spesifikasi tugas menyebut deskripsi nullable dan tipe SKS unsigned. Perbedaan ini sebaiknya dibahas dan disepakati sebelum mengubah skema inti.

## Checkpoint

- Unique composite menegakkan aturan bisnis langsung di database sehingga request bersamaan pun tidak membuat pendaftaran ganda.
- `restrictOnDelete` tepat untuk mencegah penghapusan dosen yang masih menjadi pengampu; cascade cocok untuk data anak yang masa hidupnya bergantung pada mata kuliah.
- Kolom hak akses tidak boleh dibuka untuk mass assignment.
- Migrasi perlu diuji naik dan turun, sedangkan seeder harus menghasilkan relasi yang konsisten.

## Pemeriksaan yang dapat dijalankan

Dari direktori `lms/`, gunakan `php artisan migrate:status` untuk melihat status migrasi dan `php artisan test` untuk menjalankan pengujian. Sebelum menjalankan migrasi yang mereset database, pastikan koneksi yang dipakai adalah database pengujian atau database lokal yang aman.
