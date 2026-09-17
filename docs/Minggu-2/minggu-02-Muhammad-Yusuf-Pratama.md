## Nama : Muhammad Yusuf Pratama

## NIM : 10241053

# Dokumentasi Minggu 2 Pemrograman Web

## READ - Telusuri satu request penuh

### 1. Baris mana di `routes/web.php` yang menangkap route `/tentang`?

Route `/tentang` ditangkap oleh baris berikut pada file `routes/web.php`:

```php
Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');
```

Route tersebut menggunakan method `GET`. Ketika pengguna membuka URL `/tentang`, Laravel menjalankan closure dan mengembalikan view `tentang`.

### 2. Kalau ditangani controller, berkas dan method mana?

Route `/tentang` belum ditangani oleh controller. Route tersebut masih menggunakan closure yang ditulis langsung di `routes/web.php`.

Berbeda dengan route CRUD, route mata kuliah dan pengguna ditangani oleh controller menggunakan resource route:

```php
Route::resource('courses', CourseController::class);
Route::resource('users', UserController::class);
```

Route tersebut menghubungkan operasi CRUD ke `CourseController` dan `UserController`, seperti `index`, `create`, `store`, `show`, `edit`, `update`, dan `destroy`.

### 3. View mana yang dikembalikan? Di path apa persisnya?

Route `/tentang` mengembalikan view `tentang`:

```php
return view('tentang');
```

Laravel mencari view tersebut pada path:

```text
resources/views/tentang.blade.php
```

Untuk route daftar mata kuliah, controller mengembalikan view berikut:

```php
return view('courses.index', compact('courses'));
```

Path view-nya adalah:

```text
resources/views/courses/index.blade.php
```

### 4. Layout apa yang membungkusnya?

Halaman CRUD menggunakan komponen layout `x-layout`, yang berasal dari file:

```text
resources/views/components/layout.blade.php
```

Contoh penggunaannya:

```blade
<x-layout title="Daftar Mata Kuliah">
    <!-- isi halaman -->
</x-layout>
```

Isi halaman ditempatkan pada bagian `{{ $slot }}` di dalam layout. Layout juga menyediakan navigasi ke Dashboard, Mata Kuliah, Pengguna, dan Tentang.

View `tentang.blade.php` masih berdiri sendiri dan belum menggunakan komponen `x-layout`.

### 5. Resource route CRUD

Resource route untuk mata kuliah dan pengguna membuat beberapa route secara otomatis:

```php
Route::resource('courses', CourseController::class);
Route::resource('users', UserController::class);
```

Operasi yang tersedia meliputi:

| Method | URI | Method Controller | Fungsi |
|---|---|---|---|
| GET | `/courses` | `index` | Menampilkan daftar mata kuliah |
| GET | `/courses/create` | `create` | Menampilkan form tambah mata kuliah |
| POST | `/courses` | `store` | Menyimpan mata kuliah baru |
| GET | `/courses/{course}` | `show` | Menampilkan detail mata kuliah |
| GET | `/courses/{course}/edit` | `edit` | Menampilkan form edit |
| PUT/PATCH | `/courses/{course}` | `update` | Memperbarui mata kuliah |
| DELETE | `/courses/{course}` | `destroy` | Menghapus mata kuliah |

Route yang sama juga dibuat untuk resource `users` dengan `UserController`.

## BREAK - Analisis kemungkinan kerusakan

| # | Yang dirusak | Prediksi sebelum mencoba |
|---|---|---|
| 1 | Mengubah `Route::get('/tentang')` menjadi `Route::post('/tentang')` | Browser yang membuka halaman dengan method GET akan mendapatkan error `405 Method Not Allowed`. |
| 2 | Menghapus `Route::resource('courses', CourseController::class)` | Semua route CRUD mata kuliah tidak lagi terdaftar dan pemanggilan `route('courses.index')` akan gagal. |
| 3 | Mengubah nama view `courses.index` menjadi view yang tidak ada | Laravel akan menampilkan error `View [courses.index] not found`. |
| 4 | Menghapus validasi `unique:courses,code` | Kode mata kuliah yang sama dapat disimpan lebih dari satu kali. |
| 5 | Menghapus parameter `Course $course` pada method `show` | Route model binding tidak dapat digunakan untuk mengambil data mata kuliah berdasarkan parameter route. |
| 6 | Menghapus `@csrf` dari form POST | Laravel akan menolak request dengan error `419 Page Expired` karena token CSRF tidak tersedia. |
| 7 | Mengganti `{{ $course->name }}` dengan `{!! $course->name !!}` | Data HTML atau script dari pengguna dapat dirender langsung dan menimbulkan risiko XSS. |
| 8 | Menghapus `{{ $slot }}` dari layout | Isi setiap halaman yang menggunakan `<x-layout>` tidak akan ditampilkan. |

## BUILD - Implementasi CRUD KampusLMS

### 1. Resource route

File `routes/web.php` menggunakan resource route untuk menghubungkan URL dengan controller:

```php
use App\Http\Controllers\CourseController;
use App\Http\Controllers\UserController;

Route::resource('courses', CourseController::class);
Route::resource('users', UserController::class);
```

Dengan konfigurasi tersebut, Laravel menyediakan route standar untuk melihat, menambah, mengubah, dan menghapus data mata kuliah serta pengguna.

### 2. `CourseController`

`CourseController` menangani proses CRUD mata kuliah. Method `index` mengambil data mata kuliah beserta dosen pengampunya:

```php
public function index()
{
    $courses = Course::with('lecturer')->latest()->get();

    return view('courses.index', compact('courses'));
}
```

Method `store` melakukan validasi sebelum menyimpan data baru. Beberapa aturan validasinya adalah kode mata kuliah harus unik, SKS harus berupa angka, dosen harus tersedia pada tabel users, dan status harus bernilai `draft`, `active`, atau `archived`.

Method `show`, `edit`, `update`, dan `destroy` menggunakan route model binding dengan parameter `Course $course`.

### 3. `UserController`

`UserController` menangani CRUD pengguna. Data pengguna divalidasi sebelum disimpan, termasuk email yang harus unik dan role yang hanya boleh berupa:

```text
admin, dosen, mahasiswa
```

Pada saat memperbarui pengguna, password boleh dikosongkan. Jika password kosong, password lama tidak diubah.

### 4. Komponen layout

File `resources/views/components/layout.blade.php` menyediakan struktur HTML utama dan navigasi bersama:

```blade
<x-layout title="Daftar Pengguna">
    <!-- isi halaman pengguna -->
</x-layout>
```

Komponen tersebut menggunakan `{{ $slot }}` sebagai tempat untuk menampilkan isi view yang membungkusnya.

### 5. Validasi dan keamanan form

Form CRUD menggunakan beberapa perlindungan Laravel:

- `@csrf` untuk melindungi request terhadap CSRF.
- `@method('PUT')` atau `@method('DELETE')` untuk method selain GET dan POST.
- `{{ }}` untuk melakukan escaping output Blade.
- Validasi pada controller untuk memastikan data yang masuk sesuai aturan.
- `Rule::unique(...)->ignore($model->id)` agar data yang sedang diedit tidak dianggap sebagai duplikat dirinya sendiri.

### 6. Pengujian

Pengujian aplikasi dijalankan menggunakan perintah:

```bash
php artisan test
```

Hasil pengujian:

```text
Tests: 2 passed (2 assertions)
```

Hasil tersebut menunjukkan bahwa test unit dan test feature yang tersedia berhasil dijalankan.
