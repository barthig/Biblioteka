/**
 * k6 Benchmark: Recommendation Service — measures similarity search latency.
 *
 * Run:  k6 run benchmarks/recommendation-benchmark.js
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const errorRate = new Rate('errors');
const recLatency = new Trend('recommendation_latency', true);

export const options = {
  scenarios: {
    load: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '15s', target: 10 },
        { duration: '30s', target: 30 },
        { duration: '30s', target: 50 },
        { duration: '15s', target: 0 },
      ],
    },
  },
  thresholds: {
    http_req_duration: ['p(95)<1000'],
    errors: ['rate<0.05'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8002';

export default function () {
  // Similar books
  const bookId = Math.floor(Math.random() * 20) + 1;
  const simRes = http.get(`${BASE_URL}/api/v1/recommendations/similar/${bookId}`);
  check(simRes, {
    'similar: 200 or 404': (r) => r.status === 200 || r.status === 404,
  });
  errorRate.add(simRes.status >= 500);
  recLatency.add(simRes.timings.duration);

  // User recommendations
  const userId = Math.floor(Math.random() * 10) + 1;
  const userRes = http.get(`${BASE_URL}/api/v1/recommendations/for-user/${userId}`);
  check(userRes, {
    'user recs: 200 or 404': (r) => r.status === 200 || r.status === 404,
  });
  errorRate.add(userRes.status >= 500);
  recLatency.add(userRes.timings.duration);

  sleep(0.5);
}
