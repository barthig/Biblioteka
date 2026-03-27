# Zasady współtworzenia projektu Biblioteka

Dziękujemy za chęć współtworzenia projektu. Ten dokument opisuje zalecany sposób pracy, standardy kodu oraz proces zgłaszania zmian.

## Spis treści

- [Szybki start dla kontrybutora](#szybki-start-dla-kontrybutora)
- [Proces pracy](#proces-pracy)
- [Standardy kodu](#standardy-kodu)
- [Commity i Pull Requesty](#commity-i-pull-requesty)
- [Lista kontrolna przed wysłaniem zmian](#lista-kontrolna-przed-wysłaniem-zmian)

## Szybki start dla kontrybutora

### Wymagania

- Git,
- Docker Desktop + Docker Compose v2,
- albo lokalnie: PHP 8.2+, Composer, Node 18+, npm.

### Przygotowanie repozytorium

```bash
git clone https://github.com/barthig/Biblioteka.git
cd Biblioteka
cp .env.example .env
docker compose -f docker-compose.distributed.yml up --build -d
```

## Proces pracy

1. Utwórz gałąź roboczą:

```bash
git checkout -b feature/nazwa-zmiany
```

2. Wprowadź zmiany możliwie małym i czytelnym zakresem.
3. Dodaj lub zaktualizuj testy, jeśli zmienia się logika.
4. Zaktualizuj dokumentację, jeżeli zmienia się API, przepływ lub konfiguracja.
5. Otwórz Pull Request z opisem: co, dlaczego, jak przetestowano.

## Standardy kodu

### Backend (PHP/Symfony)

- trzymaj się PSR-12,
- stosuj typowanie argumentów i typy zwrotne,
- unikaj długich metod i klas bez pojedynczej odpowiedzialności,
- nowe endpointy dokumentuj przez OpenAPI/Nelmio.

Lokalna weryfikacja:

```bash
cd backend
composer install
vendor/bin/phpstan analyse src --level=6
vendor/bin/phpunit
```

### Frontend (React)

- używaj komponentów funkcyjnych,
- umieszczaj logikę API w warstwie services,
- utrzymuj czytelne nazewnictwo i podział na komponenty,
- pilnuj spójności z istniejącym stylem projektu.

Lokalna weryfikacja:

```bash
cd frontend
npm ci
npm run lint
npm run test:run
npm run test:coverage
```

### Mikroserwisy (FastAPI)

- zmiany kontraktu endpointów uzgadniaj z kontraktem routingu,
- zwracaj stabilny format odpowiedzi dla endpointów publicznych,
- dbaj o logowanie i metryki przy ścieżkach krytycznych.

## Commity i Pull Requesty

### Format commitów

Stosuj konwencję Conventional Commits:

- `feat:` nowa funkcjonalność,
- `fix:` poprawka błędu,
- `docs:` zmiany dokumentacji,
- `refactor:` refaktoryzacja bez zmiany zachowania,
- `test:` zmiany w testach,
- `ci:` zmiany pipeline.

Przykłady:

```text
feat(api): dodaj filtrowanie listy wypożyczeń po statusie
fix(frontend): popraw obsługę pustego stanu w liście książek
docs: zaktualizuj README dla trybu rozproszonego
```

### Zawartość Pull Requesta

W opisie PR podaj:

1. cel zmiany,
2. zakres plików/obszarów,
3. sposób testowania,
4. ewentualny wpływ na kompatybilność.

## Lista kontrolna przed wysłaniem zmian

- kod buduje się lokalnie,
- testy dla zmienianego obszaru przechodzą,
- linting i analiza statyczna przechodzą,
- dokumentacja jest spójna z kodem,
- brak przypadkowych zmian w niepowiązanych plikach.
