# Notes Minggu 10 Pemrograman Web

**Nama:** Muhammad Syahid Ridho

**NIM:** 10241051

**Kelompok:** Kelompok 06 (Kelas A)

**Topik:** Milestone M3 — Implementasi Penilaian Tugas Berbasis Web

**Branch:** `syahid`

---

## 1. READ — Ikuti Satu Job (Queue & Event Execution Flow)

### 1. Matikan worker. Buat satu tugas baru dengan status published. Buka tabel `jobs` — apa isinya?

**Jawaban:**

Ketika worker tidak berjalan dan koneksi antrean menggunakan `database`, pekerjaan yang menggunakan antrean akan menunggu sampai worker dijalankan. Pada implementasi minggu ini, saya berfokus pada proses pemberian nilai melalui web dan integrasinya dengan notifikasi.

Untuk alur penilaian, dosen mengisi nilai dan feedback melalui halaman penilaian. Setelah nilai berhasil disimpan, event `GradeGiven` dapat memicu proses notifikasi secara asinkron. Jika worker belum aktif, job yang masuk ke antrean database belum diproses sehingga notifikasi belum tentu langsung tersedia bagi mahasiswa.

### 2. Baca kolom `payload`. Bisakah Anda mengenali nama Listener dan ID tugasnya?

**Jawaban:**

Payload job Laravel menyimpan informasi mengenai pekerjaan yang akan dijalankan. Pada alur penilaian, listener yang berkaitan dengan pengiriman notifikasi nilai adalah `App\Listeners\SendGradeNotification`.

Informasi model yang diserialisasi dapat digunakan untuk mengenali data yang berkaitan dengan job tersebut. Namun, payload job notifikasi tidak seharusnya menyimpan nilai atau feedback mahasiswa secara langsung. Informasi nilai tetap diambil melalui data penilaian yang tersimpan di database.

### 3. Jalankan `php artisan queue:work`, amati keluarannya. Periksa tabel `jobs` lagi.

**Jawaban:**

Saya menjalankan perintah `php artisan queue:work` melalui terminal terpisah. Pada keluaran terminal, job `App\Listeners\SendGradeNotification` dan `App\Notifications\GradeReleasedNotification` terlihat diproses dengan status `DONE`.

Hasil tersebut menunjukkan bahwa job yang tampil pada terminal berhasil diselesaikan oleh worker. Pada queue database, job yang berhasil diproses akan dihapus dari tabel `jobs`. Pemeriksaan ini membuktikan proses worker berjalan, tetapi tampilan notifikasi pada antarmuka mahasiswa tetap bergantung pada implementasi halaman notifikasi.

### 4. Periksa tabel `notifications`. Berapa baris yang muncul? Cocok dengan jumlah mahasiswa?

**Jawaban:**

Pada alur penilaian, penerima notifikasi yang semestinya adalah mahasiswa pemilik submission yang nilainya baru diberikan atau diperbarui. Notifikasi tidak ditujukan kepada seluruh mahasiswa dalam mata kuliah, karena setiap nilai berkaitan dengan submission tertentu.

Jumlah baris notifikasi untuk proses tersebut perlu diperiksa berdasarkan data yang benar-benar masuk ke tabel `notifications`. Dari pengujian fitur penilaian, saya memverifikasi bahwa notifikasi penilaian ditujukan kepada pemilik submission, bukan mahasiswa lain.

### 5. Baca kolom `data` pada satu notifikasi. Adakah yang tidak seharusnya tersimpan di situ?

**Jawaban:**

Payload notifikasi penilaian sebaiknya hanya memuat informasi yang diperlukan untuk mengenali penilaian dan mengarahkan mahasiswa ke halaman terkait, misalnya ID submission, judul tugas, nama mata kuliah, dan URL.

Nilai serta feedback tidak perlu dimasukkan ke payload karena keduanya merupakan informasi akademik milik mahasiswa. Mahasiswa dapat melihat informasi tersebut melalui halaman tugas setelah aplikasi memeriksa aksesnya. Dengan demikian, payload notifikasi tidak menjadi tempat penyimpanan salinan data penilaian yang sensitif.

---

## 2. BREAK — Delapan Kerusakan (Tabel Eksperimen & Analisis Kegagalan)

Tabel berikut membahas delapan skenario gangguan yang relevan dengan antrean dan notifikasi. Kolom hasil menjelaskan dampak yang diperkirakan pada alur aplikasi; skenario kerusakan ini tidak diklaim telah dijalankan satu per satu.

