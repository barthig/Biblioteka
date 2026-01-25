# 🛠️ IMPLEMENTACJA REKOMENDACJI - KROK PO KROKU

Przewodnik do wdrażania wszystkich sugerowanych ulepszeń.

---

## 1️⃣ KRITYCZNE DZIAŁANIA

### 1.1 Setup GitHub Actions - CI/CD Pipeline

**Krok 1: Utwórz plik workflow**

```bash
mkdir -p .github/workflows
```

**Krok 2: Backend Tests Workflow**

`.github/workflows/backend-tests.yml`:
```yaml
name: Backend Tests & Lint

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: pgvector/pgvector:pg16
        env:
          POSTGRES_USER: test
          POSTGRES_PASSWORD: test
          POSTGRES_DB: biblioteka_test
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 5432:5432

      rabbitmq:
        image: rabbitmq:3.13-alpine
        env:
          RABBITMQ_DEFAULT_USER: test
          RABBITMQ_DEFAULT_PASS: test
        options: >-
          --health-cmd rabbitmq-diagnostics status
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 5672:5672

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP 8.2
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: pdo_pgsql, redis, amqp
          coverage: xdebug

      - name: Get Composer Cache
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

      - name: Cache Composer
        uses: actions/cache@v3
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-

      - name: Install dependencies
        working-directory: backend
        run: composer install -q --no-ansi --no-interaction --prefer-dist

      - name: Create .env.test
        working-directory: backend
        run: |
          echo 'APP_ENV=test' > .env.test.local
          echo 'DATABASE_URL="postgresql://test:test@localhost:5432/biblioteka_test?serverVersion=16"' >> .env.test.local
          echo 'MESSENGER_TRANSPORT_DSN=amqp://test:test@localhost:5672/%2f/messages' >> .env.test.local

      - name: Run migrations
        working-directory: backend
        run: php bin/console doctrine:migrations:migrate --no-interaction

      - name: Run tests
        working-directory: backend
        run: php bin/phpunit --coverage-text=coverage.txt
        continue-on-error: true

      - name: Run PHPStan
        working-directory: backend
        run: vendor/bin/phpstan analyse

      - name: Upload test results
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: phpunit-results
          path: backend/coverage.txt

  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2

      - name: Install dependencies
        working-directory: backend
        run: composer install -q

      - name: Check coding standards
        working-directory: backend
        run: vendor/bin/php-cs-fixer fix --dry-run --diff . || true
```

**Krok 3: Frontend Tests Workflow**

`.github/workflows/frontend-tests.yml`:
```yaml
name: Frontend Tests & Lint

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup Node 18
        uses: actions/setup-node@v3
        with:
          node-version: 18
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json

      - name: Install dependencies
        working-directory: frontend
        run: npm ci

      - name: Run ESLint
        working-directory: frontend
        run: npm run lint

  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup Node 18
        uses: actions/setup-node@v3
        with:
          node-version: 18
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json

      - name: Install dependencies
        working-directory: frontend
        run: npm ci

      - name: Run unit tests
        working-directory: frontend
        run: npm run test:run

      - name: Run E2E tests
        working-directory: frontend
        run: npm run test:e2e

      - name: Upload test results
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: playwright-results
          path: frontend/test-results/

  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup Node 18
        uses: actions/setup-node@v3
        with:
          node-version: 18
          cache: 'npm'

      - name: Install dependencies
        working-directory: frontend
        run: npm ci

      - name: Build production
        working-directory: frontend
        run: npm run build

      - name: Upload build artifacts
        uses: actions/upload-artifact@v3
        with:
          name: frontend-build
          path: frontend/dist/
```

---

### 1.2 Custom Exception Hierarchy

**Krok 1: Utwórz strukturę folderów**

```bash
mkdir -p backend/src/Exception/{Domain,Validation,Authorization,Infrastructure}
```

**Krok 2: Base Exception**

`backend/src/Exception/ExceptionInterface.php`:
```php
<?php

namespace App\Exception;

use Throwable;

interface ExceptionInterface extends Throwable
{
    public function getErrorCode(): string;
    public function getHttpStatusCode(): int;
    public function toApiResponse(): array;
}
```

