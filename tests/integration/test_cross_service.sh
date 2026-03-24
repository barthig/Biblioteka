#!/usr/bin/env bash
# Cross-service integration test script.
# Verifies service health and the public contract exposed through the gateway.
#
# Usage: ./tests/integration/test_cross_service.sh
# Requires: docker compose up (all services running)

set -euo pipefail

GATEWAY_URL="${GATEWAY_URL:-http://localhost}"
BACKEND_URL="${BACKEND_URL:-http://localhost:8000}"
NOTIFICATION_URL="${NOTIFICATION_URL:-http://localhost:8001}"
RECOMMENDATION_URL="${RECOMMENDATION_URL:-http://localhost:8002}"
TIMEOUT=5
PASS=0
FAIL=0
TMP_BODY="$(mktemp)"
trap 'rm -f "$TMP_BODY"' EXIT

check() {
    local name="$1"
    local url="$2"
    local expected_field="${3:-}"
    local allowed_codes="${4:-200}"

    printf "  %-40s " "$name"
    local http_code
    http_code=$(curl -s -o "$TMP_BODY" -w "%{http_code}" --max-time "$TIMEOUT" "$url" 2>/dev/null || echo "000")

    if [[ ",${allowed_codes}," != *",${http_code},"* ]]; then
        echo "FAIL (HTTP $http_code, expected: $allowed_codes)"
        FAIL=$((FAIL + 1))
        return
    fi

    if [ -n "$expected_field" ] && ! grep -q "$expected_field" "$TMP_BODY" 2>/dev/null; then
        echo "FAIL (HTTP $http_code, missing field: $expected_field)"
        FAIL=$((FAIL + 1))
        return
    fi

    echo "PASS (HTTP $http_code)"
    PASS=$((PASS + 1))
}

echo ""
echo "Cross-Service Integration Tests"
echo "==============================="
echo ""
echo "Gateway:         $GATEWAY_URL"
echo "Backend:         $BACKEND_URL"
echo "Notification:    $NOTIFICATION_URL"
echo "Recommendation:  $RECOMMENDATION_URL"
echo ""

echo "1. Gateway health and docs"
echo "--------------------------"
check "Gateway /health"                 "$GATEWAY_URL/health"                 "status"
check "Gateway /health/distributed"     "$GATEWAY_URL/health/distributed"     "checks" "200,503"
check "Gateway /api/docs.json"          "$GATEWAY_URL/api/docs.json"          "openapi"

echo ""
echo "2. Direct service health"
echo "------------------------"
check "Backend /health"                 "$BACKEND_URL/health"                 "status"
check "Notification /health"            "$NOTIFICATION_URL/health"            "status"
check "Recommendation /health"          "$RECOMMENDATION_URL/health"          "status"

echo ""
echo "3. Direct service metrics"
echo "-------------------------"
check "Backend /metrics"                "$BACKEND_URL/metrics"                "php_info"
check "Notification /metrics"           "$NOTIFICATION_URL/metrics"           "python_info"
check "Recommendation /metrics"         "$RECOMMENDATION_URL/metrics"         "python_info"

echo ""
echo "4. Public API through gateway"
echo "------------------------------"
check "Gateway GET /api/books"          "$GATEWAY_URL/api/books"              "items"
check "Gateway GET /api/announcements"  "$GATEWAY_URL/api/announcements"
check "Gateway GET /api/notifications"  "$GATEWAY_URL/api/notifications"      "" "200,401,403,503"
check "Gateway GET /api/recommendations/personal" "$GATEWAY_URL/api/recommendations/personal" "" "200,401,403"

echo ""
echo "5. Service-to-service auth"
echo "--------------------------"
if [ -n "${API_SECRET:-}" ]; then
    printf "  %-40s " "Backend with x-api-secret header"
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time "$TIMEOUT" \
        -H "x-api-secret: $API_SECRET" "$BACKEND_URL/api/admin/settings" 2>/dev/null || echo "000")
    if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "403" ]; then
        echo "PASS (auth processed, HTTP $HTTP_CODE)"
        PASS=$((PASS + 1))
    else
        echo "FAIL (HTTP $HTTP_CODE)"
        FAIL=$((FAIL + 1))
    fi
else
    echo "  (skipped - API_SECRET not set)"
fi

echo ""
echo "==============================="
echo "Results: $PASS passed, $FAIL failed"
echo "==============================="

if [ "$FAIL" -gt 0 ]; then
    exit 1
fi

echo ""
echo "All cross-service integration tests passed."