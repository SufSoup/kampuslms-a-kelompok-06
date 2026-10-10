# Dokumentasi Keamanan & Matriks Pertahanan IDOR — KampusLMS

**Proyek:** KampusLMS (Kelas A — Kelompok 06)  
**Mata Kuliah:** Pemrograman Web (SI2514024) — Semester Ganjil 2026/2027  
**Dosen Pengampu:** Aidil Saputra Kirsan  
**Penulis:** Muhammad Ikhsyan (`10241049`)  
**Deliverable:** Milestone M2 (Tugas 2 Minggu 7) & Instrumen Asesmen Uji Keamanan Langsung UTS (Minggu 8)  

---

## 1. Arsitektur Keamanan & Otorisasi KampusLMS (Laravel 12)

Aplikasi KampusLMS menerapkan prinsip *Defense in Depth* (pertahanan berlapis) untuk memastikan seluruh data akademik terisolasi ketat sesuai peran dan kepemilikan objek:

1. **Autentikasi & Session Hardening:**
   - Hashing kata sandi satu arah menggunakan algoritma Bcrypt via Model Cast: `protected function casts(): array { return ['password' => 'hashed']; }`.
   - Pencegahan serangan *Session Fixation* melalui pemanggilan eksplisit `session()->regenerate()` setiap kali kredensial pengguna berhasil diverifikasi pada proses login.
   - Pembersihan session dan invalidasi CSRF token saat logout (`session()->invalidate()` dan `session()->regenerateToken()`).
   - Penolakan login memberikan pesan kesalahan generik (`Email atau kata sandi tidak sesuai`) untuk mencegah *User Enumeration Attack*.

2. **Otorisasi Server-Side (Policy & Gate):**
   - Di Laravel 12, otorisasi objek tidak menggunakan `$this->authorize()` usang, melainkan dipanggil secara eksplisit melalui fasad `Gate::authorize('method', $model)` pada controller.
   - Pengecekan visual pada Blade menggunakan direktif `@can(...)` **hanya berfungsi untuk kenyamanan antarmuka (UX)** agar tombol aksi tersembunyi bagi pengguna yang tidak berhak.
   - Seluruh endpoint mutasi dan detail objek wajib diverifikasi mutlak di backend melalui Policy. Percobaan akses langsung melalui URL acak atau penembakan request via `curl` / Postman akan dicegat oleh Policy dan menghasilkan respons **HTTP 403 Forbidden**.

3. **Pencegahan Kebocoran di Tingkat Query (Query-Level Filtering):**
   - Daftar data pada method `index()` tidak pernah mengambil semua data lalu disaring di view.
   - Data disaring langsung di tingkat basis data menggunakan relasi terotentikasi:
     - Dosen hanya mengambil relasi miliknya: `$user->taughtCourses()`.
     - Mahasiswa hanya mengambil relasi kelas yang diikutinya: `$user->courses()`.

---

## 2. Tabel Titik Rawan IDOR & Matriks Pertahanan

Berikut adalah tabel matriks pengujian keamanan titik rawan *Insecure Direct Object Reference* (IDOR) dan eskalasi hak akses yang telah ditutup dan diverifikasi pada aplikasi KampusLMS:

