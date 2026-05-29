# API

Ten dokument opisuje podstawowe endpointy HTTP dla szybkiej weryfikacji API systemu Biblioteka. Domyślny adres w quickstarcie Docker Compose to `http://localhost`, a backend jest wystawiony przez Traefika pod prefiksem `/api`.

## Główne endpointy

- `POST /api/auth/login` - logowanie użytkownika i pobranie tokenu JWT.
- `POST /api/auth/refresh` - odświeżenie tokenu dostępowego.
- `GET /api/books` - lista książek; endpoint publiczny w typowej konfiguracji.
- `GET /api/books/{id}` - szczegóły książki.
- `POST /api/loans` - utworzenie wypożyczenia; wymaga nagłówka `Authorization: Bearer <token>`.
- `GET /api/me/loans` - lista wypożyczeń zalogowanego użytkownika.

## Przykłady curl

Pobranie tokenu JWT:

```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user01@example.com","password":"password123"}'
```

Jeżeli odpowiedź zawiera pole `accessToken`, zapisz je w zmiennej:

```bash
export JWT_TOKEN="wklej_accessToken_z_odpowiedzi"
```

Użycie tokenu przy żądaniu autoryzowanym:

```bash
curl http://localhost/api/me/loans \
  -H "Authorization: Bearer ${JWT_TOKEN}"
```

Przykładowe pobranie katalogu książek:

```bash
curl "http://localhost/api/books?page=1&limit=10"
```

Przykładowe utworzenie wypożyczenia:

```bash
curl -X POST http://localhost/api/loans \
  -H "Authorization: Bearer ${JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"bookId":1}'
```

## Swagger / OpenAPI

Interaktywna dokumentacja Swagger UI jest dostępna pod adresem `http://localhost/api/docs`. Specyfikację OpenAPI w formacie JSON można pobrać z `http://localhost/api/docs.json`.
