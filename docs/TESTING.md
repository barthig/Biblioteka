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

## Distributed architecture smoke tests

For the standalone Docker development stack that uses the real `init-db-expanded-v2.sql`, use:

```bash
./tests/integration/test_standalone_stack.sh
```

Purpose:
- `test_standalone_stack.sh` verifies the real Docker startup path, seeded login flow, refresh token flow and the key dashboard/public endpoints that previously failed because of schema drift.

When the distributed stack is running through Traefik, use:

```bash
./tests/integration/test_cross_service.sh
./tests/integration/test_gateway_routing.sh
```

Purpose:
- `test_cross_service.sh` verifies health, metrics and public API availability through the gateway.
- `test_gateway_routing.sh` verifies that collision-prone paths are routed to the correct owner behind Traefik.
