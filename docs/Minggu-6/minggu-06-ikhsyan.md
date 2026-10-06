## Notes Minggu 6 Pemrograman Web

**Nama:** Muhammad Ikhsyan  
**NIM:** 10241049  
**Kelompok:** Kelompok 06 (Kelas A)  
**Topik:** REST API & Integrasi (Laravel 12, Sanctum, dan Fitur 17)

---

### READ — Bandingkan dua jalur (Web vs REST API)

#### 1. Jalankan `php artisan install:api`. Baca perubahan yang terjadi di `bootstrap/app.php`.
Jawaban :
Perubahan setelah menjalankan `php artisan install:api`:
- Package `laravel/sanctum` terpasang otomatis di `composer.json`.
- File migrasi `personal_access_tokens` dan file konfigurasi `config/sanctum.php` dibuat.
- File baru `routes/api.php` dibuat.
- Di `bootstrap/app.php`, rute API didaftarkan otomatis. Di sini kita tambahkan `apiPrefix: 'api/v1'` agar seluruh endpoint rapi memakai awalan versi 1.

#### 2 & 3. Bandingkan controller web dengan controller API. Apa yang sama dan apa yang berbeda?
Jawaban :
- Perbedaan:
  - Web mengembalikan tampilan HTML Blade (`view()`), sedangkan API mengembalikan data JSON melalui API Resource.
  - Web memakai session dan cookie browser, sedangkan API bersifat stateless memakai Bearer Token Sanctum di header request.
  - Jika validasi gagal, web melakukan redirect 302 kembali ke form dengan flash session. Di API, Laravel langsung merespons dengan JSON error status 422.
- Persamaan:
  - Logika bisnis di baliknya tetap sama, yaitu memakai Model Eloquent, Form Request untuk validasi input, dan otorisasi di server untuk mencegah IDOR.

#### 4. Panggil endpoint API tanpa header `Accept: application/json`. Lalu dengan header itu. Catat bedanya.
Jawaban :
- Tanpa header `Accept: application/json`: jika belum login atau validasi gagal, Laravel mengira request datang dari browser biasa sehingga mencoba redirect ke halaman login web. Jika rute login web belum siap, aplikasi error.
- Pakai header `Accept: application/json`: Laravel langsung tahu ini request API. Jika belum login langsung dibalas JSON 401 Unauthorized, dan jika validasi gagal dibalas JSON 422 tanpa redirect.

#### 5. Jalankan `php artisan route:list --path=api`. Cocokkan dengan kontrak di spesifikasi.
Jawaban :
Daftar route API yang sudah dibuat:

```text
POST       api/v1/auth/login .................... login dan ambil token
POST       api/v1/auth/logout .................. logout dan hapus token
GET|HEAD   api/v1/me ............................ ambil profil user yang sedang login
GET|HEAD   api/v1/courses ....................... daftar mata kuliah sesuai peran
GET|HEAD   api/v1/courses/{course} .............. detail mata kuliah
GET|HEAD   api/v1/courses/{course}/materials .... daftar materi perkuliahan
GET|HEAD   api/v1/courses/{course}/resources .... daftar kurasi sumber belajar (Fitur 17)
POST       api/v1/courses/{course}/resources .... dosen tambah kurasi referensi (Fitur 17)
POST       api/v1/resources/{resource}/toggle-read .. mahasiswa tandai sudah/belum baca (Fitur 17)
GET|HEAD   api/v1/courses/{course}/assignments .. daftar tugas mata kuliah
POST       api/v1/assignments ................... dosen buat tugas baru
PUT|PATCH  api/v1/assignments/{assignment} ...... dosen perbarui tugas
DELETE     api/v1/assignments/{assignment} ...... dosen hapus tugas
GET|HEAD   api/v1/assignments/{assignment}/submissions .. dosen lihat pengumpulan tugas
POST       api/v1/assignments/{assignment}/submissions .. mahasiswa upload tugas
PUT        api/v1/submissions/{submission}/grade ........ dosen beri nilai tugas
GET|HEAD   api/v1/notifications ................. daftar notifikasi akun
POST       api/v1/notifications/{notification}/read ..... tandai notifikasi sudah dibaca
```

---

### BREAK — Tujuh kerusakan

> Kolom **Prediksi** diisi sebelum mencoba. Kolom **Hasil sebenarnya** diisi setelah eksperimen.

| # | Yang dicoba | Prediksi sebelum mencoba | Hasil sebenarnya |
|---|-------------|--------------------------|-----------------|
| 1 | Kembalikan `response()->json(User::all())` di endpoint uji | Hash password ikut tampil | Seluruh data rahasia tampil di JSON, termasuk hash password bcrypt dan remember_token. Sangat berbahaya jika sampai diakses publik. |
| 2 | Hapus middleware `auth:sanctum` dari rute API | Endpoint bisa dibuka tanpa token | Data privat bisa diakses siapa saja lewat URL tanpa perlu login atau menyertakan token. |
| 3 | Panggil endpoint pakai token yang sudah di-logout | Request ditolak | Muncul 401 Unauthorized karena token sudah terhapus dari tabel `personal_access_tokens`. |
| 4 | Login sebagai mahasiswa, panggil endpoint kurasi atau buat tugas milik dosen | Ditolak karena beda peran | Muncul 403 Forbidden. Server mengenali user tapi menolak karena perannya bukan dosen pengampu. |
| 5 | Hapus eager loading (`with(...)`) pada pemanggilan koleksi | Query database jadi banyak | Terjadi N+1 query. Setiap baris memicu query SQL terpisah ke database sehingga respons API jadi lambat. |
| 6 | Hapus `throttle` dari route login, kirim 50 percobaan beruntun | Semua percobaan diproses | Percobaan login terus diproses tanpa henti. Ini rawan serangan brute force tebak kata sandi. |
| 7 | Bedakan pesan error antara email tidak ada dan password salah | Bisa tahu email mana yang terdaftar | Terjadi user enumeration. Penyerang bisa membedakan mana email yang sudah terdaftar di sistem dari pesan errornya. |

