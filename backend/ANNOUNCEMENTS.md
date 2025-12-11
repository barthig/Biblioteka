# Moduł Ogłoszeń (Announcements)

## Opis
Moduł ogłoszeń umożliwia administratorom biblioteki publikowanie informacji dla użytkowników systemu. Ogłoszenia mogą być targetowane do różnych grup odbiorców i wyświetlane na stronie głównej.

## Funkcjonalności

### Dla administratorów/bibliotekarzy:
- ✅ Tworzenie nowych ogłoszeń
- ✅ Edycja istniejących ogłoszeń
- ✅ Publikacja ogłoszeń
- ✅ Archiwizacja ogłoszeń
- ✅ Usuwanie ogłoszeń
- ✅ Przypinanie ważnych ogłoszeń
- ✅ Ustawianie daty wygaśnięcia
- ✅ Targetowanie do konkretnych grup użytkowników

### Dla użytkowników:
- ✅ Przeglądanie aktywnych ogłoszeń
- ✅ Widok ogłoszeń na stronie głównej
- ✅ Filtrowanie ogłoszeń według typu
- ✅ Automatyczne ukrywanie wygasłych ogłoszeń

## Struktura danych

### Encja: `Announcement`
```php
- id: int
- title: string (255)
- content: text
- type: string (info|warning|urgent|maintenance)
- status: string (draft|published|archived)
- isPinned: boolean
- showOnHomepage: boolean
- createdBy: User
- createdAt: DateTimeImmutable
- updatedAt: DateTimeImmutable
- publishedAt: DateTimeImmutable|null
- expiresAt: DateTimeImmutable|null
- targetAudience: array|null (['all']|['users']|['librarians'])
```

## Endpointy API

### GET `/api/announcements`
Pobiera listę ogłoszeń.

**Query Parameters:**
- `status` (string, optional) - Filtruje według statusu (draft/published/archived) - tylko dla bibliotekarzy
- `homepage` (boolean, optional) - Tylko ogłoszenia dla strony głównej
- `page` (int, default: 1) - Numer strony
- `limit` (int, default: 20) - Liczba elementów na stronę

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Witamy w systemie!",
      "content": "Treść ogłoszenia...",
      "type": "info",
      "status": "published",
      "isPinned": true,
      "showOnHomepage": true,
      "createdAt": "2025-12-11T12:00:00+00:00",
      "publishedAt": "2025-12-11T12:00:00+00:00"
    }
  ],
  "meta": {
    "page": 1,
    "limit": 20,
    "total": 5,
    "totalPages": 1
  }
}
```

### GET `/api/announcements/{id}`
Pobiera szczegóły pojedynczego ogłoszenia.

**Response:** Obiekt ogłoszenia z pełnymi szczegółami

### POST `/api/announcements`
Tworzy nowe ogłoszenie (wymaga roli ROLE_LIBRARIAN).

**Request Body:**
```json
{
  "title": "Nowe ogłoszenie",
  "content": "Treść ogłoszenia",
  "type": "info",
  "isPinned": false,
  "showOnHomepage": true,
  "targetAudience": ["all"],
  "expiresAt": "2025-12-31T23:59:59+00:00"
}
```

### PUT/PATCH `/api/announcements/{id}`
Aktualizuje ogłoszenie (wymaga roli ROLE_LIBRARIAN).

### POST `/api/announcements/{id}/publish`
Publikuje ogłoszenie (wymaga roli ROLE_LIBRARIAN).

### POST `/api/announcements/{id}/archive`
Archiwizuje ogłoszenie (wymaga roli ROLE_LIBRARIAN).

### DELETE `/api/announcements/{id}`
Usuwa ogłoszenie (wymaga roli ROLE_LIBRARIAN).

## Typy ogłoszeń

- **info** - Informacyjne (niebieski)
- **warning** - Ostrzeżenia (żółty)
- **urgent** - Pilne (pomarańczowy)
- **maintenance** - Przerwy techniczne (szary)

## Grupy docelowe (targetAudience)

- **all** - Wszyscy użytkownicy (zalogowani i niezalogowani)
- **users** - Tylko zarejestrowani użytkownicy
- **librarians** - Tylko bibliotekarze

## Logika widoczności

Ogłoszenie jest widoczne gdy:
1. Status = `published`
2. `publishedAt` <= teraz (lub null)
3. `expiresAt` > teraz (lub null)
4. Użytkownik należy do grupy docelowej

## Przykładowe użycie

### Utworzenie ogłoszenia o godzinach otwarcia
```bash
curl -X POST http://localhost:8000/api/announcements \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Godziny otwarcia biblioteki",
    "content": "Pon-Pt: 8:00-20:00, Sobota: 9:00-14:00",
    "type": "info",
    "isPinned": true,
    "showOnHomepage": true,
    "targetAudience": ["all"]
  }'
```

### Pobranie ogłoszeń dla strony głównej
```bash
curl http://localhost:8000/api/announcements?homepage=true&limit=5
```

## Automatyczne zadania

### Archiwizacja wygasłych ogłoszeń
Repozytorium zawiera metodę `archiveExpired()`, którą można wywołać w zadaniu CRON:

```php
$announcementRepository->archiveExpired();
```

## Testy

Moduł zawiera kompleksowe testy jednostkowe:
- ✅ Tworzenie ogłoszeń
- ✅ Publikacja i archiwizacja
- ✅ Logika widoczności
- ✅ Targetowanie grup użytkowników
- ✅ Obsługa dat wygaśnięcia

Uruchom testy:
```bash
vendor/bin/phpunit tests/Entity/AnnouncementTest.php
```

## Fixtures

Moduł zawiera fixtures z przykładowymi ogłoszeniami:
- Ogłoszenie powitalne (przypięte)
- Godziny otwarcia (przypięte)
- Nowe książki
- Aktualizacja regulaminu
- Przerwa techniczna
- Ogłoszenie tylko dla bibliotekarzy
- Przypomnienie o karach
- Szkic ogłoszenia (nieopublikowany)

Załaduj fixtures:
```bash
php bin/console doctrine:fixtures:load --append
```

## Integracja z frontendem

Frontend może wykorzystać następujące endpointy:
- Strona główna: `GET /api/announcements?homepage=true&limit=5`
- Lista ogłoszeń: `GET /api/announcements?page=1&limit=20`
- Panel administracyjny: `GET /api/announcements?status=draft` (dla bibliotekarzy)

## Migracja

Tabela `announcement` została utworzona w migracji:
- `migrations/Version20251211121430.php`

Uruchom migrację:
```bash
php bin/console doctrine:migrations:migrate
```
