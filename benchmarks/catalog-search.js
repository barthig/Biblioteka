/**
 * k6 Benchmark: Catalog Search — measures RPS and latency
 * for the main book listing endpoint.
 *
 * Run:  k6 run benchmarks/catalog-search.js
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const errorRate = new Rate('errors');
const latency = new Trend('request_latency', true);

export const options = {
  scenarios: {
    // Ramp-up test
    ramp_up: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 20 },
        { duration: '1m', target: 50 },
        { duration: '30s', target: 100 },
        { duration: '1m', target: 100 },
        { duration: '30s', target: 0 },
      ],
    },
  },
  thresholds: {
    http_req_duration: ['p(95)<500', 'p(99)<1000'],
    errors: ['rate<0.01'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost';

export default function () {
  // Search for books
  const searchRes = http.get(`${BASE_URL}/api/books?search=python&page=1&limit=20`);
  check(searchRes, {
    'status is 200': (r) => r.status === 200,
    'response has items': (r) => {
      try {
        const body = JSON.parse(r.body);
        return body.items !== undefined || body.data !== undefined;
      } catch {
        return false;
      }
    },
  });
  errorRate.add(searchRes.status !== 200);
  latency.add(searchRes.timings.duration);

  // Get single book
  const bookRes = http.get(`${BASE_URL}/api/books/1`);
  check(bookRes, {
    'book status 200 or 404': (r) => r.status === 200 || r.status === 404,
  });
  errorRate.add(bookRes.status >= 500);
  latency.add(bookRes.timings.duration);

  sleep(0.5);
}
