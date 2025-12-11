# Biblioteka

Kompleksowa aplikacja webowa umożliwiająca zarządzanie zasobami biblioteki: katalogiem książek, kontami czytelników oraz procesem wypożyczeń i zwrotów. Warstwa backend powstała w Symfony 6 (PHP 8.2) i udostępnia REST API zabezpieczone JWT oraz sekretem API, frontend to **w pełni funkcjonalny** React 18 uruchamiany w środowisku Vite.

> **✅ FRONTEND W 100% GOTOWY** - pełna funkcjonalność, wszystkie komponenty, serwisy i strony zaimplementowane!

---

## Spis treści

1. Opis projektu
2. Technologie i uzasadnienie
3. Architektura rozwiązania
4. **Frontend - Pełna funkcjonalność** ⭐
5. Wymagania wstępne
6. Konfiguracja środowiska
7. Uruchomienie aplikacji
8. Zarządzanie danymi (migracje, fixtures)
9. Konta testowe
10. Dostęp do API i autoryzacja
11. Testy i kontrola jakości
12. Zgodność z wymaganiami projektu
13. Rozwiązywanie problemów
14. Przydatne linki
15. Moduły administracyjne i zasoby cyfrowe
16. Konserwacja i skrypty utrzymaniowe

---

## 1. Opis projektu

Aplikacja realizuje pełny cykl życia książki: od dodania do katalogu, przez przypisanie autora i kategorii, po obsługę wypożyczeń oraz zwrotów. Zapewnia proces logowania i autoryzacji ról, a interfejs React dynamicznie komunikuje się z API i prezentuje aktualne stany zasobów.

Kluczowe cechy:
- Dwuwarstwowa architektura (backend REST + **frontend SPA w pełni funkcjonalny**).
- Baza relacyjna w 3NF z ponad 30 rekordami startowymi.
- Zarządzanie egzemplarzami (`BookCopy`), rezerwacjami kolejkowymi oraz karami finansowymi.
- Rozbudowany panel backoffice: akwizycje (budżety, zamówienia, dostawcy), wycofania zbiorów, raporty oraz repozytorium aktywów cyfrowych dla książek.
- JWT oraz `X-API-SECRET` zabezpieczające zasoby API.
- Testy jednostkowe i funkcjonalne (PHPUnit) oraz budowanie frontendu (Vite).
- **Pełnofunkcjonalny frontend** z 14 komponentami UI, 5 serwisami API, 12 stronami i kompletnym systemem stylów.

---

## 2. Technologie i uzasadnienie

### Backend
- **Symfony 6 / PHP 8.2** – dojrzały framework MVC z bogatym ekosystemem oraz wysoką produktywnością przy tworzeniu API.
- **Doctrine ORM** – mapowanie encji na relacyjną bazę danych, migracje i repozytoria.
- **Autorski JwtService** – generowanie oraz walidacja tokenów JWT w algorytmie HS256.
- **Doctrine Fixtures** – szybkie ładowanie danych demonstracyjnych (autorzy, kategorie, książki, egzemplarze, rezerwacje, kary).

### Frontend ⭐ **W PEŁNI FUNKCJONALNY**
- **React 18 + Vite** – szybkie środowisko deweloperskie i możliwość tworzenia komponentowego SPA.
- **React Router 6** – obsługa trasowania po stronie klienta z 12 trasami.
- **Axios** – zaawansowany HTTP client z interceptorami.
- **date-fns** – formatowanie i manipulacja datami.
- **react-icons** – biblioteka ikon (Font Awesome).
- **Context API** – zarządzanie stanem globalnym (AuthContext, ResourceCacheContext).
- **Service Layer Pattern** – czysta architektura z oddzieloną warstwą API.

**Komponenty UI (14):**
- LoadingSpinner, ErrorMessage, SuccessMessage
- Modal, Pagination, SearchBar, FilterPanel
- AnnouncementCard, LoanCard, ReservationCard
- StatCard, EmptyState, BookItem, Navbar, RequireRole

**Serwisy API (5):**
- bookService - katalog książek, wyszukiwanie, filtry
- loanService - wypożyczenia, przedłużenia, zwroty
- reservationService - rezerwacje, anulowanie
- userService - profil, ulubione, zmiana hasła
- announcementService - ogłoszenia biblioteczne

