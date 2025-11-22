# Sprawozdanie – Lab JWT

## Cel zadania
Celem laboratorium było przygotowanie prostego serwera Express z obsługą JWT:
- wygenerowanie tokenu w funkcji `signToken`,
- weryfikacja tokenu w middleware `requireAuth`,
- udostępnienie endpointu `POST /login`, który zwraca token po poprawnym logowaniu,
- zabezpieczenie endpointu `GET /profile`, aby zwracał dane tylko dla żądań z ważnym tokenem.

## Konfiguracja środowiska
1. Skopiowano plik `.env.example` do `.env` i ustawiono własny sekret:
   ```
   JWT_SECRET=super_secret_key_123
   ```
2. W katalogu `labs/jwt` zainstalowano zależności: `npm install`.
3. Narzędzia: Node.js 18+, Express, jsonwebtoken, dotenv, nodemon.

## Uruchomienie
- Komenda: `npm run dev`
- Rezultat w terminalu: `Serwer nasłuchuje na http://localhost:3000`
- Serwer należy pozostawić uruchomiony (nodemon restartuje po zmianach).

## Implementacja
- `signToken(payload)` – korzysta z `jwt.sign`, używa `JWT_SECRET`, ustawia `expiresIn: '1h'`.
- `requireAuth` – pobiera nagłówek `Authorization: Bearer <token>`, weryfikuje `jwt.verify`, wyszukuje użytkownika, zapisuje go w `req.authUser`.
- Dane demo: pojedynczy użytkownik `demo@example.com / secret123`.
- `POST /login` – sprawdza dane logowania, wywołuje `signToken`, zwraca `{ "token": "..." }`.
- `GET /profile` – chroniony przez `requireAuth`, zwraca `{ id, email, name }` bieżącego użytkownika.

## Testy funkcjonalne
1. **Sukces logowania**
   - Żądanie: `POST http://localhost:3000/login`
     ```json
     {"email":"demo@example.com","password":"secret123"}
     ```
   - Odpowiedź: status 200, JSON z tokenem (np. `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...`).

2. **Sukces pobrania profilu**
   - Żądanie: `GET http://localhost:3000/profile`
     - Nagłówek: `Authorization: Bearer <TOKEN_Z_LOGOWANIA>`
   - Odpowiedź: status 200, `{"id":1,"email":"demo@example.com","name":"Demo User"}`.

3. **Błędny login**
   - Żądanie: `POST /login` z niepoprawnym hasłem
   - Status 401, `{"error":"Nieprawidłowy login lub hasło"}`.

4. **Brak tokenu**
   - Żądanie: `GET /profile` bez nagłówka Authorization
   - Status 401, `{"error":"Brak tokenu"}`.

## Wnioski i możliwe rozszerzenia
- Aktualna implementacja w pełni spełnia wymagania laboratorium i demonstruje podstawowy przepływ JWT.
- Możliwe rozszerzenia: przechowywanie użytkowników w bazie, reset haseł, obsługa odświeżania tokenów, testy automatyczne (np. Jest/Supertest).
