# API Verification with Postman and Insomnia

This repository includes ready-to-import artifacts for manual verification of the distributed API.

Files:
- `docs/api-clients/Biblioteka.postman_collection.json`
- `docs/api-clients/Biblioteka.postman_environment.json`
- `docs/api-clients/Biblioteka.insomnia.json`

## Recommended flow

1. Start the distributed stack:
   - `docker compose -f docker-compose.distributed.yml up --build -d`
2. Import the collection or workspace into your client.
3. Run `Auth / Login` first.
4. Verify that `accessToken` and `refreshToken` were stored.
5. Run authenticated requests such as:
   - `Account / Get Me`
   - `Loans and Reservations / List Loans`
6. Run public distributed checks:
   - `Health / Distributed Health`
   - `Distributed Services / Notification Stats`
   - `Distributed Services / Recommendation Search`

## Default variables

- `baseUrl`: `http://localhost`
- `email`: `user01@example.com`
- `password`: `password123`
- `bookId`: `1`
- `loanId`: `1`
- `reservationId`: `1`

## Notes

- The collection targets the distributed gateway at `http://localhost`.
- Notification and recommendation services are verified through Traefik routes, not direct host ports.
- `Extend Loan` and `Fulfill Reservation` are included as manual smoke requests and may require adjusting `loanId` or `reservationId` to existing seeded records.