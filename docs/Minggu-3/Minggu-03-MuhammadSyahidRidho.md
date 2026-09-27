# READ 
## Nama: Muhammad Syahid Ridho       
## NIM: 10241051

## 1. Gambar ulang ERD dari spesifikasi


Relasi utama yang perlu diperhatikan:

- `users` → `courses`
- `users` → `course_user`
- `courses` → `course_user`
- `courses` → `materials`
- `courses` → `assignments`
- `assignments` → `submissions`
- `users` → `submissions`
- `submissions` → `grades`
- `users` → `grades`
- `users` → `notifications`

---

## 2. Perilaku `onDelete` setiap foreign key

| Foreign Key | `onDelete` | Alasan |
|---|---|---|
| `courses.lecturer_id` | **RESTRICT** | Mata kuliah tetap harus dipertahankan ketika dosennya masih menjadi pengajar. |
| `course_user.course_id` | **CASCADE** | Data peserta pada course tersebut tidak diperlukan lagi jika course dihapus. |
| `course_user.user_id` | **CASCADE** | Data pendaftaran user ikut dibersihkan ketika user dihapus. |
| `materials.course_id` | **CASCADE** | Material merupakan bagian dari suatu course, sehingga ikut dihapus ketika course dihapus. |
| `assignments.course_id` | **CASCADE** | Assignment bergantung pada course sehingga ikut terhapus saat course dihapus. |
| `assignments.created_by` | **Sesuai migration** | Menyimpan referensi user yang membuat assignment. |
| `submissions.assignment_id` | **CASCADE** | Submission tidak diperlukan lagi apabila assignment induknya dihapus. |
| `submissions.user_id` | **Sesuai migration** | Menunjukkan mahasiswa yang mengumpulkan submission. |
| `grades.submission_id` | **CASCADE** | Grade bergantung pada submission, sehingga ikut dibersihkan ketika submission dihapus. |
| `grades.graded_by` | **Sesuai migration** | Menyimpan referensi user yang memberikan nilai. |

> Untuk bagian **"sesuai migration"**, ini digunakan apabila migration memang tidak menetapkan `cascadeOnDelete()` atau `restrictOnDelete()` secara eksplisit.

---

## 3. Kalau seorang dosen dihapus, apa yang terjadi pada mata kuliahnya?

Dosen **tidak dapat dihapus apabila masih menjadi pengajar pada suatu mata kuliah**. Database akan menolak proses penghapusan tersebut karena `courses.lecturer_id` menggunakan `restrictOnDelete()`.

### Alasan

Mata kuliah merupakan data akademik yang tidak seharusnya ikut hilang hanya karena data dosennya dihapus. Dengan menggunakan `CASCADE`, penghapusan seorang dosen dapat menyebabkan mata kuliah yang dia ampu ikut terhapus beserta data yang bergantung pada mata kuliah tersebut.

---

## 4. Mengapa `grades.submission_id` menggunakan `unique`, bukan hanya index?

`grades.submission_id` dibuat **unique** karena setiap submission hanya diperbolehkan mempunyai **satu grade**.

Contohnya:

```text
Submission 1 → Grade 80
```

Tidak boleh menjadi:

```text
Submission 1 → Grade 80
Submission 1 → Grade 90
```

Jika hanya menggunakan index, data grade ganda masih bisa masuk. Dengan `unique`, database akan menolak submission yang sama jika sudah memiliki grade.

Jadi, `unique` digunakan untuk **menjaga aturan bahwa satu submission hanya memiliki satu nilai**.

# BREAK — Lima Kerusakan

## 1. Menguji unique pada `course_user`

Pada pengujian pertama, constraint berikut pada tabel `course_user` dihapus sementara:

```php
$table->unique(['course_id', 'user_id']);
```

Setelah itu dicoba memasukkan mahasiswa yang sama ke mata kuliah yang sama lebih dari satu kali.

Hasilnya, database dapat menerima data tersebut karena tidak ada lagi constraint yang mencegah kombinasi `course_id` dan `user_id` berulang.

Kondisi tersebut dapat menyebabkan data enrollment menjadi duplikat. Oleh karena itu, unique composite diperlukan agar satu mahasiswa hanya tercatat satu kali pada mata kuliah yang sama.

