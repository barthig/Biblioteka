#!/usr/bin/env bash
# Standalone Docker stack smoke test.
# Verifies the real Docker/dev startup path that uses init-db-expanded-v2.sql.
#
# Usage:
#   ./tests/integration/test_standalone_stack.sh
# Optional env:
#   BACKEND_URL=http://localhost:8000
#   FRONTEND_URL=http://localhost:5173

set -euo pipefail

BACKEND_URL="${BACKEND_URL:-http://localhost:8000}"
FRONTEND_URL="${FRONTEND_URL:-http://localhost:5173}"
TIMEOUT="${TIMEOUT:-5}"
MAX_WAIT_SECONDS="${MAX_WAIT_SECONDS:-180}"
PASS=0
FAIL=0
TMP_BODY="$(mktemp)"
trap 'rm -f "$TMP_BODY"' EXIT

wait_for_http() {
  local name="$1"
  local url="$2"
  local allowed_csv="$3"
  local deadline=$((SECONDS + MAX_WAIT_SECONDS))

  printf "  %-45s " "$name"

  while (( SECONDS < deadline )); do
    local code
    code=$(curl -sS -o "$TMP_BODY" -w "%{http_code}" --max-time "$TIMEOUT" "$url" 2>/dev/null || echo "000")
    if [[ ",${allowed_csv}," == *",${code},"* ]]; then
      echo "PASS (HTTP $code)"
      PASS=$((PASS + 1))
      return 0
    fi
    sleep 2
  done

  echo "FAIL (service did not become ready, expected one of: $allowed_csv)"
  FAIL=$((FAIL + 1))
  return 1
}

check_status() {
  local name="$1"
  local method="$2"
  local url="$3"
  local allowed_csv="$4"
  local body="${5:-}"
  local auth_header="${6:-}"

  printf "  %-45s " "$name"
  local code

  if [[ -n "$body" && -n "$auth_header" ]]; then
    code=$(curl -sS -o "$TMP_BODY" -w "%{http_code}" --max-time "$TIMEOUT" -X "$method" \
      -H "Content-Type: application/json" -H "$auth_header" -d "$body" "$url" 2>/dev/null || echo "000")
  elif [[ -n "$body" ]]; then
    code=$(curl -sS -o "$TMP_BODY" -w "%{http_code}" --max-time "$TIMEOUT" -X "$method" \
      -H "Content-Type: application/json" -d "$body" "$url" 2>/dev/null || echo "000")
  elif [[ -n "$auth_header" ]]; then
    code=$(curl -sS -o "$TMP_BODY" -w "%{http_code}" --max-time "$TIMEOUT" -X "$method" \
      -H "$auth_header" "$url" 2>/dev/null || echo "000")
  else
    code=$(curl -sS -o "$TMP_BODY" -w "%{http_code}" --max-time "$TIMEOUT" -X "$method" "$url" 2>/dev/null || echo "000")
  fi

  if [[ ",${allowed_csv}," != *",${code},"* ]]; then
    echo "FAIL (HTTP $code, expected one of: $allowed_csv)"
    FAIL=$((FAIL + 1))
    return 1
  fi

  echo "PASS (HTTP $code)"
  PASS=$((PASS + 1))
}

extract_json_field() {
  local field="$1"
  python3 -c "import json,sys; print(json.load(open(sys.argv[1])).get(sys.argv[2], ''))" "$TMP_BODY" "$field"
}

echo ""
echo "Standalone Docker Smoke Test"
echo "==========================="
echo "Frontend: $FRONTEND_URL"
echo "Backend:  $BACKEND_URL"
echo ""

echo "1. Wait for services"
echo "--------------------"
wait_for_http "Frontend root" "$FRONTEND_URL/" "200"
wait_for_http "Backend health" "$BACKEND_URL/health" "200"

echo ""
echo "2. Public API"
echo "-------------"
check_status "GET /api/announcements" "GET" "$BACKEND_URL/api/announcements?limit=5" "200"
check_status "GET /api/books/new" "GET" "$BACKEND_URL/api/books/new?limit=4" "200"
check_status "GET /api/library/hours" "GET" "$BACKEND_URL/api/library/hours" "200"
check_status "GET /api/library-hours" "GET" "$BACKEND_URL/api/library-hours" "200"
check_status "GET /metrics" "GET" "$BACKEND_URL/metrics" "200"
check_status "GET /health/distributed" "GET" "$BACKEND_URL/health/distributed" "200"

echo ""
echo "3. Auth flow"
echo "------------"
check_status "POST /api/auth/login" "POST" "$BACKEND_URL/api/auth/login" "200" '{"email":"user01@example.com","password":"password123"}'
TOKEN="$(extract_json_field token)"
REFRESH_TOKEN="$(extract_json_field refreshToken)"

if [[ -z "$TOKEN" || -z "$REFRESH_TOKEN" ]]; then
  echo "  Login response is missing token or refreshToken"
  exit 1
fi

AUTH_HEADER="Authorization: Bearer $TOKEN"
check_status "POST /api/auth/refresh" "POST" "$BACKEND_URL/api/auth/refresh" "200" "{\"refreshToken\":\"$REFRESH_TOKEN\"}"

echo ""
echo "4. Authenticated dashboard endpoints"
echo "------------------------------------"
check_status "GET /api/me" "GET" "$BACKEND_URL/api/me" "200" "" "$AUTH_HEADER"
check_status "GET /api/auth/profile" "GET" "$BACKEND_URL/api/auth/profile" "200" "" "$AUTH_HEADER"
check_status "GET /api/dashboard" "GET" "$BACKEND_URL/api/dashboard" "200" "" "$AUTH_HEADER"
check_status "GET /api/alerts" "GET" "$BACKEND_URL/api/alerts" "200" "" "$AUTH_HEADER"

echo ""
echo "==========================="
echo "Results: $PASS passed, $FAIL failed"
echo "==========================="

if [ "$FAIL" -gt 0 ]; then
  exit 1
fi

echo ""
echo "Standalone Docker stack looks healthy."