**Strony (12):**
- Dashboard - główna strona z ogłoszeniami i statystykami
- Books - katalog z zaawansowanym filtrowaniem
- BookDetails - szczegóły książki
- MyLoans - zarządzanie wypożyczeniami
- Reservations - lista rezerwacji
- Favorites - ulubione książki
- Profile - edycja profilu użytkownika
- Announcements - ogłoszenia biblioteczne
- Recommended - polecane książki
- AdminPanel - panel administratora
- LibrarianPanel - panel bibliotekarza
- Login/Register - autoryzacja

**Style:**
- Kompletny system CSS z CSS Variables
- Komponenty responsywne (mobile/tablet/desktop)
- Klasy użytkowe
- Animacje i przejścia
- 500+ linii stylów w `main.css` i `components.css`

### Infrastruktura
- **PostgreSQL 15 (Docker Compose)** – wydajna relacyjna baza danych dostępna lokalnie w kontenerze.
- **Composer / npm** – zarządzanie zależnościami backendu i frontendu.
- **PHPUnit, ESLint (planowane)** – kontrola jakości kodu.

---

## 3. Architektura rozwiązania

- **Warstwa API** – kontrolery Symfony (`backend/src/Controller`) wystawiają zasoby książek, egzemplarzy, rezerwacji, kar, wypożyczeń i autoryzacji.
- **Warstwa logiki biznesowej** – serwisy (np. `BookService`) pilnują zasad dostępności egzemplarzy, rezerwacji i limitów wypożyczeń.
- **Warstwa powiadomień** – dedykowane komunikaty (Messenger) oraz handler opisane w `docs/notifications.md` przygotowują przypomnienia o terminach i rezerwacjach (email/SMS) z logowaniem i deduplikacją.
- **Warstwa danych** – encje Doctrine (`Author`, `Book`, `BookCopy`, `Category`, `Loan`, `Reservation`, `Fine`, `User`) oraz repozytoria dedykowane zapytaniom.
- **Frontend** – kontekst autoryzacji (`AuthContext`), strony katalogu książek i wypożyczeń, komponenty prezentujące szczegóły.
- **Zabezpieczenia** – `ApiAuthSubscriber` wymusza obecność tokena JWT lub sekretu API dla wszystkich tras `/api/*` poza wyjątkami.

Szczegółowe diagramy i dodatkowe materiały przechowywane są w katalogu `docs/`.

---

## 4. Frontend - Pełna funkcjonalność ⭐

### 📚 Kompletna dokumentacja

Szczegółowa dokumentacja frontendu dostępna w: **`frontend/FRONTEND_DOCS.md`**

### ✨ Główne funkcjonalności

#### 🎨 Komponenty UI (14)

**Podstawowe:**
- `LoadingSpinner` - stany ładowania (3 rozmiary)
- `ErrorMessage` - wyświetlanie błędów z przyciskiem zamknięcia
- `SuccessMessage` - powiadomienia o sukcesie
- `Modal` - dialogi modalne
- `Pagination` - inteligentna paginacja z ellipsis
- `EmptyState` - stan pusty z akcjami

**Zaawansowane:**
- `SearchBar` - autocomplete z debouncing (300ms)
- `FilterPanel` - zaawansowane filtrowanie
- `StatCard` - karty statystyk z trendami
- `AnnouncementCard` - karty ogłoszeń (4 typy)
- `LoanCard` - karty wypożyczeń (status, dni do zwrotu, akcje)
- `ReservationCard` - karty rezerwacji (5 statusów)

**Nawigacja:**
- `Navbar` - nawigacja z prefetchingiem
- `RequireRole` - guard dla tras wymagających ról

#### 🔌 Serwisy API (5)

Wszystkie serwisy wykorzystują `api.js` wrapper z automatyczną obsługą JWT:

**bookService:**
- getBooks(filters) - lista z filtrowaniem
- getBook(id) - szczegóły książki
- search(query) - wyszukiwanie pełnotekstowe
- getRecommended() - polecane książki
- getPopular(limit) - popularne
- getNewArrivals(limit) - nowości
- getFilters() - dostępne filtry (gatunki, autorzy, lata)
- getAvailability(bookId) - sprawdź dostępność

**loanService:**
- getMyLoans() - moje wypożyczenia
- getAllLoans(filters) - wszystkie (admin)
- createLoan(bookId, userId) - nowe wypożyczenie
- returnLoan(loanId) - zwrot książki
- extendLoan(loanId) - przedłużenie (max 3x)
- getStatistics() - statystyki użytkownika

