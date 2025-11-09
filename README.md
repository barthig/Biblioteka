# Biblioteka

Kompleksowa aplikacja webowa umożliwiająca zarządzanie zasobami biblioteki: katalogiem książek, kontami czytelników oraz procesem wypożyczeń i zwrotów. Warstwa backend powstała w Symfony 6 (PHP 8.2) i udostępnia REST API zabezpieczone JWT oraz sekretem API, frontend to React 18 uruchamiany w środowisku Vite.

---

## Spis treści

1. Opis projektu
2. Technologie i uzasadnienie
3. Architektura rozwiązania
4. Wymagania wstępne
5. Konfiguracja środowiska
6. Uruchomienie aplikacji
7. Zarządzanie danymi (migracje, fixtures)
8. Konta testowe
9. Dostęp do API i autoryzacja
10. Testy i kontrola jakości
11. Zgodność z wymaganiami projektu
12. Rozwiązywanie problemów
13. Przydatne linki

---

## 1. Opis projektu

Aplikacja realizuje pełny cykl życia książki: od dodania do katalogu, przez przypisanie autora i kategorii, po obsługę wypożyczeń oraz zwrotów. Zapewnia proces logowania i autoryzacji ról, a interfejs React dynamicznie komunikuje się z API i prezentuje aktualne stany zasobów.

Kluczowe cechy:
- Dwuwarstwowa architektura (backend REST + frontend SPA).
- Baza relacyjna w 3NF z ponad 30 rekordami startowymi.
- Zarządzanie egzemplarzami (`BookCopy`), rezerwacjami kolejkowymi oraz karami finansowymi.
- JWT oraz `X-API-SECRET` zabezpieczające zasoby API.
- Testy jednostkowe i funkcjonalne (PHPUnit) oraz budowanie frontendu (Vite).

---

## 2. Technologie i uzasadnienie

### Backend
- **Symfony 6 / PHP 8.2** – dojrzały framework MVC z bogatym ekosystemem oraz wysoką produktywnością przy tworzeniu API.
- **Doctrine ORM** – mapowanie encji na relacyjną bazę danych, migracje i repozytoria.
- **Autorski JwtService** – generowanie oraz walidacja tokenów JWT w algorytmie HS256.
- **Doctrine Fixtures** – szybkie ładowanie danych demonstracyjnych (autorzy, kategorie, książki, egzemplarze, rezerwacje, kary).

### Frontend
- **React 18 + Vite** – szybkie środowisko deweloperskie i możliwość tworzenia komponentowego SPA.
- **React Router** – obsługa trasowania po stronie klienta.
- **Fetch API** – komunikacja z backendem oraz obsługa tokenów JWT.

### Infrastruktura
- **PostgreSQL 15 (Docker Compose)** – wydajna relacyjna baza danych dostępna lokalnie w kontenerze.
- **Composer / npm** – zarządzanie zależnościami backendu i frontendu.
- **PHPUnit, ESLint (planowane)** – kontrola jakości kodu.

---

## 3. Architektura rozwiązania

- **Warstwa API** – kontrolery Symfony (`backend/src/Controller`) wystawiają zasoby książek, egzemplarzy, rezerwacji, kar, wypożyczeń i autoryzacji.
- **Warstwa logiki biznesowej** – serwisy (np. `BookService`) pilnują zasad dostępności egzemplarzy, rezerwacji i limitów wypożyczeń.
- **Warstwa danych** – encje Doctrine (`Author`, `Book`, `BookCopy`, `Category`, `Loan`, `Reservation`, `Fine`, `User`) oraz repozytoria dedykowane zapytaniom.
- **Frontend** – kontekst autoryzacji (`AuthContext`), strony katalogu książek i wypożyczeń, komponenty prezentujące szczegóły.
- **Zabezpieczenia** – `ApiAuthSubscriber` wymusza obecność tokena JWT lub sekretu API dla wszystkich tras `/api/*` poza wyjątkami.

Szczegółowe diagramy i dodatkowe materiały przechowywane są w katalogu `docs/`.

---

## 4. Wymagania wstępne

- PHP 8.2 z rozszerzeniami: `ctype`, `iconv`, `intl`, `pdo_pgsql`.
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
| `MESSENGER_TRANSPORT_DSN` | Połączenie do brokera RabbitMQ | `amqp://guest:guest@localhost:5672/%2f/messages` |
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
   php bin/console messenger:consume async
   ```

   Wysyłane rezerwacje trafiają do kolejki RabbitMQ i są zapisywane w `var/log/reservation_queue.log`.

8. Interfejs deweloperski React będzie dostępny pod `http://127.0.0.1:5173`. Zaloguj się kontem z sekcji 8.

### 6.2. Backend w trybie standalone (np. testy API)

- Po wykonaniu kroków 1–5 możesz korzystać z API wyłącznie z narzędzia typu Postman/HTTPie.
- Pamiętaj o ustawieniu w żądaniach nagłówka `Authorization: Bearer <token>` lub `X-API-SECRET`.

### 6.3. Budowanie produkcyjne

- Backend: `php bin/console cache:clear --env=prod`, konfiguracja serwera (Nginx/Apache) wskazująca katalog `backend/public`.
- Frontend: `npm run build` tworzy statyczne pliki w `frontend/dist/` – gotowe do umieszczenia na serwerze HTTP lub w CDN.

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
- Publiczne endpointy: `POST /api/auth/login`, `GET /api/health`, `GET /health`, wszystkie zapytania `OPTIONS`.
- Weryfikacja tokena i sekretu realizowana jest w `backend/src/EventSubscriber/ApiAuthSubscriber.php`.
- Rezerwacje: `GET /api/reservations`, `POST /api/reservations`, `DELETE /api/reservations/{id}` – zarządzanie kolejką oczekujących na egzemplarze.
- Kary: `GET /api/fines`, `POST /api/fines/{id}/pay` – przegląd i opłacanie kar powiązanych z wypożyczeniami.
- Dokumentacja OpenAPI: `GET /api/docs` (UI) oraz `GET /api/docs.json` (specyfikacja JSON przygotowana przez NelmioApiDocBundle).

---

## 10. Testy i kontrola jakości

- Testy jednostkowe/funkcjonalne: `cd backend`, `vendor\bin\phpunit`.
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
