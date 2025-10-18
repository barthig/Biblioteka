# 📚 Biblioteka - Aplikacja do Zarządzania Zasobami (Library_app)

## Spis Treści
1.  Opis Projektu
2.  Prototyp Interfejsu (Lab 1)
3.  [cite_start]Wymagania Technologiczne i Uzasadnienie (Kryterium 6) [cite: 37]
4.  [cite_start]Architektura Projektu (Kryterium 7) [cite: 38]
5.  [cite_start]Uruchomienie Aplikacji (Kryterium 1) [cite: 32]
6.  [cite_start]Status Implementacji (Kryterium 5) [cite: 36]

---

## 1. Opis Projektu

**Biblioteka** to pełnoprawna, rozproszona aplikacja webowa przeznaczona do zarządzania zasobami biblioteki. Aplikacja wspiera procesy CRUD (Create, Read, Update, Delete) dla książek, użytkowników oraz zarządzania wypożyczeniami i zwrotami.

### Kluczowe Elementy Projektu
* [cite_start]**Architektura:** Podział na warstwy (kontrolery, serwisy)[cite: 38].
* [cite_start]**Baza Danych:** Zaprojektowana w 3NF, zawierająca minimum 30 rekordów testowych[cite: 34].
* [cite_start]**System Ról:** Uwierzytelnianie (JWT) i autoryzacja na podstawie ról użytkowników[cite: 40].
* [cite_start]**Historia Git:** Minimum 40 commitów z zachowaniem konwencji[cite: 35].

---

## 2. Prototyp Interfejsu (Lab 1)

[cite_start]Wstępny prototyp interfejsu (Pulpit Bibliotekarza) został przygotowany w celu zdefiniowania uporządkowanego układu strony[cite: 11, 23]. [cite_start]W projekcie zastosowano **auto-layout** oraz **komponenty** z Figmy, co wspiera budowę responsywnego interfejsu[cite: 8, 39].

**Link do projektu w Figmie / Zrzut Ekranu:**
* **Zrzut ekranu:** `./docs/Library_Dashboard_Prototype.png`
* [cite_start]**Adres repozytorium z plikiem:** `https://github.com/barthig/Biblioteka` [cite: 24]

---

## 3. Wymagania Technologiczne i Uzasadnienie (Kryterium 6)

[cite_start]Projekt wykorzystuje nowoczesne technologie [cite: 37][cite_start], a ich wybór jest sensowny dla tego typu aplikacji[cite: 18].

### 💻 Frontend
| Technologia | Cel / Uzasadnienie |
| :--- | :--- |
| **React** | Wybrany ze względu na modułowość i dużą społeczność. [cite_start]Idealny do budowania dynamicznych interfejsów (np. obsługa stanów `loading`/`error`)[cite: 42]. |
| **Tailwind CSS** | [cite_start]Wybrany jako narzędzie wspierające szybkie tworzenie **responsywnego interfejsu** i utrzymanie ujednoliconego design system[cite: 19, 39]. |

### ⚙️ Backend
| Technologia | Cel / Uzasadnienie |
| :--- | :--- |
| **[Wstaw Technologię, np. Spring Boot (Java) lub NestJS (Node.js)]** | [cite_start]Wybrany ze względu na stabilność, wydajność i natywne wsparcie dla architektury warstwowej, co ułatwia rozdzielenie kontrolerów i serwisów[cite: 38]. |
| **PostgreSQL** | [cite_start]Wybrany jako stabilny, relacyjny system baz danych, idealny do utrzymania bazy danych w 3NF[cite: 34]. |
| **RabbitMQ** | [cite_start]Użyty do implementacji asynchronicznych zadań kolejkowych (np. wysyłania powiadomień e-mail o zbliżającym się terminie zwrotu książki)[cite: 44]. |

---

## 4. Architektura Projektu (Kryterium 7)

[cite_start]Kod został zorganizowany w warstwy [cite: 38][cite_start], co zapobiega powielaniu logiki (DRY) i ułatwia zarządzanie kodem (Kryterium 12)[cite: 43].

* [cite_start]**Controller Layer:** Obsługa żądań HTTP i komunikacja z API (REST/GraphQL)[cite: 41].
* **Service Layer:** Zawiera logikę biznesową (np. walidacja, czy użytkownik ma limit wypożyczeń).
* **Repository/DAO Layer:** Bezpośrednia komunikacja z bazą danych (np. ORM).

---

## 5. Uruchomienie Aplikacji (Kryterium 1)

[cite_start]Instrukcja startu backendu i frontendu[cite: 32].

### Wymagania Wstępne
* Node.js (v18+)
* [Wymagany runtime dla backendu, np. Java 17+ lub Python 3.10+]
* Docker (dla bazy danych i RabbitMQ)

### 🚀 Start Backendu
1.  Sklonuj repozytorium: `git clone https://github.com/barthig/Biblioteka.git`
2.  Przejdź do katalogu backendu: `cd Biblioteka/backend`
3.  Uruchom kontener bazy danych i kolejek: `docker-compose up -d`
4.  Zbuduj i uruchom aplikację: `[Komenda uruchamiająca backend, np. ./mvnw spring-boot:run]`

### 🌐 Start Frontendu
1.  Przejdź do katalogu frontendu: `cd Biblioteka/frontend`
2.  Zainstaluj zależności: `npm install`
3.  Uruchom aplikację: `npm run dev`

---

## 6. Status Implementacji (Kryterium 5)

Poniższa lista przedstawia zadeklarowane funkcjonalności. [cite_start]W dniu zaliczenia musi działać co najmniej **70%** z nich[cite: 36].

| Funkcjonalność | Status | Kryterium |
| :--- | :--- | :--- |
| CRUD Książek i Użytkowników | ✅ Gotowe | [cite_start]Podstawa funkcjonalności [cite: 36] |
| Wypożyczanie/Zwrot | ✅ Gotowe | [cite_start]Podstawa funkcjonalności [cite: 36] |
| Uwierzytelnianie JWT i Role | ✅ Gotowe | [cite_start]Kryterium 9 [cite: 40] |
| Asynchroniczne powiadomienia (RabbitMQ) | ⏳ W toku | [cite_start]Kryterium 13 [cite: 44] |
| Obsługa stanów Loading/Error (Frontend) | ⏳ W toku | [cite_start]Kryterium 11 [cite: 42] |
| Dokumentacja API (Swagger/OpenAPI) | ⏳ W toku | [cite_start]Kryterium 14 [cite: 45] |
