# 📋 PEŁNY AUDYT ARCHITEKTURY I TECHNOLOGII — Biblioteka

**Data:** 2026-02-09  
**Projekt:** Projekt i implementacja systemu zarządzania biblioteką w architekturze rozproszonej  
**Wersja audytowana:** current HEAD  
**Audytor:** GitHub Copilot (Claude Opus 4.6)

---

## CZĘŚĆ 0 — Streszczenie wykonawcze

### Kluczowe ustalenia

System biblioteczny jest **funkcjonalnie kompletny** — wszystkie 12 kategori funkcjonalnych (katalog, wypożyczenia, rezerwacje, kary, użytkownicy, powiadomienia, rekomendacje, raporty, administracja, akwizycja, architektura rozproszona, observability) jest w pełni zaimplementowanych.

Architektura rozproszona (2 mikroserwisy Python, API gateway Traefik, RabbitMQ, database-per-service, Prometheus/Grafana/Jaeger) jest poprawnie zaprojektowana i uruchomiona.

**Główne ryzyka wymagające natychmiastowej uwagi:**

| Priorytet | Znalezisko | Kategoria |
|-----------|-----------|-----------|
| 🔴 CRITICAL | Ręcznie napisana implementacja JWT (brak standardowej biblioteki) | Bezpieczeństwo |
| 🔴 CRITICAL | Placeholder secrets w `.env` (change_me) | Bezpieczeństwo |
| 🔴 CRITICAL | Podwójna rejestracja handlerów CQRS (YAML + atrybut) | Architektura |
| 🔴 CRITICAL | Brak indeksów na Loan, Reservation (krytyczne tabele) | Wydajność |
| 🔴 CRITICAL | Duplikacja Rating/Review — dwa byty dla tych samych danych | Model danych |
| 🟠 HIGH | CORS wildcard (`allow_origin: ['*']`) na /api/ | Bezpieczeństwo |
| 🟠 HIGH | Rate limiting rejestracji wyłączony (zakomentowany) | Bezpieczeństwo |
| 🟠 HIGH | Brak testów dla mikroserwisów Python | Testowanie |
| 🟠 HIGH | Brak ErrorBoundary we frontendzie — crash = biały ekran | Frontend |
| 🟠 HIGH | Dwie warstwy API we frontendzie (legacy + nowa) | Frontend |

### Metryki ogólne

| Metryka | Wartość |
|---------|---------|
| Encje Doctrine | 30 |
| Kontrolery PHP | 43 |
| Handlery CQRS (Command) | 76 |
| Handlery CQRS (Query) | 51 |
| Pliki testowe (backend) | ~199 |
| Pliki testowe (frontend) | ~73 |
| Mikroserwisy | 2 (notification, recommendation) |
| Migracje | 21 |
| Serwisy frontendowe | 18 |

---

## CZĘŚĆ I — AUDYT ARCHITEKTURY I TECHNOLOGII

---

### Sekcja 1: Stos technologiczny

| Warstwa | Technologia | Wersja |
|---------|-------------|--------|
| Backend | PHP / Symfony | 8.2.30 / 6.4 LTS |
| Frontend | React / Vite | 18.2 / 5 |
| State management | Zustand | 5 |
| Baza danych | PostgreSQL + pgvector | 16 |
| Message broker | RabbitMQ | 3.13 |
| Cache | Redis (Predis) | 7 |
| API Gateway | Traefik | v3.0 |
| Search | FOSElasticaBundle | (Elasticsearch) |
| Mikroserwisy | Python / FastAPI | 3.x |
| Observability | Prometheus + Grafana + Jaeger | 2.50 / 10.3 / 1.54 |
| CI/CD | GitHub Actions | ✅ |
| Konteneryzacja | Docker Compose | multi-stage builds |

**Werdykt:** Stos jest spójny, nowoczesny i dobrze dobrany do projektu akademickiego. Symfony 6.4 LTS zapewnia długoterminowe wsparcie. React 18 + Zustand to lekka kombinacja.

---

### Sekcja 2: Wzorce architektoniczne — CQRS & Messaging

#### 2.1 Struktura handlerów

| Lokalizacja | Ilość | Przeznaczenie |
|-------------|-------|---------------|
| `Handler\Command\` | 76 | Handlery komend (command.bus) |
| `Handler\Query\` | 45 | Handlery zapytań (query.bus) |
| `QueryHandler\` | 6 | **Legacy** — handlery zapytań w starym namespace |

#### 2.2 Znalezione problemy

##### 🔴 P-01: Podwójna rejestracja handlerów (CRITICAL)

**Opis:** 94 z 127 handlerów ma atrybut `#[AsMessageHandler]` BEZ parametru `bus:`, a jednocześnie jest tagowane w `services.yaml` dla konkretnego busa. Ponieważ `autoconfigure: true`, Symfony rejestruje je:
1. Na **domyślnym busie** (`messenger.bus.default`) przez atrybut
2. Na **docelowym busie** (`command.bus`/`query.bus`) przez YAML tag

