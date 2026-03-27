# Weryfikacja API w Postmanie i Insomnii

Repozytorium zawiera gotowe pliki do importu, które ułatwiają ręczne testowanie API w trybie rozproszonym.

## Dostępne pliki

- `docs/api-clients/Biblioteka.postman_collection.json`
- `docs/api-clients/Biblioteka.postman_environment.json`
- `docs/api-clients/Biblioteka.insomnia.json`

## Zalecany przebieg

1. Uruchom stos rozproszony:

```bash
docker compose -f docker-compose.distributed.yml up --build -d
```

2. Zaimportuj kolekcję/workspace do klienta API.
3. Wykonaj żądanie logowania (`Auth / Login`).
4. Potwierdź zapis `accessToken` i `refreshToken`.
5. Wykonaj przykładowe żądania autoryzowane, np.:
   - `Account / Get Me`,
   - `Loans and Reservations / List Loans`.
6. Wykonaj żądania publiczne dla architektury rozproszonej, np.:
   - `Health / Distributed Health`,
   - `Distributed Services / Notification Stats`,
   - `Distributed Services / Recommendation Search`.

## Domyślne zmienne

- `baseUrl`: `http://localhost`
- `email`: `user01@example.com`
- `password`: `password123`
- `bookId`: `1`
- `loanId`: `1`
- `reservationId`: `1`

## Uwagi

- Kolekcja jest przygotowana pod bramę Traefik (`http://localhost`).
- Endpointy notification-service i recommendation-service są testowane przez trasy bramy, a nie przez porty wewnętrzne.
- Żądania typu `Extend Loan` i `Fulfill Reservation` mogą wymagać podania identyfikatorów istniejących rekordów testowych.
