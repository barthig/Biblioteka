# Biblioteka

Library management system with a Symfony 6.4 backend and a React 18 frontend.

## Project overview
- Catalog management: books, authors, categories, copies.
- Loans and reservations with limits and statuses.
- Fines and notifications.
- Admin and librarian modules.
- Async jobs via RabbitMQ (Symfony Messenger).
- REST API with OpenAPI/Swagger via Nelmio.

## Tech stack
Backend:
- PHP 8.2, Symfony 6.4, Doctrine ORM
- PostgreSQL 16
- RabbitMQ (Messenger)

Frontend:
- React 18, Vite

## Run locally (Docker)
1. Copy environment example and set secrets if needed:
   ```powershell
   Copy-Item .env.example .env
   ```
2. Start services:
   ```powershell
   docker compose up -d
   ```
3. Access:
   - Frontend: http://localhost:3000
   - Backend: http://localhost:8000
   - API docs: http://localhost:8000/api/docs

Database is seeded from `backend/init-db-expanded-v2.sql`.

## Database normalization (3NF)
- Core entities are normalized and related via join tables (e.g., books/authors/categories).
- The `book` table includes derived counters (`copies`, `total_copies`, `storage_copies`, `open_stack_copies`) for read performance.
  The source of truth is `book_copy`; these fields are treated as cached aggregates updated by the application.
  If strict 3NF is required, remove the counters from `book` and compute them from `book_copy` (or use a view/materialized view).

## Run locally (manual)
Backend:
```powershell
cd backend
composer install
php -S 127.0.0.1:8000 -t public
```

Frontend:
```powershell
cd frontend
npm install
npm run dev
```

Set `VITE_API_URL` in `frontend/.env` to point to the backend (default: http://127.0.0.1:8000).

## Authentication
- JWT for users.
- API secret header `X-API-SECRET` for system integrations.

## Tests
Backend:
```powershell
cd backend
vendor/bin/phpunit
```

Frontend:
```powershell
cd frontend
npm test
```

## Repository structure
- `backend/` Symfony API
- `frontend/` React app
- `docker-compose.yml` local stack
- `backend/schema_current.sql` current database schema

## Notes
- OpenAPI UI available at `/api/docs`.
- Async worker: `php bin/console messenger:consume async`.
