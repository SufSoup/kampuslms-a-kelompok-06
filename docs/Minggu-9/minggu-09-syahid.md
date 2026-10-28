# Catatan Minggu 9 Pemrograman Web

**Nama:** Muhammad Syahid Ridho  
**NIM:** 10241051  
**Kelompok:** Kelompok 06 (Kelas A)  


---

## 1. READ — Memahami Alur Monitoring Pengumpulan

### 1. Bagaimana dosen membuka halaman monitoring pengumpulan tugas?

**Jawaban:**

Dosen membuka halaman detail tugas, kemudian memilih tautan **Monitoring Pengumpulan**. Tautan tersebut mengarah ke route `dosen.assignments.submissions.index` dengan metode HTTP GET dan URL `/dosen/assignments/{assignment}/submissions`.

Route ini berada di dalam grup middleware dosen sehingga halaman monitoring hanya dapat diakses oleh pengguna yang sudah login dan memiliki peran dosen.

### 2. Bagaimana sistem menentukan mahasiswa yang ditampilkan?

**Jawaban:**

Data mahasiswa diambil dari relasi mahasiswa yang terdaftar pada mata kuliah melalui relasi `students()` milik model `Course`. Query juga membatasi data berdasarkan role `mahasiswa` dan mengurutkannya berdasarkan nama.

Dengan pendekatan ini, dosen dapat melihat seluruh mahasiswa yang terdaftar di mata kuliah terkait, bukan hanya mahasiswa yang sudah mengumpulkan tugas. Mahasiswa yang belum mengumpulkan tetap ditampilkan agar dosen dapat mengetahui siapa saja yang belum menyelesaikan kewajibannya.

### 3. Bagaimana sistem membedakan mahasiswa yang sudah dan belum mengumpulkan?

**Jawaban:**

Controller mengambil relasi `submissions` milik setiap mahasiswa dengan filter `assignment_id` yang sesuai dengan tugas yang sedang dipantau. Jika relasi tersebut berisi data, status mahasiswa ditampilkan sebagai sudah mengumpulkan. Jika tidak ada data, statusnya ditampilkan sebagai belum mengumpulkan.

Controller juga menghitung tiga ringkasan, yaitu jumlah mahasiswa terdaftar, jumlah mahasiswa yang sudah mengumpulkan, dan jumlah mahasiswa yang belum mengumpulkan. Jumlah mahasiswa yang belum mengumpulkan diperoleh dari selisih jumlah terdaftar dan jumlah yang sudah mengumpulkan.

### 4. Informasi apa yang diperlukan dosen untuk memeriksa pengumpulan?

**Jawaban:**

Halaman monitoring menampilkan nama mahasiswa, status pengumpulan, nama asli berkas, ukuran berkas, waktu pengumpulan, dan penanda keterlambatan. Jika pengumpulan sudah memiliki nilai, informasi nilai juga ditampilkan; jika belum, halaman menunjukkan bahwa pengumpulan belum dinilai.

Informasi tersebut membantu dosen memantau progres pengumpulan sekaligus mengetahui tugas yang perlu diperiksa atau dinilai.

### 5. Mengapa halaman monitoring tetap membutuhkan otorisasi di backend?

**Jawaban:**

Menyembunyikan tautan pada tampilan saja tidak cukup untuk melindungi data. Pengguna masih dapat mencoba mengakses URL secara langsung atau mengganti ID tugas pada alamat halaman.

Karena itu, controller menggunakan `Gate::authorize('viewAny', [Submission::class, $assignment])` untuk meminta pemeriksaan dari `SubmissionPolicy`. Dosen yang bukan pengampu mata kuliah terkait harus ditolak, begitu juga mahasiswa yang mencoba membuka halaman monitoring dosen.

---

## 2. BREAK — Delapan Skenario Kerusakan dan Pengujian

Tabel berikut menjelaskan skenario yang dapat digunakan untuk menguji ketahanan fitur monitoring. Skenario yang berpotensi merusak data sebaiknya dilakukan pada lingkungan pengujian, bukan pada data proyek yang sedang digunakan bersama.

