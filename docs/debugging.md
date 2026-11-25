# Dokumentasi Debugging Week 14

Dokumen ini mencatat temuan hasil audit dan debugging, dampaknya, kondisi perbaikan, serta pengujian regresi yang tersedia. Temuan yang belum diaudit tetap menunggu bukti dari anggota terkait.

## 1. Bug #1 — Indeks foreign key saat rollback indeks performa

**PIC:** Farraz  
**Riwayat perbaikan:** commit `8c6f228`

**Lokasi:** [migrasi indeks performa](../lms/database/migrations/2026_11_02_000001_add_performance_indexes.php) dan [PerformanceIndexTest](../lms/tests/Feature/PerformanceIndexTest.php).

### Gejala

Rollback migrasi indeks performa harus menghapus indeks komposit yang ditambahkannya, tetapi kolom `courses.lecturer_id` tetap menjadi foreign key. Perbaikan historis mencatat bahwa indeks pendukung foreign key tersebut harus tetap tersedia setelah rollback.

### Mempersempit penyebab

Bandingkan `up()` dan `down()` pada migrasi: `up()` menambah indeks komposit `courses(lecturer_id, status)`, sedangkan `down()` menghapusnya. Pemeriksaan skema diperlukan untuk memastikan penghapusan indeks komposit tidak meninggalkan foreign key `lecturer_id` tanpa indeks pendukung.

### Perbaikan

Sebelum menghapus indeks komposit, `down()` memeriksa `Schema::hasIndex('courses', ['lecturer_id'])` dan membuat indeks satu kolom jika belum ada. Dengan begitu, rollback hanya menghapus indeks yang diperkenalkan migrasi tersebut.

### Pengujian regresi

`PerformanceIndexTest::test_index_migration_is_present_and_rollback_is_reversible` memeriksa indeks performa, menjalankan rollback, memastikan tabel tetap tersedia dan indeks performa terhapus, lalu menjalankan migrasi kembali.


## 2. Bug #2 — Validasi deadline dan cakupan identitas pembaca

**PIC:** Syahid  
**Lokasi:**

- Web: [`SubmissionController::store`](../lms/app/Http/Controllers/SubmissionController.php).
- API: [`Api\SubmissionController::store`](../lms/app/Http/Controllers/Api/SubmissionController.php).
- Detail mata kuliah: [`CourseController::show`](../lms/app/Http/Controllers/CourseController.php).

### Masalah

Audit historis menemukan dua masalah pada jalur berbeda:

- Pengumpulan melalui API tidak selalu menegakkan deadline yang sama dengan Web saat `allow_late = false`.
- Detail mata kuliah untuk mahasiswa memuat relasi `readers` tanpa membatasi identitas ke mahasiswa yang sedang login.

### Dampak

Mahasiswa berisiko mengirim tugas terlambat melalui jalur API, atau melihat identitas mahasiswa lain yang telah menandai sumber belajar sebagai sudah dibaca.

### Perbaikan

Implementasi saat ini memeriksa aturan deadline sebelum menyimpan submission pada kedua method `store`. Saat memuat `learningResources.readers`, `CourseController::show` juga membatasi relasi tersebut ke mahasiswa yang sedang login jika pemakainya berperan sebagai mahasiswa. Temuan ini telah ditangani; bagian ini tidak mengklaim bahwa bug tersebut masih aktif.

### Pengujian regresi

- Web: [`StudentSubmissionUploadTest::test_late_submission_is_rejected_when_allow_late_is_false`](../lms/tests/Feature/StudentSubmissionUploadTest.php) memastikan error validasi muncul dan submission tidak dibuat.
- API: [`GradingApiTest::test_student_cannot_submit_after_deadline_when_late_submissions_are_disabled`](../lms/tests/Feature/Api/GradingApiTest.php) memastikan respons 422, error validasi `file`, dan tidak ada submission di database.
- Privasi pembaca: [`LearningResourceWebTest::test_student_response_and_view_data_do_not_expose_other_reader_identity`](../lms/tests/Feature/LearningResourceWebTest.php) memastikan identitas mahasiswa lain tidak muncul dalam respons maupun data view.
- API juga menguji pengumpulan tepat pada deadline melalui `test_student_can_submit_at_deadline_when_late_submissions_are_disabled`, serta pengumpulan terlambat saat `allow_late = true` melalui `test_student_can_submit_after_deadline_when_late_submissions_are_enabled`.

## 3. Bug #3 — Berkas privat tertinggal setelah penghapusan berantai

**PIC:** Yusuf  
**Riwayat implementasi:** commit `1698ea0`

**Lokasi:** [`Submission`](../lms/app/Models/Submission.php), [`Material`](../lms/app/Models/Material.php), [`CourseController::destroy`](../lms/app/Http/Controllers/CourseController.php), dan [FileLifecycleCleanupTest](../lms/tests/Feature/FileLifecycleCleanupTest.php).

### Masalah

Penghapusan baris anak melalui foreign key `ON DELETE CASCADE` dilakukan oleh database dan tidak menjalankan event `deleting` Eloquent pada setiap model anak. Jika penghapusan file hanya bergantung pada event tersebut, file submission atau materi di private storage dapat tertinggal setelah baris database-nya terhapus.

### Dampak

Berkas yatim tetap menempati storage dan tidak lagi memiliki record yang dapat digunakan aplikasi untuk menemukannya atau menghapusnya melalui alur biasa.

### Perbaikan

Model `Submission` dan `Material` menghapus file pada event `deleting`. Sebelum menghapus course atau assignment, controller menghapus record anak melalui Eloquent terlebih dahulu agar event cleanup berjalan; cascade database kemudian menghapus sisa relasi.

### Pengujian regresi

`FileLifecycleCleanupTest` memakai `Storage::fake('local')` dan memeriksa penghapusan file serta record database untuk penghapusan model, assignment melalui Web/API, dan course melalui Web. Pada audit mutasi, event `Submission::deleting` dihilangkan sementara: test penghapusan course gagal karena `submissions/1/jawaban.pdf` masih ada. Setelah event dipulihkan, test yang sama lulus (**1 test, 6 assertions**).

---

## Ringkasan

| No. | Temuan | Perbaikan / Bukti Pengujian |
|---|---|---|
| 1 | Indeks foreign key `courses.lecturer_id` saat rollback migrasi indeks — Farraz. | Guard `Schema::hasIndex` menjaga indeks pendukung; migrasi rollback/re-run diuji oleh `PerformanceIndexTest`. |
| 2 | Paritas deadline Web/API dan pembatasan identitas pembaca — Syahid. | Test deadline Web/API dan privasi pembaca tersedia; ketiganya lulus dalam verifikasi terarah. |
| 3 | Berkas privat tertinggal saat penghapusan berantai — Yusuf. | Cleanup Eloquent sebelum cascade; mutasi event cleanup membuat test gagal, lalu test lulus setelah pemulihan. |
