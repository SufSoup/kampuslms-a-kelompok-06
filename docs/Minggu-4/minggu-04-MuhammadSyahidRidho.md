# Dokumentasi Praktik Minggu 4 — Form Validation

**Nama:** Muhammad Syahid Ridho  
**Mata Kuliah:** Pemrograman Web  
**Project:** KampusLMS

---

## 1. READ — Menelusuri Proses Ketika Validasi Gagal

### Skenario Pengamatan

Pengamatan dilakukan pada proses penambahan mata kuliah. Untuk membuat validasi gagal, nilai **SKS** diisi dengan angka di luar batas yang diperbolehkan, misalnya `99`.

Aturan validasi pada `StoreCourseRequest` menetapkan bahwa nilai SKS harus berada pada rentang `1` sampai `6`.

---

### 1.1 Request masuk ke method apa?

Form tambah mata kuliah mengirim request ke method `store()` pada:

```text
app/Http/Controllers/CourseController.php
```

Method tersebut menerima:

```php
public function store(StoreCourseRequest $request)
```

Untuk proses edit, alurnya menggunakan method `update()` pada controller yang sama:

```php
public function update(UpdateCourseRequest $request, Course $course)
```

**Kesimpulan:** Request POST untuk membuat course ditangani oleh `CourseController::store()` dengan `StoreCourseRequest`.

---

### 1.2 Kapan validasi dilakukan?

Validasi tidak menunggu sampai isi method `store()` berjalan. Laravel memproses `StoreCourseRequest` terlebih dahulu berdasarkan aturan yang ada di:

```text
app/Http/Requests/StoreCourseRequest.php
```

Contoh aturan SKS:

```php
'sks' => ['required', 'integer', 'min:1', 'max:6'],
```

Jika pengguna mengirim `sks=99`, proses validasi gagal sehingga kode di dalam `store()` tidak diteruskan.

**Kesimpulan:** Validasi Form Request berlangsung sebelum kode utama controller dieksekusi.

---

### 1.3 Apa yang terjadi setelah validasi gagal?

Ketika data tidak memenuhi aturan, Laravel mengembalikan pengguna ke halaman form sebelumnya.

Alurnya dapat digambarkan seperti berikut:

```text
POST /courses
      |
      v
Pemeriksaan StoreCourseRequest
      |
      | gagal
      v
Kembali ke halaman form
      |
      v
GET /courses/create
```

Selain redirect, Laravel juga membawa informasi error dan input sebelumnya melalui session.

Jika validasi berhasil, alurnya berbeda karena controller dapat menentukan redirect sendiri, contohnya:

```php
return redirect()
    ->route('courses.index')
    ->with('success', 'Mata kuliah berhasil dibuat.');
```

**Kesimpulan:** Redirect otomatis ketika validasi gagal merupakan bagian dari mekanisme Form Request Laravel.

---

### 1.4 Bagaimana `@error('sks')` mendapatkan pesan?

Pada form terdapat kode:

```blade
@error('sks')
    <small>{{ $message }}</small>
@enderror
```

Laravel menyediakan kumpulan error validasi melalui `$errors`. Directive `@error('sks')` memeriksa apakah terdapat pesan kesalahan untuk field `sks`.

Jika ada, isi `$message` dapat digunakan untuk menampilkan pesan tersebut pada halaman.

**Kesimpulan:** `@error()` mengambil informasi dari error bag Laravel yang tersedia pada view setelah proses validasi gagal.

---

### 1.5 Dari mana nilai `old('sks')` berasal?

Pada form, nilai input dapat ditulis seperti:

```blade
value="{{ old('sks', $course->sks ?? '') }}"
```

Ketika validasi gagal, Laravel menyimpan input yang sebelumnya dikirim secara sementara di session. Helper `old()` kemudian mengambil kembali nilai tersebut pada halaman form berikutnya.

Contohnya:

```text
Pengguna mengisi SKS = 99
          ↓
Validasi gagal
          ↓
Laravel menyimpan input sebelumnya
          ↓
Redirect ke form
          ↓
old('sks') menghasilkan 99
```

Nilai tersebut hanya bersifat sementara dan tidak digunakan sebagai penyimpanan permanen.

**Kesimpulan:** `old()` membantu mempertahankan isian pengguna ketika form harus dikirim ulang akibat kesalahan validasi.

