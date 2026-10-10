## Notes Minggu 7 Pemrograman Web

**Nama:** Muhammad Ikhsyan  
**NIM:** 10241049  
**Kelompok:** Kelompok 06 (Kelas A)  
**Topik:** Autentikasi, Otorisasi, dan Validasi Menyeluruh (Milestone M2 — Akses & Keamanan)

---

### READ — Bedah starter kit autentikasi & otorisasi

#### 1. Berkas mana yang menangani POST login? Method apa?
Jawaban :  
Request HTTP `POST /login` ditangani oleh controller `app/Http/Controllers/AuthController.php` pada method `login(LoginRequest $request)`. Validasi input dilakukan terlebih dahulu secara terpisah di `app/Http/Requests/LoginRequest.php` sebelum isi method controller dieksekusi.

#### 2. Di baris mana `Auth::attempt()` atau setaranya dipanggil?
Jawaban :  
`Auth::attempt($credentials, $remember)` dipanggil langsung di dalam method `login()` pada `app/Http/Controllers/AuthController.php`:
```php
if (Auth::attempt($credentials, $remember)) {
    $request->session()->regenerate();
    return redirect()->intended(route('dashboard'));
}
```
Method ini membandingkan teks kata sandi input dengan hash Bcrypt di basis data secara aman menggunakan fungsi *timing-safe*.

#### 3. Temukan `session()->regenerate()`. Kenapa ia ada di situ?
Jawaban :  
`$request->session()->regenerate()` dipanggil tepat setelah `Auth::attempt()` mengembalikan nilai `true`.  
**Alasan:** Pemanggilan ini wajib untuk mencegah serangan **Session Fixation**. Tanpa regenerasi ID session, penyerang yang berhasil menanamkan Session ID tertentu di peramban korban (misal lewat URL atau XSS) akan tetap memiliki akses sah ke akun korban setelah korban berhasil login. Regenerasi memastikan ID session yang lama dibatalkan dan digantikan ID session baru yang acak dan aman.

#### 4. Di mana kata sandi di-hash? Cari cast `hashed` di model `User`.
Jawaban :  
Kata sandi di-hash secara otomatis melalui method `casts()` pada model `app/Models/User.php`:
```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
```
Di Laravel 12, cast `password => 'hashed'` menerapkan algoritma hashing Bcrypt satu arah (one-way hash) setiap kali atribut `password` diisi atau diperbarui pada model Eloquent, sehingga developer tidak perlu memanggil `Hash::make()` secara manual.

#### 5. Buka DevTools → Cookies sebelum dan sesudah login. Bandingkan nilai cookie session.
Jawaban :  
- **Sebelum login:** Cookie `kampuslms_session` (atau `laravel_session`) memiliki nilai hash string acak sesi *guest* (misal diawali `eyJpdiI6Ilp...`).
- **Setelah login:** Nilai pada cookie `kampuslms_session` berubah sepenuhnya menjadi string terenkripsi baru karena eksekusi `session()->regenerate()`. Hal ini membuktikan bahwa Session ID telah diganti baru oleh Laravel untuk mengikat sesi terautentikasi pengguna.

#### 6. Logout, lalu tekan tombol *back* di peramban. Apa yang terjadi? Kenapa?
Jawaban :  
Saat tombol *back* ditekan, halaman sebelumnya mungkin masih terlihat sekilas di layar karena peramban mengambilnya dari memori cache lokal (*Back-Forward Cache / bfcache*). Namun, ketika pengguna mencoba mengklik tautan atau melakukan aksi apa pun (seperti submit form atau refresh halaman), pengguna langsung dialihkan (*redirected*) ke halaman `/login` dengan status unauthenticated.  
Hal ini terjadi karena controller memanggil `$request->session()->invalidate()` dan `$request->session()->regenerateToken()`, sehingga data autentikasi di server telah terhapus total dan token CSRF lama menjadi tidak berlaku.

---

### BREAK — Delapan kerusakan

> Kolom **Prediksi** diisi sebelum mencoba. Kolom **Hasil sebenarnya** diisi setelah eksperimen langsung.