**Krok 3: Application Exception Base**

`backend/src/Exception/ApplicationException.php`:
```php
<?php

namespace App\Exception;

use Exception;

abstract class ApplicationException extends Exception implements ExceptionInterface
{
    protected string $errorCode = 'INTERNAL_ERROR';
    protected int $httpStatusCode = 500;

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function toApiResponse(): array
    {
        return [
            'error' => [
                'code' => $this->getErrorCode(),
                'message' => $this->getMessage(),
                'details' => [],
            ],
        ];
    }
}
```

**Krok 4: Domain Exceptions**

`backend/src/Exception/Domain/BookNotFoundException.php`:
```php
<?php

namespace App\Exception\Domain;

use App\Exception\ApplicationException;

class BookNotFoundException extends ApplicationException
{
    protected string $errorCode = 'BOOK_NOT_FOUND';
    protected int $httpStatusCode = 404;

    public function __construct(string $bookId = '')
    {
        parent::__construct(
            $bookId ? "Book '{$bookId}' not found" : 'Book not found'
        );
    }
}
```

`backend/src/Exception/Domain/LoanOverdueException.php`:
```php
<?php

namespace App\Exception\Domain;

use App\Exception\ApplicationException;

class LoanOverdueException extends ApplicationException
{
    protected string $errorCode = 'LOAN_OVERDUE';
    protected int $httpStatusCode = 400;

    public function __construct(string $loanId = '')
    {
        parent::__construct(
            $loanId ? "Loan '{$loanId}' is overdue" : 'Loan is overdue'
        );
    }
}
```

**Krok 5: Validation Exceptions**

`backend/src/Exception/Validation/InvalidEmailException.php`:
```php
<?php

namespace App\Exception\Validation;

use App\Exception\ApplicationException;

class InvalidEmailException extends ApplicationException
{
    protected string $errorCode = 'INVALID_EMAIL';
    protected int $httpStatusCode = 422;

    public function __construct(string $email = '')
    {
        parent::__construct($email ? "Email '{$email}' is invalid" : 'Invalid email');
    }
}
```

**Krok 6: Update EventSubscriber**

`backend/src/EventSubscriber/ApiExceptionSubscriber.php`:
```php
<?php

namespace App\EventSubscriber;

use App\Exception\ExceptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onException', 10]];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ExceptionInterface) {
            $response = new JsonResponse(
                $exception->toApiResponse(),
                $exception->getHttpStatusCode()
            );

            $event->setResponse($response);
        }
    }
}
```

---

### 1.3 API Interceptors/Middleware (Frontend)

**Krok 1: Utwórz strukturę**

```bash
mkdir -p frontend/src/api/{client,middleware,interceptors}
```

**Krok 2: API Client**

`frontend/src/api/client.js`:
```javascript
export class ApiClient {
  constructor(config = {}) {
    this.baseURL = config.baseURL || 'http://localhost:8000'
    this.timeout = config.timeout || 30000
    this.retries = config.retries || 3
    this.interceptors = config.interceptors || []
  }

  async request(path, options = {}) {
    const url = this._buildUrl(path)
    const config = this._buildConfig(options)

    // Execute request with retries
    for (let attempt = 0; attempt <= this.retries; attempt++) {
      try {
        let response = await fetch(url, config)

        // Execute response interceptors
        for (const interceptor of this.interceptors) {
          response = await interceptor.response?.(response, this) || response
        }

        if (!response.ok) {
          throw new ApiError(response)
        }

        return await response.json()
      } catch (error) {
        // Execute error interceptors
        for (const interceptor of this.interceptors) {
          await interceptor.error?.(error, this)
        }

        if (attempt === this.retries) {
          throw error
        }

        // Exponential backoff
        await new Promise(resolve => 
          setTimeout(resolve, Math.pow(2, attempt) * 1000)
        )
      }
    }
  }

  _buildUrl(path) {
    if (path.startsWith('http')) return path
    const base = this.baseURL.replace(/\/$/, '')
    const normalizedPath = path.startsWith('/') ? path : `/${path}`
    return `${base}${normalizedPath}`
  }

  _buildConfig(options) {
    return {
      ...options,
      headers: this._buildHeaders(options.headers || {}),
      signal: AbortSignal.timeout(this.timeout),
    }
  }

  _buildHeaders(headers) {
    return { 'Content-Type': 'application/json', ...headers }
  }
}

export class ApiError extends Error {
  constructor(response) {
    super(`API Error: ${response.status}`)
    this.status = response.status
    this.response = response
  }
}
```