**Wpływ:** Każdy dispatch trafia do handlera dwukrotnie (na default bus i na docelowy bus). W przypadku komend modyfikujących dane, może to powodować podwójne zapisy.

**Naprawa:** Dodać `bus: 'command.bus'` / `bus: 'query.bus'` do WSZYSTKICH atrybutów `#[AsMessageHandler]`, lub usunąć tagowanie z YAML (wybrać jedno źródło prawdy).

**Dotknięte pliki:** Wszystkie 94 handlery bez explicit `bus:` w atrybucie.

---

##### 🟡 P-02: Dwa namespace'y dla query handlerów (MEDIUM)

**Opis:** `Handler\Query\` (45 plików) i `QueryHandler\` (6 plików) współistnieją. `QueryHandler\` to stary namespace, który nie został zmigrowany.

**Dotknięte pliki:** 6 plików w `src/Application/QueryHandler/`:
- `Book/ExportBooksQueryHandler.php`
- `Dashboard/GetOverviewQueryHandler.php`
- `Recommendation/FindSimilarBooksQueryHandler.php`
- `Statistics/GetLibraryStatisticsQueryHandler.php`
- `User/GetUserByIdQueryHandler.php`
- `User/GetUserDetailsQueryHandler.php`

**Naprawa:** Przenieść do `Handler\Query\` i zaktualizować `services.yaml`.

---

##### 🟡 P-03: Brak routingu Messenger dla 6 klas (MEDIUM)

**Opis:** Następujące komendy/zapytania nie mają wpisu w `messenger.yaml`:

| Klasa | Typ | Handler istnieje? |
|-------|-----|-------------------|
| `UpdateLoanCommand` | Command | ✅ |
| `PrepareReservationCommand` | Command | ✅ |
| `ExportBooksQuery` | Query | ✅ |
| `FindSimilarBooksQuery` | Query | ✅ |
| `GetLibraryStatisticsQuery` | Query | ✅ |
| `GetUserByIdQuery` | Query | ✅ |

**Wpływ:** Dispatche trafiają na domyślny bus zamiast na docelowy.

---

##### 🟡 P-04: Handler w złym namespace (MEDIUM)

**Opis:** `PrepareReservationCommandHandler` znajduje się w `Application\Command\Reservation\` zamiast w `Handler\Command\`. Nie jest objęty tagowaniem YAML.

---

##### 🟡 P-05: Duplikacja funkcjonalna (MEDIUM)

| Domena | Handler 1 | Handler 2 | Problem |
|--------|-----------|-----------|---------|
| Dashboard | `DashboardOverviewHandler` | `GetOverviewQueryHandler` | Oba zwracają przegląd dashboardu |
| Eksport | `ExportCatalogHandler` | `ExportBooksQueryHandler` | Oba eksportują dane książek |

---

### Sekcja 3: Bezpieczeństwo

##### 🔴 S-01: Ręcznie napisana implementacja JWT (CRITICAL)

**Opis:** `JwtAuthenticator.php` i powiązany `JwtService` implementują JWT ręcznie — manualne `base64UrlEncode/Decode`, `hash_hmac('sha256')`, ręczne parsowanie JSON. Brak standardowej biblioteki (`firebase/php-jwt`, `lexik/jwt-authentication-bundle`).

**Ryzyka:**
- Tylko HS256 (symetryczny) — wyciek secret = możliwość fałszowania tokenów
- Brak claim `jti` (token ID) — tokeny nie mogą być indywidualnie unieważniane
- Brak claim `nbf` (not-before) — brak ochrony przed driftem czasu
- Metody statyczne — trudne do testowania

**Naprawa:** Zastąpić `firebase/php-jwt` lub `lexik/jwt-authentication-bundle`. Dodać `jti`, `nbf`. Użyć RS256 z parą kluczy.

---

##### 🔴 S-02: Placeholder secrets w .env (CRITICAL)

**Opis:** Plik `.env` zawiera:
```
APP_SECRET=change_me_secret
API_SECRET=change_me_api
JWT_SECRET=change_me_jwt
```

**Wpływ:** Produkcja z domyślnymi sekretami = pełne naruszenie bezpieczeństwa.

**Naprawa:** Dodać walidację przy starcie aplikacji, która odrzuca znane placeholdery. Secrets z env vars lub vault.

---

##### 🟠 S-03: CORS wildcard override (HIGH)

**Opis:** `nelmio_cors.yaml`:
```yaml
paths:
    '^/api/':
        allow_origin: ['*']
