# Biblioteka - Database Architecture

**Schema Version:** 2.1.0  
**Database:** PostgreSQL 16  
**Last Updated:** January 9, 2026

## Overview

The Biblioteka library management system uses a normalized PostgreSQL database (3NF) with 35 core tables organized into logical modules. The schema supports:

- Book catalog management with full-text search (vector embeddings)
- Loan and reservation workflows with fine tracking
- User profiles with preference tracking
- Rating & recommendation system with user interaction history
- Curated book collections
- Acquisition & budgeting module
- Comprehensive audit logging
- System administration and notifications

## Database Modules

### 1. User Management

**Tables:**
- `app_user` - User profiles, authentication, preferences, and account settings
- `refresh_token` - JWT refresh token storage with revocation tracking
- `staff_role` - Role definitions for admin and librarian users
- `registration_token` - Email verification tokens for new registrations

**Key Features:**
- Role-based access control (ROLE_ADMIN, ROLE_LIBRARIAN, ROLE_USER)
- Account statuses: active, verified, blocked, pending_approval
- User embeddings for recommendation system (taste_embedding, 1536-dim vector)
- Preference storage: theme, language, font size, contact preferences
- Age range mapping for reader segmentation

### 2. Catalog & Content

**Tables:**
- `book` - Books with full-text search, embeddings, and availability counters
- `author` - Book authors
- `category` - Book categories (fiction, science fiction, biography, etc.)
- `book_category` - Many-to-many relationship between books and categories
- `book_copy` - Individual physical/digital copies with inventory codes
- `book_digital_asset` - Digital assets (PDFs, EPUBs, cover images, audiobooks)
- `age_range` - Predefined age ranges for reader segmentation

**Key Features:**
- Full-text search via `search_vector` (GIN index)
- Vector embeddings for semantic recommendations
- Copy-level status tracking: AVAILABLE, BORROWED, RESERVED, MAINTENANCE, WITHDRAWN
- Access types: OPEN_STACK, STORAGE, REFERENCE
- Computed availability counters: copies, total_copies, storage_copies, open_stack_copies
  - *Note:* These are cached aggregates updated by application logic; source of truth is `book_copy` table

### 3. Loans & Reservations

**Tables:**
- `loan` - Loan records with due dates, extensions, and return tracking
- `reservation` - Reservation requests with status and expiration
- `fine` - Late fees and penalties

**Key Features:**
- Loan extensions tracking (extensions_count, last_extended_at)
- Reservation statuses: active, fulfilled, cancelled, expired
- Fine reasons: przetrzymanie (late), uszkodzenie (damage), zgubienie (lost), administracyjna (admin fee)
- Overdue book detection via due_at timestamp

### 4. Ratings & Recommendations

**Tables:**
- `rating` - Book ratings (1-5 stars) with optional reviews
- `review` - Standalone reviews with rating aggregation
- `recommendation_feedback` - User feedback on recommendations (dismiss, saved, interested, not_relevant)
- `user_book_interaction` - Interaction tracking for recommendation training

**Key Features:**
- Unique constraint: one rating per user per book
- Recommendation engine training via feedback loops
- User-book interaction types: view, favorite, loan, rating
- Rating history maintained via created_at/updated_at timestamps

### 5. Collections & Favorites

**Tables:**
- `book_collection` - Curated collections created by librarians
- `collection_books` - Many-to-many relationship for collection membership
- `favorite` - User-marked favorite books

**Key Features:**
- Featured/unfeatured collections with display ordering
- Curator assignment (curated_by_id)
- Unique user-favorite constraint

### 6. Administration & Audit

**Tables:**
- `audit_logs` - Complete activity audit trail (logins, updates, deletions)
- `announcement` - System announcements with targeting and scheduling
- `backup_record` - Database backup history and status tracking
- `system_setting` - Application configuration key-value store
- `staff_role` - Role definitions with module permissions

**Key Features:**
- Detailed audit trail: entity_type, action, ip_address, old_values, new_values
- Announcements support: status (draft/published/archived), expiration, event scheduling
- Backup status tracking: success, running, failed

### 7. Integrations & Notifications

**Tables:**
- `integration_config` - Third-party service integrations (SMTP, SMS, S3)
- `notification_log` - Sent notification tracking with error handling

**Key Features:**
- Integration health status monitoring
- Notification channel tracking: email, SMS, push
- Fingerprinting for deduplication (fingerprint, channel unique constraint)

### 8. Acquisitions & Budgeting

**Tables:**
- `supplier` - Vendor information and contact details
- `acquisition_budget` - Annual/fiscal budgets with allocation tracking
- `acquisition_order` - Purchase orders with items JSON
- `acquisition_expense` - Expense tracking and budget reconciliation
- `weeding_record` - Collection weeding and disposition

**Key Features:**
- Budget allocation vs. spending tracking
- Order lifecycle: pending → ordered → received/cancelled
- Weeding actions: DISCARD, DONATE, REPAIR
- Supplier active/inactive status for procurement

## Entity Relationship Diagram (ERD)

### Core Relationships

