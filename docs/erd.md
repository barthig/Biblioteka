# Diagram ERD i model danych

Dokument opisuje aktualny model danych aplikacji **Biblioteka**. Diagram został zsynchronizowany z encjami Doctrine i odzwierciedla wszystkie kluczowe tabele wykorzystywane w systemie.

---

## Diagram ERD

```mermaid
erDiagram
    AUTHOR {
        int id PK
        string name
    }
    BOOK {
        int id PK
        string title
        string isbn
        string description
        string publisher
        int publication_year
        string resource_type
        string signature
        string target_age_group
        int author_id FK
        int copies
        int total_copies
        int storage_copies
        int open_stack_copies
        datetime created_at
    }
    BOOK_COPY {
        int id PK
        int book_id FK
        string inventory_code
        string status
        string location
        string access_type
        string condition_state
        datetime created_at
        datetime updated_at
    }
    BOOK_DIGITAL_ASSET {
        int id PK
        int book_id FK
        string filename
        string mime_type
        int size_bytes
        datetime created_at
    }
    CATEGORY {
        int id PK
        string name
    }
    BOOK_CATEGORY {
        int book_id PK,FK
        int category_id PK,FK
    }
    APP_USER {
        int id PK
        string email
        string name
        string roles
        string password
        string phone_number
        string address_line
        string city
        string postal_code
        bool blocked
        string blocked_reason
        bool verified
        bool pending_approval
        string membership_group
        int loan_limit
        datetime privacy_consent_at
        datetime created_at
        datetime updated_at
    }
    LOAN {
        int id PK
        int book_id FK
        int book_copy_id FK NULLABLE
        int user_id
        datetime borrowed_at
        datetime due_at
        datetime returned_at
        int extensions_count
        datetime last_extended_at
    }

    RESERVATION {
        int id PK
        int book_id FK
        int book_copy_id FK NULLABLE
        int user_id FK
        string status
        datetime reserved_at
        datetime expires_at
        datetime fulfilled_at
        datetime cancelled_at
    }

    FINE {
        int id PK
        int loan_id FK
        float amount
        string currency
        string reason
        datetime created_at
        datetime paid_at
    }

    NOTIFICATION_LOG {

        int id PK
        int user_id FK NULLABLE
        int loan_id FK NULLABLE
        int reservation_id FK NULLABLE
        string type
        string fingerprint
        string channel
        datetime created_at
        datetime sent_at
    }

    REGISTRATION_TOKEN {
        int id PK
        int user_id
        string token
        datetime expires_at
        datetime used_at
        datetime created_at
    }

    AUTHOR ||--o{ BOOK : "pisze"
    BOOK o{--o{ CATEGORY : "przynależy"
    BOOK ||--o{ BOOK_COPY : "ma egz."
    BOOK ||--o{ BOOK_DIGITAL_ASSET : "pliki"
    BOOK_COPY |o..o{ LOAN : "wypożyczenia"
    APP_USER ||--o{ LOAN : "wypożycza"
    BOOK ||--o{ RESERVATION : "rezerwacje"
    BOOK_COPY |o..o{ RESERVATION : "egzemplarz"
    APP_USER ||--o{ RESERVATION : "składa"
    LOAN ||--o{ FINE : "generuje kary"
    APP_USER ||--o{ NOTIFICATION_LOG : "powiadomienia"
    LOAN ||--o{ NOTIFICATION_LOG : "dot. wypożyczeń"
    RESERVATION ||--o{ NOTIFICATION_LOG : "dot. rezerwacji"
    APP_USER ||--o{ REGISTRATION_TOKEN : "tokeny"

```

> Diagram można podejrzeć w VS Code (Markdown Preview Mermaid Support) albo w [Mermaid Live Editor](https://mermaid.live/).

---

## Opis relacji i kluczowych pól

- **Author → Book (1:N)** – każdy autor ma wiele książek, książka wskazuje jednego autora.
- **Book ↔ Category (N:M)** – klasyczne tagowanie katalogu przez tabelę `book_category`.
- **Book → BookCopy (1:N)** – fizyczne egzemplarze przypisane do książki; usunięcie książki usuwa egzemplarze i cyfrowe zasoby.
- **BookCopy → Loan (0..N)** – wypożyczenie może mieć przypisany egzemplarz, ale FK jest opcjonalny (`SET NULL`) dla historycznych rekordów.
- **Book/User ↔ Reservation** – rezerwacja najpierw wskazuje książkę, a po przydziale egzemplarza ustawia `book_copy_id` (również `SET NULL`).
- **User → Loan/Reservation/Fine** – użytkownicy są stroną wszystkich procesów; usunięcie użytkownika kaskadowo usuwa wypożyczenia, rezerwacje i logi.
- **Loan → Fine (1:N)** – jedno wypożyczenie może wygenerować wiele kar.
- **User/Loan/Reservation → NotificationLog** – logujemy każdy wysłany komunikat, co umożliwia deduplikację oraz audyt.
- **User → RegistrationToken (1:N)** – proces weryfikacji kont zapisuje tokeny aktywacyjne z datą ważności oraz użycia.

### Najważniejsze atrybuty biznesowe

- Liczniki `copies`, `total_copies`, `storage_copies`, `open_stack_copies` są celowo **zdenormalizowane** i aktualizowane w `Book::recalculateInventoryCounters()` przy każdej zmianie stanów `BookCopy`.
- `book_copy.access_type` rozróżnia zasoby magazynowe, wolnego dostępu i tylko do czytelni.
- `app_user.membership_group`, `loan_limit`, `pending_approval`, `blocked` oraz `blocked_reason` kontrolują limity i status konta.
- `loan.extensions_count` i `last_extended_at` wspierają zasady przedłużeń i powiadomień.
- `reservation.status` (`ACTIVE`, `FULFILLED`, `CANCELLED`, `EXPIRED`) opisuje cykl życia rezerwacji.
- `notification_log.fingerprint` zabezpiecza przed wysłaniem duplikatów i jest powiązany z typem kanału (`channel`).
- `registration_token.expires_at` i `used_at` umożliwiają automatyczne wygaszanie linków aktywacyjnych.

---

## Zgodność z wymaganiami

- Model (z wyjątkiem świadomej denormalizacji liczników dostępności) zachowuje **3NF** i zgadza się z encjami Doctrine w katalogu `backend/src/Entity`.
- Fixtures (`backend/src/DataFixtures/AppFixtures.php`) dostarczają ponad 30 rekordów obejmujących autorów, książki, egzemplarze, użytkowników, wypożyczenia, rezerwacje, kary i logi powiadomień.
- Diagram obejmuje moduły wymagane w projekcie: katalog, zarządzanie egzemplarzami, wypożyczenia, rezerwacje, kary, rejestrację użytkowników oraz system powiadomień.

### Możliwe kierunki rozbudowy

1. **Audyt egzemplarzy** – dodatkowa tabela logująca zmiany statusów `BookCopy`.
2. **Preferencje powiadomień** – magazynowanie wyboru kanału (np. e-mail/SMS) po stronie użytkownika.
3. **Integracja płatności** – powiązanie `Fine` z modułem płatności online (np. identyfikator transakcji).

Na potrzeby obecnego sprintu dokument wiernie odzwierciedla schemat bazy wykorzystywany przez aplikację.