---

### 1.6 Pemeriksaan cookie session

Pemeriksaan session dilakukan menggunakan browser:

```text
F12
→ Application
→ Cookies
→ http://127.0.0.1:8000
```

Nama cookie session perlu dicatat berdasarkan hasil pemeriksaan langsung pada browser.

```text
Nama cookie session:
[isi berdasarkan hasil DevTools]
```

Nama cookie tidak sebaiknya ditebak karena dapat mengikuti konfigurasi project pada `.env` dan `config/session.php`.

Selain cookie session, browser dapat menampilkan cookie lain yang berkaitan dengan keamanan seperti `XSRF-TOKEN`.

---

# 2. BREAK — Menguji Dampak Jika Bagian Penting Dihilangkan

Bagian ini dilakukan dengan cara mengubah sementara komponen tertentu, menjalankan pengujian, kemudian mengembalikannya seperti semula.

> **Catatan:** Percobaan sebaiknya dilakukan pada branch pekerjaan, bukan pada `main`.

---

## 2.1 Menghilangkan CSRF Token

### Kondisi awal

Form memiliki:

```blade
@csrf
```

### Percobaan

Baris tersebut dihapus sementara lalu form POST dikirim.

### Hasil

Laravel menolak request dan biasanya menampilkan:

```text
419 Page Expired
```

### Analisis

Token CSRF diperlukan agar Laravel dapat memeriksa bahwa request berasal dari sumber yang memiliki token yang sesuai.

### Pemulihan

Kembalikan:

```blade
@csrf
```

---

## 2.2 Menggunakan seluruh input request

### Kondisi awal

Controller menggunakan data yang telah divalidasi:

```php
$validated = $request->validated();
```

### Percobaan

Ubah menjadi:

```php
$validated = $request->all();
```

Kemudian kirim request dengan tambahan field yang tidak termasuk input normal form.

### Hasil pengamatan

`all()` mengambil seluruh payload yang dikirim client, sedangkan `validated()` membatasi data berdasarkan rule pada Form Request.

### Analisis

Menggunakan seluruh request membuat batas data yang diterima aplikasi menjadi lebih longgar. Karena itu, untuk proses yang memang membutuhkan data hasil validasi, `$request->validated()` lebih tepat digunakan.

### Pemulihan

```php
$validated = $request->validated();
```

---

## 2.3 Menghapus pemeriksaan keberadaan `lecturer_id`

### Kondisi awal

```php
'lecturer_id' => ['required', 'exists:users,id'],
```

### Percobaan

Rule `exists:users,id` dihilangkan kemudian dikirim ID dosen yang tidak terdapat pada tabel `users`, misalnya:

```text
lecturer_id = 99999
```

### Hasil pengamatan

Validasi aplikasi tidak lagi memberi tahu bahwa ID tersebut tidak ditemukan. Jika database memiliki foreign key, proses berikutnya dapat berakhir dengan error constraint database.

### Analisis

Rule `exists` berfungsi memeriksa referensi sebelum data diteruskan ke proses penyimpanan.

### Pemulihan

```php
'lecturer_id' => ['required', 'exists:users,id'],
```

---

## 2.4 Menghapus pembatas nilai status

### Kondisi awal

```php
'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
```

### Percobaan

Pembatas `Rule::in(...)` dihilangkan lalu dikirim:

```text
status = superadmin
```

### Hasil pengamatan

Nilai tersebut tidak lagi ditolak pada tahap validasi aplikasi. Jika struktur database juga membatasi nilai status melalui ENUM, database dapat memberikan error karena nilai tersebut tidak sesuai.

### Analisis

Validasi pada request memberikan pemeriksaan lebih awal sehingga input yang berada di luar daftar nilai yang diperbolehkan dapat ditolak sebelum proses penyimpanan.

### Pemulihan

```php
'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
```

---

## 2.5 Menghilangkan `withQueryString()`

### Kondisi awal

Pagination menggunakan:

```php
->paginate(15)
->withQueryString();
```

### Percobaan

Hapus sementara:

```php
->withQueryString()
```

Kemudian lakukan pencarian dan pindah ke halaman berikutnya.

### Hasil

Parameter pencarian atau filter tidak lagi ikut diteruskan oleh link pagination.