```
Nadpisuje `CORS_ALLOW_ORIGIN` env var — każda domena może wysyłać requesty do API.

**Naprawa:** Usunąć wildcard override. Ustawić `CORS_ALLOW_ORIGIN` na konkretną domenę frontendu.

---

##### 🟠 S-04: Rate limiting rejestracji wyłączony (HIGH)

**Opis:** W `RegistrationController.php` rate limiter jest zakomentowany:
```php
// Rate limiting tymczasowo wyłączone
// $limiter = $this->registrationAttemptsLimiter->create(…);
```

**Naprawa:** Odkomentować i włączyć.

---

##### 🟠 S-05: Test auth endpoint w production builds (HIGH)

**Opis:** `TestAuthController` i `public/test-auth.php` dostępne gdy `APP_ENV != 'prod'`. Ryzyko wycieku informacji o konfiguracji JWT, ścieżkach plików, stack traces.

**Naprawa:** Usunąć z production builds. Dodać do `.dockerignore`.

---

##### 🟠 S-06: API secret daje pełny admin (HIGH)

**Opis:** Każdy z `API_SECRET` header value dostaje `ROLE_ADMIN + ROLE_SYSTEM` bez audyt traila. W połączeniu z placeholder secrets = pełna eskalacja.

**Naprawa:** Ograniczyć zakres API secret do konkretnych endpointów. Zaimplementować service-to-service auth (mTLS lub OAuth2 client credentials).

---

##### 🟡 S-07: Hashowanie haseł omija Symfony hasher (MEDIUM)

**Opis:** `password_hashers: App\Entity\User: 'auto'` jest skonfigurowany, ale kod używa bezpośrednio `password_hash()` / `password_verify()` PHP zamiast `UserPasswordHasherInterface`.

**Naprawa:** Wstrzyknąć i używać `UserPasswordHasherInterface`.

---

##### 🟡 S-08: Niespójna polityka haseł (MEDIUM)

**Opis:** DTO wymaga 10 znaków (upper + lower + digit), serwis wymaga 8 znaków (litery + cyfra), handler zmiany hasła ma zakomentowaną walidację.

**Naprawa:** Scentralizować politykę haseł w jednym miejscu.

---

##### 🟡 S-09: 24h czas życia access tokena (MEDIUM)

**Opis:** `$ttl = 86400` (24h) w `JwtService`. Bez `jti` blacklist, skradziony token ważny przez cały dzień.

**Naprawa:** Skrócić do 15-30 minut. Mechanizm refresh token już istnieje.

---

##### 🟡 S-10: Nadmierne logowanie auth (MEDIUM)

**Opis:** 16+ wywołań `error_log()` w `JwtAuthenticator.php` loguje obecność tokenów, user IDs, długość nagłówka Authorization.

**Naprawa:** Usunąć `error_log()`. Użyć structured logger z odpowiednimi poziomami.

---

##### 🟡 S-11: PIN użytkownika jako plaintext (MEDIUM)

**Opis:** `User.$pin` przechowywany jako `VARCHAR(4)` bez hashowania.

**Naprawa:** Hashować lub usunąć. Jeśli to sekretny PIN, powinien być hashowany jak hasło.

---

##### 🟡 S-12: PESEL bez szyfrowania (MEDIUM / GDPR)

**Opis:** `User.$pesel` (PESEL — numer identyfikacyjny) przechowywany jako plaintext.

**Naprawa:** Zaszyfrować at rest lub pseudonimizować. Rozważyć wymogi RODO.

---

##### 🟡 S-13: Refresh nie sprawdza statusu użytkownika (MEDIUM)

**Opis:** Endpoint `/api/auth/refresh` nie weryfikuje czy użytkownik jest zablokowany/zweryfikowany przed wydaniem nowego access tokena.

**Naprawa:** Dodać sprawdzenie `blocked` i `verified` w flow odświeżania.

---

### Sekcja 4: Model danych i encje

##### 🔴 D-01: Brak indeksów na Loan (CRITICAL)

**Opis:** Encja `Loan` (jedna z najczęściej odpytywanych tabel) nie ma **żadnych** indeksów. Brak indeksów na `user_id`, `book_id`, `due_at`, `returned_at`.

**Wpływ:** Full table scan przy każdym zapytaniu o wypożyczenia użytkownika, książki, zaległe itp.

**Naprawa:** Dodać migrację z indeksami:
```php
#[ORM\Index(columns: ['user_id'], name: 'idx_loan_user')]
#[ORM\Index(columns: ['book_id'], name: 'idx_loan_book')]
#[ORM\Index(columns: ['due_at'], name: 'idx_loan_due')]
#[ORM\Index(columns: ['returned_at'], name: 'idx_loan_returned')]
```

---

##### 🔴 D-02: Brak indeksów na Reservation (CRITICAL)

**Opis:** `Reservation` nie ma żadnych indeksów. Kolumny `user_id`, `book_id`, `status`, `expires_at` są stale odpytywane.

**Naprawa:** Analogicznie jak D-01.

---

##### 🔴 D-03: Duplikacja Rating/Review (CRITICAL)

**Opis:** `Rating` ma pole `$review` (tekst) ORAZ istnieje osobna encja `Review`. Obie mają constraint `user_id + book_id` UNIQUE. Dwa miejsca do przechowywania recenzji tego samego użytkownika dla tej samej książki.

**Wpływ:** Niespójność danych — recenzja może być w `Rating.review` albo w `Review.comment`, albo w obu.

**Naprawa:** Połączyć w jedną encję, lub usunąć pole `$review` z `Rating`.

---

##### 🟠 D-04: DateTimeInterface zamiast DateTimeImmutable (HIGH)

**Opis:** Encja `Loan` ma 4 pola datetime typowane jako `\DateTimeInterface` (nie `\DateTimeImmutable`). Settery akceptują mutowalne obiekty `DateTime`.

**Dotknięte:** `Loan.php` (borrowedAt, dueAt, returnedAt, extendedAt), `Book.php` (createdAt).

**Naprawa:** Zmienić typy na `\DateTimeImmutable`.

---

##### 🟠 D-05: Brakujące indeksy na 8 encjach (HIGH)

| Encja | Brakujące indeksy |
|-------|-------------------|
| Fine | `loan_id`, `paid_at` |
| BookCopy | `status`, `book_id`, `access_type` |
| NotificationLog | `user_id`, `type`, `status`, `sent_at` |
| WeedingRecord | `book_id`, `action`, `removed_at` |
| AcquisitionOrder | `status`, `supplier_id`, `created_at` |
| User | `card_number` (unique), `membership_group`, `blocked` |
| Rating | `book_id` (osobny, poza unique constraint) |
| IntegrationConfig | `provider`, `enabled` |

---

##### 🟠 D-06: SoftDeletableTrait nieużywany (HIGH)

**Opis:** `src/Entity/Traits/SoftDeletableTrait.php` istnieje, ale **żadna encja go nie importuje**. Martwy kod.

**Naprawa:** Zastosować do encji wymagających soft delete (User, Book) lub usunąć.

---

##### 🟡 D-07: Brak updatedAt na Book i Loan (MEDIUM)

**Opis:** Encja `Book` nie ma pola `updatedAt`. Encja `Loan` również. Nie ma sposobu na śledzenie kiedy rekord był ostatnio zmodyfikowany.

**Naprawa:** Dodać `updatedAt` z lifecycle callbacks.

---

##### 🟡 D-08: 8 encji bez explicit #[ORM\Table] (MEDIUM)

**Dotknięte:** Book, Loan, Reservation, Fine, BookCopy, WeedingRecord, AcquisitionOrder, AcquisitionBudget.

**Naprawa:** Dodać `#[ORM\Table(name: '...')]` dla jawnej nazwy tabeli.

