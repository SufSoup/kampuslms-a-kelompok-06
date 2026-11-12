# Kontribusi Muhammad Yusuf — Minggu 12

## READ

- Membandingkan jobdesk Minggu 12 dengan keputusan deployment Railway: PHP 8.4, Apache, MySQL, Railway Volume, dan worker service terpisah.
- Meninjau `Dockerfile`, `deploy.sh`, seeder akun demo, panduan deployment, dan skrip audit otorisasi.
- Memastikan panduan membedakan risiko `APP_DEBUG=true` dari kebocoran `.env` akibat document root yang salah.

## BREAK

- **BREAK #6 — permission `storage` menjadi 555:** belum dijalankan. Percobaan memerlukan lingkungan Linux terisolasi dan tidak dilakukan pada storage proyek atau layanan publik.
- **BREAK #8 — secure session cookie melalui HTTP:** belum dijalankan pada perangkat nyata. Hasil dan screenshot akan ditambahkan setelah pengujian aman dilakukan.

## FIX

- Menyamakan versi PHP pada CI, Composer platform, Dockerfile, README, dan jobdesk menjadi PHP 8.4; menghapus `--ignore-platform-reqs` agar Composer memvalidasi platform.
- Menghapus nilai `APP_KEY` dari panduan. Key produksi dibuat khusus dan dimasukkan langsung ke Railway Variables.
- Membuat seeder akun demo menggunakan password dari konfigurasi environment. Seeder menolak password produksi yang kosong, sementara contoh lokal memakai nilai khusus development.
- Membuat skrip audit otorisasi menerima password melalui environment alih-alih mengirim password bawaan `password`.
- Memperbarui jobdesk lokal agar sesuai PHP 8.4 dan menjelaskan bahwa Whoops tidak otomatis menampilkan seluruh berkas `.env`.
- Menandai hasil uji mobile sebagai pending hingga ada eksekusi nyata dan bukti screenshot.

## BUILD

- README memuat URL produksi, akun demo, dan prasyarat lokal dari folder `lms/`.
- Panduan deployment menjelaskan konfigurasi password demo sebelum seeding dan prosedur pengujian mobile tanpa mengklaim hasil yang belum diuji.
- Aplikasi lokal menggunakan SQLite yang sudah tersedia. Saat diverifikasi, `/up` dan `/login` merespons HTTP 200.
- Uji nyata di Railway untuk worker dan persistensi volume tetap menunggu eksekusi Ikhsyan. Pengujian mobile dan BREAK #6/#8 juga belum selesai.