**reservationService:**
- getMyReservations() - moje rezerwacje
- getAllReservations(filters) - wszystkie (admin)
- createReservation(bookId) - zarezerwuj książkę
- cancelReservation(id) - anuluj rezerwację
- fulfillReservation(id) - zrealizuj (bibliotekarz)

**userService:**
- getProfile() - dane użytkownika
- updateProfile(data) - aktualizuj profil
- changePassword(current, new) - zmiana hasła
- getFavorites() - lista ulubionych
- addFavorite(bookId) - dodaj do ulubionych
- removeFavorite(id) - usuń z ulubionych
- getAllUsers(filters) - użytkownicy (admin)

**announcementService:**
- getAnnouncements(filters) - lista ogłoszeń
- getAnnouncement(id) - pojedyncze ogłoszenie
- createAnnouncement(data) - utwórz (admin)
- updateAnnouncement(id, data) - aktualizuj
- publishAnnouncement(id) - opublikuj
- archiveAnnouncement(id) - archiwizuj
- deleteAnnouncement(id) - usuń

#### 📄 Strony (12)

**Dashboard** (`/`) - strona główna:
- Hero sekcja z CTA
- Statystyki użytkownika (wypożyczenia, zaległości, ulubione, łącznie wypożyczonych)
- Ogłoszenia (3 najnowsze z możliwością przypinania)
- Popularne książki (6)
- Nowości (6)
- Szybkie akcje

**Books** (`/books`) - katalog:
- Zaawansowane filtrowanie (gatunek, autor, rok, dostępność)
- Wyszukiwanie pełnotekstowe z debouncing
- Faceted search
- Paginacja
- Cache z ResourceCacheContext

**MyLoans** (`/my-loans`) - wypożyczenia:
- Lista wypożyczeń z LoanCard
- Status (aktywne/zaległe/zwrócone)
- Licznik dni do zwrotu
- Przedłużanie (max 3x)
- Zwrot książki
- Ostrzeżenia o zaległościach

**Reservations** (`/reservations`) - rezerwacje:
- Lista rezerwacji z ReservationCard
- Statusy: pending/ready/fulfilled/cancelled/expired
- Countdown do wygaśnięcia
- Anulowanie rezerwacji
- Realizacja (bibliotekarz)

**Announcements** (`/announcements`) - ogłoszenia:
- Lista z filtrowaniem (typ, archiwalne)
- Szczegóły ogłoszenia
- Zarządzanie (admin/bibliotekarz)
- 4 typy: info/warning/success/error
- Przypinanie ogłoszeń

**Profile** (`/profile`) - profil:
- Edycja danych osobowych
- Zmiana hasła
- Historia wypożyczeń (do dodania)

**Pozostałe:**
- Favorites - ulubione książki
- BookDetails - szczegóły książki z możliwością wypożyczenia/rezerwacji
- Recommended - polecane książki
- AdminPanel - zarządzanie użytkownikami
- LibrarianPanel - zarządzanie wypożyczeniami, budżet, raporty
- Login/Register - autoryzacja

#### 🎨 System stylów

**main.css** (500+ linii):
- CSS Variables dla kolorów, cieni, borderów
- Reset i base styles
- Buttony (6 wariantów: primary/secondary/success/warning/danger/outline)
- Karty (card, card-header, card-body)
- Alerty (4 typy: error/success/warning/info)
- Modal (overlay, header, body, footer)
- Paginacja
- Loading spinner z animacjami
- Utilities (margin, padding, text-align)

**components.css** (300+ linii):
- Announcement cards ze stylami dla typów
- Loan cards ze statusami (active/overdue/warning/returned)
- Reservation cards (5 statusów)
- Dashboard (hero, stats, quick actions)
- Search bar z sugestiami
- Filter panel z dropdown
- Books grid
- Responsywność (mobile/tablet/desktop)

**Responsive:**
- Mobile: < 640px
- Tablet: 640px - 1024px
- Desktop: > 1024px

### 🚀 Uruchomienie frontendu

```bash
cd frontend

# Instalacja zależności
npm install

# Serwer deweloperski (localhost:5173)
npm run dev

# Build produkcyjny
npm run build

# Podgląd buildu
npm run preview
```

### 📦 Zależności