**Krok 3: Interceptory**

`frontend/src/api/interceptors/auth.js`:
```javascript
export const authInterceptor = {
  request: async (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers = config.headers || {}
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },

  response: async (response) => response,

  error: async (error, client) => {
    if (error.response?.status === 401) {
      // Try to refresh token
      try {
        const refreshToken = localStorage.getItem('refreshToken')
        const response = await fetch(`${client.baseURL}/api/auth/refresh`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ refreshToken }),
        })

        if (response.ok) {
          const { token } = await response.json()
          localStorage.setItem('token', token)
          // Retry original request
          return Promise.reject(error)
        }
      } catch (e) {
        // Logout
        localStorage.removeItem('token')
        localStorage.removeItem('refreshToken')
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  },
}
```

`frontend/src/api/interceptors/logging.js`:
```javascript
export const loggingInterceptor = {
  request: async (config) => {
    console.log(`[API] ${config.method || 'GET'} ${config.url}`)
    return config
  },

  response: async (response) => {
    console.log(`[API] ${response.status} ${response.url}`)
    return response
  },

  error: async (error) => {
    console.error(`[API Error] ${error.message}`)
    return Promise.reject(error)
  },
}
```

**Krok 4: Index**

`frontend/src/api/index.js`:
```javascript
import { ApiClient } from './client'
import { authInterceptor } from './interceptors/auth'
import { loggingInterceptor } from './interceptors/logging'

export const apiClient = new ApiClient({
  baseURL: import.meta.env.VITE_API_URL,
  timeout: 30000,
  retries: 3,
  interceptors: [authInterceptor, loggingInterceptor],
})

// Convenience method
export async function apiFetch(path, options = {}) {
  return apiClient.request(path, options)
}
```

**Krok 5: Update services**

```javascript
// Before: frontend/src/services/bookService.js
import { apiFetch } from '../api.js'

// After:
import { apiFetch } from '../api'

export const bookService = {
  getBooks: () => apiFetch('/api/books'),
  getBook: (id) => apiFetch(`/api/books/${id}`),
  // ... rest of methods
}
```

---

## 2️⃣ HIGH PRIORITY DZIAŁANIA

### 2.1 Route Guards (Frontend)

**Krok 1: Utwórz guards folder**

```bash
mkdir -p frontend/src/guards
```

**Krok 2: Base guards**

`frontend/src/guards/index.js`:
```javascript
export { requireAuth } from './requireAuth'
export { requireNoAuth } from './requireNoAuth'
export { requireRole } from './requireRole'
export { requirePermission } from './requirePermission'
```

**Krok 3: Auth Guard**

`frontend/src/guards/requireAuth.jsx`:
```javascript
import React from 'react'
import { Navigate, useLocation } from 'react-router-dom'
import { useAuthStore } from '../store/authStore'
import { LoadingSpinner } from '../components/common'

export function requireAuth(Component) {
  return function RequireAuthComponent(props) {
    const { isAuthenticated, loading } = useAuthStore()
    const location = useLocation()

    if (loading) return <LoadingSpinner />

    if (!isAuthenticated) {
      return <Navigate to="/login" state={{ from: location }} replace />
    }

    return <Component {...props} />
  }
}
```

**Krok 4: Role Guard**

`frontend/src/guards/requireRole.jsx`:
```javascript
import React from 'react'
import { Navigate } from 'react-router-dom'
import { useAuthStore } from '../store/authStore'

export function requireRole(roles = []) {
  return function RequireRoleComponent(Component) {
    return function WrappedComponent(props) {
      const { user } = useAuthStore()

      if (!user) return <Navigate to="/login" replace />

      const hasRole = roles.some(role => user.roles?.includes(role))

      if (!hasRole) {
        return <Navigate to="/" replace />
      }

      return <Component {...props} />
    }
  }
}
```

