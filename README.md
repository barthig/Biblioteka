# Biblioteka

> Nowoczesny system zarządzania biblioteką z architekturą REST API + SPA

## 📖 O projekcie

**Biblioteka** to kompleksowy system zarządzania biblioteką przeznaczony dla bibliotek publicznych i akademickich. Aplikacja wspiera pełny cykl wypożyczeń, zarządzanie katalogiem, rezerwacje, powiadomienia oraz rekomendacje książek oparte na AI.

### Dla kogo?
- **Czytelników** — przeglądanie katalogu, wypożyczenia, rezerwacje, historia i rekomendacje
- **Bibliotekarzy** — obsługa wypożyczeń/zwrotów, zarządzanie katalogiem, powiadomienia
- **Administratorów** — zarządzanie użytkownikami, raporty, konfiguracja systemu

### Główne funkcjonalności
- ✅ Zarządzanie katalogiem (książki, autorzy, kategorie, egzemplarze)
- ✅ Wypożyczenia i rezerwacje z przedłużeniami i terminami
- ✅ Konta użytkowników z kontrolą dostępu opartą na rolach (JWT)
- ✅ Kary i opłaty z obsługą płatności
- ✅ Powiadomienia e-mail/SMS oraz ogłoszenia
- ✅ Rekomendacje książek i wyszukiwanie semantyczne (pgvector)
- ✅ Zadania asynchroniczne przez Symfony Messenger + RabbitMQ
- ✅ API REST z pełną dokumentacją OpenAPI/Swagger

## Features

- Catalog management (books, authors, categories, copies)
- Loans and reservations with extensions and due dates
- User accounts with role-based access control
- Fines and payments (user and admin workflows)
- Notifications and announcements
- Recommendations and semantic search
- Async jobs via Symfony Messenger

## 🚀 Technologie i uzasadnienie wyboru

### Backend
- **Symfony 6.4** — dojrzały framework z wbudowanym DI, bezpieczeństwem, walidacją i architekturą CQRS
- **PHP 8.2** — nowoczesne typy, atrybuty i wysoka wydajność
- **Doctrine ORM** — spójna warstwa persystencji z migracjami i relacjami
- **PostgreSQL 16 + pgvector** — relacyjna baza danych z obsługą wyszukiwania semantycznego (embeddingi wektorowe)
- **RabbitMQ** — asynchroniczne przetwarzanie powiadomień, rekomendacji i zadań w tle

### Frontend
- **React 18** — komponenty funkcyjne z hookami dla responsywnego UI
- **React Router v6** — routing SPA z zagnieżdżonymi trasami
- **Vite** — szybki dev server i optymalizowane buildy produkcyjne
- **Axios/Fetch** — zunifikowana obsługa API (`frontend/src/api.js`)

### DevOps
- **Docker Compose** — izolowane środowisko deweloperskie z jednym poleceniem
- **Nginx** — reverse proxy i serwowanie statycznych zasobów

## 🏗️ Architektura kodu

### Backend (Symfony)
Warstwowa architektura z wyraźnym podziałem odpowiedzialności:

```
backend/src/
├── Controller/        # Obsługa HTTP, walidacja wejścia
├── Service/           # Logika biznesowa
├── Repository/        # Dostęp do danych (Doctrine)
├── Application/       # CQRS (Commands, Queries, Handlers)
├── Entity/            # Modele bazy danych (ORM)
├── Dto/               # Data Transfer Objects
├── Request/           # Obiekty żądań z walidacją
├── Event/             # Eventy domenowe
└── Middleware/        # Middleware HTTP
```

**Wzorce stosowane:**
- **CQRS** — rozdzielenie komend (zapis) i zapytań (odczyt) przez Symfony Messenger
- **Repository Pattern** — abstrakcja dostępu do danych
- **DTO Pattern** — transformacja danych między warstwami
- **Event-Driven** — eventy domenowe dla luźnego powiązania

### Frontend (React)
```
frontend/src/
├── components/        # Komponenty UI (wielokrotnego użytku)
├── pages/             # Strony aplikacji (routes)
├── services/          # Serwisy API
├── context/           # React Context (stan globalny)
├── hooks/             # Custom React hooks
├── utils/             # Funkcje pomocnicze
└── styles/            # CSS/design system
```

