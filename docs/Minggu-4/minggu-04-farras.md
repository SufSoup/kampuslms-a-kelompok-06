# Catatan Minggu 4 — Form, Validasi, Session, dan PRG

**Nama:** Muhammad Farras Kanz  
**NIM:** 10241047  
**Kelompok:** 06 Kelas A

---

## READ — Menelusuri satu siklus form gagal

Contoh yang digunakan adalah form **Tambah Mata Kuliah**. Form mengirim data ke route `admin.courses.store`. Nilai SKS saya isi `99`, sedangkan nilai yang diterima aplikasi hanya 1 sampai 6.

### 1. Method dan controller yang menerima request

Request diterima oleh method `store()` di `CourseController`:

```php
public function store(StoreCourseRequest $request)
```

Route resource `admin.courses.store` mengarahkan request `POST /admin/courses` ke method tersebut.

### 2. Titik validasi terjadi

Validasi terjadi **sebelum baris pertama** `CourseController@store()` dijalankan. Parameter method memakai `StoreCourseRequest`, sehingga Laravel menjalankan `authorize()` dan `rules()` lebih dulu.

Rule untuk SKS adalah:

```php
'sks' => ['required', 'integer', 'min:1', 'max:6']
```

Nilai `99` gagal pada rule `max:6`. Karena gagal, baris `Gate::authorize()` dan kode lain di dalam method `store()` belum dijalankan.

### 3. Tujuan redirect saat validasi gagal

Laravel mengarahkan pengguna kembali ke halaman form sebelumnya, yaitu halaman tambah mata kuliah. Tujuan ini ditentukan oleh `ValidationException` menggunakan URL sebelumnya (*previous URL*). Form Request dapat menggantinya melalui `$redirect`, `$redirectRoute`, atau `$redirectAction` jika diperlukan.

### 4. Sumber pesan `@error('sks')`

Saat validasi gagal, Laravel menyimpan daftar error ke session untuk request berikutnya. Directive Blade berikut membaca error field `sks` dari *error bag* di session:

```blade
@error('sks')
    <p>{{ $message }}</p>
@enderror
```

Pesan tersebut berasal dari method `messages()` pada `StoreCourseRequest`, yaitu: **“SKS maksimal 6.”**

### 5. Sumber dan masa hidup `old('sks')`

`old('sks')` mengambil input sebelumnya yang di-*flash* ke session ketika validasi gagal. Jadi nilai `99` tetap tampil setelah redirect agar pengguna tidak perlu mengisi ulang semua field.

Data *old input* hanya bertahan untuk **satu request berikutnya**. Setelah halaman form hasil redirect dibuka, nilainya hilang kecuali di-*flash* lagi.

### 6. Nama cookie session Laravel

`config/session.php` membuat nama cookie dengan format:

```php
Str::slug(env('APP_NAME', 'laravel'), '_') . '_session'
```

Nilai `APP_NAME` proyek ini adalah `Laravel`, sehingga cookie pada DevTools → Application → Cookies bernama:

```text
laravel_session
```

Cookie menyimpan ID session, bukan seluruh daftar error. Karena proyek memakai session database, data session disimpan pada tabel `sessions`.

---

## BREAK — Delapan kerusakan form

| # | Yang dirusak | Prediksi dan hasil yang perlu diamati |
| --- | --- | --- |
| 1 | Menghapus `@csrf` dari form, lalu mengirim form. | Request ditolak dengan **HTTP 419 Page Expired**. Token CSRF mencegah situs lain mengirim request memakai sesi login pengguna tanpa izin. |
| 2 | Mengganti `$request->validated()` menjadi `$request->all()`, lalu mengirim field liar melalui `curl`. | Field yang tidak divalidasi ikut lolos. Jika data itu dipakai untuk mass assignment, kolom sensitif dapat ikut terisi. Karena itu controller harus memakai `validated()`. |
| 3 | Menghapus rule `exists:users,id` pada `lecturer_id`, lalu mengirim `lecturer_id=99999`. | Validasi aplikasi tidak lagi menolak ID dosen yang tidak ada. Foreign key database menjadi pagar terakhir, tetapi pengguna mendapat error database, bukan pesan validasi yang jelas. |
| 4 | Menghapus rule `Rule::in(['draft', 'active', 'archived'])` pada `status`, lalu mengirim `status=superadmin`. | Aplikasi membiarkan nilai tidak valid lewat. Enum database dapat menolaknya, tetapi rule `in` tetap perlu agar kesalahan diberitahukan sebelum query dijalankan. |
| 5 | Menghapus `->withQueryString()`, melakukan pencarian, lalu membuka halaman 2. | Parameter `q` dan `status` tidak ikut di link pagination. Halaman kedua kembali menampilkan data tanpa filter. |
| 6 | Mengganti `return redirect()` pada `store()` menjadi `return view()`, lalu menekan F5 setelah simpan. | Browser mengulang request POST saat refresh. Data bisa dibuat dua kali. Pola Post/Redirect/Get mencegahnya dengan mengarahkan browser ke GET setelah simpan berhasil. |
| 7 | Menghapus semua `old(...)` dari input, lalu mengirim form dengan satu kesalahan. | Seluruh isian pengguna hilang setelah kembali ke form. Pengguna harus mengisi ulang data, sehingga pengalaman penggunaan menjadi buruk. |
| 8 | Menghapus pembungkus `where(fn ($q) => ...)` pada pencarian, lalu mencari nama sambil memfilter `status=archived`. | Kondisi `orWhere('code', ...)` dapat melewati filter status. Logikanya berubah dari `(nama ATAU kode) DAN status` menjadi `nama ATAU (kode DAN status)`, sehingga mata kuliah aktif yang kodenya cocok dapat ikut tampil. `->toRawSql()` membantu melihat SQL dan letak masalahnya. |

---

## Kesimpulan

Form yang baik tidak hanya memiliki input dan tombol simpan. Validasi harus berjalan sebelum controller, error dan input lama harus dikirim kembali melalui session, serta simpan yang berhasil harus diikuti redirect. Pada query, pengelompokan kondisi pencarian juga penting agar filter tidak dapat dilewati oleh `OR`.
