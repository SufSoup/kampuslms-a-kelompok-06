# Catatan Praktikum Minggu 14 — Debugging dan Testing

- **Nama:** Muhammad Yusuf Pratama
- **NIM:** 10241053
- **Kelompok:** Kelompok 06 (Kelas A)
- **Branch kerja:** `dev`
- **Topik:** Audit queue, notifikasi, enrollment, dan file lifecycle; debugging; checkpoint

## Status instruksi dan bukti

Rujukan resmi adalah [modul Minggu 14](../../Materi/06-modul-minggu-13-16.md), bagian 14.1–14.4. Catatan ini memisahkan test teknis yang dijalankan dengan bantuan assistant dari latihan individu yang tetap perlu dilakukan Yusuf sendiri. Hasil assistant-run bukan klaim bahwa Yusuf telah menyelesaikan praktikum personal.

## READ — Audit queue, notifikasi, enrollment, dan file lifecycle

Audit dilakukan pada [QueueReliabilityTest](../../lms/tests/Feature/QueueReliabilityTest.php), [NotificationEngineTest](../../lms/tests/Feature/NotificationEngineTest.php), [FileLifecycleCleanupTest](../../lms/tests/Feature/FileLifecycleCleanupTest.php), [CourseEnrollmentTest](../../lms/tests/Feature/CourseEnrollmentTest.php), dan [PaginationAuditTest](../../lms/tests/Feature/PaginationAuditTest.php), beserta model dan controller yang terkait.

- `QueueReliabilityTest` memakai `Queue::fake()` saat memeriksa dispatch listener dan `Notification::fake()` saat memeriksa penerima. Test kegagalan job menggunakan koneksi queue database di environment test dan memastikan record masuk ke `failed_jobs`.
- `NotificationEngineTest` memeriksa record notifikasi, penerima, dan payload melalui database. Test ini tidak memakai `Notification::fake()` karena yang diuji memang persistensi notifikasi.
- `FileLifecycleCleanupTest::setUp()` menggunakan `Storage::fake('local')`. Test memeriksa file dan record database untuk penghapusan model, assignment lewat Web/API, dan course lewat Web.
- Controller penghapusan assignment menghapus submission melalui Eloquent sebelum menghapus assignment. Penghapusan course juga menghapus model materi dan submission terlebih dahulu; event `deleting` model membersihkan file sebelum cascade database berjalan.
- Test enrollment dan pagination tidak membuat notifikasi atau file. Keduanya memakai `RefreshDatabase` dan membuat fixture masing-masing.

### Test yang dijalankan

Semua perintah berikut dijalankan dari direktori `lms/`.

- `php artisan test --filter='QueueReliabilityTest|NotificationEngineTest|FileLifecycleCleanupTest|CourseEnrollmentTest|PaginationAuditTest|StudentSubmissionUploadTest|GradingApiTest'` — **48 passed (156 assertions)**.
- `php artisan test --filter='test_index_migration_is_present_and_rollback_is_reversible|test_student_response_and_view_data_do_not_expose_other_reader_identity|test_late_submission_is_rejected_when_allow_late_is_false|test_student_cannot_submit_after_deadline_when_late_submissions_are_disabled'` — **4 passed (24 assertions)**.
- `php artisan test --testsuite=Feature` — **210 passed (864 assertions)**.
- `php artisan test` — **211 passed (865 assertions)**.

Suite lengkap berjalan menggunakan konfigurasi PHPUnit `sqlite` in-memory. [Workflow CI](../../.github/workflows/ci.yml) memiliki langkah `php artisan test`; status run terbaru di GitHub Actions tidak diperiksa dalam sesi ini.

### READ — Empat latihan di modul

