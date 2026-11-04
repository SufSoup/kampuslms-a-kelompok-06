# Laporan Benchmark Performa KampusLMS

## Metode dan dataset

Pengukuran berikut dijalankan pada 4 November 2026 melalui
`TemporaryPerformanceMeasurementTest` (test sementara, tidak disimpan dalam
commit), memakai environment `testing`, SQLite in-memory, dan satu fixture
yang terdiri dari 1 admin, 1 dosen, 1 mahasiswa, 1 mata kuliah, 20 materi,
1 tugas, dan 1 submission.

- Query: jumlah entri `DB::getQueryLog()` selama request.
- Waktu: durasi `hrtime()` dari awal sampai akhir request.
- Memori: selisih `memory_get_peak_usage(true)` dan memori sebelum request.
- Angka berlaku untuk dataset dan mesin tersebut, bukan SLA produksi.

## Hasil pengukuran aktual

| Halaman | Query | Waktu (ms) | Peak memory delta (MB) |
|---|---:|---:|---:|
| Daftar mata kuliah | 4 | 1132.16 | 4.00 |
| Detail mata kuliah | 7 | 961.24 | 0.00 |
| Daftar tugas | 6 | 841.85 | 0.00 |
| Daftar pengumpulan (dosen) | 9 | 189.74 | 0.00 |
| Rekap nilai | 8 | 485.07 | 0.00 |
| Dashboard mahasiswa | 6 | 2800.30 | 0.00 |
| Dashboard dosen | 2 | 31.19 | 0.00 |

Semua tujuh request mengembalikan HTTP 200. Rekap nilai dosen tercatat
8 query, sehingga memenuhi target di bawah 15 query pada fixture ini.

## Status baseline sebelum optimisasi

Repository tidak memiliki commit atau artefak pengukuran historis yang memuat
angka sebelum optimisasi. Karena itu kolom “sebelum” tidak diisi dengan angka
rekaan. Data yang dapat diverifikasi dari test sebelumnya hanya berupa target:
`PerformanceEagerLoadingTest` mengassert rekap nilai dosen dan mahasiswa
kurang dari 15 query, sedangkan pengukuran aktual di atas menghasilkan angka
konkret untuk seluruh halaman.

Untuk memperoleh perbandingan sebelum-sesudah yang sah, jalankan runner yang
sama pada commit sebelum perubahan pagination dan simpan outputnya dengan
dataset, environment, dan mesin yang sama.

## Temuan dan batasan cache

Daftar materi dan monitoring pengumpulan kini menggunakan pagination sehingga
tidak memuat seluruh daftar sekaligus. Statistik agregat dashboard dapat
di-cache oleh implementasi dashboard dengan invalidasi saat data berubah.
Nilai individual mahasiswa, feedback dosen, status pengumpulan real-time, dan
presensi tidak boleh di-cache karena perubahan harus langsung terlihat pengguna.