---

##### 🟡 D-09: AcquisitionBudget.adjustSpentBy() bug (MEDIUM)

**Opis:** Logika znaku w `adjustSpentBy()` podwójnie neguje wartości ujemne, bo `normalizeMoney` używa `abs()`.

---

##### 🟡 D-10: AuditLog.oldValues/newValues jako text zamiast json (MEDIUM)

**Opis:** Pola `oldValues` i `newValues` przechowywane jako `text` (prawdopodobnie JSON w stringu). Powinny mieć `type: 'json'` dla poprawnej serializacji/deserializacji.

---

### Sekcja 5: Frontend

##### 🔴 F-01: Brak ErrorBoundary (CRITICAL)

**Opis:** Zero implementacji `ErrorBoundary` w aplikacji React. Nieobsłużony błąd JavaScript w dowolnym komponencie = crash całej aplikacji z białym ekranem.

**Naprawa:** Dodać `ErrorBoundary` opakowujący `<App/>` oraz granularne boundary wokół poszczególnych stron.

---

##### 🔴 F-02: Dwie warstwy API (CRITICAL)

**Opis:** `src/api.js` (legacy, używany wszędzie) i `src/api/client.js` (nowy, z interceptorami, retry, token refresh) współistnieją. Nowy klient jest w dużej mierze martwy kod. Logika retry i token refresh nie jest efektywnie wykorzystywana.