1. **Jalankan test dan catat hasil:** full suite lulus **211 test (865 assertions)**.
2. **Buat satu test sengaja gagal:** assertion sementara ditambahkan ke `ExampleTest::test_the_application_returns_a_successful_response`. Test gagal pada [ExampleTest.php](../../lms/tests/Feature/ExampleTest.php) baris 21; assertion kemudian dihapus dan file dipulihkan.
3. **Baca log dan telusuri ke `app/`:** pada [laravel.log](../../lms/storage/logs/laravel.log) ditemukan exception historis `CourseController::show()` menerima argumen bertipe string, dengan lokasi aplikasi di [CourseController.php](../../lms/app/Http/Controllers/CourseController.php) baris 23. Catatan ini berasal dari September dan bukan klaim bug aktif pada kode sekarang.
4. **Pasang `DB::listen()` dan amati halaman rekap nilai:** listener sementara di `AppServiceProvider` menangkap query saat test membuka halaman rekap dosen. Log memperlihatkan query hitung dan daftar submission, lalu eager loading mahasiswa, assignment, grade/grader, serta hitungan notifikasi. Fixture hanya memiliki satu submission, jadi pengamatan ini tidak sendirian membuktikan ketiadaan N+1. Test [`PerformanceEagerLoadingTest::test_course_grades_page_has_no_lazy_loading_violations`](../../lms/tests/Feature/PerformanceEagerLoadingTest.php) lulus (**1 test, 1 assertion**). Listener dan file log sementara sudah dihapus.

## Uji mutasi — Bukti test menangkap regresi

Mutasi hanya sementara dan dikembalikan setelah test. Tidak ada perubahan permanen pada model atau listener.

| Mutasi sementara | Hasil ketika proteksi dihilangkan | Setelah dipulihkan |
|---|---|---|
| Menghapus event `Submission::deleting` di [Submission.php](../../lms/app/Models/Submission.php), lalu menjalankan `php artisan test --filter='test_deleting_course_through_web_cleans_material_and_submission_files_before_cascade'` | **Gagal sesuai harapan:** file `submissions/1/jawaban.pdf` tertinggal; assertion gagal di [FileLifecycleCleanupTest.php](../../lms/tests/Feature/FileLifecycleCleanupTest.php) baris 124. | Test yang sama lulus (**1 test, 6 assertions**). |
| Menghapus sementara `implements ShouldQueue` pada [SendAssignmentNotification.php](../../lms/app/Listeners/SendAssignmentNotification.php), lalu menjalankan `php artisan test --filter='QueueReliabilityTest::test_listener_retry_configuration_is_explicit'` | **Gagal sesuai harapan:** listener tidak lagi merupakan instance `ShouldQueue`; assertion gagal di [QueueReliabilityTest.php](../../lms/tests/Feature/QueueReliabilityTest.php) baris 136. | Implementasi dipulihkan; full suite sesudahnya lulus. |

Kedua eksperimen di atas adalah uji mutasi untuk jobdesk. Karena modul meminta latihan individu, bukti tersebut tidak menggantikan demonstrasi langsung oleh Yusuf.

## BUILD — Studi kasus untuk `docs/debugging.md`

Finalisasi [dokumentasi debugging](../debugging.md) mencatat tiga kasus berbasis riwayat kode dan test:

1. **Indeks foreign key saat rollback migrasi indeks performa.** Commit `8c6f228` memperbaiki perlindungan indeks `courses.lecturer_id`; regresi dijaga oleh `PerformanceIndexTest::test_index_migration_is_present_and_rollback_is_reversible`.
2. **Paritas deadline Web/API dan privasi identitas pembaca.** Jalur API menolak submission yang melewati deadline jika `allow_late` nonaktif; halaman course mahasiswa hanya memuat identitas pembaca yang sesuai. Test Web/API dan `LearningResourceWebTest::test_student_response_and_view_data_do_not_expose_other_reader_identity` tersedia.
3. **File privat yatim akibat penghapusan berantai.** Riwayat commit `1698ea0` menambahkan cleanup file dan test lifecycle. Mutasi event `Submission::deleting` membuat test penghapusan course gagal karena file submission tertinggal.

Test terarah untuk bug #1 dan #2 lulus bersama: **4 passed (24 assertions)**. Test cleanup course juga lulus kembali setelah event dipulihkan.

Pemeriksaan BUILD tambahan: suite memiliki **210 feature test** yang lulus. Tabel titik rawan di [docs/keamanan.md](../keamanan.md) memiliki kolom `Test` pada delapan baris dan masing-masing mencantumkan test regresi.

## BREAK — Status tujuh latihan

Berikut status terhadap tujuh skenario resmi di [modul Minggu 14 §14.3](../../Materi/06-modul-minggu-13-16.md).

