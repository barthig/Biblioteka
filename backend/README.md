# Biblioteka — Backend (Symfony)

Katalog `backend/` zawiera szkic aplikacji Symfony z Doctrine. Pliki tu dodane to scaffold — wymagane jest lokalne zainstalowanie zależności i wygenerowanie migracji.

Szybkie uruchomienie (PowerShell):

```powershell
# uruchom Postgres (root repo)
docker-compose up -d

# w folderze backend
cd backend

# zainstaluj zależności (composer musi być zainstalowany)
# composer install

# skopiuj .env.example -> .env.local i dopasuj DATABASE_URL

# wygeneruj migracje i uruchom migracje
# php bin/console doctrine:migrations:diff
# php bin/console doctrine:migrations:migrate

# załaduj fixtures (opcjonalnie)
# php bin/console doctrine:fixtures:load

# uruchom serwer
# symfony server:start --no-tls --port=8000
```

Endpoints (przykładowe):

- GET /api/books
- GET /api/books/{id}
- POST /api/books
- PUT /api/books/{id}
- DELETE /api/books/{id}

Uwaga: to scaffold — uruchomienie wymaga Composer i Symfony CLI lub uruchomienia przez built-in server.