| # | Yang dicoba | Prediksi sebelum mencoba | Hasil sebenarnya |
|---|-------------|--------------------------|-----------------|
| 1 | Hapus `session()->regenerate()` dari proses login | Sesi tetap berjalan, tetapi rentan terhadap serangan manipulasi sesi | Login tetap berhasil, namun Session ID di cookies tidak berganti sama sekali antara sebelum dan sesudah login. Sesi rentan terhadap *Session Fixation Attack*. |
| 2 | Hapus `Gate::authorize()` dari update controller, biarkan `@can` di Blade | Tombol di halaman web hilang, tetapi route masih bisa ditembak via cURL / Postman | **Tombol Edit memang hilang di tampilan web, namun saat URL endpoint ditembak menggunakan cURL PUT, data mata kuliah tetap berhasil diubah oleh pihak yang tidak berhak.** Ini bukti bahwa `@can` hanya pengaman antarmuka (UX), bukan pelindung otorisasi backend. |
| 3 | Sebagai Dosen A, kirim request PUT ke mata kuliah Dosen B lewat cURL | Request ditolak oleh server | Server merespons dengan **HTTP 403 Forbidden**. `CoursePolicy::update` berhasil mencegat aksi karena `$course->lecturer_id !== auth()->id()`. |
| 4 | Sebagai mahasiswa A, akses URL submission tugas milik mahasiswa B | Mahasiswa A dilarang melihat berkas tugas mahasiswa lain | Server mengembalikan **HTTP 403 Forbidden**. `SubmissionPolicy::view` memeriksa kepemilikan submission (`$submission->user_id === auth()->id()`). Celah IDOR tertutup rapat. |
| 5 | Ubah query `index` menjadi `Course::paginate()` polos tanpa filter peran | Semua mata kuliah muncul di layar mahasiswa | Seluruh mata kuliah (termasuk kelas privat/draft dan MK yang tidak di-enroll) bocor dan tampil di daftar mahasiswa. Terjadi **kebocoran data di level query**. |
| 6 | Kirim payload `role=admin` pada form pembaruan pengguna | Penyerang gagal menaikkan hak akses menjadi admin | Role pengguna tidak berubah. Atribut `role` tidak terdaftar di `$fillable` model `User` dan tidak diambil melalui Form Request, sehingga serangan *Mass Assignment Privilege Escalation* gagal total. |
| 7 | Hapus cast `password => 'hashed'` di model User, buat akun baru | Kata sandi tersimpan sebagai teks biasa | Kata sandi tersimpan dalam bentuk *plaintext* (teks polos tanpa hash) di kolom `password` basis data. Sangat berbahaya jika terjadi kebocoran basis data. |
| 8 | Login di browser A, salin cookie session, lalu tempel di browser B | Browser B otomatis ikut terautentikasi | Browser B langsung masuk ke akun yang sama tanpa perlu login. Ini menunjukkan bahaya pembajakan sesi (*Session Hijacking*) dan menegaskan pentingnya flag cookie `HttpOnly` dan `Secure`. |

---

### FIX — Analisis perbaikan sembilan masalah branch `w07`

Pada branch cacat `w07` (repo latihan), ditemukan 9 celah keamanan kritis yang telah diperbaiki:
1. **`@can` tanpa `Gate::authorize()` di Controller:** Menghapus rasa aman palsu di mana tombol disembunyikan di Blade namun controller mengizinkan siapa saja melakukan update. Solusi: menambahkan `Gate::authorize('update', $model)` pada controller.
2. **Policy yang selalu `return true`:** Memperbaiki method policy yang awalnya mengizinkan semua aksi secara membabi buta, diganti dengan validasi kepemilikan objek riil (`$model->user_id === $user->id` atau relasi `exists()`).
3. **Kebocoran daftar pada Mahasiswa:** Memperbaiki method `index()` yang mengambil seluruh baris data tabel, diganti dengan isolasi query berbasis peran (`match($user->role)`).
4. **Kolom `role` tembus via Mass Assignment:** Menghapus kolom `role` dari `$fillable` model `User` dan memastikan update profil hanya menerima field yang divalidasi aman.
5. **Ketiadaan `session()->regenerate()`:** Menambahkan pemanggilan regenerasi session pada method login untuk menutup celah *Session Fixation*.
6. **Pesan login membocorkan keberadaan email (*User Enumeration*):** Mengubah pesan kesalahan login menjadi satu pesan generik: *"Email atau kata sandi tidak sesuai."*.
7. **Rute dosen tanpa middleware otentikasi:** Mendaftarkan middleware grup `role:dosen` dan `auth` di `routes/web.php`.
8. **Policy memicu query N+1:** Mengganti pengecekan koleksi lambat (`$course->students->contains($user)`) menjadi kueri cepat berbasis basis data menggunakan `exists()`: `$course->students()->whereKey($user->id)->exists()`.
9. **Penolakan salah pada aksi legal mahasiswa:** Memperbaiki bug logika otorisasi pada tugas di mana mahasiswa terdaftar sebelumnya dicekal secara keliru.

---

### BUILD — Rangkuman implementasi Milestone M2 (Akses & Keamanan)

Seluruh komponen sistem untuk Milestone M2 telah terimplementasi dan terintegrasi secara utuh:

1. **Web Authentication & Session Hardening:**
   - Menyediakan form login terproteksi CSRF dengan quick-fill helper 3 akun demo (`admin@kampuslms.test`, `dosen@kampuslms.test`, `mahasiswa@kampuslms.test`).
   - Alur logout aman menggunakan method POST dan regenerasi token CSRF.
   - Layout aplikasi dinamis menampilkan nama user, role badge, dan menu navigasi terpisah untuk masing-masing peran.
   - Dashboard terpadu yang menampilkan ringkasan data spesifik sesuai hak akses.

2. **Arsitektur Policy Laravel 12 & Anti-IDOR:**
   - Dibuat rangkaian Policy: `CoursePolicy`, `MaterialPolicy`, `AssignmentPolicy`, `SubmissionPolicy`, dan `LearningResourcePolicy`.
   - Menggunakan standar Laravel 12 modern: otorisasi backend dieksekusi via `Gate::authorize()` tanpa ketergantungan trait `AuthorizesRequests` yang usang.
   - Tombol antarmuka di Blade dilindungi direktif `@can` sebagai UX lapis luar.

