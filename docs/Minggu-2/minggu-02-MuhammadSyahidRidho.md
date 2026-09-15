#  Minggu 2 — Pemrograman Web

**Nama:** Muhammad Syahid Ridho  
**NIM:** 10241051

# READ

## 1. Menelusuri Route `/tentang`

Pada `routes/web.php`, request menuju halaman `/tentang` ditangkap menggunakan route GET berikut:

```php
Route::get('/tentang', function () {
    return view('tentang');
});
```

`Route::get()` menunjukkan bahwa route tersebut merespons HTTP GET. Ketika alamat `/tentang` dibuka melalui browser, fungsi anonim (*closure*) dijalankan dan hasilnya adalah view bernama `tentang`.

## 2. Hubungan Route dengan Controller

Di tugas saya terdapat `PageController` dengan method:

```php
public function tentang()
{
    return view('tentang');
}
```

Berkasnya berada di:

```text
app/Http/Controllers/PageController.php
```

Method `tentang()` bertugas mengembalikan view. Namun, dari route yang sedang diamati, `/tentang` masih menggunakan closure secara langsung di `routes/web.php`, sehingga route tersebut belum memanggil `PageController`.

Jika ingin dipisahkan dengan pola controller, bentuk route-nya dapat diarahkan menjadi:

```php
Route::get('/tentang', [PageController::class, 'tentang']);
```

Pemisahan ini membuat file route berfokus pada pemetaan URL, sedangkan proses halaman ditangani controller.

## 3. View yang Digunakan

Pemanggilan:

```php
return view('tentang');
```

mengarah ke:

```text
resources/views/tentang.blade.php
```

Jadi `tentang` bukan nama folder atau URL, melainkan nama view yang dicari Laravel di dalam direktori `resources/views`.

## 4. Layout Halaman

Untuk halaman yang sudah diperbarui, komponen layout digunakan melalui:

```blade
<x-layout>
    ...
</x-layout>
```

File komponennya adalah:

```text
resources/views/components/layout.blade.php
```

Penggunaan komponen ini membuat bagian umum seperti struktur HTML dan navigasi tidak perlu dibuat ulang pada setiap halaman. Isi halaman ditempatkan pada bagian slot komponen.

## 5. Pemeriksaan Route dengan Artisan

Untuk memastikan route `/tentang` benar-benar terdaftar, digunakan:

```bash
php artisan route:list --path=tentang
```

Hasil pemeriksaan menunjukkan:

```text
GET|HEAD  tentang  ................................................ routes/web.php:6

Showing [1] routes
```

Dari hasil tersebut dapat disimpulkan bahwa Laravel mengenali satu route untuk `/tentang`, dengan metode GET dan HEAD. Pemeriksaan ini berguna untuk memastikan definisi route yang ditulis di kode sudah terbaca oleh aplikasi.

---

# BREAK

## Eksperimen Delapan Kerusakan

| No. | Perubahan yang Dicoba | Perkiraan Sebelum Diuji | Hasil / Pembelajaran |
|---|---|---|---|
| 1 | Mengubah route `/courses` dari `GET` menjadi `POST` | Saat alamat dibuka dari browser, request GET tidak akan menemukan route yang cocok. | Laravel menghasilkan **405 Method Not Allowed**. Artinya URL-nya ada, tetapi metode HTTP yang digunakan tidak sesuai dengan route. |
| 2 | Mengarahkan `view()` ke nama view yang tidak tersedia | Laravel akan mencoba mencari file Blade tersebut dan gagal. | Muncul **View [nama-view] not found**. Ini menunjukkan nama pada `view()` harus sesuai dengan struktur file di `resources/views`. |
| 3 | Menghapus nama route `courses.show` tetapi tetap memanggil `route('courses.show')` | Helper `route()` tidak lagi mempunyai nama route yang bisa dicari. | Terjadi **RouteNotFoundException** karena route bernama `courses.show` sudah tidak terdaftar. |
| 4 | Menempatkan route `/courses/{course}` sebelum route `/courses/create` | Kata `create` berpotensi dianggap sebagai isi parameter `{course}`. | Route dinamis dapat mengambil `/courses/create` lebih dahulu. Ini memperlihatkan bahwa **susunan route berpengaruh terhadap pencocokan URL**. |
| 5 | Mengganti output `{{ $nama }}` dengan `{!! $nama !!}` dan memasukkan tag script | HTML yang sebelumnya diamankan oleh escaping akan ditampilkan sebagai HTML mentah. | Script dapat dijalankan di browser. Percobaan ini memperlihatkan risiko **Cross-Site Scripting (XSS)** dan alasan output dari input pengguna sebaiknya tetap menggunakan `{{ }}`. |
| 6 | Menghapus `@vite(...)` dari layout | File CSS dan JavaScript yang disediakan melalui Vite tidak lagi dipanggil oleh halaman. | Tampilan dapat kehilangan styling dan fungsi frontend. Ini menunjukkan layout juga berperan dalam memuat aset aplikasi. |
| 7 | Menghentikan `npm run dev` kemudian membuka halaman kembali | Aset yang sebelumnya dilayani server development tidak tersedia seperti sebelumnya. | Asset frontend dapat gagal dimuat. Dari sini terlihat bahwa Vite development server dibutuhkan saat aplikasi mengandalkan proses development secara langsung. |
| 8 | Memanggil `route('courses.show')` tanpa memberikan nilai `{course}` | URL dinamis tidak dapat dibentuk karena ada parameter yang wajib. | Laravel memberikan error **Missing required parameter**. Helper `route()` membutuhkan nilai parameter untuk menghasilkan URL lengkap. |

### Kesimpulan dari BREAK

