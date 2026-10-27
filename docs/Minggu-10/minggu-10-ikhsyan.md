# Notes Minggu 10 Pemrograman Web

**Nama:** Muhammad Ikhsyan  
**NIM:** 10241049  
**Kelompok:** Kelompok 06 (Kelas A)  
**Topik:** Milestone M3 — Queue, Event, Listener, & Asynchronous Notifications  
**Branch:** `feat/w10-notification-engine` / `ikhsyan`

---

## 1. READ — Ikuti Satu Job (Queue & Event Execution Flow)

### 1. Matikan worker. Buat satu tugas baru dengan status published. Buka tabel `jobs` — apa isinya?
**Jawaban:**  
Ketika worker tidak berjalan (`php artisan queue:work` mati) dan dosen mempublikasikan tugas baru dengan `QUEUE_CONNECTION=database`:
- Tabel `jobs` bertambah 1 baris antrean.
- Kolom `id`: Primary key integer bertambah.
- Kolom `queue`: `'default'` (nama antrean standar).
- Kolom `attempts`: bernilai `0` karena belum ada worker yang memprosesnya.
- Kolom `reserved_at`: bernilai `NULL` (belum dikunci oleh proses manapun).
- Kolom `available_at` dan `created_at`: mencatat timestamp saat event dipicu.
- Mahasiswa belum menerima notifikasi di tabel `notifications` sama sekali.

### 2. Baca kolom `payload`. Bisakah Anda mengenali nama Listener dan ID tugasnya?
**Jawaban:**  
Bisa. Kolom `payload` menyimpan string JSON terserialisasi yang berisi metadata pekerjaan Laravel:
```json
{
  "uuid": "4c424a71-61cf-4186-9812-706f9ad5e5c8",
  "displayName": "App\\Listeners\\SendAssignmentNotification",
  "job": "Illuminate\\Queue\\CallQueuedHandler@call",
  "maxTries": 3,
  "delay": null,
  "timeout": null,
  "data": {
    "commandName": "Illuminate\\Events\\CallQueuedListener",
    "command": "O:36:\"Illuminate\\Events\\CallQueuedListener\":...s:10:\"assignment\";O:45:\"Illuminate\\Contracts\\Database\\ModelIdentifier\":...s:2:\"id\";i:1;..."
  }
}
```
Di dalam payload terserialisasi tersebut, terlihat jelas bahwa class yang akan dijalankan adalah `App\Listeners\SendAssignmentNotification`, event yang dibawa adalah `App\Events\AssignmentPublished`, dan model binding membawa ID tugas `i:1` melalui mekanisme `SerializesModels`.

### 3. Jalankan `php artisan queue:work`, amati keluarannya. Periksa tabel `jobs` lagi.
**Jawaban:**  
Keluaran di terminal worker:
```
[2026-10-27 10:15:00][1] Processing: App\Listeners\SendAssignmentNotification
[2026-10-27 10:15:01][1] Processed:  App\Listeners\SendAssignmentNotification (112.45ms)
```
Setelah job selesai diproses (*Processed*), baris pada tabel `jobs` otomatis dihapus oleh Laravel untuk menghemat ruang basis data karena job telah berhasil tuntas tanpa kegagalan.

### 4. Periksa tabel `notifications`. Berapa baris yang muncul? Cocok dengan jumlah mahasiswa?
**Jawaban:**  
Jumlah baris yang muncul pada tabel `notifications` bertambah tepat sejumlah mahasiswa yang terdaftar (*enrolled*) pada mata kuliah bersangkutan. Jika mata kuliah memiliki 5 mahasiswa terdaftar, maka tepat 5 baris baru masuk dengan `notifiable_type = 'App\Models\User'` dan `notifiable_id` sesuai ID masing-masing mahasiswa. Mahasiswa lain yang tidak mengambil mata kuliah tersebut terbukti tidak mendapatkan baris notifikasi apapun.

