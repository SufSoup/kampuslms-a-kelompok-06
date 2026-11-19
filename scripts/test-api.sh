#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-https://kampuslms-a-kelompok-06-production.up.railway.app}"
STUDENT_EMAIL="${STUDENT_EMAIL:-mahasiswa@kampuslms.test}"
STUDENT_PASSWORD="${STUDENT_PASSWORD:-password}"
LECTURER_EMAIL="${LECTURER_EMAIL:-dosen@kampuslms.test}"
LECTURER_PASSWORD="${LECTURER_PASSWORD:-password}"

if ! command -v curl >/dev/null 2>&1 || ! command -v jq >/dev/null 2>&1; then
    echo "Error: skrip ini membutuhkan curl dan jq." >&2
    exit 1
fi



login() {
    local email="$1"
    local password="$2"
    local response

    response="$(curl --silent --show-error --fail \
        -X POST "$BASE_URL/api/v1/auth/login" \
        -H "Accept: application/json" \
        -H "Content-Type: application/json" \
        -d "$(jq -cn --arg email "$email" --arg password "$password" \
            '{email: $email, password: $password, device_name: "api-test-script"}')")"

    jq -er '.token' <<<"$response"
}

assert_status() {
    local description="$1"
    local expected="$2"
    local method="$3"
    local path="$4"
    local token="${5:-}"
    local body="${6:-}"
    local actual
    local args=(-sS -o /dev/null -w '%{http_code}' -X "$method"
        "$BASE_URL$path" -H "Accept: application/json")

    if [[ -n "$token" ]]; then
        args+=(-H "Authorization: Bearer $token")
    fi
    if [[ -n "$body" ]]; then
        args+=(-H "Content-Type: application/json" -d "$body")
    fi

    actual="$(curl "${args[@]}")"
    if [[ "$actual" != "$expected" ]]; then
        echo "FAIL: $description (expected HTTP $expected, got HTTP $actual)" >&2
        exit 1
    fi

    echo "PASS: $description (HTTP $actual)"
}

student_token="$(login "$STUDENT_EMAIL" "$STUDENT_PASSWORD")"
lecturer_token="$(login "$LECTURER_EMAIL" "$LECTURER_PASSWORD")"

assert_status "request tanpa token ditolak" 401 GET /api/v1/me
assert_status "mahasiswa tidak boleh membuat tugas dosen" 403 POST \
    /api/v1/assignments "$student_token" '{}'
if [[ -n "${FOREIGN_SUBMISSION_ID:-}" ]]; then
    assert_status "dosen tidak boleh menilai submission dosen lain" 403 PUT \
        "/api/v1/submissions/$FOREIGN_SUBMISSION_ID/grade" "$lecturer_token" \
        '{"score":80,"feedback":"Pengujian anti-IDOR"}'
else
    echo "[SKIP] Uji IDOR grading: tidak ada submission khusus pengujian."
fi
assert_status "token dosen valid dapat membuka profil sendiri" 200 GET \
    /api/v1/me "$lecturer_token"

echo "Semua skenario API selesai."
