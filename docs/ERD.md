# Entity Relationship Diagram (ERD) - Biblioteka

## Visual ERD (ASCII Art)

```
┌─────────────────────────────────────────────────────────────────────┐
│                     BIBLIOTEKA DATABASE SCHEMA                       │
│                         (PostgreSQL 16)                              │
└─────────────────────────────────────────────────────────────────────┘

╔═══════════════════════════════════════════════════════════════════════╗
║                      USER & AUTHENTICATION LAYER                       ║
╚═══════════════════════════════════════════════════════════════════════╝

    ┌──────────────────┐
    │   app_user       │
    ├──────────────────┤
    │ id (PK)          │
    │ email (UNIQUE)   │
    │ password         │
    │ name             │
    │ roles (JSON)     │
    │ blocked          │
    │ verified         │
    │ membership_group │
    │ age_range_code→──┼───┐
    │ loan_limit       │   │
    │ taste_embedding  │   │
    └────────┬─────────┘   │
             │             │
      ┌──────┴──────────┐  │
      │                 │  │
      ↓                 ↓  ↓
    ┌──────────────┐  ┌──────────┐
    │refresh_token │  │age_range │
    ├──────────────┤  ├──────────┤
    │ id (PK)      │  │code (PK) │
    │ user_id (FK) │  │label     │
    │ token        │  │min_age   │
    │ token_hash   │  │max_age   │
    │ expires_at   │  │description
    │ is_revoked   │  └──────────┘
    └──────────────┘

    ┌──────────────────────┐
    │  staff_role          │
    ├──────────────────────┤
    │ id (PK)              │
    │ name (UNIQUE)        │
    │ role_key (UNIQUE)    │
    │ modules (JSON)       │
    └──────────────────────┘

    ┌──────────────────────┐
    │  registration_token  │
    ├──────────────────────┤
    │ id (PK)              │
    │ user_id (FK)→app_user│
    │ token (UNIQUE)       │
    │ expires_at           │
    │ used_at              │
    └──────────────────────┘


╔═══════════════════════════════════════════════════════════════════════╗
║                      CATALOG & CONTENT LAYER                          ║
╚═══════════════════════════════════════════════════════════════════════╝

    ┌──────────────┐        ┌──────────────┐
    │   author     │        │  category    │
    ├──────────────┤        ├──────────────┤
    │ id (PK)      │        │ id (PK)      │
    │ name (UNIQUE)│        │ name (UNIQUE)│
    └────────┬─────┘        └────────┬─────┘
             │                       │
             │                       │
             │                 ┌─────────────────┐
             │                 │ book_category   │
             │                 ├─────────────────┤
             │                 │ book_id (FK)    │
             │                 │ category_id(FK) │
             │                 └─────────────────┘
             │                       ↑
             │                       │
    ┌────────▼─────────────────────┐
    │   book                        │
    ├───────────────────────────────┤
    │ id (PK)                       │
    │ title                         │
    │ isbn                          │
    │ author_id (FK)→author         │
    │ target_age_group→age_range    │
    │ copies (cached)               │
    │ total_copies (cached)         │
    │ storage_copies (cached)       │
    │ open_stack_copies (cached)    │
    │ description                   │
    │ embedding (vector, 1536-dim)  │
    │ search_vector (tsvector)      │
    │ publisher                     │
    │ publication_year              │
    │ resource_type                 │
    │ signature                     │
    └────────┬──────────────────────┘
             │
             ├─→ (1:N) ┌──────────────────┐
             │         │  book_copy       │
             │         ├──────────────────┤
             │         │ id (PK)          │
             │         │ book_id (FK)     │
             │         │ inventory_code   │
             │         │ status           │
             │         │ location         │
             │         │ access_type      │
             │         │ condition_state  │
             │         └──────────────────┘
             │
             ├─→ (1:N) ┌──────────────────┐
             │         │book_digital_asset│
             │         ├──────────────────┤
             │         │ id (PK)          │
             │         │ book_id (FK)     │
             │         │ label            │
             │         │ mime_type        │
             │         │ storage_name     │
             │         └──────────────────┘
             │
             └─→ (M:N) ┌──────────────────┐
                       │book_collection   │
                       ├──────────────────┤
                       │ id (PK)          │
                       │ name             │
                       │ curated_by_id→──┐│
                       │ featured         ││
                       │ display_order    ││
                       └──────────────────┘│
                                           │
                         ┌─────────────────┘
                         │
                    ┌────▼─────────────┐
                    │collection_books  │
                    ├──────────────────┤
                    │collection_id(FK) │
                    │book_id (FK)      │
                    └──────────────────┘


╔═══════════════════════════════════════════════════════════════════════╗
║                   LOANS & RESERVATIONS LAYER                          ║
╚═══════════════════════════════════════════════════════════════════════╝

    ┌──────────────────┐
    │  loan            │
    ├──────────────────┤
    │ id (PK)          │
    │ user_id (FK)     │──────┐
    │ book_id (FK)     │──┐   │
    │ book_copy_id (FK)│  │   │
    │ borrowed_at      │  │   │
    │ due_at           │  │   │
    │ returned_at      │  │   │
    │ extensions_count │  │   │
    │ last_extended_at │  │   │
    └────────┬─────────┘  │   │
             │            │   │
             ├─→ (1:N)────┼───┼──→ app_user
             │            │   │
             │            └──→ book
             │
             └─→ (1:N) ┌──────────────┐
                       │     fine     │
                       ├──────────────┤
                       │ id (PK)      │
                       │ loan_id (FK) │
                       │ amount       │
                       │ reason       │
                       │ created_at   │
                       │ paid_at      │
                       └──────────────┘

    ┌──────────────────┐
    │  reservation     │
    ├──────────────────┤
    │ id (PK)          │
    │ user_id (FK)     │──────→ app_user
    │ book_id (FK)     │──────→ book
    │ book_copy_id(FK) │──────→ book_copy
    │ status           │
    │ reserved_at      │
    │ expires_at       │
    │ fulfilled_at     │
    │ cancelled_at     │
    │ expired_at       │
    └──────────────────┘

    ┌──────────────────┐
    │  review          │
    ├──────────────────┤
    │ id (PK)          │
    │ user_id (FK)     │──────→ app_user
    │ book_id (FK)     │──────→ book
    │ rating           │
    │ comment          │
    │ created_at       │
    │ updated_at       │
    └──────────────────┘


╔═══════════════════════════════════════════════════════════════════════╗
║              RATINGS & RECOMMENDATIONS LAYER                          ║
╚═══════════════════════════════════════════════════════════════════════╝

    ┌──────────────────┐
    │  rating          │
    ├──────────────────┤
    │ id (PK)          │
    │ user_id (FK)     │──────→ app_user
    │ book_id (FK)     │──────→ book
    │ rating (1-5)     │
    │ review           │
    │ created_at       │
    │ updated_at       │
    │ (UNIQUE: user+book)
    └──────────────────┘

    ┌──────────────────────────┐
    │  recommendation_feedback │
    ├──────────────────────────┤
    │ id (PK)                  │
    │ user_id (FK)             │──────→ app_user
    │ book_id (FK)             │──────→ book
    │ feedback_type            │
    │ created_at               │
    │ (UNIQUE: user+book)      │
    └──────────────────────────┘

    ┌──────────────────┐
    │  favorite        │
    ├──────────────────┤
    │ id (PK)          │
    │ user_id (FK)     │──────→ app_user
    │ book_id (FK)     │──────→ book
    │ created_at       │
    │ (UNIQUE: user+book)
    └──────────────────┘

    ┌──────────────────────────┐
    │  user_book_interaction   │
    ├──────────────────────────┤
    │ id (PK)                  │
    │ user_id (FK)             │──────→ app_user
    │ book_id (FK)             │──────→ book
    │ type                     │
    │ rating                   │
    │ created_at               │
    └──────────────────────────┘


╔═══════════════════════════════════════════════════════════════════════╗
║               ADMINISTRATION & AUDIT LAYER                            ║
╚═══════════════════════════════════════════════════════════════════════╝

    ┌──────────────────┐
    │  announcement    │
    ├──────────────────┤
    │ id (PK)          │
    │ created_by_id→──┐│
    │ title            ││
    │ content          ││
    │ type             ││
    │ status           ││
    │ is_pinned        ││
    │ created_at       ││
    │ published_at     ││
    │ expires_at       ││
    │ event_at         ││
    │ target_audience  ││
    └──────────────────┘│
                        │
                   ┌────▼──────────┐
                   │   app_user    │
                   └──────────────┘

    ┌──────────────────┐
    │  audit_logs      │
    ├──────────────────┤
    │ id (PK)          │
    │ entity_type      │
    │ entity_id        │
    │ action           │
    │ user_id (FK)     │──────→ app_user
    │ ip_address       │
    │ old_values       │
    │ new_values       │
    │ description      │
    │ created_at       │
    └──────────────────┘

    ┌──────────────────┐
    │  backup_record   │
    ├──────────────────┤
    │ id (PK)          │
    │ file_name        │
    │ file_path        │
    │ file_size        │
    │ status           │
    │ created_at       │
    │ initiated_by     │
    └──────────────────┘

    ┌──────────────────┐
    │system_setting    │
    ├──────────────────┤
    │ id (PK)          │
    │ setting_key      │
    │ setting_value    │
    │ value_type       │
    │ description      │
    └──────────────────┘


╔═══════════════════════════════════════════════════════════════════════╗
║            INTEGRATIONS & NOTIFICATIONS LAYER                         ║
╚═══════════════════════════════════════════════════════════════════════╝

    ┌─────────────────────┐
    │ integration_config  │
    ├─────────────────────┤
    │ id (PK)             │
    │ name                │
    │ provider            │
    │ enabled             │
    │ settings (JSON)     │
    │ last_status         │
    │ last_tested_at      │
    └─────────────────────┘

    ┌─────────────────────┐
    │ notification_log    │
    ├─────────────────────┤
    │ id (PK)             │
    │ user_id (FK)        │──────→ app_user
    │ type                │
    │ channel             │
    │ fingerprint         │
    │ payload             │
    │ status              │
    │ error_message       │
    │ sent_at             │
    └─────────────────────┘


╔═══════════════════════════════════════════════════════════════════════╗
║              ACQUISITIONS & BUDGETING LAYER                           ║
╚═══════════════════════════════════════════════════════════════════════╝

    ┌─────────────────────┐
    │    supplier         │
    ├─────────────────────┤
    │ id (PK)             │
    │ name                │
    │ contact_email       │
    │ contact_phone       │
    │ address_line        │
    │ city, country       │
    │ tax_identifier      │
    │ active              │
    └────────────┬────────┘
                 │
                 └─→ (1:N)┌────────────────────┐
                          │acquisition_order   │
                          ├────────────────────┤
                          │ id (PK)            │
                          │ supplier_id (FK)   │
                          │ budget_id (FK)→───┐│
                          │ reference_number   ││
                          │ title              ││
                          │ items (JSON)       ││
                          │ total_amount       ││
                          │ status             ││
                          │ ordered_at         ││
                          │ received_at        ││
                          │ cancelled_at       ││
                          └────────────────────┘│
                                                │
                                   ┌────────────┘
                                   │
                    ┌──────────────▼──────────┐
                    │acquisition_budget      │
                    ├────────────────────────┤
                    │ id (PK)                │
                    │ name                   │
                    │ fiscal_year            │
                    │ allocated_amount       │
                    │ spent_amount           │
                    │ currency               │
                    └────────────┬───────────┘
                                 │
                                 └─→ (1:N)┌──────────────────┐
                                          │acquisition_expense
                                          ├──────────────────┤
                                          │ id (PK)          │
                                          │ budget_id (FK)   │
                                          │ order_id (FK)    │
                                          │ amount           │
                                          │ type             │
                                          │ posted_at        │
                                          └──────────────────┘

    ┌────────────────────┐
    │ weeding_record     │
    ├────────────────────┤
    │ id (PK)            │
    │ book_id (FK)       │──────→ book
    │ book_copy_id (FK)  │──────→ book_copy
    │ processed_by_id→───┼────────→ app_user
    │ reason             │
    │ action             │
    │ condition_state    │
    │ notes              │
    │ removed_at         │
    └────────────────────┘
```