**Design System:**
- Tokeny projektowe w `styles/main.css` (kolory, spacing, typografia)
- Komponenty UI współdzielą wspólne style dla spójności
- Responsywność: mobile-first, breakpointy dla tablet/desktop

📚 **Dokumentacja architektury:**
- `docs/DATABASE_ARCHITECTURE.md` — szczegółowy opis schematu bazy danych
- `docs/ERD.md` — diagramy ERD dla wszystkich modułów

## 🎨 UX/UI i responsywność

### Design System
Aplikacja używa spójnego systemu projektowego:
- **Tokeny CSS** — zmienne dla kolorów, spacingu, typografii (`frontend/src/styles/main.css`)
- **Komponenty UI** — reużywalne komponenty w `frontend/src/components/`
- **Responsywność** — mobile-first, breakpointy: 768px (tablet), 1024px (desktop)
- **Dostępność** — semantyczny HTML, ARIA labels, obsługa klawiatury

### Tryby kolorystyczne
- **Jasny** — domyślny dla dnia
- **Ciemny** — dla pracy wieczornej
- **Automatyczny** — dostosowanie do ustawień systemowych

Użytkownik może zmienić preferencje w profilu: motyw, wielkość czcionki, język interfejsu.

## 🚀 Szybki start (Docker) — ZALECANE

### Wymagania
- **Docker Desktop** zainstalowany i uruchomiony
- **Docker Compose** v2.0+
- **Git**

### Kroki

1. **Sklonuj repozytorium:**
```powershell
git clone https://github.com/your-username/biblioteka.git
cd biblioteka
```

2. **Skopiuj pliki konfiguracyjne (opcjonalnie):**
```powershell
# Backend - można użyć domyślnych wartości z docker-compose.yml
Copy-Item backend\.env.example backend\.env

# Frontend
Copy-Item frontend\.env.example frontend\.env
```

3. **Uruchom wszystkie serwisy:**
```powershell
docker compose up -d
```

4. **Sprawdź status:**
```powershell
docker compose ps
```

### 🌐 Dostępne serwisy

Po uruchomieniu, aplikacja jest dostępna pod adresami:

| Serwis | URL | Opis |
|--------|-----|------|
| **Frontend** | http://localhost:5173 | Interfejs użytkownika (React SPA) |
| **Backend API** | http://localhost:8000 | REST API (Symfony) |
| **API Docs** | http://localhost:8000/api/docs | Swagger UI z dokumentacją |
| **RabbitMQ UI** | http://localhost:15672 | Panel zarządzania kolejkami (login: app/app) |
| **PostgreSQL** | localhost:5432 | Baza danych (user: app, pass: app, db: biblioteka) |

### Domyślne konta testowe

Po inicjalizacji bazy danych dostępne są testowe konta:

| Rola | Email | Hasło |
|------|-------|-------|
| Admin | admin@biblioteka.local | admin123 |
| Bibliotekarz | librarian@biblioteka.local | librarian123 |
| Czytelnik | user@biblioteka.local | user123 |

## ⚙️ Instalacja manualna (bez Dockera)

### Wymagania
- **PHP 8.2+** z rozszerzeniami: pdo_pgsql, mbstring, xml, curl
- **Composer** 2.x
- **Node.js** 18+ i npm
- **PostgreSQL 16+** z rozszerzeniem pgvector
- **RabbitMQ** (opcjonalnie, dla zadań async)

### Backend (Symfony)

1. **Zainstaluj zależności:**
```powershell
cd backend
composer install
```

2. **Skonfiguruj środowisko:**
```powershell
Copy-Item .env.example .env
```

Edytuj `backend/.env` i ustaw:
```env
DATABASE_URL="postgresql://app:app@localhost:5432/biblioteka"
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-secret-passphrase
```

3. **Wygeneruj klucze JWT:**
```powershell
php bin/console lexik:jwt:generate-keypair
```

4. **Utwórz bazę danych:**
```powershell
# Połącz się z PostgreSQL
psql -U postgres

# W konsoli PostgreSQL:
CREATE DATABASE biblioteka;
\c biblioteka
CREATE EXTENSION IF NOT EXISTS vector;
\q
```

5. **Zainicjalizuj schemat i dane testowe:**
```powershell
psql -U postgres -d biblioteka -f init-db-expanded-v2.sql
```

