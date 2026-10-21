# Dokumentasi Praktik Minggu 7 — Autentikasi dan Otorisasi

## Nama: Muhammad Yusuf Pratama

## NIM: 10241053

## READ — Mengenali lapisan pertahanan

Autentikasi memastikan identitas pengguna; otorisasi memutuskan tindakan apa yang boleh dilakukan identitas tersebut. Role middleware dapat membatasi kelompok rute, sedangkan Policy memeriksa tindakan terhadap objek tertentu. Keduanya tetap perlu disertai pembatasan query agar daftar tidak lebih luas dari hak pengguna.

Pada login web, `AuthController` memeriksa kredensial dan mengganti ID session dengan `session()->regenerate()` setelah berhasil. Saat logout, session dibatalkan dan token CSRF diperbarui. Model `User` menggunakan cast `hashed` untuk password dan tidak memasukkan kolom `role` ke `$fillable`.

## BREAK — Titik rawan IDOR

| Aksi yang dicoba | Perlindungan yang diperlukan |
|---|---|
| Dosen A membuka atau mengubah mata kuliah Dosen B | Policy course memeriksa `lecturer_id` terhadap pengguna yang login. |
| Mahasiswa membuka mata kuliah yang tidak diambil | Policy course memeriksa enrollment melalui relasi mahasiswa. |
| Mahasiswa membaca submission orang lain | Policy submission membandingkan pemilik submission dengan ID pengguna aktif. |
| Dosen melihat tugas di course lain | Policy assignment membandingkan dosen dengan pengampu course tugas. |
| Mahasiswa meminta tugas draft | Policy atau query memastikan hanya tugas `published` yang dapat dilihat. |
| Pengguna memanggil URL secara langsung setelah tombol disembunyikan | Controller tetap harus menjalankan otorisasi di server. |
| Mahasiswa menyisipkan `role=admin` pada payload | Mass assignment tidak boleh mengizinkan perubahan role melalui input umum. |

Route model binding hanya menemukan record berdasarkan parameter URL; ia tidak membuktikan bahwa pengguna berhak atas record tersebut. Demikian pula, `@can` di Blade hanya mengatur tampilan antarmuka dan tidak menggantikan pemeriksaan backend.

## BUILD — Otorisasi KampusLMS

Route web dipisahkan dengan middleware `auth` dan grup peran `admin`, `dosen`, serta `mahasiswa`. `bootstrap/app.php` mendaftarkan alias middleware role, sesuai struktur Laravel 12 yang tidak menggunakan `app/Http/Kernel.php`.

Policy yang tersedia memberi aturan per objek untuk course, assignment, material, submission, dan grade. Contohnya:

- `CoursePolicy` memberi akses detail kepada admin, dosen pengampu, atau mahasiswa yang terdaftar.
- `AssignmentPolicy` membatasi dosen pada tugas di course yang diampu dan mahasiswa pada tugas published di course yang diikuti.
- `SubmissionPolicy` membatasi mahasiswa pada submission miliknya dan dosen pada submission di course yang dia ampu.
- `MaterialPolicy` memeriksa peran serta kepemilikan/enrollment pada course terkait.

Controller menggunakan pemeriksaan backend seperti `Gate::authorize()` atau pemeriksaan kepemilikan sebelum menampilkan atau mengubah objek. `CourseController::index()` membangun query berbeda menurut peran, sehingga data tidak diambil seluruhnya untuk kemudian disaring di view.

## Verifikasi melalui feature test

`MilestoneTwoAuthzTest` mencakup antara lain pengelolaan assignment oleh dosen yang tepat, penolakan dosen lain, pembatasan mahasiswa ke tugas published, serta larangan membaca submission milik mahasiswa lain. `WeekFiveIdorTest` menguji akses berbasis ID dan perilaku nested route. Pengujian ini penting karena tampilan tombol saja tidak membuktikan endpoint terlindungi.

Jalankan dari direktori `lms/`:

```bash
php artisan test --filter=MilestoneTwoAuthzTest
php artisan test --filter=WeekFiveIdorTest
```

## Checkpoint Minggu 7

- Autentikasi menjawab “siapa pengguna”; otorisasi menjawab “apa yang boleh ia lakukan”.
- Regenerasi session setelah login membantu mencegah session fixation.
- Hash password bersifat satu arah; reset password dilakukan melalui mekanisme reset, bukan dengan membaca password lama.
- Akses objek dan daftar harus diuji terpisah: Policy menutup objek, query per peran menutup kebocoran daftar.
- Setelah perubahan otorisasi, uji jalur sukses dan jalur penolakan (403), termasuk akses langsung ke URL atau API.
