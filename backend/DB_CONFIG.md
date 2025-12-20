Database Configuration

This project uses PostgreSQL (pgvector image) via Docker Compose. The values
below are the default development settings used by docker-compose.yml.

Connection (Docker network)
- Host: db
- Port: 5432
- Database: biblioteka_dev
- User: biblioteka
- Password: biblioteka_secure_2024

DATABASE_URL (backend container)
- postgresql://biblioteka:biblioteka_secure_2024@db:5432/biblioteka_dev?serverVersion=16&charset=utf8

Connection (host machine)
- Host: 127.0.0.1
- Port: 5432
- Database: biblioteka_dev
- User: biblioteka
- Password: biblioteka_secure_2024

Seed data
- Initial schema + demo data: backend/init-db-expanded-v2.sql
- Applied automatically when the database volume is empty (docker-compose.yml).
