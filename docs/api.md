# KampusLMS REST API

Dokumen ini menjelaskan endpoint API yang sudah terdaftar di aplikasi. Semua URL menggunakan prefix `/api/v1`, dan response dikirim sebagai JSON.

## Menjalankan aplikasi

Dari direktori `lms/`:

```bash
php artisan migrate:fresh --seed
php artisan serve
```

Server lokal biasanya tersedia di `http://127.0.0.1:8000`. Akun demo:

| Peran | Email | Kata sandi |
|---|---|---|
| Admin | `admin@kampuslms.test` | `password` |
| Dosen | `dosen@kampuslms.test` | `password` |
| Mahasiswa | `mahasiswa@kampuslms.test` | `password` |

Contoh di bawah menggunakan variabel `BASE_URL` dan `TOKEN`:

```bash
BASE_URL=http://127.0.0.1:8000
```

Untuk semua endpoint yang memerlukan login, kirim header:

```text
Accept: application/json
Authorization: Bearer <token>
```

Token didapat dari endpoint login. Jangan memasukkan token ke repository atau membagikannya.

## Ringkasan endpoint yang tersedia

Selain `401` jika tidak ada token dan `422` jika payload gagal validasi, aksi terhadap data yang tidak berhak diakses akan mengembalikan `403` (atau `404` untuk notifikasi milik pengguna lain).

| Method | Endpoint | Akses utama | Sukses |
|---|---|---|---|
| `POST` | `/auth/login` | Publik | `200` |
| `POST` | `/auth/logout` | User login | `200` |
| `GET` | `/me` | User login | `200` |
| `GET` | `/courses` | Admin semua; dosen course yang diajar; mahasiswa course yang diikuti | `200` |
| `GET` | `/courses/{course}` | Admin / dosen pengampu / mahasiswa terdaftar | `200` |
| `GET` | `/courses/{course}/materials` | Admin / dosen pengampu / mahasiswa terdaftar | `200` |
| `GET` | `/courses/{course}/resources` | Dosen pengampu / mahasiswa terdaftar | `200` |
| `POST` | `/courses/{course}/resources` | Dosen pengampu / admin | `201` |
| `GET` | `/resources/{resource}` | User berhak pada course | `200` |
| `POST` | `/resources/{resource}/toggle-read` | Mahasiswa terdaftar | `200` |
| `GET` | `/courses/{course}/assignments` | Dosen pengampu / mahasiswa terdaftar | `200` |
| `POST` | `/assignments` | Dosen pengampu | `201` |
| `PUT`, `PATCH` | `/assignments/{assignment}` | Dosen pemilik tugas | `200` |
| `DELETE` | `/assignments/{assignment}` | Dosen pemilik tugas | `204` |
| `GET` | `/assignments/{assignment}/submissions` | Dosen pembuat tugas | `200` |
| `POST` | `/assignments/{assignment}/submissions` | Mahasiswa terdaftar | `201` |
| `PUT` | `/submissions/{submission}/grade` | Dosen pembuat tugas | `201` baru, `200` diperbarui |
| `GET` | `/notifications` | User login, data milik sendiri | `200` |
| `POST` | `/notifications/{notification}/read` | User login, notifikasi milik sendiri | `200` |

## Autentikasi

### `POST /api/v1/auth/login`

Endpoint publik. Login dibatasi lima percobaan per menit.

Request:

```json
{
  "email": "mahasiswa@kampuslms.test",
  "password": "password",
  "device_name": "browser"
}
```

`device_name` opsional. Response sukses `200` berisi token teks biasa dan data user yang sudah disaring:

```json
{
  "token": "1|...",
  "user": {
    "data": {
      "id": 3,
      "name": "Mahasiswa Demo",
      "email": "mahasiswa@kampuslms.test",
      "role": "mahasiswa",
      "nim_nip": "2024105001",
      "created_at": "2026-10-07T10:00:00+00:00"
    }
  }
}
```

Jika email atau kata sandi salah, API mengembalikan `422` dengan pesan umum `Email atau kata sandi salah.`.

