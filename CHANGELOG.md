# Changelog

All notable changes to the Biblioteka project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- API versioning (`/api/v1/`)
- Rate limiting for API endpoints
- TypeScript migration for frontend
- E2E tests with Playwright
- CI/CD pipeline (GitHub Actions)

## [2.1.0] - 2026-01-09

### Added
- Comprehensive ERD documentation with ASCII diagrams
- Database architecture guide with normalization notes
- Schema guide for developers
- Complete audit report for all 14 criteria
- CHANGELOG.md for version tracking
- CONTRIBUTING.md with contribution guidelines

### Changed
- README completely rewritten with professional structure
- Added badges for technologies
- Improved installation instructions (Docker + Manual)
- Enhanced troubleshooting section
- Better project structure documentation

### Fixed
- Documentation links in README
- Schema export (`schema_current.sql`)
- API documentation completeness

## [2.0.0] - 2025-12-25

### Added
- Vector embeddings for AI-powered recommendations (pgvector)
- Personalized recommendation engine using user taste profiles
- Full-text search with PostgreSQL tsvector
- Curated book collections
- Recommendation feedback system
- User book interaction tracking
- Age range support for readers

### Changed
- Upgraded to PostgreSQL 16
- Migration to PHP 8.2 with modern syntax
- React 18 with concurrent features
- Improved caching strategy

## [1.1.0] - 2025-11-15

### Added
- Acquisitions module (budgets, orders, suppliers)
- Weeding records for collection management
- Backup tracking system
- Integration configuration management
- Notification logging with deduplication
- Staff roles with module permissions
- System settings management

### Changed
- Enhanced admin panel with new modules
- Improved librarian workflows
- Better audit logging

### Fixed
- Fine calculation for overdue loans
- Reservation queue ordering
- Email notification reliability

## [1.0.0] - 2025-10-01

### Added
- Core catalog management (books, authors, categories)
- Book copy tracking with inventory codes
- Loan system with due dates and extensions
- Reservation system with queue management
- Fine calculation and payment tracking
- User authentication with JWT
- Role-based access control (User, Librarian, Admin)
- Favorites and ratings
- Reviews with ratings
- Announcements system
- Audit logging
- REST API with OpenAPI documentation
- Swagger UI for API exploration
- React 18 frontend with Vite
- Responsive design with mobile support
- Docker Compose for development
- Symfony Messenger for async jobs
- RabbitMQ integration
- Email notifications
- Full-text search
- Pagination and filtering
- Error handling and validation

### Security
- JWT authentication with refresh tokens
- Password hashing with bcrypt
- CORS configuration
- API secret header support
- Token revocation support
- IP address and user agent tracking

## [0.5.0] - 2025-09-01

### Added
- Initial project structure
- Symfony 6.4 backend setup
- React 18 frontend setup
- PostgreSQL database
- Docker configuration
- Basic entity models
- Initial migrations

---

## Version Numbering

- **Major version** (X.0.0): Breaking changes, major features
- **Minor version** (0.X.0): New features, backwards compatible
- **Patch version** (0.0.X): Bug fixes, minor improvements

## Links

- [Repository](https://github.com/your-username/biblioteka)
- [Issues](https://github.com/your-username/biblioteka/issues)
- [Pull Requests](https://github.com/your-username/biblioteka/pulls)
