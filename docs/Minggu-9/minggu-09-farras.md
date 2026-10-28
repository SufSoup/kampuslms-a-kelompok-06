# Catatan Minggu 9 — Pengelolaan Berkas dan Keamanan Download

**Nama:** Muhammad Farras Kanz  
**NIM:** 10241047  
**Kelompok:** 06 Kelas A

---

## READ — Menelusuri satu berkas materi

Saya menggunakan satu contoh berkas materi yang diunggah ke aplikasi. Pada proyek ini, file materi disimpan dengan disk `local`, yaitu storage privat.

### 1. Di mana berkas fisiknya disimpan?

Jika kolom `file_path` bernilai:

```text
materials/1/namafile-acak.pdf
```

maka lokasi fisiknya adalah:

```text
lms/storage/app/private/materials/1/namafile-acak.pdf
```

Folder `storage/app/private` berada di luar folder `public`. Karena itu browser tidak dapat mengambil file ini langsung hanya dengan menebak URL.

### 2. Apa perbedaan `file_path` di database dengan lokasi file fisik?

Kolom `file_path` pada tabel `materials` hanya menyimpan path relatif terhadap root disk `local`, bukan alamat lengkap komputer/server.

| Data | Contoh nilai |
| --- | --- |
| `file_path` di database | `materials/1/namafile-acak.pdf` |
| Lokasi file fisik | `lms/storage/app/private/materials/1/namafile-acak.pdf` |

Laravel mengetahui root disk `local` dari konfigurasi, lalu menggabungkannya dengan `file_path` saat file diunduh. Dengan cara ini, struktur lengkap folder server tidak perlu disimpan di database atau diketahui pengguna.

### 3. Apakah file dapat dibuka langsung dengan menebak URL?

Tidak. Contohnya, membuka URL berikut tidak akan langsung menampilkan file:

```text
http://127.0.0.1:8000/storage/materials/1/namafile-acak.pdf
```

URL `/storage` hanya mengarah ke `storage/app/public` setelah menjalankan `storage:link`, sedangkan materi proyek ini ada di `storage/app/private`.

Untuk mengunduh materi, pengguna harus menggunakan route aplikasi. Route tersebut memeriksa login dan izin akses melalui `Gate::authorize()`. Jadi, mengetahui ID materi saja tidak cukup untuk mengunduhnya.

### 4. Apa perbedaan disk `local` dan `public`?

Konfigurasinya ada di `lms/config/filesystems.php`.

| Disk | Lokasi root | Bisa diakses langsung dari browser? | Kegunaan |
| --- | --- | --- | --- |
| `local` | `storage/app/private` | Tidak | File sensitif, misalnya submission mahasiswa dan materi kuliah |
| `public` | `storage/app/public` | Bisa, setelah `storage:link` | File yang memang boleh terbuka untuk umum, misalnya gambar publik |

Disk `local` dipakai ketika aplikasi harus memeriksa siapa yang berhak membaca file. Disk `public` dipakai hanya ketika semua orang boleh mengakses file tersebut.

### 5. Apa yang dibuat oleh `php artisan storage:link` dan mengapa materi tidak boleh ada di sana?

Perintah berikut:

```bash
php artisan storage:link
```

membuat symbolic link:

```text
public/storage -> storage/app/public
```

Sesudah link dibuat, file di `storage/app/public` dapat diakses lewat URL `/storage/...`. Akses ini tidak melewati controller, login, atau policy. Karena itu materi kuliah tidak boleh disimpan di sana: siapa pun dapat membukanya jika tahu atau berhasil menebak URL file.

---

## BREAK — Delapan kerusakan yang perlu dipahami

Tujuan bagian ini adalah melihat akibat jika pengamanan file dihilangkan. Nomor 1 sudah diverifikasi menggunakan file uji pada storage publik: URL `/storage/week9-public-access-check.txt` dapat dibuka tanpa autentikasi dan mengembalikan HTTP 200. Ini menjelaskan mengapa submission tidak boleh disimpan di disk `public`.

| # | Yang dicoba | Hasil yang diamati dan penjelasan |
| --- | --- | --- |
| 1 | Memindahkan submission ke `storage/app/public`, lalu membuka URL file dari jendela penyamaran tanpa login. | File dapat dibuka tanpa login. Artinya, tugas seluruh kelas berisiko terbuka untuk publik karena web server langsung melayani file dari folder publik tanpa pemeriksaan Laravel. |
| 2 | Menghapus `Gate::authorize()` dari endpoint download lalu memakai ID submission mahasiswa lain. | Mahasiswa lain dapat mengunduh file yang bukan miliknya. Ini disebut IDOR (*Insecure Direct Object Reference*): cukup mengganti ID pada URL untuk mengakses data orang lain. |
| 3 | Menyimpan file dengan `storeAs()` memakai nama asli, lalu dua mahasiswa mengunggah `tugas.pdf` ke folder tugas yang sama. | File mahasiswa kedua dapat menimpa file mahasiswa pertama karena nama dan lokasi fisiknya sama. Karena itu proyek memakai `$file->store()` yang membuat nama fisik acak. |
| 4 | Mengunggah file PHP yang diganti nama menjadi `.pdf`, lalu membandingkan rule `extensions` dan `mimes`. | `extensions` hanya melihat akhiran nama file sehingga lebih mudah dikelabui. `mimes` memeriksa tipe file yang dikenali Laravel, sehingga lebih tepat untuk validasi upload. |
| 5 | Mengganti `max:5120` menjadi `max:5`, lalu mengunggah file 1 MB. | File 1 MB ditolak karena `max` untuk file di Laravel memakai kilobyte. `max:5` berarti 5 KB, sedangkan `max:5120` berarti 5 MB. |
| 6 | Mengatur `upload_max_filesize=1M` di `php.ini`, lalu mengunggah file 3 MB saat rule Laravel masih `max:5120`. | Upload ditolak lebih dahulu oleh PHP/server. Validasi Laravel tidak sempat memproses file karena batas server 1 MB lebih kecil daripada batas aplikasi 5 MB. |
| 7 | Mengganti `updateOrCreate()` menjadi `create()`, lalu mahasiswa mengumpulkan tugas yang sama dua kali. | Database menolak data kedua karena tabel `submissions` memiliki unique constraint untuk pasangan `assignment_id` dan `user_id`. Constraint ini mencegah satu mahasiswa memiliki dua submission untuk tugas yang sama. |
| 8 | Menghapus satu baris submission langsung lewat SQL, lalu memeriksa folder storage. | File fisiknya tetap ada walaupun data database sudah hilang. File seperti ini disebut *orphan file* atau berkas yatim dan dapat memenuhi storage jika tidak dibersihkan. |

---

## Kesimpulan

Keamanan file tidak cukup hanya dengan menyembunyikan tombol download di halaman. File harus disimpan di disk privat, akses download harus melalui controller, dan controller wajib memeriksa izin pengguna. Dengan begitu, mengetahui URL atau ID file saja tidak cukup untuk membuka file milik orang lain.
