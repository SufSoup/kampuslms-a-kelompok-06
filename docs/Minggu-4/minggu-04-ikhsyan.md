## Notes Minggu 4 Pemrograman Web

### READ — Telusuri satu siklus form gagal

#### 1. Method apa yang menerima request? Di controller mana?
Jawaban : <br>
Request form tambah mata kuliah diterima oleh method `store(StoreCourseRequest $request)` di dalam controller `app/Http/Controllers/CourseController.php`. Sementara untuk proses update, diterima oleh method `update(UpdateCourseRequest $request, Course $course)` di controller yang sama.

#### 2. Di titik mana persisnya validasi terjadi — sebelum atau sesudah baris pertama method controller?
Jawaban : <br>
Validasi terjadi **sebelum** baris pertama method controller dijalankan. Karena kita menggunakan *Form Request* (`StoreCourseRequest`), Laravel memvalidasi request melalui middleware dan *form request lifecycle* sebelum controller dipanggil. Kalau ada data yang tidak lolos validasi, Laravel langsung melempar `ValidationException` dan me-redirect pengguna kembali ke halaman form. Akibatnya, baris kode pertama di dalam `public function store(...)` tidak pernah dieksekusi sama sekali saat validasi gagal.

#### 3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?
Jawaban : <br>
Laravel me-redirect kembali ke halaman form sebelumnya (yaitu `http://127.0.0.1:8000/courses/create` atau `url()->previous()`). Yang menentukan tujuannya adalah trait `ValidatesWhenResolved` bawaan Laravel melalui method `redirectUrl()` / `getRedirectUrl()`, yang secara default membaca URL asal pengiriman request (`UrlGenerator::previous()`).

#### 4. Dari mana `@error('sks')` mengambil pesannya?
Jawaban : <br>
Directive `@error('sks')` mengambil pesan error dari session flash storage key `errors` (berupa objek `Illuminate\Support\ViewErrorBag`) yang otomatis di-share oleh middleware `ShareErrorsFromSession` ke semua template Blade. Pesan ini dicocokkan dengan array `messages()` yang ada di dalam `StoreCourseRequest.php`.

#### 5. Dari mana `old('sks')` mengambil nilainya? Berapa lama nilai itu bertahan?
Jawaban : <br>
Helper `old('sks')` mengambil nilai input yang diketik sebelumnya dari flash session dengan key `_old_input`. Nilai ini hanya bertahan selama **satu request berikutnya** (*one-time flash*). Jika halaman form tersebut di-refresh sekali lagi tanpa submit form, data `_old_input` akan otomatis dihapus oleh Laravel dan form kembali kosong.

#### 6. Buka DevTools → Application → Cookies. Temukan cookie session Laravel. Catat namanya.
Jawaban : <br>
Di DevTools browser (F12) → tab **Application** → bagian **Cookies** (`http://127.0.0.1:8000`):
Nama cookie session Laravel adalah:
```text
kampuslms_session (atau laravel_session)
```
Cookie ini bertipe *encrypted string* dan berfungsi sebagai session identifier unik untuk mencocokkan browser pengguna dengan session state yang disimpan di server. Selain itu ada juga cookie `XSRF-TOKEN` untuk keperluan keamanan CSRF.

---

### BREAK — Tujuh kerusakan

> Kolom **Prediksi** diisi sebelum mencoba. Kolom **Pesan error sebenarnya** diisi setelah menjalankan eksperimen.

