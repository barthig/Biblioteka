#!/bin/bash
#
# Start the full Biblioteka system in distributed architecture mode.
#
# Requirements: Docker, Docker Compose v2+
#
# Usage:
#   chmod +x scripts/start-distributed.sh
#   ./scripts/start-distributed.sh
#
set -euo pipefail

echo "=== Biblioteka - Distributed Architecture ==="
echo ""

# Build & start
docker compose -f docker-compose.distributed.yml up --build -d

echo "Services started."
echo "Frontend:            http://localhost:3000"
echo "API Gateway:         http://localhost"
echo "Traefik Dashboard:   http://localhost:8080"
echo "Backend API:         http://localhost/api"
echo "Prometheus:          http://localhost:9090"
echo "Grafana:             http://localhost:3001"
echo "Jaeger:              http://localhost:16686"
echo "RabbitMQ:            http://localhost:15672"
echo "Mailpit:             http://localhost:8025"
echo "Health:              http://localhost/health"
echo "Distributed health:  http://localhost/health/distributed"
echo ""
echo "Logs:  docker compose -f docker-compose.distributed.yml logs -f"
echo "Stop:  docker compose -f docker-compose.distributed.yml down"