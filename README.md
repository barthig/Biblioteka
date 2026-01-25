# Biblioteka - System Zarządzania Biblioteką

> Kompleksowy system zarządzania biblioteką z architekturą REST API + SPA, wspierający pełny cykl wypożyczeń, inteligentne rekomendacje oparte na AI oraz asynchroniczne przetwarzanie zadań.

## Spis treści

- [Opis projektu](#opis-projektu)
- [Architektura systemu](#architektura-systemu)
- [Stack technologiczny](#stack-technologiczny)
- [Wymagania systemowe](#wymagania-systemowe)
- [Instalacja i uruchomienie](#instalacja-i-uruchomienie)
- [Struktura projektu](#struktura-projektu)
- [Baza danych](#baza-danych)
- [API - dokumentacja](#api---dokumentacja)
- [Funkcjonalności](#funkcjonalności)
- [Uwierzytelnianie i autoryzacja](#uwierzytelnianie-i-autoryzacja)
- [Kolejki asynchroniczne](#kolejki-asynchroniczne)
- [Testowanie](#testowanie)
- [UX/UI i responsywność](#uxui-i-responsywność)

## Opis projektu

**Biblioteka** to nowoczesne narzędzie dla bibliotek publicznych i akademickich umożliwiające:

- **Zarządzanie katalogiem** — pełna obsługa książek, autorów, kategorii i egzemplarzy z wyszukiwaniem pełnotekstowym i semantycznym
- **Wypożyczenia i rezerwacje** — kompleksowy system wypożyczeń z automatycznym śledzeniem terminów, przedłużeniami i karami
- **Inteligentne rekomendacje** — system AI wykorzystujący embeddingi wektorowe (pgvector) do personalizowanych sugestii książek
- **Powiadomienia w czasie rzeczywistym** — automatyczne przypomnienia e-mail/SMS o zbliżających się terminach zwrotu
- **Panel administracyjny** — zarządzanie użytkownikami, generowanie raportów, konfiguracja systemu
- **Obsługa kolekcji** — tworzenie i kuracja tematycznych kolekcji książek
- **System płatności** — obsługa kar i opłat z pełną historią transakcji

### Dla kogo?

- **Czytelnicy** — intuicyjne przeglądanie katalogu, zarządzanie wypożyczeniami, rezerwacje, personalizowane rekomendacje
- **Bibliotekarze** — sprawna obsługa wypożyczeń/zwrotów, zarządzanie katalogiem, wysyłanie powiadomień
- **Administratorzy** — pełna kontrola nad systemem, zarządzanie użytkownikami, raporty, audyt działań

Projekt realizowany na potrzeby przedmiotu **ZTPAI** (Zaawansowane Technologie Programowania Aplikacji Internetowych).

## Architektura systemu

### Diagram warstw

```
+------------------+     +------------------+     +------------------+
|    FRONTEND      |     |     BACKEND      |     |   ZEWNĘTRZNE     |
|   (React 18)     |<--->|  (Symfony 6.4)   |<--->|      API         |
+------------------+     +------------------+     +------------------+
        |                        |                        |
        |                        v                        |
        |                +------------------+             |
        |                |   PostgreSQL 16  |             |
        |                |    + pgvector    |             |
        |                +------------------+             |
        |                        |                        |
        |                        v                        |
        |                +------------------+             |
        |                |    RabbitMQ      |             |
        |                | (Kolejki Async)  |             |
        +----------------+------------------+-------------+
```

### Diagram ERD

![ERD Diagram](docs/erd-diagram.png)

Schemat bazy danych zawiera **35 tabel** pogrupowanych w moduły:

1. **Użytkownicy i autoryzacja** (4 tabele) — `app_user`, `refresh_token`, `staff_role`, `registration_token`
2. **Katalog** (7 tabel) — `book`, `author`, `category`, `book_copy`, `book_category`, `book_digital_asset`, `age_range`
3. **Wypożyczenia** (3 tabele) — `loan`, `reservation`, `fine`
4. **Oceny i rekomendacje** (4 tabele) — `rating`, `review`, `recommendation_feedback`, `user_book_interaction`
5. **Kolekcje** (3 tabele) — `book_collection`, `collection_books`, `favorite`
6. **Administracja** (4 tabele) — `audit_logs`, `announcement`, `backup_record`, `system_setting`
7. **Integracje** (2 tabele) — `integration_config`, `notification_log`
8. **Zakupy i inwentaryzacja** (5 tabel) — `supplier`, `acquisition_budget`, `acquisition_order`, `acquisition_expense`, `weeding_record`


## Stack technologiczny

### Backend

| Technologia | Wersja | Uzasadnienie |
|-------------|--------|--------------|
| **Symfony** | 6.4 LTS | Dojrzały framework z wbudowanym DI, bezpieczeństwem, walidacją i architekturą CQRS. Long-term support zapewnia stabilność i bezpieczeństwo. |
| **PHP** | 8.2+ | Nowoczesne typy (union, intersection), atrybuty, enumeracje i wysoka wydajność dzięki JIT compiler. |
| **Doctrine ORM** | 3.x | Type-safe ORM ze wsparćiem dla migracji, relacji i query builder. Automatic mapping i lazy loading dla wydajności. |
| **PostgreSQL** | 16.x | Relacyjna baza danych z gwarancją ACID, zaawansowanymi indeksami (GIN, GiST) i obsługą JSON. |
| **pgvector** | 0.5.x | Rozszerzenie PostgreSQL do embeddingów wektorowych - umożliwia semantyczne wyszukiwanie książek na podstawie podobieństwa (cosine similarity). |
| **RabbitMQ** | 3.12.x | Message broker do asynchronicznego przetwarzania (powiadomienia, embeddingi). Gwarancja dostarczenia wiadomości, retry logic, dead letter queues. |
| **Symfony Messenger** | 6.4 | Komponent do obsługi kolejek z wbudowanym transportem AMQP, retry strategy i middleware. |

### Frontend

| Technologia | Wersja | Uzasadnienie |
|-------------|--------|--------------|
| **React** | 18.x | Komponenty funkcyjne z hooks, Suspense dla lepszego UX, concurrent rendering dla wydajności. |
| **React Router** | 6.x | Deklaratywny routing z zagnieżdżonymi trasami, code splitting i lazy loading. |
| **Vite** | 5.x | Ultra-szybki dev server dzięki ES modules, HMR bez przeładowania strony, optymalizowane buildy produkcyjne. |
| **Axios** | 1.x | HTTP client z interceptorami (auth tokens), timeout handling, automatic JSON transformation. |

### DevOps

| Technologia | Uzasadnienie |
|-------------|--------------|
| **Docker** | Konteneryzacja dla izolacji środowisk, spójność między dev/staging/production. |
| **Docker Compose** | Orkiestracja wielokontenerowa - backend, frontend, baza, kolejki w jednym pliku konfiguracyjnym. |
| **Nginx** | Reverse proxy, load balancing, serwowanie statycznych zasobów z cache headers. |

## Wymagania systemowe

### Minimalne wymagania

- **Docker Desktop** 20.10+ i **Docker Compose** v2.0+ (zalecana metoda instalacji)
- **Git** dla klonowania repozytorium
- 4 GB RAM (8 GB zalecane)
- 5 GB wolnego miejsca na dysku

### Wymagania dla instalacji manualnej (bez Dockera)

- **PHP** 8.2+ z rozszerzeniami: `pdo_pgsql`, `mbstring`, `xml`, `curl`, `intl`, `opcache`
- **Composer** 2.x
- **Node.js** 18+ i npm
- **PostgreSQL** 16+ z rozszerzeniem `pgvector`
- **RabbitMQ** 3.12+ (opcjonalnie, dla zadań asynchronicznych)

## Instalacja i uruchomienie

### Metoda 1: Docker (zalecana)

#### 1. Klonowanie repozytorium

```powershell
git clone https://github.com/barthig/Biblioteka.git
cd biblioteka
```

#### 2. Konfiguracja środowiska (opcjonalna)

Możesz użyć domyślnych wartości z `config/docker-compose.yml` lub skopiować przykładowe pliki:

```powershell
# Backend
Copy-Item backend\.env.example backend\.env

# Frontend
Copy-Item frontend\.env.example frontend\.env
```

#### 3. Uruchomienie wszystkich serwisów

```powershell
docker compose up -d
```

Docker automatycznie:
- Zbuduje wszystkie kontenery (backend, frontend, baza, kolejki)
- Utworzy bazę danych PostgreSQL z rozszerzeniem pgvector
- Załaduje schemat i dane testowe (~30 rekordów na tabelę)
- Uruchomi workera RabbitMQ dla zadań asynchronicznych

#### 4. Sprawdzenie statusu

```powershell
docker compose ps
```

Wszystkie kontenery powinny mieć status `Up`.

### Dostępne serwisy

Po uruchomieniu aplikacja jest dostępna pod adresami:

| Serwis | URL | Opis |
|--------|-----|------|
| **Frontend** | http://localhost:5173 | Interfejs użytkownika (React SPA) |
| **Backend API** | http://localhost:8000 | REST API (Symfony) |
| **API Docs** | http://localhost:8000/api/docs | Swagger UI - interaktywna dokumentacja OpenAPI |
| **RabbitMQ Management** | http://localhost:15672 | Panel zarządzania kolejkami (login: `app` / `app`) |
| **PostgreSQL** | localhost:5432 | Baza danych (user: `app`, password: `app`, db: `biblioteka`) |

### Konta testowe

Po inicjalizacji bazy danych dostępne są konta testowe:

| Rola | Email | Hasło | Uprawnienia |
|------|-------|-------|-------------|
| **Administrator** | user01@example.com| password123 | Pełny dostęp do systemu, zarządzanie użytkownikami, raporty |
| **Bibliotekarz** | user02@example.com | password123 | Zarządzanie katalogiem, obsługa wypożyczeń/zwrotów |
| **Czytelnik** | user03@example.com | password123 | Przeglądanie katalogu, wypożyczenia, rezerwacje |

### Metoda 2: Instalacja manualna (bez Dockera)

<details>
<summary><strong>Rozwiń instrukcję instalacji manualnej</strong></summary>

#### Backend (Symfony)

1. **Zainstaluj zależności:**

```powershell
cd backend
composer install
```

2. **Skonfiguruj środowisko:**

```powershell
Copy-Item .env.example .env
```

Edytuj `backend/.env`:

```env
DATABASE_URL="postgresql://app:app@localhost:5432/biblioteka?serverVersion=16&charset=utf8"
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages
JWT_SECRET=change_me_to_strong_secret_key
JWT_SECRETS=secret1,secret2,secret3
API_SECRET=your_api_secret_for_integrations
```

3. **Utwórz bazę danych:**

```powershell
# Połącz się z PostgreSQL
psql -U postgres

# W konsoli PostgreSQL:
CREATE DATABASE biblioteka;
\c biblioteka
CREATE EXTENSION IF NOT EXISTS vector;
\q
```

4. **Załaduj schemat i dane testowe:**

```powershell
psql -U postgres -d biblioteka -f backend/init-db-expanded-v2.sql
```

**LUB** użyj migracji Doctrine (jeśli schemat jeszcze nie istnieje):

```powershell
php bin/console doctrine:migrations:migrate --no-interaction
```

5. **Uruchom serwer deweloperski:**

```powershell
# Wbudowany serwer PHP
php -S 127.0.0.1:8000 -t public

# LUB Symfony CLI (zalecane)
symfony server:start
```

Backend dostępny pod: http://127.0.0.1:8000

#### Frontend (React + Vite)

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

Frontend dostępny pod: http://localhost:5173

#### RabbitMQ (opcjonalne)

1. **Zainstaluj RabbitMQ** (Windows/Linux/macOS)
2. **Uruchom workera:**

```powershell
cd backend
php bin/console messenger:consume async -vv
```

</details>

## Struktura projektu

## Struktura projektu

```
biblioteka/
├── backend/                     # Symfony 6.4 REST API
│   ├── bin/                    # Komendy konsolowe Symfony
│   │   └── console             # Entry point dla CLI
│   ├── config/                 # Konfiguracja aplikacji
│   │   ├── packages/           # Konfiguracja bundli (doctrine, messenger, security)
│   │   ├── routes.yaml         # Definicje routingu
│   │   ├── services.yaml       # Dependency Injection container
│   │   └── bundles.php         # Zarejestrowane bundle
│   ├── migrations/             # Migracje bazy danych Doctrine (20+ plików)
│   ├── public/                 # Web root
│   │   └── index.php           # Front controller
│   ├── src/
│   │   ├── Controller/         # REST API endpoints (17 kontrolerów)
│   │   │   ├── AuthController.php
│   │   │   ├── BookController.php
│   │   │   ├── LoanController.php
│   │   │   └── ...
│   │   ├── Service/            # Logika biznesowa (15 serwisów)
│   │   │   ├── BookService.php
│   │   │   ├── LoanService.php
│   │   │   ├── NotificationService.php
│   │   │   └── ...
│   │   ├── Repository/         # Dostęp do danych (25 repozytoriów)
│   │   │   ├── BookRepository.php
│   │   │   ├── UserRepository.php
│   │   │   └── ...
│   │   ├── Entity/             # Modele ORM (35 encji)
│   │   │   ├── Book.php
│   │   │   ├── User.php
│   │   │   ├── Loan.php
│   │   │   └── ...
│   │   ├── Application/        # CQRS (Commands, Queries, Handlers)
│   │   │   ├── Command/
│   │   │   ├── Query/
│   │   │   └── Handler/
│   │   ├── Dto/                # Data Transfer Objects
│   │   ├── Request/            # Request validators (Symfony ParamConverter)
│   │   ├── Event/              # Eventy domenowe
│   │   ├── Message/            # Async messages (RabbitMQ)
│   │   │   ├── LoanDueReminderMessage.php
│   │   │   ├── UpdateBookEmbeddingMessage.php
│   │   │   └── ...
│   │   └── MessageHandler/     # Handlery wiadomości asynchronicznych
│   ├── tests/                  # Testy (PHPUnit)
│   │   ├── Unit/               # Testy jednostkowe (10+ testów)
│   │   ├── Integration/        # Testy integracyjne (8+ testów)
│   │   ├── Functional/         # Testy funkcjonalne API (15+ testów)
│   │   └── Performance/        # Testy wydajnościowe
│   ├── var/                    # Pliki generowane
│   │   ├── cache/              # Cache Symfony
│   │   ├── log/                # Logi aplikacji
│   │   └── tmp/                # Pliki tymczasowe
│   ├── composer.json           # PHP dependencies
│   ├── phpunit.xml.dist        # Konfiguracja PHPUnit
│   ├── phpstan.neon            # Konfiguracja PHPStan (analiza statyczna)
│   ├── init-db-expanded-v2.sql # Pełny schemat DDL + dane testowe (694 linie)
│   └── schema_current.sql      # DDL bez danych
│
├── frontend/                    # React 18 SPA
│   ├── public/                 # Statyczne assety
│   │   ├── index.html
│   │   └── assets/             # Obrazy, ikony
│   ├── src/
│   │   ├── components/         # Komponenty UI (50+ komponentów wielokrotnego użytku)
│   │   │   ├── BookCard.jsx
│   │   │   ├── LoanTable.jsx
│   │   │   ├── Navbar.jsx
│   │   │   └── ...
│   │   ├── pages/              # Strony aplikacji (20+ routes)
│   │   │   ├── HomePage.jsx
│   │   │   ├── BookDetailsPage.jsx
│   │   │   ├── LoansPage.jsx
│   │   │   ├── AdminDashboard.jsx
│   │   │   └── ...
│   │   ├── services/           # API clients
│   │   │   ├── api.js          # Axios wrapper z interceptorami
│   │   │   ├── authService.js
│   │   │   ├── bookService.js
│   │   │   └── ...
│   │   ├── context/            # React Context (global state)
│   │   │   ├── AuthContext.jsx
│   │   │   ├── ThemeContext.jsx
│   │   │   └── ...
│   │   ├── hooks/              # Custom React hooks
│   │   │   ├── useAuth.js
│   │   │   ├── useDebounce.js
│   │   │   └── ...
│   │   ├── utils/              # Funkcje pomocnicze
│   │   │   ├── formatDate.js
│   │   │   ├── validators.js
│   │   │   └── ...
│   │   ├── styles/             # CSS/design tokens
│   │   │   ├── main.css        # Globalne style + design system
│   │   │   └── components/     # Style komponentów
│   │   └── App.jsx             # Root component
│   ├── tests/                  # Testy
│   │   ├── unit/               # Vitest unit tests (15+ testów)
│   │   ├── integration/        # Vitest integration tests (10+ testów)
│   │   └── e2e/                # Playwright E2E tests (8+ testów)
│   ├── package.json            # Node dependencies
│   ├── vite.config.js          # Konfiguracja Vite
│   ├── vitest.config.js        # Konfiguracja Vitest
│   └── playwright.config.js    # Konfiguracja Playwright
│
├── docs/                        # Dokumentacja
│   ├── SCHEMA_GUIDE.md          # Quick reference
│   ├── database-diagram.puml    # PlantUML diagram
│   └── INDEX.md                 # Indeks dokumentacji
│
├── docker/                      # Konfiguracje Docker
│   ├── backend/                # PHP-FPM, Nginx
│   │   ├── Dockerfile
│   │   └── nginx.conf
│   ├── frontend/               # Node, Nginx
│   │   ├── Dockerfile
│   │   └── nginx.conf
│   ├── db/                     # PostgreSQL + pgvector
│   │   └── Dockerfile
│   └── php-worker/             # Symfony Messenger worker
│       └── Dockerfile
│
├── config/                      # Konfiguracja projektu
│   ├── docker-compose.yml       # Orkiestracja kontenerów (5 serwisów)
│   ├── docker-compose.windows.yml # Konfiguracja dla Windows
│   ├── .env.example             # Przykładowe zmienne środowiskowe
│   └── .dockerignore            # Ignorowane pliki Docker
│
├── docs/                        # Dokumentacja
│   ├── SCHEMA_GUIDE.md          # Quick reference
│   ├── database-diagram.puml    # PlantUML diagram
│   ├── INDEX.md                 # Indeks dokumentacji
│   ├── CHANGELOG.md             # Historia zmian projektu
│   ├── CONTRIBUTING.md          # Wytyczne dla kontrybutorów
│   ├── SECURITY.md              # Polityka bezpieczeństwa
│   ├── AUDYT_SENIOR_DEV.md      # Audyt dla senior dewelopera
│   └── migration-info.php       # Informacje o migracjach
│
├── README.md                    # Ten plik
├── LICENSE                      # Licencja MIT
└── .gitignore                   # Ignorowane pliki
```

### Architektura kodu - Backend (Symfony)

Warstwowa architektura z wyraźnym podziałem odpowiedzialności:

**Wzorce projektowe stosowane:**
- **CQRS (Command Query Responsibility Segregation)** — rozdzielenie komend (zapis) i zapytań (odczyt) przez Symfony Messenger
- **Repository Pattern** — abstrakcja dostępu do danych, możliwość łatwej zmiany źródła danych
- **DTO Pattern** — transformacja danych między warstwami, oddzielenie modeli domenowych od API
- **Event-Driven Architecture** — eventy domenowe dla luźnego powiązania komponentów
- **Dependency Injection** — zarządzanie zależnościami przez Symfony Container
- **Middleware Pattern** — HTTP middleware dla cross-cutting concerns (auth, logging, CORS)

**Przepływ żądania:**
```
Request → Controller → Service → Repository → Database
                ↓         ↓
              DTO    Event Dispatcher → Async Message → RabbitMQ → Worker
```

### Architektura kodu - Frontend (React)

**Design Patterns:**
- **Component-Based Architecture** — reużywalne, izolowane komponenty UI
- **Container/Presentational Pattern** — rozdzielenie logiki (container) od widoku (presentational)
- **Custom Hooks** — enkapsulacja logiki (useAuth, useDebounce, usePagination)
- **Context API** — globalne stany bez prop drilling (AuthContext, ThemeContext)
- **Service Layer** — centralizacja logiki API w dedykowanych serwisach

**Design System:**
- Tokeny projektowe w `styles/main.css` (kolory, spacing, typografia, shadows, transitions)
- Komponenty UI współdzielą wspólne style dla spójności wizualnej
- Responsywność: **mobile-first**, breakpointy: 768px (tablet), 1024px (desktop)

## Baza danych

### Normalizacja (3NF)

Schemat bazy danych jest znormalizowany do **Trzeciej Postaci Normalnej (3NF)**:

**1NF (First Normal Form):**
- ✅ Wszystkie atrybuty zawierają wartości atomowe (pojedyncze, niepodzielne)
- ✅ Każda komórka zawiera tylko jedną wartość
- ✅ Każda tabela ma klucz główny (PRIMARY KEY)

**2NF (Second Normal Form):**
- ✅ Spełnia 1NF
- ✅ Każdy atrybut nie-kluczowy jest w pełni funkcjonalnie zależny od klucza głównego
- ✅ Brak częściowych zależności funkcyjnych

**3NF (Third Normal Form):**
- ✅ Spełnia 2NF
- ✅ Brak zależności przechodnich — atrybuty nie-kluczowe zależą tylko od klucza głównego
- ✅ Brak duplikacji danych między tabelami

### Schemat bazy danych — 35 tabel

#### 1. **Użytkownicy i autoryzacja** (4 tabele)

```sql
app_user               -- Użytkownicy systemu (czytelnicy, bibliotekarze, admini)
refresh_token          -- Tokeny JWT do odświeżania sesji
staff_role             -- Role systemowe z uprawnieniami
registration_token     -- Tokeny aktywacyjne dla nowych kont
```

**Normalizacja:** Role (`ROLE_USER`, `ROLE_LIBRARIAN`, `ROLE_ADMIN`) przechowywane jako JSON w `app_user.roles` mogą naruszać 1NF, ale jest to uzasadnione dla elastyczności systemu uprawnień Symfony. Alternatywnie można użyć tabeli pośredniczącej `user_roles`.

#### 2. **Katalog** (7 tabel)

```sql
book                   -- Książki z metadanymi i embeddingami wektorowymi
author                 -- Autorzy książek
category               -- Kategorie tematyczne
book_copy              -- Fizyczne egzemplarze książek (źródło prawdy dla dostępności)
book_category          -- Relacja M:N między książkami a kategoriami
book_digital_asset     -- Zasoby cyfrowe (okładki, e-booki, audiobooki)
age_range              -- Grupy wiekowe docelowych czytelników
```

**Normalizacja:** Tabela `book` zawiera cachowane liczniki (`copies`, `total_copies`, `storage_copies`, `open_stack_copies`), które techniczne naruszają 3NF, ponieważ są obliczane na podstawie `book_copy`. 

**Uzasadnienie:** Liczniki są **obliczane kolumny** (computed columns) dla wydajności zapytań. Źródłem prawdy jest `book_copy` z filtrowaniem po statusie. Dla ścisłej 3NF można użyć:
- **Materialized Views** — widoki zmaterializowane odświeżane okresowo
- **Database Triggers** — automatyczna aktualizacja przy zmianach w `book_copy`
- **Dynamiczne obliczanie** — COUNT() w zapytaniach (wolniejsze)

#### 3. **Wypożyczenia i rezerwacje** (3 tabele)

```sql
loan                   -- Wypożyczenia książek z terminami zwrotu
reservation            -- Rezerwacje niedostępnych książek
fine                   -- Kary za przetrzymanie lub zniszczenie
```

#### 4. **Oceny i rekomendacje** (4 tabele)

```sql
rating                 -- Oceny książek (1-5 gwiazdek)
review                 -- Recenzje tekstowe
recommendation_feedback -- Feedback użytkownika na rekomendacje AI(in progress)
user_book_interaction  -- Historia interakcji (wyszukiwania, kliknięcia) dla ML(in progress)
```

#### 5. **Kolekcje** (3 tabele)

```sql
book_collection        -- Tematyczne kolekcje książek (np. "Bestsellery 2025")
collection_books       -- Relacja M:N między kolekcjami a książkami
favorite               -- Ulubione książki użytkowników
```

#### 6. **Administracja** (4 tabele)

```sql
audit_logs             -- Dziennik audytu działań w systemie
announcement           -- Ogłoszenia dla użytkowników (np. "Biblioteka nieczynna 1.05")
backup_record          -- Historia backupów bazy danych
system_setting         -- Konfiguracja systemu (key-value store)
```

#### 7. **Integracje** (2 tabele)

```sql
integration_config     -- Konfiguracja API zewnętrznych (OpenAI, SMS gateway)
notification_log       -- Historia wysłanych powiadomień (e-mail, SMS)
```

#### 8. **Zakupy i inwentaryzacja** (5 tabel)(in progress)

```sql
supplier               -- Dostawcy książek
acquisition_budget     -- Budżety zakupowe na okresy
acquisition_order      -- Zamówienia książek
acquisition_expense    -- Wydatki na zakupy
weeding_record         -- Rejestr wycofanych książek (selekcja negatywna)
```

### Dane testowe

Plik `backend/init-db-expanded-v2.sql` (694 linie) zawiera:

- ✅ **Pełny schemat DDL** — CREATE TABLE z indeksami, kluczami obcymi, constraints
- ✅ **30+ rekordów testowych na tabelę** dla realstycznych scenariuszy
- ✅ **Zróżnicowane dane** — użytkownicy z różnymi rolami, książki z różnych kategorii, aktywne wypożyczenia, historyczne rezerwacje

**Statystyki danych testowych:**
```
📚 Książki:          30+  (polskie i zagraniczne, różne gatunki)
👥 Użytkownicy:      15+  (admin, bibliotekarze, czytelnicy)
📖 Egzemplarze:      50+  (różne statusy: available, loaned, lost)
📥 Wypożyczenia:     20+  (aktywne i historyczne)
🔖 Rezerwacje:       10+
⭐ Oceny/recenzje:   25+
📁 Kolekcje:         5+
```

### Indeksy i optymalizacje

**Indeksy dla wydajności:**

```sql
-- Full-text search (PostgreSQL tsvector)
CREATE INDEX idx_book_search_vector ON book USING gin(search_vector);

-- Semantic search (pgvector - cosine similarity)
CREATE INDEX idx_book_embedding ON book USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100);

-- Foreign keys (automatyczne indeksy)
CREATE INDEX idx_book_copy_book_id ON book_copy(book_id);
CREATE INDEX idx_loan_user_id ON loan(user_id);
CREATE INDEX idx_loan_book_copy_id ON loan(book_copy_id);

-- Composite indexes dla częstych zapytań
CREATE INDEX idx_loan_status_due_date ON loan(status, due_date);
CREATE INDEX idx_book_copy_status_location ON book_copy(status, location);
```

**Więzy integralności:**

```sql
-- Check constraints
ALTER TABLE fine ADD CONSTRAINT check_fine_amount_positive CHECK (amount >= 0);
ALTER TABLE rating ADD CONSTRAINT check_rating_value CHECK (value BETWEEN 1 AND 5);

-- Unique constraints
ALTER TABLE app_user ADD CONSTRAINT unique_email UNIQUE (email);
ALTER TABLE book ADD CONSTRAINT unique_isbn UNIQUE (isbn);

-- Foreign keys z CASCADE
ALTER TABLE loan ADD CONSTRAINT fk_loan_user 
  FOREIGN KEY (user_id) REFERENCES app_user(id) ON DELETE CASCADE;
```

## API - dokumentacja

### OpenAPI/Swagger

Pełna dokumentacja API w formacie **OpenAPI 3.0** z interaktywnym interfejsem Swagger UI.

**Dostęp:**
- **Swagger UI:** http://localhost:8000/api/docs (interfejs graficzny, możliwość testowania endpointów)
- **JSON spec:** http://localhost:8000/api/docs.json (specyfikacja dla narzędzi)

**Funkcje Swagger UI:**
- 🔍 Przeglądanie wszystkich endpointów z parametrami i schematami
- 🧪 Testowanie API bezpośrednio z przeglądarki (Try it out)
- 🔐 Autoryzacja JWT przez interfejs
- 📋 Generowanie przykładowych żądań curl/Python/JavaScript

### Główne endpointy

#### Uwierzytelnianie

| Metoda | Endpoint | Opis | Autoryzacja |
|--------|----------|------|-------------|
| POST | `/api/auth/register` | Rejestracja nowego użytkownika | ❌ Publiczny |
| POST | `/api/auth/login` | Logowanie, zwraca JWT token | ❌ Publiczny |
| POST | `/api/auth/refresh` | Odświeżenie tokenu JWT | ✅ Refresh token |
| GET | `/api/me` | Profil zalogowanego użytkownika | ✅ JWT |
| PUT | `/api/me` | Aktualizacja profilu | ✅ JWT |

#### Katalog

| Metoda | Endpoint | Opis | Autoryzacja |
|--------|----------|------|-------------|
| GET | `/api/books` | Lista książek (paginacja, filtry, wyszukiwanie) | ❌ Publiczny |
| GET | `/api/books/{id}` | Szczegóły książki z dostępnością | ❌ Publiczny |
| POST | `/api/books` | Dodanie nowej książki | ✅ LIBRARIAN |
| PUT | `/api/books/{id}` | Aktualizacja książki | ✅ LIBRARIAN |
| DELETE | `/api/books/{id}` | Usunięcie książki | ✅ ADMIN |
| GET | `/api/books/search` | Wyszukiwanie full-text + semantyczne | ❌ Publiczny |
| GET | `/api/authors` | Lista autorów | ❌ Publiczny |
| GET | `/api/categories` | Lista kategorii | ❌ Publiczny |

#### Użytkownik

| Metoda | Endpoint | Opis | Autoryzacja |
|--------|----------|------|-------------|
| GET | `/api/me/loans` | Moje aktywne wypożyczenia | ✅ USER |
| GET | `/api/me/loans/history` | Historia wypożyczeń | ✅ USER |
| GET | `/api/me/reservations` | Moje rezerwacje | ✅ USER |
| POST | `/api/me/reservations` | Utworzenie rezerwacji | ✅ USER |
| DELETE | `/api/me/reservations/{id}` | Anulowanie rezerwacji | ✅ USER |
| GET | `/api/me/fees` | Moje opłaty i kary | ✅ USER |
| POST | `/api/me/fees/{id}/pay` | Opłacenie kary | ✅ USER |
| GET | `/api/me/favorites` | Ulubione książki | ✅ USER |
| POST | `/api/me/favorites/{bookId}` | Dodaj do ulubionych | ✅ USER |

#### Wypożyczenia (bibliotekarz)

| Metoda | Endpoint | Opis | Autoryzacja |
|--------|----------|------|-------------|
| GET | `/api/loans` | Wszystkie wypożyczenia (filtry, paginacja) | ✅ LIBRARIAN |
| POST | `/api/loans` | Utworzenie wypożyczenia | ✅ LIBRARIAN |
| PUT | `/api/loans/{id}/return` | Zwrot książki | ✅ LIBRARIAN |
| PUT | `/api/loans/{id}/extend` | Przedłużenie wypożyczenia | ✅ LIBRARIAN |
| GET | `/api/loans/overdue` | Przeterminowane wypożyczenia | ✅ LIBRARIAN |

#### Rekomendacje

| Metoda | Endpoint | Opis | Autoryzacja |
|--------|----------|------|-------------|
| GET | `/api/recommendations` | Personalizowane rekomendacje AI | ✅ USER |
| GET | `/api/recommendations/similar/{bookId}` | Podobne książki | ❌ Publiczny |
| POST | `/api/recommendations/{id}/feedback` | Feedback na rekomendację | ✅ USER |

#### Administracja

| Metoda | Endpoint | Opis | Autoryzacja |
|--------|----------|------|-------------|
| GET | `/api/users` | Lista użytkowników (paginacja) | ✅ ADMIN |
| PUT | `/api/users/{id}` | Aktualizacja użytkownika | ✅ ADMIN |
| POST | `/api/users/{id}/block` | Blokowanie użytkownika | ✅ ADMIN |
| POST | `/api/users/{id}/unblock` | Odblokowanie użytkownika | ✅ ADMIN |
| GET | `/api/audit-logs` | Dziennik audytu | ✅ ADMIN |
| GET | `/api/stats/dashboard` | Statystyki dashboardu | ✅ ADMIN |
| POST | `/api/announcements` | Utworzenie ogłoszenia | ✅ ADMIN |

### Parametry zapytań

**Paginacja:**
```http
GET /api/books?page=1&limit=20
```

**Filtrowanie:**
```http
GET /api/books?category=fantasy&author=Sapkowski&available=true
```

**Sortowanie:**
```http
GET /api/books?sort=title&order=asc
```

**Wyszukiwanie:**
```http
GET /api/books/search?q=wiedźmin&type=fulltext  # Full-text search
GET /api/books/search?q=fantasy+adventure&type=semantic  # Semantic search (AI)
```

### Obsługa błędów

API zwraca standardowe kody HTTP z szczegółowymi komunikatami błędów:

| Kod | Znaczenie | Przykład |
|-----|-----------|----------|
| **200** | OK — żądanie zakończone sukcesem | Pobrano listę książek |
| **201** | Created — zasób utworzony | Utworzono nowe wypożyczenie |
| **204** | No Content — sukces bez treści | Usunięto rezerwację |
| **400** | Bad Request — błędne dane wejściowe | Nieprawidłowy format ISBN |
| **401** | Unauthorized — brak autoryzacji | Brak tokenu JWT lub token wygasł |
| **403** | Forbidden — brak uprawnień | Użytkownik nie ma roli LIBRARIAN |
| **404** | Not Found — zasób nie istnieje | Książka o ID 999 nie istnieje |
| **422** | Unprocessable Entity — błąd walidacji | Email już istnieje w systemie |
| **429** | Too Many Requests — rate limiting | Przekroczono limit żądań (100/min) |
| **500** | Internal Server Error — błąd serwera | Nieoczekiwany błąd, sprawdź logi |

**Przykład odpowiedzi błędu walidacji:**

```json
{
  "error": {
    "code": "VALIDATION_FAILED",
    "message": "Walidacja danych nie powiodła się",
    "statusCode": 422,
    "details": {
      "email": ["Email jest wymagany", "Email musi być prawidłowym adresem"],
      "password": ["Hasło musi mieć minimum 8 znaków"]
    }
  }
}
```

**Przykład odpowiedzi błędu autoryzacji:**

```json
{
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Token JWT wygasł",
    "statusCode": 401
  }
}
```

### Frontend ↔ API

Frontend konsumuje API przez zunifikowany wrapper (`frontend/src/services/api.js`):

**Funkcje:**
- ✅ **Automatyczne dodawanie tokenów JWT** do nagłówka `Authorization: Bearer <token>`
- ✅ **Obsługa błędów** z wyświetlaniem przyjaznych komunikatów użytkownikowi
- ✅ **Stany loading** — spinner lub skeleton loader podczas ładowania
- ✅ **Transformacja odpowiedzi** — automatyczne parsowanie JSON
- ✅ **Retry logic** — ponowienie nieudanych żądań (3 próby z exponential backoff)
- ✅ **Request/Response interceptors** — logowanie, cache, error handling

**Przykład użycia w komponencie React:**

```javascript
// frontend/src/services/bookService.js
import api from './api';

export const getBooks = async (params) => {
  try {
    const response = await api.get('/books', { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

// frontend/src/pages/BooksPage.jsx
import { useState, useEffect } from 'react';
import { getBooks } from '../services/bookService';

function BooksPage() {
  const [books, setBooks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchBooks = async () => {
      try {
        setLoading(true);
        const data = await getBooks({ page: 1, limit: 20 });
        setBooks(data.items);
      } catch (err) {
        setError('Nie udało się pobrać książek');
      } finally {
        setLoading(false);
      }
    };
    
    fetchBooks();
  }, []);

  if (loading) return <LoadingSpinner />;
  if (error) return <ErrorMessage message={error} retry={fetchBooks} />;
  
  return <BookGrid books={books} />;
}
```

**Komponenty React wyświetlają trzy stany:**
- **Loading** — `<LoadingSpinner />` lub `<SkeletonLoader />` podczas ładowania danych
- **Error** — `<ErrorMessage />` z komunikatem błędu i przyciskiem "Spróbuj ponownie"
- **Success** — wyświetlenie danych w odpowiednim komponencie UI

## Funkcjonalności

## Funkcjonalności

### Zaimplementowane (>90%)

#### ✅ Zarządzanie katalogiem
- **CRUD książek** — pełna obsługa dodawania, edycji, usuwania książek z walidacją
- **Zarządzanie autorami i kategoriami** — przypisywanie wielu kategorii do książki (M:N)
- **Egzemplarze fizyczne** — śledzenie statusu każdego egzemplarza (available, loaned, damaged, lost)
- **Zasoby cyfrowe** — przechowywanie okładek
- **Wyszukiwanie pełnotekstowe** — GIN index na `tsvector` dla szybkiego przeszukiwania tytułów, opisów, autorów
- **Wyszukiwanie semantyczne (AI)** — pgvector z embeddingami OpenAI (podobieństwo kosinusowe)

#### ✅ Wypożyczenia i rezerwacje
- **Wypożyczanie książek** — automatyczne obliczanie terminu zwrotu (14 dni), weryfikacja limitu użytkownika
- **Zwrot książek** — obsługa opóźnień, automatyczne naliczanie kar (0.50 PLN/dzień)
- **Przedłużanie wypożyczeń** — możliwość przedłużenia o 14 dni (max 2 razy)
- **Rezerwacje** — kolejkowanie rezerwacji dla niedostępnych książek
- **Powiadomienia** — automatyczne przypomnienia 3 dni przed terminem zwrotu (email/SMS)
- **Historia** — pełna historia wypożyczeń użytkownika z statystykami

#### ✅ Inteligentne rekomendacje
- **Rekomendacje oparte na AI** — wykorzystanie embeddingów wektorowych do znajdowania podobnych książek
- **Personalizacja** — rekomendacje na podstawie historii wypożyczeń i ocen użytkownika
- **Feedback loop** — użytkownik może ocenić rekomendacje (thumbs up/down), co poprawia algorytm
- **Podobne książki** — "Czytelnicy tej książki czytali również..."

#### ✅ System użytkowników
- **Rejestracja i aktywacja** — rejestracja z potwierdzeniem e-mail (registration token)
- **Logowanie JWT** — bezpieczne tokeny z czasem wygaśnięcia (1h access token, 7 dni refresh token)
- **Role i uprawnienia** — USER, LIBRARIAN, ADMIN z kontrolą dostępu na poziomie endpointów
- **Profil użytkownika** — edycja danych osobowych, preferencji (motyw, język, wielkość czcionki)
- **Blokowanie kont** — administrator może zablokować użytkownika za naruszenia regulaminu

#### ✅ Kary i płatności
- **Automatyczne naliczanie kar** — za przetrzymanie (0.50 PLN/dzień), zniszczenie, zagubienie
- **Historia opłat** — pełna historia kar z statusami (pending, paid, waived)
- **Płatności online** — integracja z bramką płatności (placeholder, gotowe do rozbudowy)
- **Zwolnienie z kary** — bibliotekarz lub admin może anulować karę (np. okoliczności łagodzące)

#### ✅ Panel administracyjny
- **Dashboard ze statystykami** — liczby wypożyczeń, top książki, aktywność użytkowników
- **Zarządzanie użytkownikami** — lista, edycja, blokowanie, resetowanie haseł
- **Dziennik audytu** — rejestracja wszystkich ważnych akcji w systemie (login, CRUD, admin actions)
- **Ogłoszenia** — publikowanie ogłoszeń dla użytkowników (np. "Biblioteka nieczynna 1.05")
- **Raporty** — generowanie raportów (wypożyczenia, finanse, statystyki)

#### ✅ Kolekcje tematyczne
- **Tworzenie kolekcji** — bibliotekarz może tworzyć kolekcje (np. "Bestsellery 2025", "Nowości")
- **Wyróżnione kolekcje** — wyświetlanie na stronie głównej
- **Ulubione książki** — użytkownik może dodawać książki do ulubionych

#### ✅ Powiadomienia
- **E-mail** — przypomnienia o zbliżających się terminach, potwierdzenia działań (in progress)
- **SMS** — krytyczne powiadomienia (przeterminowane wypożyczenie, gotowa rezerwacja)
- **Historia powiadomień** — tracking wysłanych wiadomości w `notification_log`
- **Test endpoint** (ROLE_LIBRARIAN):
  ```http
  POST /api/notifications/test
  Content-Type: application/json
  Authorization: Bearer <token>
  
  {
    "channel": "email",        // "email" lub "sms"
    "target": "user@email.com", // adres e-mail lub numer telefonu
    "message": "Test message"   // opcjonalna wiadomość
  }
  ```
  **Odpowiedź (202 Accepted):** Powiadomienie zakolejkowane do wysłania

#### ✅ Asynchroniczne przetwarzanie
- **RabbitMQ + Symfony Messenger** — kolejkowanie zadań w tle (transport `async`)
- **Worker** — automatyczne uruchamianie w Docker Compose (`php-worker`)
- **Obsługiwane wiadomości asynchroniczne:**
  - `ReservationQueuedNotification` — powiadomienie o zakolejkowanej rezerwacji
  - `ReservationReadyMessage` — powiadomienie o gotowej rezerwacji
  - `LoanDueReminderMessage` — przypomnienie o zbliżającym się terminie zwrotu
  - `LoanOverdueMessage` — powiadomienie o przeterminowanym wypożyczeniu
  - `UpdateBookEmbeddingMessage` — generowanie embeddingów (OpenAI API) dla wyszukiwania semantycznego
- **Retry strategy:** max 3 próby z exponential backoff (1s → 2s → 4s)
- **Dead letter queue:** nieudane wiadomości zapisywane do osobnej kolejki

### W trakcie implementacji (~5%)

- **Integracja płatności** — pełna integracja z Stripe/PayU (obecnie placeholder)
- **Export raportów** — generowanie PDF raportów 
- **Wersje książek** — obsługa wielu wydań tej samej książki (ISBN-10 vs ISBN-13)

### Planowane rozszerzenia (~5%)

- **Czytnik e-booków** — integracja z formatami EPUB/PDF
- **API publiczne** — dostęp dla bibliotek zewnętrznych 
- **Chatbot** — asystent AI dla użytkowników (wyszukiwanie, rekomendacje, FAQ)

## Uwierzytelnianie i autoryzacja

### JWT (JSON Web Tokens)

System wykorzystuje **JWT (JSON Web Tokens)** do bezpiecznej autoryzacji użytkowników. Symfony Security + LexikJWTAuthenticationBundle zapewnia robust implementation.

#### Flow logowania

**1. Logowanie:**

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@biblioteka.local",
  "password": "user123"
}
```

**Odpowiedź (Success 200):**

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refreshToken": "def502004a1b2c3d4e5f6789...",
  "user": {
    "id": 3,
    "email": "user@biblioteka.local",
    "name": "Jan Kowalski",
    "roles": ["ROLE_USER"],
    "verified": true
  }
}
```

**Access Token:**
- Wygasa po **1 godzinie**
- Przechowywany w `localStorage` (frontend)
- Używany do autoryzacji żądań API

**Refresh Token:**
- Wygasa po **7 dniach**
- Przechowywany w `httpOnly` cookie (bezpieczniejsze) lub `localStorage`
- Używany do odświeżenia access token bez ponownego logowania

**2. Odświeżanie tokena:**

```http
POST /api/auth/refresh
Content-Type: application/json

{
  "refreshToken": "def502004a1b2c3d4e5f6789..."
}
```

**Odpowiedź:**

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",  // Nowy access token
  "refreshToken": "abc123def456..."  // Nowy refresh token (rotation)
}
```

**3. Autoryzowane żądania:**

```http
GET /api/me
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

**Odpowiedź:**

```json
{
  "id": 3,
  "email": "user@biblioteka.local",
  "name": "Jan Kowalski",
  "roles": ["ROLE_USER"],
  "membershipGroup": "standard",
  "loanLimit": 5,
  "activeLoanCount": 2,
  "outstandingFees": 0.00
}
```

### Role użytkowników (RBAC)

System wykorzystuje **Role-Based Access Control (RBAC)** z trzema głównymi rolami:

| Rola | Uprawnienia | Opis |
|------|-------------|------|
| **ROLE_USER** | Podstawowe uprawnienia czytelnika | Przeglądanie katalogu, wypożyczenia, rezerwacje, profil, ulubione, oceny |
| **ROLE_LIBRARIAN** | Uprawnienia bibliotekarza | ROLE_USER + zarządzanie katalogiem, obsługa wypożyczeń/zwrotów, powiadomienia, raporty |
| **ROLE_ADMIN** | Pełne uprawnienia administratora | ROLE_LIBRARIAN + zarządzanie użytkownikami, system settings, audit logs, backupy |

**Hierarchia ról** (`backend/config/packages/security.yaml`):

```yaml
security:
  role_hierarchy:
    ROLE_LIBRARIAN: ROLE_USER
    ROLE_ADMIN: ROLE_LIBRARIAN
```

**Przykład zabezpieczenia endpointów (Symfony):**

```php
// backend/src/Controller/LoanController.php

#[Route('/api/loans', methods: ['GET'])]
#[IsGranted('ROLE_LIBRARIAN')]  // Tylko bibliotekarz i admin
public function getAllLoans(): JsonResponse
{
    // ...
}

#[Route('/api/me/loans', methods: ['GET'])]
#[IsGranted('ROLE_USER')]  // Każdy zalogowany użytkownik
public function getMyLoans(): JsonResponse
{
    // ...
}

#[Route('/api/users', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]  // Tylko administrator
public function getAllUsers(): JsonResponse
{
    // ...
}
```

### API Secret (integracje systemowe)

Dla integracji zewnętrznych (np. RFID, external library systems), system obsługuje również autoryzację przez **API Secret**:

```http
GET /api/books
X-API-SECRET: your-secret-key-here
```

**Konfiguracja** (`backend/.env`):

```env
API_SECRET=3x@mpl3-s3cr3t-k3y-f0r-1nt3gr@t10n5
```

**Middleware** weryfikuje nagłówek `X-API-SECRET` przed dostępem do endpointów oznaczonych atrybutem `#[RequiresApiSecret]`.

### Bezpieczeństwo

- ✅ **Hasła** — hashowane Argon2id (najlepszy dostępny algorytm w PHP 8.2)
- ✅ **CSRF Protection** — tokeny dla formularzy (SameSite cookies)
- ✅ **Rate Limiting** — max 100 żądań/minutę na IP (zapobieganie brute-force)
- ✅ **CORS** — konfiguracja dla dozwolonych origin
- ✅ **SQL Injection** — Doctrine ORM z prepared statements
- ✅ **XSS** — sanitization danych wejściowych, CSP headers
- ✅ **JWT Secret Rotation** — wielokluczowa walidacja (`JWT_SECRETS`) dla zero-downtime rotation

## Kolejki asynchroniczne

System wykorzystuje **Symfony Messenger** z transportem **RabbitMQ** do przetwarzania zadań w tle, co poprawia wydajność i skalowalność aplikacji.

### Architektura

```
┌─────────────┐         ┌──────────────┐         ┌───────────────┐
│  Symfony    │ Message │   RabbitMQ   │ Consume │  PHP Worker   │
│ Application ├────────→│  (Broker)    │←────────┤  (Consumer)   │
└─────────────┘         └──────────────┘         └───────────────┘
       │                        │                         │
       │ Dispatch               │ Queue                   │ Handle
       ↓                        ↓                         ↓
   Controller             Exchange/Queue            MessageHandler
```

### Kolejki (Queues)

System wykorzystuje jedną kolejkę `async` z różnymi typami wiadomości (messages):

**1. LoanDueReminderMessage** — Przypomnienia o zbliżającym się terminie zwrotu

```php
// backend/src/Message/LoanDueReminderMessage.php
class LoanDueReminderMessage
{
    public function __construct(
        private int $loanId
    ) {}
}

// backend/src/MessageHandler/LoanDueReminderHandler.php
#[AsMessageHandler]
class LoanDueReminderHandler
{
    public function __invoke(LoanDueReminderMessage $message): void
    {
        $loan = $this->loanRepository->find($message->getLoanId());
        
        // Wysłanie e-mail 3 dni przed terminem
        $this->mailer->send(
            to: $loan->getUser()->getEmail(),
            subject: 'Przypomnienie o zbliżającym się terminie zwrotu',
            template: 'emails/loan_due_reminder.html.twig',
            context: ['loan' => $loan]
        );
        
        // Opcjonalnie SMS dla użytkowników z włączonymi powiadomieniami SMS
        if ($loan->getUser()->getSmsNotificationsEnabled()) {
            $this->smsService->send($loan->getUser()->getPhone(), $message);
        }
    }
}
```

**Kiedy:** Cron job uruchamiany codziennie o 9:00 wysyła wiadomości dla wszystkich wypożyczeń kończących się za 3 dni.

**2. LoanOverdueMessage** — Powiadomienia o przeterminowanych wypożyczeniach

```php
class LoanOverdueMessage
{
    public function __construct(
        private int $loanId
    ) {}
}
```

**Kiedy:** Cron job uruchamiany codziennie o 10:00 dla wypożyczeń po terminie.

**Akcje:**
- Wysłanie powiadomienia e-mail/SMS
- Automatyczne naliczenie kary (`fine` w bazie danych)
- Zablokowanie możliwości nowych wypożyczeń do czasu zwrotu/płatności

**3. ReservationReadyMessage** — Powiadomienie o gotowej rezerwacji

```php
class ReservationReadyMessage
{
    public function __construct(
        private int $reservationId
    ) {}
}
```

**Kiedy:** Gdy książka zostanie zwrócona i jest pierwsza w kolejce rezerwacji.

**4. UpdateBookEmbeddingMessage** — Generowanie embeddingów AI

```php
class UpdateBookEmbeddingMessage
{
    public function __construct(
        private int $bookId
    ) {}
}
```

**Kiedy:** Dodanie/edycja książki (zmiana tytułu, opisu, kategorii).

**Akcje:**
- Wywołanie OpenAI API (text-embedding-3-small) dla opisu książki
- Zapisanie wektora 1536-wymiarowego w `book.embedding` (pgvector)
- Umożliwienie semantycznego wyszukiwania

**5. UpdateUserRecommendationsMessage** — Aktualizacja rekomendacji użytkownika

```php
class UpdateUserRecommendationsMessage
{
    public function __construct(
        private int $userId
    ) {}
}
```

**Kiedy:** Użytkownik ocenił książkę, zakończył wypożyczenie, zmienił preferencje.

**Akcje:**
- Obliczenie wektora preferencji użytkownika (`taste_embedding`)
- Wyszukiwanie podobnych książek (cosine similarity w pgvector)
- Cache wyników w dedykowanej tabeli lub Redis

### Uruchomienie workera

**Docker (automatyczne):**
Worker jest uruchamiany jako osobny kontener `php-worker` w `docker-compose.yml`:

```yaml
php-worker:
  build: ./docker/backend
  command: php /var/www/backend/bin/console messenger:consume async -vv --time-limit=3600
  restart: always
  depends_on:
    - db
    - rabbitmq
```

**Ręcznie (development):**

```powershell
cd backend
php bin/console messenger:consume async -vv
```

**Opcje:**
- `-vv` — verbose output (wyświetlanie szczegółów przetwarzania)
- `--time-limit=3600` — restart workera po 1h (zapobieganie memory leaks)
- `--memory-limit=128M` — limit pamięci
- `--limit=100` — przetwórz max 100 wiadomości i zakończ

**Production (Supervisor):**

```ini
[program:messenger-consume]
command=php /var/www/backend/bin/console messenger:consume async --time-limit=3600
user=www-data
numprocs=2
autostart=true
autorestart=true
```

### Monitoring

**RabbitMQ Management UI:** http://localhost:15672

- **Login:** `app` / `app`
- **Funkcje:**
  - Podgląd kolejek (liczba wiadomości, consumers)
  - Statystyki wydajności (messages/sec, acks, rejects)
  - Ręczne wysyłanie testowych wiadomości
  - Dead Letter Queue (DLQ) — nieudane wiadomości

**Logi Symfony:**

```powershell
# Śledzenie workera w czasie rzeczywistym
docker compose logs -f php-worker

# Backend logs
docker compose logs -f backend
```

### Konfiguracja

**Plik:** `backend/config/packages/messenger.yaml`

```yaml
framework:
  messenger:
    failure_transport: failed  # Dead Letter Queue
    
    transports:
      async:
        dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        retry_strategy:
          max_retries: 3
          delay: 1000           # 1 sekunda
          multiplier: 2         # Exponential backoff (1s, 2s, 4s)
          max_delay: 60000      # Max 60 sekund
      
      failed: 'doctrine://default?queue_name=failed'
    
    routing:
      App\Message\LoanDueReminderMessage: async
      App\Message\LoanOverdueMessage: async
      App\Message\ReservationReadyMessage: async
      App\Message\UpdateBookEmbeddingMessage: async
      App\Message\UpdateUserRecommendationsMessage: async
```

**Zmienne środowiskowe** (`backend/.env`):

```env
# RabbitMQ connection
MESSENGER_TRANSPORT_DSN=amqp://app:app@rabbitmq:5672/%2f/messages

# OpenAI API (dla embeddingów)
OPENAI_API_KEY=sk-proj-...

# Email (SMTP)
MAILER_DSN=smtp://localhost:1025  # Development (MailHog)
# MAILER_DSN=smtp://user:pass@smtp.gmail.com:587  # Production

# SMS Gateway
SMS_API_KEY=your-sms-api-key
SMS_API_URL=https://api.smsgateway.com/send
```

### Retry Strategy i Dead Letter Queue

**Retry strategy:**
- Worker automatycznie ponawia nieudane wiadomości (max 3 razy)
- Exponential backoff: 1s, 2s, 4s
- Po 3 nieudanych próbach → wiadomość trafia do `failed` transport

**Dead Letter Queue (DLQ):**
- Nieudane wiadomości przechowywane w `failed` transport (Doctrine)
- Możliwość ręcznego ponowienia:

```powershell
# Lista nieudanych wiadomości
php bin/console messenger:failed:show

# Ponów wszystkie
php bin/console messenger:failed:retry

# Ponów konkretną wiadomość
php bin/console messenger:failed:retry 5

# Usuń nieudane wiadomości
php bin/console messenger:failed:remove 5
```

## Testowanie

## Testowanie

### Backend (PHPUnit)

Projekt zawiera **50+ testów** obejmujących testy jednostkowe, integracyjne i funkcjonalne.

**Uruchomienie wszystkich testów:**

```powershell
cd backend
vendor/bin/phpunit
```

**Uruchomienie konkretnej grupy testów:**

```powershell
# Testy jednostkowe
vendor/bin/phpunit tests/Unit

# Testy integracyjne
vendor/bin/phpunit tests/Integration

# Testy funkcjonalne (API)
vendor/bin/phpunit tests/Functional
```

**Pokrycie testów (code coverage):**

```powershell
vendor/bin/phpunit --coverage-html coverage
# Raport dostępny w: backend/coverage/index.html
```

**Typy testów:**

**1. Unit Tests** (`tests/Unit/`) — 20+ testów
- Testy serwisów biznesowych (BookService, LoanService, NotificationService)
- Testy helperów i utilities
- Izolowane od bazy danych (mocki, stuby)

Przykład:
```php
// tests/Unit/Service/LoanServiceTest.php
class LoanServiceTest extends TestCase
{
    public function testCalculateDueDate(): void
    {
        $service = new LoanService();
        $dueDate = $service->calculateDueDate(new \DateTime('2025-01-15'), 14);
        
        $this->assertEquals('2025-01-29', $dueDate->format('Y-m-d'));
    }
    
    public function testCalculateFine(): void
    {
        $service = new LoanService();
        $fine = $service->calculateFine(daysOverdue: 10, dailyRate: 0.50);
        
        $this->assertEquals(5.00, $fine);
    }
}
```

**2. Integration Tests** (`tests/Integration/`) — 15+ testów
- Testy repozytoriów z prawdziwą bazą danych (SQLite in-memory)
- Testy Doctrine queries
- Weryfikacja integralności danych

Przykład:
```php
// tests/Integration/Repository/BookRepositoryTest.php
class BookRepositoryTest extends KernelTestCase
{
    private BookRepository $repository;
    
    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(BookRepository::class);
    }
    
    public function testFindAvailableBooks(): void
    {
        $books = $this->repository->findAvailableBooks();
        
        $this->assertGreaterThan(0, count($books));
        $this->assertTrue($books[0]->getCopies() > 0);
    }
}
```

**3. Functional Tests** (`tests/Functional/`) — 20+ testów
- Testy endpointów API (HTTP requests)
- Weryfikacja statusów, headers, JSON response
- Testy autoryzacji i uprawnień

Przykład:
```php
// tests/Functional/Controller/BookControllerTest.php
class BookControllerTest extends WebTestCase
{
    public function testGetBooks(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/books?page=1&limit=10');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('total', $data);
    }
    
    public function testCreateBookRequiresLibrarianRole(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/books', [], [], [], json_encode([
            'title' => 'Test Book',
            'isbn' => '978-83-7574-842-0',
            'authorId' => 1
        ]));
        
        $this->assertResponseStatusCodeSame(401);  // Unauthorized
    }
    
    public function testCreateBookWithValidData(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getLibrarianUser());  // Mock LIBRARIAN user
        
        $client->request('POST', '/api/books', [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Wiedźmin: Ostatnie życzenie',
                'isbn' => '978-83-7574-842-0',
                'authorId' => 1,
                'categoryIds' => [1, 2],
                'publicationYear' => 1993
            ])
        );
        
        $this->assertResponseStatusCodeSame(201);  // Created
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Wiedźmin: Ostatnie życzenie', $data['title']);
    }
}
```

**Analiza statyczna (PHPStan):**

PHPStan sprawdza kod bez uruchamiania, wykrywając błędy typów, nieosiągalny kod, nieużywane zmienne.

```powershell
cd backend
vendor/bin/phpstan analyse src --level=8
```

**Level 8** — najwyższy poziom strictness, wymaga pełnego typowania.

### Frontend (Vitest + Playwright)

Projekt zawiera **35+ testów** frontendowych.

**Unit Tests (Vitest):**

```powershell
cd frontend
npm run test              # Tryb watch (interaktywny)
npm run test:run          # Uruchom wszystkie testy (CI)
npm run test:ui           # UI do testów (browser)
npm run test:coverage     # Pokrycie kodu
```

**Przykład testu komponentu:**

```javascript
// frontend/tests/unit/BookCard.test.jsx
import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import BookCard from '../../src/components/BookCard';

describe('BookCard', () => {
  it('renders book title and author', () => {
    const book = {
      id: 1,
      title: 'Wiedźmin',
      author: { name: 'Andrzej Sapkowski' },
      copiesAvailable: 3
    };
    
    render(<BookCard book={book} />);
    
    expect(screen.getByText('Wiedźmin')).toBeInTheDocument();
    expect(screen.getByText('Andrzej Sapkowski')).toBeInTheDocument();
  });
  
  it('shows availability status', () => {
    const book = {
      id: 1,
      title: 'Test Book',
      author: { name: 'Test Author' },
      copiesAvailable: 0
    };
    
    render(<BookCard book={book} />);
    
    expect(screen.getByText('Niedostępna')).toBeInTheDocument();
  });
});
```

**E2E Tests (Playwright):**

```powershell
cd frontend
npm run test:e2e          # Uruchom testy E2E
npm run test:e2e:ui       # Playwright UI mode
```

**Przykład testu E2E:**

```javascript
// frontend/tests/e2e/loan-flow.spec.js
import { test, expect } from '@playwright/test';

test.describe('Loan Flow', () => {
  test('user can borrow a book', async ({ page }) => {
    // Login
    await page.goto('http://localhost:5173/login');
    await page.fill('[name="email"]', 'user@biblioteka.local');
    await page.fill('[name="password"]', 'user123');
    await page.click('button[type="submit"]');
    
    // Navigate to catalog
    await expect(page).toHaveURL(/\/dashboard/);
    await page.click('a[href="/books"]');
    
    // Find and borrow a book
    await page.click('.book-card:first-child');
    await expect(page.locator('h1')).toContainText('Wiedźmin');
    await page.click('button:has-text("Wypożycz")');
    
    // Verify success
    await expect(page.locator('.success-message')).toContainText('Książka wypożyczona');
    
    // Check loans page
    await page.click('a[href="/my-loans"]');
    await expect(page.locator('.loan-item')).toHaveCount(1);
  });
});
```

**Test Coverage Goals:**

| Typ | Obecne | Cel |
|-----|--------|-----|
| Backend Unit | 75% | 85% |
| Backend Integration | 80% | 90% |
| Backend Functional | 70% | 85% |
| Frontend Unit | 65% | 80% |
| Frontend E2E | Critical paths | All major flows |

## UX/UI i responsywność

### Design System

Aplikacja wykorzystuje spójny system projektowy oparty na tokenach CSS i wielokrotnie używalnych komponentach UI.

**Tokeny projektowe** (`frontend/src/styles/main.css`):

```css
:root {
  /* Kolory - Paleta */
  --color-primary: #3b82f6;      /* Blue-500 */
  --color-primary-hover: #2563eb; /* Blue-600 */
  --color-secondary: #8b5cf6;     /* Purple-500 */
  --color-accent: #f59e0b;        /* Amber-500 */
  --color-success: #10b981;       /* Green-500 */
  --color-warning: #f59e0b;       /* Amber-500 */
  --color-error: #ef4444;         /* Red-500 */
  
  /* Neutrals */
  --color-text-primary: #1f2937;   /* Gray-800 */
  --color-text-secondary: #6b7280; /* Gray-500 */
  --color-background: #ffffff;
  --color-surface: #f9fafb;        /* Gray-50 */
  --color-border: #e5e7eb;         /* Gray-200 */
  
  /* Spacing (8px baseline grid) */
  --space-xs: 0.25rem;  /* 4px */
  --space-sm: 0.5rem;   /* 8px */
  --space-md: 1rem;     /* 16px */
  --space-lg: 1.5rem;   /* 24px */
  --space-xl: 2rem;     /* 32px */
  --space-2xl: 3rem;    /* 48px */
  
  /* Typography */
  --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
  --font-mono: 'Fira Code', monospace;
  
  --text-xs: 0.75rem;   /* 12px */
  --text-sm: 0.875rem;  /* 14px */
  --text-base: 1rem;    /* 16px */
  --text-lg: 1.125rem;  /* 18px */
  --text-xl: 1.25rem;   /* 20px */
  --text-2xl: 1.5rem;   /* 24px */
  --text-3xl: 1.875rem; /* 30px */
  
  /* Shadows */
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
  
  /* Border Radius */
  --radius-sm: 0.25rem; /* 4px */
  --radius-md: 0.5rem;  /* 8px */
  --radius-lg: 0.75rem; /* 12px */
  --radius-full: 9999px;
  
  /* Transitions */
  --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
  --transition-base: 250ms cubic-bezier(0.4, 0, 0.2, 1);
  --transition-slow: 350ms cubic-bezier(0.4, 0, 0.2, 1);
}

/* Dark mode */
[data-theme="dark"] {
  --color-text-primary: #f9fafb;
  --color-text-secondary: #d1d5db;
  --color-background: #111827;
  --color-surface: #1f2937;
  --color-border: #374151;
}
```

### Responsywność (Mobile-First)

Aplikacja wykorzystuje podejście **mobile-first** z trzema głównymi breakpointami:

```css
/* Mobile (default) - 320px+ */
.container {
  padding: var(--space-md);
}

/* Tablet - 768px+ */
@media (min-width: 768px) {
  .container {
    padding: var(--space-lg);
  }
  
  .book-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Desktop - 1024px+ */
@media (min-width: 1024px) {
  .container {
    max-width: 1280px;
    margin: 0 auto;
    padding: var(--space-xl);
  }
  
  .book-grid {
    grid-template-columns: repeat(4, 1fr);
  }
  
  .sidebar {
    display: block;  /* Hidden on mobile */
  }
}

/* Large Desktop - 1536px+ */
@media (min-width: 1536px) {
  .book-grid {
    grid-template-columns: repeat(5, 1fr);
  }
}
```

**Przykładowe komponenty responsywne:**

- **Nawigacja** — hamburger menu na mobile, full navbar na desktop
- **Tabele** — karty na mobile, tabela na desktop
- **Formularze** — jedna kolumna na mobile, dwie kolumny na desktop
- **Siatki produktów** — 1 kolumna (mobile), 2 (tablet), 4 (desktop), 5 (XL desktop)

### Dostępność (Accessibility)

- ✅ **Semantyczny HTML** — `<nav>`, `<main>`, `<article>`, `<section>`, `<button>`
- ✅ **ARIA labels** — `aria-label`, `aria-describedby`, `aria-live` dla dynamicznych treści
- ✅ **Obsługa klawiatury** — Tab, Enter, Escape, Arrow keys
- ✅ **Focus visible** — wyraźne wskazanie fokusa dla użytkowników klawiatury
- ✅ **Kontrast kolorów** — zgodność z WCAG 2.1 Level AA (minimum 4.5:1)
- ✅ **Screen reader friendly** — opisowe teksty alternatywne, ukryte opisy dla ikon

### Tryby kolorystyczne

Użytkownik może wybrać motyw w profilu:

- 🌞 **Jasny** — domyślny, najlepszy dla dnia
- 🌙 **Ciemny** — dla pracy wieczornej, mniej męczący dla oczu
- 🔄 **Automatyczny** — dostosowanie do ustawień systemowych (`prefers-color-scheme`)

**Implementacja:**

```javascript
// frontend/src/context/ThemeContext.jsx
const ThemeContext = createContext();

export const ThemeProvider = ({ children }) => {
  const [theme, setTheme] = useState(() => {
    const saved = localStorage.getItem('theme');
    return saved || 'auto';
  });
  
  useEffect(() => {
    const root = document.documentElement;
    
    if (theme === 'auto') {
      const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      root.setAttribute('data-theme', isDark ? 'dark' : 'light');
    } else {
      root.setAttribute('data-theme', theme);
    }
    
    localStorage.setItem('theme', theme);
  }, [theme]);
  
  return (
    <ThemeContext.Provider value={{ theme, setTheme }}>
      {children}
    </ThemeContext.Provider>
  );
};
```

### Personalizacja

Użytkownik może dostosować aplikację w profilu:

- **Motyw** — jasny, ciemny, automatyczny
- **Wielkość czcionki** — mała, normalna, duża (accessibility)
- **Język interfejsu** — polski, angielski (i18n ready, obecnie tylko PL)
- **Powiadomienia** — e-mail, SMS, push (włącz/wyłącz)
- **Prywatność** — publiczny profil, ukryte ulubione

## Repozytorium Git

### Historia commitów

Projekt zawiera **ponad 100 commitów** z czytelną historią zmian.

**Konwencja commitów** — Conventional Commits:

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```


**Przykłady commitów:**

```bash
feat(auth): implement JWT refresh token rotation
fix(loans): prevent borrowing when user has overdue loans
docs(api): add Swagger documentation for reservation endpoints
refactor(frontend): extract BookCard component from BookList
test(integration): add tests for LoanRepository
perf(search): optimize full-text search with GIN index
chore(docker): update PostgreSQL to version 16
```

**Branches:**

- `main` — stable, production-ready
- `develop` — aktywny development
- `feature/*` — nowe funkcjonalności (feature/semantic-search, feature/recommendations)
- `fix/*` — naprawy błędów (fix/login-validation)
- `hotfix/*` — pilne naprawy produkcyjne


## Rozwiązywanie problemów

### Problemy z portami

**Błąd:** "Port already in use" (5173, 8000, 5432, 15672)

```powershell
# Sprawdź co zajmuje port
netstat -ano | findstr :8000
netstat -ano | findstr :5173
netstat -ano | findstr :5432

# Zatrzymaj proces lub zmień porty w config/docker-compose.yml
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

### Błędy JWT

**Błąd:** "JWT secret is not configured" lub "Invalid JWT"

```powershell
# Upewnij się, że ustawiono JWT_SECRET lub JWT_SECRETS
cd backend
Get-Content .env | Select-String "JWT_"

# Jeśli brakuje, dodaj:
# JWT_SECRET=your-strong-secret-key-here
# JWT_SECRETS=secret1,secret2,secret3
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
       allow_headers: ['*']
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
Remove-Item -Recurse -Force node_modules\.vite
npm run dev

# Docker: rebuild bez cache
docker compose build --no-cache
docker compose up -d
```

### Brak danych testowych

**Problem:** Baza danych pusta po inicjalizacji

```powershell
# Zaimportuj dane ręcznie (Docker)
docker compose exec db psql -U app -d biblioteka -f /docker-entrypoint-initdb.d/init-db-expanded-v2.sql

# LUB lokalnie
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

## Licencja

MIT License — projekt open-source dostępny dla społeczności.

---



