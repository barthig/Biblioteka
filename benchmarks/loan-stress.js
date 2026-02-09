/**
 * k6 Benchmark: Loan Creation — tests the distributed loan flow
 * (backend → integration event → notification service).
 *
 * Requires a valid JWT token (set via K6_AUTH_TOKEN env var).
 *
 * Run:  K6_AUTH_TOKEN=xxx k6 run benchmarks/loan-stress.js
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Counter } from 'k6/metrics';

const errorRate = new Rate('errors');
const loansCreated = new Counter('loans_created');

export const options = {
  scenarios: {
    stress: {
      executor: 'constant-vus',
      vus: 30,
      duration: '2m',
    },
  },
  thresholds: {
    http_req_duration: ['p(95)<1000'],
    errors: ['rate<0.05'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost';
const AUTH_TOKEN = __ENV.K6_AUTH_TOKEN || '';

const headers = {
  'Content-Type': 'application/json',
  Authorization: `Bearer ${AUTH_TOKEN}`,
  'X-API-SECRET': 'dev_api_secret',
};

export default function () {
  // Create a loan (uses random book/user IDs — adjust for your seed data)
  const bookCopyId = Math.floor(Math.random() * 50) + 1;
  const payload = JSON.stringify({
    bookCopyId: bookCopyId,
  });

  const res = http.post(`${BASE_URL}/api/loans`, payload, { headers });

  const ok = check(res, {
    'loan created or expected error': (r) =>
      r.status === 201 || r.status === 400 || r.status === 409 || r.status === 404,
  });

  if (res.status === 201) {
    loansCreated.add(1);
  }
  errorRate.add(res.status >= 500);

  sleep(1);
}
