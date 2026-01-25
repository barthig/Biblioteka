# Contributing to Biblioteka

Thank you for your interest in contributing to Biblioteka! This document provides guidelines and instructions for contributing to the project.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Commit Messages](#commit-messages)
- [Pull Request Process](#pull-request-process)
- [Testing](#testing)
- [Documentation](#documentation)

---

## Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inclusive environment for all contributors. We expect all participants to:

- Be respectful and considerate
- Welcome diverse perspectives
- Provide constructive feedback
- Focus on what is best for the community
- Show empathy towards others

### Unacceptable Behavior

- Harassment, discrimination, or offensive comments
- Personal attacks or trolling
- Publishing private information without consent
- Any conduct that could be considered inappropriate in a professional setting

---

## Getting Started

### Prerequisites

Before contributing, ensure you have:

- Git installed and configured
- Docker Desktop (for containerized development)
- OR: PHP 8.2+, Node.js 18+, PostgreSQL 16+, Composer, npm

### Fork and Clone

1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/YOUR-USERNAME/biblioteka.git
   cd biblioteka
   ```
3. Add upstream remote:
   ```bash
   git remote add upstream https://github.com/original-owner/biblioteka.git
   ```

### Set Up Development Environment

#### Using Docker (Recommended)

```bash
docker compose up -d
```

#### Manual Setup

**Backend:**
```bash
cd backend
composer install
php bin/console doctrine:migrations:migrate
php -S 127.0.0.1:8000 -t public
```

**Frontend:**
```bash
cd frontend
npm install
npm run dev
```

---

## Development Workflow

### 1. Create a Branch

Always create a new branch for your work:

```bash
git checkout -b feature/my-feature-name
# or
git checkout -b fix/issue-description
```

**Branch naming conventions:**
- `feature/` — New features
- `fix/` — Bug fixes
- `docs/` — Documentation updates
- `refactor/` — Code refactoring
- `test/` — Test additions/updates
- `chore/` — Build/config changes

### 2. Make Your Changes

- Write clean, readable code
- Follow existing code style
- Add tests for new functionality
- Update documentation as needed
- Keep commits focused and atomic

### 3. Test Your Changes

**Backend:**
```bash
cd backend
vendor/bin/phpunit
vendor/bin/phpstan analyse
```

**Frontend:**
```bash
cd frontend
npm test
npm run lint
```

### 4. Commit Your Changes

Follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

```bash
git add .
git commit -m "feat: add book recommendation engine"
```

See [Commit Messages](#commit-messages) section for details.

### 5. Push and Create Pull Request

```bash
git push origin feature/my-feature-name
```

Then create a Pull Request on GitHub.

---

## Coding Standards

### PHP (Backend)

- Follow **PSR-12** coding standard
- Use **PHP 8.2** features (typed properties, enums, attributes)
- Type hint all parameters and return types
- Write **PHPDoc** for complex methods
- Use **strict types**: `declare(strict_types=1);`
- Keep methods short and focused (< 30 lines)
- Avoid "magic" numbers/strings — use constants

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Service;

final class BookService
{
    public function __construct(
        private readonly BookRepository $repository
    ) {}

    /**
     * Find books matching the given criteria.
     *
     * @return Book[]
     */
    public function findBooks(string $query): array
    {
        return $this->repository->search($query);
    }
}
```

### JavaScript/React (Frontend)

- Follow **ESLint** configuration
- Use **functional components** with hooks
- Prefer **arrow functions**
- Use **destructuring** where appropriate
- Keep components small and focused
- Extract reusable logic into **custom hooks**
- Use **PropTypes** or TypeScript for type checking

**Example:**
```jsx
import React, { useState, useEffect } from 'react'
import PropTypes from 'prop-types'

export default function BookList({ filters }) {
  const [books, setBooks] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    loadBooks()
  }, [filters])

  async function loadBooks() {
    setLoading(true)
    try {
      const data = await fetchBooks(filters)
      setBooks(data)
    } finally {
      setLoading(false)
    }
  }

  if (loading) return <div>Loading...</div>

  return (
    <div>
      {books.map(book => (
        <BookItem key={book.id} book={book} />
      ))}
    </div>
  )
}

BookList.propTypes = {
  filters: PropTypes.object.isRequired
}
```

### SQL

- Use **lowercase** for keywords
- **Indent** subqueries
- Use **meaningful aliases**
- Add **comments** for complex queries
- Always use **parameterized queries** (prevent SQL injection)

---

## Commit Messages

Follow the **Conventional Commits** specification:

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

| Type | Description |
|------|-------------|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation changes |
| `style` | Code style changes (formatting, missing semi-colons, etc.) |
| `refactor` | Code refactoring (no functional changes) |
| `perf` | Performance improvements |
| `test` | Adding or updating tests |
| `chore` | Build process, dependencies, tooling |
| `ci` | CI/CD configuration |
| `revert` | Revert previous commit |

### Scope (optional)

Scope indicates the area of the codebase:
- `api` — Backend API
- `ui` — Frontend UI
- `db` — Database
- `auth` — Authentication
- `docs` — Documentation
- `config` — Configuration

### Examples

```
feat(api): add book recommendation endpoint

Implements collaborative filtering algorithm for personalized
book recommendations based on user ratings and borrowing history.

Closes #123
```

```
fix(ui): correct pagination on books page

Previous implementation failed when total pages exceeded 10.
Fixed off-by-one error in pagination component.
```

```
docs: update README with Docker instructions

Added step-by-step guide for running the application with
Docker Compose, including environment configuration.
```

### Rules

- Use **imperative mood** in subject ("add" not "added")
- Don't capitalize first letter
- No period at the end of subject
- Limit subject to 50 characters
- Wrap body at 72 characters
- Reference issues: `Closes #123`, `Fixes #456`

---

## Pull Request Process

### Before Submitting

1. ✅ Ensure all tests pass
2. ✅ Run linters and fix issues
3. ✅ Update documentation
4. ✅ Add tests for new features
5. ✅ Rebase on latest `main` branch
6. ✅ Write clear commit messages
7. ✅ Update CHANGELOG.md (for significant changes)

### Pull Request Template

```markdown
## Description
Brief description of the changes.

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Related Issue
Closes #123

## Testing
Describe how to test the changes.

## Checklist
- [ ] Tests pass locally
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Comments added for complex code
- [ ] Documentation updated
- [ ] No new warnings generated
```

### Review Process

1. **Automated Checks** — CI/CD pipeline runs tests and linters
2. **Code Review** — At least one maintainer reviews the code
3. **Feedback** — Address review comments
4. **Approval** — Maintainer approves the PR
5. **Merge** — Maintainer merges the PR

---

## Testing

### Backend Tests

```bash
cd backend

# Run all tests
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit tests/Unit
vendor/bin/phpunit tests/Integration

# Run with coverage
vendor/bin/phpunit --coverage-html var/coverage

# Static analysis
vendor/bin/phpstan analyse
```

### Frontend Tests

```bash
cd frontend

# Run all tests
npm test

# Watch mode
npm test -- --watch

# Coverage
npm test -- --coverage
```

### Writing Tests

**Backend (PHPUnit):**
```php
public function testCreateBook(): void
{
    $book = new Book();
    $book->setTitle('Test Book');
    
    $this->assertSame('Test Book', $book->getTitle());
}
```

**Frontend (Vitest):**
```javascript
import { render, screen } from '@testing-library/react'
import BookItem from './BookItem'

test('renders book title', () => {
  const book = { id: 1, title: 'Test Book' }
  render(<BookItem book={book} />)
  
  expect(screen.getByText('Test Book')).toBeInTheDocument()
})
```

---

## Documentation

### Code Documentation

- **PHP:** Use PHPDoc for all public methods
- **JavaScript:** Use JSDoc for complex functions
- **README:** Keep updated with new features
- **API:** Update OpenAPI specs when changing endpoints

### PHPDoc Example

```php
/**
 * Creates a new loan for the given user and book.
 *
 * @param User $user The user borrowing the book
 * @param Book $book The book being borrowed
 * @param int $durationDays Loan duration in days
 * 
 * @return Loan The created loan
 * 
 * @throws LoanLimitExceededException When user has too many active loans
 * @throws BookNotAvailableException When book has no available copies
 */
public function createLoan(User $user, Book $book, int $durationDays): Loan
{
    // Implementation
}
```

### JSDoc Example

```javascript
/**
 * Fetches books matching the given filters.
 * 
 * @param {Object} filters - Search filters
 * @param {string} [filters.query] - Search query
 * @param {number} [filters.page=1] - Page number
 * @param {number} [filters.limit=20] - Items per page
 * @returns {Promise<{items: Book[], meta: Object}>} Books and pagination metadata
 * @throws {ApiError} When the request fails
 */
async function fetchBooks(filters) {
  // Implementation
}
```

---

## Questions?

If you have questions or need help:

1. Check existing [Issues](https://github.com/your-username/biblioteka/issues)
2. Read the [Documentation](docs/)
3. Create a new [Discussion](https://github.com/your-username/biblioteka/discussions)

---

## License

By contributing to Biblioteka, you agree that your contributions will be licensed under the MIT License.

Thank you for contributing! 🎉