| # | Yang Dicoba | Prediksi | Hasil Sebenarnya & Analisis |
|---|---|---|---|
| **1** | Set `QUEUE_CONNECTION=database`, jangan jalankan worker, buat tugas baru | Tugas tetap tersimpan, tetapi job notifikasi menunggu di antrean. | Notifikasi asinkron tidak dapat dipastikan terkirim selama worker belum memproses job. Karena itu, keberhasilan menyimpan data tugas atau nilai tidak otomatis membuktikan notifikasi sudah diterima mahasiswa. |
| **2** | Hapus `implements ShouldQueue` dari listener, buat tugas untuk banyak mahasiswa | Proses pengiriman notifikasi berpotensi memperlambat request web. | Listener yang tidak lagi menggunakan antrean dapat menjalankan pekerjaannya secara langsung dalam request. Pada alur penilaian, pemisahan proses notifikasi membantu agar proses penyimpanan nilai tidak perlu menunggu seluruh pekerjaan notifikasi selesai. |
| **3** | Ganti `QUEUE_CONNECTION=sync`, ulangi nomor 2 | Job dijalankan langsung, bukan menunggu worker database. | Driver `sync` menjalankan pekerjaan secara langsung. Perilaku ini berbeda dari antrean database dan dapat membuat proses notifikasi menambah waktu respons ketika jumlah penerima atau pekerjaan meningkat. |
| **4** | Buat Notification melempar `throw new Exception()`, jalankan worker | Job mengalami kegagalan ketika diproses. | Jika terjadi exception, job dapat dicoba kembali sesuai konfigurasi retry. Setelah batas percobaan habis, job dapat masuk ke `failed_jobs` apabila konfigurasi antrean mendukungnya. Skenario ini perlu diuji tersendiri untuk memastikan perilaku retry aktual. |
| **5** | Hentikan worker secara paksa (`kill -9`) di tengah pemrosesan job | Job yang belum selesai berpotensi diproses ulang. | Worker yang berhenti mendadak dapat meninggalkan job dalam kondisi sedang diproses. Mekanisme `retry_after` pada queue database membantu membuat job tersedia kembali setelah waktu tertentu, sehingga kondisi ini perlu diperhatikan untuk menghindari pekerjaan yang terhenti. |
| **6** | Masukkan nilai mahasiswa (`score`) dan komentar (`feedback`) ke payload notifikasi database | Informasi nilai dan feedback berisiko terbaca dari tabel notifikasi. | Nilai dan feedback tidak seharusnya dimasukkan ke payload notifikasi. Pada fitur yang saya kerjakan, nilai dan feedback disimpan melalui alur penilaian dan ditampilkan pada halaman yang memiliki pembatasan akses. Pengujian notifikasi juga memeriksa agar payload tidak memuat `score` dan `feedback`. |
| **7** | Panggil endpoint `POST /notifications/{id}/read` menggunakan UUID notifikasi milik pengguna lain | Jika pencarian notifikasi tidak dibatasi pemiliknya, status baca pengguna lain berisiko diubah. | Endpoint penandaan notifikasi sebagai telah dibaca perlu membatasi query berdasarkan pengguna yang sedang login. Skenario ini termasuk bagian keamanan sistem notifikasi dan harus diverifikasi pada implementasi endpoint notifikasi, bukan hanya dari halaman penilaian. |
| **8** | Pada Laravel Reverb / WebSockets: gunakan `Channel('notifications')` publik alih-alih `PrivateChannel('users.' . $userId)` | Pengguna lain berpotensi menerima siaran notifikasi yang bukan miliknya. | Channel publik dapat membuka akses pesan kepada pihak yang tidak berhak jika tidak ada pembatasan lain. Implementasi WebSocket dan otorisasi channel merupakan bagian terpisah dari fitur penilaian web yang saya kerjakan; skenario ini tidak diklaim telah diuji langsung. |

---

## 3. BUILD — Kontribusi Fitur Muhammad Syahid Ridho (`10241051`)

Fokus pengerjaan saya adalah **alur penilaian tugas melalui web**, meliputi pemberian nilai oleh dosen, tampilan nilai dan feedback bagi mahasiswa, rekap nilai, serta pengujian otorisasi.

### 1. Formulir pemberian dan perubahan nilai

Berkas utama: `resources/views/dosen/submissions/grade.blade.php`

Saya menambahkan halaman yang memungkinkan dosen memberikan nilai dan feedback pada submission mahasiswa. Dosen juga dapat membuka kembali formulir tersebut untuk memperbarui penilaian yang sudah diberikan.

### 2. Validasi nilai dan otorisasi

Berkas utama: `app/Http/Controllers/SubmissionController.php`

Proses penyimpanan nilai memvalidasi agar nilai berada pada rentang 0 sampai `max_score` milik tugas. Otorisasi menggunakan `GradePolicy` yang sudah tersedia, sehingga dosen tidak boleh memberikan nilai pada submission di luar kewenangannya. Identitas penilai diambil dari pengguna yang sedang login, bukan dari input bebas pada browser.

### 3. Tampilan nilai bagi mahasiswa

Berkas utama: `resources/views/assignments/show.blade.php`

Halaman detail tugas menampilkan nilai, nilai maksimum, feedback dosen, dan informasi penilaian ketika submission sudah dinilai. Kondisi submission yang belum dinilai juga ditangani agar mahasiswa tidak melihat informasi penilaian yang belum tersedia.

### 4. Rekap nilai

Berkas yang ditambahkan:

- `resources/views/dosen/grades/index.blade.php`
- `resources/views/mahasiswa/grades/index.blade.php`

Saya menambahkan halaman rekap nilai untuk dosen dan mahasiswa. Rekap dosen dibatasi sesuai mata kuliah yang menjadi kewenangannya, sedangkan rekap mahasiswa hanya menampilkan data penilaian milik akun yang sedang login.

### 5. Integrasi dengan notifikasi

Alur penilaian diintegrasikan dengan event `GradeGiven` agar pemberian atau perubahan nilai dapat memicu proses notifikasi. Registrasi ganda manual untuk event `GradeGiven` di `AppServiceProvider` juga dihapus karena event sudah didaftarkan melalui mekanisme auto-discovery Laravel.

Perubahan tersebut dilakukan tanpa mengubah skema database, migrasi, maupun kontrak event dan listener yang sudah ada.

### 6. Feature test

Berkas baru: `tests/Feature/GradingWebTest.php`

Pengujian mencakup pemberian nilai oleh dosen yang berwenang, penolakan dosen lain, tampilan nilai mahasiswa, pembatasan akses terhadap nilai mahasiswa lain, validasi rentang nilai, serta rekap nilai.

**Hasil pengujian:**

- `php artisan test --filter=GradingWebTest`: **13 test passed, 64 assertions**.
- `php artisan test`: **163 test passed, 604 assertions**.


