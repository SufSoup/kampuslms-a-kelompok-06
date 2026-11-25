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

| # | Endpoint / Route | Metode | Parameter Rawan | Aktor Penyerang | Skenario Serangan | Mekanisme Pertahanan Server | Hasil Uji | Test | Status |
|---|---|---|---|---|---|---|:---:|---|:---:|
| 1 | `/dosen/courses/{course}` | `GET` | `{course}` ID milik Dosen B | Dosen A (ID: 2) | Dosen A mencoba membuka detail mata kuliah yang diampu Dosen B (ID: 3) | `CoursePolicy::view` memvalidasi `$course->lecturer_id === $user->id`. (Rute `PUT` pengubahan mata kuliah hanya berada pada grup `/admin/courses/{course}`.) | **403 Forbidden** | `WeekFiveIdorTest::test_course_show_denies_a_lecturer_who_does_not_teach_it` | [PASS] Ditutup |
| 2 | `/mahasiswa/courses/{course}` | `GET` | `{course}` ID MK yang tidak di-enroll | Mahasiswa A | Mahasiswa A mengganti angka ID di URL untuk mengakses materi dan tugas MK yang tidak ia ikuti | `CoursePolicy::view` memeriksa keberadaan enrollment via `$course->students()->whereKey($user->id)->exists()`. | **403 Forbidden** | `SecurityMatrixCoverageTest::test_unenrolled_student_cannot_view_another_course_detail` | [PASS] Ditutup |
| 3 | `/materials/{material}` | `GET / PUT / DELETE` | `{material}` ID materi Dosen B | Dosen A / Mahasiswa tak terdaftar | Dosen A mencoba mengedit/menghapus materi yang dibuat oleh Dosen B | `MaterialPolicy::update` & `MaterialPolicy::delete` memeriksa kepemilikan relasi course. | **403 Forbidden** | `WeekFiveIdorTest::test_lecturer_can_view_material_only_for_a_course_they_teach`, `WeekFiveIdorTest::test_student_can_view_material_only_for_an_enrolled_course`, `MaterialWebCrudTest::test_another_lecturer_cannot_manage_materials_for_the_course` | [PASS] Ditutup |
| 4 | `/dosen/courses/{course}/assignments` dan `/dosen/assignments/{assignment}`; `/mahasiswa/assignments/{assignment}` | `GET / POST / PUT / DELETE` | `{course}` atau `{assignment}` milik pihak lain | Dosen A / Mahasiswa tak terdaftar | Dosen A mencoba mengubah tugas dosen lain; mahasiswa mencoba membaca tugas draft atau tugas dari kelas yang tidak diikuti | `AssignmentPolicy` memeriksa pengampu, enrollment, dan status `published`; course untuk pembuatan berasal dari route dan pemilik dari session. | **403 Forbidden** | `MilestoneTwoAuthzTest::test_lecturer_cannot_view_or_manage_assignments_for_another_course`, `MilestoneTwoAuthzTest::test_unenrolled_student_cannot_view_an_assignment`, `MilestoneTwoAuthzTest::test_student_cannot_use_lecturer_assignment_crud_routes` | [PASS] Ditutup |
| 5 | `/mahasiswa/submissions/{submission}` dan `/dosen/submissions/{submission}` | `GET` | `{submission}` milik mahasiswa lain atau course dosen lain | Mahasiswa A / Dosen A | Pengguna mengganti ID submission pada URL | `SubmissionPolicy::view` memastikan mahasiswa adalah pemilik submission atau dosen pengampu course. | **403 Forbidden** | `MilestoneTwoAuthzTest::test_student_can_see_only_their_own_submission`, `MilestoneTwoAuthzTest::test_lecturer_can_view_submissions_only_for_their_course`, `SubmissionDownloadSecurityTest::test_other_student_cannot_download_submission` | [PASS] Ditutup |
| 6 | `/resources/{resource}/toggle-read` | `POST` | `{resource}` ID sumber belajar | Mahasiswa Luar / Attacker | Penyerang mencoba menandai status baca atas nama mahasiswa lain atau pada MK yang tidak ia ambil (Fitur 17) | `LearningResourcePolicy::toggleRead` memverifikasi enrollment, dan aksi penanda baca diikat mutlak ke `auth()->id()`. | **403 Forbidden** | `LearningResourceWebTest::test_unenrolled_student_cannot_toggle_read_status_anti_idor`, `LearningResourceWebTest::test_student_sees_only_their_own_read_status_not_the_course_total`, `LearningResourceApiTest::test_student_read_status_is_isolated_per_user_anti_idor` | [PASS] Ditutup |
| 7 | `/admin/users` & `/admin/courses` | `ALL` | N/A | Dosen / Mahasiswa | Pengguna non-admin menembak langsung rute administrasi sistem | Middleware `role:admin` mencegat request sebelum masuk controller. | **403 Forbidden** | `SecurityMatrixCoverageTest::test_non_admin_cannot_access_admin_users_or_courses_routes`, `CourseEnrollmentTest::test_only_admin_can_manage_enrollments` | [PASS] Ditutup |
| 8 | `/admin/users/{user}` | `PUT` | `role=admin` pada payload | Attacker | Penyerang menyisipkan field `role=admin` pada update data pengguna | Proteksi mass assignment: kolom `role` tidak ada di properti `$fillable` model `User`, dan rute dilindungi middleware `role:admin`. | **Role Tidak Berubah** | `SecurityMatrixCoverageTest::test_user_role_cannot_be_changed_via_mass_assignment`, `SecurityMatrixCoverageTest::test_non_admin_cannot_access_admin_users_or_courses_routes` | [PASS] Ditutup |

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
