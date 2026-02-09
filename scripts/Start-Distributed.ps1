<#
.SYNOPSIS
  Start Biblioteka in distributed architecture mode (Windows).

.DESCRIPTION
  Builds and starts all microservices, databases, and observability stack.

.EXAMPLE
  .\scripts\Start-Distributed.ps1
#>

Write-Host "=== Biblioteka — Distributed Architecture ===" -ForegroundColor Cyan
Write-Host ""

# Build & start
docker compose -f docker-compose.distributed.yml up --build -d

Write-Host ""
Write-Host "Services started. Access points:" -ForegroundColor Green
Write-Host ""
Write-Host "  Frontend (React):       http://localhost:5173"
Write-Host "  API Gateway (Traefik):  http://localhost"
Write-Host "  Traefik Dashboard:      http://localhost:8080"
Write-Host "  Backend API:            http://localhost/api"
Write-Host "  Notification Service:   http://localhost:8001"
Write-Host "  Recommendation Service: http://localhost:8002"
Write-Host ""
Write-Host "  --- Observability ---" -ForegroundColor Yellow
Write-Host "  Prometheus:             http://localhost:9090"
Write-Host "  Grafana:                http://localhost:3001  (admin/admin)"
Write-Host "  Jaeger (Tracing):       http://localhost:16686"
Write-Host ""
Write-Host "  --- Infrastructure ---" -ForegroundColor Yellow
Write-Host "  RabbitMQ:               http://localhost:15672  (app/app)"
Write-Host "  Mailpit:                http://localhost:8025"
Write-Host ""
Write-Host "  --- Health Checks ---" -ForegroundColor Yellow
Write-Host "  Backend:                http://localhost/health"
Write-Host "  Distributed:            http://localhost/health/distributed"
Write-Host "  Notification:           http://localhost:8001/health"
Write-Host "  Recommendation:         http://localhost:8002/health"
Write-Host ""
Write-Host "Logs:  docker compose -f docker-compose.distributed.yml logs -f" -ForegroundColor DarkGray
Write-Host "Stop:  docker compose -f docker-compose.distributed.yml down" -ForegroundColor DarkGray