### 5. Baca kolom `data` pada satu notifikasi. Adakah yang tidak seharusnya tersimpan di situ?
**Jawaban:**  
Kolom `data` berisi JSON terformat:
```json
{
  "assignment_id": 1,
  "course_name": "Pemrograman Web",
  "title": "Tugas 1 Pemrograman Web",
  "due_at": "2026-11-03T10:00:00+00:00",
  "url": "http://localhost/mahasiswa/assignments/1"
}
```
Seluruh data yang tersimpan adalah data publik/operasional navigasi. **Sama sekali tidak memuat data sensitif**, nilai (`score`), maupun umpan balik dosen (`feedback`). Hal ini sesuai ketat dengan ketentuan keamanan arsitektur `modul.md §10.1` dan `CLAUDE.md`.

---

## 2. BREAK — Delapan Kerusakan (Tabel Eksperimen & Analisis Kegagalan)

| # | Yang Dicoba | Prediksi | Hasil Sebenarnya & Analisis |
|---|-------------|----------|-----------------------------|
| **1** | Set `QUEUE_CONNECTION=database`, jangan jalankan worker, buat tugas baru | Aplikasi sukses disimpan, tetapi antrean mandek di basis data | **GAGAL TOTAL TANPA PESAN ERROR:** HTTP respons 302 Redirect dengan flash `success` muncul normal di UI dosen. Dosen mengira notifikasi sudah terkirim, namun tabel `notifications` kosong melompong. Data terjebak di tabel `jobs`. Ini adalah bahaya terbesar sistem asinkron di server produksi jika process supervisor (Supervisord/systemd) mati. |
| **2** | Hapus `implements ShouldQueue` dari Listener, buat tugas untuk 40 mahasiswa | Request HTTP POST dosen menjadi lambat karena pemrosesan notifikasi dijalankan synchronous di web thread | **TERBUKTI:** Waktu respons HTTP melonjak tajam dari ~80ms menjadi >1.8 detik karena thread web harus mengeksekusi 40 query insert notifikasi satu per satu sebelum mengembalikan respons redirect ke browser dosen. |
| **3** | Ganti `QUEUE_CONNECTION=sync`, ulangi nomor 2 | Perilaku identik dengan synchronous listener | **TERBUKTI:** Driver `sync` mengeksekusi job antrean secara in-process saat itu juga. Pengguna mengalami kelambatan UI yang sama persis dengan meniadakan `ShouldQueue`. Driver `sync` hanya cocok untuk lingkungan pengujian otomatis (PHPUnit/Pest). |
| **4** | Buat Notification melempar `throw new Exception()`, jalankan worker | Worker mencoba ulang sesuai batas `tries`, lalu memasukkannya ke `failed_jobs` | **TERBUKTI:** Worker memproses job, mencatat kegagalan, mencoba lagi setelah interval `backoff = 10` detik hingga 3 kali (`tries = 3`). Setelah percobaan ke-3 gagal, job dihapus dari `jobs` dan dipindahkan ke tabel `failed_jobs` lengkap dengan stack trace eksepsinya. Method `failed()` mencatat log error. |
| **5** | Hentikan worker secara paksa (`kill -9`) di tengah pemrosesan job | Job terkunci sementara di `reserved_at`, lalu tersedia kembali setelah batas timeout | **TERBUKTI:** Baris pada tabel `jobs` memiliki kolom `reserved_at` terisi timestamp. Karena worker mati mendadak sebelum menandai selesai, job tidak hilang. Setelah durasi `retry_after` (default 90 detik pada database queue) terlewati, worker baru yang dinyalakan akan mengambil kembali (*re-acquire*) job tersebut. |
| **6** | Masukkan nilai mahasiswa (`score`) dan komentar (`feedback`) ke payload notifikasi database | Nilai pribadi mahasiswa terekspos ke semua pihak yang memiliki akses ke tabel `notifications` | **KEBOCORAN DATA PERMANEN (SECURITY BREACH):** Kolom `notifications.data` disimpan dalam bentuk teks biasa (plain JSON) yang tidak terenkripsi. Pengguna dengan hak baca database (DBA, admin, auditor) atau kebocoran SQL dapat membaca nilai seluruh mahasiswa tanpa batasan otorisasi. Nilai akademik harus selalu dirujuk melalui ID relasi di tabel `grades`, bukan di-embed ke notifikasi. |
| **7** | Panggil endpoint `POST /notifications/{id}/read` menggunakan UUID notifikasi milik pengguna lain | Terjadi IDOR (Insecure Direct Object Reference) pada sistem notifikasi | **IDOR TERBUKTI:** Jika controller menggunakan `DatabaseNotification::findOrFail($id)->markAsRead()` tanpa mengikat ke `auth()->user()->notifications()`, penyerang dapat mengubah status baca seluruh notifikasi pengguna lain hanya dengan menebak/mengirim ID UUID-nya. Solusi wajib: `auth()->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead()`. |
| **8** | Pada Laravel Reverb / WebSockets: gunakan `Channel('notifications')` publik alih-alih `PrivateChannel('users.' . $userId)` | Seluruh pengguna dapat mendengarkan siaran notifikasi pengguna lain | **PENYADAPAN REALTIME:** Klien manapun dapat berlangganan ke channel publik dan membaca pesan notifikasi secara langsung via browser developer tools / websocket sniffer. Wajib menggunakan `PrivateChannel` dengan channel authorization di `routes/channels.php`. |

