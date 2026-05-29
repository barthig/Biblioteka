# Reproducibility

Ten katalog opisuje minimalne kroki odtworzenia środowiska developerskiego z czystego checkoutu repozytorium.

## 1. Konfiguracja środowiska

```bash
cp .env.example .env
```

Edytuj `.env` i zmień wartości oznaczone jako `change_me`. Nie używaj prawdziwych sekretów produkcyjnych w lokalnym środowisku.

## 2. Uruchomienie usług

```bash
docker compose up --build -d
```

Podstawowe adresy po starcie:

- aplikacja: `http://localhost`
- API docs: `http://localhost/api/docs`
- Traefik dashboard: `http://localhost:8080`
- RabbitMQ management: `http://localhost:15672`

## 3. Migracje i seed danych

Po uruchomieniu kontenerów wykonaj migracje:

```bash
docker exec backend php bin/console doctrine:migrations:migrate --no-interaction
```

Załaduj fixtures dla środowiska dev:

```bash
docker exec backend php bin/console doctrine:fixtures:load --no-interaction --env=dev
```

Jeżeli nazwa kontenera ma prefiks projektu Compose, sprawdź ją poleceniem:

```bash
docker compose ps
```

## 4. Klucze JWT

Jeżeli lokalna konfiguracja wymaga pary kluczy JWT, wygeneruj ją w kontenerze backendu:

```bash
docker compose exec backend mkdir -p config/jwt
docker compose exec backend openssl genrsa -out config/jwt/private.pem 4096
docker compose exec backend openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```

W przypadku konfiguracji opartej o `JWT_SECRET` wystarczy ustawić lokalną wartość w `.env`.

## 5. Przykładowe konta dev

Po załadowaniu fixtures użyj przykładowych kont developerskich:

- czytelnik: `user01@example.com` / `password123`
- bibliotekarz: `librarian@example.com` / `password123`
- administrator: `admin@example.com` / `password123`
