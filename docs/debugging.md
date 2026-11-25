# Dokumentasi Debugging Week 14

Dokumen ini mencatat temuan hasil audit dan debugging, dampaknya, kondisi perbaikan, serta pengujian regresi yang tersedia. Temuan yang belum diaudit tetap menunggu bukti dari anggota terkait.

## 1. Bug #1 — Admin Course CRUD

**PIC:** Farraz  
**Lokasi:** Menunggu hasil audit Farraz.

### Masalah

Temuan dan masalah pada Admin Course CRUD masih menunggu hasil audit Farraz.

### Dampak

Belum dapat ditentukan sebelum masalah diverifikasi.

### Perbaikan

Menunggu hasil audit dan usulan perbaikan dari Farraz.

### Pengujian regresi

Nama test dan hasil pengujian masih menunggu audit Farraz.



## 2. Bug #2 — Perbedaan validasi deadline pengumpulan Web dan API

**PIC:** Syahid  
**Lokasi:**

- Web: [`SubmissionController::store`](../lms/app/Http/Controllers/SubmissionController.php).
- API: [`Api\SubmissionController::store`](../lms/app/Http/Controllers/Api/SubmissionController.php).

### Masalah

Temuan historis menyangkut perbedaan validasi antara pengumpulan melalui Web dan API ketika deadline sudah lewat dan `allow_late = false`. Jika salah satu jalur tidak menegakkan aturan ini, mahasiswa berisiko dapat mengumpulkan tugas terlambat melalui jalur tersebut.

### Dampak

Kebijakan deadline tugas dapat dilewati melalui jalur pengumpulan yang tidak menolak submission terlambat.

### Perbaikan

Pada implementasi saat ini, kedua method `store` memeriksa kondisi `allow_late = false`, `due_at` terisi, dan waktu sekarang sudah melewati deadline sebelum menyimpan submission. Web mengembalikan error validasi pada session; API mengembalikan validation error. Dengan demikian, temuan sudah ditangani dan bukan bug aktif. Tidak ada commit atau PR perbaikan yang diklaim.

### Pengujian regresi

- Web: [`StudentSubmissionUploadTest::test_late_submission_is_rejected_when_allow_late_is_false`](../lms/tests/Feature/StudentSubmissionUploadTest.php) memastikan error validasi muncul dan submission tidak dibuat.
- API: [`GradingApiTest::test_student_cannot_submit_after_deadline_when_late_submissions_are_disabled`](../lms/tests/Feature/Api/GradingApiTest.php) memastikan respons 422, error validasi `file`, dan tidak ada submission di database.
- API juga menguji pengumpulan tepat pada deadline melalui `test_student_can_submit_at_deadline_when_late_submissions_are_disabled`, serta pengumpulan terlambat saat `allow_late = true` melalui `test_student_can_submit_after_deadline_when_late_submissions_are_enabled`.

Test regresi Web lulus (**1 test, 3 assertions**) dan test API lulus (**1 test, 4 assertions**).



## 3. Bug #3 — Queue/file lifecycle

**PIC:** Yusuf  
**Lokasi:** Menunggu hasil audit Yusuf.

### Masalah

Temuan mengenai queue/file lifecycle masih menunggu hasil audit Yusuf.

### Dampak

Belum dapat ditentukan sebelum masalah diverifikasi.

### Perbaikan

Menunggu hasil audit dan usulan perbaikan dari Yusuf.

### Pengujian regresi

Nama test dan hasil pengujian masih menunggu audit Yusuf.



---

## Ringkasan

| No. | Temuan | Perbaikan / Bukti Pengujian |
|---|---|---|
| 1 | Admin Course CRUD — audit Farraz belum selesai. | Lokasi, perbaikan, dan test masih menunggu hasil audit. |
| 2 | Perbedaan historis validasi deadline Web/API saat `allow_late = false` — Syahid. | Kedua jalur menolak submission terlambat pada implementasi saat ini. Test regresi Web (1 test, 3 assertions) dan API (1 test, 4 assertions) lulus; tidak ada klaim mutation khusus. |
| 3 | Queue/file lifecycle — audit Yusuf belum selesai. | Lokasi, perbaikan, dan test masih menunggu hasil audit. |