| No. | Skenario yang diuji | Risiko atau prediksi | Hasil yang diharapkan |
|---|---|---|---|
| 1 | Menghapus pemanggilan `Gate::authorize()` dari controller monitoring | Pengguna yang tidak berhak berpotensi membuka data pengumpulan tugas. | Pengujian harus mendeteksi bahwa akses tanpa otorisasi tidak lagi terlindungi. |
| 2 | Dosen lain membuka URL monitoring tugas yang bukan miliknya | Data pengumpulan kelas lain berpotensi terbaca. | Server menolak akses dengan HTTP 403. |
| 3 | Mahasiswa membuka URL monitoring dosen secara langsung | Mahasiswa dapat mencoba melewati tampilan yang seharusnya hanya tersedia bagi dosen. | Server menolak akses dengan HTTP 403. |
| 4 | Menghapus filter `assignment_id` pada relasi `submissions` | Pengumpulan dari tugas lain dapat ikut ditampilkan atau memengaruhi status. | Data yang muncul hanya berasal dari tugas yang sedang dipantau. |
| 5 | Mengubah query agar hanya mengambil mahasiswa yang sudah mengumpulkan | Mahasiswa yang belum mengumpulkan akan hilang dari daftar. | Seluruh mahasiswa terdaftar tetap ditampilkan, termasuk yang belum mengumpulkan. |
| 6 | Mengubah perhitungan jumlah mahasiswa yang belum mengumpulkan | Ringkasan jumlah dapat berbeda dari data tabel. | Jumlah belum mengumpulkan sama dengan jumlah terdaftar dikurangi jumlah yang sudah mengumpulkan. |
| 7 | Menghapus eager loading relasi `grade` | Informasi nilai dapat memicu query tambahan atau tidak ditampilkan sesuai kebutuhan. | Nilai tampil jika tersedia, dan status belum dinilai tampil jika belum ada nilai. |
| 8 | Mengganti tautan unduh dengan URL berkas fisik di storage | Berkas berpotensi diakses tanpa melewati pemeriksaan hak akses download. | Halaman menggunakan route download yang tersedia dan server tetap memeriksa otorisasi. |

**Catatan hasil pengujian:** tabel ini berisi skenario dan hasil yang diharapkan. Status berhasil atau gagalnya setiap eksperimen harus dicatat berdasarkan pengujian yang benar-benar dijalankan, bukan hanya berdasarkan prediksi.

---

## 3. BUILD — Implementasi Lecturer Submission Monitoring

### 1. Route monitoring — `routes/web.php`

Route GET ditambahkan pada grup dosen dengan nama `dosen.assignments.submissions.index`. Route tersebut menerima parameter tugas sehingga halaman dapat menampilkan data untuk tugas yang dipilih.

Tautan menuju halaman monitoring juga ditambahkan pada `resources/views/assignments/show.blade.php` dan hanya ditampilkan ketika pengguna memiliki role dosen.

### 2. Controller — `app/Http/Controllers/SubmissionController.php`

Method `index(Assignment $assignment)` menangani pengambilan dan pengolahan data monitoring. Method ini menjalankan otorisasi melalui `SubmissionPolicy`, mengambil mahasiswa yang terdaftar pada mata kuliah, serta memuat pengumpulan yang berkaitan dengan tugas yang dipilih beserta relasi nilainya.

Controller kemudian menghitung jumlah mahasiswa terdaftar, sudah mengumpulkan, dan belum mengumpulkan sebelum mengirimkan data ke halaman Blade.

### 3. Antarmuka monitoring — `resources/views/dosen/submissions/index.blade.php`

Halaman monitoring menyajikan data pengumpulan dalam bentuk tabel agar dosen mudah membandingkan status setiap mahasiswa. Informasi yang disediakan mencakup identitas mahasiswa, detail berkas, waktu pengumpulan, keterlambatan, dan nilai.

Halaman juga menangani kondisi mahasiswa belum mengumpulkan, pengumpulan belum dinilai, dan daftar mahasiswa kosong. Tautan detail dan download menggunakan route yang sudah tersedia agar pemeriksaan hak akses tetap dilakukan di server.

### 4. Pengujian otomatis — `tests/Feature/LecturerSubmissionMonitoringTest.php`

Feature test dibuat untuk memeriksa perilaku utama halaman monitoring, termasuk akses dosen pengampu, penolakan akses dosen lain dan mahasiswa, serta tampilan data pengumpulan dan ringkasan jumlah.

Berdasarkan hasil validasi implementasi yang dilaporkan, pengujian monitoring memperoleh **8 test lulus dengan 25 assertions**. Pengujian upload mahasiswa yang sudah ada juga dilaporkan memperoleh **9 test lulus dengan 27 assertions**. Hasil tersebut perlu dijalankan kembali pada kondisi proyek terakhir apabila diperlukan sebagai bukti validasi final.

### 5. Ringkasan perubahan

File yang dimodifikasi:
- `routes/web.php`
- `app/Http/Controllers/SubmissionController.php`
- `resources/views/assignments/show.blade.php`

File yang ditambahkan:
- `resources/views/dosen/submissions/index.blade.php`
- `tests/Feature/LecturerSubmissionMonitoringTest.php`
- `docs/Minggu-9/minggu-09-syahid.md`

Implementasi ini berfokus pada pemantauan pengumpulan tugas oleh dosen. Schema database, migration, policy yang sudah ada, serta alur upload dan download tidak diubah. Perubahan masih berada pada branch `syahid` dan belum dilakukan commit atau push untuk pekerjaan ini.
