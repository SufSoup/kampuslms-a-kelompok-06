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

expect_guest_status() {
    local label="$1"
    local expected="$2"
    local path="$3"
    local actual

    actual="$(curl --silent --show-error --output "$TMP_DIR/guest-response.html" \
        --write-out '%{http_code}' "$APP_URL$path")"

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
login_as "dosen2" "budi.dosen@kampuslms.test" "password"
login_as "mahasiswa" "mahasiswa@kampuslms.test" "password"
login_as "mahasiswa2" "mahasiswa2@kampuslms.test" "password"

ADMIN_COOKIE="$TMP_DIR/admin.cookies"
DOSEN_COOKIE="$TMP_DIR/dosen.cookies"
DOSEN2_COOKIE="$TMP_DIR/dosen2.cookies"
MAHASISWA_COOKIE="$TMP_DIR/mahasiswa.cookies"
MAHASISWA2_COOKIE="$TMP_DIR/mahasiswa2.cookies"

# ============================================================
# 1. OTORISASI BERBASIS PERAN (RBAC)
# ============================================================
expect_status "Admin dapat mengakses manajemen mata kuliah" "200" "/admin/courses" "$ADMIN_COOKIE"
expect_status "Admin dapat mengakses manajemen pengguna" "200" "/admin/users" "$ADMIN_COOKIE"

expect_status "Dosen 1 dapat mengakses daftar mata kuliahnya" "200" "/dosen/courses" "$DOSEN_COOKIE"
curl --silent --show-error --output "$TMP_DIR/dosen-courses.html" \
    --cookie "$DOSEN_COOKIE" "$APP_URL/dosen/courses"
DOSEN_COURSE_PATH="$(course_path_from_page "dosen" "$TMP_DIR/dosen-courses.html")"
expect_status "Dosen 1 dapat melihat daftar materi miliknya" "200" "$DOSEN_COURSE_PATH/materials" "$DOSEN_COOKIE"
expect_status "Dosen 1 dapat membuka form tambah materi miliknya" "200" "$DOSEN_COURSE_PATH/materials/create" "$DOSEN_COOKIE"

expect_status "Dosen 2 dapat mengakses daftar mata kuliahnya" "200" "/dosen/courses" "$DOSEN2_COOKIE"
curl --silent --show-error --output "$TMP_DIR/dosen2-courses.html" \
    --cookie "$DOSEN2_COOKIE" "$APP_URL/dosen/courses"
DOSEN2_COURSE_PATH="$(course_path_from_page "dosen" "$TMP_DIR/dosen2-courses.html")"
expect_status "Dosen 2 dapat melihat daftar materi miliknya" "200" "$DOSEN2_COURSE_PATH/materials" "$DOSEN2_COOKIE"

expect_status "Mahasiswa dapat mengakses daftar mata kuliah" "200" "/mahasiswa/courses" "$MAHASISWA_COOKIE"
curl --silent --show-error --output "$TMP_DIR/mahasiswa-courses.html" \
    --cookie "$MAHASISWA_COOKIE" "$APP_URL/mahasiswa/courses"
MAHASISWA_COURSE_PATH="$(course_path_from_page "mahasiswa" "$TMP_DIR/mahasiswa-courses.html")"
expect_status "Mahasiswa dapat melihat daftar materi kelasnya" "200" "$MAHASISWA_COURSE_PATH/materials" "$MAHASISWA_COOKIE"

expect_status "Dosen ditolak dari manajemen admin" "403" "/admin/users" "$DOSEN_COOKIE"
expect_status "Mahasiswa ditolak dari manajemen admin" "403" "/admin/users" "$MAHASISWA_COOKIE"
expect_status "Mahasiswa ditolak dari route dosen" "403" "/dosen/courses" "$MAHASISWA_COOKIE"

# ============================================================
# 2. PENGUJIAN ANTI-IDOR ANTAR-OBJEK (OBJECT-LEVEL AUTHORIZATION)
# ============================================================
echo "[INFO] Menguji proteksi IDOR antar-objek..."

# Skenario IDOR 1: Dosen A mengakses atau memanipulasi materi/tugas milik Dosen B
expect_status "IDOR: Dosen 1 ditolak mengakses MK milik Dosen 2" "403" "$DOSEN2_COURSE_PATH" "$DOSEN_COOKIE"
expect_status "IDOR: Dosen 1 ditolak membuka form materi MK milik Dosen 2" "403" "$DOSEN2_COURSE_PATH/materials/create" "$DOSEN_COOKIE"
expect_status "IDOR: Dosen 2 ditolak mengakses MK milik Dosen 1" "403" "$DOSEN_COURSE_PATH" "$DOSEN2_COOKIE"
expect_status "IDOR: Dosen 2 ditolak membuka form materi MK milik Dosen 1" "403" "$DOSEN_COURSE_PATH/materials/create" "$DOSEN2_COOKIE"

# Skenario IDOR 2: Mahasiswa mencoba mengakses form materi dosen
expect_status "IDOR: Mahasiswa ditolak membuka form tambah materi" "403" "$DOSEN_COURSE_PATH/materials/create" "$MAHASISWA_COOKIE"

# Skenario IDOR 3: Mahasiswa mengakses MK yang tidak di-enroll
curl --silent --show-error --output "$TMP_DIR/admin-courses.html" \
    --cookie "$ADMIN_COOKIE" "$APP_URL/admin/courses"
ALL_COURSES="$(grep --only-matching --extended-regexp "/admin/courses/[0-9]+" "$TMP_DIR/admin-courses.html" | sed 's|/admin/courses/||' | sort -u || true)"
ENROLLED_COURSES="$(grep --only-matching --extended-regexp "/mahasiswa/courses/[0-9]+" "$TMP_DIR/mahasiswa-courses.html" | sed 's|/mahasiswa/courses/||' | sort -u || true)"

UNENROLLED_ID=""
for cid in $ALL_COURSES; do
    if ! echo "$ENROLLED_COURSES" | grep -qw "$cid"; then
        UNENROLLED_ID="$cid"
        break
    fi
done

if [[ -n "$UNENROLLED_ID" ]]; then
    expect_status "IDOR: Mahasiswa ditolak mengakses MK yang tidak diikuti (ID: $UNENROLLED_ID)" "403" "/mahasiswa/courses/$UNENROLLED_ID" "$MAHASISWA_COOKIE"
    expect_status "IDOR: Mahasiswa ditolak melihat materi MK yang tidak diikuti (ID: $UNENROLLED_ID)" "403" "/mahasiswa/courses/$UNENROLLED_ID/materials" "$MAHASISWA_COOKIE"
fi

curl --silent --show-error --output "$TMP_DIR/mahasiswa-assignments.html" \
    --cookie "$MAHASISWA_COOKIE" "$APP_URL/mahasiswa/assignments"
ASSIGNMENT_IDS="$(grep --only-matching --extended-regexp '/mahasiswa/assignments/[0-9]+' "$TMP_DIR/mahasiswa-assignments.html" | sed 's|/mahasiswa/assignments/||' | sort -u || true)"
SUBMISSION_DOWNLOAD_PATH=""

for assignment_id in $ASSIGNMENT_IDS; do
    curl --silent --show-error --output "$TMP_DIR/assignment-show.html" \
        --cookie "$MAHASISWA_COOKIE" "$APP_URL/mahasiswa/assignments/$assignment_id"
    SUBMISSION_DOWNLOAD_PATH="$(grep --only-matching --extended-regexp '/submissions/[0-9]+/download' "$TMP_DIR/assignment-show.html" | head -n 1 || true)"

    if [[ -n "$SUBMISSION_DOWNLOAD_PATH" ]]; then
        break
    fi
done

[[ -n "$SUBMISSION_DOWNLOAD_PATH" ]] || fail "Tidak ada submission seed yang dapat digunakan untuk uji IDOR berkas. Jalankan seeder terlebih dahulu."
expect_guest_status "Guest ditolak mengunduh submission privat" "302" "$SUBMISSION_DOWNLOAD_PATH"
expect_status "IDOR: Mahasiswa 2 ditolak mengunduh submission milik mahasiswa lain" "403" "$SUBMISSION_DOWNLOAD_PATH" "$MAHASISWA2_COOKIE"

echo "[PASS] Semua pemeriksaan autentikasi, otorisasi RBAC, dan proteksi IDOR berhasil."