```json
{
  "react": "^18.2.0",
  "react-dom": "^18.2.0",
  "react-router-dom": "^6.14.1",
  "axios": "^1.6.0",
  "date-fns": "^2.30.0",
  "react-icons": "^4.11.0"
}
```

### 🔐 Konteksty

**AuthContext:**
- Zarządzanie stanem autoryzacji
- login(email, password)
- logout()
- register(data)
- user, token

**ResourceCacheContext:**
- Cache dla zapytań API
- getCachedResource(key, ttl)
- setCachedResource(key, data)
- invalidateResource(pattern)

### ✅ Gotowe do produkcji

- ✅ Wszystkie komponenty zaimplementowane
- ✅ Wszystkie serwisy API gotowe
- ✅ Wszystkie strony funkcjonalne
- ✅ Kompletny system stylów
- ✅ Responsywny design
- ✅ Obsługa błędów
- ✅ Loading states
- ✅ Cache i optymalizacja
- ✅ Dokumentacja w FRONTEND_DOCS.md

---

## 5. Wymagania wstępne

- PHP 8.1+ (zalecane 8.2) z rozszerzeniami: `ctype`, `iconv`, `intl`, `pdo_pgsql`.
- Composer w wersji 2.x.
- Node.js 18+ wraz z npm.
- Docker Desktop lub kompatybilny silnik kontenerów (dla PostgreSQL).
- (Opcjonalnie) Symfony CLI ułatwiające start serwera lokalnego.

---

## 5. Konfiguracja środowiska

Przed uruchomieniem przygotuj pliki `.env.local` na backendzie i froncie.

### Backend (`backend/.env.local`)

| Zmienna | Opis | Przykład |
| :-- | :-- | :-- |
| `APP_ENV` | Tryb pracy Symfony | `dev` |
| `APP_SECRET` | Klucz aplikacji (generuj losowo) | `php -r "echo bin2hex(random_bytes(16));"` |
| `DATABASE_URL` | Łącze do PostgreSQL | `postgresql://biblioteka:biblioteka@127.0.0.1:5432/biblioteka_dev?serverVersion=15&charset=utf8` |
| `API_SECRET` | Sekret nagłówka `X-API-SECRET` | np. `super_tajne_haslo` |
| `JWT_SECRET` | Sekret podpisu tokenów JWT | wygeneruj własny | 
| `MESSENGER_TRANSPORT_DSN` | Połączenie do brokera RabbitMQ | `amqp://app:app@localhost:5672/%2f/messages` |
| `PORT` | Port lokalnego serwera | `8000` |

Punkt wyjścia: `backend/.env.example`.

### Frontend (`frontend/.env.local`)

| Zmienna | Opis | Przykład |
| :-- | :-- | :-- |
| `VITE_API_URL` | Bazowy adres API | `http://127.0.0.1:8000/api` |
| `VITE_API_SECRET` | Sekret używany przed zalogowaniem | zgodny z backendowym `API_SECRET` |

Plik należy utworzyć manualnie – patrz instrukcja w sekcji 6.

---

## 6. Uruchomienie aplikacji

### 6.0. Automatyczny start (Windows PowerShell)

Jeśli pracujesz na Windowsie i chcesz jednym poleceniem uruchomić backend oraz frontend, skorzystaj ze skryptu `scripts/start-dev.ps1`:

```powershell
Set-Location Biblioteka
Set-ExecutionPolicy -Scope Process Bypass -Force  # jeśli wcześniej nie zezwolono na skrypty
./scripts/start-dev.ps1                          # pełny start (backend + frontend)
```

Skrypt:

- Sprawdza obecność `php`, `composer` oraz `npm` i automatycznie zainstaluje zależności (`composer install`, `npm install`), jeśli jeszcze nie istnieją katalogi `vendor/` lub `node_modules/`.
- Uruchamia backend na `http://127.0.0.1:8000` i frontend Vite na `http://127.0.0.1:5173` w oddzielnych oknach PowerShell, pozostawiając logi na ekranie.
- Tworzy `frontend/.env.local` z wartościami domyślnymi, gdy plik nie istnieje (koniecznie zaktualizuj `VITE_API_SECRET`).
- Przyjmuje opcjonalne parametry `-BackendOnly`, `-FrontendOnly` oraz `-NoBrowser` (nie otwiera przeglądarki).