```bash
curl -X POST "$BASE_URL/api/v1/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"mahasiswa@kampuslms.test","password":"password","device_name":"browser"}'
```

### `POST /api/v1/auth/logout`

Mencabut token yang sedang dipakai. Memerlukan token. Response sukses `200`:

```json
{"message":"Berhasil logout."}
```

```bash
curl -X POST "$BASE_URL/api/v1/auth/logout" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### `GET /api/v1/me`

Mengambil profil user berdasarkan token aktif. Response `200` berisi `data` dengan `id`, `name`, `email`, `role`, `nim_nip`, dan `created_at`. Password dan token tidak ikut dikirim.

```bash
curl "$BASE_URL/api/v1/me" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Contoh response:

```json
{"data":{"id":3,"name":"Mahasiswa Demo","email":"mahasiswa@kampuslms.test","role":"mahasiswa","nim_nip":"2024105001","created_at":"2026-10-07T10:00:00+00:00"}}
```

## Mata kuliah, materi, dan sumber belajar

Endpoint mata kuliah membatasi data berdasarkan peran: admin dapat melihat semua mata kuliah, dosen hanya mata kuliah yang diajar, dan mahasiswa hanya mata kuliah yang diikuti. Endpoint detail dan materi mengembalikan `403` jika user tidak berhak mengakses mata kuliah tersebut. Koleksi dipaginasi 15 item per halaman; gunakan `?page=2` untuk halaman berikutnya.

### `GET /api/v1/courses`

Mengambil daftar mata kuliah yang boleh dilihat user. Response `200` berisi `data`, `links`, dan `meta`. Setiap mata kuliah mencakup data dosen pengampu serta jumlah materi dan tugas (`counts.materials`, `counts.assignments`).

```bash
curl "$BASE_URL/api/v1/courses?page=1" \
  -H "Accept: application/json" \
  -H "Authorization: ******"
```

Struktur setiap item pada `data`:

```json
{
  "id": 1,
  "code": "IF101",
  "name": "Pemrograman Web",
  "description": "Pengantar pengembangan aplikasi web.",
  "sks": 3,
  "status": "active",
  "lecturer": {
    "id": 2,
    "name": "Dosen Demo",
    "email": "dosen@kampuslms.test",
    "role": "dosen",
    "nim_nip": "19800101001",
    "created_at": "2026-10-07T10:00:00+00:00"
  },
  "counts": {
    "materials": 4,
    "assignments": 2
  },
  "created_at": "2026-10-07T10:00:00+00:00"
}
```

`meta` memuat `current_page`, `last_page`, dan `total`.

### `GET /api/v1/courses/{course}`

Mengambil detail satu mata kuliah yang dapat diakses user. Response `200` membungkus satu objek mata kuliah di dalam `data`, dengan dosen pengampu dan jumlah materi/tugas seperti pada daftar mata kuliah. ID mata kuliah yang tidak ada menghasilkan `404`; mata kuliah milik dosen lain atau yang tidak diikuti mahasiswa menghasilkan `403`.

```bash
curl "$BASE_URL/api/v1/courses/1" \
  -H "Accept: application/json" \
  -H "Authorization: ******"
```

### `GET /api/v1/courses/{course}/materials`

Mengambil daftar materi dalam mata kuliah yang boleh diakses user. Response `200` berisi `data`, `links`, dan `meta`. Item materi hanya memuat `id`, `course_id`, `title`, `description`, `type`, `original_name`, `file_size`, `mime_type`, `external_url`, dan `created_at`; `file_path` tidak pernah disertakan.

```bash
curl "$BASE_URL/api/v1/courses/1/materials?page=1" \
  -H "Accept: application/json" \
  -H "Authorization: ******"
```

Mahasiswa yang tidak terdaftar dan dosen yang bukan pengampu menerima `403`.

Sumber belajar Fitur 17 memiliki endpoint tersendiri:

### `GET /api/v1/courses/{course}/resources`

Mengambil referensi belajar untuk mata kuliah. Dosen pengampu dan mahasiswa yang terdaftar dapat mengaksesnya. Response `200` berisi `data` (dengan `is_read` untuk user saat ini) beserta metadata pagination.