**Naprawa:** Usunąć legacy `api.js`. Zmigrować wszystkie importy na nowy `api/client.js`.

---

##### 🔴 F-03: Auth state w dwóch systemach (CRITICAL)

**Opis:** `AuthContext.jsx` (React Context, aktywnie używany) i `authStore.js` (Zustand, nieużywany przez żaden komponent) zarządzają stanem auth niezależnie. Oba persystują tokeny do `localStorage`.

**Naprawa:** Wybrać jedno rozwiązanie (preferowane: Zustand store). Usunąć drugie.

---

##### 🟠 F-04: Brak code splitting / lazy loading (HIGH)

**Opis:** Wszystkie 14+ stron importowanych eagerly w App.jsx. Admin panel, raporty, panel bibliotekarza pobierane przez wszystkich użytkowników (w tym gości).

**Naprawa:** Użyć `React.lazy()` + `Suspense` dla route-level code splitting.

---

##### 🟠 F-05: Większość tras niechronionych (HIGH)

**Opis:** Trasy `/loans`, `/reservations`, `/favorites`, `/notifications`, `/dashboard` nie mają auth guard. Niezalogowany użytkownik może nawigować do tych stron.

**Naprawa:** Owinąć wszystkie wymagające logowania trasy w `AuthGuard`.

---

##### 🟠 F-06: Brak trasy 404 (HIGH)

**Opis:** Brak catch-all route w routerze. Nieistniejące URL wyświetlają pustą stronę.

**Naprawa:** Dodać `<Route path="*" element={<NotFound />} />`.

---

##### 🟡 F-07: console.log wycieka prefiks JWT (MEDIUM)

**Opis:** Legacy `api.js` zawiera `console.log` które logują fragmenty tokenów JWT do konsoli przeglądarki.

---

##### 🟡 F-08: window.location zamiast React Router navigate (MEDIUM)

**Opis:** `window.location.href = '...'` w kilku miejscach powoduje pełny reload strony, niszcząc stan React.

---

##### 🟡 F-09: Brak TypeScript (MEDIUM)

**Opis:** 100% JavaScript. Brak bezpieczeństwa typów, słabsze wsparcie IDE.

---

##### 🟡 F-10: ResourceCacheContext duplikuje Zustand cacheStore (MEDIUM)

**Opis:** Identyczny wzorzec jak F-03 — Context vs Zustand store dla cache.

---

### Sekcja 6: Testowanie i CI/CD

##### 🔴 T-01: Brak testów mikroserwisów Python (CRITICAL/HIGH)

**Opis:** `notification-service/` i `recommendation-service/` nie mają **żadnych** testów. Serwisy mogą cichaczem się psuć.

**Naprawa:** Dodać `pytest` dla obu serwisów. Dodać do pipeline CI.

---

##### 🟠 T-02: Brak testów integracji cross-service (HIGH)

**Opis:** Brak testów weryfikujących kontrakty między serwisami (PHP backend ↔ notification-service ↔ recommendation-service). Brak testów Pact lub CDC.

**Naprawa:** Dodać Docker Compose-based integration tests w CI.

---

##### 🟡 T-03: PHPUnit config brakuje 5 suite'ów (MEDIUM)

**Opis:** `phpunit.xml.dist` rejestruje tylko `Unit`, `Application`, `Functional`, `Controller`. Brak `Integration`, `Service`, `Entity`, `EventSubscriber`, `Performance` — testy istnieją na dysku, ale nie uruchomią się przez `--testsuite`.

**Naprawa:** Dodać brakujące `<testsuite>` do phpunit.xml.dist.

---

##### 🟡 T-04: PHPStan level mismatch CI vs local (MEDIUM)

**Opis:** CI uruchamia PHPStan z `--level=5`, lokalny `phpstan.neon` definiuje `level: 6`.

**Naprawa:** Zmienić CI na `--level=6`.

---

##### 🟡 T-05: PHP-CS-Fixer z continue-on-error (MEDIUM)

**Opis:** W CI step `php-cs-fixer` ma `continue-on-error: true` — naruszenia stylu nie blokują merge.

---

### Sekcja 7: Konfiguracja i operacje

##### 🟡 O-01: Duplikacja rate limiter configuration (MEDIUM)