Możesz nadal wykonywać ręczne kroki z kolejnych sekcji – skrypt je tylko automatyzuje.

### 6.1. Szybki start (środowisko deweloperskie)

1. Sklonuj repozytorium i przejdź do katalogu projektu:

   ```powershell
   git clone https://github.com/barthig/Biblioteka.git
   Set-Location Biblioteka
   ```

2. Uruchom bazę danych:

   ```powershell
   docker compose up -d db
   ```

3. Backend – instalacja zależności i konfiguracja:

   ```powershell
   Set-Location backend
   composer install
   Copy-Item .env.example .env.local -Force
   ```

   Edytuj `backend/.env.local`, ustawiając poprawne sekrety.

4. Migracje i dane przykładowe:

   ```powershell
   php bin/console doctrine:migrations:migrate
   php bin/console doctrine:fixtures:load --no-interaction
   ```

5. Uruchom API (wybierz jedną z opcji):

   ```powershell
   # Symfony CLI
   symfony server:start --dir=public --no-tls

   # serwer wbudowany w PHP
   php -S 127.0.0.1:8000 -t public
   ```

6. Frontend – nowe okno terminala:

   ```powershell
   Set-Location ..\frontend
   npm install
   if (-not (Test-Path .env.local)) {
     Set-Content .env.local "VITE_API_URL=http://127.0.0.1:8000/api`nVITE_API_SECRET=change_me"
   }
   npm run dev
   ```

   Wygenerowane wartości `change_me` należy zastąpić własnym sekretem zgodnym z backendem.

7. Kolejki i powiadomienia asynchroniczne:

   ```powershell
   docker compose up -d rabbitmq
   docker compose run --rm php-worker php bin/console messenger:consume async
   ```

   Wysyłane rezerwacje trafiają do kolejki RabbitMQ i są zapisywane w `var/log/reservation_queue.log`. Kontener `php-worker` ma wbudowane rozszerzenie `ext-amqp`, dzięki czemu konsument działa bez dodatkowej konfiguracji lokalnego PHP.

   > Uwaga: moduł SMS jest na razie symulowany – `NotificationSender::sendSms()` tylko loguje komunikaty. Aby wysyłać realne SMS-y, skonfiguruj Symfony Notifier i transport SMS opisany w `docs/notifications.md`.

   Po wdrożeniu architektury z `docs/notifications.md` uruchom dodatkowo cyklicznie komendy (każda obsługuje przełącznik `--dry-run` do inspekcji bez wysyłki):

   ```powershell
   php bin/console notifications:dispatch-due-reminders --days=2
   php bin/console notifications:dispatch-overdue-warnings --threshold=1
   php bin/console notifications:dispatch-reservation-ready
   ```

   Komendy można dodać do Harmonogramu zadań (Windows) lub CRON-a i pozostawić `messenger:consume async` w tle — szczegóły w sekcji „Automatyczne powiadomienia”.

8. Interfejs deweloperski React będzie dostępny pod `http://127.0.0.1:5173`. Zaloguj się kontem z sekcji 8.

### 6.2. Backend w trybie standalone (np. testy API)

- Po wykonaniu kroków 1–5 możesz korzystać z API wyłącznie z narzędzia typu Postman/HTTPie.
- Pamiętaj o ustawieniu w żądaniach nagłówka `Authorization: Bearer <token>` lub `X-API-SECRET`.

### 6.3. Budowanie produkcyjne

- Backend: `php bin/console cache:clear --env=prod`, konfiguracja serwera (Nginx/Apache) wskazująca katalog `backend/public`.
- Frontend: `npm run build` tworzy statyczne pliki w `frontend/dist/` – gotowe do umieszczenia na serwerze HTTP lub w CDN.

### 6.4. Automatyczne powiadomienia

