#!/bin/bash
#
# Uruchom pełny system Biblioteka w architekturze rozproszonej.
#
# Wymagania: Docker, Docker Compose v2+
#
# Użycie:
#   chmod +x scripts/start-distributed.sh
#   ./scripts/start-distributed.sh
#
set -euo pipefail

echo "=== Biblioteka — Distributed Architecture ==="
echo ""

# Build & start
docker compose -f docker-compose.distributed.yml up --build -d

echo ""
echo "╔══════════════════════════════════════════════════════════╗"
echo "║  Biblioteka — uruchomiona w architekturze rozproszonej  ║"
echo "╠══════════════════════════════════════════════════════════╣"
echo "║                                                          ║"
echo "║  Frontend:            http://localhost:5173               ║"
echo "║  API Gateway (Traefik): http://localhost                  ║"
echo "║  Traefik Dashboard:   http://localhost:8080               ║"
echo "║  Backend API:         http://localhost/api                ║"
echo "║  Notification API:    http://localhost:8001               ║"
echo "║  Recommendation API:  http://localhost:8002               ║"
echo "║                                                          ║"
echo "║  ── Observability ──                                     ║"
echo "║  Prometheus:          http://localhost:9090               ║"
echo "║  Grafana:             http://localhost:3001  (admin/admin)║"
echo "║  Jaeger (Tracing):    http://localhost:16686              ║"
echo "║                                                          ║"
echo "║  ── Infrastructure ──                                    ║"
echo "║  RabbitMQ Dashboard:  http://localhost:15672  (app/app)  ║"
echo "║  Mailpit (dev mail):  http://localhost:8025               ║"
echo "║                                                          ║"
echo "║  ── Health checks ──                                     ║"
echo "║  Backend:             http://localhost/health             ║"
echo "║  Distributed:         http://localhost/health/distributed ║"
echo "║  Notification:        http://localhost:8001/health        ║"
echo "║  Recommendation:      http://localhost:8002/health        ║"
echo "║                                                          ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""
echo "Logs:  docker compose -f docker-compose.distributed.yml logs -f"
echo "Stop:  docker compose -f docker-compose.distributed.yml down"
