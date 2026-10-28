# Minggu 10 — Queue Reliability dan Anti-IDOR

## READ

1. Queue aplikasi menggunakan koneksi `database`. Event publikasi tugas dan
   pemberian nilai membuat listener `ShouldQueue`, sehingga pekerjaan masuk ke
   tabel `jobs` sebelum diproses worker.
2. Payload job dapat diperiksa dengan `php artisan queue:monitor` atau query
   langsung ke kolom `payload` pada tabel `jobs`. Payload tidak boleh dijadikan
   tempat menyimpan nilai atau feedback.
3. Worker dijalankan dengan `php artisan queue:work`. Tanpa worker, job tetap
   menumpuk di `jobs` dan notifikasi belum masuk ke `notifications`.
4. Job yang gagal setelah batas percobaan disimpan di `failed_jobs` dan dapat
   dilihat menggunakan `php artisan queue:failed`.
5. Job gagal dapat dicoba kembali menggunakan `php artisan queue:retry all`.
   Tabel `job_batches` tersedia untuk pekerjaan yang menggunakan batch queue.

## BREAK

| Kerusakan | Dampak |
|---|---|
| Worker queue tidak dijalankan | Notifikasi tidak pernah diproses |
| Listener tidak mengimplementasikan `ShouldQueue` | Request web/API ikut mengerjakan pengiriman notifikasi |
| Listener tidak memiliki retry | Gangguan sementara langsung menggagalkan pengiriman |
| `backoff` tidak dikonfigurasi | Percobaan ulang terlalu cepat dan membebani sistem |
| Method `failed()` tidak mencatat error | Kegagalan sulit ditelusuri |
| Payload notifikasi memuat score atau feedback | Data penilaian sensitif bocor di notifikasi |
| Mark-as-read tidak membatasi user | ID notifikasi user lain dapat dimanipulasi (IDOR) |
| Event `GradeGiven` tidak terhubung ke listener | Nilai tersimpan tetapi mahasiswa tidak mendapat notifikasi |

## FIX

Perbaikan yang diterapkan pada branch `feat/w10-queue-reliability`:

1. Memastikan tabel `jobs`, `failed_jobs`, dan `job_batches` diverifikasi
   melalui migrasi queue bawaan.
2. Menambahkan dokumentasi worker, inspeksi job gagal, dan retry job ke
   `README.md`.
3. Memastikan kedua listener memiliki `ShouldQueue`, tiga percobaan, jeda
   retry 10 detik, dan logging melalui `failed()`.
4. Mendaftarkan `GradeGiven` ke `SendGradeNotification` agar event nilai
   benar-benar membuat queued listener.
5. Menambahkan feature test bahwa listener assignment dan grade dipush sebagai
   `CallQueuedListener`.
6. Menambahkan test penerima notifikasi untuk mahasiswa terdaftar dan pemilik
   submission.
7. Menambahkan test bahwa job yang gagal setelah satu percobaan dicatat di
   `failed_jobs`.
8. Memperbarui `scripts/test-authz.sh` untuk guest API, anti-IDOR
   mark-as-read via API, dan anti-IDOR route web.

## BUILD

- Branch: `feat/w10-queue-reliability`
- Implementasi: queue monitoring, failed-job handling, listener registration,
  feature test reliability, dan pengujian anti-IDOR notifikasi.
- Validasi utama:
  `php artisan test --filter=QueueReliabilityTest`
- Worker lokal:
  `php artisan queue:work`

