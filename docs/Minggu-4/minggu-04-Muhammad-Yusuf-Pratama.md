# Dokumentasi Praktik Minggu 4 — State, Validasi, dan Pagination

## Nama: Muhammad Yusuf Pratama

## NIM: 10241053

## READ — Siklus form tidak valid

HTTP tidak menyimpan konteks antarrekuest dengan sendirinya. Pada form web, Laravel menggunakan session untuk mempertahankan pesan validasi dan input lama setelah redirect. Filter daftar seperti kata kunci dan status lebih sesuai ditempatkan pada query string agar URL dapat dibagikan dan pagination tetap membawa filter yang sama.

Untuk operasi simpan, alurnya adalah browser mengirim `POST`, Form Request menjalankan validasi sebelum method controller, dan Laravel kembali ke halaman form jika validasi gagal. Blade dapat menampilkan pesan melalui `@error` dan mempertahankan nilai dengan `old()`. Jika penyimpanan berhasil, controller sebaiknya melakukan redirect dengan flash message (pola Post-Redirect-Get/PRG), bukan mengembalikan halaman hasil langsung dari request POST.

## BREAK — Risiko jika perlindungan form dihilangkan

| Perubahan | Akibat |
|---|---|
| Menghapus `@csrf` | Laravel menolak request web tanpa token CSRF yang valid. |
| Menyimpan seluruh payload tanpa whitelist | Atribut yang tidak dimaksudkan untuk form dapat ikut diproses. |
| Menghilangkan validasi foreign key | Referensi ke dosen yang tidak tersedia berisiko masuk ke proses simpan. |
| Tidak membatasi nilai status | Nilai di luar daftar status yang sah dapat diterima. |
| Menghapus `withQueryString()` | Pindah halaman dapat menghilangkan pencarian atau filter aktif. |
| Mengembalikan view setelah POST berhasil | Refresh browser berisiko mengirim ulang operasi simpan. |
| Menghapus `old()` dari form | Pengguna perlu memasukkan ulang seluruh data setelah satu kesalahan validasi. |

## BUILD — Penerapan pada KampusLMS

### Validasi

`StoreCourseRequest` dan `UpdateCourseRequest` mengatur validasi kode, nama, deskripsi, SKS, dosen, dan status. Nilai `lecturer_id` harus ditemukan di tabel `users`, status dibatasi pada `draft`, `active`, atau `archived`, dan aturan `unique` pada update mengecualikan record yang sedang diedit.

Controller mengambil data tervalidasi, bukan seluruh request. `CourseController::index()` juga:

- menyimpan pencarian `q` dan filter `status` di URL;
- membatasi hasil query sesuai peran pengguna;
- melakukan eager loading dosen pengampu;
- menggunakan pagination 15 data per halaman dan `withQueryString()`.

Request materi mempunyai validasi kondisional: URL wajib ketika tipe materi berupa tautan; berkas wajib ketika tipenya file, dengan format dan ukuran yang dibatasi. Redirect dan pesan flash setelah operasi berhasil mengikuti pola PRG.

### Catatan pemisahan validasi dan otorisasi

Validasi memastikan data yang dikirim berbentuk benar dan sesuai aturan. Otorisasi menjawab apakah pengguna tertentu boleh melakukan tindakan tersebut. Pada controller mata kuliah, pemanggilan `Gate::authorize()` menjadi pemeriksaan akses di backend; penyembunyian tombol di Blade saja tidak cukup. Beberapa Form Request course masih mengembalikan `true` pada `authorize()` dengan catatan implementasi Policy, sehingga otorisasi controller tetap penting.

## Checkpoint

- Validasi sisi browser membantu pengalaman pengguna tetapi tidak dipercaya sebagai batas keamanan.
- `$request->validated()` membatasi data aplikasi pada field yang lolos aturan.
- PRG mencegah pengiriman ulang operasi POST saat halaman di-refresh.
- Query string sesuai untuk pencarian dan filter; `withQueryString()` mempertahankannya di tautan pagination.
- `@csrf` melindungi request web dari pemalsuan lintas situs, bukan dari mass assignment.

## Pengujian

Dari direktori `lms/`, pengujian filter dan pagination dapat dijalankan dengan:

```bash
php artisan test --filter=SearchAndPaginationTest
```

Skenario yang perlu diverifikasi mencakup hasil pencarian, filter status, nilai filter pada tautan halaman berikutnya, dan penolakan input yang tidak sesuai aturan.
