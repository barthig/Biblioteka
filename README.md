# Biblioteka – System Zarządzania Biblioteką

Całościowy system biblioteczny zbudowany w nowoczesnej architekturze rozproszonej. Projekt łączy backend Symfony z frontendem React oraz dwoma wyspecjalizowanymi mikroserwisami (FastAPI), wszystko zaranżowane przez bramę Traefik z pełnym stosem monitoringu i observabilności.

## 📋 Spis treści

1. [O projekcie](#o-projekcie)
2. [Cechy i funkcjonalności](#cechy-i-funkcjonalności)
3. [Stos technologiczny](#stos-technologiczny)
4. [Architektura systemowa](#architektura-systemowa)
5. [Wymagania](#wymagania)
6. [Instalacja i szybki start](#instalacja-i-szybki-start)
7. [Konfiguracja środowiska](#konfiguracja-środowiska)
8. [Uruchamianie aplikacji](#uruchamianie-aplikacji)
9. [Dostępne adresy i porty](#dostępne-adresy-i-porty)
10. [Struktura repozytorium](#struktura-repozytorium)
11. [Routing i komunikacja między serwisami](#routing-i-komunikacja-między-serwisami)
12. [Backend (Symfony)](#backend-symfony)
13. [Frontend (React)](#frontend-react)
14. [Mikroserwisy (FastAPI)](#mikroserwisy-fastapi)
15. [Bazy danych](#bazy-danych)
16. [Komunikacja asynchroniczna (RabbitMQ)](#komunikacja-asynchroniczna-rabbitmq)
17. [Obserwiowalność i monitoring](#obserwiowalność-i-monitoring)
18. [Testy i jakość kodu](#testy-i-jakość-kodu)
19. [Development lokalny](#development-lokalny)
20. [CI/CD Pipeline](#cicd-pipeline)
21. [Najczęstsze problemy i rozwiązania](#najczęstsze-problemy-i-rozwiązania)
22. [Troubleshooting](#troubleshooting)
23. [Contributing](#contributing)
24. [Dokumentacja dodatkowa](#dokumentacja-dodatkowa)
25. [Najczęstsze pytania (FAQ)](#najczęstsze-pytania-faq)
26. [Licencja](#licencja)

## O projekcie

Biblioteka to uniwersalny system do zarządzania całą operacyjnością biblioteki. Obejmuje:

- **Katalog książek** – kompleksna baza egzemplarzy z metadanymi
- **Zarządzanie wypożyczeniami** – rezerwacje, wydłużenia, karty czytelnika
- **Awansowy system rekomendacji** – oparty na embeddings (pgvector) i Open AI
- **Powiadomienia** – przesyłane asynchronicznie przez dedykowany mikroserwis
- **Bezpieczeństwo** – uwierzytelnianie JWT, role RBAC
- **Observabilność** – pełny stos Prometheus + Grafana + Jaeger
- **Event-driven architektura** – asynchroniczne przetwarzanie zdarzeń przez RabbitMQ
- **API first design** – OpenAPI (Swagger 3.0), RESTful

## Cechy i funkcjonalności

| Obszar | Opis |
|--------|------|
| **Katalog** | Pełna wyszukiwalność książek, filtry, metadata |
| **Wypożyczenia** | Rezerwacje, limit wypożyczonych, powiadomienia o terminach |
| **Rekomendacje** | Semantyczne wyszukiwanie per user z pgvector/OpenAI |
| **Notyfikacje** | Email, logi systemowe, archiwum w dedicated DB |
| **Bezpieczeństwo** | JWT auth, password hashing (argon2), CORS, rate limiting |
| **Monitoring** | Real-time metryki, traces, logi ustrukturyzowane |
| **Performance** | Redis cache, session storage, optimized SQL queries |
| **Testing** | 50+ testów backend, 35+ testów frontend, E2E Playwright |
| **API Design** | Swagger UI, dokumentacja automatyczna, versioning ready |

## Stos technologiczny

### Backend API
- **Framework**: Symfony 6.4 LTS
- **Język**: PHP 8.2+
- **ORM**: Doctrine 2.20
- **Messaging**: Symfony Messenger + RabbitMQ (AMQP)
- **Autentykacja**: Firebase JWT + Symfony Security
- **Cache**: Predis (Redis 7)
- **API Doc**: NelmioApiDocBundle (OpenAPI)

### Frontend
- **Framework**: React 18.2
- **Build Tool**: Vite 5
- **Routing**: React Router v6
- **State**: Zustand 5.0
- **Styling**: TailwindCSS 4.2
- **Testing**: Vitest 4.0 + Playwright
- **Linting**: ESLint 8.5

### Mikroserwisy
- **Framework**: FastAPI + Uvicorn
- **ORM**: SQLAlchemy + asyncio
- **Vector DB**: pgvector extension (PostgreSQL 16)
- **AI Integration**: OpenAI API (embeddings)
- **Testing**: pytest

### Infrastruktura
- **Konteneryzacja**: Docker + Docker Compose v2
- **API Gateway**: Traefik v3 (dynamic routing)
- **Message Queue**: RabbitMQ 3.12 (Management UI)
- **Cache**: Redis 7
- **Bazy danych**: PostgreSQL 16 (3 instancje + pgvector)
- **Monitoring**: Prometheus + Grafana + Jaeger
- **Email**: Mailpit (SMTP testing)
- **CI/CD**: GitHub Actions (OIDC for Codecov)

## Architektura systemowa

### Diagram wysokiego poziomu

```
┌─────────────────────────────────────────────────────────┐
│                     Browser / Client                      │
└────────────────────────┬────────────────────────────────┘
                         │ HTTP/HTTPS
                         ↓
        ┌────────────────────────────────┐
        │  Traefik v3 (API Gateway)       │
        │  ├─ Port 80 (main entry)        │
        │  ├─ Port 8080 (dashboard)       │
        │  └─ Dynamic routing rules       │
        └─┬──────┬──────────┬─────────────┘
          │      │          │
    ┌─────┘      │          └────────┐
    │            │                   │
    ↓            ↓                   ↓
┌─────────────┐ ┌────────────────────────────────────┐
│  Frontend   │ │      Mikroserwisy                  │
│  React/Vite│ │  ├─ notification-service (logs)    │
│  :3000 →80 │ │  ├─ notification-service (stats)   │
└─────────────┘ │  ├─ recommendation-service (*/    │
                │  │  similar/{id})                 │
                │  └─ recommendation-service (*/    │
                │     for-user/{id})                │
                └────────────────────────────────────┘

    ┌────────────────────────────────────────┐
    │      Bach (API)  - :80/api/*           │
    │  ├─ Controller (Symfony Security)      │
    │  ├─ Service (Business Logic)           │
    │  ├─ Repository/Entity (Doctrine)       │
    │  ├─ MessageHandler (Events)            │
    │  └─ Command/Query Handlers             │
    └─┬───────────────────────────────────┬──┘
      │                                   │
      ↓                                   ↓
┌──────────────────┐    ┌────────────────────────┐
│   RabbitMQ       │    │  External Services     │
│ Exchange+Queues  │    │  ├─ OpenAI API        │
│ (async events)   │    │  └─ Mailpit (SMTP)    │
└──────────────────┘    └────────────────────────┘

    ┌──────────────────────────────────────────┐
    │      Databases (PostgreSQL 16)           │
    │  ├─ db (main: catalog + loans)          │
    │  ├─ notification-db (notification logs) │
    │  └─ recommendation-db (vectors)         │
    │     └─ pgvector extension               │
    └──────────────────────────────────────────┘

    ┌──────────────────────────────────────────┐
    │     Infrastructure                       │
    │  ├─ Redis (cache + sessions)            │
    │  ├─ Prometheus (metrics)                │
    │  ├─ Grafana (dashboards)                │
    │  ├─ Jaeger (distributed tracing)        │
    │  └─ Mailpit (SMTP UI)                   │
    └──────────────────────────────────────────┘
```

### Wzorce architektoniczne

1. **BFF (Backend for Frontend)** – Backend Symfony stanowi singlepoint'owy punkt wejścia dla frontendu, ukrywając kompleksowość mikroserwisów
2. **API Gateway** – Traefik handluje routingiem, load balancingiem i SSL termination
3. **CQRS-like Pattern** – Event sourcing + Doctrine QueryBuilder dla read models
4. **Asynchronous Processing** – RabbitMQ + Symfony Messenger dla background jobs
5. **Database per Service** – Każdy mikroserwis ma swoją bazę (data isolation)
6. **Health Checks** – /health endpoints dla Traefik readiness

## Wymagania

### Minimalne

- **Docker Desktop** 4.20+ z Docker Compose v2
- **Git** do klonowania repozytorium
- **RAM**: 8–16 GB (zalecane 16 GB dla wygodnej pracy)
- **Dysk**: 5–10 GB wolnego miejsca (dla obrazów Docker i volumes)
- **Porty**: 80, 3000, 3001, 5432, 5672, 8025, 8080, 9090, 15672, 16686 (muszą być dostępne)

### Zalecane

- Docker Desktop z WSL2 backend (Windows)
- 4+ vCPU
- SSD

## Instalacja i szybki start

### 1. Klonowanie repozytorium

```bash
git clone https://github.com/barthig/Biblioteka.git
cd Biblioteka
```

### 2. Przygotowanie konfiguracji

Skopiuj plik zmiennych środowiskowych:

```bash
cp .env.example .env
```

W pliku `.env` zmień wrażliwe wartości (patrz [Konfiguracja środowiska](#konfiguracja-środowiska)).

### 3. Uruchomienie całego stosu w Docker

```bash
docker compose -f docker-compose.distributed.yml up --build -d
```

Alternatywnie możesz użyć Helper skryptów:

**Windows PowerShell:**
```powershell
.\scripts\Start-Distributed.ps1
```

**Linux / macOS:**
```bash
bash scripts/start-distributed.sh
```

### 4. Sprawdzenie statusu kontenerów

```bash
docker compose -f docker-compose.distributed.yml ps
```

Poczekaj, aż wszystkie kontenery będą w stanie `healthy` lub `running`.

### 5. Testowanie dostępności

Otwórz w przeglądarce:

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost/api
- **OpenAPI Docs**: http://localhost/api/docs

Powinna się załadować aplikacja React i dokumentacja API.

### 6. Zatrzymanie aplikacji

```bash
docker compose -f docker-compose.distributed.yml down -v
```

Flaga `-v` usuwa również volumes (jeśli chcesz zachować dane, pomiń `-v`).

## Konfiguracja środowiska

Wszystkie zmienne konfiguracyjne znajdują się w pliku `.env` (sklonowany z `.env.example`).

### Kluczowe zmienne

```bash
# API Security
API_SECRET=your_secret_key_here                    # Secret key dla API
JWT_SECRET=your_jwt_secret_key_here               # JWT signing key
APP_ENV=dev                                        # Environment (dev, test, prod)

# OpenAI Integration (dla recommendation-service)
OPENAI_API_KEY=sk-your-openai-key-here           # API key z OpenAI

# RabbitMQ
RABBITMQ_USER=guest                              # Login RabbitMQ
RABBITMQ_PASSWORD=guest                          # Hasło RabbitMQ

# Main Database (katalog + wypożyczenia)
MAIN_DB_USER=biblioteka                          # User PostgreSQL
MAIN_DB_PASSWORD=change_me_main_db               # Password
MAIN_DB_HOST=db                                  # Host (w Docker)
MAIN_DB_PORT=5432                                # Port
MAIN_DB_NAME=biblioteka                          # Nazwa bazy

# Notification Service Database
NOTIFICATION_DB_USER=notifications               # User PostgreSQL
NOTIFICATION_DB_PASSWORD=change_me_notification_db
NOTIFICATION_DB_HOST=notification-db
NOTIFICATION_DB_NAME=notifications

# Recommendation Service Database
RECOMMENDATION_DB_USER=recommendations           # User PostgreSQL
RECOMMENDATION_DB_PASSWORD=change_me_recommendation_db
RECOMMENDATION_DB_HOST=recommendation-db
RECOMMENDATION_DB_NAME=recommendations

# Grafana Admin
GRAFANA_ADMIN_PASSWORD=change_me_grafana_admin  # Hasło admina Grafany
```

### Zmienne dla development

```bash
APP_ENV=dev                                       # Włącz debug toolbary
APP_DEBUG=1                                       # Symfony debugger
DATABASE_URL=postgresql://...                    # Full DB URL (optional)
```

### Zmienne dla production

```bash
APP_ENV=prod                                      # Production mode
APP_DEBUG=0                                       # Wyłącz debug
TRUSTED_HOSTS=example.com,www.example.com       # Allowed hosts
```

## Uruchamianie aplikacji

### Opcja 1: Docker Compose (Rekomendowana)

Najłatwiej i najprzenostalniej. Wszystkie serwisy uruchamiają się razem:

```bash
docker compose -f docker-compose.distributed.yml up -d
docker compose -f docker-compose.distributed.yml ps
```

### Opcja 2: Development lokalny (PHP + Node backend)

Tylko jeśli chcesz pracować nad kodem lokalnie bez kontenerów:

#### Backend

```bash
cd backend
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console server:run 127.0.0.1:8000
```

#### Frontend

```bash
cd frontend
npm ci
npm run dev
```

#### Mikroserwisy (Python)

```bash
cd notification-service
python -m venv venv
source venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8001
```

### Opcja 3: Hybrid (Docker + Local Frontend)

Uruchom Docker, ale frontend lokalnie na hot-reload:

```bash
# Terminal 1: Docker stack
docker compose -f docker-compose.distributed.yml up -d

# Terminal 2: Frontend dev server
cd frontend
npm ci
npm run dev
```

## Dostępne adresy i porty

Po uruchomieniu aplikacji dostępne są następujące adresy:

### Aplikacja

| Komponent | URL | Port | Opis |
|-----------|-----|------|------|
| **Frontend** | http://localhost:3000 | 3000 | React SPA |
| **Backend API** | http://localhost/api | 80 (Traefik) | REST API w Symfony |
| **OpenAPI Docs** | http://localhost/api/docs | 80 | Swagger UI (NelmioApiDocBundle) |
| **Health Check** | http://localhost/health | 80 | Backend health status |
| **Distributed Health** | http://localhost/health/distributed | 80 | Status wszystkich serwisów |

### Infrastructure UI

| Komponent | URL | Port | Login | Hasło |
|-----------|-----|------|-------|-------|
| **Traefik Dashboard** | http://localhost:8080 | 8080 | (public) | – |
| **RabbitMQ Management** | http://localhost:15672 | 15672 | guest | guest |
| **Mailpit UI** | http://localhost:8025 | 8025 | (public) | – |
| **Grafana Dashboard** | http://localhost:3001 | 3001 | admin | (z .env) |
| **Prometheus** | http://localhost:9090 | 9090 | (public) | – |
| **Jaeger Tracing** | http://localhost:16686 | 16686 | (public) | – |

## Struktura repozytorium

```
Biblioteka/
├── .github/
│   └── workflows/
│       └── ci.yml                    # GitHub Actions pipeline
├── backend/                           # PHP/Symfony API Backend
│   ├── src/
│   │   ├── Controller/               # Symfony Controllers (HTTP endpoints)
│   │   ├── Entity/                   # Doctrine Entities (Domain models)
│   │   ├── Repository/               # Doctrine Repositories (Data access)
│   │   ├── Service/                  # Business logic services
│   │   ├── Message/                  # Async command messages
│   │   ├── MessageHandler/           # Async event handlers (RabbitMQ)
│   │   ├── Event/                    # Domain events
│   │   ├── EventSubscriber/          # Event listeners
│   │   ├── Security/                 # Authentication & authorization
│   │   ├── Dto/                      # Data Transfer Objects
│   │   └── ApiDoc/                   # OpenAPI/Swagger definitions
│   ├── tests/
│   │   ├── Unit/                     # Unit tests (no dependencies)
│   │   ├── Application/              # Application logic tests
│   │   ├── Functional/               # HTTP request/response tests
│   │   ├── Service/                  # Service layer tests
│   │   ├── EventSubscriber/          # Event handler tests
│   │   └── Integration/              # Integration tests
│   ├── migrations/                   # Database migrations (Doctrine)
│   ├── config/
│   │   ├── services.yaml             # Dependency injection config
│   │   ├── services_test.yaml        # Test-specific services
│   │   ├── routes.yaml               # Route definitions
│   │   └── bundles.php               # Bundle configuration
│   ├── composer.json                 # PHP dependencies
│   ├── phpunit.xml.dist              # PHPUnit configuration
│   └── phpstan.neon                  # PHPStan static analysis config
├── frontend/                          # React/Vite Frontend
│   ├── src/
│   │   ├── api/                      # API client code (fetch/axios)
│   │   ├── components/               # React components
│   │   ├── context/                  # React Context
│   │   ├── hooks/                    # Custom React hooks
│   │   ├── pages/                    # Page components (React Router)
│   │   ├── services/                 # Service classes
│   │   ├── store/                    # Zustand store (state management)
│   │   ├── styles/                   # Global CSS + Tailwind config
│   │   ├── utils/                    # Utility functions
│   │   ├── guards/                   # Route guards
│   │   ├── constants/                # Constants
│   │   ├── layouts/                  # Layout components
│   │   ├── App.jsx                   # Root app component
│   │   └── main.jsx                  # Entry point
│   ├── tests/                        # Vitest tests
│   ├── playwright/                   # E2E tests (Playwright)
│   ├── public/                       # Static assets
│   ├── package.json                  # NPM dependencies
│   ├── vite.config.js                # Vite configuration
│   ├── vitest.config.js              # Vitest configuration
│   ├── tailwind.config.js            # TailwindCSS configuration
│   ├── eslint.config.js              # ESLint rules
│   └── playwright.config.js          # Playwright configuration
├── notification-service/              # FastAPI notification microservice
│   ├── app/
│   │   ├── main.py                   # FastAPI app definition
│   │   ├── routers/                  # API endpoints
│   │   ├── models.py                 # SQLAlchemy models
│   │   ├── schemas.py                # Pydantic schemas
│   │   ├── database.py               # DB connection
│   │   └── services/                 # Business logic
│   ├── tests/                        # pytest tests
│   ├── requirements.txt              # Python dependencies
│   ├── Dockerfile                    # Docker image definition
│   └── .env (git-ignored)           # Environment config
├── recommendation-service/            # FastAPI recommendation microservice
│   ├── app/
│   │   ├── main.py                   # FastAPI app + pgvector
│   │   ├── routers/                  # API endpoints (embeddings)
│   │   ├── models.py                 # SQLAlchemy + pgvector models
│   │   ├── services/                 # OpenAI + vector search
│   │   └── database.py               # PostgreSQL + pgvector
│   ├── tests/                        # pytest tests
│   ├── requirements.txt              # Dependencies (openai, pgvector)
│   ├── Dockerfile
│   └── .env
├── docker/
│   ├── backend/
│   │   ├── Dockerfile               # PHP-FPM image
│   │   └── php.ini                 # PHP configuration
│   ├── frontend/
│   │   ├── Dockerfile              # Node build + nginx
│   │   ├── nginx.conf              # Nginx reverse proxy
│   │   └── .dockerignore
│   ├── db/
│   │   └── Dockerfile              # PostgreSQL + pgvector
│   ├── nginx/
│   │   ├── Dockerfile              # Nginx reverse proxy
│   │   ├── default.conf            # Nginx config
│   │   └── app.conf                # App-specific routing
│   └── php-worker/
│       ├── Dockerfile              # PHP CLI for background jobs
│       └── php.ini
├── config/
│   ├── traefik/
│   │   ├── traefik.yml            # Traefik main config (API Gateway)
│   │   └── dynamic.yml            # Dynamic routing rules
│   ├── prometheus/
│   │   └── prometheus.yml         # Prometheus scrape config
│   └── grafana/
│       ├── datasources.yaml       # Grafana data source config
│       └── dashboards/            # Pre-built dashboards
├── tests/
│   └── integration/
│       ├── test_cross_service.sh      # Cross-service collaboration tests
│       ├── test_gateway_routing.sh    # Traefik routing tests
│       └── test_standalone_stack.sh   # Docker smoke tests
├── scripts/
│   ├── Start-Distributed.ps1      # Windows startup script
│   └── start-distributed.sh       # Linux/macOS startup script
├── benchmarks/
│   ├── catalog-search.js
│   ├── recommendation-benchmark.js
│   ├── loan-stress.js
│   └── chaos-test.js
├── docs/
│   ├── CONTRIBUTING.md            # Contributing guidelines (Polish)
│   ├── DISTRIBUTED_ROUTING.md     # API Gateway routing contract (Polish)
│   ├── TESTING.md                 # Testing guide (Polish)
│   ├── database-diagram.puml      # PlantUML database diagram
│   ├── erd.png                    # Entity Relationship Diagram (visual)
│   ├── api-clients/
│   │   ├── README.md
│   │   ├── Biblioteka.postman_collection.json
│   │   ├── Biblioteka.postman_environment.json
│   │   └── Biblioteka.insomnia.json
│   └── audit_export.json
├── docker-compose.distributed.yml # Main docker-compose (production-like)
├── .env.example                   # Environment template
├── .gitignore                     # Git ignore rules
├── LICENSE                        # Proprietary License
└── README.md                      # This file
```

## Routing i komunikacja między serwisami

Projekt używa Traefika jako centralnego API Gateway. Kontrakt routingu jest zdefiniowany w [docs/DISTRIBUTED_ROUTING.md](docs/DISTRIBUTED_ROUTING.md).

### Zasada ogólna

- **Frontend** rozmawia TYLKO z bramą Traefik (`http://localhost`)
- Każda publiczna ścieżka ma jednego właściciela (backend LUB mikroserwis)
- **Backend** pełni rolę **BFF (Backend for Frontend)** dla żądań wymagających kontekstu użytkownika

### Routing endpointów

```
┌─────────────────────────────────────────────┐
│  Traefik (http://localhost)                 │
├─────────────────────────────────────────────┤
│                                              │
│ /api/notifications (POST, GET)   → Backend   │
│ /api/notifications/test          → Backend   │
│ /api/notifications/logs          → Notif Svc │
│ /api/notifications/stats         → Notif Svc │
│                                              │
│ /api/recommendations/personal    → Backend   │
│ /api/recommendations/similar/*   → Recommend │
│ /api/recommendations/for-user/*  → Recommend │
│ /api/recommendations/search      → Recommend │
│                                              │
│ /api/**                          → Backend   │
│ /health                          → Backend   │
│ /health/distributed              → Backend   │
│                                              │
│ /                                → Frontend  │
│ /assets/*                        → Frontend  │
│                                              │
└─────────────────────────────────────────────┘
```

### Asynchroniczna komunikacja

Serwisy komunikują się asynchronicznie poprzez RabbitMQ:

```
Backend (Event Producer)
    ↓ publishes
RabbitMQ (Broker)
    ├→ notification-service (listener: sends emails/logs)
    └→ recommendation-service (listener: refreshes indices)
```

Zdarzenia:
- `NotificationCreated` – powiadomienie wymagające wysłania
- `LoanCreated` – nowe wypożyczenie (powiadomienia)
- `BookRecommended` – nowa rekomendacja

RabbitMQ konfiguracja:
- **Exchange**: `bibliote.events`
- **Queues**: `notification.queue`, `recommendation.queue`

## Backend (Symfony)

### Struktura aplikacji Symfony

```
backend/src/
├── Controller/           # HTTP Endpoints (REST API)
├── Entity/              # Doctrine Entities
├── Repository/          # Data access
├── Service/             # Business logic (domain services)
├── Dto/                 # Data transfer objects
├── Message/             # Message commands for async
├── MessageHandler/      # Async handlers (RabbitMQ consumers)
├── Event/               # Domain events
├── EventSubscriber/     # Event listeners
├── Security/            # Auth & Authorization
├── Exception/           # Custom exceptions
├── ApiDoc/              # OpenAPI annotations
└── Kernel.php           # Application kernel
```

### Główne encje domenowe

- `Book` – Książka w katalogu
- `Loan` – Wypożyczenie
- `Reservation` – Rezerwacja
- `User` – Czytelnik
- `Notification` – Powiadomienie
- `Recommendation` – Rekomendacja

### Główne endpointy API

#### Katalog książek
- `GET /api/books` – lista książek (z filtrami)
- `GET /api/books/{id}` – szczegóły książki
- `POST /api/books` – dodaj nową książkę (admin)
- `PUT /api/books/{id}` – edytuj książkę

#### Wypożyczenia
- `POST /api/loans` – nowe wypożyczenie
- `GET /api/loans/my` – moje wypożyczenia
- `PUT /api/loans/{id}/extend` – wydłuż wypożyczenie
- `DELETE /api/loans/{id}` – oddaj książkę

#### Rekomendacje
- `GET /api/recommendations/personal` – moje rekomendacje (z backend)
- `GET /api/recommendations/similar/{bookId}` – podobne (mikroserwis)
- `GET /api/recommendations/for-user/{userId}` – dla użytkownika

#### Bezpieczeństwo
- `POST /api/auth/register` – rejestracja
- `POST /api/auth/login` – login
- `GET /api/user/profile` – profil użytkownika

Pełna dokumentacja: http://localhost/api/docs

### Migracje bazy

Backend używa Doctrine Migrations. Migracje są w `backend/migrations/`.

Uruchomienie migracji:

```bash
docker compose -f docker-compose.distributed.yml exec backend php bin/console doctrine:migrations:migrate
```

### Testowanie backendu

```bash
cd backend

# Uruchomienie wszystkich testów
composer test

# Uruchomienie testów z sufiksem
composer test -- --filter=BookControllerTest

# PHPStan (static analysis, level 6)
composer analyse
```

## Frontend (React)

### Struktura aplikacji React

```
frontend/src/
├── api/                 # API client (fetch calls)
├── components/          # React components
│   ├── Common/         # Reusable (Button, Modal, etc.)
│   ├── Layout/         # Layout components
│   ├── Books/          # Book-specific components
│   ├── Loans/          # Loan-specific components
│   └── Auth/           # Authentication components
├── pages/              # Page components (React Router)
├── store/              # Zustand state management
├── hooks/              # Custom hooks
├── guards/             # Route guards (ProtectedRoute)
├── utils/              # Utility functions
├── context/            # React Context
├── constants/          # App constants
├── styles/             # Global CSS & Tailwind config
└── main.jsx            # Entry point
```

### Routing (React Router)

Główne ścieżki:

- `/` – Home
- `/login` – Login
- `/register` – Register
- `/books` – Katalog książek
- `/books/:id` – Szczegóły książki
- `/loans` – Moje wypożyczenia
- `/recommendations` – Moje rekomendacje
- `/admin` – Panel administracyjny
- `/admin/users` – Zarządzanie użytkownikami
- `*` – 404 Not Found

### State Management (Zustand)

Główne stores w `src/store/`:

```javascript
useAuthStore        // auth state, login/logout
useBooksStore       // books cache
useLoansStore       // loans state
useNotificationStore // toast notifications
```

### API Communication

API client w `src/api/`:

```javascript
import { apiClient } from './client';

const response = await apiClient.get('/api/books', {
  params: { page: 1, limit: 20 }
});
```

### Testowanie frontendu

```bash
cd frontend

npm run lint           # ESLint
npm run test:run       # Vitest
npm run test:coverage  # Coverage report
npm run test:e2e       # Playwright E2E tests
```

### Style (TailwindCSS)

Tailwind v4.2 skonfigurowany w `tailwind.config.js`.

## Mikroserwisy (FastAPI)

Projekt zawiera dwa wyspecjalizowane mikroserwisy w Pythonie.

### Notification Service

**Rola**: Obsługa powiadomień i logowanie.

Endpointy:
- `GET /api/notifications/logs` – log powiadomień
- `GET /api/notifications/stats` – statystyki
- `GET /health` – health check

Funkcjonalność:
- Subskrybuje `NotificationCreated` z RabbitMQ
- Wysyła email (Mailpit SMTP)
- Loguje do własnej bazy (`notification-db`)

### Recommendation Service

**Rola**: Wyszukiwanie semantyczne i rekomendacje per-user.

Endpointy:
- `GET /api/recommendations/similar/{bookId}` – podobne książki
- `GET /api/recommendations/for-user/{userId}` – dla użytkownika
- `POST /api/recommendations/search` – heurystyczne wyszukiwanie
- `GET /health` – status

Funkcjonalność:
- Embeddings z OpenAI API
- Vector similarity search z pgvector
- Asynchroniczne słuchanie RabbitMQ

### Testowanie mikroserwisów

```bash
cd notification-service
python -m pytest

cd ../recommendation-service
python -m pytest
```

## Bazy danych

Projekt używa **PostgreSQL 16** z **pgvector** extensionem. Trzy niezależne instancje:

### 1. Główna baza (`db`)

**Rola**: Katalog książek, wypożyczenia, użytkownicy.

### 2. Notification DB (`notification-db`)

**Rola**: Log wysłanych powiadomień (audit trail).

### 3. Recommendation DB (`recommendation-db`)

**Rola**: Vector embeddings i indeksy (pgvector).

### Dostęp do baz

```bash
docker compose -f docker-compose.distributed.yml exec db psql -U biblioteka -d biblioteka
docker compose -f docker-compose.distributed.yml exec notification-db psql -U notifications -d notifications
docker compose -f docker-compose.distributed.yml exec recommendation-db psql -U recommendations -d recommendations
```

## Komunikacja asynchroniczna (RabbitMQ)

RabbitMQ obsługuje asynchroniczną komunikację:

```
Exchange: bibliote.events (type: topic)
│
├→ Queue: notification.queue → notification-service
└→ Queue: recommendation.queue → recommendation-service
```

## Obserwiowalność i monitoring

### Prometheus

- **URL**: http://localhost:9090
- Zbiera metryki z Traefika, backendu, kontenerów

### Grafana

- **URL**: http://localhost:3001
- **Login**: admin (hasło z .env)
- Pre-built dashboards dla metryk systemowych

### Jaeger

- **URL**: http://localhost:16686
- Distributed tracing dla request flow tracking

### Logs

```bash
docker compose -f docker-compose.distributed.yml logs -f
docker compose -f docker-compose.distributed.yml logs -f backend
```

## Testy i jakość kodu

### Backend (PHP)

```bash
cd backend

composer test              # All tests
composer test -- --filter=BookTest  # Specific test
composer analyse          # PHPStan level 6
```

### Frontend (React)

```bash
cd frontend

npm run lint              # ESLint
npm run test:run          # Vitest
npm run test:coverage     # Coverage report
npm run test:e2e          # Playwright E2E tests
```

### Integration Tests

```bash
bash tests/integration/test_cross_service.sh
bash tests/integration/test_gateway_routing.sh
```

## Development lokalny

### Setup

1. Backend lokalnie (bez Docker):

```bash
cd backend
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console server:run 127.0.0.1:8000
```

2. Frontend lokalnie (hot-reload):

```bash
cd frontend
npm ci
npm run dev
```

3. Bazy danych w Docker:

```bash
docker compose -f docker-compose.distributed.yml up -d db rabbitmq redis
```

### Code Standards

- **Backend**: PSR-12, PHPStan level 6, Conventional Commits
- **Frontend**: ESLint React plugin + Prettier

## CI/CD Pipeline

GitHub Actions pipeline w `.github/workflows/ci.yml`:

1. Code Quality (PHPStan, ESLint)
2. Unit Tests (backend + frontend)
3. Functional/E2E Tests
4. Docker build & smoke tests
5. Security scan (Trivy)
6. Coverage upload (OIDC)

## Najczęstsze problemy i rozwiązania

### Port już zajęty

```bash
# Windows
netstat -ano | findstr :80

# macOS/Linux
lsof -i :80

# Usuń proces
taskkill /PID <PID> /F  # Windows
kill -9 <PID>           # macOS/Linux
```

### RabbitMQ connection refused

```bash
docker compose -f docker-compose.distributed.yml logs rabbitmq
docker compose -f docker-compose.distributed.yml restart rabbitmq
```

### Baza nie inicjalizuje

```bash
docker compose -f docker-compose.distributed.yml down -v
docker compose -f docker-compose.distributed.yml up -d
docker compose -f docker-compose.distributed.yml exec backend \
  php bin/console doctrine:migrations:migrate
```

### Frontend nie widzi API

Sprawdź:
- Czy Traefik zwraca: http://localhost:8080
- Czy Backend odpowiada: http://localhost/health
- Czy URL w frontend to `http://localhost` (nie `localhost:8000`)

## Troubleshooting

### Docker cleanup

```bash
docker compose -f docker-compose.distributed.yml down -v
docker system prune -a -f
docker compose -f docker-compose.distributed.yml up --build -d
```

### Database reset

```bash
docker compose -f docker-compose.distributed.yml exec db \
  dropdb -U biblioteka biblioteka
docker compose -f docker-compose.distributed.yml exec backend \
  php bin/console doctrine:database:create
docker compose -f docker-compose.distributed.yml exec backend \
  php bin/console doctrine:migrations:migrate
```

## Contributing

1. Fork repozytorium
2. Utwórz branch: `git checkout -b feature/my-feature`
3. Commituj: `git commit -m "feat: description"`
4. Push: `git push origin feature/my-feature`
5. Otwórz Pull Request

Używamy Conventional Commits:
- `feat:` – nowa funkcjonalność
- `fix:` – bugfix
- `docs:` – dokumentacja
- `test:` – testy
- `refactor:` – refactoring
- `perf:` – performance
- `chore:` – maintenance

## Dokumentacja dodatkowa

- [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md) – Contributing guidelines
- [docs/DISTRIBUTED_ROUTING.md](docs/DISTRIBUTED_ROUTING.md) – Routing contract
- [docs/TESTING.md](docs/TESTING.md) – Testing guide
- [docs/database-diagram.puml](docs/database-diagram.puml) – Database schema
- [docs/erd.png](docs/erd.png) – Visual ERD

## Najczęstsze pytania (FAQ)

### P: Mogę pracować lokalnie bez Docker?

**O**: Tak, ale musisz zainstalować: PHP 8.2+, Node 18+, PostgreSQL 16, RabbitMQ. Docker jest zalecany dla consistency.

### P: Jakie minimum RAM?

**O**: 8 GB, ale zalecane 16 GB dla wygodnej pracy (14 kontenerów).

### P: Jak zmienić port 80?

**O**: W `docker-compose.distributed.yml`, zmień `ports: - "8000:80"` (był `80:80`).

### P: Jak załadować fixtures?

**O**: `docker compose -f docker-compose.distributed.yml exec backend php bin/console doctrine:fixtures:load --no-interaction`

### P: Jaka jest polityka versioning API?

**O**: Dopóki v0.x, API może się zmieniać bez deprecation. Po v1.0, będziemy SemVer.

## Licencja

Projekt jest licencjonowany na licencji proprietary (patrz [LICENSE](LICENSE)).