**LUB** użyj migracji Doctrine (jeśli schemat jeszcze nie istnieje):
```powershell
php bin/console doctrine:migrations:migrate --no-interaction
```

6. **Uruchom serwer deweloperski:**
```powershell
php -S 127.0.0.1:8000 -t public
```

**LUB** użyj Symfony CLI:
```powershell
symfony server:start
```

Backend dostępny pod: **http://127.0.0.1:8000**

### Frontend (React + Vite)

1. **Zainstaluj zależności:**
```powershell
cd frontend
npm install
```

2. **Skonfiguruj środowisko:**
```powershell
Copy-Item .env.example .env
```

Edytuj `frontend/.env`:
```env
VITE_API_URL=http://127.0.0.1:8000
```

3. **Uruchom serwer deweloperski:**
```powershell
npm run dev
```

Frontend dostępny pod: **http://localhost:5173**

### RabbitMQ (opcjonalne — dla zadań async)

1. **Zainstaluj RabbitMQ** (Windows/Linux/Mac)
2. **Uruchom worker:**
```powershell
cd backend
php bin/console messenger:consume async -vv
```

## 🔐 Uwierzytelnianie i autoryzacja

### JWT (JSON Web Tokens)
System wykorzystuje JWT do autoryzacji użytkowników:

**Logowanie:**
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@biblioteka.local",
  "password": "user123"
}
```

**Odpowiedź:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def502004a1b2c3d..."
}
```

**Odświeżanie tokena:**
```http
POST /api/auth/refresh
Content-Type: application/json

{
  "refresh_token": "def502004a1b2c3d..."
}
```

