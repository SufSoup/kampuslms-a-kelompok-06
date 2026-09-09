## Notes Minggu 2 Pemrograman Web

### READ — Telusuri satu request penuh

#### 1. Baris mana di `routes/web.php` yang menangkap route `/tentang`?
Jawaban : <br>
Saat ini di `routes/web.php` **belum ada route khusus `/tentang`**. Yang ada justru dua route yang sama-sama menangkap `/` (root):
```php
Route::get('/', function () {
    return view('welcome');
});

Route::get('/', function () {
    return view('tentang');
});
```
Karena Laravel membaca route dari atas ke bawah dan memakai yang **terakhir cocok** untuk closure route yang identik, maka yang dijalankan adalah route kedua yang mengembalikan view `tentang`. Artinya route pertama (`welcome`) tidak pernah dipakai — ini seharusnya diperbaiki dengan memberi path berbeda, misalnya `/tentang`.

#### 2. Kalau ditangani controller, berkas dan method mana?
Jawaban : <br>
Route `/tentang` saat ini **tidak ditangani oleh controller**. Route tersebut langsung memakai closure (fungsi anonim) di dalam `routes/web.php`:
```php
Route::get('/', function () {
    return view('tentang');
});
```
Belum ada controller sama sekali selain `Controller.php` bawaan Laravel di `app/Http/Controllers/`. Idealnya untuk Minggu 2 ini, route seharusnya diarahkan ke sebuah controller agar mengikuti pola MVC yang benar.

#### 3. View mana yang dikembalikan? Di path apa persisnya?
Jawaban : <br>
View yang dikembalikan adalah `tentang`, yang merujuk ke file:
```
resources/views/tentang.blade.php
```
File ini berisi halaman HTML sederhana yang menampilkan nama kelompok (Kelompok 06 Kelas A) dan daftar anggota beserta NIM-nya. View ini **belum memakai layout/komponen** — semua tag HTML (`<!DOCTYPE>`, `<head>`, `<body>`) ditulis langsung di dalamnya.

#### 4. Layout apa yang membungkusnya?
Jawaban : <br>
Saat ini **belum ada layout yang membungkus** view `tentang.blade.php`. File tersebut berdiri sendiri dengan struktur HTML lengkap dari `<!DOCTYPE html>` sampai `</html>`. Belum ada komponen layout (`x-layout`) atau `@extends` yang dipakai. Ini artinya kalau ada banyak halaman, header dan navbar harus disalin-tempel ke setiap halaman — yang tentu tidak efisien.

#### 5. Jalankan `php artisan route:list --path=tentang`. Cocok dengan analisis Anda?
Jawaban : <br>
Ketika dijalankan, hasilnya **kosong** — tidak ada route yang cocok dengan path `/tentang`. Ini membuktikan analisis di atas: route `/tentang` memang belum ada. Yang ada hanya dua route dengan path `/` (root). Keluaran `route:list` hanya menampilkan satu route `GET /` karena route kedua menimpa yang pertama pada path yang sama.

Ini berarti tugas pertama yang harus dikerjakan di bagian BUILD adalah **memperbaiki route** agar `/tentang` punya path sendiri dan tidak menimpa halaman welcome.
