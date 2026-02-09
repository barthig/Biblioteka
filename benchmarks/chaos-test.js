/**
 * k6 Benchmark: Chaos Testing — run while a service is down.
 *
 * Usage:
 *   1. Start all services:           docker compose -f docker-compose.distributed.yml up -d
 *   2. Run this test:                k6 run benchmarks/chaos-test.js &
 *   3. Kill notification-service:    docker compose -f docker-compose.distributed.yml stop notification-service
 *   4. Wait 30s, restart:            docker compose -f docker-compose.distributed.yml start notification-service
 *   5. Observe results — loans should still succeed (notification is async).
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const errorRate = new Rate('errors');
const latency = new Trend('api_latency', true);

export const options = {
  scenarios: {
    steady: {
      executor: 'constant-vus',
      vus: 20,
      duration: '3m',
    },
  },
  thresholds: {
    // Even during chaos, the main API should remain available
    http_req_duration: ['p(95)<2000'],
    errors: ['rate<0.1'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost';

export default function () {
  // Hit multiple endpoints to simulate real traffic
  const endpoints = [
    '/api/books?page=1&limit=10',
    '/api/books/1',
    '/health',
  ];

  for (const path of endpoints) {
    const res = http.get(`${BASE_URL}${path}`);
    check(res, {
      [`${path} — not 5xx`]: (r) => r.status < 500,
    });
    errorRate.add(res.status >= 500);
    latency.add(res.timings.duration);
  }

  // Check notification service health (may fail during chaos)
  const notifRes = http.get(`${BASE_URL}/api/notifications/internal/health`);
  check(notifRes, {
    'notification health reachable or expected down': (r) =>
      r.status === 200 || r.status === 502 || r.status === 503,
  });

  sleep(1);
}
