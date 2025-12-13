# Konfiguracja Projektu Biblioteka
**Data**: 2025-12-13
**Wersja**: 2.0.0

## Stack Technologiczny

### Backend
- **Framework**: Symfony 6.4.30
- **PHP**: 8.2-FPM
- **ORM**: Doctrine
- **Cache**: Redis 7-alpine
- **Queue**: RabbitMQ 3.13-management
- **Environment**: Production (APP_ENV=prod, APP_DEBUG=0)

### Frontend
- **Framework**: React 18
- **Build Tool**: Vite
- **Router**: React Router DOM
- **Port**: 5173 (dev), 80 (production via nginx)

### Database
- **Engine**: PostgreSQL 15.15
- **Database**: biblioteka_dev
- **User**: biblioteka
- **Port**: 5432

### Web Server
- **Server**: Nginx Alpine
- **Port**: 8000
- **Backend Proxy**: PHP-FPM (port 9000)

## Optymalizacje

### Backend Performance
- **OPcache**: Enabled
  - memory_consumption: 128MB
  - max_accelerated_files: 10000
  - revalidate_freq: 2s
- **Redis Cache**: 
  - Doctrine metadata cache
  - Doctrine query cache
  - Doctrine result cache
  - Application cache
- **Response Time**: ~122ms (optymalizacja z 2400ms)

### N+1 Query Fixes
- Rating statistics batch loading (RatingRepository::getRatingStatsForBooks)
- Reduced 20+ queries per page to single query

## Struktura Bazy Danych

### Główne Tabele

#### Użytkownicy i Autoryzacja
- `app_user` - Konta użytkowników
- `refresh_tokens` - Tokeny odświeżania JWT
- `audit_logs` - Logi aktywności systemowej

#### Katalog i Zasoby
- `book` - Książki
- `book_copy` - Egzemplarze książek
- `author` - Autorzy
- `category` - Kategorie
- `book_category` - Relacja książka-kategoria
- `book_collection` - Kolekcje kuratorskie
- `collection_books` - Relacja kolekcja-książka

#### Wypożyczenia i Rezerwacje
- `loan` - Wypożyczenia
- `reservation` - Rezerwacje
- `fine` - Kary finansowe

#### Rekomendacje i Oceny
- `rating` - Oceny książek
- `recommendation_feedback` - Feedback rekomendacji
- `favorite` - Ulubione książki
- `review` - Recenzje książek

#### Akwizycja
- `acquisition_budget` - Budżety
- `acquisition_order` - Zamówienia
- `acquisition_expense` - Wydatki
- `supplier` - Dostawcy

#### Komunikacja
- `announcement` - Ogłoszenia
- `notification_log` - Logi powiadomień

### Sekwencje (SEQUENCE strategy)
Wszystkie tabele używają PostgreSQL SEQUENCE z allocationSize=1:
- `app_user_id_seq`
- `book_id_seq`
- `loan_id_seq`
- `reservation_id_seq`
- `fine_id_seq`
- `refresh_token_id_seq`
- `review_id_seq`
- `favorite_id_seq`
- `rating_id_seq`

## Endpoints API

### Autentykacja
- `POST /api/auth/login` - Logowanie
- `POST /api/auth/refresh` - Odświeżanie tokena
- `POST /api/auth/logout` - Wylogowanie
- `GET /api/auth/profile` - Profil użytkownika

### Użytkownicy
- `GET /api/users` - Lista użytkowników (LIBRARIAN+)
- `GET /api/users/{id}` - Szczegóły użytkownika
- `GET /api/users/{id}/details` - Pełne szczegóły (wypożyczenia, kary, historia) - **NOWE**
- `GET /api/users/search?q=` - Wyszukiwanie użytkowników
- `PUT /api/users/{id}` - Aktualizacja użytkownika
- `DELETE /api/users/{id}` - Usunięcie użytkownika
- `POST /api/users/{id}/block` - Blokowanie/odblokowanie

### Książki
- `GET /api/books` - Lista książek (paginacja, filtry)
- `GET /api/books/{id}` - Szczegóły książki
- `POST /api/books` - Dodanie książki (LIBRARIAN+)
- `PUT /api/books/{id}` - Aktualizacja książki
- `DELETE /api/books/{id}` - Usunięcie książki

### Wypożyczenia
- `GET /api/loans` - Lista wypożyczeń
- `POST /api/loans` - Utworzenie wypożyczenia
- `POST /api/loans/{id}/return` - Zwrot książki
- `POST /api/loans/{id}/extend` - Przedłużenie wypożyczenia

### Rezerwacje
- `GET /api/reservations` - Lista rezerwacji
- `POST /api/reservations` - Utworzenie rezerwacji
- `DELETE /api/reservations/{id}` - Anulowanie rezerwacji

### Ulubione i Oceny
- `GET /api/favorites` - Ulubione książki użytkownika
- `POST /api/favorites` - Dodanie do ulubionych
- `DELETE /api/favorites/{id}` - Usunięcie z ulubionych
- `POST /api/books/{id}/rate` - Ocena książki
- `GET /api/books/{id}/ratings` - Oceny książki

### Dashboard i Statystyki
- `GET /api/dashboard` - Statystyki główne (totalBooks, totalUsers, activeLoans, activeReservations)

### Audyt
- `GET /api/audit-logs` - Logi aktywności (ADMIN)

## Deployment

### Docker Services
```yaml
services:
  - db (PostgreSQL 15)
  - redis (7-alpine)
  - rabbitmq (3.13-management)
  - backend (PHP 8.2-FPM + Symfony)
  - php-worker (Consumer dla kolejek)
  - frontend (Node 20 + Vite)
  - nginx (Alpine)
```