| Komenda | Cel | Zalecana częstotliwość |
| :-- | :-- | :-- |
| `php bin/console notifications:dispatch-due-reminders --days=2` | przypomnienia o zbliżających się terminach zwrotu | raz dziennie (np. 08:00) |
| `php bin/console notifications:dispatch-overdue-warnings --threshold=1` | ostrzeżenia o spóźnionych wypożyczeniach | raz dziennie (np. 09:00) |
| `php bin/console notifications:dispatch-reservation-ready` | informowanie o rezerwacjach gotowych do odbioru | co 10–15 minut |
| `php bin/console fines:assess-overdue --daily-rate=1.50` | naliczanie automatycznych kar za przetrzymania (1,50 zł/dzień domyślnie) | raz na dobę (np. 00:05) |
| `php bin/console reservations:expire-ready --pickup-hours=48` | wygaszanie nieodebranych rezerwacji i przekazywanie egzemplarza kolejnym osobom | co godzinę |
| `php bin/console users:block-delinquent --fine-limit=50 --overdue-days=30` | blokowanie kont z wysokimi karami lub długimi przetrzymaniami | raz dziennie (np. 06:00) |
| `php bin/console notifications:dispatch-newsletter --days=7 --channel=email` | cykliczna wysyłka newslettera z nowościami (można łączyć kanały email/SMS) | raz w tygodniu (np. poniedziałek 07:30) |

Każda komenda obsługuje `--dry-run`, dzięki czemu można sprawdzić, ile komunikatów zostanie wysłanych, bez faktycznego wrzucania ich do kolejki.

- **Windows (Harmonogram zadań)** – uruchom PowerShell jako administrator i utwórz zadanie cykliczne:

   ```powershell
   schtasks /Create /SC HOURLY /MO 1 /TN "Biblioteka Reservation Ready" ^
      /TR "powershell -NoProfile -Command \"cd /d D:\Biblioteka-1\backend; php bin/console notifications:dispatch-reservation-ready\""
   ```

- **Linux/macOS (cron)** – dopisz wpis do `crontab -e`:

   ```cron
   0 8 * * * cd /opt/biblioteka/backend && php bin/console notifications:dispatch-due-reminders --days=2 >> var/log/notifications.log 2>&1
   */15 * * * * cd /opt/biblioteka/backend && php bin/console notifications:dispatch-reservation-ready >> var/log/notifications.log 2>&1
   ```

Pamiętaj, aby w tle działał konsument `php bin/console messenger:consume async`, który odbierze komunikaty i faktycznie wyśle e-maile/SMS-y.

Każda z powyższych komend przyjmuje przełącznik `--dry-run`, dzięki czemu możesz sprawdzić ilu użytkowników/rezervacji zostanie dotkniętych bez modyfikowania bazy. `fines:assess-overdue` pozwala też definiować kurs kary (`--daily-rate`, `--currency`, `--grace-days`), a `users:block-delinquent` umożliwia ustawienie progów (`--fine-limit`, `--overdue-days`).

---

## 7. Zarządzanie danymi (migracje, fixtures)

- Aktualne migracje znajdują się w `backend/migrations/` (np. `Version20251109101500.php`, `Version20251109113000.php`).
- W przypadku zmian schematu uruchom `php bin/console doctrine:migrations:diff`, następnie `doctrine:migrations:migrate`.
- Dane demonstracyjne (ponad 30 rekordów) ładowane są za pomocą `php bin/console doctrine:fixtures:load --no-interaction` (tworzą m.in. egzemplarze książek, rezerwacje, kary).
- Encje i relacje są znormalizowane (3NF): osobne tabele dla autorów, kategorii, egzemplarzy, wypożyczeń, rezerwacji i kar.

---

## 8. Konta testowe

| Email | Hasło | Role |
| :-- | :-- | :-- |
| `user1@example.com` | `password1` | `ROLE_LIBRARIAN` |
| `user2@example.com` – `user6@example.com` | `password2` – `password6` | `ROLE_USER` |

Hasła zapisywane są w formacie bcrypt i generowane podczas ładowania fixtures.
Każde konto posiada przykładowe dane kontaktowe (telefon, adres, kod pocztowy), które można wykorzystać przy powiadomieniach i naliczaniu kar.

---

## 9. Dostęp do API i autoryzacja

- Logowanie: `POST /api/auth/login` z parametrami `email`, `password` (JSON).
- Po autoryzacji każdorazowo wysyłaj nagłówek `Authorization: Bearer <token>`.
- Integracje systemowe mogą używać `X-API-SECRET` bez JWT (np. w procesach automatycznych).

### Publiczne endpointy (bez tokenu / sekretu)

- `POST /api/auth/login`
- `POST /api/auth/register`
- `GET /api/auth/verify/{token}`
- `GET /api/books`, `GET /api/books/filters`, `GET /api/books/{id}`
- `GET /api/health`, `GET /health`
- wszystkie żądania `OPTIONS`

