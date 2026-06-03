# Dokumentacja API Smart Library

Dokument opisuje najważniejsze endpointy HTTP systemu Smart Library oraz przykładowe wywołania do ręcznej weryfikacji API. Pełna dokumentacja kontraktu jest generowana jako OpenAPI/Swagger.

## 1. Adres bazowy

W konfiguracji Docker Compose API jest wystawiane przez bramę Traefik:

```text
http://localhost/api
```

Bezpośredni adres backendu w środowisku developerskim:

```text
http://localhost:8000/api
```

Swagger UI:

```text
http://localhost/api/docs
```

Specyfikacja OpenAPI JSON:

```text
http://localhost/api/docs.json
```

## 2. Format komunikacji

API przyjmuje i zwraca dane głównie w formacie JSON.

Typowe nagłówki:

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer <accessToken>
```

Endpointy publiczne, takie jak katalog książek lub ogłoszenia, nie wymagają tokenu. Operacje użytkownika, bibliotekarza i administratora wymagają autoryzacji JWT.

## 3. Autoryzacja

### Logowanie

```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user01@example.com","password":"password123"}'
```

Przykładowa odpowiedź zawiera token dostępowy:

```json
{
  "accessToken": "jwt-token",
  "refreshToken": "refresh-token",
  "user": {
    "id": 1,
    "email": "user01@example.com",
    "roles": ["ROLE_USER"]
  }
}
```

Zapis tokenu do zmiennej środowiskowej:

```bash
export JWT_TOKEN="wklej_accessToken"
```

W PowerShell:

```powershell
$env:JWT_TOKEN="wklej_accessToken"
```

### Odświeżenie tokenu

```bash
curl -X POST http://localhost/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refreshToken":"wklej_refreshToken"}'
```

### Wylogowanie

Jeżeli endpoint wylogowania jest dostępny w danej konfiguracji:

```bash
curl -X POST http://localhost/api/auth/logout \
  -H "Authorization: Bearer ${JWT_TOKEN}"
```

## 4. Katalog książek

### Lista książek

```bash
curl "http://localhost/api/books?page=1&limit=10"
```

### Wyszukiwanie

```bash
curl "http://localhost/api/books?q=wiedzmin&page=1&limit=10"
```

### Filtrowanie

Przykładowe parametry:

- `q` - fraza,
- `authorId` - identyfikator autora,
- `categoryId` - identyfikator kategorii,
- `publisher` - wydawca,
- `resourceType` - typ zasobu,
- `yearFrom` - rok od,
- `yearTo` - rok do,
- `available=true` - tylko dostępne pozycje.

Przykład:

```bash
curl "http://localhost/api/books?q=robot&available=true&page=1&limit=20"
```

### Szczegóły książki

```bash
curl http://localhost/api/books/1
```

### Metadane filtrów

```bash
curl http://localhost/api/books/filters
```

## 5. Wypożyczenia

### Utworzenie wypożyczenia

```bash
curl -X POST http://localhost/api/loans \
  -H "Authorization: Bearer ${JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"bookId":1}'
```

Opcjonalnie można podać konkretny egzemplarz:

```bash
curl -X POST http://localhost/api/loans \
  -H "Authorization: Bearer ${JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"bookId":1,"bookCopyId":10}'
```

System sprawdza:

- limit wypożyczeń,
- blokadę konta,
- zaległe opłaty,
- dostępność egzemplarza,
- aktywną rezerwację.

### Wypożyczenia zalogowanego użytkownika

```bash
curl http://localhost/api/me/loans \
  -H "Authorization: Bearer ${JWT_TOKEN}"
```

### Zwrot książki

Endpoint zwrotu może być dostępny dla bibliotekarza lub administratora:

```bash
curl -X POST http://localhost/api/loans/1/return \
  -H "Authorization: Bearer ${JWT_TOKEN}"
```

Przy zwrocie po terminie system może naliczyć opłatę regulaminową.

## 6. Rezerwacje

### Utworzenie rezerwacji

```bash
curl -X POST http://localhost/api/reservations \
  -H "Authorization: Bearer ${JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"bookId":1,"expiresInDays":7}'
```

Rezerwacja jest możliwa, gdy książka nie ma aktualnie dostępnego egzemplarza.

### Lista rezerwacji użytkownika

```bash
curl http://localhost/api/reservations \
  -H "Authorization: Bearer ${JWT_TOKEN}"
```

### Anulowanie rezerwacji

```bash
curl -X DELETE http://localhost/api/reservations/1 \
  -H "Authorization: Bearer ${JWT_TOKEN}"
```

## 7. Oceny i opinie

### Dodanie oceny

```bash
curl -X POST http://localhost/api/books/1/rating \
  -H "Authorization: Bearer ${JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"rating":5}'
