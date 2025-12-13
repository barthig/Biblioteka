# Lista kontrolna sprawności systemu bibliotecznego

Użyj poniższych kroków, aby potwierdzić, że API działa stabilnie, ma łączność z bazą danych oraz obsługuje kluczowe procesy (wypożyczenia, zwroty, rezerwacje, opłaty).

## 1. Podstawowy healthcheck z kontrolą bazy
- Wywołaj `GET /health` (bez nagłówka `X-API-SECRET`).
- Oczekuj odpowiedzi `200 OK` z `status: "ok"` i sekcją `checks.database: "ok"`, co potwierdza poprawne połączenie z bazą.

## 2. Walidacja schematu i migracji
- Sprawdź spójność schematu z encjami: `php bin/console doctrine:schema:validate`.
- Upewnij się, że migracje mogą zostać zastosowane: `php bin/console doctrine:migrations:migrate --dry-run`.

## 3. Integralność danych i dane startowe
- Jeżeli środowisko potrzebuje danych przykładowych, załaduj je: `php bin/console doctrine:fixtures:load --env=dev --append` (lub `--env=test` w środowisku testowym).
- Po załadowaniu danych wykonaj prosty odczyt, np. `php bin/console doctrine:query:sql "SELECT COUNT(*) FROM book"`, aby potwierdzić komunikację z bazą.

## 4. Kolejki i zadania asynchroniczne
- Ustaw zmienną `MESSENGER_TRANSPORT_DSN` (np. do brokera AMQP/Redis) i uruchom konsumenta: `php bin/console messenger:consume async --limit=10 --time-limit=30 --memory-limit=128M`.
- Sprawdź, czy wiadomości związane z rezerwacjami i powiadomieniami (np. `ReservationQueuedNotification`, `LoanDueReminderMessage`) są odbierane bez błędów.

## 5. Testy funkcjonalne krytycznych ścieżek
- Uruchom pakiet testów funkcjonalnych pokrywających wypożyczenia, zwroty, rezerwacje i opłaty: 
  ```bash
  php vendor/bin/phpunit --display-warnings \
    tests/Functional/LoanControllerTest.php \
    tests/Functional/FineControllerTest.php \
    tests/Functional/ReservationControllerTest.php \
    tests/Functional/UserJourneyTest.php
  ```
- Wszystkie testy powinny przejść na zielono; błędy wskażą brakujące uprawnienia, problemy z dostępnością egzemplarzy lub niespójność danych.

## 6. Manualne sprawdzenie kluczowych endpointów
- **Wypożyczenie**: `POST /api/loans` z prawidłowym `userId` i `bookId` zwraca `201` i zmniejsza liczbę dostępnych egzemplarzy.
- **Zwrot**: `PUT /api/loans/{id}/return` zwraca `200` i zwiększa liczbę dostępnych egzemplarzy.
- **Przedłużenie**: `PUT /api/loans/{id}/extend` z dozwolonym okresem zwraca `200`.
- **Rezerwacja**: `POST /api/reservations` zwraca `201` i blokuje egzemplarz dla użytkownika; anulowanie `DELETE /api/reservations/{id}` zwraca `200`.
- **Opłaty**: `POST /api/fines/{id}/pay` (lub odpowiedni endpoint płatności) zwraca `200` i zmienia status opłaty na rozliczoną.

Jeżeli którykolwiek z kroków kończy się błędem lub niespójnością, najpierw sprawdź konfigurację środowiska (.env, DSN do bazy/kolejek), a następnie logi aplikacji, aby ustalić źródło problemu.