**Opis:** Rate limitery zdefiniowane w DWÓCH plikach:
- `config/packages/framework.yaml` — `login_attempts` (sliding_window, 5/15min), `registration_attempts` (sliding_window, 3/1h), `api_global` (token_bucket, 200/min)
- `config/packages/rate_limiter.yaml` — `anonymous_api` (300/min), `authenticated_api` (1000/min), `login_attempts` (fixed_window, 5/15min), `registration_attempts` (fixed_window, 3/1h)

**Wpływ:** `login_attempts` zdefiniowany dwa razy z RÓŻNYMI politykami (`sliding_window` vs `fixed_window`). Symfony załaduje oba — ostatni wygra, ale jest to niebezpieczne.

**Naprawa:** Skonsolidować w jednym pliku.

---

##### 🟡 O-02: APP_ENV: prod w docker-compose dev (MEDIUM)

**Opis:** Zarówno `docker-compose.yml` (dev) jak i `docker-compose.distributed.yml` mają `APP_ENV: prod`. W środowisku deweloperskim powinno być `dev` dla lepszego debugowania.

---

##### 🟡 O-03: Brak PHP Prometheus metrics (MEDIUM)

**Opis:** Prometheus scrapuje Traefik, notification-service, recommendation-service, RabbitMQ — ale NIE PHP backend. Brak endpointu `/metrics` na backendzie.

**Naprawa:** Dodać `promphp/prometheus_client_php` lub `artprima/prometheus-metrics-bundle`.

---

##### 🟡 O-04: Dockerfiles bez HEALTHCHECK (LOW)

**Opis:** Żaden Dockerfile nie zawiera instrukcji `HEALTHCHECK`. Orchestratory nie mogą auto-healować.

---

---

## CZĘŚĆ II — AUDYT FUNKCJONALNY BIBLIOTEKI

### Macierz funkcjonalności

| # | Kategoria | Status | Szczegóły |
|---|-----------|--------|-----------|
| 1 | **Katalog i wyszukiwanie** | ✅ KOMPLETNY | Book/Author/Category CRUD, ISBN, search, filters, collections, availability, copies |
| 2 | **Wypożyczenia** | ✅ KOMPLETNY | Create/return/extend, history, overdue detection, due reminders |
| 3 | **Rezerwacje** | ✅ KOMPLETNY | Full lifecycle: create → prepare → fulfill/expire/cancel + queue |
| 4 | **Kary** | ✅ KOMPLETNY | Auto-assess overdue, manual create, pay, cancel |
| 5 | **Użytkownicy** | ✅ KOMPLETNY | Registration, JWT auth, profiles, roles, blocking, cards, permissions |
| 6 | **Powiadomienia** | ✅ KOMPLETNY | Email + in-app, due/overdue/reservation events, preferences, newsletter |
| 7 | **Rekomendacje** | ✅ KOMPLETNY | Ratings, reviews, OpenAI embeddings, pgvector similarity, feedback |
| 8 | **Raporty i statystyki** | ✅ KOMPLETNY | Usage, popular, financial, patron, inventory, dashboard, export |
| 9 | **Administracja** | ✅ KOMPLETNY | Settings, roles, integrations, audit logs, announcements, weeding, backups |
| 10 | **Akwizycja** | ✅ KOMPLETNY | Orders, suppliers, budgets, expenses, status tracking |
| 11 | **Architektura rozproszona** | ✅ KOMPLETNY | 2 mikroserwisy, Traefik, RabbitMQ, DB-per-service, event-driven |
| 12 | **Observability** | ✅ KOMPLETNY | Prometheus, Grafana, Jaeger, health endpoints |

**Werdykt: 12/12 kategorii — KOMPLETNY**

---

## CZĘŚĆ III — PODSUMOWANIE WSZYSTKICH ZNALEZISK

### Tabela zbiorcza (posortowana wg severity)

