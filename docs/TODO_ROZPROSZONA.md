# TODO — braki do uzupełnienia (architektura rozproszona)

Poniższa lista podsumowuje brakujące elementy wymagane do obrony tezy o architekturze rozproszonej dla systemu Biblioteka. Każdy punkt powinien być zaimplementowany i udokumentowany.

> **Legenda statusów:**  ✅ zrobione  |  🔧 do sprawdzenia/dopracowania  |  ⬜ do zrobienia

---

## 0. Poprawki z audytu (FULL_AUDIT_REPORT.md) — ✅ UKOŃCZONE

Wszystkie poprawki z 46-punktowego audytu zostały wdrożone:

### Architektura / CQRS (P-01 … P-04)
- ✅ **P-01** – Dodano jawny `bus:` do 110 handler `#[AsMessageHandler]` (63 command + 47 query)
- ✅ **P-02** – Przeniesiono 6 handlerów z `QueryHandler/` do `Handler/Query/`, usunięto legacy namespace z services.yaml
- ✅ **P-03** – Dodano 6 brakujących wpisów routing do messenger.yaml (UpdateLoanCommand, PrepareReservationCommand, ExportBooksQuery, FindSimilarBooksQuery, GetLibraryStatisticsQuery, GetUserByIdQuery)
- ✅ **P-04** – Przeniesiono `PrepareReservationCommandHandler` z `Command/Reservation/` do `Handler/Command/`, dodano `bus: 'command.bus'`

### Bezpieczeństwo (S-03, S-04, S-09, S-10, S-13)
- ✅ **S-03** – CORS wildcard `['*']` → `['%env(CORS_ALLOW_ORIGIN)%']` w nelmio_cors.yaml
- ✅ **S-04** – Odblokowano rate limiting w RegistrationController
- ✅ **S-09** – JWT TTL zmieniony z 86400 (24h) na 900 (15 min) — JwtService + AuthController + OA annotations
- ✅ **S-10** – Usunięto 9 `error_log()` z JwtAuthenticator + 1 z JwtService
- ✅ **S-13** – Dodano walidację statusu użytkownika (isVerified, isPendingApproval, isBlocked) w endpoincie refresh

### Model danych / Encje (D-01 … D-10)
- ✅ **D-01** – Dodano 4 indeksy do Loan (user, book, due, returned) + `#[ORM\Table]` + `#[HasLifecycleCallbacks]`
- ✅ **D-02** – Dodano 4 indeksy do Reservation (user+status, book+status, expires, status) + `#[ORM\Table]`
- ✅ **D-03** – Usunięto pole `$review` z Rating (dane żyją w Review entity); poprawiono kaskadowo RatingController, RateBookHandler, CreateReviewHandler, ListBookReviewsHandler
- ✅ **D-04** – `DateTimeInterface` → `DateTimeImmutable` w Loan i Book (properties + getters/setters)
- ✅ **D-05** – Dodano indeksy do Fine, BookCopy, NotificationLog, WeedingRecord, AcquisitionOrder
- ✅ **D-07** – Dodano pole `updatedAt` + `#[ORM\PreUpdate]` do Book i Loan
- ✅ **D-08** – Dodano jawne `#[ORM\Table(name: ...)]` do 8 encji
- ✅ **D-09** – Naprawiono błąd znaku w `AcquisitionBudget::adjustSpentBy()`
- ✅ **D-10** – AuditLog `oldValues`/`newValues` zmienione z `text` → `json` (string→array); poprawiono kaskadowo AuditService, BookBorrowedSubscriber, BookReturnedSubscriber

### CI / Konfiguracja (T-03, T-04, O-01, O-02)
- ✅ **T-03** – Dodano brakujące test suites do phpunit.xml.dist (Integration, Security)
- ✅ **T-04** – Poprawiono poziom PHPStan w CI z 5 na 6 (zgodność z phpstan.neon)
- ✅ **O-01** – Skonsolidowano rate_limiter (usunięto duplikat z framework.yaml, dodano api_global do rate_limiter.yaml)
- ✅ **O-02** – Zmieniono APP_ENV z `prod` na `dev` w docker-compose.yml (środowisko dev)

### Frontend (F-01 … F-07)
- ✅ **F-01** – ErrorBoundary istnieje i owija App
- ✅ **F-02** – Legacy api.js przeniesione do .bak/.legacy (usunięte), aktywne API w `api/client.js`
- ✅ **F-03** – Auth skonsolidowane w `context/AuthContext.jsx`, Zustand store usunięty
- ✅ **F-04** – React.lazy + Suspense zaimplementowane w App.jsx
- ✅ **F-05** – AuthGuard chroni chronione trasy
- ✅ **F-06** – Route `path="*"` → NotFound istnieje
- ✅ **F-07** – console.log JWT leaks usunięte (pliki legacy usunięte)

---

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