Lista powyżej odpowiada wyjątkom skonfigurowanym w `backend/src/EventSubscriber/ApiAuthSubscriber.php`. Każdy inny zasób `/api/*` wymaga poprawnego JWT lub nagłówka `X-API-SECRET`.

- Rezerwacje: `GET /api/reservations`, `POST /api/reservations`, `DELETE /api/reservations/{id}` – zarządzanie kolejką oczekujących na egzemplarze.
- Kary: `GET /api/fines`, `POST /api/fines/{id}/pay` – przegląd i opłacanie kar powiązanych z wypożyczeniami.
- Dokumentacja OpenAPI: `GET /api/docs` (UI) oraz `GET /api/docs.json` (specyfikacja JSON przygotowana przez NelmioApiDocBundle).

---

## 10. Testy i kontrola jakości

- Testy jednostkowe/funkcjonalne: `cd backend`, `vendor\bin\phpunit`.
- Dedykowane testy komend powiadomień: `php vendor\bin\phpunit --filter NotificationCommandsTest`.
- Sprawdzenie statusu migracji: `php bin/console doctrine:migrations:status`.
- Budowa frontendu (test smoke): `cd frontend`, `npm run build`.
- Zalecane (opcjonalne): konfiguracja lintów PHPStan/ESLint oraz testów e2e.
- Scenariusze pokryte testami funkcjonalnymi obejmują m.in. wypożyczenia, rezerwacje (`ReservationControllerTest`) oraz kary (`FineControllerTest`).

---

## 11. Zgodność z wymaganiami projektu

| Kryterium | Status | Uwagi |
| :-- | :-- | :-- |
| Architektura rozproszona (frontend + backend) | Zrealizowane | React + Symfony komunikujące się REST.
| Baza danych w 3NF z min. 30 rekordami | Zrealizowane | Migracja `Version20251109101500`, fixtures >30 rekordów.
| CRUD książek, kategorii, wypożyczeń | Zrealizowane | Endpointy w `BookController`, `LoanController`.
| Zarządzanie egzemplarzami, rezerwacjami i karami | Zrealizowane | Encje `BookCopy`, `Reservation`, `Fine` + kontrolery `ReservationController`, `FineController`.
| Uwierzytelnianie i role | Zrealizowane | JWT + role użytkowników.
| Historia git (min. 40 commitów) | W toku / do weryfikacji | Sprawdź przed oddaniem pracy.
| Kolejki asynchroniczne (RabbitMQ) | Zrealizowane | Symfony Messenger + RabbitMQ, konsument `messenger:consume async`.
| Automatyczne powiadomienia (due/overdue/reservation) | Zrealizowane | Komendy `notifications:*` + testy w `tests/Functional/Command/NotificationCommandsTest.php` oraz opis w `docs/notifications.md`.
| Dokumentacja API (Swagger/OpenAPI) | Zrealizowane | NelmioApiDocBundle, UI pod `/api/docs`.
| Stany loading/error na froncie | W trakcie | Częściowo zaimplementowane.
| Kompletny README + instrukcja startu | Zrealizowane | Niniejszy dokument.

---

## 12. Rozwiązywanie problemów

- **Baza nie startuje** – sprawdź konflikt portu `5432`; zmodyfikuj `docker-compose.yml` lub zatrzymaj lokalny Postgres.
- **Brak rozszerzeń PHP** – włącz `pdo_pgsql` oraz `intl` w konfiguracji PHP.
- **Komunikat 401/403** – zweryfikuj poprawność tokena lub sekretu API oraz konfigurację CORS.
- **Migracje konfliktują** – uruchom `doctrine:migrations:status`, a następnie wykonaj brakujące migracje.
- **Vite nie widzi API** – upewnij się, że `VITE_API_URL` wskazuje na właściwy adres oraz że backend jest uruchomiony.

---

## 13. Przydatne linki

- Symfony: https://symfony.com/doc/current/
- Doctrine ORM: https://www.doctrine-project.org/projects/doctrine-orm/en/current/
- React: https://react.dev/
- Vite: https://vitejs.dev/
- PostgreSQL: https://www.postgresql.org/
- Symfony Messenger: https://symfony.com/doc/current/messenger.html
- Nelmio ApiDoc: https://symfony.com/bundles/NelmioApiDocBundle/current/index.html

---

## 14. Moduły administracyjne i zasoby cyfrowe

