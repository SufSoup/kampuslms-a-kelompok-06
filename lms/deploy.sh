#!/usr/bin/env bash
# deploy.sh — Skrip deployment ulang produksi KampusLMS (Laravel 12)
# Sesuai spesifikasi modul.md §12.1 dan prompt pack §12.2 D.

# Hentikan eksekusi segera jika ada perintah gagal, variabel unset terpanggil, atau pipe error.
# Akibat jika dihilangkan: Skrip terus berjalan saat langkah kritis (misal migrasi) gagal,
# meninggalkan server dalam kondisi setengah ter-deploy dan inkonsisten.
set -euo pipefail

# Validasi URL aplikasi untuk health check pasca-deploy.
APP_URL="${APP_URL:-http://127.0.0.1:8000}"

echo "=== [1/8] Mengaktifkan Mode Maintenance ==="
# Menolak request pengguna baru dengan respons 503 dan retry header selama proses berlangsung.
# Akibat jika dihilangkan: Pengguna mengakses aplikasi saat migrasi atau build aset belum selesai,
# memicu error 500 dan potensi race condition transaksi data.
php artisan down --retry=60

# Pastikan mode maintenance dicabut jika script gagal di tengah jalan
trap 'php artisan up || true' ERR

echo "=== [2/8] Memperbarui Dependensi Composer ==="
# Menginstal paket vendor tanpa paket dev dan mengoptimalkan classmap autoloader.
# Akibat jika dihilangkan: Perubahan dependensi tidak terinstal, dan autoloader tidak teroptimasi
# menyebabkan overhead disk I/O lambat pada setiap request PHP.
if ! command -v composer &> /dev/null; then
    echo "[ERROR] Composer tidak ditemukan di PATH."
    exit 1
fi

composer install --no-dev --optimize-autoloader --no-interaction

echo "=== [3/8] Mengompilasi Aset Frontend (Vite) ==="
# Mengompilasi CSS dan JS untuk produksi ke folder public/build.
# Akibat jika dihilangkan: Perubahan tampilan atau script klien tidak terbarui di browser,
# atau file manifest Vite hilang memicu ViteManifestNotFoundException.
# Catatan container Railway: Kompilasi Vite diproses di Docker Stage 1 (node:20-alpine).
# Jika skrip dijalankan di dalam container runtime PHP (Stage 2), npm dilewati dengan aman
# karena berkas public/build/manifest.json sudah siap saji.
if command -v npm &> /dev/null; then
    npm ci && npm run build
elif [[ -f "public/build/manifest.json" ]]; then
    echo "[INFO] npm tidak ada di runtime container, namun aset terkompilasi public/build/manifest.json telah tersedia dari build stage."
else
    echo "[ERROR] npm tidak ditemukan dan public/build/manifest.json tidak ada! Build frontend gagal."
    exit 1
fi

echo "=== [4/8] Menjalankan Migrasi Database ==="
# Menerapkan migrasi skema tabel baru dengan flag --force (wajib di production).
# Akibat jika dihilangkan: Skema database tertinggal dari kode Eloquent, query ke kolom baru
# akan crash dengan SQL error 1054 Unknown column.
php artisan migrate --force

echo "=== [5/8] Memperbarui Seluruh Cache Produksi ==="
# Menggabungkan seluruh file config menjadi satu berkas cache statis.
# Akibat jika dihilangkan: Laravel mem-parse puluhan file config di tiap request, memperlambat TTFB.
php artisan config:cache

# Mengompilasi seluruh rute menjadi satu cache terindeks cepat.
# Akibat jika dihilangkan: Pencocokan rute regex diproses ulang setiap kali request masuk.
php artisan route:cache

# Mengompilasi seluruh view Blade menjadi file PHP statis.
# Akibat jika dihilangkan: Server harus mengompilasi template Blade saat pertama kali diakses user.
php artisan view:cache

# Mendaftarkan event dan queue listener ke dalam cache.
# Akibat jika dihilangkan: Laravel melakukan auto-discovery listener setiap event di-dispatch.
php artisan event:cache

echo "=== [6/8] Me-restart Background Queue Worker ==="
# Memberi sinyal graceful restart ke seluruh instance queue worker yang sedang berjalan.
# Akibat jika dihilangkan: Worker lama di RAM tetap mengeksekusi logika kode versi lama,
# menyebabkan bug notifikasi/grading yang sudah diperbaiki tetap berjalan versi lamanya.
php artisan queue:restart

echo "=== [7/8] Smoke Test Health Check ==="
# Memverifikasi endpoint /up merespons dengan HTTP 200 sebelum aplikasi dibuka ke publik.
# Akibat jika dihilangkan: Aplikasi keluar dari maintenance mode dalam kondisi rusak tanpa terdeteksi.
curl -fsS "${APP_URL%/}/up" > /dev/null || {
    echo "[ERROR] Smoke test endpoint /up gagal! Periksa storage/logs/laravel.log"
    exit 1
}

echo "=== [8/8] Menonaktifkan Mode Maintenance ==="
# Membuka kembali akses aplikasi untuk seluruh pengguna.
# Akibat jika dihilangkan: Aplikasi selamanya berada di mode maintenance (503 Service Unavailable).
php artisan up

echo "[SUKSES] Deployment ulang selesai dan terverifikasi sehat di $APP_URL"