**Krok 5: Update routing**

`frontend/src/App.jsx`:
```javascript
import { requireAuth, requireRole } from './guards'

const ProtectedBooks = requireAuth(Books)
const AdminPanel = requireRole(['ROLE_ADMIN'])(AdminPanelComponent)

export default function App() {
  return (
    <Routes>
      <Route path="/books" element={<ProtectedBooks />} />
      <Route path="/admin" element={<AdminPanel />} />
      {/* ... */}
    </Routes>
  )
}
```

---

## 3️⃣ MEDIUM PRIORITY DZIAŁANIA

### 3.1 Barrel Exports (Frontend)

**Aktualizuj każdy folder komponentów:**

`frontend/src/components/books/index.js`:
```javascript
export { default as BookItem } from './BookItem.jsx'
export { default as BookCover } from './BookCover.jsx'
export { default as BookCard } from './BookCard.jsx'
export { default as StarRating } from './StarRating.jsx'
export { default as UserRecommendations } from './UserRecommendations.jsx'
export { default as SemanticSearch } from './SemanticSearch.jsx'
export { default as AnnouncementCard } from './AnnouncementCard.jsx'
```

`frontend/src/components/common/index.js`:
```javascript
export { default as Navbar } from './Navbar.jsx'
export { default as FilterPanel } from './FilterPanel.jsx'
export { default as LoadingSpinner } from './LoadingSpinner.jsx'
export { default as Modal } from './Modal.jsx'
export { default as Pagination } from './Pagination.jsx'
export { default as RequireRole } from './RequireRole.jsx'
export { default as SearchBar } from './SearchBar.jsx'
export { default as ErrorMessage } from './ErrorMessage.jsx'
export { default as SuccessMessage } from './SuccessMessage.jsx'
export { default as EmptyState } from './EmptyState.jsx'
```

**Usage:**
```javascript
// Before
import BookItem from '../components/books/BookItem.jsx'
import BookCover from '../components/books/BookCover.jsx'

// After
import { BookItem, BookCover } from '../components/books'
```

---

### 3.2 Service Interfaces (Backend)

**Przykład dla BookService:**

`backend/src/Service/Book/BookServiceInterface.php`:
```php
<?php

namespace App\Service\Book;

use App\Dto\ApiResponse;
use App\Exception\Domain\BookNotFoundException;
use App\Request\CreateBookRequest;
use App\Request\UpdateBookRequest;

interface BookServiceInterface
{
    /**
     * @throws BookNotFoundException
     */
    public function getBook(string $id): ApiResponse;

    public function getBooks(int $page = 1, int $limit = 20): ApiResponse;

    public function createBook(CreateBookRequest $request): ApiResponse;

    /**
     * @throws BookNotFoundException
     */
    public function updateBook(string $id, UpdateBookRequest $request): ApiResponse;

    /**
     * @throws BookNotFoundException
     */
    public function deleteBook(string $id): void;
}
```

`backend/src/Service/Book/BookService.php`:
```php
<?php

namespace App\Service\Book;

use App\Dto\ApiResponse;
use App\Exception\Domain\BookNotFoundException;
use App\Repository\BookRepository;
use App\Request\CreateBookRequest;
use App\Request\UpdateBookRequest;
use Doctrine\ORM\EntityManagerInterface;

class BookService implements BookServiceInterface
{
    public function __construct(
        private BookRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function getBook(string $id): ApiResponse
    {
        $book = $this->repository->find($id);

        if (!$book) {
            throw new BookNotFoundException($id);
        }

        return new ApiResponse(['data' => $book]);
    }

    // ... implement other methods
}
```

**Update DI:**

`backend/config/services.yaml`:
```yaml
services:
  App\Service\Book\BookServiceInterface: '@App\Service\Book\BookService'
  App\Service\Book\BookService:
    arguments:
      - '@App\Repository\BookRepository'
      - '@doctrine.orm.entity_manager'
```

