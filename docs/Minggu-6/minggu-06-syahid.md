# REST API dan Integrasi

## Notes Minggu 6 Pemrograman Web

**Nama:** Muhammad Syahid Ridho  
**NIM:** 10241051  
**Kelompok:** Kelompok 06 (Kelas A)  
**Topik:** REST API dan Integrasi

---

## READ — Bandingkan Dua Jalur

### 1. Menjalankan `php artisan install:api`

Perintah:

```bash
php artisan install:api
```

digunakan untuk mengaktifkan dukungan API pada Laravel.

Setelah perintah dijalankan, Laravel menyiapkan beberapa komponen untuk REST API, yaitu:

- `routes/api.php` untuk mendefinisikan route API.
- Sanctum untuk autentikasi API menggunakan token.
- Migration untuk `personal_access_tokens`.
- Konfigurasi API pada `bootstrap/app.php`.

Pada project KampusLMS, API menggunakan prefix:

```text
/api/v1
```

Sehingga endpoint API dapat dibedakan dari route web.

---

### 2. Membuat endpoint `GET /api/v1/courses`

Endpoint `GET /api/v1/courses` digunakan untuk mengambil data mata kuliah dari database dan mengembalikannya dalam format JSON.

REST API menggunakan JSON karena data tersebut dapat digunakan oleh berbagai client, seperti frontend web, aplikasi mobile, maupun aplikasi lainnya.

---

### 3. Perbandingan API dengan Web Controller

API dan web sama-sama dapat menggunakan Model dan database Laravel, tetapi cara memberikan response berbeda.

| Web | REST API |
|---|---|
| Menggunakan View/Blade | Menggunakan JSON |
| Menggunakan session/cookie | Menggunakan Bearer Token |
| Dapat melakukan redirect | Mengembalikan HTTP status dan JSON |
| Ditujukan untuk halaman web | Ditujukan untuk client/aplikasi |
| Menggunakan CSRF untuk request tertentu | Tidak menggunakan CSRF seperti web |

Contohnya, `CourseController` pada web dapat mengembalikan halaman Blade, sedangkan Controller API mengembalikan data dalam bentuk JSON.

---

### 4. Penggunaan Header `Accept: application/json`

Header:

```text
Accept: application/json
```

digunakan untuk memberitahu server bahwa client mengharapkan response dalam format JSON.

Pada REST API, penggunaan header tersebut membantu Laravel memberikan response yang sesuai dengan kebutuhan API, terutama ketika terjadi error validasi atau autentikasi.

---

### 5. Mengecek Route API

Route API dapat diperiksa menggunakan:

```bash
php artisan route:list --path=api
```

Perintah tersebut digunakan untuk memastikan endpoint API sudah terdaftar dan sesuai dengan kontrak API.

Pada project KampusLMS, route API menggunakan prefix:

```text
/api/v1
```

---

# BREAK — Tujuh Kerusakan

## 1. Mengembalikan Model Mentah

Contoh:

```php
return response()->json(User::all());
```

Hal ini berbahaya karena seluruh atribut model dapat ikut dikirim ke client.

Jika data sensitif tidak dilindungi, informasi seperti password hash dapat ikut terekspos.

Solusinya adalah menggunakan **API Resource** agar hanya field yang diperlukan yang dikirimkan.

---

## 2. Menghapus `auth:sanctum`

Jika middleware:

```text
auth:sanctum
```

dihapus dari route yang seharusnya terlindungi, endpoint dapat diakses tanpa token.

Hal ini menyebabkan API menjadi terbuka untuk pengguna yang tidak terautentikasi.

Seharusnya endpoint yang membutuhkan autentikasi menggunakan:

```php
Route::middleware('auth:sanctum')->group(function () {
    // protected API routes
});
```

---

## 3. Menggunakan Token yang Tidak Valid

Jika client menggunakan token yang sudah tidak valid atau sudah dihapus, request harus ditolak.

Response yang sesuai adalah:

```text
401 Unauthorized
```

Hal ini menunjukkan bahwa pengguna belum berhasil melakukan autentikasi.

---

## 4. Mahasiswa Mengakses Endpoint Assignment

Mahasiswa mencoba:

```text
POST /api/v1/assignments
```

Endpoint tersebut digunakan untuk membuat assignment sehingga hanya dosen yang seharusnya dapat menggunakannya.

Jika mahasiswa sudah terautentikasi tetapi tidak memiliki hak akses, response yang benar adalah:

```text
403 Forbidden
```

Perbedaan penting:

- **401** → belum terautentikasi.
- **403** → sudah terautentikasi tetapi tidak mempunyai izin.

---

## 5. Menghapus Eager Loading

Jika eager loading dihilangkan, misalnya:

```php
$assignment->load('creator');
```

tidak digunakan, pemanggilan relasi pada Resource dapat menyebabkan query tambahan.

Jika terdapat banyak data, hal tersebut dapat menyebabkan **N+1 Query Problem**, yaitu jumlah query database menjadi jauh lebih banyak daripada yang seharusnya.

Eager loading digunakan untuk mengambil relasi yang diperlukan secara lebih efisien.

---

## 6. Menghapus Throttle Login

Login tanpa rate limiting memungkinkan client melakukan percobaan login berkali-kali.

Hal tersebut dapat meningkatkan risiko **brute force attack**.

Karena itu endpoint login perlu diberikan rate limit, misalnya:

```text
throttle:5,1
```

Artinya percobaan login dibatasi maksimal 5 kali dalam satu menit.

---

## 7. Membedakan Error Email dan Password

Jika sistem memberikan pesan berbeda antara email yang tidak terdaftar dan password yang salah, penyerang dapat mengetahui akun mana yang terdaftar.

Contoh yang tidak aman:

```text
Email tidak ditemukan
```

berbeda dengan:

```text
Password salah
```

Hal tersebut dapat menyebabkan **user enumeration**.

Sebaiknya sistem memberikan pesan yang bersifat umum, misalnya:

```text
Email atau kata sandi salah.
```

Dengan demikian, sistem tidak memberikan informasi apakah email tersebut terdaftar atau tidak.
