# Biblioteka - Full Docker Setup

## Uruchamianie

### Development (z hot-reload)
```bash
docker compose -f docker-compose.dev.yml up -d
```

- Frontend: http://localhost:5173
- Backend API: http://localhost:8000
- RabbitMQ Management: http://localhost:15672 (user: app, pass: app)
- PostgreSQL: localhost:5432

### Production
```bash
docker compose up -d --build
```

- Frontend: http://localhost:3000
- Backend API: http://localhost:8000
- RabbitMQ Management: http://localhost:15672

## Komendy PHP przez Docker

### Konsola Symfony
```bash
docker compose exec backend php bin/console <komenda>
```

### Migracje
```bash
# Uruchom migracje
docker compose exec backend php bin/console doctrine:migrations:migrate

# Stwórz nową migrację
docker compose exec backend php bin/console doctrine:migrations:diff
```

### Composer
```bash
docker compose exec backend composer install
docker compose exec backend composer require <package>
```

### Powłoka w kontenerze
```bash
# Backend
docker compose exec backend bash

# PHP Worker
docker compose exec php-worker bash

# Frontend (dev)
docker compose -f docker-compose.dev.yml exec frontend sh
```

## Struktura

```
docker/
├── backend/          # PHP-FPM + Nginx (production)
│   ├── Dockerfile
│   ├── nginx.conf
│   ├── default.conf
│   └── supervisord.conf
├── frontend/         # Node build + Nginx (production)
│   ├── Dockerfile
│   └── nginx.conf
└── php-worker/       # PHP CLI dla Messenger
    └── Dockerfile
```

## Pierwsze uruchomienie

```bash
# Development
docker compose -f docker-compose.dev.yml up -d
docker compose -f docker-compose.dev.yml exec backend composer install
docker compose -f docker-compose.dev.yml exec backend php bin/console doctrine:migrations:migrate

# Production
docker compose up -d --build
docker compose exec backend php bin/console doctrine:migrations:migrate
```
