# Instrukcja administratora i uruchomienia

Dokument uzupełnia instrukcję użytkownika o aspekty administracyjne i wdrożeniowe.

## Uruchomienie systemu

Rekomendowany sposób startu:

```bash
docker compose -f docker-compose.distributed.yml up -d --build
```

System uruchamia:

- frontend,
- backend Symfony,
- `notification-service`,
- `recommendation-service`,
- PostgreSQL oraz bazy pomocnicze usług,
- RabbitMQ, Redis, Traefik.

## Podstawowe adresy

- `http://localhost` - brama publiczna aplikacji,
- `http://localhost:3000` - frontend,
- `http://localhost:8000` - backend za nginx,
- `http://localhost:8080` - Traefik dashboard,
- `http://localhost:15672` - RabbitMQ,
- `http://localhost:3001` - Grafana,
- `http://localhost:16686` - Jaeger.

## Obszary administracyjne

Administrator odpowiada za:

- zarządzanie kontami i rolami,
- konfigurację ustawień systemowych,
- weryfikację raportów i statystyk,
- kontrolę poprawności działania usług rozproszonych.

## Weryfikacja działania

Do szybkiej weryfikacji po uruchomieniu użyj:

- `GET /health`
- `GET /health/distributed`
- skryptów z katalogu `tests/integration`
- klientów API z katalogu `docs/api-clients`

## Dokumentacja pomocnicza

- diagram bazy danych: `docs/database-diagram.puml`
- diagramy architektury C4: `docs/c4`
- diagramy UML procesów: `docs/uml`
- przewodnik testowania: `docs/TESTING.md`