---

### BUILD — Struktur REST API KampusLMS

1. **Autentikasi Sanctum:**  
   Login di `/api/v1/auth/login` menghasilkan token plaintext Sanctum. Token ini dipakai di header `Authorization: Bearer <token>` untuk mengakses endpoint terproteksi. Saat logout di `/api/v1/auth/logout`, token aktif langsung dihapus dari database. Data user diambil lewat `/api/v1/me`.

2. **Modul Mata Kuliah & Tugas:**  
   Semua endpoint dibatasi otorisasi peran. Dosen hanya mengelola mata kuliah dan tugas yang dia ampu, sementara mahasiswa hanya melihat mata kuliah yang diikutinya. Pengumpulan tugas dan penilaian dilindungi agar mahasiswa tidak bisa melihat berkas milik mahasiswa lain.

3. **Fitur 17 (Kurasi Sumber Belajar & Penanda Baca):**  
   Dosen pengampu bisa menambahkan referensi belajar di `POST /api/v1/courses/{course}/resources`. Mahasiswa yang terdaftar bisa menandai materi sudah atau belum dibaca lewat `POST /api/v1/resources/{resource}/toggle-read`. Penanda baca ini terikat ke `auth()->id()` mahasiswa yang sedang login agar tidak bisa dimanipulasi orang lain.

4. **Keamanan & Resource:**  
   Semua respons memakai API Resource agar data sensitif seperti password hash tidak bocor. Endpoint login dibatasi `throttle:5,1` untuk mencegah brute force, dan pesan gagal login diseragamkan menjadi "Email atau kata sandi salah." untuk menutup celah enumerasi akun.

---

### CHECKPOINT MINGGU 6

#### 1. Kenapa mengembalikan model mentah berbahaya? Peragakan kebocorannya.
Jawaban :
Mengembalikan model mentah (`response()->json($user)`) akan mengubah semua kolom tabel menjadi JSON tanpa filter. Akibatnya, data sensitif seperti hash password dan token rahasia bisa bocor ke klien.
Contoh peragaan:
- Model mentah: `{"id": 1, "name": "Budi", "password": "$2y$12$KIXeJ1...", "remember_token": "abc"}`
- Pakai API Resource: `{"id": 1, "name": "Budi", "email": "budi@kampuslms.test", "role": "mahasiswa"}`

#### 2. Apa beda 401 dan 403? Tunjukkan di API Anda satu contoh masing-masing.
Jawaban :
- 401 Unauthorized artinya masalah autentikasi (belum login atau token tidak valid). Contoh: Mengakses `GET /api/v1/me` tanpa membawa Bearer token.
- 403 Forbidden artinya masalah otorisasi (sudah login tapi tidak punya hak akses). Contoh: Mahasiswa mencoba menambah kurasi materi dosen di `POST /api/v1/courses/1/resources`.

#### 3. Kenapa `routes/api.php` tidak ada secara default di Laravel 12? Bagaimana mengaktifkannya?
Jawaban :
Laravel 12 sengaja dibuat ramping sejak awal agar tidak membebani aplikasi web biasa yang tidak memerlukan REST API. Untuk mengaktifkannya, jalankan perintah `php artisan install:api`. Perintah ini otomatis memasang Sanctum, membuat file `routes/api.php`, dan mendaftarkannya di `bootstrap/app.php`.

#### 4. Apa fungsi `whenLoaded()`? Apa yang terjadi tanpanya?
Jawaban :
Fungsi `whenLoaded('relasi')` memastikan data relasi hanya disertakan ke JSON jika relasi tersebut sudah di-load di query controller (*eager loading*). Tanpa fungsi ini, relasi akan di-query manual satu per satu di setiap baris (*lazy loading*), sehingga memicu masalah N+1 query yang membuat API lambat.

#### 5. Kenapa pesan gagal login tidak boleh membedakan email salah dan password salah?
Jawaban :
Jika dibedakan, penyerang bisa menebak akun mana saja yang terdaftar di kampus (*user enumeration*). Penyerang cukup mencoba berbagai email; jika muncul "password salah", artinya email itu valid. Karena itu, pesan wajib diseragamkan menjadi "Email atau kata sandi salah."

#### 6. Kenapa endpoint login wajib di-throttle? Berapa nilai yang Anda pakai dan mengapa?
Jawaban :
Pengecekan password memakai hash bcrypt yang berat untuk CPU. Tanpa rate limiting, penyerang bisa mengirim ribuan tebakan password per menit (*brute force*) atau membuat server down. Di sini kita memakai `throttle:5,1`, artinya maksimal 5 kali percobaan per menit per IP. Nilai ini cukup aman untuk pengguna yang salah ketik, tapi langsung memblokir bot otomatis.
