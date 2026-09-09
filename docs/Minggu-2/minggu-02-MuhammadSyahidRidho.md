**Nama:Muhammad Syahid Ridho**

**NIM:10241051**

# READ
## 1. Definisi Route (`routes/web.php`)
* **Baris Kode:** 
```php
  Route::get('/tentang', function () {
    return view('tentang');});
```
Baris kode yang menangkap permintaan menggunakan fungsi Route::get('/tentang', ...) untuk menerima akses masuk pada path /tentang.

## 2. Penanganan Controller & Method (`PageController.php`)

* **Berkas Controller:** `app/Http/Controllers/PageController.php`
* **Method:**
  ```php
  public function tentang() {
      return view('tentang');
  }
  ```
* **Analisis:** Controller menerima eksekusi dari route, memproses data jika diperlukan, lalu memerintahkan Laravel untuk merender file view.

## 3. Berkas View & Path Fisik

* **Nama View:** `tentang`
* **Path Persis:** `resources/views/tentang.blade.php`
* **Analisis:** Menyediakan struktur antarmuka pengguna (HTML/Blade) spesifik untuk halaman "Tentang" yang dibungkus layout modular.

## 4. Pembungkus Layout (Layout Component)

* **Komponen:** `<x-layout>`
* **Path Komponen:** `resources/views/components/layout.blade.php`
* **Analisis:** Berkas `tentang.blade.php` dibungkus oleh komponen modular Blade untuk menjaga konsistensi struktur HTML dan navigasi global.

## 5. Verifikasi Perintah Artisan

* **Perintah Terminal:**
  ```bash
  php artisan route:list --path=tentang
  ```
    ```bash
   GET|HEAD       tentang ................................................................................................................... routes/web.php:6

                                                                                                                                           Showing [1] routes
  ```
* **Hasil Verifikasi:** Hasil terminal tersebut mengonfirmasi bahwa rute /tentang telah terdaftar dan aktif pada berkas routes/web.php. Output ini menunjukkan rute siap menerima metode HTTP GET dan HEAD, sekaligus memvalidasi bahwa tepat satu rute ditemukan sesuai kriteria pencarian.


# BUILD


## Tabel Eksperimen Delapan Kerusakan

| # | Bagian yang Dirusak | Prediksi Saya | Yang Dipelajari / Hasil Nyata |
|---|---|---|---|
| 1 | Ubah `Route::get` menjadi `Route::post` pada route daftar mata kuliah (`/courses`) | Halaman tidak bisa diakses lewat browser secara normal karena browser melakukan *request* tipe `GET`. | **Method HTTP tidak cocok (405 Method Not Allowed)**: Laravel menolak *request* karena metode HTTP dari browser tidak sesuai dengan definisi rute yang diatur. |
| 2 | Ubah nama view di `return view('courses.index')` menjadi view yang tidak ada (misal: `courses.salah`) | Muncul halaman error bawaan Laravel karena file template tidak ditemukan. | **Exception ViewNotFound**: Laravel melemparkan `InvalidArgumentException` karena berkas `.blade.php` tujuan gagal ditemukan di dalam direktori `resources/views`. |
| 3 | Hapus `->name('courses.show')`, lalu muat halaman yang memakai helper `route('courses.show')` | Halaman *index* atau *detail* akan mengalami error saat fungsi helper dijalankan. | **Kenapa nama route wajib**: Helper `route()` bergantung pada identifikasi nama unik untuk merangkai URI. Tanpa nama, Laravel memicu `RouteNotFoundException`. |
| 4 | Pindahkan route `Route::get('/courses/{course}', ...)` ke ATAS `Route::get('/courses/create', ...)` | Halaman `/courses/create` akan gagal menampilkan form tambah data. | **Urutan route menentukan**: Laravel mengevaluasi rute dari atas ke bawah. Kata `create` akan dianggap sebagai nilai parameter `{course}`, bukan string statis. |
| 5 | Ganti `{{ $nama }}` menjadi `{!! $nama !!}`, lalu isi data dengan `<script>alert('XSS')</script>` | Skrip JavaScript akan tereksekusi langsung di dalam browser pengguna berupa pop-up *alert*. | **XSS nyata di layar sendiri**: Tanpa *escaping* otomatis dari kurung kurawal ganda, browser mengeksekusi tag HTML/JS mentah, membuktikan celah keamanan *Cross-Site Scripting*. |
| 6 | Hapus `@vite(...)` dari komponen layout utama (`layout.blade.php`) | Halaman web kehilangan seluruh berkas gaya (*styling* CSS) dan fungsi JavaScript interaktif. | **Aset tidak termuat**: Hubungan antara Laravel dengan server aset Vite terputus, sehingga manifes aset gagal disuntikkan ke tag `<head>` HTML. |
| 7 | Hentikan proses terminal `npm run dev` lalu muat ulang halaman web | Halaman gagal memuat aset frontend atau koneksi ke server lokal terputus. | **Beda dev server vs build**: Development server berhenti melayani kompilasi *on-the-fly* dan *Hot Module Replacement (HMR)* untuk file aset. |
| 8 | Panggil `route('courses.show')` di dalam view tanpa mengirim parameter ID/course | Terjadi error pada aplikasi karena fungsi pembangun URL kekurangan argumen. | **Missing required parameter**: Helper `route()` gagal merangkai URL dinamis secara utuh karena parameter wajib `{course}` tidak disertakan saat pemanggilan. |