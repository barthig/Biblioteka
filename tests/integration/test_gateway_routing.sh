#!/usr/bin/env bash
# Gateway routing smoke test for distributed mode.
# Verifies that Traefik forwards conflict-prone endpoints to the correct owner.
#
# Usage:
#   ./tests/integration/test_gateway_routing.sh
# Optional env:
#   GATEWAY_URL=http://localhost

set -euo pipefail

GATEWAY_URL="${GATEWAY_URL:-http://localhost}"
TIMEOUT="${TIMEOUT:-5}"
PASS=0
FAIL=0
TMP_BODY="$(mktemp)"
trap 'rm -f "$TMP_BODY"' EXIT

check_status() {
  local name="$1"
  local path="$2"
  local allowed_csv="$3"
  local expected_snippet="${4:-}"

  printf "  %-45s " "$name"
  local code
  code=$(curl -sS -o "$TMP_BODY" -w "%{http_code}" --max-time "$TIMEOUT" "$GATEWAY_URL$path" 2>/dev/null || echo "000")

  if [[ ",${allowed_csv}," != *",${code},"* ]]; then
    echo "FAIL (HTTP $code, expected one of: $allowed_csv)"
    FAIL=$((FAIL + 1))
    return
  fi

  if [[ -n "$expected_snippet" ]] && ! grep -q "$expected_snippet" "$TMP_BODY" 2>/dev/null; then
    echo "FAIL (HTTP $code, missing body fragment: $expected_snippet)"
    FAIL=$((FAIL + 1))
    return
  fi

  echo "PASS (HTTP $code)"
  PASS=$((PASS + 1))
}

echo ""
echo "Gateway Routing Smoke Test"
echo "=========================="
echo "Gateway: $GATEWAY_URL"
echo ""

echo "1. Backend-owned notification endpoints"
echo "---------------------------------------"
check_status "GET /api/notifications" "/api/notifications" "200,401,403,503"
check_status "POST /api/notifications/test (wrong method)" "/api/notifications/test" "405"

echo ""
echo "2. Notification-service endpoints"
echo "---------------------------------"
check_status "GET /api/notifications/logs" "/api/notifications/logs" "200" "items"
check_status "GET /api/notifications/stats" "/api/notifications/stats" "200" "total"

echo ""
echo "3. Backend-owned recommendation endpoint"
echo "----------------------------------------"
check_status "GET /api/recommendations/personal" "/api/recommendations/personal" "200,401,403"

echo ""
echo "4. Recommendation-service endpoints"
echo "-----------------------------------"
check_status "GET /api/recommendations/search without q" "/api/recommendations/search" "422"
check_status "GET /api/recommendations/similar/999" "/api/recommendations/similar/999" "404"

echo ""
echo "=========================="
echo "Results: $PASS passed, $FAIL failed"
echo "=========================="

if [ "$FAIL" -gt 0 ]; then
  exit 1
fi

echo ""
echo "Gateway routing looks consistent."