---

## 3. FIX — Perbaikan Repo Cacat Minggu 10 (`w10`)

Tujuh cacat utama pada kode broken minggu 10 dianalisis dan diperbaiki dengan standar Laravel 12:

1. **Listener Tanpa `ShouldQueue`:**
   - *Cacat:* `SendAssignmentNotification` berjalan synchronous dan memblokir request dosen.
   - *Perbaikan:* Menambahkan kontrak `implements ShouldQueue` dan trait `InteractsWithQueue` sehingga eksekusi dialihkan ke background worker.
2. **Notifikasi N+1 Query:**
   - *Cacat:* Mengambil daftar mahasiswa dengan looping relasi individual yang memicu puluhan query SELECT.
   - *Perbaikan:* Melakukan query terarah dan menggunakan facade `Notification::send($students, ...)` untuk melakukan batch insertion notifikasi secara efisien.
3. **Penyusupan Data Sensitif ke Payload Notifikasi:**
   - *Cacat:* `GradeReleasedNotification` memuat field `'score' => $grade->score` dan `'feedback' => $grade->feedback`.
   - *Perbaikan:* Menghapus seluruh atribut nilai dan feedback dari method `toDatabase()`. Payload hanya memuat `submission_id`, `assignment_title`, `course_name`, dan `url`.
4. **Endpoint Tandai Dibaca Rentan IDOR:**
   - *Cacat:* Controller mencari notifikasi secara global: `DatabaseNotification::find($id)`.
   - *Perbaikan:* Membatasi lingkup query hanya pada notifikasi pengguna yang terautentikasi: `auth()->user()->notifications()->whereKey($id)->firstOrFail()`.
5. **Ketiadaan Konfigurasi `failed_jobs`:**
   - *Cacat:* Kegagalan antrean hilang tanpa rekam jejak.
   - *Perbaikan:* Memastikan migrasi tabel `failed_jobs` aktif dan perintah monitoring `queue:failed` siap digunakan.
6. **Listener Tanpa Batas `tries` dan `backoff`:**
   - *Cacat:* Job bermasalah mengulang tanpa henti (*infinite loop*).
   - *Perbaikan:* Menetapkan `public int $tries = 3;` dan `public int $backoff = 10;` serta menambahkan method `failed()` untuk pencatatan log.
7. **Channel Siaran Publik:**
   - *Cacat:* Event realtime disiarkan lewat channel publik tanpa proteksi token.
   - *Perbaikan:* Memastikan siaran menggunakan `PrivateChannel` dengan otorisasi berbasis sesi/token pengguna.

