# Catatan Week 14 — Debugging & Testing

- **Nama:** Syahid
- **Branch:** `syahid`
- **Proyek:** KampusLMS Laravel 12
- **Batasan:** Tidak mengerjakan `kampuslms-broken`, tidak mengubah schema, dan tidak melakukan commit/push.

## Status sumber instruksi

Saya mencari `modul.md` di repository KampusLMS, tetapi file tersebut tidak tersedia. Catatan [Week 14 Ikhsyan](./minggu-14-ikhsyan.md) merujuk ke `modul.md §14.3`, namun bukan pengganti modul aslinya. Karena itu, saya tidak menyatakan seluruh praktikum READ dan tujuh BREAK resmi telah selesai. Tabel BREAK di bawah memisahkan eksperimen yang benar-benar dijalankan dari yang belum dilakukan.

## READ — audit dan pemeriksaan yang dilakukan

Audit dilakukan pada [MilestoneTwoAuthzTest](../../lms/tests/Feature/MilestoneTwoAuthzTest.php), [GradingWebTest](../../lms/tests/Feature/GradingWebTest.php), [GradingApiTest](../../lms/tests/Feature/Api/GradingApiTest.php), [DashboardCachingTest](../../lms/tests/Feature/DashboardCachingTest.php), dan [LecturerSubmissionMonitoringTest](../../lms/tests/Feature/LecturerSubmissionMonitoringTest.php).

- Tidak ditemukan test halaman atau grading dalam cakupan ini yang hanya memeriksa respons sukses (`assertOk`/status 200) tanpa memeriksa isi respons, efek database, atau perilaku terkait.
- Test detail mahasiswa dan dosen memeriksa data yang boleh tampil serta menolak identitas, tugas, berkas, catatan, dan course lain.
- Test grading Web dan API memeriksa respons forbidden bagi dosen dari course lain dan memastikan nilai/feedback Grade existing tidak berubah.
- Test cache Admin sekarang membuktikan jumlah Assignment dari 1 menjadi 0, memastikan key cache hilang setelah penghapusan, lalu memuat ulang dashboard.
- Invalidasi cache dosen telah diuji untuk siklus Assignment, Submission, Grade, enrollment, Material, dan perubahan course. Cache statistik Admin tidak memuat Grade; tidak ada dasar untuk menambah invalidasi Admin atas perubahan Grade.
- Test monitoring dosen memeriksa nama tugas/course, identitas dan file submission, tautan yang relevan, jumlah mahasiswa, status pengumpulan/nilai, serta respons forbidden untuk dosen course lain dan mahasiswa.
- Test API pengumpulan mencakup kondisi deadline: ditolak setelah deadline jika `allow_late` mati, diterima tepat pada deadline, dan diterima setelah deadline jika `allow_late` hidup.
- Dua assertion API diperkuat setelah audit: daftar submission memastikan ID yang benar dikembalikan; penolakan pengumpulan ganda memastikan jumlah submission di database tetap satu.

Tidak ditemukan kelemahan lain yang memerlukan perubahan pada lima file tersebut dalam ruang lingkup audit ini.

## BREAK — eksperimen mutation yang benar-benar dijalankan

Setiap mutasi berikut hanya sementara, diuji pada test yang disebutkan, lalu kode produksi/view dipulihkan. Setelah pemulihan, file-file target ikut lulus pada verifikasi akhir **57 test, 318 assertions**.