### Uruchomienie
```bash
# Startuj wszystko
.\scripts\start-app.ps1

# lub manualnie
docker-compose -f docker-compose.dev.yml up -d

# Backend cache clear
docker exec lib-backend-1 php bin/console cache:clear
docker exec lib-backend-1 chown -R www-data:www-data /app/var
```

### Dostęp
- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8000/api
- **RabbitMQ Management**: http://localhost:15672 (guest/guest)

## Role i Uprawnienia

### ROLE_USER
- Przeglądanie katalogu
- Wypożyczenia własne
- Rezerwacje
- Ulubione i oceny
- Profil użytkownika

### ROLE_LIBRARIAN
- Wszystko co USER
- Zarządzanie wypożyczeniami (create, return, extend)
- Zarządzanie rezerwacjami
- Przeglądanie użytkowników
- **Szczegóły użytkownika** (wypożyczenia, kary, edycja danych)
- Zarządzanie egzemplarzami książek

### ROLE_ADMIN
- Wszystko co LIBRARIAN
- Zarządzanie użytkownikami (CRUD, blokowanie, role)
- Zarządzanie książkami (CRUD)
- Zarządzanie kategoriami i autorami
- Kolekcje kuratorskie
- Ogłoszenia
- Budżety i akwizycja
- Logi audytowe
- Konfiguracja systemu

## Najnowsze Zmiany (2025-12-13)

### Backend
1. ✅ **Szczegóły użytkownika** - endpoint `/api/users/{id}/details`
   - Aktywne wypożyczenia
   - Historia wypożyczeń (ostatnie 20)
   - Aktywne kary finansowe
   - Historia opłaconych kar
   - Statystyki użytkownika

2. ✅ **Poprawki pól encji**
   - Loan: `loanedAt` → `borrowedAt`, `borrower` → `user`
   - Fine: `issuedAt` → `createdAt`
   - Fine: dodano metodę `findByUser()` (query przez loan.user)
   - Fine: dodano metodę `isPaid()` i `getUser()`

3. ✅ **Grupy serializacji**
   - User: dodano `Groups(['user:read'])` do pól: phoneNumber, addressLine, city, postalCode, pesel, cardNumber
   - Fine: `Groups(['fine:read'])` do wszystkich pól

4. ✅ **UpdateUserCommand**
   - Dodano pola: pesel, cardNumber, phoneNumber, addressLine, city, postalCode
   - UpdateUserCommandHandler: obsługa wszystkich pól osobowych

5. ✅ **AuditLogRepository**
   - Naprawiono błąd GROUP BY w `findWithPagination()`
   - Rozdzielono query COUNT od query z joinami

### Frontend
1. ✅ **UserDetails.jsx** - nowa strona szczegółów użytkownika
   - Pełna edycja danych osobowych (in-place)
   - **Edycja ról** (checkboxy dla USER, LIBRARIAN, ADMIN)
   - Statystyki użytkownika (czytelne karty bez gradientu)
   - Aktywne wypożyczenia z podświetleniem przetrzymań
   - Historia wypożyczeń
   - Aktywne i opłacone kary

2. ✅ **AdminPanel.jsx**
   - Usunięto przycisk "Edytuj" (przeniesiono do UserDetails)
   - Dodano przycisk "Szczegóły" (pierwszy w kolejności)
   - Zakładka "Role i audyt" → "📋 Audyt"

3. ✅ **LibrarianPanel.jsx**
   - Dodano przycisk "Szczegóły" w wyszukiwaniu użytkowników

4. ✅ **Routing**
   - `/users/:id/details` - strona szczegółów (LIBRARIAN+)

## Znane Problemy

### Rozwiązane
- ✅ N+1 query problem w ocenach książek
- ✅ Sequence cache w Doctrine (explicit SEQUENCE strategy)
- ✅ Permissions /app/var (wymaga chown po cache clear)
- ✅ GROUP BY error w audit logs
- ✅ Missing user fields w API (dodano serialization groups)

### Do Monitorowania
- Permissions persistence po cache clear (wymaga manualnego `chown -R www-data:www-data /app/var`)
- Sequence synchronization po TRUNCATE (wszystkie sekwencje zresetowane)

## Backup i Restore

### Schema Export
```bash
docker exec -e PGPASSWORD=postgres lib-db-1 pg_dump -U biblioteka biblioteka_dev --schema-only --no-owner > backend/schema_current.sql
```

### Full Backup
```bash
docker exec -e PGPASSWORD=postgres lib-db-1 pg_dump -U biblioteka biblioteka_dev > backend/backup_$(Get-Date -Format 'yyyyMMdd_HHmmss').sql
```

### Restore
```bash
docker exec -i lib-db-1 psql -U biblioteka biblioteka_dev < backend/init-db.sql
```

## Monitoring

### Logi
```bash
# Backend logs
docker-compose -f docker-compose.dev.yml logs -f backend

# Błędy
docker-compose -f docker-compose.dev.yml logs --tail=50 backend | Select-String -Pattern "ERROR|Exception|critical"

# Redis stats
docker exec lib-redis-1 redis-cli INFO stats
```

### Performance
- Average response time: ~122ms
- OPcache hit rate: Check via `php -i | grep opcache`
- Redis commands processed: `redis-cli INFO stats | grep total_commands_processed`

## Kontakt i Wsparcie
- Repository: local development
- Environment: docker-compose.dev.yml
- Database backups: `backend/var/backups/`