| # | Skenario yang dirujuk | Status pada sesi ini |
|---|---|---|
| 1 | Buat test yang hanya memeriksa `assertStatus(200)`, lalu kosongkan controller | **Dilakukan sementara:** ditambahkan test admin yang hanya memeriksa status, lalu method `CourseController::index` diganti dengan respons kosong. Test tetap lulus (**1 test, 1 assertion**). Test dan controller dipulihkan. |
| 2 | Hapus `Gate::authorize()` dari satu controller dan jalankan test | **Dilakukan sementara:** menghapus Gate di `CourseController::show` membuat `SecurityMatrixCoverageTest::test_unenrolled_student_cannot_view_another_course_detail` gagal: mengharapkan 403, menerima 200. Gate dipulihkan. |
| 3 | Hapus `RefreshDatabase`, jalankan seluruh test dua kali | **Dilakukan dua kali** tanpa trait pada `CourseEnrollmentTest`, memakai SQLite sementara berbasis file agar skema tersedia. Kedua run menghasilkan **4 failed, 207 passed (850 assertions)**: data antar-test tertinggal dan memengaruhi assertion enrollment, dashboard, dan demo seeder. Run awal dengan SQLite in-memory tanpa migrasi lebih dulu menghasilkan lima kegagalan `no such table: users`. Trait dipulihkan dan database sementara dihapus. |
| 4 | Tulis test yang bergantung pada data seeder, jalankan di CI | **Disimulasikan lokal:** test sementara mengasumsikan user `admin@kampuslms.test` sudah di-seed, tetapi `RefreshDatabase` memberi database bersih; test gagal karena tabel `users` kosong (**1 failure, 1 assertion**). Ini bukan run GitHub Actions. Test sementara dihapus. |
| 5 | Hapus `Storage::fake()`, jalankan test unggah | **Dilakukan sementara:** test unggah lulus (**1 test, 3 assertions**) dan membuat satu file fisik di `storage/app/private/submissions/`. File hasil test dihapus secara spesifik dan `Storage::fake()` dipulihkan. |
| 6 | Rusak satu migrasi `down()`, jalankan test | **Dilakukan sementara:** nama indeks di `down()` diganti dengan indeks yang tidak ada; `PerformanceIndexTest::test_index_migration_is_present_and_rollback_is_reversible` gagal dengan `no such index` (**1 failure, 4 assertions**). Migrasi dipulihkan. |
| 7 | Tinggalkan `dd()` di controller, jalankan test | **Dilakukan sementara:** `dd()` menghentikan test ketika `CourseController::show` dipanggil, mencetak marker debug, dan mengakhiri proses dengan exit code 1. Baris `dd()` dihapus. |

Percobaan dijalankan di workspace dengan bantuan assistant dan semua mutasi dipulihkan. Skenario #4 hanya disimulasikan secara lokal; status CI jarak jauh tetap belum diverifikasi.

## Checkpoint — Persiapan demonstrasi dan jawaban

- **Demo regresi:** hapus sementara event `Submission::deleting`, lalu jalankan test penghapusan course di atas. Test gagal karena berkas fisik tertinggal. Pulihkan event sebelum menyimpan atau mengirim perubahan.
- **Mengapa `assertStatus(200)` saja lemah?** Status sukses tidak membuktikan isi respons, otorisasi, maupun perubahan database sesuai harapan.
- **Mengapa jalur penolakan penting?** Test harus membuktikan pengguna tanpa izin menerima status yang tepat dan tidak mengubah data.
- **Apa fungsi `RefreshDatabase`?** Mengisolasi data dan skema antar-test; migration juga perlu dapat di-rollback agar lifecycle database tetap dapat diulang.
- **Contoh debugging:** penghapusan baris anak dengan cascade database tidak menjalankan event Eloquent; file harus dihapus melalui model sebelum parent dihapus.
- **Mengapa `dd()` tidak boleh masuk PR?** `dd()` menghentikan request/test dan dapat menampilkan data internal; `Log::info()` mencatat diagnostik ke log tanpa menghentikan alur aplikasi.

Latihan READ dan seluruh tujuh BREAK telah dijalankan di workspace dengan bantuan assistant; hasilnya bukan klaim bahwa Yusuf telah melakukan latihan personal atau interview rehearsal. Demo checkpoint #1 dapat memakai penghapusan event `Submission::deleting` yang sudah terbukti membuat test cleanup gagal, lalu memulihkan kode sebelum menyimpan perubahan.