| ID | Tytuł | Severity | Kategoria | Sekcja |
|----|-------|----------|-----------|--------|
| P-01 | Podwójna rejestracja handlerów CQRS | 🔴 CRITICAL | Architektura | 2.2 |
| S-01 | Ręcznie napisana implementacja JWT | 🔴 CRITICAL | Bezpieczeństwo | 3 |
| S-02 | Placeholder secrets w .env | 🔴 CRITICAL | Bezpieczeństwo | 3 |
| D-01 | Brak indeksów na Loan | 🔴 CRITICAL | Model danych | 4 |
| D-02 | Brak indeksów na Reservation | 🔴 CRITICAL | Model danych | 4 |
| D-03 | Duplikacja Rating/Review | 🔴 CRITICAL | Model danych | 4 |
| F-01 | Brak ErrorBoundary | 🔴 CRITICAL | Frontend | 5 |
| F-02 | Dwie warstwy API (legacy + nowa) | 🔴 CRITICAL | Frontend | 5 |
| F-03 | Auth state w dwóch systemach | 🔴 CRITICAL | Frontend | 5 |
| T-01 | Brak testów mikroserwisów Python | 🔴 HIGH | Testowanie | 6 |
| S-03 | CORS wildcard override | 🟠 HIGH | Bezpieczeństwo | 3 |
| S-04 | Rate limiting rejestracji wyłączony | 🟠 HIGH | Bezpieczeństwo | 3 |
| S-05 | Test auth endpoint w production | 🟠 HIGH | Bezpieczeństwo | 3 |
| S-06 | API secret daje pełny admin | 🟠 HIGH | Bezpieczeństwo | 3 |
| D-04 | DateTimeInterface zamiast Immutable | 🟠 HIGH | Model danych | 4 |
| D-05 | Brakujące indeksy na 8 encjach | 🟠 HIGH | Model danych | 4 |
| D-06 | SoftDeletableTrait nieużywany | 🟠 HIGH | Model danych | 4 |
| F-04 | Brak code splitting | 🟠 HIGH | Frontend | 5 |
| F-05 | Większość tras niechronionych | 🟠 HIGH | Frontend | 5 |
| F-06 | Brak trasy 404 | 🟠 HIGH | Frontend | 5 |
| T-02 | Brak testów cross-service | 🟠 HIGH | Testowanie | 6 |
| P-02 | Dwa namespace'y query handlerów | 🟡 MEDIUM | Architektura | 2.2 |
| P-03 | Brak routingu Messenger dla 6 klas | 🟡 MEDIUM | Architektura | 2.2 |
| P-04 | Handler w złym namespace | 🟡 MEDIUM | Architektura | 2.2 |
| P-05 | Duplikacja funkcjonalna (dashboard, export) | 🟡 MEDIUM | Architektura | 2.2 |
| S-07 | Password hashing omija Symfony hasher | 🟡 MEDIUM | Bezpieczeństwo | 3 |
| S-08 | Niespójna polityka haseł | 🟡 MEDIUM | Bezpieczeństwo | 3 |
| S-09 | 24h access token TTL | 🟡 MEDIUM | Bezpieczeństwo | 3 |
| S-10 | Nadmierne logowanie auth | 🟡 MEDIUM | Bezpieczeństwo | 3 |
| S-11 | PIN jako plaintext | 🟡 MEDIUM | Bezpieczeństwo | 3 |
| S-12 | PESEL bez szyfrowania | 🟡 MEDIUM | Bezpieczeństwo | 3 |
| S-13 | Refresh nie sprawdza statusu user | 🟡 MEDIUM | Bezpieczeństwo | 3 |
| D-07 | Brak updatedAt na Book/Loan | 🟡 MEDIUM | Model danych | 4 |
| D-08 | Brak explicit ORM\Table na 8 encjach | 🟡 MEDIUM | Model danych | 4 |
| D-09 | Bug w AcquisitionBudget.adjustSpentBy | 🟡 MEDIUM | Model danych | 4 |
| D-10 | AuditLog text zamiast json | 🟡 MEDIUM | Model danych | 4 |
| F-07 | console.log wycieka JWT prefix | 🟡 MEDIUM | Frontend | 5 |
| F-08 | window.location zamiast navigate | 🟡 MEDIUM | Frontend | 5 |
| F-09 | Brak TypeScript | 🟡 MEDIUM | Frontend | 5 |
| F-10 | Duplikacja ResourceCache (Context vs Zustand) | 🟡 MEDIUM | Frontend | 5 |
| T-03 | PHPUnit brakuje 5 suite'ów | 🟡 MEDIUM | Testowanie | 6 |
| T-04 | PHPStan level mismatch CI vs local | 🟡 MEDIUM | Testowanie | 6 |
| T-05 | PHP-CS-Fixer continue-on-error | 🟡 MEDIUM | Testowanie | 6 |
| O-01 | Duplikacja rate limiter config | 🟡 MEDIUM | Konfiguracja | 7 |
| O-02 | APP_ENV: prod w dev compose | 🟡 MEDIUM | Konfiguracja | 7 |
| O-03 | Brak PHP Prometheus metrics | 🟡 MEDIUM | Observability | 7 |
| O-04 | Dockerfiles bez HEALTHCHECK | 🟢 LOW | Konfiguracja | 7 |

### Podsumowanie ilościowe