3. **Web CRUD Akademik Lengkap:**
   - **Admin:** Mengelola data pengguna, mata kuliah, dan pendaftaran mahasiswa (*enrollment* ke `course_user`).
   - **Dosen:** Mengelola materi kuliah dan penugasan pada mata kuliah yang diampunya, serta memeriksa pengumpulan tugas mahasiswa.
   - **Mahasiswa:** Mengakses materi, mengumpulkan penugasan kelas yang diikutinya, dan terisolasi dari kelas lain.

4. **Fitur 17 (Diferensiasi Kelompok 06):**
   - Dosen dapat mengurasi referensi belajar tambahan (artikel, video, buku, dokumentasi) dengan penanda rekomendasi.
   - Mahasiswa dapat menandai sudah/belum dibaca dengan tombol interaktif yang terikat langsung ke `auth()->id()`, mencegah manipulasi parameter tanda baca antar mahasiswa.

5. **Uji Keamanan & Skrip Otomatisasi:**
   - Dokumen `docs/keamanan.md` selesai disusun memuat matriks pertahanan IDOR dan panduan cURL untuk asesmen UTS.
   - Skrip pengujian otomatis `scripts/test-authz.sh` dan Feature Test suite lolos 100% tanpa kegagalan.

---

### CHECKPOINT MINGGU 7 — Gerbang interview Tugas 2

#### 1. Apa beda autentikasi dan otorisasi? Tunjukkan satu contoh masing-masing di kode Anda.
Jawaban :
- **Autentikasi (*Authentication*):** Proses membuktikan **siapa Anda** (identitas pengguna).
  *Contoh di kode:* Di `AuthController.php`, pemanggilan `Auth::attempt($credentials)` mencocokkan email dan kata sandi untuk menerbitkan session pengguna.
- **Otorisasi (*Authorization*):** Proses menentukan **apa yang boleh Anda lakukan** (hak akses atas objek tertentu).
  *Contoh di kode:* Di `LearningResourceController.php`, pemanggilan `Gate::authorize('create', [LearningResource::class, $course])` memastikan bahwa hanya dosen pengampu mata kuliah tersebut yang boleh menambah sumber belajar.

#### 2. Tunjukkan Policy yang Anda tulis. Jelaskan tiap barisnya.
Jawaban :  
Policy yang saya tulis adalah `app/Policies/LearningResourcePolicy.php`.
```php
public function toggleRead(User $user, LearningResource $learningResource): bool
{
    // Baris 1: Tolak jika pengguna bukan mahasiswa
    if ($user->role !== 'mahasiswa') {
        return false;
    }

    // Baris 2: Cek di database apakah mahasiswa ini terdaftar (enrolled) di mata kuliah terkait
    return $learningResource->course->students()->whereKey($user->id)->exists();
}
```
- Baris pertama memastikan bahwa hanya pengguna dengan role `mahasiswa` yang boleh menandai status baca (dosen dan admin tidak memiliki pivot penanda baca).
- Baris kedua mengecek relasi pivot `course_user` menggunakan method `whereKey($user->id)->exists()`. Method ini mengeksekusi query SQL `SELECT EXISTS(...)` yang sangat cepat dan hemat memori (mencegah masalah N+1) untuk memastikan mahasiswa tersebut benar-benar mengambil mata kuliah tersebut sebelum diizinkan menandai status baca.

#### 3. Mengapa `@can` di Blade tidak cukup? Peragakan.
Jawaban :  
`@can` di Blade hanyalah filter visual pada sisi *rendering* HTML untuk kenyamanan pengguna (UX). Jika penyerang membuka DevTools dan menyalin URL aksi, atau menggunakan `curl`/Postman:
```bash
curl -X DELETE http://localhost:8000/courses/1/resources/5 -H "Cookie: kampuslms_session=..."
```
Request HTTP tersebut akan langsung sampai ke controller. Jika controller tidak memiliki `Gate::authorize('delete', $resource)`, server akan tetap mengeksekusi perintah penghapusan data tersebut meskipun tombol Hapus di halaman web tidak ditampilkan. Oleh karena itu, otorisasi mutlak wajib ditegakkan di sisi server (controller/policy).

#### 4. Apa perbedaan penyaringan data di level query versus level view?
Jawaban :  
- **Level View (Salah):** Mengambil seluruh data dari basis data (`Course::all()`), lalu melakukan perulangan `@foreach` di Blade dan menyembunyikannya menggunakan `@if($course->lecturer_id === auth()->id())`. Ini sangat berbahaya karena data yang tidak berhak tetap terkirim dari basis data ke memori aplikasi, merusak pagination (*blank page*), dan memicu kebocoran memori.
- **Level Query (Benar):** Membatasi data langsung sejak perintah SQL di database (`auth()->user()->taughtCourses()->paginate()`). Hanya baris data yang sah yang ditarik dari database, sehingga performa optimal dan tidak ada celah kebocoran data.
