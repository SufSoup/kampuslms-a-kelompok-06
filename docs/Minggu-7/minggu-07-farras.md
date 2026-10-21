# Catatan Minggu 7 — Autentikasi, Session, dan Policy

**Nama:** Muhammad Farras Kanz  
**NIM:** 10241047  
**Kelompok:** 06 Kelas A

---

## READ — Membedaah alur autentikasi

### 1. Berkas dan method yang menangani POST login

Route `POST /login` pada `routes/web.php` mengarah ke:

```php
[AuthController::class, 'login']
```

Jadi request login web ditangani oleh method `login(LoginRequest $request)` di `app/Http/Controllers/AuthController.php`.

### 2. Lokasi pemanggilan `Auth::attempt()`

Di method `login()`, kredensial yang telah divalidasi dikirim ke:

```php
if (Auth::attempt($credentials, $remember)) {
```

`Auth::attempt()` mencari pengguna berdasarkan email dan membandingkan password input dengan hash password yang tersimpan di database. Jika cocok, Laravel menandai pengguna sebagai sudah login.

### 3. Mengapa ada `session()->regenerate()`?

Tepat setelah login berhasil, controller menjalankan:

```php
$request->session()->regenerate();
```

Perintah ini mengganti ID session lama dengan ID session baru. Tujuannya mencegah *session fixation*: penyerang tidak dapat menyiapkan atau mengetahui ID session sebelum korban login, lalu memakai ID yang sama setelah korban berhasil login.

### 4. Di mana password di-hash?

Pada `app/Models/User.php` terdapat cast berikut:

```php
'password' => 'hashed',
```

Dengan cast ini, ketika password biasa diberikan ke atribut `password` lalu model disimpan, Laravel mengubahnya menjadi hash terlebih dahulu. Database tidak menyimpan password asli.

### 5. Perbandingan cookie sebelum dan sesudah login

Cookie session bernama `laravel_session`. Sebelum login, browser sudah dapat mempunyai session anonim. Setelah `Auth::attempt()` berhasil lalu session diregenerate, nilai cookie `laravel_session` berubah karena ID session baru dibuat.

Perubahan nilai inilah yang dapat dilihat melalui DevTools → Application → Cookies. Nilai cookie tidak perlu dibaca atau dibagikan; yang penting adalah nilainya berbeda sebelum dan sesudah login.

### 6. Logout lalu menekan tombol back

Logout menjalankan tiga hal: `Auth::logout()`, `session()->invalidate()`, dan `session()->regenerateToken()`. Session lama tidak lagi valid.

Saat tombol *back* ditekan, browser mungkin masih menampilkan tampilan halaman lama dari cache. Namun jika halaman direfresh atau pengguna mencoba mengirim request, middleware `auth` memeriksa session lagi dan mengarahkan pengguna ke login. Tampilan cache bukan bukti bahwa pengguna masih memiliki akses.

---

## BREAK — Delapan kerusakan keamanan

| # | Yang dicoba | Hasil yang perlu diamati dan penjelasan |
| --- | --- | --- |
| 1 | Menghapus `$request->session()->regenerate()` setelah login. | Session fixation terbuka. ID session sebelum login tetap dipakai sesudah login, sehingga penyerang yang sudah mengetahui ID tersebut berpotensi mengambil alih session. |
| 2 | Menghapus `Gate::authorize()` dari method `update`, tetapi membiarkan `@can` di Blade. | Tombol edit mungkin hilang, tetapi URL dan request PUT masih dapat dikirim manual. `@can` hanya mengatur tampilan; policy di controller yang benar-benar menghentikan akses dengan 403. |
| 3 | Sebagai dosen A, mengirim PUT ke mata kuliah dosen B lewat `curl`. | `CoursePolicy` atau policy terkait harus menolak jika dosen A bukan pengampu mata kuliah tersebut. Ini menguji otorisasi di server, bukan hanya tombol pada halaman. |
| 4 | Sebagai mahasiswa, membuka submission mahasiswa lain. | `SubmissionPolicy::view` membandingkan `submission.user_id` dengan ID pengguna login. Hasil yang benar adalah **403 Forbidden**. |
| 5 | Mengganti query index menjadi `Course::paginate()` polos, lalu login sebagai mahasiswa. | Daftar dapat memuat seluruh mata kuliah, termasuk yang tidak diikuti mahasiswa. Policy pada halaman detail saja tidak cukup; query daftar juga harus disaring berdasarkan role. |
| 6 | Mengirim `role=admin` pada form edit profil. | Field `role` tidak ada di `$fillable` model `User`, sehingga `fill()` tidak dapat mengubah role secara sembarangan. Pada manajemen user admin, role diambil dari data tervalidasi lalu ditetapkan secara eksplisit. |
| 7 | Menghapus cast `hashed`, membuat user baru, lalu melihat kolom password di database. | Password dapat tersimpan sebagai teks biasa jika tidak ada mekanisme hashing lain. Ini sangat berbahaya karena kebocoran database langsung membocorkan kata sandi pengguna. |
| 8 | Login, menyalin cookie session, lalu menempelkannya di browser lain. | Cookie session adalah bukti sesi login. Jika dicuri, orang lain dapat menyamar sebagai pengguna sampai session berakhir atau dibatalkan. Atribut `HttpOnly` mencegah JavaScript membaca cookie; `Secure` membuat cookie hanya dikirim melalui HTTPS. |

---

## Pelajaran utama: Blade bukan pagar keamanan

`@can` berguna untuk menyembunyikan tombol yang memang tidak boleh dipakai pengguna. Namun pengguna masih dapat mengetik URL sendiri, memakai Postman, atau mengirim `curl`. Karena itu, setiap method controller yang mengakses atau mengubah model harus memanggil `Gate::authorize()` atau memakai middleware/policy yang setara.

Singkatnya: **Blade mengatur pengalaman pengguna, sedangkan policy mengatur keamanan server.**
