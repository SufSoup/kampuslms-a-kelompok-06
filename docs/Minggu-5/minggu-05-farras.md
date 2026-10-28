# Catatan Minggu 5 — Route, Middleware, dan IDOR

**Nama:** Muhammad Farras Kanz  
**NIM:** 10241047  
**Kelompok:** 06 Kelas A

---

## READ — Peta route aplikasi

Perintah yang digunakan:

```bash
cd lms
php artisan route:list --except-vendor
```

Pada saat pengecekan, Laravel menampilkan **88 route**. Berikut salinan ringkas output route yang relevan. Route yang memiliki `{...}` adalah route dengan parameter; parameter tersebut perlu diperiksa karena dapat menjadi titik IDOR.

```text
GET|HEAD  admin/courses/{course}                         admin.courses.show
PUT|PATCH admin/courses/{course}                         admin.courses.update
DELETE    admin/courses/{course}                         admin.courses.destroy
GET|HEAD  admin/courses/{course}/edit                    admin.courses.edit
POST      admin/courses/{course}/enrollments             admin.courses.enrollments.store
DELETE    admin/courses/{course}/enrollments/{student}   admin.courses.enrollments.destroy
GET|HEAD  admin/courses/{course}/materials               admin.courses.materials.index
POST      admin/courses/{course}/materials               admin.courses.materials.store
GET|HEAD  admin/courses/{course}/materials/create        admin.courses.materials.create
GET|HEAD  admin/materials/{material}                     admin.materials.show
PUT|PATCH admin/materials/{material}                     admin.materials.update
DELETE    admin/materials/{material}                     admin.materials.destroy
GET|HEAD  admin/materials/{material}/download            admin.materials.download
GET|HEAD  admin/materials/{material}/edit                admin.materials.edit
GET|HEAD  admin/users/{user}                             admin.users.show
PUT|PATCH admin/users/{user}                             admin.users.update
DELETE    admin/users/{user}                             admin.users.destroy
GET|HEAD  admin/users/{user}/edit                        admin.users.edit
PUT|PATCH api/v1/assignments/{assignment}                Api\AssignmentController@update
DELETE    api/v1/assignments/{assignment}                Api\AssignmentController@destroy
GET|HEAD  api/v1/assignments/{assignment}/submissions    Api\SubmissionController@index
POST      api/v1/assignments/{assignment}/submissions    Api\SubmissionController@store
GET|HEAD  api/v1/courses/{course}                        Api\CourseController@show
GET|HEAD  api/v1/courses/{course}/assignments            Api\AssignmentController@index
GET|HEAD  api/v1/courses/{course}/materials              Api\MaterialController@index
GET|HEAD  api/v1/courses/{course}/resources              Api\LearningResourceController@index
POST      api/v1/courses/{course}/resources              Api\LearningResourceController@store
POST      api/v1/notifications/{notification}/read       Api\NotificationController@markAsRead
GET|HEAD  api/v1/resources/{resource}                    Api\LearningResourceController@show
POST      api/v1/resources/{resource}/toggle-read        Api\LearningResourceController@toggleRead
PUT       api/v1/submissions/{submission}/grade          Api\SubmissionController@grade
POST      courses/{course}/resources                     learning-resources.store
GET|HEAD  courses/{course}/resources/create              learning-resources.create
PUT       courses/{course}/resources/{resource}          learning-resources.update
DELETE    courses/{course}/resources/{resource}          learning-resources.destroy
GET|HEAD  courses/{course}/resources/{resource}/edit     learning-resources.edit
GET|HEAD  dosen/assignments/{assignment}                 dosen.assignments.show
PUT|PATCH dosen/assignments/{assignment}                 dosen.assignments.update
DELETE    dosen/assignments/{assignment}                 dosen.assignments.destroy
GET|HEAD  dosen/assignments/{assignment}/edit            dosen.assignments.edit
GET|HEAD  dosen/assignments/{assignment}/submissions     dosen.assignments.submissions.index
GET|HEAD  dosen/courses/{course}                         dosen.courses.show
POST      dosen/courses/{course}/assignments             dosen.courses.assignments.store
GET|HEAD  dosen/courses/{course}/assignments/create      dosen.courses.assignments.create
GET|HEAD  dosen/courses/{course}/materials               dosen.courses.materials.index
POST      dosen/courses/{course}/materials               dosen.courses.materials.store
GET|HEAD  dosen/courses/{course}/materials/create        dosen.courses.materials.create
GET|HEAD  dosen/materials/{material}                     dosen.materials.show
PUT|PATCH dosen/materials/{material}                     dosen.materials.update
DELETE    dosen/materials/{material}                     dosen.materials.destroy
GET|HEAD  dosen/materials/{material}/download            dosen.materials.download
GET|HEAD  dosen/materials/{material}/edit                dosen.materials.edit
GET|HEAD  dosen/submissions/{submission}                 dosen.submissions.show
GET|HEAD  mahasiswa/assignments/{assignment}             mahasiswa.assignments.show
POST      mahasiswa/assignments/{assignment}/submissions mahasiswa.assignments.submissions.store
GET|HEAD  mahasiswa/courses/{course}                     mahasiswa.courses.show
GET|HEAD  mahasiswa/courses/{course}/materials           mahasiswa.courses.materials.index
GET|HEAD  mahasiswa/materials/{material}                 mahasiswa.materials.show
GET|HEAD  mahasiswa/materials/{material}/download        mahasiswa.materials.download
GET|HEAD  mahasiswa/submissions/{submission}             mahasiswa.submissions.show
GET|HEAD  reset-password/{token}                         password.reset
POST      resources/{resource}/toggle-read               learning-resources.toggle-read
GET|HEAD  submissions/{submission}/download              submissions.download
```