| # | Yang dirusak | Prediksi Anda sebelum mencoba | Pesan error sebenarnya |
|---|--------------|-------------------------------|------------------------|
| 1 | Hapus `@csrf` dari form, lalu kirim | Request ditolak oleh middleware VerifyCsrfToken karena token tidak ada | **Error 419 \| Page Expired**. Laravel memblokir request POST tanpa token CSRF yang valid untuk mencegah serangan forgery. |
| 2 | Ganti `$request->validated()` menjadi `$request->all()`, lalu kirim field liar lewat `curl` | Field liar yang tidak ada di formulir (misal `role=admin` atau kolom tambahan lain) akan ikut lolos diproses | **Mass Assignment terbuka**. Semua payload request yang dikirim diterima mentah-mentah. Jika kolom ada di tabel dan lolos `$fillable`, field berbahaya bisa langsung tersimpan ke database. |
| 3 | Hapus validasi `exists:users,id` pada `lecturer_id`, kirim `lecturer_id=99999` | Database menolak karena id dosen 99999 tidak terdaftar di tabel users | **SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails**. Aplikasi crash dengan error 500, bukannya memberikan pesan validasi ramah kepada user. |
| 4 | Hapus validasi `in:...` pada `status`, kirim `status=superadmin` | Nilai enum yang tidak valid lolos ke database dan ditolak oleh tipe kolom ENUM di database | **SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status'** atau query insert error. Data status menjadi kosong/rusak karena nilai tidak sesuai enum database. |
| 5 | Hapus `->withQueryString()`, lakukan pencarian lalu klik halaman 2 | Parameter pencarian di URL hilang saat klik halaman berikutnya | **Filter hilang**. Saat klik ke halaman 2, URL hanya memuat `?page=2`. Kata kunci pencarian (`q`) dan status terbuang, sehingga halaman 2 menampilkan seluruh data tanpa filter. |
| 6 | Ganti `return redirect()` menjadi `return view()` pada `store`, lalu tekan F5 setelah simpan | Browser meminta konfirmasi resubmission dan data bisa masuk ganda | Browser menampilkan dialog **"Confirm Form Resubmission"**. Saat ditekan Continue/F5, browser mengirim ulang payload POST yang sama dan data mata kuliah terduplikasi dua kali di database. |
| 7 | Hapus `old(...)` dari semua input, lalu kirim form dengan satu kesalahan | Isian form yang sudah benar ikut terhapus dan kembali kosong saat validasi gagal | Seluruh form **kosong kembali**. Pengguna dipaksa mengetik ulang semua isian dari awal hanya karena ada satu kolom yang salah. Sangat merugikan kenyamanan pengguna. |

---

### Catatan Uji Coba BREAK Nomor 2 & 3 (via curl):
Uji coba mengirim request POST berbahaya menggunakan perintah curl:
```bash
curl -X POST http://127.0.0.1:8000/courses \
  -H "X-CSRF-TOKEN: <token_csrf_dari_halaman>" \
  -b cookies.txt \
  -d "code=XX01" -d "name=Uji Lapangan" -d "sks=3" \
  -d "lecturer_id=99999" -d "status=superadmin"
```
**Temuan penting**:
1. Tanpa aturan validasi `exists:users,id`, Laravel akan langsung mencoba menjalankan query `INSERT` dengan `lecturer_id=99999`, yang langsung berakibat fatal (crash database constraint error 500).
2. Dengan Form Request dan method `$request->validated()`, Laravel memvalidasi keberadaan `id` tersebut di tabel `users` terlebih dahulu. Jika tidak ada, pengguna diberi pesan kesalahan yang rapi (*"Dosen yang dipilih tidak valid."*) tanpa menyentuh crash database.

---

### CHECKPOINT MINGGU 4

#### 1. Kenapa validasi di JavaScript tidak dianggap keamanan? Peragakan cara melewatinya.
Validasi JavaScript (frontend) dijalankan di browser pengguna (client-side). Pengguna memiliki kendali penuh atas browser mereka dan dapat dengan mudah mematikan JavaScript, mengubah attribute HTML via *Inspect Element* (DevTools), atau bahkan mengirimkan request langsung lewat perkakas HTTP seperti `curl` atau Postman tanpa menyentuh browser sama sekali. Oleh karena itu, validasi frontend hanya berfungsi sebagai kenyamanan tampilan (*UX/convenience*), sementara benteng keamanan yang sesungguhnya **wajib berada di server (backend)**.