```
User (app_user) ──┬─→ 1:N Loan
                  ├─→ 1:N Reservation
                  ├─→ 1:N Rating
                  ├─→ 1:N Review
                  ├─→ 1:N Favorite
                  ├─→ 1:N BookCollection (curated_by)
                  └─→ 1:N UserBookInteraction

Book ──────┬─→ 1:N BookCopy
           ├─→ 1:N Loan
           ├─→ 1:N Reservation
           ├─→ 1:N Rating
           ├─→ 1:N Review
           ├─→ 1:N Favorite
           ├─→ M:N Category (via book_category)
           ├─→ M:N BookCollection (via collection_books)
           ├─→ 1:N DigitalAsset
           ├─→ 1:N UserBookInteraction
           └─→ 1:N RecommendationFeedback

BookCopy ──┬─→ 1:N Loan
           ├─→ 1:N Reservation
           └─→ 1:N WeedingRecord
```

### Normalization Notes

**3NF Compliance:**
- All tables properly normalized with single-valued attributes
- Join tables used for many-to-many relationships
- No transitive dependencies

**Performance Optimizations:**
- Cached aggregates: `book.copies`, `book.total_copies`, `book.storage_copies`, `book.open_stack_copies`
  - Source of truth: `book_copy` table filtered by status
  - Updated by application logic; consider materialized view for strict 3NF
- Full-text search vector (GIN index): `book.search_vector`
- Vector embeddings for semantic search: `book.embedding`, `app_user.taste_embedding`
- Explicit sequence strategy (allocationSize=1) for ID generation

## Key Indexes

| Table | Index Name | Columns | Purpose |
|-------|-----------|---------|---------|
| app_user | UNIQ_88BDF3E9E7927C74 | email | Email uniqueness, login lookups |
| book | book_search_vector_idx | search_vector (GIN) | Full-text search performance |
| book_category | IDX_1FB30F9816A2B381 | book_id | Category filtering by book |
| loan | IDX_C5D30D03A76ED395 | user_id | Loan history by user |
| audit_logs | idx_audit_entity | entity_type, entity_id | Audit trail lookup |
| refresh_token | uniq_refresh_token | token | Token validation |

## Data Constraints

### Unique Constraints

- `app_user.email` - Email addresses are unique per user
- `refresh_token.token` - Tokens are unique and revocable
- `rating` - One rating per user per book (rating_user_book_unique)
- `recommendation_feedback` - One feedback per user per book
- `favorite` - One favorite per user per book (favorite_user_book_unique)
- `review` - One review per user per book (review_user_book_unique)
- `book_digital_asset.storage_name` - Unique file storage identifiers
- `book_copy.inventory_code` - Unique physical copy identifiers

## Common Queries

### 1. Find Available Books by Title & Category

```sql
SELECT b.id, b.title, b.copies, c.name
FROM book b
JOIN book_category bc ON b.id = bc.book_id
JOIN category c ON bc.category_id = c.id
WHERE b.search_vector @@ plainto_tsquery('simple', 'search_term')
  AND b.copies > 0
  AND c.name = 'Fiction'
ORDER BY b.title;
```

### 2. User Loan History with Due Status

```sql
SELECT l.id, b.title, u.name, l.borrowed_at, l.due_at, 
       CASE 
         WHEN l.returned_at IS NOT NULL THEN 'Returned'
         WHEN l.due_at < NOW() THEN 'OVERDUE'
         ELSE 'Active'
       END as status
FROM loan l
JOIN book b ON l.book_id = b.id
JOIN app_user u ON l.user_id = u.id
WHERE u.id = ?
ORDER BY l.due_at DESC;
```

### 3. Popular Books (Top Rated)

```sql
SELECT b.id, b.title, 
       AVG(r.rating) as avg_rating, 
       COUNT(r.id) as rating_count
FROM book b
LEFT JOIN rating r ON b.id = r.book_id
GROUP BY b.id, b.title
HAVING COUNT(r.id) >= 3
ORDER BY avg_rating DESC
LIMIT 10;
```

### 4. Budget Reconciliation

```sql
SELECT ab.name, ab.fiscal_year, ab.allocated_amount,
       COALESCE(SUM(ae.amount), 0) as total_expenses,
       ab.allocated_amount - COALESCE(SUM(ae.amount), 0) as remaining
FROM acquisition_budget ab
LEFT JOIN acquisition_expense ae ON ab.id = ae.budget_id
GROUP BY ab.id, ab.name, ab.fiscal_year, ab.allocated_amount;
```

## Schema Access

**Schema Definition:** [backend/schema_current.sql](../backend/schema_current.sql)

**Initialization with Data:** [backend/init-db-expanded-v2.sql](../backend/init-db-expanded-v2.sql)

**Doctrine Migrations:** [backend/migrations/](../backend/migrations/)

## Performance Considerations

1. **Read Optimization:** Full-text search and vector embeddings for semantic discovery
2. **Availability Counters:** Maintain aggregates at application level; monitor for drift
3. **Audit Logging:** Consider partitioning or archival for high-volume environments
4. **Notification Deduplication:** Fingerprinting prevents duplicate notifications

## Future Enhancements

- Materialized view for strict 3NF (replace cached book counters)
- Partitioning of audit_logs by created_at for better query performance
- Additional indexes on frequently-filtered columns (loan.due_at, reservation.status)
- Full-text search language support beyond 'simple' config
