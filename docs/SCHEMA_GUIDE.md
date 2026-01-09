# Biblioteka - Schema & Documentation Guide

## Overview

This document serves as a quick reference guide to the database schema and documentation artifacts for the Biblioteka library management system.

## Files & Locations

### 1. **Schema Definition** 
**File:** [`backend/schema_current.sql`](../backend/schema_current.sql)

**Purpose:** Complete SQL DDL (Data Definition Language) for the entire PostgreSQL database.

**Contents:**
- Table definitions with columns, types, and constraints
- Indexes (including full-text search and vector search)
- Foreign key relationships
- Unique constraints and sequences

**Usage:**
- Import directly into PostgreSQL to inspect current schema
- Use as reference for database compatibility
- Review as part of code review/audit process

**Size:** ~15 KB (schema only, no data)

---

### 2. **Database Architecture Documentation**
**File:** [`docs/DATABASE_ARCHITECTURE.md`](DATABASE_ARCHITECTURE.md)

**Purpose:** Comprehensive guide to the database design, organization, and rationale.

**Contents:**
- Module-by-module breakdown of all 30 tables
- Normalization notes (3NF compliance and optimizations)
- Key indexes and their purposes
- Data constraints and unique constraints
- Common query patterns and examples
- Performance considerations

**Best For:**
- Understanding overall schema organization
- Learning about module relationships
- Reviewing normalization decisions
- Performance optimization decisions

**Key Sections:**
1. Overview & Module Organization
2. Core relationships and normalization
3. Performance optimizations (cached aggregates, full-text search)
4. Common query examples
5. Future enhancement suggestions

---

### 3. **Entity Relationship Diagram (ERD)**
**File:** [`docs/ERD.md`](ERD.md)

**Purpose:** Visual and conceptual representation of the database structure.

**Contents:**
- ASCII art ERD showing all entity relationships
- Visual grouping by module (User, Catalog, Loans, Admin, etc.)
- Relationship cardinality (1:N, M:N, 1:1)
- Table statistics summary
- Design patterns used

**Best For:**
- Quick visual understanding of data relationships
- Learning entity names and relationships
- Understanding data flow and dependencies
- Presenting to stakeholders

**Visual Elements:**
- User & Authentication Layer
- Catalog & Content Layer
- Loans & Reservations Layer
- Ratings & Recommendations Layer
- Administration & Audit Layer
- Integrations & Notifications Layer
- Acquisitions & Budgeting Layer

---

### 4. **Initialization Data Script**
**File:** [`backend/init-db-expanded-v2.sql`](../backend/init-db-expanded-v2.sql)

**Purpose:** Complete database initialization with seed data (30 records per table).

**Contents:**
- Full schema definition (same as schema_current.sql)
- 30 test records for each core entity
- Realistic data for loans, reservations, ratings, etc.
- Age ranges, system settings, staff roles

**Size:** ~1.5 MB (includes substantial seed data)

**Usage:**
- Docker initialization via `docker compose up -d`
- Development and testing environments
- Load testing with realistic data volumes

---

## Quick Reference: Which File to Use?

| Question | File to Consult |
|----------|-----------------|
| "What tables exist in the database?" | `schema_current.sql` or `DATABASE_ARCHITECTURE.md` |
| "How are books and categories related?" | `ERD.md` (Visual) or `DATABASE_ARCHITECTURE.md` (Text) |
| "What are the indexes on the loan table?" | `DATABASE_ARCHITECTURE.md` |
| "What's the schema for creating a new database?" | `schema_current.sql` |
| "How do I query for overdue books?" | `DATABASE_ARCHITECTURE.md` (Common Queries section) |
| "Is this design normalized?" | `DATABASE_ARCHITECTURE.md` (Normalization section) |
| "Why does the book table have a 'copies' field?" | `DATABASE_ARCHITECTURE.md` (Performance Optimizations) |
| "What's the schema version and last update date?" | `schema_current.sql` (header) |

---

## Schema Highlights

### Table Count & Organization
- **Total Tables:** 30
- **Core Entities:** 7 (user, book, author, category, loan, reservation, fine)
- **Supporting Entities:** 23 (collections, ratings, integrations, audit, etc.)

### Modules
1. **User Management** (4 tables)
2. **Catalog & Content** (7 tables)
3. **Loans & Reservations** (3 tables)
4. **Ratings & Recommendations** (4 tables)
5. **Administration & Audit** (4 tables)
6. **Integrations & Notifications** (2 tables)
7. **Acquisitions & Budgeting** (5 tables)

### Key Features
- ✅ 3NF Normalized (with strategic cached aggregates)
- ✅ PostgreSQL 16 Compatible
- ✅ Full-text search via tsvector
- ✅ Vector embeddings for semantic search (1536-dim)
- ✅ Audit logging for compliance
- ✅ JSON columns for flexible configuration
- ✅ Comprehensive indexing for performance

---

## Documentation Structure in Docs Folder

```
docs/
├── DATABASE_ARCHITECTURE.md  ← Start here for comprehensive overview
├── ERD.md                    ← Visual diagrams and relationships
└── SCHEMA_GUIDE.md           ← This file
```

---

## Version Information

- **Schema Version:** 2.1.0
- **Database:** PostgreSQL 16
- **Last Updated:** January 9, 2026
- **Migration Strategy:** Doctrine Migrations (backend/migrations/)

---

## For Reviewers

When reviewing this codebase, reference these artifacts:

1. **Schema Review:** Check `backend/schema_current.sql` for DDL correctness
2. **Data Flow Review:** Use `docs/ERD.md` to understand entity relationships
3. **Normalization Review:** Read `DATABASE_ARCHITECTURE.md` Normalization section
4. **Query Performance:** Review indexes in `DATABASE_ARCHITECTURE.md`

---

## References

- **Doctrine Migrations:** [backend/migrations/](../backend/migrations/)
- **Entity Definitions:** [backend/src/Entity/](../backend/src/Entity/)
- **Repository Classes:** [backend/src/Repository/](../backend/src/Repository/)
- **API Documentation:** http://localhost:8000/api/docs (when running locally)

---

## Future Enhancements

Potential improvements to schema or documentation:

1. Interactive ERD (using tools like draw.io or PlantUML)
2. Separate read/write schema diagrams
3. Query performance analysis and optimization recommendations
4. Data migration guide for version upgrades
5. Backup and recovery procedures documentation

---

## Contact & Questions

For questions about the schema or database design:
1. Check the relevant documentation file above
2. Review Doctrine Entity definitions in `backend/src/Entity/`
3. Examine migrations in `backend/migrations/` for change history
4. Run `schema_current.sql` inspection queries against the database

