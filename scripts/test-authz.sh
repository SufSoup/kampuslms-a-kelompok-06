#!/usr/bin/env bash
set -euo pipefail

APP_URL="${APP_URL:-http://127.0.0.1:8000}"
APP_URL="${APP_URL%/}"
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

fail() {
    echo "[FAIL] $*" >&2
    exit 1
}

expect_status() {
    local label="$1"
    local expected="$2"
    local path="$3"
    local cookie_file="$4"
    local actual

    actual="$(curl --silent --show-error --output "$TMP_DIR/response.html" \
        --write-out '%{http_code}' \
        --cookie "$cookie_file" "$APP_URL$path")"

    if [[ "$actual" != "$expected" ]]; then
        fail "$label: diharapkan HTTP $expected, didapat HTTP $actual ($path)"
    fi

    echo "[PASS] $label (HTTP $actual)"
}

login_as() {
    local role="$1"
    local email="$2"
    local password="$3"
    local cookie_file="$TMP_DIR/$role.cookies"
    local login_page="$TMP_DIR/$role-login.html"
    local token
    local status

    curl --silent --show-error --output "$login_page" \
        --cookie-jar "$cookie_file" "$APP_URL/login"
    token="$(sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' "$login_page" | head -n 1)"
    [[ -n "$token" ]] || fail "Token CSRF tidak ditemukan pada halaman login."

    status="$(curl --silent --show-error --output /dev/null \
        --write-out '%{http_code}' \
        --cookie "$cookie_file" --cookie-jar "$cookie_file" \
        --data-urlencode "_token=$token" \
        --data-urlencode "email=$email" \
        --data-urlencode "password=$password" \
        "$APP_URL/login")"
    [[ "$status" == "302" ]] || fail "Login $role gagal (HTTP $status)."

    expect_status "Login/dashboard $role" "200" "/dashboard" "$cookie_file"
}

course_path_from_page() {
    local prefix="$1"
    local page_file="$2"
    local path

    path="$(grep --only-matching --extended-regexp "/$prefix/courses/[0-9]+" "$page_file" | head -n 1 || true)"
    [[ -n "$path" ]] || fail "Tidak ada mata kuliah untuk role $prefix. Pastikan database sudah di-seed."
    printf '%s' "$path"
}

echo "[INFO] Menguji autentikasi web dan otorisasi multi-role di $APP_URL"
login_as "admin" "admin@kampuslms.test" "password"
login_as "dosen" "dosen@kampuslms.test" "password"
login_as "mahasiswa" "mahasiswa@kampuslms.test" "password"

ADMIN_COOKIE="$TMP_DIR/admin.cookies"
DOSEN_COOKIE="$TMP_DIR/dosen.cookies"
MAHASISWA_COOKIE="$TMP_DIR/mahasiswa.cookies"

expect_status "Admin dapat mengakses manajemen mata kuliah" "200" "/admin/courses" "$ADMIN_COOKIE"
expect_status "Admin dapat mengakses manajemen pengguna" "200" "/admin/users" "$ADMIN_COOKIE"

expect_status "Dosen dapat mengakses mata kuliahnya" "200" "/dosen/courses" "$DOSEN_COOKIE"
curl --silent --show-error --output "$TMP_DIR/dosen-courses.html" \
    --cookie "$DOSEN_COOKIE" "$APP_URL/dosen/courses"
DOSEN_COURSE_PATH="$(course_path_from_page "dosen" "$TMP_DIR/dosen-courses.html")"
expect_status "Dosen dapat melihat daftar materi" "200" "$DOSEN_COURSE_PATH/materials" "$DOSEN_COOKIE"
expect_status "Dosen dapat membuka form tambah materi" "200" "$DOSEN_COURSE_PATH/materials/create" "$DOSEN_COOKIE"

expect_status "Mahasiswa dapat mengakses mata kuliah" "200" "/mahasiswa/courses" "$MAHASISWA_COOKIE"
curl --silent --show-error --output "$TMP_DIR/mahasiswa-courses.html" \
    --cookie "$MAHASISWA_COOKIE" "$APP_URL/mahasiswa/courses"
MAHASISWA_COURSE_PATH="$(course_path_from_page "mahasiswa" "$TMP_DIR/mahasiswa-courses.html")"
expect_status "Mahasiswa dapat melihat daftar materi" "200" "$MAHASISWA_COURSE_PATH/materials" "$MAHASISWA_COOKIE"

expect_status "Dosen ditolak dari manajemen admin" "403" "/admin/users" "$DOSEN_COOKIE"
expect_status "Mahasiswa ditolak dari manajemen admin" "403" "/admin/users" "$MAHASISWA_COOKIE"
expect_status "Mahasiswa ditolak dari route dosen" "403" "/dosen/courses" "$MAHASISWA_COOKIE"

echo "[PASS] Semua pemeriksaan autentikasi dan otorisasi multi-role berhasil."
