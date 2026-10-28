# Catatan Minggu 10 Pemrograman Web

**Nama:** Muhammad Farraz Kanz  
**NIM:** 10241047  
**Kelompok:** Kelompok 06 (Kelas A)  
**Topik:** UI Notifikasi Web dan Queue Worker  
**Branch tugas:** `paras`

---

## 1. READ — Ikuti Satu Job

Pemeriksaan ini merujuk alur tugas yang dipublikasikan dan listener `SendAssignmentNotification`. Saya tidak menghentikan atau menjalankan worker, membuat tugas untuk eksperimen, maupun membaca tabel antrean aplikasi yang sedang berjalan. Hasil di bawah membedakan pemeriksaan kode dan test otomatis dari eksperimen manual yang belum dilakukan.

| # | Pemeriksaan | Status dan hasil yang benar-benar diketahui | Bukti |
|---|---|---|---|
| 1 | Matikan worker, buat satu tugas baru, lalu periksa tabel `jobs`. | **Belum dilakukan.** Belum ada observasi apakah baris job benar-benar masuk atau berapa `attempts`-nya. Konfigurasi contoh proyek memilih database queue, tetapi itu bukan bukti keadaan database runtime. | `lms/.env.example` menetapkan `QUEUE_CONNECTION=database`; `lms/config/queue.php` menggunakan tabel `jobs`. Tidak ada tabel runtime yang diperiksa untuk eksperimen ini. |
| 2 | Baca kolom `payload`; periksa nama Listener dan ID tugas. | **Belum dilakukan.** Tidak ada payload job aktual yang dibaca; nama Listener atau ID tugas tidak diklaim telah ditemukan di dalamnya. | `php artisan event:list` menampilkan `SendAssignmentNotification (ShouldQueue)`. `AssignmentPublished` membawa model `Assignment` menurut `lms/app/Events/AssignmentPublished.php`, tetapi tidak membuktikan isi payload tersimpan. |
| 3 | Jalankan `php artisan queue:work`, amati keluaran, lalu periksa tabel `jobs` kembali. | **Belum dilakukan.** Worker tidak dijalankan manual dan keluaran `Processing`/`Processed` maupun penghapusan baris job tidak diamati. | Tidak ada worker yang dijalankan dalam verifikasi ini. `lms/phpunit.xml` menetapkan `QUEUE_CONNECTION=sync`, sehingga hasil test suite tidak membuktikan database worker berjalan. |
| 4 | Periksa jumlah baris `notifications` dan bandingkan dengan mahasiswa penerima. | **Eksperimen tabel manual belum dilakukan.** Test otomatis menguji pengiriman kepada mahasiswa terdaftar dan tidak kepada mahasiswa lain; jumlah pada database runtime tidak diperiksa. | `lms/tests/Feature/NotificationEngineTest.php` menguji penerima untuk tugas dan submission dengan database test terisolasi. |
| 5 | Baca kolom `data` dan evaluasi apakah ada data yang tidak seharusnya disimpan. | **Pemeriksaan manual tabel belum dilakukan.** Dalam test otomatis, payload grade diperiksa tidak memiliki `score` dan `feedback`; bukan pembacaan manual tabel produksi/runtime. | `NotificationEngineTest::test_notification_payload_strictly_omits_score_and_feedback` memverifikasi kunci tersebut tidak ada dan memeriksa field payload yang diizinkan. |

---

## 2. BREAK — Delapan Kerusakan

Skenario yang mengubah konfigurasi, kode Listener, atau payload sensitif tidak dijalankan pada aplikasi/runtime bersama. Status berikut mencatat apakah pengamatan benar-benar dilakukan dan bukti yang tersedia.

