#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../lms" && pwd)}"
cd "$APP_DIR"

echo "[INFO] Menjalankan validasi otorisasi KampusLMS (Farraz / material policy)."
php artisan test --filter=WeekFiveIdorTest

if [ $? -eq 0 ]; then
    echo "[PASS] Semua validasi keamanan dasar material dan IDOR berhasil."
else
    echo "[FAIL] Validasi keamanan gagal." >&2
    exit 1
fi