Setelah percobaan selesai, constraint dikembalikan:

```php
$table->unique(['course_id', 'user_id']);
```

Kemudian database dikembalikan ke kondisi awal menggunakan:

```bash
php artisan migrate:fresh --seed
```

---

## 2. Menguji mass assignment pada `role`

Pada kondisi normal, `role` tidak terdapat dalam `$fillable` pada model `User`:

```php
protected $fillable = [
    'name',
    'email',
    'password',
    'nim_nip',
];
```

Untuk percobaan, `role` ditambahkan sementara ke `$fillable`. Setelah itu dilakukan request pembuatan user dengan memberikan nilai:

```text
role=admin
```

Walaupun form pembuatan user tidak menyediakan pilihan `role`, nilai tersebut tetap dapat diproses oleh backend ketika menggunakan mass assignment.

Hasil pengujian menunjukkan bahwa user dapat dibuat dengan `role` sebagai `admin`.

Hal ini membuktikan bahwa field pada frontend bukan merupakan perlindungan terhadap mass assignment. Perlindungan harus dilakukan pada model dengan hanya mengizinkan atribut yang memang aman untuk diisi secara mass assignment.

Setelah pengujian, `role` dikeluarkan kembali dari `$fillable`.

---

## 3. Menguji `$guarded = []`

Pada percobaan berikutnya, `$fillable` diganti dengan:

```php
protected $guarded = [];
```

Konfigurasi tersebut membuat tidak ada atribut yang diblokir dari mass assignment.

Akibatnya, field yang seharusnya tidak boleh diubah dari input pengguna, termasuk `role`, dapat ikut diberikan melalui mass assignment.

Hal ini berisiko karena pengguna dapat mengirim atribut sensitif yang seharusnya hanya ditentukan oleh backend.

Karena itu, pada model `User` digunakan `$fillable` secara eksplisit agar hanya atribut tertentu yang dapat diisi melalui mass assignment.

---

## 4. Menguji apakah migration dapat di-rollback

Untuk menguji sifat reversible migration, isi method `down()` pada salah satu migration dikosongkan sementara.

Kemudian dijalankan:

```bash
php artisan migrate:refresh
```

Hasil pengujian menunjukkan bahwa proses migration kembali mengalami masalah karena perubahan yang dibuat oleh `up()` tidak dibatalkan dengan benar.

Sebagai contoh, jika tabel `users` tidak dihapus pada `down()`, saat migration dijalankan kembali database dapat memberikan error karena tabel tersebut masih ada.

Method `down()` harus menyediakan kebalikan dari proses pada `up()`. Contohnya:

```php
public function down(): void
{
    Schema::dropIfExists('users');
}
```

Dengan begitu migration dapat dijalankan maju maupun di-rollback dengan benar.

Setelah pengujian selesai, method `down()` dikembalikan seperti semula dan database dapat di-reset menggunakan:

```bash
php artisan migrate:fresh --seed
```

---

## 5. Menguji `restrictOnDelete` pada `lecturer_id`

Pada migration `courses`, `lecturer_id` menggunakan:

```php
->restrictOnDelete();
```

Untuk percobaan, sementara diubah menjadi:

```php
->cascadeOnDelete();
```

Kemudian dilakukan penghapusan terhadap seorang dosen yang masih digunakan sebagai `lecturer_id` pada sebuah course.

Dengan `cascadeOnDelete()`, course yang memiliki hubungan dengan dosen tersebut dapat ikut terhapus secara otomatis.

Hal ini menunjukkan bahwa penggunaan `CASCADE` pada `lecturer_id` dapat menyebabkan kehilangan data course ketika dosen dihapus.

Karena course merupakan data akademik yang perlu dipertahankan, `lecturer_id` dikembalikan menggunakan:

```php
->restrictOnDelete();
```

Dengan `RESTRICT`, penghapusan dosen yang masih digunakan sebagai lecturer akan ditolak oleh database.

Setelah seluruh percobaan selesai, database dikembalikan ke kondisi awal:

```bash
php artisan migrate:fresh --seed
```

