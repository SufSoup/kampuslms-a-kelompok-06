# Dokumentasi Praktik Minggu 6 — REST API dan Integrasi

## Nama: Muhammad Yusuf Pratama

## NIM: 10241053

## READ — Membandingkan alur web dan API

Web dan API dapat memakai model serta database yang sama, tetapi kontrak keluarannya berbeda. Controller web biasanya mengembalikan Blade atau redirect dengan flash session. Controller API mengembalikan JSON dan status HTTP yang dibaca oleh client seperti aplikasi mobile atau frontend terpisah.

Route API KampusLMS didefinisikan pada `lms/routes/api.php` dan menggunakan prefix `/api/v1`. Login API dibatasi dengan `throttle:5,1`; route terlindungi memakai `auth:sanctum` serta batas umum `throttle:60,1`. Token API dikirim melalui header `Authorization`. Header `Accept: application/json` meminta response dalam format JSON, termasuk ketika terjadi kesalahan.

## BREAK — Pemeriksaan keamanan API

| Kesalahan | Risiko / hasil yang seharusnya |
|---|---|
| Mengembalikan model pengguna mentah | Field internal yang tidak ditujukan ke client dapat ikut terekspos; gunakan API Resource. |
| Menghapus `auth:sanctum` | Endpoint yang semestinya privat dapat dibuka tanpa autentikasi. |
| Memakai token tidak valid | Request harus ditolak sebagai belum terautentikasi (401). |
| Mengakses objek yang bukan hak pengguna | Pengguna sudah terautentikasi tetapi tidak berhak; hasil yang tepat adalah 403. |
| Menghilangkan eager loading pada koleksi | Query tambahan per item dapat menimbulkan masalah N+1. |
| Menghapus throttle login | Percobaan login berulang tidak lagi memiliki batas laju yang ditetapkan. |
| Memberi pesan berbeda untuk email dan password | Perbedaan pesan membantu pihak lain menebak email yang terdaftar. |

401 dan 403 menjawab masalah berbeda: 401 berarti kredensial/token tidak mengautentikasi pengguna, sedangkan 403 berarti pengguna yang dikenali tidak memiliki hak atas tindakan atau data tersebut.

## BUILD — Endpoint dan pola response yang diamati

Endpoint API saat ini mencakup login/logout, profil `/me`, mata kuliah, materi, tugas, submission, penilaian, notifikasi, dan sumber belajar. Koleksi mata kuliah menggunakan `CourseResource`, eager loading dosen, menghitung jumlah materi dan tugas, serta membatasi daftar berdasarkan peran:

- admin memperoleh seluruh mata kuliah;
- dosen memperoleh mata kuliah yang diampu;
- mahasiswa memperoleh mata kuliah yang diikuti.

Daftar tugas menggunakan `AssignmentResource`, eager loading pembuat tugas, pagination, dan pembatasan akses berdasarkan pengampu atau enrollment. Untuk mahasiswa, tugas yang terlihat harus berstatus `published`. Pembuatan resource menghasilkan status `201`, penghapusan tugas menghasilkan `204`, dan percobaan akses lintas kepemilikan diuji sebagai `403`.

Dokumentasi endpoint dan contoh request tersedia di `docs/api.md`. Sebelum mengirim token dalam request, token harus diperlakukan sebagai rahasia dan tidak disimpan dalam repository.

## Catatan kesesuaian kontrak

Modul meminta API Resource pada endpoint agar struktur response terkontrol dan tidak mengirim model mentah. Endpoint daftar mata kuliah dan tugas yang diperiksa sudah menggunakan Resource. Untuk menyatakan seluruh target API telah terpenuhi, endpoint lain tetap perlu dibandingkan satu per satu dengan skema response dan kode status di spesifikasi.

## Pengujian

Dari direktori `lms/`, jalankan pengujian API berikut:

```bash
php artisan test --filter=AssignmentApiTest
php artisan test --filter=CourseApiTest
php artisan test --filter=AuthApiTest
```

Pengujian tersebut mencakup otorisasi tugas berdasarkan dosen, pembatasan tugas draft bagi mahasiswa, daftar mata kuliah yang sesuai peran, serta autentikasi token.
