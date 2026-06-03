# Instrukcja administratora i uruchomienia projektu

Dokument opisuje uruchomienie systemu Smart Library, podstawową konfigurację środowiska oraz czynności administracyjne potrzebne do utrzymania aplikacji.

## 1. Wymagania

Minimalne wymagania lokalne:

- Docker Desktop z Docker Compose v2,
- Git,
- Node.js 20+ dla pracy z frontendem poza kontenerem,
- PHP 8.2+ i Composer dla pracy z backendem poza kontenerem,
- 8 GB RAM, zalecane 16 GB,
- wolne porty używane przez stos developerski.

Najważniejsze porty:

- `80` - publiczna brama Traefik,
- `3000` - frontend React/Vite,
- `8000` - backend Symfony przez nginx,
- `5432` - główna baza PostgreSQL,
- `5672` - RabbitMQ AMQP,
- `15672` - panel RabbitMQ,
- `6379` - Redis,
- `8025` - Mailpit,
- `8080` - dashboard Traefik,
- `9090` - Prometheus,
- `3001` - Grafana,
- `16686` - Jaeger.

## 2. Uruchomienie całego systemu w Dockerze

Rekomendowany sposób uruchomienia:

```bash
docker compose -f docker-compose.distributed.yml up -d --build
```

Po starcie kontenerów warto sprawdzić status:

```bash
docker compose -f docker-compose.distributed.yml ps
```

Logi wybranej usługi:

```bash
docker compose -f docker-compose.distributed.yml logs -f backend
docker compose -f docker-compose.distributed.yml logs -f frontend
docker compose -f docker-compose.distributed.yml logs -f notification-service
docker compose -f docker-compose.distributed.yml logs -f recommendation-service
```

Zatrzymanie środowiska:

```bash
docker compose -f docker-compose.distributed.yml down
```

Zatrzymanie z usunięciem wolumenów danych:

```bash
docker compose -f docker-compose.distributed.yml down -v
```

Używaj `down -v` ostrożnie, ponieważ usuwa dane bazodanowe z wolumenów.

## 3. Dostępne adresy

Po uruchomieniu systemu dostępne są:

- `http://localhost` - aplikacja przez bramę,
- `http://localhost:3000` - frontend,
- `http://localhost:8000` - backend,
- `http://localhost/api/docs` - Swagger UI,
- `http://localhost/api/docs.json` - specyfikacja OpenAPI,
- `http://localhost:15672` - RabbitMQ Management,
- `http://localhost:8025` - Mailpit,
- `http://localhost:3001` - Grafana,
- `http://localhost:9090` - Prometheus,
- `http://localhost:16686` - Jaeger,
- `http://localhost:8080` - Traefik dashboard.

## 4. Uruchomienie developerskie bez pełnego stosu Docker

### Backend

```bash
cd backend
composer install
php bin/console doctrine:migrations:migrate
symfony server:start
```

Jeżeli projekt działa za nginx lub kontenerem, backend może być dostępny przez `http://localhost:8000`.

### Frontend

```bash
cd frontend
npm ci
npm run dev
```

Domyślny adres frontendu:

```text
http://localhost:3000
```

### Mikroserwisy

Projekt zawiera usługi:

- `notification-service`,
- `recommendation-service`.

W typowym scenariuszu developerskim najłatwiej uruchomić je przez `docker-compose.distributed.yml`, ponieważ wymagają własnych zależności i baz danych.

## 5. Konfiguracja środowiska

Przed uruchomieniem należy sprawdzić pliki `.env` oraz konfigurację Docker Compose.

Najważniejsze grupy ustawień:

- połączenie z bazą danych,
- sekret JWT,
- CORS,
- adres frontendu,
- konfiguracja RabbitMQ,
- konfiguracja Redis,
- konfiguracja SMTP/Mailpit,
- ustawienia OpenAI dla rekomendacji, jeżeli są używane.

W środowisku produkcyjnym nie należy używać domyślnych sekretów ani haseł z konfiguracji developerskiej.

## 6. Baza danych i migracje

Migracje Symfony:

```bash
cd backend
php bin/console doctrine:migrations:migrate
```

