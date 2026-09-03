## Nama : Muhammad Yusuf Pratama

## NIM : 10241053

### 1. Buka public/index.php. Baca dari atas ke bawah. Tulis dalam 3 kalimat apa yang dilakukan berkas ini.

### Jawab :

### Berkas public/index.php mengimpor class Laravel Application dan Request, lalu mencatat waktu awal proses aplikasi.

### Berkas ini memeriksa apakah Laravel sedang dalam mode maintenance dan memuat file maintenance jika tersedia.

### Selanjutnya, berkas memuat Composer autoloader, membuat aplikasi melalui bootstrap/app.php, lalu menangani request HTTP yang masuk.

### 2. Buka bootstrap/app.php. Identifikasi bagian mana yang mengurus route, mana yang mengurus middleware, mana yang mengurus exception.

### Jawab :

### bootstrap/app.php mengatur route melalui withRouting, middleware melalui withMiddleware, dan penanganan exception melalui withExceptions.

### 3. Buka routes/web.php. Temukan route yang menghasilkan halaman selamat datang. Ubah teksnya, muat ulang browser, pastikan berubah.

### Jawab : Di routes/web.php, route Route::get('/', function () { return view('welcome'); }); menghasilkan halaman selamat datang dengan memanggil view resources/views/welcome.blade.php; untuk mengubah teksnya, buka file tersebut, edit tulisan yang ingin diganti, simpan, lalu muat ulang browser di http://127.0.0.1:8000 dan pastikan teks baru tampil.

### 4. Jalankan php artisan route:list. Cocokkan keluarannya dengan isi routes/web.php.

### Jawab :

### Output menunjukkan 4 route: satu berasal langsung dari routes/web.php, sedangkan tiga lainnya adalah route bawaan Laravel untuk storage dan health check.

### Hasil php artisan route:list menampilkan 4 route:

```php
GET|HEAD  /                    routes/web.php:5
GET|HEAD  storage/{path}       storage.local
PUT       storage/{path}       storage.local.upload
GET|HEAD  up                   Laravel health check

```

### Route GET|HEAD / cocok dengan isi routes/web.php, yaitu route / yang mengembalikan view welcome; tiga route lainnya merupakan route bawaan Laravel untuk file storage dan pemeriksaan kesehatan aplikasi.
