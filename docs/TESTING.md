# Testing Guide

## Backend (PHPUnit)

Test configuration is defined in `backend/phpunit.xml.dist`.

Main suites:
- `tests/Unit` - isolated unit tests for services, value objects and helpers.
- `tests/Application` - CQRS command/query handler tests.
- `tests/Functional` - HTTP/API tests using Symfony WebTestCase.
- `tests/EventSubscriber` - event subscriber tests.
- `tests/Service` - business service and Messenger handler tests.

Run examples:

```bash
cd backend
php vendor/bin/phpunit --testsuite Unit
php vendor/bin/phpunit --testsuite Application
php vendor/bin/phpunit --testsuite Functional
php vendor/bin/phpunit
```

## Frontend (Vitest + Vite build)

Run examples:

```bash
cd frontend
npm test -- --run
npm run build
```

This split matches the diploma requirements: backend unit tests are in `tests/Unit`, while CQRS and async notification flow coverage is expanded in `tests/Application`, `tests/EventSubscriber` and `tests/Service`.