```bash
curl "$BASE_URL/api/v1/courses/1/resources?page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### `POST /api/v1/courses/{course}/resources`

Menambah sumber belajar. Hanya dosen pengampu atau admin. Response sukses `201`.

Request:

```json
{
  "title": "Dokumentasi Laravel",
  "description": "Panduan framework",
  "type": "documentation",
  "url": "https://laravel.com/docs",
  "source_platform": "Laravel Docs",
  "is_recommended": true
}
```

`type` harus salah satu dari `article`, `video`, `book`, `documentation`, atau `other`.

Contoh cURL:

```bash
curl -X POST "$BASE_URL/api/v1/courses/1/resources" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Dokumentasi Laravel","description":"Panduan framework","type":"documentation","url":"https://laravel.com/docs","source_platform":"Laravel Docs","is_recommended":true}'
```

Response sukses `201` menggunakan objek sumber belajar di dalam `data`, termasuk `id`, `course_id`, `title`, `type`, `url`, `curator`, dan `is_read`.

### `GET /api/v1/resources/{resource}`

Melihat satu sumber belajar. Akses dibatasi untuk admin, dosen pengampu, atau mahasiswa terdaftar pada mata kuliah sumber tersebut. Response sukses `200`.

```bash
curl "$BASE_URL/api/v1/resources/1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### `POST /api/v1/resources/{resource}/toggle-read`

Mahasiswa terdaftar menandai sumber belajar sebagai sudah dibaca atau menghapus tanda baca yang ada. Status hanya berlaku untuk user dari token yang sedang dipakai. Response `200`, misalnya:

```json
{"is_read":true,"read_at":"2026-10-07T10:00:00+00:00","message":"Status sumber belajar ditandai sudah dibaca."}
```

```bash
curl -X POST "$BASE_URL/api/v1/resources/1/toggle-read" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## Tugas, pengumpulan, dan nilai

### `GET /api/v1/courses/{course}/assignments`

Melihat daftar tugas mata kuliah. Dosen harus mengampu mata kuliah tersebut; mahasiswa harus terdaftar. Mendukung `?status=draft` atau `?status=published` dan `?page=2`. Response `200` menggunakan pagination.

```bash
curl "$BASE_URL/api/v1/courses/1/assignments?status=published&page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Koleksi menggunakan bentuk `data`, `links`, dan `meta`; `meta` memuat `current_page`, `last_page`, dan `total`.

### `POST /api/v1/assignments`

Membuat tugas untuk mata kuliah milik dosen yang login. Response sukses `201`.

Request:

```json
{
  "course_id": 1,
  "title": "Tugas API",
  "instructions": "Buat endpoint sederhana.",
  "due_at": "2026-10-14 23:59:00",
  "max_score": 100,
  "allow_late": true,
  "status": "published"
}
```

Contoh cURL:

```bash
curl -X POST "$BASE_URL/api/v1/assignments" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"course_id":1,"title":"Tugas API","instructions":"Buat endpoint sederhana.","due_at":"2026-10-14 23:59:00","max_score":100,"allow_late":true,"status":"published"}'
```

Response sukses `201` berisi data tugas yang dibuat. Dosen yang bukan pengampu course mendapat `403`.

### `PUT` atau `PATCH /api/v1/assignments/{assignment}`

Memperbarui satu atau beberapa field tugas milik dosen yang login. Field yang dapat diperbarui: `title`, `instructions`, `due_at`, `max_score`, `allow_late`, dan `status`. Response sukses `200` dengan data tugas.

```bash
curl -X PATCH "$BASE_URL/api/v1/assignments/1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Judul tugas diperbarui"}'
```

### `DELETE /api/v1/assignments/{assignment}`

Menghapus tugas milik dosen yang login. Response sukses `204 No Content`.

```bash
curl -X DELETE "$BASE_URL/api/v1/assignments/1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### `GET /api/v1/assignments/{assignment}/submissions`

Dosen pembuat tugas melihat daftar pengumpulan. Mendukung pagination; response sukses `200`.

```bash
curl "$BASE_URL/api/v1/assignments/1/submissions?page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### `POST /api/v1/assignments/{assignment}/submissions`