| Target | Kondisi normal/setelah pemulihan | Saat mutation | Bukti |
|---|---|---|---|
| Web grading, `SubmissionController@storeGrade` | Test otorisasi lulus setelah pemulihan. | `Gate::authorize('update', $grade)` pada cabang Grade existing dihapus sementara; test mengharapkan 403 tetapi menerima **302**. | Test terkait kembali lulus pada suite terarah akhir; controller tidak memiliki diff. |
| API grading, `Api\SubmissionController@grade` | Test otorisasi lulus setelah pemulihan. | Pemeriksaan update Grade existing dihapus sementara; test mengharapkan 403 tetapi menerima **200**. | Test terkait kembali lulus pada suite terarah akhir; controller tidak memiliki diff. |
| Cache statistik Admin saat Assignment dihapus | Test pulih dan lulus (**1 test, 5 assertions**). | Pemanggilan `DashboardCache::forgetAdminStats()` pada event penghapusan dihapus sementara; assertion bahwa key cache sudah hilang gagal karena cache masih ada. | Model Assignment dipulihkan; tidak ada diff mutation. |
| Submission mahasiswa lain pada halaman detail mahasiswa | Setelah pemulihan: **1 test, 9 assertions lulus**. | Halaman sementara menampilkan identitas submission mahasiswa lain; `assertDontSee($otherStudent->name)` gagal. | Controller dan view dipulihkan; tidak ada diff mutation. |
| Submission course lain pada halaman detail dosen | Setelah pemulihan: **1 test, 12 assertions lulus**. | Controller/view sementara menampilkan ringkasan submission dari course lain; `assertDontSee($otherAssignment->title)` gagal setelah judul asing muncul. | Test dijalankan lagi setelah pemulihan dan lulus; controller dan view bersih. |

### Status tujuh BREAK

Daftar skenario berikut hanya terlihat melalui rujukan pada catatan Week 14 yang ada; karena `modul.md` asli tidak ditemukan, ini bukan klaim bahwa daftar tersebut identik dengan instruksi resmi.

| # | Skenario | Status sebenarnya |
|---|---|---|
| 1 | Mengosongkan controller pada test yang hanya memeriksa status 200 | Belum dilakukan. |
| 2 | Menghapus pemeriksaan `Gate::authorize()` lalu menjalankan test | Dilakukan untuk otorisasi update Grade Web dan API; hasilnya tercatat di tabel mutation. |
| 3 | Menghapus `RefreshDatabase` lalu menjalankan test berulang | Belum dilakukan; tidak diubah agar isolasi test/database tetap terjaga. |
| 4 | Menguji ketergantungan test pada seeder/CI | Belum dilakukan. |
| 5 | Menghapus `Storage::fake()` lalu menjalankan test upload | Belum dilakukan; tidak dilakukan agar test tidak menulis file ke storage proyek. |
| 6 | Merusak `down()` migration dan menjalankan test | Belum dilakukan karena batasan tugas melarang perubahan schema/migration. |
| 7 | Menambahkan `dd()` lalu menjalankan test | Eksperimen tidak dilakukan. Pemindaian `app` dan `resources/views` untuk `dd()`/`dump()` menghasilkan **0 temuan**. |

## Test dan perubahan

Test terkait yang dijalankan setelah perubahan audit:

```text
MilestoneTwoAuthzTest.php
GradingWebTest.php
Api/GradingApiTest.php
DashboardCachingTest.php
LecturerSubmissionMonitoringTest.php

57 passed (318 assertions), 0 failed
```

Test dosen lintas course setelah mutation dipulihkan juga lulus sendiri: **1 test, 12 assertions**. Tidak ada klaim bahwa seluruh test suite Laravel dijalankan pada sesi ini.

File perubahan permanen:

- [GradingApiTest.php](../../lms/tests/Feature/Api/GradingApiTest.php) — test otorisasi update Grade yang sudah ada dipertahankan; assertion pengumpulan ganda dan ID submission yang benar diperkuat.
- [DashboardCachingTest.php](../../lms/tests/Feature/DashboardCachingTest.php) — test invalidasi statistik Admin saat Assignment dihapus.
- [GradingWebTest.php](../../lms/tests/Feature/GradingWebTest.php) — test dosen course lain tidak dapat mengubah Grade yang sudah ada.
- [MilestoneTwoAuthzTest.php](../../lms/tests/Feature/MilestoneTwoAuthzTest.php) — assertion data halaman detail diperkuat dan mutation kebocoran dosen lintas course tertangkap.
- [minggu-14-syahid.md](./minggu-14-syahid.md) — dokumentasi ini.

Tidak ada perubahan permanen pada controller, policy, route, model, view produksi, migration, atau schema. Eksperimen yang membutuhkan instruksi modul asli, storage terisolasi, atau perubahan migration masih memerlukan tindakan lanjutan setelah sumber instruksi dan lingkungan aman tersedia.