Route tanpa parameter, seperti `/dashboard`, `/login`, `admin/courses`, dan `mahasiswa/assignments`, juga ada pada output penuh tetapi tidak masuk daftar titik IDOR karena tidak memilih satu record berdasarkan ID dari URL.

## Daftar Titik Rawan IDOR

| Parameter / kelompok route | Siapa yang seharusnya boleh mengakses? | Perlindungan yang ada saat ini | Risiko bila pengecekan objek dihapus |
| --- | --- | --- | --- |
| `{course}` pada admin | Admin | Middleware `auth` + `role:admin`, lalu `CoursePolicy` pada controller | Pengguna non-admin dapat melihat atau mengubah mata kuliah lewat ID URL. |
| `{course}` pada dosen | Dosen pengampu mata kuliah | Middleware `role:dosen` dan `CoursePolicy::view` | Dosen A dapat membuka mata kuliah dosen B jika hanya mengandalkan middleware role. |
| `{course}` pada mahasiswa | Mahasiswa yang terdaftar | Middleware `role:mahasiswa` dan `CoursePolicy::view` | Mahasiswa dapat melihat mata kuliah yang tidak diambil dengan mengganti ID. |
| `{assignment}` | Admin, dosen pengampu, atau mahasiswa terdaftar untuk tugas published | `AssignmentPolicy` melalui `Gate::authorize()` | Mahasiswa dapat membaca tugas kelas lain atau dosen mengubah tugas dosen lain. |
| `{submission}` | Pemilik submission, dosen pengampu, atau admin | `SubmissionPolicy::view` melalui `Gate::authorize()` | Mahasiswa A dapat membuka/unduh submission mahasiswa B hanya dengan mengubah ID. |
| `{material}` | Pengguna yang berhak pada mata kuliah materi | Middleware role dan `MaterialPolicy` di controller | Materi kelas lain dapat dibuka atau diunduh dengan menebak ID. |
| `{user}` | Admin | Middleware `role:admin` dan policy user | Pengguna biasa berpotensi membaca atau mengubah akun lain. |
| `{student}` pada enrollment | Admin dan mahasiswa yang memang terdaftar di course terkait | Route bersarang memakai `scopeBindings()` dan controller/policy | Admin dapat melepaskan mahasiswa dari mata kuliah yang salah jika hubungan parent-child tidak diperiksa. |
| `{resource}` dan `{notification}` | Resource sesuai hak akses; notifikasi hanya pemiliknya | Otorisasi controller/policy | Pengguna dapat menandai baca atau mengubah resource orang lain lewat ID. |
| `{token}` reset password | Hanya pemilik token yang masih valid | Validasi token reset password, bukan role middleware | Token yang bocor dapat dipakai untuk mengganti password pemilik token. |

Catatan penting: middleware `role:dosen` hanya menjawab pertanyaan **“apakah pengguna ini dosen?”** Middleware tersebut tidak menjawab **“apakah course ID 10 milik dosen ini?”**. Pertanyaan kedua wajib dijawab oleh policy atau pengecekan kepemilikan di controller.

---

## BREAK — Enam kerusakan

| # | Yang dicoba | Prediksi dan hasil yang perlu diamati |
| --- | --- | --- |
| 1 | Login sebagai mahasiswa A lalu membuka submission mahasiswa B dengan mengganti angka ID URL. | Ini adalah IDOR nyata jika endpoint hanya memakai model binding tanpa `SubmissionPolicy`. Pada kode saat ini, `Gate::authorize('view', $submission)` seharusnya memberi **403 Forbidden** kepada mahasiswa A. |
| 2 | Membuka `/courses/1/assignments/99` ketika tugas 99 milik mata kuliah lain. | Tanpa scoped binding, Laravel hanya mencari assignment ID 99 tanpa memastikan tugas itu milik course 1. Route bersarang dapat bocor antar parent. |
| 3 | Menambahkan `Route::scopeBindings()` lalu mengulangi nomor 2. | Laravel memastikan model anak benar-benar milik model induk. Jika tidak cocok, responsnya 404, bukan data tugas dari mata kuliah lain. |
| 4 | Mendaftarkan middleware pada `app/Http/Kernel.php` mengikuti tutorial Laravel lama. | Pada Laravel 12 proyek ini, file `app/Http/Kernel.php` tidak ada. Alias middleware didaftarkan di `bootstrap/app.php`, yaitu `role => EnsureUserHasRole::class`. |
| 5 | Memasang `role:admin` pada grup lalu mengaksesnya sebagai dosen. | Middleware menghentikan request dengan **403 Forbidden**, karena pengguna sudah login tetapi rolenya tidak termasuk `admin`. |
| 6 | Sebagai dosen A, mencoba mengedit mata kuliah dosen B. | Kedua pengguna memang lolos middleware `role:dosen`, tetapi itu belum berarti dosen A memiliki course B. Policy harus menolak akses berdasarkan `lecturer_id`. Ini membuktikan middleware saja tidak cukup. |

---

## Kesimpulan

ID di URL bukan bukti kepemilikan. Model binding hanya mencari data berdasarkan ID; `scopeBindings()` memastikan hubungan parent-child pada route bersarang; dan policy memastikan pengguna yang sedang login memang berhak memakai data tersebut. Ketiganya mempunyai tugas yang berbeda dan perlu dipakai bersama.