Wczytanie danych przykładowych zależy od aktualnej konfiguracji fixture/skryptów projektu. Jeżeli używany jest gotowy skrypt SQL inicjalizacyjny, należy upewnić się, że jest zgodny z aktualnymi migracjami.

Przy problemach ze schematem należy sprawdzić:

```bash
cd backend
php bin/console doctrine:schema:validate
```

## 7. Konta i role

System obsługuje role:

- `ROLE_USER` - czytelnik,
- `ROLE_LIBRARIAN` - bibliotekarz,
- `ROLE_ADMIN` - administrator.

Dostęp do paneli jest zależny od ról. Jeżeli użytkownik widzi komunikat o braku uprawnień, należy sprawdzić role przypisane do konta.

## 8. Obszary administracyjne

Administrator odpowiada za:

- zarządzanie kontami użytkowników,
- nadawanie i odbieranie ról,
- blokowanie kont,
- konfigurację ustawień systemowych,
- weryfikację raportów,
- kontrolę poprawności działania usług rozproszonych,
- nadzorowanie kolejek RabbitMQ,
- obserwację logów i metryk.

## 9. Monitoring i diagnostyka

Podstawowe endpointy diagnostyczne:

- `GET /health`,
- `GET /health/distributed`.

Narzędzia pomocnicze:

- RabbitMQ Management - kontrola kolejek,
- Grafana - dashboardy,
- Prometheus - metryki,
- Jaeger - śledzenie zapytań,
- Mailpit - podgląd wiadomości e-mail w środowisku testowym.

## 10. Testy i kontrola jakości

Backend:

```bash
cd backend
vendor/bin/phpunit
vendor/bin/phpstan analyse src --level=6
```

Frontend:

```bash
cd frontend
npm run lint
npm run test
npm run build
npm run test:e2e
```

Testy integracyjne:

```bash
./tests/integration/test_cross_service.sh
./tests/integration/test_gateway_routing.sh
```

Na Windowsie skrypty `.sh` najlepiej uruchamiać z poziomu WSL lub Git Bash.

## 11. Dokumentacja pomocnicza

Przydatne pliki:

- `docs/USER_GUIDE.md` - instrukcja użytkownika,
- `docs/api/README.md` - dokumentacja API,
- `docs/TESTING.md` - przewodnik testowania,
- `docs/c4` - diagramy architektury C4,
- `docs/database-diagram.puml` - diagram bazy danych,
- `docs/smart-library-architecture.puml` - diagram architektury systemu,
- `docs/api-clients` - kolekcje Postman i Insomnia.

## 12. Najczęstsze problemy

### Frontend zwraca 502 dla endpointów API

Sprawdź, czy backend działa i czy kontener nginx/backend jest zdrowy:

```bash
docker compose -f docker-compose.distributed.yml ps
docker compose -f docker-compose.distributed.yml logs -f backend
```

### Błąd CORS

Błąd CORS często wynika z niedziałającego backendu lub odpowiedzi 502 bez nagłówków CORS. Najpierw sprawdź status backendu, a dopiero potem konfigurację `Access-Control-Allow-Origin`.

### Brak powiadomień

Sprawdź:

- tabelę/logi powiadomień,
- endpoint `/api/notifications`,
- działanie RabbitMQ, jeżeli powiadomienie jest wysyłane asynchronicznie,
- logi backendu i `notification-service`.

### Brak rekomendacji

Sprawdź:

- działanie `recommendation-service`,
- połączenie z bazą rekomendacji,
- konfigurację pgvector,
- dostępność klucza OpenAI, jeżeli rekomendacje wymagają embeddingów.

### Problem z logowaniem

Sprawdź:

- poprawność danych użytkownika,
- sekret JWT,
- czas systemowy,
- refresh tokeny,
- status konta użytkownika.

### Problem z RabbitMQ

Sprawdź panel `http://localhost:15672`, kolejki, exchange oraz logi backendu. Główne operacje biznesowe nie powinny zależeć krytycznie od dostępności kolejki, ale część powiadomień lub zadań tle może zostać opóźniona.
