# 📚 Biblioteka - Documentation Index

Welcome to the Biblioteka documentation! This index helps you find the right documentation for your needs.

---

## 🚀 Quick Start

**New to the project?** Start here:
1. [README.md](../README.md) - Main project documentation
2. [Quick Start Guide](../README.md#-quick-start) - Get up and running in 5 minutes
3. [Test Credentials](../README.md#6-test-credentials) - Login to the application

---

## 📊 Audit & Quality Assurance

**Want to understand project quality?** Check audit reports:

| Document | Description | Audience |
|----------|-------------|----------|
| [📋 Executive Summary](AUDIT_EXECUTIVE_SUMMARY.md) | Quick overview, key metrics | Managers, Stakeholders |
| [📊 Detailed Audit](DETAILED_AUDIT_2026.md) | Full analysis of 14 criteria | Developers, Reviewers |
| [🔧 Fixes & Improvements](FIXES_AND_IMPROVEMENTS.md) | Action plan, completed fixes | Development Team |
| [🚨 Quick Fixes](QUICK_FIXES.md) | Priority actions (30-60 min) | Developers |

**Key Results:**
- ✅ Score: **99.3/100**
- ✅ All 14 criteria met
- ✅ Production ready
- ✅ 136+ Git commits
- ✅ 30 database tables
- ✅ 90%+ functionality

---

## 🗄️ Database & Architecture

**Need to understand the data model?**

| Document | Description | Best For |
|----------|-------------|----------|
| [🏗️ Database Architecture](DATABASE_ARCHITECTURE.md) | Complete schema overview, modules | Understanding data structure |
| [📐 ERD Diagram](ERD.md) | Visual entity relationships (ASCII) | Database design review |
| [📖 Schema Guide](SCHEMA_GUIDE.md) | Quick reference for developers | Day-to-day development |
| [💾 Schema SQL](../backend/schema_current.sql) | Full PostgreSQL DDL | Database setup, migration |

**Key Facts:**
- 30 tables organized in 7 modules
- 3NF normalized
- PostgreSQL 16 with pgvector extension
- Full-text search with tsvector
- Vector embeddings for AI recommendations

---

## 🛠️ Development Guides

**Setting up development environment?**

### Backend (PHP/Symfony)
1. [README - Backend Setup](../README.md#backend-setup) - Manual installation
2. [Backend .env Configuration](../backend/.env.example) - Environment variables
3. [Migrations](../backend/migrations/) - Database version control

### Frontend (React)
1. [README - Frontend Setup](../README.md#frontend-setup) - Manual installation
2. [Frontend .env Configuration](../frontend/.env.example) - Environment variables
3. [Tests](../frontend/tests/) - Frontend test suite

### Docker
1. [README - Docker Setup](../README.md#3-start-all-services) - Containerized environment
2. [docker-compose.yml](../docker-compose.yml) - Service orchestration

---

## 🔌 API Documentation

**Integrating with the API?**

| Resource | URL | Description |
|----------|-----|-------------|
| **Swagger UI** | http://localhost:8000/api/docs | Interactive API documentation |
| **OpenAPI JSON** | http://localhost:8000/api/docs.json | OpenAPI 3.0 specification |
| [API Endpoints](../README.md#key-endpoints) | README section | Quick endpoint reference |

**Key Features:**
- 50+ documented endpoints
- Request/Response schemas
- Authentication examples
- Error codes and handling
- HATEOAS links

---

## 🧪 Testing

**Running tests?**

### Backend Tests
```powershell
cd backend
vendor/bin/phpunit                    # All tests
vendor/bin/phpunit tests/Unit         # Unit tests only
vendor/bin/phpunit tests/Integration  # Integration tests
vendor/bin/phpunit --coverage-html var/coverage  # With coverage
```

### Frontend Tests
```powershell
cd frontend
npm test              # All tests (watch mode)
npm test -- --run     # Run once
npm test -- --coverage # With coverage
npm run test:e2e      # E2E tests (Playwright)
```

---

## 📝 Contributing

**Want to contribute?**

1. [CONTRIBUTING.md](../CONTRIBUTING.md) - Contribution guidelines
2. [CHANGELOG.md](../CHANGELOG.md) - Version history
3. [Code Standards](FIXES_AND_IMPROVEMENTS.md#4-długie-metody-w-kodzie) - Coding conventions

**Key Standards:**
- PHP: PSR-12 coding standard
- JavaScript: ESLint configuration
- Commits: Conventional Commits format (`feat:`, `fix:`, `chore:`)
- Branches: Feature branches with descriptive names

---

## 🔐 Security

**Security-related documentation:**

### Authentication
- [JWT Authentication](../README.md#-authentication) - Token-based auth
- [User Roles](../README.md#default-roles) - RBAC system
- [Token Refresh](../README.md#user-authentication) - Refresh token flow

### Configuration
- Backend API Secret: `backend/.env` → `API_SECRET`
- JWT Keys: `backend/config/jwt/`
- CORS Settings: `backend/config/packages/nelmio_cors.yaml`

---

## 🎯 Feature-Specific Guides

### AI/ML Recommendations
- Database: `book.embedding` column (vector, 1536-dim)
- Service: `backend/src/Service/PersonalizedRecommendationService.php`
- API: `GET /api/recommendations/personalized`

### Full-Text Search
- Database: `book.search_vector` column (tsvector)
- Indexing: GIN index on search_vector
- API: `GET /api/books/search?q=query`

### Async Jobs
- Configuration: `config/packages/messenger.yaml`
- Workers: `php bin/console messenger:consume async`
- Events: `backend/src/Event/` and `backend/src/EventSubscriber/`

### Audit Logging
- Table: `audit_logs`
- Service: `backend/src/Service/AuditService.php`
- Events: Login, CRUD operations, admin actions

---

## 🆘 Troubleshooting

**Having issues?**

1. [README - Troubleshooting](../README.md#-troubleshooting) - Common issues
2. [GitHub Issues](https://github.com/your-username/biblioteka/issues) - Report bugs
3. [Detailed Audit](DETAILED_AUDIT_2026.md) - Known limitations

**Common Problems:**
- Port conflicts (3000, 8000, 5432)
- Database connection errors
- JWT token issues
- CORS problems

---

## 📊 Project Statistics

**As of January 9, 2026:**

| Metric | Value |
|--------|-------|
| **Lines of Code** | ~50,000+ |
| **Git Commits** | 136+ |
| **Backend Controllers** | 25+ |
| **Backend Services** | 16+ |
| **Database Tables** | 30 |
| **API Endpoints** | 50+ |
| **Frontend Pages** | 20+ |
| **Frontend Components** | 30+ |
| **Test Coverage** | ~70-80% |
| **Documentation Lines** | 2,000+ |

---

## 🗺️ Project Roadmap

**Future improvements:**

### Short Term (Next Sprint)
- [ ] Graphical ERD diagram (PNG/SVG)
- [ ] Conditional console.logs (DEV only)
- [ ] ESLint strict rules
- [ ] ARIA labels improvements

### Medium Term (Next Month)
- [ ] Acquisition module frontend
- [ ] Weeding module frontend
- [ ] Test coverage >90%
- [ ] Dark mode

### Long Term (Next Quarter)
- [ ] GraphQL API
- [ ] Error tracking (Sentry)
- [ ] Performance optimization
- [ ] Mobile app (React Native)

See [FIXES_AND_IMPROVEMENTS.md](FIXES_AND_IMPROVEMENTS.md) for detailed action plan.

---

## 📞 Support & Contact

**Need help?**

- 📧 GitHub Issues - Bug reports and feature requests
- 📚 Documentation - This folder
- 💬 Code Comments - Inline documentation in source code
- 🔍 API Docs - http://localhost:8000/api/docs

---

## 📄 License

This project is licensed under the MIT License. See [LICENSE](../LICENSE) file for details.

---

**Last Updated:** January 9, 2026  
**Version:** 2.1.1  
**Status:** 🟢 Production Ready

---

[← Back to Main README](../README.md)
