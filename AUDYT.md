AUDYT PROJEKTU BIBLIOTEKA (wymagania 1-14)

Zakres:
- przeglad plikow w repozytorium (bez uruchamiania aplikacji)
- uruchomione testy: backend PHPUnit Functional w Dockerze

Testy uruchomione:
- docker compose -f docker-compose.dev.yml run --rm -e APP_ENV=test -e APP_DEBUG=1 -e "DATABASE_URL=postgresql://biblioteka:biblioteka@db:5432/biblioteka_dev?serverVersion=16&charset=utf8" -e API_SECRET=dev_api_secret -e JWT_SECRET=dev_jwt_secret backend sh -c "php vendor/bin/phpunit --testsuite Functional"
- wynik: OK (143 tests, 446 assertions); wystepuja ostrzezenia deprecations z symfony/options-resolver i symfony/property-info

Wyniki wymagania po wymaganiu:

1) README i uruchomienie
- Status: TAK
- Dowody: README.md zawiera opis projektu; QUICKSTART.md dodaje krotka instrukcje uruchomienia backendu i frontendu (docker compose).
- Pliki: README.md, QUICKSTART.md



2) Architektura / ERD (min. 5 tabel)
- Status: TAK
- Dowody: dodany ERD_DIAGRAM.md z diagramem ERD (mermaid) pokazujacym >=5 tabel i relacje.
- Pliki: ERD_DIAGRAM.md



3) Baza danych (3NF, min. 30 rekordow testowych)
- Status: CZESCIOWO
- Dowody: fixtures w backend/src/DataFixtures/AppFixtures.php tworza wiele rekordow (autorzy, kategorie, uzytkownicy, ksiazki, kopie, wypozyczenia, rezerwacje, kary itd.). To sugeruje >30 rekordow.
- Brak twardej weryfikacji 3NF i faktycznego zaladowania danych w DB w tej sesji.
- Pliki: backend/src/DataFixtures/AppFixtures.php, backend/schema_full_export.sql

4) Repozytorium Git (min. 40 commitow, konwencja)
- Status: TAK
- Dowody: git rev-list --count HEAD = 108.
- Konwencja commitow nie byla weryfikowana nazwami (brak logu).

5) Implementacja funkcji (>=70%)
- Status: NIEZWERYFIKOWANE
- Dowody: istnieje szeroki zakres kontrolerow i serwisow, ale bez uruchomienia aplikacji i testow e2e nie da sie potwierdzic procentu funkcji.

6) Dbor technologii (nowoczesny backend i frontend + uzasadnienie)
- Status: TAK
- Dowody: README.md zawiera tabelki technologii i uzasadnienia; backend Symfony 6.4, frontend React 18 + Vite.

7) Architektura kodu (warstwy rozdzielone)
- Status: TAK
- Dowody: rozdzial na Controller/Service/Repository/Entity w backendzie.
- Pliki: backend/src/Controller, backend/src/Service, backend/src/Repository, backend/src/Entity

8) UX/UI (responsywnosc, design system)
- Status: NIEZWERYFIKOWANE
- Dowody: istnieja komponenty UI i arkusze CSS, ale brak inspekcji dzialajacej aplikacji.
- Pliki: frontend/src/components/*, frontend/src/styles.css, frontend/src/styles/main.css

9) Uwierzytelnianie i autoryzacja (JWT, role, sesje)
- Status: TAK (na poziomie kodu)
- Dowody: ApiAuthSubscriber + JwtService + role w kontrolerach; testy funkcjonalne przechodza.
- Pliki: backend/src/EventSubscriber/ApiAuthSubscriber.php, backend/src/Service/JwtService.php

10) API (REST zgodny ze standardami, statusy i bledy)
- Status: TAK (na poziomie kodu i testow)
- Dowody: kontrolery zwracaja poprawne statusy, ujednolicone JSON z message; testy funkcjonalne OK.
- Pliki: backend/src/Controller/*, backend/src/EventSubscriber/ApiExceptionSubscriber.php, backend/src/EventSubscriber/ApiResponseNormalizationSubscriber.php

11) Frontend API (korzystanie z API, loading/error)
- Status: CZESCIOWO
- Dowody: frontend ma warstwe apiFetch + serwisy; komponenty LoadingSpinner/ErrorMessage istnieja, ale brak audytu zachowania w UI.
- Pliki: frontend/src/api.js, frontend/src/services/*.js, frontend/src/components/LoadingSpinner.jsx, frontend/src/components/ErrorMessage.jsx

12) Jakosc kodu (DRY, konwencje, brak smieci)
- Status: CZESCIOWO
- Dowody: usunieto console.log w frontendzie; przeniesiono ad-hoc skrypty testowe z katalogu backend do backend/scripts/manual-tests, aby ograniczyc smieci w katalogu glownym. Brak pelnej analizy DRY i konwencji nazw.
- Pliki: frontend/src/context/AuthContext.jsx, backend/scripts/manual-tests/*

13) Asynchronicznosc / kolejki (RabbitMQ/Kafka)
- Status: TAK
- Dowody: Symfony Messenger, komendy i handlery wiadomosci.
- Pliki: backend/src/Message/*, backend/src/MessageHandler/*, backend/config/packages/messenger.yaml

14) Dokumentacja API (Swagger/OpenAPI)
- Status: TAK (na poziomie kodu)
- Dowody: kontrolery API posiadaja adnotacje OpenApi (OA) dla endpointow i parametrow; Nelmio ApiDoc skonfigurowany i routy /api/docs, /api/docs.json istnieja.
- Pliki: backend/src/Controller/*.php, backend/config/packages/nelmio_api_doc.yaml, backend/config/routes.yaml

Najwazniejsze braki do naprawy:
- Czesc plikow dokumentacji wspomnianych w README nadal nie istnieje (FRONTEND_DOCS.md, ARCHITECTURE.md, REQUIREMENTS_VERIFICATION.md, COMPLETION_SUMMARY.md).
- Brak jednoznacznej weryfikacji 3NF i liczby rekordow w DB w tej sesji (wymaganie #3).
- Brak potwierdzenia UX/UI oraz pokrycia funkcji na poziomie uruchomionej aplikacji (#5, #8, #11, #12).

Rekomendacje:
- Uzupelnic/naprawic dokumentacje: utworzyc brakujace pliki wspomniane w README lub usunac odwolania.
- Jesli ma byc wykazane 3NF i 30 rekordow: opisac to w dokumencie i pokazac procedure fixtures + przyklad liczby rekordow po zaladowaniu.
- Uruchomic aplikacje i zweryfikowac UX/UI oraz pokrycie funkcji na poziomie dzialajacego systemu.