---

## 4. BUILD — Kontribusi Fitur Muhammad Ikhsyan (`10241049`)

Fokus pengerjaan adalah **Notification Core Engine** (Event, Listener, Notification, Dispatching Triggers, dan Feature Tests):

### 1. Dua Domain Event
- `app/Events/AssignmentPublished.php`:
  Membawa payload model `Assignment $assignment` dengan trait `Dispatchable` dan `SerializesModels`.
- `app/Events/GradeGiven.php`:
  Membawa payload model `Grade $grade` dengan trait `Dispatchable` dan `SerializesModels`.

### 2. Dua Asynchronous Notification (Database Channel)
- `app/Notifications/NewAssignmentNotification.php`:
  - Mengimplementasikan `ShouldQueue` dan menggunakan trait `Queueable`.
  - Saluran `via()`: `['database']`.
  - Payload aman: `assignment_id`, `course_name`, `title`, `due_at`, `url`.
- `app/Notifications/GradeReleasedNotification.php`:
  - Mengimplementasikan `ShouldQueue` dan menggunakan trait `Queueable`.
  - Saluran `via()`: `['database']`.
  - Payload aman (tanpa nilai): `submission_id`, `assignment_title`, `course_name`, `url`.

### 3. Dua Queued Listener dengan Fault-Tolerance
- `app/Listeners/SendAssignmentNotification.php`:
  - Mengimplementasikan `ShouldQueue`.
  - Konfigurasi keandalan: `$tries = 3; $backoff = 10;`.
  - Mengirimkan notifikasi hanya kepada mahasiswa yang terdaftar di kelas (`course->students()`) menggunakan `Notification::send()`.
  - Penanganan kegagalan via method `failed()` dengan logging ke `Log::error`.
- `app/Listeners/SendGradeNotification.php`:
  - Mengimplementasikan `ShouldQueue`.
  - Konfigurasi keandalan: `$tries = 3; $backoff = 10;`.
  - Mengirimkan notifikasi hanya kepada mahasiswa pemilik submission (`$submission->student`).
  - Penanganan kegagalan via method `failed()` dengan logging ke `Log::error`.

### 4. Registrasi & Pemicu Event (Dispatch Triggers)
- Mendaftarkan pemetaan Event-Listener secara eksplisit di `app/Providers/AppServiceProvider.php` via `Event::listen()`.
- Menambahkan trigger dispatch pada `AssignmentController@store` dan `@update` saat status tugas bernilai `'published'`.
- Menambahkan trigger dispatch pada `Api\AssignmentController@store` dan `@update`.
- Menambahkan trigger dispatch pada `Api\SubmissionController@grade` saat dosen memberi/memperbarui nilai tugas.

### 5. Pengujian Fitur Otomatis (`tests/Feature/NotificationEngineTest.php`)
Menulis 6 feature test komprehensif:
1. `test_listeners_implement_should_queue_with_retry_and_backoff`: Memverifikasi kedua listener mengimplementasikan `ShouldQueue` dengan `$tries = 3` dan `$backoff = 10`.
2. `test_publishing_assignment_sends_notification_only_to_enrolled_students`: Memverifikasi hanya mahasiswa terdaftar yang menerima notifikasi saat tugas dirilis.
3. `test_draft_assignment_does_not_send_notifications`: Memverifikasi tugas berstatus draft tidak mengirimkan notifikasi apapun.
4. `test_grading_submission_sends_notification_only_to_submission_owner`: Memverifikasi notifikasi rilis nilai hanya masuk ke mahasiswa pemilik submission.
5. `test_notification_payload_strictly_omits_score_and_feedback`: Memverifikasi ketat tidak ada kebocoran `score` maupun `feedback` di basis data.
6. `test_events_can_be_faked_independently`: Memverifikasi event dapat diuji secara terisolasi menggunakan `Event::fake()`.

**Hasil Pengujian:**
150 tests passed (540 assertions) — 100% PASS.