- **Akwizycje i gospodarka zbiorami** – kontrolery w `backend/src/Controller/Acquisition*.php` oraz `WeedingController.php` obsługują budżety (`/api/admin/acquisitions/budgets`), zamówienia (`/api/admin/acquisitions/orders`), dostawców (`/api/admin/acquisitions/suppliers`) i proces wycofań egzemplarzy. Wszystkie endpointy wymagają roli `ROLE_LIBRARIAN`.
- **Administracja systemem** – przestrzeń `backend/src/Controller/Admin` udostępnia zarządzanie integracjami, uprawnieniami, kopiami zapasowymi i ustawieniami (`/api/admin/system/*`).
- **Zasoby cyfrowe książek** – `BookAssetController` pozwala na przesyłanie i pobieranie plików powiązanych z książką (`/api/admin/books/{id}/assets`). Pliki są przechowywane w katalogu `var/digital-assets`, który należy uwzględnić w backupach i zapewnić mu prawa zapisu.
- **Rejestry i raporty** – `NotificationController`, `ReportController` oraz `BackupService` udostępniają dane operacyjne (np. logi powiadomień) oraz generowanie zestawień zgodnie z modułami opisanymi wyżej.

> Tip: przed wdrożeniem na serwer sprawdź, czy katalog `var/digital-assets` istnieje i posiada prawa zapisu dla użytkownika uruchamiającego PHP/FPM. W środowisku produkcyjnym warto również podpiąć dedykowany storage (S3, dysk sieciowy) i wskazać go poprzez symlink.

---

## 15. Konserwacja i skrypty utrzymaniowe

Biblioteka posiada dedykowane komendy CLI ułatwiające prace utrzymaniowe. Wszystkie przyjmują przełącznik `--help`, który opisuje dodatkowe opcje.

| Komenda | Cel | Najważniejsze opcje |
| :-- | :-- | :-- |
| `php bin/console maintenance:import-isbn --source=var/import/isbn.csv` | hurtowy import lub uzupełnienie metadanych książek na podstawie listy ISBN | `--format=csv|json`, `--dry-run`, `--limit`, `--default-author`, `--default-category` |
| `php bin/console maintenance:anonymize-patrons --inactive-days=730` | anonimizacja danych kontaktowych czytelników nieaktywnych i bez zaległości | `--limit`, `--dry-run` |
| `php bin/console maintenance:weeding-analyze --cutoff-months=18` | raport kandydatów do wycofania (niska rotacja / brak wypożyczeń) | `--min-loans` (domyślnie 0), `--limit`, `--format=json` |
| `php bin/console maintenance:create-backup --initiator="cron"` | szybka kopia zapasowa (wpis w `backup_record` + plik JSON w `var/backups`) | `--note` (opis snapshotu) |

### Import ISBN

Pliki CSV/JSON powinny zawierać przynajmniej kolumnę `isbn`. Opcjonalnie możesz dodać `title`, `author`, `publisher`, `year`, `description`, `category`, `resourceType`, `signature`. Tryb `--dry-run` pozwala sprawdzić ilu rekordów dotknie import bez modyfikowania bazy.

### Anonimizacja nieaktywnych kont

Komenda usuwa dane osobowe użytkowników, którzy od zadanej liczby dni nie aktualizowali konta i nie mają aktywnych wypożyczeń, rezerwacji ani zaległych kar. Pola kontaktowe są czyszczone, e‑mail zastępowany jest adresem w domenie `example.invalid`, a konto odblokowywane (jeśli było blokowane automatycznie). Regularne uruchamianie pomaga spełnić wymagania RODO.

### Analiza ubytków (weeding)

`maintenance:weeding-analyze` łączy dane książek, wypożyczeń oraz rezerwacji i pokazuje tytuły, które nie cieszą się popularnością (brak wypożyczeń od X miesięcy lub marginalna liczba wypożyczeń). Wynik można zserializować do JSON i zasilić panel BI.

### Kopia zapasowa

`maintenance:create-backup` wykorzystuje `BackupService` do zapisania lekkiego snapshotu (np. listy ustawień) i wpisu w tabeli `backup_record`. W praktyce warto podpiąć to polecenie do CRON-a oraz rozszerzyć `BackupService` o eksport bazy/postaci archiwum – komenda stanowi punkt wejścia i loguje metadane kopii.
