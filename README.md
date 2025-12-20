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
