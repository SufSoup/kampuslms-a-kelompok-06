# Minggu 11 Pemrograman Web

## Identitas

- **Nama:** Muhammad Syahid Ridho
- **NIM:** 10241051
- **Kelompok:** Kelompok 06 (Kelas A)
- **Topik:** Dashboard Caching dan Invalidasi Otomatis
- **Branch:** `syahid`

## READ — Analisis Implementasi

Dashboard admin menggunakan empat query untuk menghitung jumlah pengguna, mata kuliah, materi, dan tugas. Scope `User` tetap mengecualikan pengguna yang telah dihapus secara soft delete. Dashboard dosen mengambil mata kuliah yang diampu dengan menggunakan `withCount()` untuk menghitung jumlah mahasiswa, materi, tugas, dan submission yang belum dinilai.

Perhitungan submission yang belum dinilai menggunakan relasi `Course::submissions()` (`hasManyThrough` ke Submission melalui Assignment) dengan kondisi `whereDoesntHave('grade')`. Dengan demikian, submission yang sudah memiliki grade tidak ikut dihitung. Agregasi dilakukan melalui query mata kuliah sehingga tidak memerlukan query tambahan untuk setiap mata kuliah.

Statistik admin dan koleksi mata kuliah dosen menggunakan `Cache::remember()` dengan masa berlaku selama 10 menit. Key cache dosen memuat ID dosen (`dashboard:lecturer:{id}:courses`) agar data setiap dosen terpisah. Sementara itu, dashboard mahasiswa tidak menggunakan cache dan tetap menjalankan query personal yang sudah ada.

## BREAK — 7 Eksperimen

Eksperimen dilakukan melalui feature test pada `DashboardCachingTest`. Pengujian difokuskan pada perilaku cache, agregasi data, dan invalidasi otomatis. Tidak dilakukan benchmark waktu maupun beban, sehingga tidak ada angka performa sebelum dan sesudah yang dilaporkan.

1. **Submission tanpa grade:** Membuat dua submission pada mata kuliah yang sama, kemudian memberikan grade pada salah satunya. Dashboard menampilkan satu submission yang belum dinilai.
2. **Agregasi tanpa N+1:** Mencatat query dashboard dan memastikan ekspresi perhitungan `ungraded_submissions_count` terdapat pada query mata kuliah.
3. **Isolasi cache dosen:** Menguji dua dosen yang memuat dashboard masing-masing. Setiap dosen hanya memperoleh mata kuliah miliknya dan menggunakan key cache berbeda berdasarkan ID.
4. **Siklus submission:** Menguji proses create, update, dan delete submission untuk memastikan cache dosen pemilik course terkait dihapus.
5. **Perubahan relasi course:** Menguji pemindahan submission ke assignment milik dosen lain serta perubahan `course_id` assignment untuk memastikan cache dosen lama dan baru dihapus.
6. **Siklus grade:** Menguji create, update, delete grade, serta pemindahan grade ke submission dosen lain untuk memastikan cache yang terdampak diinvalidasi.
7. **Regresi dashboard:** Memastikan statistik admin tetap menggunakan cache dan dashboard mahasiswa tetap berjalan tanpa menyimpan nilai maupun feedback individual ke dalam cache.

## FIX — Strategi Invalidasi

- Event `Submission::saved` dan `Submission::deleted` digunakan untuk menghapus cache berdasarkan assignment sebelum dan sesudah perubahan. Mekanisme ini menangani proses create, update, delete, serta perubahan `assignment_id` apabila submission berpindah relasi.
- Event `Grade::saved` dan `Grade::deleted` digunakan untuk menghapus cache dosen berdasarkan submission sebelumnya dan submission saat ini. Perubahan `submission_id` juga menangani invalidasi cache dari kedua pemilik course.
- Event `Assignment::saved` digunakan untuk menginvalidasi cache dosen pemilik course lama dan baru, termasuk ketika terjadi perubahan `course_id`. Penghapusan assignment juga menghapus cache dosen pemilik course terkait.
- Invalidasi cache yang sudah diterapkan pada model `Course`, `Material`, `User`, dan pivot `CourseEnrollment` tetap dipertahankan.
- Invalidasi hanya dilakukan terhadap cache daftar course beserta agregatnya. Nilai individual, feedback dosen, dan status submission mahasiswa tidak disimpan dalam cache dashboard.