| Severity | Ilość |
|----------|-------|
| 🔴 CRITICAL | 9 |
| 🟠 HIGH | 12 |
| 🟡 MEDIUM | 24 |
| 🟢 LOW | 1 |
| **Razem** | **46** |

---

## CZĘŚĆ IV — MAPA DROGOWA NAPRAW

### Sprint 1 — Bezpieczeństwo krytyczne (1-2 dni)

| Zadanie | Issues |
|---------|--------|
| Dodać `bus:` parameter do wszystkich 94 handlerów `#[AsMessageHandler]` | P-01 |
| Usunąć placeholder secrets, dodać walidację przy starcie | S-02 |
| Usunąć CORS wildcard override | S-03 |
| Odkomentować rate limiting rejestracji | S-04 |
| Usunąć test auth endpoint z production builds | S-05 |

### Sprint 2 — Dane i wydajność (1-2 dni)

| Zadanie | Issues |
|---------|--------|
| Dodać migrację z indeksami na Loan, Reservation, Fine, BookCopy, NotificationLog, User, WeedingRecord, AcquisitionOrder | D-01, D-02, D-05 |
| Połączyć Rating/Review w jedną encję | D-03 |
| Zmienić DateTimeInterface → DateTimeImmutable w Loan, Book | D-04 |
| Dodać updatedAt z lifecycle callbacks do Book, Loan | D-07 |
| Naprawić AcquisitionBudget.adjustSpentBy() | D-09 |

### Sprint 3 — Frontend stabilność (2-3 dni)

| Zadanie | Issues |
|---------|--------|
| Dodać ErrorBoundary | F-01 |
| Skonsolidować API layer (usunąć legacy api.js) | F-02 |
| Skonsolidować auth state (wybrać Zustand lub Context) | F-03 |
| Dodać React.lazy + Suspense code splitting | F-04 |
| Dodać AuthGuard na chronione trasy | F-05 |
| Dodać 404 catch-all route | F-06 |
| Usunąć console.log wycieki | F-07 |

### Sprint 4 — Architektura CQRS cleanup (1 dzień)

| Zadanie | Issues |
|---------|--------|
| Przenieść QueryHandler → Handler\Query | P-02 |
| Dodać brakujący routing Messenger | P-03 |
| Przenieść PrepareReservationCommandHandler | P-04 |
| Usunąć/połączyć zduplikowane handlery dashboard/export | P-05 |

### Sprint 5 — Testowanie i CI/CD (2-3 dni)

| Zadanie | Issues |
|---------|--------|
| Dodać pytest dla notification-service i recommendation-service | T-01 |
| Dodać cross-service integration tests | T-02 |
| Dodać brakujące PHPUnit suites | T-03 |
| Wyrównać PHPStan level w CI | T-04 |

### Sprint 6 — Hardening (ongoing)

| Zadanie | Issues |
|---------|--------|
| Zastąpić ręczny JWT biblioteką (firebase/php-jwt) | S-01 |
| Użyć UserPasswordHasherInterface | S-07 |
| Skrócić access token TTL | S-09 |
| Dodać sprawdzenie statusu user w refresh | S-13 |
| Hash PIN, szyfrować PESEL | S-11, S-12 |
| Usunąć/zastosować SoftDeletableTrait | D-06 |
| Dodać PHP Prometheus metrics | O-03 |
| Skonsolidować rate limiter config | O-01 |

---

## CZĘŚĆ V — MOCNE STRONY PROJEKTU ✅

Dla balansu — projekt ma wiele silnych stron:

1. **Funkcjonalna kompletność** — 12/12 kategorii bibliotecznych w pełni zaimplementowanych
2. **Doskonała architektura testów** — 199 plików testowych backend, 73 frontend, pełna izolacja (SQLite, sync transport, null mailer)
3. **Solidny pipeline CI/CD** — Multi-version matrix (PHP 8.2/8.3, Node 18/20), Codecov, Trivy security scan
4. **Prawdziwa architektura rozproszona** — 3 oddzielne bazy danych, event-driven integration, Traefik API gateway, observability stack
5. **Spójna warstwa serwisowa frontendu** — 18 serwisów z jednolitym wzorcem
6. **Dobrze zaprojektowane custom hooks** — useDataFetching z cache, usePagination, useFilters
7. **Comprehensive RBAC** — hierarchia ról, permission-based guards, feature flags
8. **AuditLog jest wzorcowo zaindeksowany** — powinien być wzorem dla pozostałych encji
9. **Docker multi-stage builds** — builder → production, osobno frontend (nginx:alpine), dobre cachowanie warstw
10. **Feature flag system** — env-driven z guard komponentem i hookiem

---

*Koniec audytu.*