Percobaan tersebut menunjukkan bahwa bagian-bagian Laravel saling bergantung. Route menentukan bagaimana request diarahkan, controller mengatur proses, view menampilkan hasil, sedangkan helper seperti `route()` dan `@vite()` membantu aplikasi terhubung dengan bagian lainnya.

Percobaan nomor 5 juga menjadi pengingat bahwa cara menampilkan data bukan hanya masalah tampilan. Penggunaan escaping pada Blade berhubungan langsung dengan keamanan aplikasi.

---

# BUILD

## 1. Membuat Layout Komponen

File:

```text
resources/views/components/layout.blade.php
```

Contoh implementasi:

```php
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'KampusLMS' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <header>
        <h1>KampusLMS</h1>

        <nav>
            <a href="{{ url('/') }}">Dashboard</a>
            <a href="{{ route('courses.index') }}">Mata Kuliah</a>
            <a href="{{ route('tentang') }}">Tentang</a>
        </nav>
    </header>

    <main>
        {{ $slot }}
    </main>
</body>
</html>
```

Saya menempatkan navigasi di satu komponen agar halaman lain cukup memakai `<x-layout>`. Dengan begitu, perubahan navbar tidak perlu dilakukan berulang kali.

## 2. Membuat `CourseController`

File:

```text
app/Http/Controllers/CourseController.php
```

Data masih dibuat dalam bentuk array karena database belum digunakan pada tahap ini.

```php
<?php

namespace App\Http\Controllers;

class CourseController extends Controller
{
    private array $courses = [
        [
            'id' => 1,
            'code' => 'SI2514024',
            'name' => 'Pemrograman Web',
            'sks' => 3,
            'lecturer' => 'Dosen A',
        ],
        [
            'id' => 2,
            'code' => 'SI2514025',
            'name' => 'Basis Data',
            'sks' => 3,
            'lecturer' => 'Dosen B',
        ],
        [
            'id' => 3,
            'code' => 'SI2514026',
            'name' => 'Jaringan Komputer',
            'sks' => 2,
            'lecturer' => 'Dosen C',
        ],
    ];

    public function index()
    {
        return view('courses.index', [
            'courses' => $this->courses,
        ]);
    }

    public function show(int $id)
    {
        $course = collect($this->courses)->firstWhere('id', $id);

        if ($course === null) {
            abort(404);
        }

        return view('courses.show', [
            'course' => $course,
        ]);
    }
}
```

Method `index()` mengirim seluruh data ke halaman daftar. Sementara `show()` mencari satu data berdasarkan ID. Jika ID tidak ditemukan, aplikasi diarahkan ke halaman 404.

## 3. Halaman Daftar Mata Kuliah

File:

```text
resources/views/courses/index.blade.php
```

```blade
<x-layout title="Mata Kuliah">

    <h2>Daftar Mata Kuliah</h2>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Mata Kuliah</th>
                <th>SKS</th>
                <th>Dosen</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($courses as $course)
                <tr>
                    <td>{{ $course['code'] }}</td>
                    <td>
                        <a href="{{ route('courses.show', $course['id']) }}">
                            {{ $course['name'] }}
                        </a>
                    </td>
                    <td>{{ $course['sks'] }}</td>
                    <td>{{ $course['lecturer'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Belum ada mata kuliah.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</x-layout>
```

Saya menggunakan `@forelse` agar halaman tetap memiliki tampilan yang jelas ketika data kosong. Link detail dibuat dengan `route()` supaya URL tidak ditulis secara manual.

## 4. Halaman Detail

File:

```text
resources/views/courses/show.blade.php
```

```blade
<x-layout title="Detail Mata Kuliah">

    <h2>{{ $course['name'] }}</h2>

    <dl>
        <dt>Kode</dt>
        <dd>{{ $course['code'] }}</dd>

        <dt>Jumlah SKS</dt>
        <dd>{{ $course['sks'] }}</dd>

        <dt>Dosen Pengampu</dt>
        <dd>{{ $course['lecturer'] }}</dd>
    </dl>

    <a href="{{ route('courses.index') }}">
        Kembali ke daftar mata kuliah
    </a>

</x-layout>
```

Halaman detail menerima satu data `$course` dari controller. Semua data ditampilkan menggunakan `{{ }}` sehingga Blade tetap melakukan escaping terhadap output.

## 5. Menambahkan Route Mata Kuliah

Pada `routes/web.php`:

```php
use App\Http\Controllers\CourseController;

Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');

Route::get('/courses', [CourseController::class, 'index'])
    ->name('courses.index');

Route::get('/courses/{course}', [CourseController::class, 'show'])
    ->name('courses.show');
```

Route `/courses` diletakkan sebelum `/courses/{course}` agar URL daftar tidak salah dibaca sebagai parameter `{course}`.

## 6. Halaman 404

File:

```text
resources/views/errors/404.blade.php
```

```blade
<x-layout title="404 - Tidak Ditemukan">

    <h2>Halaman Tidak Ditemukan</h2>

    <p>
        Data atau halaman yang diminta tidak tersedia.
    </p>

    <a href="{{ url('/') }}">Kembali ke Dashboard</a>

</x-layout>
```

Halaman ini memberikan tampilan yang lebih jelas ketika resource yang diminta tidak tersedia.

## 7. Verifikasi Akhir

Setelah route dibuat, pengecekan dapat dilakukan dengan:

```bash
php artisan route:list
```

Route yang diharapkan antara lain:

```text
GET|HEAD   courses
GET|HEAD   courses/{course}
GET|HEAD   tentang
```

Dengan hasil tersebut, dapat diperiksa bahwa daftar mata kuliah memiliki route sendiri dan halaman detail menggunakan route dinamis.

---