## BUILD — Implementasi dan Verifikasi

Implementasi dilakukan dengan menambahkan relasi `Course::submissions()` tanpa membuat migration atau mengubah struktur database. Dashboard dosen menggunakan agregasi `withCount()` untuk menghitung `ungraded_submissions_count`, kemudian menampilkan jumlah submission yang belum dinilai pada kartu mata kuliah.

Callback penghapusan file pada `Submission::booted()` tetap dipertahankan dan digabungkan dengan mekanisme invalidasi cache. Proses grading yang telah dikembangkan pada Week 10 tidak diubah.

Cache statistik admin dan dashboard dosen menggunakan TTL 10 menit. Key cache dosen dibuat berdasarkan ID dosen untuk menjaga isolasi data antarpengguna.

`DashboardCachingTest` mencakup tujuh feature test menggunakan `RefreshDatabase`. Pengujian meliputi jumlah submission yang belum dinilai, agregasi query, isolasi cache dosen, invalidasi perubahan submission dan grade, serta regresi dashboard admin dan mahasiswa.

### Hasil Pengujian

| Perintah | Hasil |
|---|---|
| `php artisan test --filter=DashboardCachingTest` | 7 passed (102 assertions) |
| `php artisan test --filter=DashboardTest` | 3 passed (21 assertions) |
| `php artisan test --filter=PerformanceEagerLoadingTest` | 6 passed (8 assertions) |
| `php artisan test` | 189 passed (755 assertions) |
| Laravel Pint pada file PHP yang berubah | Lulus |
| `git diff --check` | Lulus |

Tidak dilakukan benchmark waktu maupun beban secara khusus. Tidak ada migration atau perubahan struktur database dalam implementasi ini.

### Ringkasan File

| File | Tindakan |
|---|---|
| `lms/app/Http/Controllers/DashboardController.php` | Menambahkan caching statistik admin dan agregasi jumlah mahasiswa, materi, tugas, serta submission yang belum dinilai pada dashboard dosen. |
| `lms/app/Models/Assignment.php` | Menambahkan invalidasi cache ketika tugas dibuat, diperbarui, atau dihapus, termasuk ketika tugas berpindah course. |
| `lms/app/Models/Course.php` | Menambahkan relasi `hasManyThrough` ke submission serta invalidasi cache ketika data course berubah. |
| `lms/app/Models/Grade.php` | Menambahkan invalidasi cache ketika grade dibuat, diperbarui, dihapus, atau dipindahkan ke submission lain. |
| `lms/app/Models/Material.php` | Menambahkan invalidasi cache ketika materi berubah. |
| `lms/app/Models/Submission.php` | Menambahkan invalidasi cache pada penyimpanan dan penghapusan tanpa mengubah callback penghapusan file. |
| `lms/app/Models/User.php` | Menambahkan invalidasi cache ketika data pengguna yang memengaruhi dashboard berubah. |
| `lms/app/Models/CourseEnrollment.php` | Menangani invalidasi cache ketika data pendaftaran mahasiswa pada course berubah. |
| `lms/app/Support/DashboardCache.php` | Menyediakan helper untuk key cache, TTL, dan invalidasi cache admin serta dosen. |
| `lms/resources/views/dashboard.blade.php` | Menampilkan jumlah submission yang belum dinilai pada kartu course dashboard dosen. |
| `lms/tests/Feature/DashboardCachingTest.php` | Menguji caching dashboard, isolasi cache dosen, jumlah submission belum dinilai, dan invalidasi otomatis. |
| `docs/Minggu-11/minggu-11-syahid.md` | Mendokumentasikan analisis READ, tujuh eksperimen BREAK, strategi FIX, implementasi BUILD, dan hasil pengujian. |