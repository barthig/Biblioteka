# Uruchomienie systemu Biblioteka w Docker

## Wymagania
- Docker Desktop (wersja 20.10 lub nowsza)
- Docker Compose (wersja 2.0 lub nowsza)

## Szybki start (Development)

1. **Sklonuj repozytorium i przejdź do katalogu projektu**
   ```powershell
   cd D:\Biblioteka-1
   ```

2. **Uruchom wszystkie serwisy w trybie development**
   ```powershell
   docker-compose -f docker-compose.dev.yml up -d --build
   ```

3. **Sprawdź status kontenerów**
   ```powershell
   docker-compose -f docker-compose.dev.yml ps
   ```

4. **Aplikacja będzie dostępna pod adresami:**
   - Frontend: http://localhost:5173
   - Backend API: http://localhost:8000
   - RabbitMQ Management: http://localhost:15672 (login: app / hasło: app)

## Uruchomienie produkcyjne

1. **Skopiuj plik konfiguracyjny**
   ```powershell
   Copy-Item .env.example .env
   ```

2. **Edytuj plik .env i zmień sekrety**
   ```
   API_SECRET=twoj_bezpieczny_klucz_api
   JWT_SECRET=twoj_bezpieczny_klucz_jwt
   POSTGRES_PASSWORD=twoje_haslo_do_bazy
   RABBITMQ_PASSWORD=twoje_haslo_do_rabbitmq
   ```

3. **Uruchom w trybie produkcyjnym**
   ```powershell
   docker-compose up -d --build
   ```

4. **Aplikacja będzie dostępna pod adresami:**
   - Frontend: http://localhost:3000
   - Backend API: http://localhost:8000

## Zarządzanie kontenerami

### Zatrzymanie wszystkich serwisów
```powershell
docker-compose -f docker-compose.dev.yml down
```

### Zatrzymanie i usunięcie volumes (UWAGA: usuwa dane!)
```powershell
docker-compose -f docker-compose.dev.yml down -v
```

### Restart konkretnego serwisu
```powershell
docker-compose -f docker-compose.dev.yml restart backend
```

### Przeglądanie logów
```powershell
# Wszystkie serwisy
docker-compose -f docker-compose.dev.yml logs -f

# Konkretny serwis
docker-compose -f docker-compose.dev.yml logs -f backend
```

## Baza danych

### Inicjalizacja
Baza danych jest automatycznie inicjalizowana przy pierwszym uruchomieniu z jednego pliku:

- `backend/init-db.sql` – kompletny schemat wraz ze spójnymi danymi startowymi (użytkownicy, książki, egzemplarze, rezerwacje, płatności, role, integracje)

Kontener Postgresa załaduje dane podczas pierwszego uruchomienia volume `postgres_data`. Jeśli potrzebujesz ponownej inicjalizacji, usuń volume (`docker volume rm biblioteka_postgres_data`) lub uruchom `docker-compose ... down -v`.

#### Dostępne konta testowe
- Administrator: `admin@biblioteka.local` / hasło `Password123!`
- Bibliotekarz: `bibliotekarz@biblioteka.local` / hasło `Password123!`
- Czytelnik: `marta.nowak@example.com` / hasło `Password123!`

### Ręczne wykonanie migracji (jeśli potrzebne)
```powershell
docker-compose -f docker-compose.dev.yml exec backend php bin/console doctrine:migrations:migrate
```

### Dostęp do bazy danych
```powershell
docker-compose -f docker-compose.dev.yml exec db psql -U biblioteka -d biblioteka_dev
```

### Backup bazy danych
```powershell
docker-compose -f docker-compose.dev.yml exec db pg_dump -U biblioteka biblioteka_dev > backup.sql
```

### Restore bazy danych
```powershell
Get-Content backup.sql | docker-compose -f docker-compose.dev.yml exec -T db psql -U biblioteka -d biblioteka_dev
```

## Debugowanie

### Sprawdzenie stanu wszystkich kontenerów
```powershell
docker-compose -f docker-compose.dev.yml ps
```

### Wejście do kontenera (shell)
```powershell
# Backend
docker-compose -f docker-compose.dev.yml exec backend sh

# Frontend
docker-compose -f docker-compose.dev.yml exec frontend sh

# Baza danych
docker-compose -f docker-compose.dev.yml exec db sh
```

### Sprawdzenie zasobów Docker
```powershell
docker system df
```

### Czyszczenie niewykorzystanych zasobów
```powershell
docker system prune -a
```

## Rozwiązywanie problemów

### Problem: Kontener się restartuje
```powershell
# Sprawdź logi
docker-compose -f docker-compose.dev.yml logs backend
```

### Problem: Port jest już zajęty
```powershell
# Sprawdź co używa portu
netstat -ano | findstr :8000

# Zatrzymaj proces lub zmień port w docker-compose.yml
```

### Problem: Brak połączenia z bazą danych
```powershell
# Sprawdź czy baza jest zdrowa
docker-compose -f docker-compose.dev.yml exec db pg_isready -U biblioteka

# Sprawdź logi bazy
docker-compose -f docker-compose.dev.yml logs db
```

### Problem: Frontend nie łączy się z backendem
- Sprawdź czy zmienna `VITE_API_URL` w docker-compose.dev.yml wskazuje na `http://localhost:8000`
- Upewnij się, że backend odpowiada: `curl http://localhost:8000/api/announcements`

## Struktura serwisów

- **db** - PostgreSQL 15 (baza danych)
- **rabbitmq** - RabbitMQ 3.13 (kolejka komunikatów)
- **backend** - PHP 8.2 + Symfony (API)
- **php-worker** - Worker do obsługi kolejki
- **frontend** - Node 20 + React + Vite (interfejs użytkownika)

## Volumes (persystencja danych)

- `postgres_data` - dane bazy PostgreSQL
- `rabbitmq_data` - dane RabbitMQ
- `backend_var` - cache i logi backendu (tylko production)
- `frontend_node_modules` - node_modules frontendu (tylko dev)

## Archiwa

Stare konfiguracje Docker znajdują się w katalogu `archive/docker-configs/`.
