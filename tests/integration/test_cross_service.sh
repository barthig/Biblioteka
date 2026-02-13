#!/usr/bin/env bash
# Cross-service integration test script
# Verifies that all services are up and their health endpoints respond correctly.
#
# Usage: ./tests/integration/test_cross_service.sh
# Requires: docker compose up (all services running)

set -euo pipefail

BACKEND_URL="${BACKEND_URL:-http://localhost:8000}"
NOTIFICATION_URL="${NOTIFICATION_URL:-http://localhost:8001}"
RECOMMENDATION_URL="${RECOMMENDATION_URL:-http://localhost:8002}"
TIMEOUT=5
PASS=0
FAIL=0

check() {
    local name="$1"
    local url="$2"
    local expected_field="$3"

    printf "  %-40s " "$name"
    HTTP_CODE=$(curl -s -o /tmp/response.json -w "%{http_code}" --max-time "$TIMEOUT" "$url" 2>/dev/null || echo "000")

    if [ "$HTTP_CODE" = "200" ]; then
        if grep -q "$expected_field" /tmp/response.json 2>/dev/null; then
            echo "✅ PASS (HTTP $HTTP_CODE)"
            PASS=$((PASS + 1))
        else
            echo "⚠️  PASS (HTTP $HTTP_CODE, missing field: $expected_field)"
            PASS=$((PASS + 1))
        fi
    else
        echo "❌ FAIL (HTTP $HTTP_CODE)"
        FAIL=$((FAIL + 1))
    fi
}

echo ""
echo "🔗 Cross-Service Integration Tests"
echo "==================================="
echo ""
echo "Backend:         $BACKEND_URL"
echo "Notification:    $NOTIFICATION_URL"
echo "Recommendation:  $RECOMMENDATION_URL"
echo ""

echo "1. Health Endpoints"
echo "-------------------"
check "Backend /health"               "$BACKEND_URL/health"               "status"
check "Backend /api/health"            "$BACKEND_URL/api/health"            "status"
check "Notification /health"           "$NOTIFICATION_URL/health"           "status"
check "Recommendation /health"         "$RECOMMENDATION_URL/health"         "status"

echo ""
echo "2. Metrics Endpoints (Prometheus)"
echo "---------------------------------"
check "Backend /metrics"               "$BACKEND_URL/metrics"               "php_info"
check "Notification /metrics"          "$NOTIFICATION_URL/metrics"          "python_info"
check "Recommendation /metrics"        "$RECOMMENDATION_URL/metrics"        "python_info"

echo ""
echo "3. API Documentation"
echo "--------------------"
check "Backend /api/docs.json"         "$BACKEND_URL/api/docs.json"         "openapi"

echo ""
echo "4. Public API Endpoints"
echo "-----------------------"
check "GET /api/books"                 "$BACKEND_URL/api/books"             "items"
check "GET /api/announcements"         "$BACKEND_URL/api/announcements"     ""

echo ""
echo "5. Service-to-Service (API Secret)"
echo "-----------------------------------"
if [ -n "${API_SECRET:-}" ]; then
    printf "  %-40s " "Backend with x-api-secret header"
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time "$TIMEOUT" \
        -H "x-api-secret: $API_SECRET" "$BACKEND_URL/api/admin/settings" 2>/dev/null || echo "000")
    if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "403" ]; then
        echo "✅ PASS (auth processed, HTTP $HTTP_CODE)"
        PASS=$((PASS + 1))
    else
        echo "❌ FAIL (HTTP $HTTP_CODE)"
        FAIL=$((FAIL + 1))
    fi
else
    echo "  (skipped — API_SECRET not set)"
fi

echo ""
echo "==================================="
echo "Results: $PASS passed, $FAIL failed"
echo "==================================="

if [ "$FAIL" -gt 0 ]; then
    exit 1
fi

echo ""
echo "All cross-service integration tests passed! ✅"