Contoh URL dapat berubah dari:

```text
/users?search=andi&page=2
```

menjadi hanya:

```text
/users?page=2
```

### Analisis

Filter yang sebelumnya tersimpan pada query string ikut hilang ketika halaman pagination berubah.

### Pemulihan

```php
->withQueryString();
```

---

## 2.6 Mengubah redirect setelah penyimpanan menjadi view

### Kondisi awal

Setelah data berhasil disimpan, controller menggunakan redirect:

```php
return redirect()
    ->route('courses.index')
    ->with('success', 'Mata kuliah berhasil dibuat.');
```

### Percobaan

Alur tersebut diubah sementara agar POST langsung mengembalikan view.

### Hasil

Ketika halaman di-refresh, browser dapat meminta pengiriman ulang request POST.

Hal ini biasanya ditandai dengan pesan seperti:

```text
Confirm Form Resubmission
```

Jika request benar-benar dikirim ulang, data yang sama berpotensi tersimpan kembali.

### Analisis

Redirect setelah POST menerapkan pola **Post/Redirect/Get (PRG)**. Setelah POST selesai, browser diarahkan ke request GET sehingga refresh tidak mengulangi POST sebelumnya.

### Pemulihan

Kembalikan pola redirect:

```php
return redirect()
    ->route('courses.index')
    ->with('success', 'Mata kuliah berhasil dibuat.');
```

---

## 2.7 Menghapus penggunaan `old()`

### Kondisi awal

Contoh:

```blade
value="{{ old('name', $user->name ?? '') }}"
```

### Percobaan

Hilangkan `old()` dari input form.

### Pengujian

1. Isi beberapa field.
2. Buat satu field tidak valid.
3. Submit form.
4. Perhatikan nilai input setelah halaman kembali.

### Hasil

Data yang sebelumnya sudah diketik dapat hilang karena form tidak lagi mengambil nilai dari old input.

### Analisis

`old()` membuat pengalaman pengisian form lebih baik karena pengguna hanya perlu memperbaiki field yang salah, bukan mengulang seluruh isian.

### Pemulihan

Gunakan kembali:

```blade
value="{{ old('name', $user->name ?? '') }}"
```

---

# 3. Rekap Hasil READ

| Bagian | Hasil Pengamatan |
|---|---|
| Request tambah course | Masuk ke `CourseController::store()` |
| Form Request | `StoreCourseRequest` |
| Waktu validasi | Sebelum isi method controller dijalankan |
| Redirect saat gagal | Kembali ke halaman form sebelumnya |
| Sumber pesan `@error()` | Error bag Laravel (`$errors`) |
| Sumber `old()` | Input sebelumnya yang disimpan sementara pada session |
| Cookie session | Dicatat dari DevTools browser |

# 4. Rekap Hasil BREAK

| Pengujian | Dampak yang Diamati |
|---|---|
| Menghapus `@csrf` | Request POST ditolak, umumnya 419 |
| Menggunakan `$request->all()` | Seluruh payload request dapat ikut diambil |
| Menghapus `exists` | ID yang tidak valid tidak tertahan di validasi aplikasi |
| Menghapus `Rule::in` | Nilai status di luar daftar dapat melewati validasi aplikasi |
| Menghapus `withQueryString()` | Search/filter tidak bertahan saat pagination |
| Mengganti redirect dengan view | POST dapat diminta ulang ketika halaman direfresh |
| Menghapus `old()` | Isian sebelumnya dapat hilang setelah validasi gagal |

# 5. Kesimpulan

Praktik READ memperlihatkan bagaimana Laravel memproses sebuah form ketika data tidak memenuhi aturan. Form Request melakukan pemeriksaan sebelum controller menjalankan proses utama, sedangkan session membantu membawa informasi error dan input sebelumnya kembali ke halaman form.

Praktik BREAK memperlihatkan dampak dari menghilangkan komponen-komponen penting dalam alur aplikasi. Dari percobaan tersebut dapat dilihat bahwa CSRF, validasi server, pemeriksaan relasi data, pembatasan nilai, query string, PRG, dan `old()` masing-masing memiliki fungsi yang berbeda dalam menjaga keamanan, konsistensi data, serta kenyamanan penggunaan aplikasi.

---


