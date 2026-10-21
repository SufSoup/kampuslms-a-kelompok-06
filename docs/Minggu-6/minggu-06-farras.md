# Catatan Minggu 6 — API, Sanctum, Resource, dan Keamanan JSON

**Nama:** Muhammad Farras Kanz  
**NIM:** 10241047  
**Kelompok:** 06 Kelas A

---

## READ — Membandingkan jalur web dan API

### 1. Hasil pemasangan API

Perintah `php artisan install:api` digunakan Laravel untuk memasang kebutuhan API, terutama Sanctum dan file route API. Pada repository ini, API sudah terpasang sehingga perintah tidak dijalankan ulang agar konfigurasi serta dependensi yang ada tidak berubah.

Perubahan penting yang sudah terlihat pada `bootstrap/app.php` adalah:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    apiPrefix: 'api/v1',
)
```

Artinya, route di `routes/api.php` diberi awalan `/api/v1`. Contohnya, route `/courses` di file API dapat dipanggil sebagai `GET /api/v1/courses`.

### 2. Endpoint `GET /api/v1/courses`

Endpoint sudah ada pada `routes/api.php` dan berada dalam grup middleware berikut:

```php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/courses', [CourseController::class, 'index']);
});
```

Method `Api\CourseController@index` mengambil mata kuliah sesuai role pengguna, memuat relasi dosen dengan eager loading, menghitung jumlah materi/tugas, lalu mengembalikan `CourseResource::collection($courses)`.

Contoh pemanggilan:

```bash
curl -H "Accept: application/json" \
     -H "Authorization: Bearer TOKEN_SANCTUM" \
     http://127.0.0.1:8000/api/v1/courses
```

### 3. Perbandingan controller web dan controller API

| Bagian | `CourseController` web | `Api\CourseController` |
| --- | --- | --- |
| Data yang ditampilkan | Mata kuliah sesuai role pengguna | Mata kuliah sesuai role pengguna |
| Query utama | Menggunakan scope role, eager loading `lecturer`, dan pagination | Menggunakan scope role, eager loading `lecturer`, count materi/tugas, dan pagination |
| Filter pencarian | Mendukung query `q` dan `status` | Belum menyediakan filter `q` dan `status` |
| Bentuk respons | `view('courses.index', ...)` untuk HTML | `CourseResource::collection(...)` untuk JSON |
| Akses detail | `Gate::authorize('view', $course)` | `Gate::authorize('view', $course)` |
| Tujuan | Halaman browser untuk pengguna | Kontrak data untuk frontend lain, mobile app, atau client API |

Kesamaannya adalah keduanya membatasi daftar mata kuliah berdasarkan role dan mencegah akses detail yang tidak berhak melalui policy. Bedanya ada pada bentuk respons: web mengirim HTML, sedangkan API mengirim JSON yang bentuknya dikontrol oleh Resource.

### 4. Perbedaan header `Accept: application/json`

Untuk respons sukses dari endpoint course, hasilnya tetap JSON karena controller mengembalikan `CourseResource`. Namun client API tetap perlu mengirim header berikut:

```http
Accept: application/json
```

Header ini menyatakan bahwa client mengharapkan JSON, khususnya saat terjadi error validasi, autentikasi, atau exception. Tanpa header tersebut, aplikasi Laravel pada konfigurasi umum dapat memilih respons HTML atau redirect untuk beberapa jenis error. Pada proyek ini, handler exception juga secara khusus mengirim JSON untuk path `api/*` pada error 403 dan validasi 422, tetapi header tetap merupakan bagian kontrak API yang baik.

### 5. Hasil `php artisan route:list --path=api`

```text
POST       api/v1/assignments
PUT|PATCH  api/v1/assignments/{assignment}
DELETE     api/v1/assignments/{assignment}
GET|HEAD   api/v1/assignments/{assignment}/submissions
POST       api/v1/assignments/{assignment}/submissions
POST       api/v1/auth/login
POST       api/v1/auth/logout
GET|HEAD   api/v1/courses
GET|HEAD   api/v1/courses/{course}
GET|HEAD   api/v1/courses/{course}/assignments
GET|HEAD   api/v1/courses/{course}/materials
GET|HEAD   api/v1/courses/{course}/resources
POST       api/v1/courses/{course}/resources
GET|HEAD   api/v1/me
GET|HEAD   api/v1/notifications
POST       api/v1/notifications/{notification}/read
GET|HEAD   api/v1/resources/{resource}
POST       api/v1/resources/{resource}/toggle-read
PUT        api/v1/submissions/{submission}/grade
```

Jumlahnya 19 route. Kontrak utamanya sesuai: login berada di `/api/v1/auth/login`, endpoint mata kuliah berada di `/api/v1/courses`, dan endpoint yang membutuhkan data pengguna berada di balik `auth:sanctum`.

---

## BREAK — Tujuh kerusakan API

| # | Yang dicoba | Hasil yang perlu diamati dan alasannya |
| --- | --- | --- |
| 1 | Mengembalikan `response()->json(User::all())` pada endpoint uji. | Semua atribut model yang tidak disembunyikan dapat muncul di JSON, seperti email, role, NIM/NIP, dan timestamp. Model `User` proyek ini saat ini belum mendeklarasikan `$hidden`; karena itu password hash juga berisiko ikut terserialisasi bila model mentah dikembalikan. `UserResource` harus dipakai agar field respons dipilih dengan sengaja. |
| 2 | Menghapus `auth:sanctum` dari grup route lalu memanggil endpoint tanpa token. | Endpoint yang sebelumnya privat menjadi dapat dipanggil publik. Token Sanctum tidak lagi diperiksa. |
| 3 | Memanggil endpoint terlindungi memakai token yang sudah dihapus saat logout. | Respons harus **401 Unauthorized** karena token tidak lagi ditemukan atau tidak valid. |
| 4 | Login sebagai mahasiswa lalu memanggil `POST /api/v1/assignments`. | Respons harus **403 Forbidden**, bukan 401. Mahasiswa sudah berhasil dikenali oleh token, tetapi tidak memiliki izin membuat assignment. |
| 5 | Menghapus eager loading lalu memanggil daftar mata kuliah dengan relasi dosen. | Terjadi N+1 query: satu query untuk courses, lalu query tambahan untuk dosen setiap course. `with('lecturer')` menghindari masalah ini. |
| 6 | Menghapus `throttle:5,1` dari route login lalu mengirim 50 percobaan login. | Tidak ada pembatasan percobaan brute force dari route. Rate limit membuat endpoint login menolak request berlebihan dalam satu menit. |
| 7 | Membuat pesan login berbeda untuk email salah dan password salah. | Penyerang dapat menebak email mana yang terdaftar (*user enumeration*). Pesan umum “Email atau kata sandi salah.” menjaga informasi tersebut tetap tersembunyi. |

---

## Kesimpulan

API bukan sekadar controller web yang mengembalikan `json()`. API membutuhkan kontrak respons yang stabil melalui Resource, autentikasi token melalui Sanctum, otorisasi yang membedakan 401 dan 403, eager loading untuk mencegah N+1, serta rate limit pada login. Model Eloquent tidak boleh dikembalikan mentah karena field sensitif dapat bocor.