## Table Statistics

| Module | Tables | Purpose |
|--------|--------|---------|
| User Management | 4 | Authentication, roles, tokens |
| Catalog & Content | 7 | Books, authors, categories, copies, assets |
| Loans & Reservations | 3 | Borrowing operations, fines |
| Ratings & Recommendations | 4 | User feedback, recommendations, interactions |
| Administration & Audit | 4 | Announcements, audit logs, backups, settings |
| Integrations & Notifications | 2 | Third-party integrations, notifications |
| Acquisitions & Budgeting | 5 | Suppliers, orders, budgets, expenses, weeding |
| **TOTAL** | **30** | **Full library management system** |

## Relationship Summary

- **One-to-Many:** 24 relationships (e.g., User → Loans, Book → Copies)
- **Many-to-Many:** 4 relationships (Book ↔ Category, Book ↔ Collection, etc.)
- **One-to-One:** Implicit via unique foreign keys
- **Self-Referential:** None

## Key Design Patterns

1. **Soft Deletes:** Implemented via status columns (WITHDRAWN, CANCELLED, ARCHIVED)
2. **Audit Trail:** Complete entity tracking via audit_logs table
3. **Polymorphic Relationships:** audit_logs.entity_type handles multiple entity types
4. **JSON Columns:** Used for flexible configuration (staff_role.modules, integration_config.settings, acquisition_order.items)
5. **Vector Embeddings:** PostgreSQL vector extension for semantic search and recommendations
6. **Full-Text Search:** tsvector for book title/description search

## Referential Integrity

All foreign keys use:
- **ON DELETE:** Varies by relationship (CASCADE for M:M, RESTRICT for dependencies)
- **DEFERRABLE:** INITIALLY IMMEDIATE for data consistency
- **NOT NULL constraints:** Applied strategically to enforce business logic