**Autoryzowane żądania:**
```http
GET /api/me
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### Role użytkowników

| Rola | Uprawnienia |
|------|-------------|
| **ROLE_USER** | Czytelnik — przeglądanie katalogu, wypożyczenia, rezerwacje, profil |
| **ROLE_LIBRARIAN** | Bibliotekarz — zarządzanie katalogiem, obsługa wypożyczeń/zwrotów, powiadomienia |
| **ROLE_ADMIN** | Administrator — pełny dostęp, zarządzanie użytkownikami, raporty, konfiguracja |

### API Secret (dla integracji systemowych)

System obsługuje również autoryzację przez API Secret dla integracji:

```http
GET /api/books
X-API-SECRET: your-secret-key
```

Konfiguracja w `backend/.env`:
```env
API_SECRET=your-secret-key-here
```

## 📡 API i dokumentacja

### OpenAPI/Swagger

Pełna dokumentacja API dostępna w formacie OpenAPI 3.0:

- **Swagger UI:** http://localhost:8000/api/docs
- **JSON spec:** http://localhost:8000/api/docs.json

### Przykładowe endpointy

#### Katalog
```http
GET /api/books                    # Lista książek (paginacja, filtry)
GET /api/books/{id}               # Szczegóły książki
GET /api/authors                  # Lista autorów
GET /api/categories               # Lista kategorii
```

#### Użytkownik
```http
GET /api/me                       # Profil zalogowanego użytkownika
GET /api/me/loans                 # Moje wypożyczenia
GET /api/me/reservations          # Moje rezerwacje
GET /api/me/fees                  # Moje opłaty
POST /api/me/fees/{id}/pay        # Opłać karę
```

#### Wypożyczenia (bibliotekarz)
```http
GET /api/loans                    # Wszystkie wypożyczenia
POST /api/loans                   # Utwórz wypożyczenie
PUT /api/loans/{id}/return        # Zwróć książkę
PUT /api/loans/{id}/extend        # Przedłuż wypożyczenie
```

#### Administracja
```http
GET /api/users                    # Lista użytkowników (admin)
POST /api/announcements           # Utwórz ogłoszenie (admin)
GET /api/audit-logs               # Dziennik audytu (admin)
```

### Obsługa błędów

API zwraca standardowe kody HTTP:

| Kod | Znaczenie |
|-----|-----------|
| 200 | OK — żądanie zakończone sukcesem |
| 201 | Created — zasób utworzony |
| 400 | Bad Request — błędne dane wejściowe |
| 401 | Unauthorized — brak autoryzacji |
| 403 | Forbidden — brak uprawnień |
| 404 | Not Found — zasób nie istnieje |
| 422 | Unprocessable Entity — błąd walidacji |
| 500 | Internal Server Error — błąd serwera |

**Przykład błędu walidacji:**
```json
{
  "error": "Validation failed",
  "details": {
    "email": ["Email jest wymagany"],
    "password": ["Hasło musi mieć min. 8 znaków"]
  }
}
```

### Frontend ↔ API

Frontend konsumuje API przez zunifikowany wrapper (`frontend/src/api.js`):

- ✅ Automatyczne dodawanie tokenów JWT
- ✅ Obsługa błędów i stanów loading
- ✅ Transformacja odpowiedzi
- ✅ Retry logic dla nieudanych żądań

Komponenty React wyświetlają stany:
- **Loading** — spinner lub skeleton loader
- **Error** — komunikat błędu z możliwością retry
- **Success** — wyświetlenie danych

## 🗄️ Baza danych i normalizacja (3NF)

### PostgreSQL 16 + pgvector

Schemat bazy danych jest znormalizowany do **3NF (Third Normal Form)** z 35 tabelami:

**Główne moduły:**
1. **Użytkownicy i autoryzacja** (4 tabele) — app_user, refresh_token, staff_role, registration_token
2. **Katalog** (7 tabel) — book, author, category, book_copy, book_category, book_digital_asset, age_range
3. **Wypożyczenia** (3 tabele) — loan, reservation, fine
4. **Oceny i rekomendacje** (4 tabele) — rating, review, recommendation_feedback, user_book_interaction
5. **Kolekcje** (3 tabele) — book_collection, collection_books, favorite
6. **Administracja** (4 tabele) — audit_logs, announcement, backup_record, system_setting
7. **Integracje** (2 tabele) — integration_config, notification_log
8. **Zakupy** (5 tabele) — supplier, acquisition_budget, acquisition_order, acquisition_expense, weeding_record

### Dane testowe

Plik `backend/init-db-expanded-v2.sql` zawiera:
- ✅ Pełny schemat DDL (694 linie)
- ✅ **30+ rekordów testowych na tabelę** (książki, użytkownicy, wypożyczenia, itp.)
- ✅ Realistyczne dane dla development i testów

### Optymalizacje wydajności

Dla wydajności odczytu, tabela `book` zawiera **cachowane liczniki**:
- `copies` — liczba dostępnych egzemplarzy
- `total_copies` — suma wszystkich egzemplarzy
- `storage_copies` — egzemplarze w magazynie
- `open_stack_copies` — egzemplarze w wolnym dostępie

**Źródło prawdy:** Tabela `book_copy` (filtrowana po statusie).  
**Dla ścisłej 3NF:** Liczniki można zastąpić widokami (materialized views) lub liczyć dynamicznie.

### Indeksy i wyszukiwanie

- **Full-text search:** GIN index na `book.search_vector` (tsvector)
- **Semantic search:** pgvector index na `book.embedding` (1536-dim vector)
- **Foreign keys:** Automatyczne indeksy dla wszystkich kluczy obcych

📚 **Dokumentacja:**
- [ERD](docs/ERD.md) — diagramy relacji między tabelami
- [Database Architecture](docs/DATABASE_ARCHITECTURE.md) — szczegółowy opis schematu
- [Schema Guide](docs/SCHEMA_GUIDE.md) — quick reference

## ⚡ Asynchroniczność i kolejki (RabbitMQ)

System wykorzystuje **Symfony Messenger** z transportem **RabbitMQ** do przetwarzania zadań w tle.

### Przykładowe zadania asynchroniczne

1. **Powiadomienia o zbliżającym się terminie zwrotu**
   ```php
   App\Message\LoanDueReminderMessage
   ```
   - Wysyłane 3 dni przed terminem zwrotu
   - Obsługiwane przez `App\MessageHandler\LoanDueReminderHandler`

2. **Powiadomienia o przeterminowanych wypożyczeniach**
   ```php
   App\Message\LoanOverdueMessage
   ```
   - Wysyłane po przekroczeniu terminu
   - Automatyczne naliczanie kar

3. **Powiadomienia o gotowej rezerwacji**
   ```php
   App\Message\ReservationReadyMessage
   ```
   - Wysyłane gdy zarezerwowana książka jest dostępna

4. **Aktualizacja embeddingów książek**
   ```php
   App\Message\UpdateBookEmbeddingMessage
   ```
   - Generowanie wektorów dla wyszukiwania semantycznego
   - Wykorzystuje OpenAI API

### Uruchomienie workera

**Docker (automatycznie):**
Worker jest uruchamiany jako osobny kontener `php-worker`.

**Ręcznie:**
```powershell
cd backend
php bin/console messenger:consume async -vv
```

**Monitoring:**
RabbitMQ Management UI: http://localhost:15672 (login: app/app)

### Konfiguracja

Plik: `backend/config/packages/messenger.yaml`

```yaml
framework:
  messenger:
    transports:
      async:
        dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        retry_strategy:
          max_retries: 3
          delay: 1000
          multiplier: 2
    
    routing:
      App\Message\LoanDueReminderMessage: async
      App\Message\ReservationReadyMessage: async
      App\Message\UpdateBookEmbeddingMessage: async