#### 2. Apa yang dikembalikan `$request->validated()` dan kenapa lebih aman daripada `$request->all()`?
`$request->validated()` hanya mengembalikan pasangan key-value yang secara eksplisit didefinisikan dan lolos pada method `rules()` di Form Request. Sebaliknya, `$request->all()` mengembalikan semua payload yang dikirimkan oleh client termasuk field-field tersembunyi yang diselipkan penyerang (seperti `role=admin` atau kolom sensitif lainnya). Dengan `$request->validated()`, kita secara otomatis terlindung dari kerentanan *mass assignment*.

#### 3. Jelaskan pola PRG. Apa yang terjadi kalau `store` mengembalikan view?
Pola PRG (*Post → Redirect → Get*) adalah pola standar di mana setelah request `POST` berhasil diproses di server, controller tidak langsung mengembalikan view (`return view()`), melainkan melakukan redirect (`return redirect()`) ke route `GET`.
Jika `store` mengembalikan view secara langsung, halaman browser saat itu masih berada dalam status request `POST`. Jika pengguna tidak sengaja menekan F5 (*refresh*) atau tombol back, browser akan otomatis mengirim ulang data `POST` tersebut (*Confirm Form Resubmission*), yang menyebabkan data yang sama tersimpan berulang kali ke database.

#### 4. Kenapa filter pencarian sebaiknya di query string, bukan session? Beri satu skenario yang rusak kalau dipindah ke session.
Filter pencarian dan halaman adalah bagian dari representasi data yang diminta, sehingga tempat yang benar adalah **Query String** (URL).
Jika filter disimpan di dalam session:
- **Tautan tidak bisa dibagikan**: Pengguna tidak bisa menyalin URL hasil pencarian ke orang lain karena filternya tersimpan di session lokal browser sendiri.
- **Masalah Multi-tab**: Jika pengguna membuka dua tab browser (misal Tab 1 mencari "Basis Data" dan Tab 2 mencari "Pemrograman Web"), session pada server akan tertimpa oleh tab terakhir. Ketika pengguna mengklik halaman 2 di Tab 1, hasil yang keluar malah data dari Tab 2.
- **Tombol Back bermasalah**: Riwayat navigasi browser menjadi tidak konsisten.

#### 5. Apa fungsi `@csrf`? Serangan apa yang dicegahnya, dan bagaimana serangan itu bekerja?
Directive `@csrf` menghasilkan input tersembunyi berisi token rahasia per-sesi (`<input type="hidden" name="_token" value="...">`). Fungsinya mencegah serangan **CSRF (Cross-Site Request Forgery)**.
Serangan CSRF bekerja ketika seorang pengguna yang sedang login di aplikasi kita membuka situs jahat lain di tab berbeda. Situs jahat tersebut diam-diam mengirimkan request `POST` ke aplikasi kita menggunakan kredensial/cookie session pengguna yang masih aktif. Tanpa token `@csrf`, aplikasi kita akan mengira request tersebut sah dari pengguna, padahal dipicu oleh situs jahat. Dengan `@csrf`, Laravel menolak request karena situs jahat tidak mengetahui token rahasia tersebut.

#### 6. Kenapa aturan `unique` pada update perlu `ignore()`?
Pada operasi UPDATE, data yang sedang diedit sudah ada di dalam database dengan kode/email tersebut. Jika kita menggunakan aturan `unique:courses,code` polos tanpa `ignore()`, proses update akan gagal karena Laravel mendeteksi bahwa kode tersebut "sudah digunakan" — padahal yang menggunakan adalah data itu sendiri yang sedang diedit. Dengan menambahkan `Rule::unique('courses', 'code')->ignore($this->course->id)`, Laravel akan mengabaikan id baris yang bersangkutan saat memeriksa keunikan data.
