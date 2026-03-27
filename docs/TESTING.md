# Przewodnik testowania

Dokument opisuje, gdzie znajdują się testy i jak je uruchamiać lokalnie.

## Backend (PHPUnit)

Konfiguracja testów backendu: [backend/phpunit.xml.dist](backend/phpunit.xml.dist).

Najważniejsze katalogi testowe:

- `backend/tests/Unit` - testy jednostkowe,
- `backend/tests/Application` - testy warstwy CQRS,
- `backend/tests/Functional` - testy HTTP/API,
- `backend/tests/EventSubscriber` - testy subskrybentów zdarzeń,
- `backend/tests/Service` - testy usług i handlerów.

Uruchamianie:

```bash
cd backend
php vendor/bin/phpunit --testsuite Unit
php vendor/bin/phpunit --testsuite Application
php vendor/bin/phpunit --testsuite Functional
php vendor/bin/phpunit
```

Dodatkowa kontrola jakości:

```bash
cd backend
vendor/bin/phpstan analyse src --level=6
```

## Frontend (Vitest i Playwright)

Uruchamianie:

```bash
cd frontend
npm ci
npm run lint
npm run test:run
npm run test:coverage
npm run test:e2e
npm run build
```

## Testy integracyjne architektury rozproszonej

Skrypty smoke/integration:

- [tests/integration/test_cross_service.sh](tests/integration/test_cross_service.sh)
- [tests/integration/test_gateway_routing.sh](tests/integration/test_gateway_routing.sh)
- [tests/integration/test_standalone_stack.sh](tests/integration/test_standalone_stack.sh)

Przykład uruchomienia po starcie kontenerów:

```bash
./tests/integration/test_cross_service.sh
./tests/integration/test_gateway_routing.sh
```

Co weryfikują:

- zdrowie usług i dostępność metryk,
- dostępność API publicznego przez bramę,
- poprawność routingu konfliktowych ścieżek backend/mikroserwis.

## Klienci API do testów ręcznych

Gotowe pliki do Postmana i Insomnii znajdziesz w [docs/api-clients](docs/api-clients).

## Artefakty do dokumentacji projektu

Na potrzeby pracy dyplomowej repozytorium zawiera dodatkowe materialy projektowe:

- diagramy C4 w `docs/c4`,
- diagram bazy danych w `docs/database-diagram.puml`,
- diagram przypadkow uzycia i diagramy sekwencji w `docs/uml`,
- instrukcje uzytkownika w `docs/USER_GUIDE.md`,
- instrukcje administratora w `docs/ADMIN_GUIDE.md`.
