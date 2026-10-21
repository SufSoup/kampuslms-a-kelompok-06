# Catatan Minggu 3 — Database, Relasi, dan Migrasi

**Nama:** Muhammad Farras Kanz  
**NIM:** 10241047  
**Kelompok:** 06 Kelas A

---

## READ — Membaca skema sebelum menulisnya

### 1. ERD inti KampusLMS

Berikut ERD sederhana dari relasi utama aplikasi.

```text
users (dosen)  1 ───< courses
users          >───< courses          melalui course_user
courses        1 ───< assignments
assignments    1 ───< submissions
users          1 ───< submissions
submissions    1 ─── 1 grades
users (dosen)  1 ───< grades          melalui graded_by
```

Artinya, satu dosen dapat mengampu banyak mata kuliah. Mahasiswa dan mata kuliah mempunyai hubungan banyak-ke-banyak yang dicatat pada tabel `course_user`. Satu tugas memiliki banyak submission, tetapi satu mahasiswa hanya boleh mempunyai satu submission untuk satu tugas. Satu submission hanya mempunyai satu nilai.

### 2. Foreign key dan perilaku saat data induk dihapus

| Foreign key | Perilaku `onDelete` | Alasan |
| --- | --- | --- |
| `courses.lecturer_id -> users.id` | `restrictOnDelete` | Dosen yang masih mengampu mata kuliah tidak boleh langsung dihapus. Admin harus memindahkan atau menangani mata kuliah tersebut lebih dahulu agar data akademik tidak hilang. |
| `course_user.course_id -> courses.id` | `cascadeOnDelete` | Jika mata kuliah dihapus, semua data pendaftaran pada mata kuliah itu juga tidak lagi berguna dan harus ikut terhapus. |
| `course_user.user_id -> users.id` | `cascadeOnDelete` | Jika akun mahasiswa benar-benar dihapus, data keikutsertaannya pada kelas ikut dihapus agar tidak ada data relasi tanpa pengguna. |
| `assignments.course_id -> courses.id` | `cascadeOnDelete` | Tugas tidak dapat berdiri tanpa mata kuliah. Menghapus mata kuliah juga menghapus tugas-tugasnya. |
| `assignments.created_by -> users.id` | default database (`restrict`) | Pembuat tugas tidak boleh dihapus selama masih menjadi referensi tugas, agar riwayat pembuat tugas tetap valid. |
| `submissions.assignment_id -> assignments.id` | `cascadeOnDelete` | Submission hanya ada untuk sebuah tugas. Jika tugas dihapus, submission terkait harus ikut dihapus dari database. |
| `submissions.user_id -> users.id` | default database (`restrict`) | Mahasiswa tidak boleh dihapus jika masih memiliki submission yang menjadi rekam akademik, kecuali data tersebut ditangani terlebih dahulu. |
| `grades.submission_id -> submissions.id` | `cascadeOnDelete` | Nilai tidak bermakna tanpa submission. Ketika submission terhapus, nilainya harus ikut terhapus. |
| `grades.graded_by -> users.id` | default database (`restrict`) | Dosen pemberi nilai tidak boleh dihapus begitu saja karena identitas pemberi nilai merupakan bagian dari riwayat akademik. |

### 3. Apa yang terjadi jika seorang dosen dihapus?

Jika dosen tersebut masih tercatat sebagai `lecturer_id` pada tabel `courses`, database menolak penghapusan. Ini karena relasinya memakai `restrictOnDelete`.

Desain ini dipilih agar mata kuliah tidak ikut hilang secara tidak sengaja. Sebelum menghapus akun dosen, admin perlu memindahkan mata kuliah ke dosen lain atau mengarsipkan/menangani mata kuliahnya terlebih dahulu. Data akademik lebih aman karena penghapusan satu akun tidak menghapus banyak data penting secara berantai.

### 4. Mengapa `grades.submission_id` bersifat unique?

`grades.submission_id` diberi `unique()` karena satu submission hanya boleh memiliki satu nilai. Hubungannya adalah satu-ke-satu:

```text
satu submission -> satu grade
```

Index biasa hanya membantu pencarian data; index biasa tetap mengizinkan beberapa baris grade memakai `submission_id` yang sama. `unique` bukan hanya mempercepat pencarian, tetapi juga memaksa aturan bisnis di tingkat database. Dengan begitu, dua dosen atau dua request tidak dapat tanpa sengaja membuat dua nilai untuk satu submission.

---

## BREAK — Lima kerusakan yang perlu dipahami

| # | Yang dicoba | Hasil yang harus diamati dan penjelasan |
| --- | --- | --- |
| 1 | Menghapus `unique(['course_id', 'user_id'])` dari `course_user`, lalu mendaftarkan mahasiswa yang sama dua kali. | Data pendaftaran ganda dapat masuk. Akibatnya, satu mahasiswa dapat muncul dua kali pada daftar peserta mata kuliah. Unique gabungan mencegah pasangan mahasiswa dan mata kuliah yang sama disimpan lebih dari sekali. |
| 2 | Menambahkan `role` ke `$fillable` pada model `User`, lalu mengirim request pembuatan user dengan `role=admin` dari form yang tidak mempunyai field role. | Terjadi mass assignment. Laravel menerima nilai `role` dari request saat memakai `User::create($request->validated())` atau `fill()`, sehingga pengguna dapat membuat akun dengan role admin tanpa tombol khusus di form. |
| 3 | Mengganti `$fillable` menjadi `protected $guarded = [];`, lalu mengulangi percobaan nomor 2. | Semua kolom model boleh diisi mass assignment, termasuk `role`, `id`, atau kolom sensitif lain yang mungkin ditambahkan nanti. Karena itu `$guarded = []` berbahaya: model tidak lagi mempunyai daftar aman yang jelas. |
| 4 | Mengosongkan method `down()` pada salah satu migrasi, lalu menjalankan `php artisan migrate:refresh`. | Laravel tidak dapat membatalkan migrasi tersebut dengan benar. Tabel/struktur lama bisa tertinggal dan proses refresh atau CI gagal. Setiap `up()` harus mempunyai pasangan `down()` yang membatalkan perubahan tersebut. |
| 5 | Mengganti `restrictOnDelete` pada `courses.lecturer_id` menjadi `cascadeOnDelete`, lalu menghapus satu dosen. | Mata kuliah yang diampu dosen itu ikut terhapus. Karena mata kuliah memiliki relasi cascade ke tugas, enrollment, dan data lain, penghapusan dapat merambat menjadi kehilangan data akademik dalam jumlah besar. |

---

## Kesimpulan

Migrasi bukan hanya tempat membuat tabel. Foreign key, `unique`, dan aturan `onDelete` adalah pagar di tingkat database. Pagar ini tetap bekerja walaupun ada bug di controller atau request dari pengguna dimanipulasi. Karena itu, sebelum menulis kode aplikasi, skema dan aturan hubungan datanya harus dipahami terlebih dahulu.
