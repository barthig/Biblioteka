# Kontrakt routingu w trybie rozproszonym

Dokument opisuje, które ścieżki publiczne są obsługiwane przez backend, a które przez mikroserwisy, gdy ruch przechodzi przez Traefik.

## Zasada ogólna

- Frontend rozmawia wyłącznie z bramą (`http://localhost`).
- Każda publiczna ścieżka ma jednego właściciela.
- Backend pełni rolę warstwy BFF dla endpointów wymagających kontekstu użytkownika lub spójnej logiki domenowej.
- Mikroserwisy wystawiają tylko swoje wyspecjalizowane API.

## Własność ścieżek

### Ścieżki backendu

Przykładowe ścieżki kierowane do backendu Symfony:

- `/api/notifications`
- `/api/notifications/test`
- `/api/recommendations/personal`
- pozostałe `/api/**`, które nie są jawnie przekierowane do mikroserwisu
- `/health` oraz `/health/distributed`

Uzasadnienie:

- wymagają centralnej autoryzacji i logiki BFF,
- stanowią publiczny kontrakt dla frontendu.

### Ścieżki notification-service

Ścieżki kierowane bezpośrednio do usługi powiadomień:

- `/api/notifications/logs`
- `/api/notifications/stats`

Uzasadnienie:

- to read model usługi powiadomień oparty o jej własną bazę,
- nie powinny kolidować z endpointem backendowym `/api/notifications`.

### Ścieżki recommendation-service

Ścieżki kierowane bezpośrednio do usługi rekomendacji:

- `/api/recommendations/similar/{bookId}`
- `/api/recommendations/for-user/{userId}`
- `/api/recommendations/search`

Uzasadnienie:

- to wyspecjalizowane endpointy usługowe,
- personalizacja dla użytkownika końcowego pozostaje pod backendowym BFF (`/api/recommendations/personal`).

## Kontrakt dla frontendu

Kod frontendu powinien używać wyłącznie publicznych ścieżek bramy. Jeżeli funkcja UI potrzebuje danych dostępnych tylko w mikroserwisie, preferowane jest dodanie adaptera po stronie backendu zamiast bezpośredniego spinania UI z wewnętrznym kontraktem usługi.

## Testy regresyjne routingu

Po zmianach w Traefik lub ścieżkach API uruchamiaj:

- `tests/integration/test_cross_service.sh`
- `tests/integration/test_gateway_routing.sh`

Skrypty sprawdzają m.in.:

- czy endpointy backendowe nie są „połykane” przez reguły prefiksowe mikroserwisów,
- czy ścieżki mikroserwisów pozostają osiągalne przez bramę,
- czy nie wracają błędy 404 wynikające z nieprawidłowego ownera trasy.