```

**Env variable:**
```env
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages
```

## 🧪 Testowanie

### Backend (PHPUnit)

```powershell
cd backend
vendor/bin/phpunit
```

**Pokrycie testów:**
```powershell
vendor/bin/phpunit --coverage-html coverage
```

**Typy testów:**
- `tests/Unit/` — testy jednostkowe (serwisy, helpery)
- `tests/Integration/` — testy integracyjne (repozytoria, baza danych)
- `tests/Functional/` — testy funkcjonalne (endpointy API)

### Frontend (Vitest)

```powershell
cd frontend
npm run test:run         # Uruchom wszystkie testy
npm run test             # Tryb watch
npm run test:ui          # UI do testów
npm run test:coverage    # Pokrycie kodu
```

**E2E (Playwright):**
```powershell
npm run test:e2e
```

### Analiza statyczna (PHPStan)

```powershell
cd backend
vendor/bin/phpstan analyse src --level=8
```

## 🐛 Rozwiązywanie problemów

### Problemy z portami

**Błąd:** "Port already in use" (5173, 8000, 5432)

```powershell
# Sprawdź co zajmuje port
netstat -ano | findstr :8000
netstat -ano | findstr :5173
netstat -ano | findstr :5432

# Zatrzymaj proces lub zmień porty w docker-compose.yml
```

### Błędy połączenia z bazą danych

**Błąd:** "Connection refused" lub "SQLSTATE[08006]"

```powershell
# Sprawdź czy PostgreSQL działa
docker compose ps db

# Sprawdź logi
docker compose logs db

# Zrestartuj bazę
docker compose restart db
```

### Konflikty migracji/schematu

**Błąd:** "SQLSTATE[42P07] table already exists"

```powershell
# Opcja 1: Użyj gotowej bazy (zalecane dla dev)
# init-db-expanded-v2.sql zawiera pełny schemat + dane
# Migracje są pomijane jeśli schemat już istnieje

# Opcja 2: Zacznij od nowa (USUWA WSZYSTKIE DANE!)
docker compose down -v
docker compose up -d
```

**Uwaga:** `docker-compose.yml` sprawdza czy tabela `app_user` istnieje:
- Jeśli **TAK** → pomija migracje (schema z init-db już załadowany)
- Jeśli **NIE** → uruchamia migracje Doctrine

### Błędy JWT

**Błąd:** "Unable to load key" lub "Invalid JWT"

```powershell
# Wygeneruj nowe klucze JWT
cd backend
php bin/console lexik:jwt:generate-keypair --overwrite

# Sprawdź uprawnienia do plików
# Windows: klucze powinny być readable
# Linux/Mac: chmod 644 config/jwt/public.pem, chmod 600 config/jwt/private.pem
```

### Frontend nie łączy się z backendem

**Problem:** API requests fail, CORS errors

1. Sprawdź `VITE_API_URL` w `frontend/.env`:
   ```env
   VITE_API_URL=http://localhost:8000
   ```

2. Sprawdź CORS w `backend/config/packages/nelmio_cors.yaml`:
   ```yaml
   nelmio_cors:
     defaults:
       origin_regex: true
       allow_origin: ['*']
       allow_methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
   ```

3. Upewnij się, że backend działa:
   ```powershell
   curl http://localhost:8000/api/docs
   ```

### Wolne buildy lub cache'owanie

```powershell
# Backend: wyczyść cache Symfony
cd backend
php bin/console cache:clear

# Frontend: wyczyść cache Vite
cd frontend
rm -rf node_modules/.vite
npm run dev