```

### Dodanie opinii

```bash
curl -X POST http://localhost/api/books/1/reviews \
  -H "Authorization: Bearer ${JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"rating":5,"comment":"Bardzo dobra książka."}'
```

Po zapisaniu oceny lub opinii użytkownik otrzymuje powiadomienie in-app.

## 8. Powiadomienia

### Lista powiadomień

```bash
curl http://localhost/api/notifications \
  -H "Authorization: Bearer ${JWT_TOKEN}"
```

Powiadomienia obejmują między innymi:

- wypożyczenie książki,
- zwrot książki,
- rezerwację,
- przygotowanie rezerwacji do odbioru,
- dodanie oceny,
- dodanie opinii,
- powitanie po utworzeniu konta,
- nowe ogłoszenia,
- nowe wydarzenia.

### Testowe powiadomienie

Jeżeli endpoint testowy jest włączony:

```bash
curl -X POST http://localhost/api/notifications/test \
  -H "Authorization: Bearer ${JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"message":"Test powiadomienia"}'
```

## 9. Ogłoszenia i wydarzenia

### Lista ogłoszeń

```bash
curl "http://localhost/api/announcements?homepage=true&limit=20"
```

### Ogłoszenia publiczne na stronie głównej

Endpoint może być wykorzystywany przez frontend przed zalogowaniem. Jeżeli odpowiedź jest pusta, należy sprawdzić, czy ogłoszenia są aktywne i oznaczone jako widoczne na stronie głównej.

## 10. Akcesje i administracja

Endpointy administracyjne wymagają roli bibliotekarza lub administratora.

### Budżety akcesji

```bash
curl http://localhost/api/admin/acquisitions/budgets \
  -H "Authorization: Bearer ${JWT_TOKEN}"
```

### Dodanie budżetu

```bash
curl -X POST http://localhost/api/admin/acquisitions/budgets \
  -H "Authorization: Bearer ${JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"name":"Zakupy 2026","fiscalYear":"2026","allocatedAmount":1000,"currency":"PLN"}'
```

### Dodanie wydatku do budżetu

```bash
curl -X POST http://localhost/api/admin/acquisitions/budgets/1/expenses \
  -H "Authorization: Bearer ${JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"amount":50,"description":"Transport"}'
```

Typ ręcznego wydatku jest domyślnie traktowany jako `MISC`.

### Dostawcy

```bash
curl http://localhost/api/admin/acquisitions/suppliers \
  -H "Authorization: Bearer ${JWT_TOKEN}"
```

### Zamówienia

```bash
curl http://localhost/api/admin/acquisitions/orders \
  -H "Authorization: Bearer ${JWT_TOKEN}"
```

## 11. Rekomendacje

### Rekomendacje dla zalogowanego użytkownika

```bash
curl http://localhost/api/recommendations/personal \
  -H "Authorization: Bearer ${JWT_TOKEN}"
```

### Podobne książki

```bash
curl http://localhost/api/recommendations/similar/1
```

## 12. Kody odpowiedzi HTTP

Najczęściej spotykane kody:

- `200 OK` - operacja zakończona poprawnie,
- `201 Created` - utworzono zasób,
- `400 Bad Request` - niepoprawne dane wejściowe,
- `401 Unauthorized` - brak lub nieważny token,
- `403 Forbidden` - brak uprawnień,
- `404 Not Found` - zasób nie istnieje,
- `409 Conflict` - konflikt stanu, np. duplikat lub niedozwolona operacja,
- `422 Unprocessable Entity` - dane nie przeszły walidacji,
- `500 Internal Server Error` - błąd serwera,
- `502 Bad Gateway` - problem z bramą lub niedostępny backend.

## 13. Testowanie ręczne

Gotowe kolekcje API znajdują się w katalogu:

```text
docs/api-clients
```

Dostępne są:

- kolekcja Postman,
- środowisko Postman,
- kolekcja Insomnia.

## 14. Diagnostyka problemów API

### 401 Unauthorized

Sprawdź, czy token JWT jest aktualny i czy nagłówek ma format:

```http
Authorization: Bearer <token>
```

### 403 Forbidden

Token jest poprawny, ale konto nie ma wymaganej roli.

### 400 Bad Request

Sprawdź treść JSON i wymagane pola. W przypadku akcesji częstą przyczyną jest brak kwoty, opisu albo identyfikatora budżetu.

### 502 Bad Gateway

Najczęściej oznacza, że backend lub usługa za bramą nie działa. Sprawdź kontenery i logi:

```bash
docker compose -f docker-compose.distributed.yml ps
docker compose -f docker-compose.distributed.yml logs -f backend
```

### Błąd CORS

Jeżeli status odpowiedzi to `502`, problem zwykle nie leży w samej konfiguracji CORS, tylko w niedostępnym backendzie. Najpierw sprawdź logi API.