Mahasiswa terdaftar mengirim pengumpulan sebagai `multipart/form-data`. File wajib, maksimum 10 MB; `note` opsional. Mahasiswa hanya dapat mengumpulkan sekali untuk satu tugas. Response sukses `201`.

```bash
curl -X POST "$BASE_URL/api/v1/assignments/1/submissions" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -F "file=@tugas.pdf" \
  -F "note=Pengumpulan tugas"
```

Response sukses `201` berisi `assignment_id`, `user_id`, `original_name`, `file_size`, `submitted_at`, dan status keterlambatan.

### `PUT /api/v1/submissions/{submission}/grade`

Dosen pembuat tugas memberi atau memperbarui nilai. Request:

```json
{"score":90,"feedback":"Bagus."}
```

Nilai baru menghasilkan `201`; memperbarui nilai yang sudah ada menghasilkan `200`. Nilai harus berada pada rentang 0–100.

```bash
curl -X PUT "$BASE_URL/api/v1/submissions/1/grade" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"score":90,"feedback":"Bagus."}'
```

## Notifikasi

### `GET /api/v1/notifications`

Mengambil notifikasi milik user yang sedang login saja. Mendukung `?page=2`, response `200` berisi pagination dan hanya menampilkan `id`, `type`, `data`, `read_at`, dan `created_at`.

```bash
curl "$BASE_URL/api/v1/notifications?page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### `POST /api/v1/notifications/{notification}/read`

Menandai notifikasi milik user yang sedang login sebagai sudah dibaca. Response `200` berisi `id`, `type`, `data`, `read_at`, dan `created_at` pada objek utama (field notifikasi `data` menggunakan nama yang sama dengan pembungkus standar Resource). ID milik user lain tidak dapat digunakan; API mengembalikan `404`.

```bash
curl -X POST "$BASE_URL/api/v1/notifications/$NOTIFICATION_ID/read" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## Error umum

| Status | Arti |
|---|---|
| `401 Unauthorized` | Token tidak ada atau tidak valid. |
| `403 Forbidden` | User terautentikasi tetapi tidak memiliki izin untuk aksi atau data tersebut. |
| `404 Not Found` | Data tidak ditemukan; juga digunakan agar ID notifikasi milik orang lain tidak terbuka. |
| `422 Unprocessable Entity` | Request tidak lolos validasi. Response menyertakan pesan dan rincian `errors`. |

Contoh error autentikasi dan otorisasi:

```json
{"message":"Unauthenticated."}
```

```json
{"message":"Anda tidak memiliki akses ke sumber daya ini."}
```

Error validasi API memiliki format seperti:

```json
{
  "message": "Data yang diberikan tidak valid.",
  "errors": {
    "email": ["Email wajib diisi."]
  }
}
```

## Skrip uji cURL

Skrip `scripts/test-api.sh` menguji request tanpa token (`401`), mahasiswa yang mencoba membuat tugas (`403`), dosen yang mencoba menilai submission dosen lain (`403`), dan request sukses dengan token dosen (`200`). Jalankan server Laravel terlebih dahulu, lalu:

```bash
# Ambil ID submission yang assignment-nya dibuat dosen selain dosen@kampuslms.test.
FOREIGN_SUBMISSION_ID=123 bash scripts/test-api.sh
```

Contoh query MySQL untuk mencari ID submission tersebut setelah database di-seed:

```sql
SELECT submissions.id
FROM submissions
JOIN assignments ON assignments.id = submissions.assignment_id
JOIN users AS assignment_owner ON assignment_owner.id = assignments.created_by
WHERE assignment_owner.email <> 'dosen@kampuslms.test'
LIMIT 1;
```

Skrip menggunakan `curl` dan `jq`. URL server dan akun demo dapat diganti melalui `BASE_URL`, `STUDENT_EMAIL`, `STUDENT_PASSWORD`, `LECTURER_EMAIL`, dan `LECTURER_PASSWORD`.