# Docker: rebuild bez cache
docker compose build --no-cache
docker compose up -d
```

### Brak danych testowych

**Problem:** Baza danych pusta po inicjalizacji

```powershell
# Zaimportuj dane ręcznie
docker compose exec db psql -U app -d biblioteka -f /docker-entrypoint-initdb.d/init-db-expanded-v2.sql

# LUB załaduj przez mounted volume
psql -U app -h localhost -d biblioteka -f backend/init-db-expanded-v2.sql
```

### RabbitMQ nie działa

```powershell
# Sprawdź status
docker compose ps rabbitmq

# Sprawdź logi
docker compose logs rabbitmq

# Restart
docker compose restart rabbitmq

# Weryfikuj połączenie
# UI: http://localhost:15672 (app/app)
```

### Uprawnienia plików (Linux/Mac)

```bash
# Backend cache i logi
chmod -R 777 backend/var/cache backend/var/log

# JWT keys
chmod 600 backend/config/jwt/private.pem
chmod 644 backend/config/jwt/public.pem
```

## 📁 Struktura projektu

## 📁 Struktura projektu

```
biblioteka/
├── backend/                     # Symfony 6.4 REST API
│   ├── bin/                    # Komendy konsolowe
│   ├── config/                 # Konfiguracja (services, routes, packages)
│   ├── migrations/             # Migracje bazy danych (Doctrine)
│   ├── public/                 # Web root (index.php)
│   ├── src/
│   │   ├── Controller/         # REST API endpoints
│   │   ├── Service/            # Logika biznesowa
│   │   ├── Repository/         # Dostęp do danych
│   │   ├── Entity/             # Modele ORM (Doctrine)
│   │   ├── Application/        # CQRS (Commands, Queries, Handlers)
│   │   ├── Dto/                # Data Transfer Objects
│   │   ├── Request/            # Request validators
│   │   ├── Event/              # Eventy domenowe
│   │   ├── Message/            # Async messages
│   │   └── MessageHandler/     # Handlery wiadomości
│   ├── tests/                  # PHPUnit tests
│   │   ├── Unit/               # Testy jednostkowe
│   │   ├── Integration/        # Testy integracyjne
│   │   └── Functional/         # Testy funkcjonalne (API)
│   ├── var/                    # Cache, logs, tmp
│   ├── composer.json           # PHP dependencies
│   ├── init-db-expanded-v2.sql # Inicjalizacja bazy + seed data
│   └── schema_current.sql      # DDL bez danych
│
├── frontend/                    # React 18 SPA
│   ├── public/                 # Statyczne assety
│   ├── src/
│   │   ├── components/         # Komponenty UI (reusable)
│   │   ├── pages/              # Strony (routes)
│   │   ├── services/           # API clients
│   │   ├── context/            # React Context (global state)
│   │   ├── hooks/              # Custom hooks
│   │   ├── utils/              # Helpery
│   │   ├── styles/             # CSS/design tokens
│   │   └── App.jsx             # Root component
│   ├── tests/                  # Vitest + Playwright tests
│   ├── package.json            # Node dependencies
│   └── vite.config.js          # Vite config
│
├── docs/                        # Dokumentacja
│   ├── DATABASE_ARCHITECTURE.md # Opis schematu bazy
│   ├── ERD.md                   # Diagramy ERD
│   ├── SCHEMA_GUIDE.md          # Quick reference
│   └── INDEX.md                 # Indeks dokumentacji
│
├── docker/                      # Konfiguracje Docker
│   ├── backend/                # PHP-FPM, Nginx
│   ├── frontend/               # Node, Nginx
│   ├── db/                     # PostgreSQL
│   └── php-worker/             # Symfony Messenger worker
│
├── docker-compose.yml           # Orkiestracja kontenerów
└── README.md                    # Ten plik
```

## 📚 Dokumentacja

- **[ERD.md](docs/ERD.md)** — diagramy relacji między tabelami (ASCII art + opis)
- **[DATABASE_ARCHITECTURE.md](docs/DATABASE_ARCHITECTURE.md)** — szczegółowy opis architektury bazy danych
- **[SCHEMA_GUIDE.md](docs/SCHEMA_GUIDE.md)** — szybki przewodnik po schemacie
- **[INDEX.md](docs/INDEX.md)** — indeks całej dokumentacji

## 📄 Licencja

MIT
