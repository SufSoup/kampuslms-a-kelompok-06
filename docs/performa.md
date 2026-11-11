# Laporan Benchmark Performa KampusLMS

## Metode dan dataset

Pengukuran dijalankan melalui `BeforeAfterQueryCountTest` (test sementara, tidak
disimpan dalam commit) menggunakan `DB::getQueryLog()` per request, environment
`testing`, SQLite in-memory, dan satu fixture yang terdiri dari 1 admin, 1 dosen,
1 mahasiswa, 1 mata kuliah, 20 materi, 1 tugas, dan 1 submission+grade.

Kolom **Sebelum** diukur pada commit `276af95` (pra-w11 — sebelum PR Ikhsyan, Yusuf,
Syahid, dan Farraz masuk ke `dev`). Kolom **Sesudah** diukur pada commit `bc1d906`
(setelah seluruh PR w11 merged). Metode pengukuran identik di kedua kondisi.

> Angka query berlaku untuk fixture di atas. Dataset yang lebih besar (seeder penuh:
> 30 mahasiswa, 100+ submission) akan menunjukkan perbedaan yang jauh lebih dramatis
> pada halaman-halaman yang sebelumnya menggunakan `->get()` tanpa paginate.

## Perbandingan Sebelum–Sesudah

| Halaman | Query Sebelum | Query Sesudah | Selisih | Keterangan |
|---------|:---:|:---:|:---:|---|
| Daftar mata kuliah | 4 | 4 | — | Tidak berubah (sudah optimal) |
| Detail mata kuliah | 5 | 5 | — | Tidak berubah (sudah optimal) |
| Daftar tugas | 6 | 6 | — | Tidak berubah (sudah optimal) |
| Daftar pengumpulan (dosen) | 6 | 9 | +3 | `->get()` → `->paginate(25)` + 2 COUNT terpisah; tradeoff wajar: query naik tapi tidak load seluruh tabel ke memori |
| Rekap nilai | 9 | 9 | — | Sudah pakai `with([...])` + `paginate(25)` sejak awal; target < 15 ✓ |
| Dashboard mahasiswa | 6 | 6 | — | Tidak berubah |
| Dashboard dosen | 2 | 2 (cache hit: 0) | ↓ | Cache hit ke-2+: 0 query ke database — statistik diambil dari `Cache::remember` |

## Analisis

### Halaman terburuk sebelum optimisasi
**Daftar pengumpulan (dosen)** dan **daftar materi** merupakan halaman yang paling
berisiko: keduanya menggunakan `->get()` tanpa limitasi, artinya seluruh baris
dimuat ke memori PHP dalam satu request. Pada dataset fixture (1 submission, 20
materi) angkanya terlihat kecil, tetapi pada seeder penuh (100+ submission, 20+
materi per mata kuliah) response time dan penggunaan memori akan meningkat linier.

Penyebab utama:
- `MaterialController@index`: `$course->materials()->get()` — tidak dipaginasi.
- `SubmissionController@index`: `$assignment->course->students()->get()` — semua
  mahasiswa dan submission-nya dimuat sekaligus.

### Perubahan yang dilakukan w11

| Komponen | Sebelum | Sesudah |
|---|---|---|
| `MaterialController@index` | `->get()` | `->paginate(15)->withQueryString()` |
| `SubmissionController@index` | `->get()` tanpa limit | `->paginate(25)->withQueryString()` |
| `AppServiceProvider` | Tanpa guard | `Model::preventLazyLoading(!app()->isProduction())` |
| `DashboardController` | Query langsung setiap request | `Cache::remember` TTL 10 menit, invalidasi otomatis via model events |
| Index database | Tanpa index composite | Index `['lecturer_id','status']`, `['course_id','status']`, `['assignment_id','submitted_at']` |
| `env()` di luar `config/` | Tidak diaudit | Zero violations (diverifikasi `PerformanceEagerLoadingTest`) |

### Mengapa query daftar pengumpulan naik (+3)?

Ini tradeoff yang disengaja dan benar:

- **Sebelum:** 1 query `SELECT *` yang memuat **seluruh mahasiswa + submission ke memori**.
  Pada 30 mahasiswa: 30 user object + N submission hydrated ke PHP RAM sekaligus.
- **Sesudah:** `paginate(25)` = 1 query SELECT + 1 COUNT + 2 COUNT terpisah untuk
  `submittedCount` dan `notSubmittedCount`. Total 9 query, tapi setiap query
  ringan dan tidak ada dump data masif ke memori.

Prinsipnya: **lebih banyak query kecil > satu query besar yang memuat semua data**.

## Data yang dilarang di-cache

| Data | Alasan |
|---|---|
| Nilai individual mahasiswa (`Grade.score`) | Harus real-time — data basi 10 menit berarti mahasiswa melihat nilai lama yang sudah dikoreksi dosen |
| Feedback dosen (`Grade.feedback`) | Sama seperti score |
| Status pengumpulan submission real-time | Mahasiswa yang baru submit tidak boleh melihat status "Belum Mengumpulkan" dari cache |

Yang boleh di-cache: statistik agregat dashboard (jumlah user, course, materi,
tugas, submission belum dinilai) dengan TTL 10 menit dan invalidasi otomatis
saat model terkait mengalami `saved`/`deleted` event.
