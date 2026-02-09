# TODO — braki do uzupełnienia (architektura rozproszona)

Poniższa lista podsumowuje brakujące elementy wymagane do obrony tezy o architekturze rozproszonej dla systemu Biblioteka. Każdy punkt powinien być zaimplementowany i udokumentowany.

> **Legenda statusów:**  ✅ zrobione  |  🔧 do sprawdzenia/dopracowania  |  ⬜ do zrobienia

## A. Krytyczne (wymagane do zgodności z tematem)

- ✅ **Wydzielenie mikroserwisów**
  - ✅ Notification Service (`notification-service/`) — Python/FastAPI, własna baza, RabbitMQ consumer
  - ✅ Recommendation Service (`recommendation-service/`) — Python/FastAPI, pgvector, embeddingi AI
  - ✅ Catalog & Loan Service — backend Symfony z wydzielonymi bounded contexts
- ✅ **Database-per-service** — 3 oddzielne PostgreSQL: main (5432), notification (5433), recommendation (5434)
- ✅ **Komunikacja międzyserwisowa**
  - ✅ Integration events via RabbitMQ topic exchange (`biblioteka.events`)
  - ✅ IntegrationEventBridgeSubscriber — bridge 14 domain events → RabbitMQ
  - ✅ REST API per serwis (backend :80, notification :8001, recommendation :8002)
- ✅ **API Gateway** — Traefik v3 z routingiem, rate limiting, circuit breaker, retry, OTLP tracing
- ✅ **Diagram architektury rozproszonej (C4)** — Context, Container, Component (`docs/c4/`)
- ✅ **Poprawność event pipeline** — wszystkie eventy (book.*, loan.*, reservation.*, fine.*, user.*, rating.*, favorite.*) są dispatchowane przez handlery i bridgeowane do RabbitMQ
- ⬜ **Opis CAP i spójności danych** w pracy pisemnej (eventual consistency przez RabbitMQ)

## B. Wysoki priorytet (istotnie wzmacnia pracę)

- ⬜ **Saga / orchestration** dla procesów wieloetapowych (np. wypożyczenie → rezerwacja → powiadomienie)
- ✅ **Observability stack**
  - ✅ Prometheus (metryki serwisów + Traefik) — `:9090`
  - ✅ Grafana (dashboard z panelami) — `:3001`
  - ✅ Jaeger (distributed tracing via OTLP) — `:16686`
- ✅ **Benchmarki**
  - ✅ k6: catalog-search, loan-stress, chaos-test, recommendation-benchmark (`benchmarks/`)
- ⬜ **Testy odporności** (chaos testing — uruchom skrypt + kill serwis, opisz wyniki)

## C. Średni priorytet (bonus, ale przydatne)

- ⬜ **Replikacja PostgreSQL** (primary + read replica)
- 🔧 **Load balancing** (Traefik z wieloma instancjami — `docker compose up --scale notification-service=3`)
- ✅ **Service discovery** (Docker DNS w bridge network)
- ⬜ **Schema registry / kontrakty eventów** (np. AsyncAPI spec)

## D. Dokumentacja i artefakty

- ✅ Specyfikacja API backend (Nelmio ApiDoc / OpenAPI)
- ✅ Specyfikacja API notification-service (FastAPI auto-docs `:8001/docs`)
- ✅ Specyfikacja API recommendation-service (FastAPI auto-docs `:8002/docs`)
- ✅ Diagramy C4 PlantUML (`docs/c4/`)
- ⬜ Diagramy UML (use case, sekwencji dla kluczowych procesów)
- ⬜ Opis decyzji architektonicznych (ADR)
- ⬜ Raport z eksperymentów (metryki, wykresy, wnioski)
- ✅ Instrukcja uruchomienia (Docker Compose, skrypty `scripts/`)

## E. Testy i ewaluacja

- ✅ Testy jednostkowe (backend: PHPUnit)
- ✅ Testy integracyjne (backend)
- ✅ Testy wydajnościowe (k6 scripts)
- ⬜ Testy bezpieczeństwa (JWT, RBAC, rate limiting — udokumentowane)
- ⬜ Przeprowadzenie benchmarków i zebranie wyników (tabele, wykresy)

---

## Nowe pliki i katalogi (dodane)

```
config/traefik/traefik.yml          — API Gateway static config
config/traefik/dynamic.yml          — API Gateway routing, middleware, circuit breaker
config/prometheus/prometheus.yml    — Metryki scrape config
config/grafana/                     — Dashboardy, datasources

notification-service/               — Osobny mikroserwis (Python/FastAPI)
  Dockerfile
  requirements.txt
  app/main.py, config.py, database.py, models.py
  app/consumer.py                   — RabbitMQ consumer
  app/handlers.py                   — Event → notification
  app/routes/health.py, notifications.py

recommendation-service/             — Osobny mikroserwis (Python/FastAPI)
  Dockerfile
  requirements.txt
  app/main.py, config.py, database.py, models.py
  app/embedding.py                  — OpenAI embedding client
  app/consumer.py                   — RabbitMQ consumer (book events)
  app/routes/health.py, recommendations.py

backend/src/Service/Integration/
  IntegrationEventPublisher.php     — Publishes to RabbitMQ topic exchange
  AmqpConnectionFactory.php         — AMQP connection factory

backend/src/EventSubscriber/
  IntegrationEventBridgeSubscriber.php  — Domain events → Integration events

backend/src/Controller/
  DistributedHealthController.php   — Aggregated health check

docker-compose.distributed.yml     — Pełna architektura rozproszona
docs/c4/                            — C4 Context, Container, Component diagrams
benchmarks/                         — k6 performance/chaos test scripts
scripts/start-distributed.sh        — Linux start script
scripts/Start-Distributed.ps1       — Windows start script
```
