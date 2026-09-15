# READ

## 1. Baris yang menangkap route `/tentang`

Route `/tentang` ditangkap pada file `routes/web.php`.

```php
Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');
```

Route tersebut menggunakan method HTTP **GET** untuk menangani permintaan ke URL `/tentang`.

Ketika pengguna mengakses `/tentang`, Laravel akan menjalankan `function` tersebut dan mengembalikan view `tentang`.

## 2. Controller yang menangani route `/tentang`

Route `/tentang` **tidak ditangani oleh controller**.

Pada `routes/web.php`, route tersebut menggunakan closure:

```php
Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');
```

Artinya, logika untuk menampilkan halaman `tentang` langsung ditulis di dalam `routes/web.php`, bukan di dalam controller.

**Berkas controller:** Tidak ada
**Method controller:** Tidak ada

Jadi, route `/tentang` saat ini langsung mengembalikan view `tentang` melalui closure.

## 3. View yang dikembalikan

Route `/tentang` mengembalikan view **`tentang`**, seperti yang terlihat pada kode:

```php
Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');
```

Laravel akan mencari file view tersebut di:

```text
resources/views/tentang.blade.php
```

**View:** `tentang`
**Path:** `resources/views/tentang.blade.php`

## 4. Layout yang membungkus view

## 4. Layout yang membungkus view

View `/tentang` **tidak menggunakan layout**.

File `resources/views/tentang.blade.php` langsung berisi struktur HTML lengkap, mulai dari `<!DOCTYPE html>`, `<html>`, `<head>`, hingga `<body>`.

## 5. Hasil `php artisan route:list --path=tentang`

Setelah menjalankan perintah:

```bash
php artisan route:list --path=tentang
```

route `/tentang` muncul pada daftar route dengan method **GET|HEAD**, URI **tentang**, dan nama route **tentang**.
Route `/tentang` menggunakan `Route::get()`, tidak menggunakan controller karena masih menggunakan closure, dan memiliki nama route `tentang`.

Hasil `route:list` cocok dengan analisis terhadap route `/tentang`.