| # | Skenario dan tujuan pengamatan | Status dan hasil | Bukti |
|---|---|---|---|
| 1 | Gunakan `QUEUE_CONNECTION=database`, matikan worker, buat tugas, dan amati kegagalan yang tidak menampilkan error jelas—aksi web bisa tampak berhasil padahal notifikasi masih menunggu. | **Eksperimen belum dilakukan.** Tidak ada tugas eksperimen atau pemeriksaan tabel `jobs`; karena itu tidak ada kegagalan “tanpa error” yang diklaim teramati. | `.env.example` dan `config/queue.php` menunjukkan dukungan database queue. Kode saja tidak membuktikan perilaku runtime tanpa worker. |
| 2 | Hapus `implements ShouldQueue`, buat tugas untuk 40 mahasiswa, dan amati waktu pemrosesan. | **Tidak dilakukan.** Kode produksi tidak diubah dan tidak ada pengukuran waktu atau 40 akun dummy yang dibuat untuk eksperimen ini. | `SendAssignmentNotification` saat ini mengimplementasikan `ShouldQueue`; tidak ada hasil benchmark yang tersedia. |
| 3 | Ganti `QUEUE_CONNECTION=sync`, ulangi skenario nomor 2, lalu bandingkan dengan nomor 1 dan 2. | **Perbandingan eksperimen tidak dilakukan.** Test suite berjalan dengan driver `sync`, tetapi tidak ada perubahan konfigurasi manual atau pengukuran komparatif. | `lms/phpunit.xml` menetapkan `QUEUE_CONNECTION=sync`; itu hanya konfigurasi test, bukan hasil perbandingan waktu. |
| 4 | Buat Notification yang melempar exception, jalankan worker, lalu amati `tries` dan `failed_jobs`. | **Tidak dilakukan.** Tidak ada exception yang sengaja dilempar dan tabel `failed_jobs` tidak diperiksa. | Kode Listener menetapkan `$tries = 3` dan `$backoff = 10`; test engine memverifikasi nilainya, tetapi tidak menguji retry/failure worker. |
| 5 | Hentikan worker saat job diproses, lalu periksa apakah job kembali ke queue atau hilang. | **Tidak dilakukan.** Worker tidak dihentikan dan tabel `jobs` tidak diperiksa sebelum/sesudah. | Tidak ada bukti observasi `reserved_at`, `retry_after`, atau pemulihan job dalam verifikasi ini. |
| 6 | Masukkan nilai mahasiswa ke payload dan periksa tabel `notifications` sebagai admin untuk mengevaluasi kebocoran permanen. | **Eksperimen kebocoran tidak dilakukan** demi menghindari penulisan data sensitif. Test payload aman berhasil dijalankan. | `NotificationEngineTest::test_notification_payload_strictly_omits_score_and_feedback` memastikan `score` serta `feedback` tidak ada dalam payload grade. Tabel runtime tidak dibuka sebagai admin. |
| 7 | Kirim aksi tandai-dibaca dengan ID notifikasi orang lain dan periksa perlindungan IDOR. | **Lulus melalui test otomatis; bukan request manual.** Endpoint web mengembalikan 404 dan notifikasi milik pengguna lain tetap belum dibaca. | `lms/tests/Feature/NotificationWebTest.php::test_user_cannot_mark_another_users_notification_as_read` dan `lms/tests/Feature/Api/NotificationApiTest.php::test_user_cannot_mark_another_users_notification_as_read`. |
| 8 | Jika memakai Reverb, ganti `PrivateChannel` menjadi `Channel` dan uji langganan/pengamatan oleh pengguna lain. | **Tidak berlaku.** Proyek tidak dikonfigurasi untuk Reverb atau channel notifikasi; eksperimen broadcast tidak dijalankan. | `.env.example` memakai `BROADCAST_CONNECTION=log`; pencarian kode/dependensi proyek tidak menemukan konfigurasi atau penggunaan Reverb. |

---

## 3. BUILD — Perubahan dan Verifikasi

### Berkas implementasi yang berubah

- `lms/app/Http/Controllers/NotificationController.php` — halaman web dan aksi mark-as-read yang mencari notifikasi melalui relasi pengguna login.
- `lms/routes/web.php` — route web notifikasi di dalam middleware `auth`.
- `lms/resources/views/notifications/index.blade.php` — status baca, form CSRF, flash, dan pagination.
- `lms/resources/views/components/layout.blade.php` — lonceng, unread count untuk pengguna login, dan polling 30 detik.
- `lms/routes/api.php` dan `lms/app/Http/Controllers/Api/NotificationController.php` — session web untuk GET polling dengan autentikasi tetap aktif dan `meta.unread_count` pada respons.
- `lms/tests/Feature/NotificationWebTest.php` dan `lms/tests/Feature/Api/NotificationApiTest.php` — pengujian UI, anti-IDOR, pagination, count, serta autentikasi polling.
- `README.md` — petunjuk menjalankan dan menghentikan database queue worker.
- `docs/Minggu-10/minggu-10-farraz.md` — catatan READ dan BREAK berbasis bukti serta ringkasan BUILD ini.

Tidak ada perubahan schema/migrasi, Event, Listener, Notification core, grading, atau queue reliability dalam tugas ini.

### Test yang dijalankan

- `php artisan route:list --path=notifications -v` — menampilkan route API dan web; route web index/read berada di middleware `auth`.
- `php artisan test tests/Feature/NotificationWebTest.php tests/Feature/Api/NotificationApiTest.php` — **12 lulus, 40 assertion**.
- `php artisan test` — **171 lulus, 630 assertion**.
- `vendor/bin/pint --test ...` — lulus untuk berkas PHP yang diperiksa.
- `git diff --check` — lulus.

Test suite menggunakan SQLite in-memory dan `QUEUE_CONNECTION=sync` dari `phpunit.xml`; hasil tersebut tidak membuktikan worker database berjalan.

### Catatan yang masih perlu ditindaklanjuti

`php artisan event:list` menampilkan `SendAssignmentNotification` dua kali untuk `AssignmentPublished`—sebagai listener class dan `@handle`. Ini berpotensi menyebabkan pemrosesan ganda dan berada di luar ruang lingkup UI Farraz; pemilik Event/Listener perlu meninjau pendaftarannya. Eksperimen job database, retry, dan pemulihan worker tetap belum dilakukan.

Tidak ada commit baru yang dibuat untuk pekerjaan ini. Jalankan `php artisan queue:work` pada terminal terpisah di direktori `lms/` untuk pemeriksaan manual queue lokal; hentikan dengan `Ctrl+C`. Gunakan database dan akun dummy bila melanjutkan eksperimen BREAK.
