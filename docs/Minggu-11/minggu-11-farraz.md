# Notes Minggu 11 Pemrograman Web

**Nama:** Muhammad Farras Kanz
**NIM:** 10241047
**Kelompok:** Kelompok 06 (Kelas A)
**Topik:** Optimisasi Performa — Index Database & Query Planner
**Branch:** `feat/w11-database-indexes`

---

## 1. READ — Ukur aplikasi Anda sendiri (40 menit)

Benchmark aplikasi yang tersedia dicatat pada
[`docs/performa.md`](../performa.md) pada 4 November 2026. Pengukuran memakai
satu fixture SQLite in-memory, bukan seeder penuh, sehingga angka ini bukan
baseline produksi dan tidak bisa dipakai sebagai perbandingan sebelum/sesudah.
Urutan berikut dari waktu request paling lama ke paling singkat:

| Halaman | Jumlah query | Waktu (ms) | Peak memory delta (MB) |
|---|---:|---:|---:|
| Dashboard mahasiswa | 6 | 2800.30 | 0.00 |
| Daftar mata kuliah | 4 | 1132.16 | 4.00 |
| Detail mata kuliah | 7 | 961.24 | 0.00 |
| Daftar tugas | 6 | 841.85 | 0.00 |
| Rekap nilai | 8 | 485.07 | 0.00 |
| Daftar pengumpulan (dosen) | 9 | 189.74 | 0.00 |
| Dashboard dosen | 2 | 31.19 | 0.00 |

Angka paling lambat pada fixture tersebut adalah dashboard mahasiswa; rekap
nilai menjalankan 8 query. Namun dataset hanya berisi satu mahasiswa dan satu
submission, jadi hasil ini tidak cukup untuk menyimpulkan perilaku pada seeder
penuh atau menemukan N+1 yang baru muncul pada volume besar.

Untuk fokus index, feature test menjalankan `EXPLAIN QUERY PLAN` SQLite pada
query filter course, tugas, dan submission terbaru. Test membandingkan rencana
saat index tambahan dilepas dengan rencana sesudah index dipasang: index baru
tidak dipilih pada kondisi awal, lalu index dengan nama
`courses_lecturer_id_status_index`,
`assignments_course_id_status_index`, dan
`submissions_assignment_id_submitted_at_index` dipilih sesudahnya. SQLite
menampilkan detail `SCAN`/`SEARCH`, bukan kolom `type` dan `rows` yang tersedia
pada output `EXPLAIN` MySQL/MariaDB. Karena lingkungan yang dipakai SQLite,
nilai `type`/`rows` MySQL tidak diklaim.

---

## 2. BREAK — Tujuh percobaan (45 menit)

| # | Yang dicoba | Yang harus diamati | Status pada pengerjaan Farras |
|---:|---|---|---|
| 1 | Pasang `Model::preventLazyLoading()`, jalankan seluruh halaman. | Berapa N+1 tersembunyi yang muncul? | Guard aktif; test rekap nilai lolos. Belum mengukur seluruh halaman dengan Debugbar. |
| 2 | Hapus semua `with()` dari satu controller, ukur ulang. | Bandingkan angka sebelum dan sesudah. | Belum dilakukan; perubahan sengaja tidak dibuat pada branch ini. |
| 3 | Ganti `withCount('students')` menjadi `count($course->students)`, ukur memori. | Bandingkan menghitung di database dengan memuat koleksi ke memori. | Belum dilakukan; di luar ruang lingkup index. |
| 4 | Hapus index dari `assignments.course_id`, jalankan `EXPLAIN`. | Pada MySQL/MariaDB amati apakah `type` menjadi `ALL`; pada SQLite amati `SCAN`/`SEARCH`. | Test membandingkan query plan sebelum/sesudah melepas dan memasang index tambahan. Tidak menghapus index FK `assignments.course_id` yang sudah ada. |
| 5 | Naikkan seeder menjadi 300 mahasiswa dan 1000 submission, buka rekap nilai. | Masalah performa yang tadinya kecil menjadi jelas. | Belum dilakukan; benchmark yang ada hanya memakai satu mahasiswa dan satu submission. |
| 6 | Jalankan `config:cache`, panggil `env('APP_NAME')` dari controller. | Amati pemanggilan mengembalikan `null`. | Tidak dilakukan; eksperimen mengubah cache konfigurasi tidak diperlukan untuk memvalidasi index. |
| 7 | Cache nilai mahasiswa selama 10 menit, ubah nilai, muat ulang. | Amati data basi yang merugikan pengguna. | Tidak dilakukan; eksperimen tidak dijalankan agar tidak menambahkan cache yang tidak aman pada nilai. |

BREAK di atas mengikuti skenario modul. Eksperimen 2, 3, 5, 6, dan 7 tidak
diklaim telah dijalankan sebagai bagian dari kontribusi index ini. Nomor 6
menjadi bekal untuk minggu depan; nomor 7 mengingatkan bahwa cache bukan selalu
perbaikan.

---

## 3. FIX — Perbaikan yang diterapkan

Migrasi baru dibuat pada file:

- `lms/database/migrations/2026_11_02_000001_add_performance_indexes.php`

Index yang ditambahkan:

1. `courses`: `['lecturer_id', 'status']`
2. `assignments`: `['course_id', 'status']` dan `status`
3. `submissions`: `['assignment_id', 'submitted_at']`

Tujuannya:
- mempercepat query dashboard dosen,
- mempercepat filter tugas aktif per mata kuliah,
- mempercepat pengambilan submission terbaru untuk rekap nilai dan monitoring.

Setiap index memiliki komentar eksplisit di migrasi agar jelas manfaat query yang ditopang. Method `down()` juga disiapkan agar rollback aman dan reversible.

---

## 4. BUILD — Kontribusi Muhammad Farras Kanz (`10241047`)

### File yang dibuat

| File | Fungsi |
|---|---|
| `lms/database/migrations/2026_11_02_000001_add_performance_indexes.php` | Membuat indeks database untuk query kunci aplikasi |
| `lms/tests/Feature/PerformanceIndexTest.php` | Verifikasi migrasi, rollback, dan `EXPLAIN QUERY PLAN` |
| `docs/Minggu-11/minggu-11-farraz.md` | Catatan karya mingguan |

### Hasil validasi

Test yang dibuat menguji tiga hal penting:

- indeks berhasil dibuat di skema database;
- rollback migrasi berjalan tanpa merusak integritas tabel;
- query filter yang sering dipakai tidak lagi melakukan full scan pada dataset yang cukup besar.

Dengan strategi index komposit, query yang sebelumnya punya pola `WHERE ... AND ...` dan `ORDER BY submitted_at` memiliki peluang jauh lebih baik untuk menjalankan pencarian berbasis indeks dibanding full table scan.

---

## Ringkasan

Bagian Muhammad Farras Kanz fokus pada optimisasi di level database, bukan fitur baru. Tujuan utamanya adalah memastikan query utama aplikasi berjalan lebih cepat dengan struktur indeks yang tepat, sekaligus menjaga kemampuan rollback dan validasi otomatis lewat feature test.
