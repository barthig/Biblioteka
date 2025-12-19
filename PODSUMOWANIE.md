# Podsumowanie projektu Biblioteka

## Opis
Projekt to kompletny system biblioteczny z podzia?em na backend (Symfony 6.4, PHP 8.2) i frontend (React 18 + Vite). Aplikacja obs?uguje katalog ksi??ek, wypo?yczenia, rezerwacje, kary, konta u?ytkownik?w i modu?y administracyjne. Komunikacja odbywa si? przez REST API, a asynchroniczne zadania realizuje Symfony Messenger z RabbitMQ. Dane przechowywane s? w PostgreSQL (pgvector). Frontend dostarcza responsywny interfejs SPA.

## Architektura w skr?cie
- Backend: Symfony 6.4, Doctrine ORM, JWT + X-API-SECRET, Messenger, RabbitMQ
- Frontend: React 18, Vite, React Router, serwisy API
- Baza: PostgreSQL 16 (pgvector)
- DevOps: Docker Compose (db, redis, rabbitmq, backend, worker, frontend, nginx)

## Audyt techniczny (automatyczny)
Audyt oparty o uruchomione testy i weryfikacj? konfiguracji.

### Wyniki test?w
- Backend (PHPUnit): 332 testy, OK, ale 3 oznaczone jako Incomplete. Wyst?puj? ostrze?enia o deprecacjach Symfony.
- Frontend (Vitest): 15 plik?w testowych, 63 testy OK, 2 pomini?te.
- E2E (Playwright): aktualnie zablokowane przez brak zale?no?ci systemowych w kontenerze `node:20-alpine` (brak `apt-get`). Wymaga uruchamiania e2e w obrazie Playwright lub zmiany bazy na Debian/Ubuntu.

### Wykryte i naprawione problemy
- Brak `symfony/http-client` powodowa? b??d autowiringu w testach backendu.
- Testy backendu wywraca?y si? na typie `vector` (pgvector) ? dodano inicjalizacj? rozszerzenia i mapowanie typu.
- Dev Docker: brak instalacji zale?no?ci backendu i niesp?jne `APP_ENV/APP_DEBUG`.
- Prod Docker: brak aktywnego vhosta Nginx (brak symlinku w `sites-enabled`).

### Otwarte ryzyka / uwagi
- E2E: wymagaj? kontenera z systemem wspieraj?cym `apt-get` lub dedykowanego obrazu Playwright.
- Deprecacje Symfony: nie blokuj? dzia?ania, ale warto zaplanowa? aktualizacj? konfiguracji walidatora i OptionsResolver.
- 3 testy Incomplete w backendzie ? wymagaj? decyzji, czy maj? zosta? uko?czone, czy wy??czone.

## Stan uruchomienia
- Docker Compose dev dzia?a: backend, frontend, db, redis, rabbitmq, nginx, worker.
- Frontend: http://localhost:5173
- Backend: http://localhost:8000

## Rekomendacje
1. Ustali? docelowy spos?b uruchamiania test?w E2E (Playwright image lub zmiana obrazu node).
2. Doko?czy? testy Incomplete w backendzie.
3. Rozwa?y? ograniczenie log?w deprecated w testach (np. przez konfiguracj? PHPUnit).
