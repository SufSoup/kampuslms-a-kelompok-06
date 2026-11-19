# Minggu 11 — Yusuf

## READ

Audit controller menemukan sebagian besar daftar sudah memakai `paginate()`:
mata kuliah, tugas, pengguna, notifikasi, API, dan rekap nilai. Daftar materi
dan roster monitoring pengumpulan masih memakai `get()` sehingga seluruh tabel
dibaca sekaligus.

## BREAK

| Skenario | Dampak | Bukti |
|---|---|---|
| Daftar tanpa pagination | Memori dan waktu meningkat seiring jumlah baris | `MaterialController@index` dan `SubmissionController@index` |
| Pagination tanpa query string | Filter hilang saat pindah halaman | Link halaman tidak membawa parameter request |
| Roster besar | Halaman monitoring memuat seluruh mahasiswa | Query roster memakai `get()` |
| Rekap tanpa eager loading | Query bertambah per baris | Dilindungi oleh `preventLazyLoading` dan test performa |
| Cache data nilai basi | Pengguna melihat nilai/feedback lama | Data nilai dan status real-time dikecualikan dari cache |
| `env()` di luar config | Nilai dapat kosong setelah `config:cache` | Audit codebase pada feature test performa |
| Index tidak sesuai query | Filter dan sorting dapat full scan | Ditangani pada tugas anggota lain |

## FIX

- Mengubah daftar materi menjadi `paginate(15)->withQueryString()`.
- Mengubah roster monitoring pengumpulan menjadi `paginate(25)->withQueryString()`.
- Menjaga ringkasan total roster, submitted, dan belum submitted tetap dihitung
  secara agregat sehingga tidak bergantung pada halaman aktif.
- Menambahkan `PaginationAuditTest` untuk memastikan tipe paginator, batas
  halaman, dan keberadaan tautan halaman kedua.
- Menyusun [laporan benchmark](../performa.md) dan mencatat batasan pengukuran.

## BUILD

- Branch: `feat/w11-pagination-benchmark`
- Kontribusi: standarisasi pagination daftar materi dan monitoring pengumpulan,
  audit test pagination, serta dokumentasi benchmark Minggu 11.