---

### 3.3 CSS/Styling Organization

**Krok 1: Reorganize styles**

```bash
mkdir -p frontend/src/styles/{globals,components,layouts,pages}
```

**Krok 2: Variables**

`frontend/src/styles/globals/variables.css`:
```css
:root {
  /* Colors */
  --color-primary: #2c3e50;
  --color-secondary: #3498db;
  --color-success: #27ae60;
  --color-danger: #e74c3c;
  --color-warning: #f39c12;
  --color-light: #ecf0f1;
  --color-dark: #2c3e50;
  --color-gray-50: #f9fafb;
  --color-gray-100: #f3f4f6;
  --color-gray-200: #e5e7eb;
  --color-gray-300: #d1d5db;

  /* Typography */
  --font-family-base: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  --font-family-mono: 'SF Mono', Monaco, 'Cascadia Code', Roboto Mono, Consolas, Courier New, monospace;
  --font-size-xs: 0.75rem;
  --font-size-sm: 0.875rem;
  --font-size-base: 1rem;
  --font-size-lg: 1.125rem;
  --font-size-xl: 1.25rem;
  --font-size-2xl: 1.5rem;
  --font-size-3xl: 1.875rem;
  --line-height-tight: 1.25;
  --line-height-normal: 1.5;
  --line-height-relaxed: 1.75;

  /* Spacing */
  --spacing-xs: 0.25rem;
  --spacing-sm: 0.5rem;
  --spacing-md: 1rem;
  --spacing-lg: 1.5rem;
  --spacing-xl: 2rem;
  --spacing-2xl: 3rem;
  --spacing-3xl: 4rem;

  /* Shadows */
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
  --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.1);
  --shadow-2xl: 0 25px 50px rgba(0, 0, 0, 0.1);

  /* Transitions */
  --transition-fast: 150ms ease-in-out;
  --transition-normal: 300ms ease-in-out;
  --transition-slow: 500ms ease-in-out;

  /* Border radius */
  --radius-sm: 0.375rem;
  --radius-md: 0.5rem;
  --radius-lg: 0.75rem;
  --radius-xl: 1rem;
  --radius-full: 9999px;

  /* Z-index */
  --z-dropdown: 1000;
  --z-sticky: 1020;
  --z-fixed: 1030;
  --z-modal-backdrop: 1040;
  --z-modal: 1050;
  --z-popover: 1060;
  --z-tooltip: 1070;
}
```

**Krok 3: Main index**

`frontend/src/styles/main.css`:
```css
/* Global styles */
@import './globals/variables.css';
@import './globals/normalize.css';
@import './globals/base.css';

/* Component styles */
@import './components/buttons.css';
@import './components/cards.css';
@import './components/modals.css';
@import './components/forms.css';
@import './components/alerts.css';
@import './components/skeleton.css';

/* Layout styles */
@import './layouts/navbar.css';
@import './layouts/sidebar.css';
@import './layouts/grid.css';

/* Page-specific styles */
@import './pages/dashboard.css';
@import './pages/books.css';
@import './pages/admin.css';
```

**Krok 4: Update main.jsx**

```javascript
// Before - importing individual CSS files
import './styles.css'
import './styles/main.css'
import './styles/components.css'

// After - single import
import './styles/main.css'
```

---

## 📋 CHECKLIST DO ŚLEDZENIA

```markdown
### CRITICAL (Muszą być)
- [ ] Setup GitHub Actions CI/CD
- [ ] Create Exception Hierarchy
- [ ] Add API Interceptors

### HIGH (Bardzo ważne)
- [ ] Add Route Guards
- [ ] Create Service Interfaces
- [ ] Refactor large services

### MEDIUM (Ważne)
- [ ] Add Barrel Exports
- [ ] Create Layout Components
- [ ] Organize CSS with variables
- [ ] Add Validators folder

### LOW (Nice to have)
- [ ] Add Prettier formatter
- [ ] Consolidate Auth (Zustand only)
- [ ] Update documentation
```

---

**Szczegółowe wdrażanie wszystkich punktów w kolejnych fazach.**
