# 📚 Biblioteka - Aplikacja do Zarządzania Zasobami (Library_app)
## Spis Treści
1.  Opis Projektu
2.  Prototyp Interfejsu (Lab 1)
3.  Wymagania Technologiczne i Uzasadnienie
4.  Architektura Projektu
5.  Uruchomienie Aplikacji
6.  Status Implementacji

---

## 1. Opis Projektu

**Biblioteka** to pełnoprawna, rozproszona aplikacja webowa przeznaczona do zarządzania zasobami biblioteki. Aplikacja wspiera procesy CRUD (Create, Read, Update, Delete) dla książek, użytkowników oraz zarządzania wypożyczeniami i zwrotami.

### Kluczowe Elementy Projektu
* **Architektura:** Podział na rozdzielone warstwy (kontrolery, serwisy).
* **Baza Danych:** Zaprojektowana w 3NF, zawierająca minimum 30 rekordów testowych.
* **System Ról:** Uwierzytelnianie (JWT) i autoryzacja na podstawie ról użytkowników.
* **Historia Git:** Minimum 40 commitów z zachowaniem konwencji.

---

## 2. Prototyp Interfejsu (Lab 1)

Wstępny prototyp interfejsu (Pulpit Bibliotekarza) został przygotowany w celu zdefiniowania uporządkowanego układu strony. W projekcie zastosowano **auto-layout** oraz **komponenty** z Figmy, co wspiera budowę responsywnego interfejsu.
[<img figma.png /img>](https://github.com/barthig/Biblioteka)

---

## 3. Wymagania Technologiczne i Uzasadnienie

Projekt wykorzystuje nowoczesne technologie, a ich wybór jest sensowny dla tego typu aplikacji.

### 💻 Frontend
| Technologia | Cel / Uzasadnienie |
| :--- | :--- |
| **React** | Wybrany ze względu na modułowość i dużą społeczność. Idealny do budowania dynamicznych interfejsów (np. obsługa stanów `loading`/`error`). |
| **Tailwind CSS** | Wybrany jako narzędzie wspierające szybkie tworzenie **responsywnego interfejsu** i utrzymanie ujednoliconego design system. |

### ⚙️ Backend
| Technologia | Cel / Uzasadnienie |
| :--- | :--- |
| **[Wstaw Technologię, np. Spring Boot (Java) lub NestJS (Node.js)]** | Wybrany ze względu na stabilność, wydajność i natywne wsparcie dla architektury warstwowej, co ułatwia rozdzielenie kontrolerów i serwisów. |
| **PostgreSQL** | Wybrany jako stabilny, relacyjny system baz danych, idealny do utrzymania bazy danych w 3NF. |
| **RabbitMQ** | Użyty do implementacji asynchronicznych zadań kolejkowych (np. wysyłania powiadomień e-mail o zbliżającym się terminie zwrotu książki). |

---

## 4. Architektura Projektu

Kod został zorganizowany w warstwy, co zapobiega powielaniu logiki (DRY) i ułatwia zarządzanie kodem.

* **Controller Layer:** Obsługa żądań HTTP i komunikacja z API (REST/GraphQL).
* **Service Layer:** Zawiera logikę biznesową (np. walidacja, czy użytkownik ma limit wypożyczeń).
* **Repository/DAO Layer:** Bezpośrednia komunikacja z bazą danych (np. ORM).

---

## 5. Uruchomienie Aplikacji

Instrukcja startu backendu i frontendu.

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

## 6. Status Implementacji

Poniższa lista przedstawia zadeklarowane funkcjonalności. W dniu zaliczenia musi działać co najmniej **70%** z nich.

| Funkcjonalność | Status | Kryterium |
| :--- | :--- | :--- |
| CRUD Książek i Użytkowników | ✅ Gotowe | Podstawa funkcjonalności |
| Wypożyczanie/Zwrot | ✅ Gotowe | Podstawa funkcjonalności |
| Uwierzytelnianie JWT i Role | ✅ Gotowe | Bezpieczeństwo |
| Asynchroniczne powiadomienia (RabbitMQ) | ⏳ W toku | Kolejki |
| Obsługa stanów Loading/Error (Frontend) | ⏳ W toku | Frontend-API |
| Dokumentacja API (Swagger/OpenAPI) | ⏳ W toku | Dokumentacja |