| # | Endpoint / Route | Metode | Parameter Rawan | Aktor Penyerang | Skenario Serangan | Mekanisme Pertahanan Server | Hasil Uji | Status |
|---|---|---|---|---|---|---|:---:|:---:|
| 1 | `/dosen/courses/{course}` | `GET / PUT` | `{course}` ID milik Dosen B | Dosen A (ID: 2) | Dosen A mencoba membuka atau memperbarui mata kuliah yang diampu Dosen B (ID: 3) | `CoursePolicy::view` & `CoursePolicy::update` memvalidasi `$course->lecturer_id === $user->id`. | **403 Forbidden** | ✅ Ditutup |
| 2 | `/mahasiswa/courses/{course}` | `GET` | `{course}` ID MK yang tidak di-enroll | Mahasiswa A | Mahasiswa A mengganti angka ID di URL untuk mengakses materi dan tugas MK yang tidak ia ikuti | `CoursePolicy::view` memeriksa keberadaan enrollment via `$course->students()->whereKey($user->id)->exists()`. | **403 Forbidden** | ✅ Ditutup |
| 3 | `/materials/{material}` | `GET / PUT / DELETE` | `{material}` ID materi Dosen B | Dosen A / Mahasiswa tak terdaftar | Dosen A mencoba mengedit/menghapus materi yang dibuat oleh Dosen B | `MaterialPolicy::update` & `MaterialPolicy::delete` memeriksa kepemilikan relasi course. | **403 Forbidden** | ✅ Ditutup |
| 4 | `/assignments/{assignment}` | `GET / PUT / DELETE` | `{assignment}` ID tugas Dosen B | Dosen A / Mahasiswa tak terdaftar | Dosen A mencoba mengubah tugas dosen lain; Mahasiswa luar mencoba membuka tugas | `AssignmentPolicy` memeriksa kepemilikan dosen atau status enrollment mahasiswa. | **403 Forbidden** | ✅ Ditutup |
| 5 | `/submissions/{submission}` | `GET` | `{submission}` ID milik Mahasiswa B | Mahasiswa A | Mahasiswa A mengganti ID submission pada URL untuk mengintip/mengunduh berkas tugas Mahasiswa B | `SubmissionPolicy::view` memastikan `$submission->user_id === $user->id` (atau dosen pengampu). | **403 Forbidden** | ✅ Ditutup |
| 6 | `/resources/{resource}/toggle-read` | `POST` | `{resource}` ID sumber belajar | Mahasiswa Luar / Attacker | Penyerang mencoba menandai status baca atas nama mahasiswa lain atau pada MK yang tidak ia ambil (Fitur 17) | `LearningResourcePolicy::toggleRead` memverifikasi enrollment, dan aksi penanda baca diikat mutlak ke `auth()->id()`. | **403 Forbidden** | ✅ Ditutup |
| 7 | `/admin/users` & `/admin/courses` | `ALL` | N/A | Dosen / Mahasiswa | Pengguna non-admin menembak langsung rute administrasi sistem | Middleware `role:admin` mencegat request sebelum masuk controller. | **403 Forbidden** | ✅ Ditutup |
| 8 | `/admin/users/{user}` | `PUT` | `role=admin` pada payload | Attacker | Penyerang menyisipkan field `role=admin` pada update data pengguna | Proteksi mass assignment: kolom `role` tidak ada di properti `$fillable` model `User`. | **Role Tidak Berubah** | ✅ Ditutup |

---

## 3. Skenario Pengujian Langsung (Panduan Uji Tembus UTS Bagian 3)

Penguji UTS dapat menguji langsung skenario pertahanan di atas menggunakan peramban (browser) atau perintah `curl` dengan akun demo bawaan seeder:
- **Admin:** `admin@kampuslms.test` / `password`
- **Dosen Demo (Pengampu MK 1):** `dosen@kampuslms.test` / `password` (ID: 2)
- **Dosen Budi (Pengampu MK 2):** `budi.dosen@kampuslms.test` / `password` (ID: 3)
- **Mahasiswa Demo (Terdaftar di MK tertentu):** `mahasiswa@kampuslms.test` / `password` (ID: 5)

### Uji Coba 1: Dosen A Mengakses Mata Kuliah Dosen B via Browser / cURL
* **Langkah:**
  1. Login ke web sebagai `dosen@kampuslms.test`.
  2. Buka URL: `http://localhost:8000/dosen/courses/2` (di mana mata kuliah ID 2 diampu oleh Budi Santoso).
* **Hasil:** Sistem menampilkan halaman kustom **403 Akses Ditolak**.
* **Pertahanan:** `CoursePolicy::view()` menolak karena `$course->lecturer_id !== auth()->id()`.

### Uji Coba 2: Mahasiswa Mengakses Mata Kuliah yang Tidak Diikuti
* **Langkah:**
  1. Login ke web sebagai `mahasiswa@kampuslms.test`.
  2. Cari ID mata kuliah yang tidak diambil mahasiswa tersebut (misal ID 5).
  3. Buka URL: `http://localhost:8000/mahasiswa/courses/5`.
* **Hasil:** Sistem merespons dengan **403 Akses Ditolak**.
* **Pertahanan:** `CoursePolicy::view()` mengecek relasi pivot `$course->students()->whereKey(auth()->id())->exists()`, yang mengembalikan nilai `false`.

### Uji Coba 3: Anti-IDOR Penanda Baca Fitur 17 (Diferensiasi Kelompok 06)
* **Langkah:**
  1. Mahasiswa membuka sumber belajar pada mata kuliah yang diikutinya.
  2. Mahasiswa menekan tombol **"Tandai Selesai Baca"**.
* **Hasil:** Status baca berhasil diperbarui untuk dirinya sendiri.
* **Pertahanan:** Method `toggleRead()` di `LearningResourceController` tidak menerima input `user_id` dari form (mencegah parameter tampering). Server membaca pengguna langsung dari session aman: `$user = auth()->user()`.

---

## 4. Bukti Otomatisasi: Skrip `scripts/test-authz.sh`

Seluruh skenario pengujian di atas telah dikemas ke dalam skrip pengujian bash otomatis pada repositori kelompok:
```bash
chmod +x scripts/test-authz.sh
./scripts/test-authz.sh
```
Skrip tersebut melakukan login otomatis menggunakan cookie session untuk ketiga peran, menguji endpoint legal (harus HTTP 200), dan menguji seluruh titik rawan serangan IDOR (harus HTTP 403), dengan keluaran status hijau (*PASSED*).
