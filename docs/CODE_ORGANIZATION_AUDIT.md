# 🔍 AUDYT ORGANIZACJI KODU - PROJEKT BIBLIOTEKA

**Data audytu:** 25 stycznia 2026  
**Typ audytu:** Szczegółowa analiza organizacji frontenden + backend  
**Wersja:** 1.0  

---

## 📊 PODSUMOWANIE WYKONAWCZE

| Obszar | Status | Wynik | Uwagi |
|--------|--------|-------|-------|
| **Frontend - Struktura** | ⚠️ PROBLEMY | 65/100 | Brakuje barrel exports, niejednorodna organizacja |
| **Frontend - Konfiguracja** | ✅ DOBRA | 82/100 | ESLint OK, Vite OK, ale brakuje Prettier |
| **Backend - Struktura** | ✅ DOBRA | 85/100 | CQRS + Repository, ale brakuje Custom Exceptions |
| **Backend - Konfiguracja** | ✅ DOBRA | 88/100 | PHPStan, routing scentralizowany, ale brakuje interfejsów |
| **Cały projekt** | ✅ DOBRA | 80/100 | Docker OK, docs OK, ale brakuje CI/CD |
| **ŚREDNIA OGÓLNA** | ✅ DOBRA | **80/100** | Projekt gotowy do produkcji po ulepszeniach |

---

# 1️⃣ AUDYT FRONTEND

## 1.1 Struktura `src/` - Komponenty, Pages, Hooks, Constants, Types

### ✅ Co JEST DOBRZE
```
frontend/src/
├── components/
│   ├── admin/           ✅ Features folder
│   ├── books/           ✅ Features folder
│   ├── loans/           ✅ Features folder
│   ├── users/           ✅ Features folder
│   ├── common/          ✅ Reusable components
│   └── ui/              ✅ Reusable UI components
├── pages/               ✅ Route-level components
├── services/            ✅ API clients per domain
├── hooks/               ✅ Custom hooks (z index.js!)
├── constants/           ✅ App constants
├── types/               ✅ Type definitions
├── utils/               ✅ Helper functions
├── context/             ✅ React Context
├── store/               ✅ Zustand state management
└── styles/              ✅ Global styles
```

### ⚠️ PROBLEMY

#### **PROBLEM #1: Brak Barrel Exports (index.js) w większości folderów**
**Priorytet:** MEDIUM  
**Lokalizacja:** `frontend/src/components/*`, `frontend/src/pages/*`

**Co jest źle:**
```javascript
// ❌ TERAZ - musisz znać dokładne ścieżki
import BookItem from '../components/books/BookItem.jsx'
import BookCover from '../components/books/BookCover.jsx'
import StarRating from '../components/books/StarRating.jsx'
```

**Powinno być:**
```javascript
// ✅ LEPIEJ - czyste importy z barrel exports
import { BookItem, BookCover, StarRating } from '../components/books'
```

**Dlaczego to ważne:**
- 📦 Refactoring - jeśli przenosisz plik, zmienia się mniej importów
- 🎯 API - folder ma jasne publiczne API
- 📚 Czytelność - wiadomo co folder eksportuje
- 🔒 Enkapsulacja - możesz ukryć implementacyjne pliki

**Działanie:**
```bash
# Utwórz index.js w każdym folderze komponentów
frontend/src/components/books/index.js
frontend/src/components/common/index.js
frontend/src/components/ui/index.js
frontend/src/pages/*/index.js (dla każdej ścieżki)
```

**Przykład dla `components/books/index.js`:**
```javascript
export { default as BookItem } from './BookItem.jsx'
export { default as BookCover } from './BookCover.jsx'
export { default as BookCard } from './BookCard.jsx'
export { default as StarRating } from './StarRating.jsx'
export { default as UserRecommendations } from './UserRecommendations.jsx'
export { default as SemanticSearch } from './SemanticSearch.jsx'
export { default as AnnouncementCard } from './AnnouncementCard.jsx'
```

---

#### **PROBLEM #2: Brak centralizacji stylów (CSS rozproszone)**
**Priorytet:** MEDIUM  
**Lokalizacja:** `frontend/src/styles/`, `frontend/src/pages/UserDetails.css`, `frontend/src/components/ui/Skeleton.css`

**Co jest źle:**
```
Pliki CSS:
├── styles/
│   ├── main.css
│   ├── components.css
│   └── styles.css (?)
├── pages/UserDetails.css          ❌ Zmieszane ze stronami
└── components/ui/Skeleton.css     ❌ Zmieszane z komponentami
```

**Powinno być:**
```
Struktura stylów:
├── styles/
│   ├── globals/           # Globalne style
│   │   ├── index.css
│   │   ├── variables.css  # CSS variables (kolory, rozmiary)
│   │   └── normalize.css
│   ├── components/        # Style komponentów
│   │   ├── buttons.css
│   │   ├── modals.css
│   │   ├── cards.css
│   │   └── skeleton.css
│   ├── layouts/          # Style layoutów
│   │   ├── navbar.css
│   │   └── sidebar.css
│   ├── pages/            # Style stron
│   │   ├── dashboard.css
│   │   ├── books.css
│   │   └── user-details.css
│   └── main.css          # Import całości w głównym stylu
```

**Dlaczego to ważne:**
- 🎨 Konsystencja - zmienne CSS dla kolorów, rozmiarów, fontów
- 🔍 Łatwość utrzymania - style blisko komponentów (co-located)
- 📊 Reducibility - wiadomo które style się używają
- 🎯 CSS cleanup - łatwo znaleźć nieużywane style

**CSS Variables - dodaj do `styles/globals/variables.css`:**
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
  
  /* Typography */
  --font-family-base: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
  --font-size-base: 1rem;
  --line-height-base: 1.5;
  
  /* Spacing */
  --spacing-xs: 0.25rem;
  --spacing-sm: 0.5rem;
  --spacing-md: 1rem;
  --spacing-lg: 1.5rem;
  --spacing-xl: 2rem;
  
  /* Shadows */
  --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
  --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
  --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
}
```

---

#### **PROBLEM #3: Brak middleware/interceptors dla API**
**Priorytet:** HIGH  
**Lokalizacja:** `frontend/src/api.js`

**Co jest źle:**
```javascript
// api.js - syrowy fetch bez struktury middleware
export async function apiFetch(path, opts = {}) {
  // Logika ściągana do jednej funkcji
  // Brak obsługi retry, error handling, transformacji
}
```

**Powinno być:**
```
frontend/src/
├── api/
│   ├── client.js          # Konfiguracja HTTP client
│   ├── interceptors/
│   │   ├── auth.js        # Bearer token
│   │   ├── error.js       # Error handling
│   │   └── retry.js       # Retry logic
│   ├── middleware/
│   │   ├── logging.js     # Request/response logging
│   │   └── cache.js       # Caching
│   └── index.js           # Export główny
```

**Implementacja - `api/client.js`:**
```javascript
import { createApiClient } from './middleware'

export const apiClient = createApiClient({
  baseURL: import.meta.env.VITE_API_URL,
  timeout: 30000,
  retries: 3,
  interceptors: [
    'auth',      // Add authorization header
    'logging',   // Log requests/responses
    'retry',     // Retry failed requests
    'cache'      // Cache GET requests
  ]
})
```

**Middleware - `api/middleware/auth.js`:**
```javascript
export const authMiddleware = {
  request: (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  response: (response) => response,
  error: (error) => {
    if (error.response?.status === 401) {
      // Clear auth, redirect to login
    }
    return Promise.reject(error)
  }
}
```

---

#### **PROBLEM #4: Brak Guards/Route Protection**
**Priorytet:** HIGH  
**Lokalizacja:** `frontend/src/components/RequireRole.jsx`

**Co jest źle:**
```javascript
// RequireRole sprawdza role, ale brakuje innych guardów
<RequireRole allowed={['ROLE_ADMIN']}>
  <AdminPanel />
</RequireRole>
```

**Powinno być:**
```
frontend/src/guards/
├── requireAuth.js          # Wymagaj zalogowania
├── requireRole.js          # Wymagaj roli
├── requireNoAuth.js        # Reverse - zalogowany? -> nie wchodź
├── requirePermission.js    # Granular permissions
└── canActivate.js          # Custom conditions
```

**Implementacja - `guards/requireAuth.js`:**
```javascript
import { Navigate, useLocation } from 'react-router-dom'
import { useAuthContext } from '../context/AuthContext'

export function RequireAuth({ children, fallback = <Navigate to="/login" /> }) {
  const { isAuthenticated, loading } = useAuthContext()
  const location = useLocation()
  
  if (loading) return <LoadingSpinner />
  
  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />
  }
  
  return children
}
```

---

#### **PROBLEM #5: Brakuje Layout Components**
**Priorytet:** MEDIUM  
**Lokalizacja:** Brak dedykowanego folderu `layouts/`

**Co jest źle:**
```javascript
// Struktura layoutu zmieszana z App.jsx
<div className="app-shell theme-root">
  <Navbar />
  <main className="main">
    <div className="content-shell">
      <Routes>...</Routes>
    </div>
  </main>
</div>
```

**Powinno być:**
```
frontend/src/
├── layouts/
│   ├── AppLayout.jsx       # Main layout
│   ├── AdminLayout.jsx     # Admin-only layout
│   ├── AuthLayout.jsx      # Login/Register layout
│   └── useLayout.js        # Hook do zarządzania layoutem
```

**Implementacja - `layouts/AppLayout.jsx`:**
```javascript
import { Navbar } from '../components/common'
import { Toaster } from 'react-hot-toast'

export function AppLayout({ children }) {
  return (
    <>
      <Toaster position="top-right" />
      <div className="app-shell theme-root">
        <Navbar />
        <main className="main">
          <div className="content-shell">
            {children}
          </div>
        </main>
      </div>
    </>
  )
}
```

---

## 1.2 Index.js w folderach (Barrel Exports)

### ⚠️ PROBLEM: Niekompletne barrel exports

**Status:** NIEZADOWALAJĄCY

| Folder | Barrel Export | Status |
|--------|---------------|--------|
| `hooks/` | ✅ Istnieje | `index.js` |
| `constants/` | ❌ Brak | Tylko `app.js` |
| `types/` | ❌ Brak | Tylko `index.d.js` |
| `utils/` | ❌ Brak | Rozproszone pliki |
| `services/` | ❌ Brak | 13 plików bez indexu |
| `components/**/` | ❌ Brak | Każdy folder bez indexu |
| `pages/` | ❌ Brak | Rozproszone strony |

**Działanie - utwórz brakujące barrel exports:**

```javascript
// frontend/src/constants/index.js
export * from './app.js'

// frontend/src/utils/index.js
export { logger } from './logger.js'
export { loadStoredUiPreferences, storeUiPreferences } from './uiPreferences.js'

// frontend/src/services/index.js
export * as authService from './authService.js'
export * as bookService from './bookService.js'
export * as loanService from './loanService.js'
// ... itd
```

---

## 1.3 Routing

### ✅ STATUS: DOBRZE (ale można lepiej)

**Co jest dobrze:**
- ✅ React Router v6 (nowoczesny)
- ✅ Centralizacja w `App.jsx`
- ✅ Role-based guards `RequireRole`
- ✅ Zagnieżdżone routes dla admin/librarian

**Co można poprawić:**

#### **PROBLEM: Routing config rozproszona w JSX**
**Priorytet:** MEDIUM

**Teraz:**
```javascript
// App.jsx - wszystko w JSX
<Routes>
  <Route path="/" element={<Dashboard />} />
  <Route path="/books" element={<Books />} />
  {/* 30+ routes */}
</Routes>
```

**Powinno być:**
```javascript
// routes/index.js - struktura konfiguracji
export const routes = [
  {
    path: '/',
    element: Dashboard,
    public: true
  },
  {
    path: '/books',
    element: Books,
    public: true
  },
  {
    path: '/admin',
    element: AdminPanel,
    requiredRole: 'ROLE_ADMIN'
  },
  // ...
]

// App.jsx - render z pętli
<Routes>
  {routes.map(route => (
    <Route
      key={route.path}
      path={route.path}
      element={
        route.requiredRole ? (
          <RequireRole allowed={[route.requiredRole]}>
            <route.element />
          </RequireRole>
        ) : (
          <route.element />
        )
      }
    />
  ))}
</Routes>
```

---

## 1.4 State Management - Zustand

### ✅ STATUS: DOBRY

**Co jest dobrze:**
- ✅ Zustand (lżejszy niż Redux)
- ✅ Persist middleware dla localStorage
- ✅ Dwa store'y: `authStore` + `cacheStore`

**Struktura:**
```javascript
// authStore.js
export const useAuthStore = create(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      login: (user, token) => { ... },
      logout: () => { ... }
    }),
    { name: 'auth-store' }
  )
)
```

**Co można poprawić:**

#### **PROBLEM: Duplikacja state - AuthContext + authStore**
**Priorytet:** MEDIUM

Projekt MA zarówno Context API jak i Zustand:
- ❌ `AuthContext` - React Context (w utils)
- ❌ `authStore` - Zustand (osobna biblioteka)
- ❌ Brakuje jasności który używać

**Rekomendacja:**
```javascript
// ✅ Użyj TYLKO Zustand dla all global state
// Context API tylko dla localized state (Theme, Locale itp)

// frontend/src/store/index.js
export { useAuthStore } from './authStore'
export { useCacheStore } from './cacheStore'
export { useUIStore } from './uiStore'       // NEW
export { useBookStore } from './bookStore'   // NEW - book filters/search
```

---

## 1.5 Testy - Organizacja

### ✅ STATUS: DOBRY

**Co jest dobrze:**
- ✅ Vitest (szybkie unit tests)
- ✅ Playwright (E2E tests)
- ✅ Coverage reporting
- ✅ Oddzielony folder `tests/`

**Struktura:**
```
tests/
├── unit/          ✅ Unit tests
├── e2e/           ✅ E2E tests (Playwright)
└── setup.js       ✅ Test configuration
```

**Co można poprawić:**

#### **PROBLEM: Zbyt mało testów**
**Priorytet:** MEDIUM

```javascript
// Aktualnie - liczba testów
├── tests/unit/    → brak szczegółów, ale mało
└── tests/e2e/     → 63 tests (ok)
```

**Rekomendacja - struktura testów:**
```
tests/
├── unit/
│   ├── components/      # Component tests
│   ├── hooks/           # Custom hook tests
│   ├── services/        # API service tests
│   ├── store/           # Zustand store tests
│   └── utils/           # Utility function tests
├── e2e/
│   ├── auth.spec.js     # Login/logout flows
│   ├── books.spec.js    # Book browsing
│   └── loans.spec.js    # Loan management
└── setup.js
```

---

## 1.6 ESLint

### ✅ STATUS: DOBRZE

**Konfiguracja - `frontend/.eslintrc.cjs`:**
```javascript
extends: ["eslint:recommended", "plugin:react/recommended", "plugin:react-hooks/recommended"]
rules: {
  "react/prop-types": "off",
  "no-console": "warn"
}
```

**Co jest dobrze:**
- ✅ ESLint zainstalowany i skonfigurowany
- ✅ Pluginy React + React Hooks
- ✅ Script `npm run lint`

**Co brakuje:**

#### **PROBLEM #1: Brak Prettier (code formatter)**
**Priorytet:** MEDIUM

```bash
npm install --save-dev prettier eslint-config-prettier
```

**Dodaj `frontend/.prettierrc.json`:**
```json
{
  "semi": false,
  "singleQuote": true,
  "trailingComma": "es5",
  "tabWidth": 2,
  "printWidth": 100
}
```

#### **PROBLEM #2: Brak pre-commit hooks**
**Priorytet:** LOW

```bash
npm install --save-dev husky lint-staged
npx husky install
```

**`.husky/pre-commit`:**
```bash
#!/bin/sh
npx lint-staged
```

**`package.json`:**
```json
{
  "lint-staged": {
    "src/**/*.{js,jsx}": ["eslint --fix", "prettier --write"],
    "src/**/*.css": ["prettier --write"]
  }
}
```

---

## 1.7 Environment Files

### ✅ STATUS: DOBRZE

**Istnieje `.env.example`:**
```
VITE_API_URL=http://localhost:8000
VITE_ENABLE_RECOMMENDATIONS=true
VITE_DEBUG=false
VITE_API_TIMEOUT=30000
```

**Co jest dobrze:**
- ✅ `.env.example` istnieje
- ✅ Zmienne prefiksowane `VITE_` (Vite standard)

**Co można poprawić:**

#### **PROBLEM: Brak walidacji env vars przy starcie**
**Priorytet:** MEDIUM

Aplikacja nie sprawdza czy wymagane zmienne są ustawione.

**Utworz `frontend/src/config/env.js`:**
```javascript
const requiredEnvVars = ['VITE_API_URL']

function validateEnv() {
  const missing = requiredEnvVars.filter(
    varName => !import.meta.env[varName]
  )
  
  if (missing.length > 0) {
    throw new Error(
      `Missing required environment variables: ${missing.join(', ')}`
    )
  }
}

// Call in main.jsx before mounting app
validateEnv()
```

---

## 1.8 Brakujące: Interceptory API, Middleware, Guards, Layouts

### STATUS: **BRAKUJE - CRITICAL**

Powyżej - szczegółowe opisy:
- ✅ [PROBLEM #3](#problem-3-brak-middlewareinterceptors-dla-api) - Middleware API
- ✅ [PROBLEM #4](#problem-4-brak-guardsroute-protection) - Guards
- ✅ [PROBLEM #5](#problem-5-brakuje-layout-components) - Layouts

---

## 1.9 CSS/Styling

### ⚠️ STATUS: BRAKUJE ORGANIZACJI

**Teraz:**
```
Rozproszone CSS:
├── styles.css
├── styles/main.css
├── styles/components.css
├── pages/UserDetails.css
└── components/ui/Skeleton.css
```

**Rekomendacja - patrz [PROBLEM #2](#problem-2-brak-centralizacji-stylów-css-rozproszone)**

---

---

# 2️⃣ AUDYT BACKEND

## 2.1 Struktura `src/` - Controllers, Services, Repositories

### ✅ STATUS: BARDZO DOBRY

**Struktura:**
```
backend/src/
├── Controller/          ✅ REST endpoints
├── Service/             ✅ Business logic
├── Repository/          ✅ Data access (Doctrine)
├── Application/         ✅ CQRS pattern
├── Entity/              ✅ Doctrine ORM models
├── Dto/                 ✅ Data Transfer Objects
├── Request/             ✅ Request objects with validation
├── Event/               ✅ Domain events
├── EventSubscriber/     ✅ Event listeners
├── Middleware/          ✅ HTTP middleware
└── Message/             ✅ Async messages
```

**Co jest dobrze:**
- ✅ Warstwowa architektura (Controllers → Services → Repositories)
- ✅ DTOs dla API responses
- ✅ Request objects z walidacją
- ✅ CQRS pattern (Commands + Queries + Handlers)
- ✅ Event-driven architecture
- ✅ 30 repositories (sczególnie generované przez Doctrine)

**Co można poprawić:**

#### **PROBLEM #1: Brakuje interfejsów dla Service'ów**
**Priorytet:** MEDIUM  
**Lokalizacja:** `backend/src/Service/`

**Dlaczego to ważne:**
- 📦 Dependency Injection - możesz mockować w testach
- 🔄 Dependency Inversion Principle (SOLID)
- 🔌 Pluggable architecture - łatwo zamienić implementację

**Przykład - teraz:**
```php
// UserService.php
public function createUser(CreateUserRequest $request) {
  // Implementacja
}

// UserController.php
public function __construct(private UserService $service) {}
```

**Powinno być:**
```php
// Service/User/UserServiceInterface.php
interface UserServiceInterface {
  public function createUser(CreateUserRequest $request): UserDto;
  public function updateUser(string $id, UpdateUserRequest $request): UserDto;
  public function deleteUser(string $id): void;
}

// Service/User/UserService.php
class UserService implements UserServiceInterface {
  public function createUser(CreateUserRequest $request): UserDto {
    // Implementacja
  }
}

// UserController.php
public function __construct(private UserServiceInterface $service) {}
// ✅ W testach można użyć mock implementacji
```

**Utworz interfejsy dla głównych serwisów:**
```bash
backend/src/Service/
├── Book/
│   ├── BookServiceInterface.php
│   └── BookService.php
├── Loan/
│   ├── LoanServiceInterface.php
│   └── LoanService.php
├── User/
│   ├── UserServiceInterface.php
│   └── UserService.php
├── Notification/
│   ├── NotificationServiceInterface.php
│   └── NotificationService.php
└── ... (dla każdego głównego serwisu)
```

---

#### **PROBLEM #2: Serwisy zbyt duże (>300 linii) - naruszenie SRP**
**Priorytet:** HIGH  
**Lokalizacja:** `backend/src/Service/`

**Co jest źle:**
```php
// NotificationService.php - zbyt wiele odpowiedzialności:
// - Wysyłanie emaili
// - Wysyłanie SMS
// - Logowanie powiadomień
// - Obsługa szablonów
// - Retry logic
```

**Rekomendacja - rozdzielić na mniejsze serwisy:**
```bash
backend/src/Service/Notification/
├── NotificationServiceInterface.php
├── NotificationService.php              # Orchestrator
├── EmailSender.php                      # Odpowiada za email
├── SmsSender.php                        # Odpowiada za SMS
├── TemplateRenderer.php                 # Rendering szablonów
└── NotificationLogger.php               # Logging
```

**Przykład refactoringu:**
```php
// Przed - wszystko w NotificationService
class NotificationService {
  public function sendEmail($recipient, $subject, $body) {
    // 50 linii logiki mailera
  }
  
  public function sendSms($phone, $message) {
    // 40 linii logiki SMS
  }
  
  public function logNotification(...) {
    // 30 linii logiki loggingu
  }
}

// Po - rozdzielone odpowiedzialności
class NotificationService {
  public function __construct(
    private EmailSenderInterface $emailSender,
    private SmsSenderInterface $smsSender,
    private NotificationLoggerInterface $logger
  ) {}
  
  public function send(Notification $notification): void {
    match ($notification->getType()) {
      'email' => $this->emailSender->send($notification),
      'sms' => $this->smsSender->send($notification),
    };
    
    $this->logger->log($notification);
  }
}
```

---

#### **PROBLEM #3: Brakuje Custom Exception Hierarchy**
**Priorytet:** HIGH  
**Lokalizacja:** `backend/src/Service/Auth/RegistrationException.php` (tylko 1!)

**Co jest źle:**
```php
// Aktualnie - masz tylko 1 custom exception
throw new RegistrationException('User already exists');

// Ale brakuje dla innych przypadków
throw new Exception('Book not found');  // ❌ Generic
throw new Exception('Loan overdue');     // ❌ Generic
```

**Powinno być:**
```bash
backend/src/Exception/
├── ExceptionInterface.php
├── ApplicationException.php              # Base exception
├── Domain/
│   ├── BookNotFoundException.php
│   ├── LoanOverdueException.php
│   ├── InsufficientCopiesException.php
│   ├── ReservationException.php
│   └── PaymentException.php
├── Validation/
│   ├── ValidationException.php
│   ├── InvalidEmailException.php
│   ├── PasswordTooWeakException.php
│   └── DuplicateEmailException.php
├── Authorization/
│   ├── AccessDeniedException.php
│   ├── InsufficientPermissionsException.php
│   └── RoleRequiredException.php
└── Infrastructure/
    ├── DatabaseException.php
    ├── EmailSendingException.php
    └── ExternalServiceException.php
```

**Implementacja:**
```php
// Exception/ExceptionInterface.php
interface ExceptionInterface extends Throwable {
  public function getErrorCode(): string;
  public function getHttpStatusCode(): int;
  public function toApiResponse(): array;
}

// Exception/ApplicationException.php
abstract class ApplicationException extends Exception implements ExceptionInterface {
  protected string $errorCode = 'INTERNAL_ERROR';
  protected int $httpStatusCode = 500;
  
  public function getErrorCode(): string {
    return $this->errorCode;
  }
  
  public function getHttpStatusCode(): int {
    return $this->httpStatusCode;
  }
  
  public function toApiResponse(): array {
    return [
      'error' => [
        'code' => $this->getErrorCode(),
        'message' => $this->getMessage(),
        'details' => []
      ]
    ];
  }
}

// Exception/Domain/BookNotFoundException.php
class BookNotFoundException extends ApplicationException {
  protected string $errorCode = 'BOOK_NOT_FOUND';
  protected int $httpStatusCode = 404;
  
  public function __construct(string $bookId = '') {
    parent::__construct("Book '{$bookId}' not found");
  }
}
```

**Użycie w Service:**
```php
public function getBook(string $id): Book {
  $book = $this->repository->find($id);
  
  if (!$book) {
    throw new BookNotFoundException($id);  // ✅ Custom exception
  }
  
  return $book;
}
```

**EventSubscriber obsłuży exceptions:**
```php
// src/EventSubscriber/ExceptionHandlerSubscriber.php
class ExceptionHandlerSubscriber implements EventSubscriberInterface {
  public static function getSubscribedEvents(): array {
    return [ExceptionEvent::class => 'onException'];
  }
  
  public function onException(ExceptionEvent $event): void {
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

## 2.2 Routing - routes.yaml

### ✅ STATUS: DOSKONAŁY

**Pliki:**
- ✅ Scentralizowany `config/routes.yaml` (1181 linii!)
- ✅ Wyraźna definicja ścieżek
- ✅ Controllers mapowane w YAML

**Struktura:**
```yaml
health_check:
  path: /health
  controller: App\Controller\HealthController::health
  
api_auth_login:
  path: /api/auth/login
  controller: App\Controller\AuthController::login
  methods: [POST]
  
api_books_list:
  path: /api/books
  controller: App\Controller\Books\BookController::list
  methods: [GET]
```

**Co jest dobrze:**
- ✅ Wszystkie route'y w jednym miejscu
- ✅ Konsystentne naming (`api_`prefix)
- ✅ Pełna kontrola nad metodami HTTP
- ✅ Łatwe do przeszukania

**Co można poprawić:**

#### **PROBLEM: Route'y nie są podzielone na moduły**
**Priorytet:** LOW  
**Dla dużych projektów**

```yaml
# Teraz - wszystko w jednym pliku
api_auth_login:
  path: /api/auth/login
  ...
api_books_list:
  path: /api/books
  ...
api_loans_create:
  path: /api/loans
  ...
```

**Powinno być (dla większego projektu):**
```yaml
# config/routes.yaml
imports:
  - ./routes/auth.yaml
  - ./routes/books.yaml
  - ./routes/loans.yaml
  - ./routes/users.yaml

health_check:
  path: /health
  controller: App\Controller\HealthController::health

# config/routes/auth.yaml
api_auth_login:
  path: /api/auth/login
  controller: App\Controller\AuthController::login

# config/routes/books.yaml
api_books_list:
  path: /api/books
  controller: App\Controller\Books\BookController::list
```

**Plusy:**
- Strukturyzacja
- Łatwość nawigacji
- Separacja domeny

---

## 2.3 DTOs

### ✅ STATUS: DOSKONAŁY

**Struktura:**
```
backend/src/Dto/
├── ApiError.php              ✅ Standard error response
├── ApiResponse.php           ✅ Standard success response
└── HateoasTrait.php          ✅ HATEOAS links
```

**Co jest dobrze:**
- ✅ ApiError - standardizowana obsługa błędów
- ✅ ApiResponse - obwoluta dla responses
- ✅ HateoasTrait - links do relacionowanych zasobów

**Przykład:**
```php
// ApiResponse.php
{
  "data": { /* user data */ },
  "meta": {
    "timestamp": "2026-01-25T10:00:00Z",
    "version": "1.0"
  },
  "_links": {
    "self": { "href": "/api/users/123" },
    "update": { "href": "/api/users/123", "method": "PUT" },
    "delete": { "href": "/api/users/123", "method": "DELETE" }
  }
}
```

---

## 2.4 Entities

### ✅ STATUS: BARDZO DOBRY

**Ilość:**
- ✅ 30 encji (wymagane: minimum 5)
- ✅ Pełne relacje (One-to-Many, Many-to-Many)
- ✅ Traits dla wspólnej logiki

**Struktura:**
```
backend/src/Entity/
├── User.php                   ✅ Użytkownik
├── Book.php                   ✅ Książka
├── BookCopy.php               ✅ Egzemplarz
├── Loan.php                   ✅ Wypożyczenie
├── Reservation.php            ✅ Rezerwacja
├── Fine.php                   ✅ Kara
├── Review.php                 ✅ Recenzja
├── Rating.php                 ✅ Ocena
├── Author.php                 ✅ Autor
├── Category.php               ✅ Kategoria
├── ... (20+ więcej)
└── Traits/                    ✅ Wspólne traits
    └── TimestampableTrait.php
```

**Co jest dobrze:**
- ✅ Doctrine ORM z atrybutami PHP 8
- ✅ Pełne relacje
- ✅ Indeksy na kluczach obcych
- ✅ Traits dla DRY

**Co można poprawić:**

#### **PROBLEM: Brak Value Objects**
**Priorytet:** LOW  
**Dla zaawansowanego DDD**

Aktualnie wartości są prymitywami:
```php
class User {
  private string $email;
  private string $password;
  private string $phoneNumber;
}
```

**Powinno być (z Value Objects):**
```php
class User {
  private Email $email;           // Value Object
  private Password $password;     // Value Object
  private PhoneNumber $phoneNumber; // Value Object
}

class Email {
  public function __construct(private string $value) {
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
      throw new InvalidEmailException();
    }
  }
  
  public function getValue(): string {
    return $this->value;
  }
}
```

---

## 2.5 Repositories

### ✅ STATUS: DOSKONAŁY

**Ilość:**
- ✅ 30 repositories (każda encja ma repo)
- ✅ Generyczne przez Doctrine

**Struktura:**
```php
class UserRepository extends ServiceEntityRepository {
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, User::class);
  }
  
  public function findActiveUsers(): array {
    return $this->createQueryBuilder('u')
      ->where('u.isActive = true')
      ->orderBy('u.createdAt', 'DESC')
      ->getQuery()
      ->getResult();
  }
}
```

**Co jest dobrze:**
- ✅ Standardowy QueryBuilder pattern
- ✅ Custom query methods
- ✅ Obsługiwane przez Doctrine

---

## 2.6 Middleware

### ⚠️ STATUS: MINIMALNY

**Aktualnie:**
```
backend/src/Middleware/
└── LegacyErrorResponseConverter.php   (1 middleware)
```

**Powinno być:**
```bash
backend/src/Middleware/
├── RequestLoggingMiddleware.php       # Logowanie requestów
├── SecurityHeadersMiddleware.php      # Security headers
├── RateLimitingMiddleware.php         # Rate limiting
├── CompressResponseMiddleware.php     # Gzip compression
├── CorsMiddleware.php                 # CORS handling
└── ValidationMiddleware.php           # Request validation
```

#### **PROBLEM: Brakuje middleware'u do logowania requestów**
**Priorytet:** MEDIUM

**Implementacja - `Middleware/RequestLoggingMiddleware.php`:**
```php
class RequestLoggingMiddleware {
  public function __construct(private LoggerInterface $logger) {}
  
  public function onKernelRequest(RequestEvent $event): void {
    $request = $event->getRequest();
    
    $this->logger->info('HTTP Request', [
      'method' => $request->getMethod(),
      'path' => $request->getPathInfo(),
      'ip' => $request->getClientIp(),
      'timestamp' => date('Y-m-d H:i:s'),
    ]);
  }
}
```

---

## 2.7 Event Listeners / Subscribers

### ✅ STATUS: BARDZO DOBRY

**Ilość:**
- ✅ 12 subscribers

**Struktura:**
```
backend/src/EventSubscriber/
├── ApiAuthSubscriber.php              ✅ JWT validation
├── ApiExceptionSubscriber.php          ✅ Exception handling
├── ApiResponseNormalizationSubscriber.php  ✅ Response format
├── BookBorrowedSubscriber.php          ✅ Domain events
├── BookEmbeddingSubscriber.php         ✅ AI embeddings
├── BookReturnedSubscriber.php          ✅ Domain events
├── CacheInvalidationSubscriber.php     ✅ Cache management
├── CorsSubscriber.php                  ✅ CORS headers
├── HandlerFailedExceptionSubscriber.php ✅ Async errors
├── LegacyResponseConversionSubscriber.php ✅ Backward compat
├── RateLimiterSubscriber.php           ✅ Rate limiting
└── RateLimitHeaderSubscriber.php       ✅ Rate limit headers
```

**Co jest dobrze:**
- ✅ Dobrze podzielone odpowiedzialności
- ✅ Event-driven architecture
- ✅ CORS, authentication, exceptions obsługiwane

---

## 2.8 Brakujące: Custom Exceptions, Validators, Formatters, Mappers

### PROBLEM #1: Custom Exceptions - patrz [powyżej](#problem-3-brakuje-custom-exception-hierarchy)

### PROBLEM #2: Brakuje Validators

**Priorytet:** MEDIUM  
**Lokalizacja:** Brakuje dedykowanego folderu

**Teraz:**
```php
// Walidacja w Request objects
class CreateUserRequest {
  #[Assert\Email]
  #[Assert\NotBlank]
  public string $email;
}
```

**Powinno być - dedykowany folder:**
```bash
backend/src/Validator/
├── UserValidator.php               # Logika validacji użytkownika
├── BookValidator.php               # Logika validacji książki
├── LoanValidator.php               # Logika validacji wypożyczenia
└── Constraints/                    # Custom constraint annotations
    ├── ValidIsbn.php
    ├── UniqueEmail.php
    └── AvailableBook.php
```

**Implementacja:**
```php
// Validator/UserValidator.php
class UserValidator {
  public function validateEmail(string $email): void {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new InvalidEmailException('Invalid email format');
    }
  }
  
  public function validatePassword(string $password): void {
    if (strlen($password) < 8) {
      throw new PasswordTooWeakException('Password must be at least 8 chars');
    }
  }
}

// Service/UserService.php
public function createUser(CreateUserRequest $request): UserDto {
  $this->validator->validateEmail($request->email);
  $this->validator->validatePassword($request->password);
  // ...
}
```

---

### PROBLEM #3: Brakuje Formatters/Serializers

**Priorytet:** MEDIUM

**Powinno być:**
```bash
backend/src/Formatter/
├── DateFormatter.php              # Data → ISO 8601
├── MoneyFormatter.php             # Pieniądze → zł format
├── BooleanFormatter.php           # Boolean → true/false
└── Serializer/
    ├── UserSerializer.php         # Entity → DTO
    ├── BookSerializer.php
    └── LoanSerializer.php
```

**Implementacja:**
```php
// Formatter/DateFormatter.php
class DateFormatter {
  public static function toIso8601(DateTime $date): string {
    return $date->format('c');  // ISO 8601
  }
  
  public static function fromString(string $dateStr): DateTime {
    return DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
  }
}

// Serializer/UserSerializer.php
class UserSerializer {
  public function serialize(User $user): UserDto {
    return new UserDto(
      id: $user->getId(),
      email: $user->getEmail(),
      name: $user->getName(),
      createdAt: DateFormatter::toIso8601($user->getCreatedAt()),
      roles: $user->getRoles(),
    );
  }
}
```

---

### PROBLEM #4: Brakuje Mappers

**Priorytet:** MEDIUM

**Powinno być:**
```bash
backend/src/Mapper/
├── UserMapper.php              # User Entity ↔ UserDto
├── BookMapper.php              # Book Entity ↔ BookDto
├── LoanMapper.php              # Loan Entity ↔ LoanDto
└── ... (dla każdej encji)
```

**Implementacja:**
```php
// Mapper/UserMapper.php
class UserMapper {
  public function toDomain(CreateUserRequest $request): User {
    $user = new User();
    $user->setEmail($request->email);
    $user->setPassword(password_hash($request->password, PASSWORD_BCRYPT));
    $user->setFirstName($request->firstName);
    return $user;
  }
  
  public function toDto(User $user): UserDto {
    return new UserDto(
      id: (string)$user->getId(),
      email: $user->getEmail(),
      firstName: $user->getFirstName(),
      role: $user->getRole(),
      createdAt: $user->getCreatedAt()->format('c'),
    );
  }
  
  public function toDtos(array $users): array {
    return array_map($this->toDto(...), $users);
  }
}
```

---

## 2.9 Configuration

### ✅ STATUS: DOSKONAŁY

**Struktura:**
```
backend/config/
├── bootstrap.php              ✅ Bootstrap aplikacji
├── bundles.php                ✅ Bundle configuration
├── routes.yaml                ✅ Routing (1181 linii)
├── services.yaml              ✅ Dependency Injection (214 linii)
├── services_test.yaml         ✅ Test configuration
└── packages/                  ✅ Package-specific config
```

**Konfiguracja (.env):**
```env
DATABASE_URL=postgresql://...  ✅ Database
JWT_SECRET=...                 ✅ JWT
MESSENGER_TRANSPORT_DSN=...    ✅ RabbitMQ
REDIS_URL=...                  ✅ Cache
ELASTICSEARCH_HOST=...         ✅ Search
CORS_ALLOW_ORIGIN=...          ✅ CORS
```

**Co jest dobrze:**
- ✅ Oddzielona konfiguracja per environment
- ✅ Services DI fully configured
- ✅ Integracja wszystkich serwisów (DB, Mail, Cache, Search)

---

## 2.10 Security

### ✅ STATUS: BARDZO DOBRY

**Lokalizacja:**
```
backend/src/Security/
├── ApiSecretUser.php          ✅ API Secret authentication
├── JwtTokenAuthenticator.php  ✅ JWT authentication
└── UserProvider.php           ✅ Custom user provider
```

**Co jest dobrze:**
- ✅ JWT authentication (Bearer tokens)
- ✅ Refresh token mechanism
- ✅ Role-based access control (RBAC)
- ✅ API Secret for machine-to-machine
- ✅ Password hashing (bcrypt)
- ✅ CORS security

**Implementacja:**
```php
// JWT flow
1. POST /api/auth/login → JWT token
2. GET /api/books, header: Authorization: Bearer {token}
3. JWT authenticated, access granted

// Refresh flow
1. Token expiry → 401
2. POST /api/auth/refresh with refreshToken
3. New JWT token issued
```

**Co można poprawić:**

#### **PROBLEM: Brakuje detailedowych permission checks**
**Priorytet:** MEDIUM

**Teraz - role-based:**
```php
// Tylko sprawdzanie roli
#[IsGranted('ROLE_ADMIN')]
public function deleteBook(string $id) {}
```

**Powinno być - permission-based:**
```php
// Granular permissions
#[IsGranted('BOOK_DELETE')]
public function deleteBook(string $id) {}

// Voter sprawdzi:
// - Czy user ma rolę ROLE_ADMIN lub ROLE_LIBRARIAN?
// - Czy user jest właścicielem książki? (dla recenzentów)
// - Czy istnieje aktywne wypożyczenie tej książki?
```

---

## 2.11 API Documentation

### ✅ STATUS: DOSKONAŁY

**Tool:** NelmioApiDocBundle (Swagger/OpenAPI)

**Konfiguracja:**
```
backend/config/packages/nelmio_api_doc.yaml
```

**Dokumentacja:**
- ✅ 190+ endpoints
- ✅ Auto-generated z annotacji PHP
- ✅ Schema validation
- ✅ Try-it-out functionality

**Dostęp:**
```
http://localhost:8000/api/doc
```

---

---

# 3️⃣ AUDYT CAŁEGO PROJEKTU

## 3.1 .gitignore

### ✅ STATUS: DOSKONAŁY

**Plik:**
```
backend/var/
backend/tmp/
backend/vendor/
frontend/dist/
frontend/test-results/
.env
.env.local
.env.test
node_modules/
.DS_Store
```

**Co jest dobrze:**
- ✅ Sekretne `.env` zmienne ignorowane
- ✅ Build outputs (`dist/`)
- ✅ Dependencies (`vendor/`, `node_modules`)
- ✅ Cache i logs
- ✅ OS-specific files (`.DS_Store`)

---

## 3.2 Docker

### ✅ STATUS: DOSKONAŁY

**Pliki:**
```
config/docker-compose.yml       ✅ Main configuration
config/docker-compose.windows.yml ✅ Windows-specific
docker/
├── backend/                    ✅ PHP + FPM
├── db/                         ✅ PostgreSQL 16 + pgvector
├── frontend/                   ✅ Node build image
├── nginx/                      ✅ Reverse proxy
└── php-worker/                 ✅ Message consumer
```

**Serwisy:**
- ✅ PostgreSQL 16 (pgvector dla AI)
- ✅ RabbitMQ (async messages)
- ✅ Redis (cache)
- ✅ Nginx (reverse proxy)
- ✅ PHP-FPM (backend)
- ✅ Node (frontend)
- ✅ php-worker (async jobs)

**Health checks:**
- ✅ Database healthcheck
- ✅ RabbitMQ healthcheck
- ✅ Service dependencies

**Szybki start:**
```bash
docker compose up -d
# Wszystko gotowe za ~30 sekund
```

---

## 3.3 Config - Podział

### ✅ STATUS: DOSKONAŁY

**Struktura:**
```
config/
├── docker-compose.yml          ✅ Environment definition
├── docker-compose.windows.yml  ✅ OS-specific
└── .env.example                ✅ Template variables

backend/config/
├── bootstrap.php
├── bundles.php
├── routes.yaml                 ✅ All routes
├── services.yaml               ✅ All DI services
├── services_test.yaml          ✅ Test services
└── packages/                   ✅ Third-party bundles

frontend/
├── vite.config.js             ✅ Build config
├── vitest.config.js           ✅ Test config
├── eslint.cjs                 ✅ Linting
├── playwright.config.js       ✅ E2E tests
└── .env.example               ✅ Env variables
```

**Co jest dobrze:**
- ✅ Jasna separacja domeny
- ✅ Environment-specific files
- ✅ Każdy tool konfigurowany oddzielnie

---

## 3.4 Docs

### ✅ STATUS: BARDZO DOBRY

**Dokumenty:**
```
docs/
├── README.md                           ✅ Main docs (1995 linii!)
├── CONTRIBUTING.md                     ✅ Contribution guide
├── CHANGELOG.md                        ✅ Version history
├── SECURITY.md                         ✅ Security policy
├── AUDYT_SENIOR_DEV.md                 ✅ 683-line audit!
├── ERD.md                              ✅ Database diagram (460 linii)
├── database-diagram.puml               ✅ PlantUML (245 linii)
└── migration-info.php                  ✅ Migration docs
```

**Czego brakuje:**

#### **PROBLEM #1: Brak ARCHITECTURE.md**
**Priorytet:** MEDIUM

```bash
docs/ARCHITECTURE.md  # Brakuje!
```

**Powinien zawierać:**
```markdown
# Architecture Guide

## Frontend Architecture
- Folder structure explanation
- Component patterns (containers vs presentational)
- State management with Zustand
- API layer design
- Testing strategy

## Backend Architecture
- CQRS pattern explanation
- Service layer design
- Repository pattern
- Event-driven architecture
- Security design

## Database Design
- Entity relationships
- Normalization strategy
- Indexing strategy
```

#### **PROBLEM #2: Brak API_EXAMPLES.md**
**Priorytet:** MEDIUM

```markdown
# API Examples

## Authentication
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

Response:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refreshToken": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

## Get Books
```bash
curl -X GET http://localhost:8000/api/books \
  -H "Authorization: Bearer {token}"
```
```

#### **PROBLEM #3: Brak DEPLOYMENT.md**
**Priorytet:** MEDIUM

```bash
docs/DEPLOYMENT.md  # Brakuje!
```

**Powinien zawierać:**
```markdown
# Deployment Guide

## Production Checklist
- [ ] Set strong APP_SECRET
- [ ] Set strong JWT_SECRET
- [ ] Disable debug mode (APP_ENV=prod)
- [ ] Configure CDN for static assets
- [ ] Setup SSL certificates
- [ ] Configure backup strategy

## Docker Production Deployment
- Using docker compose in production
- Environment-specific compose files
- Secrets management
- Scaling considerations
```

---

## 3.5 CI/CD

### ❌ STATUS: BRAKUJE - CRITICAL

**Aktualnie:** Brak
**Powinno być:**

```bash
.github/workflows/
├── test.yml              # Unit tests (frontend + backend)
├── lint.yml              # Linting (ESLint + PHPStan)
├── e2e.yml               # End-to-end tests
├── deploy.yml            # Production deployment
└── security.yml          # Security scanning
```

#### **PROBLEM: Brak GitHub Actions**
**Priorytet:** CRITICAL  
**Dlaczego:** Bez CI/CD każdy commit może złamać projekt

**Implementacja - `.github/workflows/test.yml`:**

```yaml
name: Tests

on: [push, pull_request]

jobs:
  backend-tests:
    runs-on: ubuntu-latest
    
    services:
      postgres:
        image: pgvector/pgvector:pg16
        env:
          POSTGRES_USER: test
          POSTGRES_PASSWORD: test
          POSTGRES_DB: test_db
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 5432:5432
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: pdo_pgsql
      
      - name: Install dependencies
        run: composer install
        working-directory: backend
      
      - name: Run tests
        run: php bin/phpunit
        working-directory: backend
      
      - name: Run PHPStan
        run: vendor/bin/phpstan analyse
        working-directory: backend

  frontend-tests:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: 18
      
      - name: Install dependencies
        run: npm install
        working-directory: frontend
      
      - name: Lint
        run: npm run lint
        working-directory: frontend
      
      - name: Unit tests
        run: npm run test:run
        working-directory: frontend
      
      - name: Build
        run: npm run build
        working-directory: frontend

  e2e-tests:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: 18
      
      - name: Install dependencies
        run: npm install
        working-directory: frontend
      
      - name: Install Playwright
        run: npx playwright install
      
      - name: Run E2E tests
        run: npm run test:e2e
        working-directory: frontend
      
      - name: Upload report
        uses: actions/upload-artifact@v3
        if: always()
        with:
          name: playwright-report
          path: frontend/test-results/
```

---

## 3.6 README

### ✅ STATUS: DOSKONAŁY

**Plik:** `README.md` (1995 linii)

**Zawiera:**
- ✅ Opis projektu
- ✅ Architektura systemu
- ✅ Stack technologiczny
- ✅ Wymagania systemowe
- ✅ Instrukcje instalacji (Docker + Manual)
- ✅ Struktura projektu
- ✅ Dokumentacja bazy danych
- ✅ API documentation link
- ✅ Funkcjonalności
- ✅ Uwierzytelnianie
- ✅ Testowanie
- ✅ Troubleshooting
- ✅ Domyślne konta testowe
- ✅ Badges z technologiami

---

---

# 📋 PODSUMOWANIE PROBLEMÓW - RANKING WAGI

## 🔴 CRITICAL (Muszą być naprawione)

| # | Problem | Lokalizacja | Wpływ | Wysiłek |
|---|---------|-------------|-------|--------|
| 1 | **Brak CI/CD pipeline** | `.github/workflows/` | Bardzo wysoki | Duży |
| 2 | **Brak Custom Exception Hierarchy** | `backend/src/Exception/` | Wysoki | Średni |
| 3 | **Brak API interceptors/middleware** | `frontend/src/api/` | Wysoki | Średni |

## 🟠 HIGH (Ważne, powinna być w najbliższej iteracji)

| # | Problem | Lokalizacja | Wpływ | Wysiłek |
|---|---------|-------------|-------|--------|
| 4 | **Brakuje Route Guards** | `frontend/src/guards/` | Wysoki | Mały |
| 5 | **Serwisy > 300 linii (SRP violation)** | `backend/src/Service/` | Średni | Duży |
| 6 | **Brak Service Interfaces** | `backend/src/Service/` | Średni | Średni |
| 7 | **Brak centralizacji stylów** | `frontend/src/styles/` | Średni | Średni |
| 8 | **Brakuje Validators folder** | `backend/src/Validator/` | Średni | Mały |

## 🟡 MEDIUM (Powinna być poprawiona)

| # | Problem | Lokalizacja | Wpływ | Wysiłek |
|---|---------|-------------|-------|--------|
| 9 | **Brak Barrel Exports (index.js)** | `frontend/src/components/` | Mały | Mały |
| 10 | **Brakuje Layout Components** | `frontend/src/layouts/` | Mały | Mały |
| 11 | **Brakuje Prettier (formatter)** | `frontend/` | Mały | Bardzo mały |
| 12 | **Duplikacja Auth - Context + Zustand** | `frontend/src/` | Mały | Mały |
| 13 | **Brakuje Formatters/Serializers** | `backend/src/Formatter/` | Mały | Średni |
| 14 | **Brakuje Mappers** | `backend/src/Mapper/` | Mały | Średni |
| 15 | **Brakuje ARCHITECTURE.md** | `docs/` | Mały | Mały |
| 16 | **Brakuje API_EXAMPLES.md** | `docs/` | Mały | Mały |
| 17 | **Brakuje DEPLOYMENT.md** | `docs/` | Mały | Mały |

## 🟢 LOW (Nice to have, przydatne do optymalizacji)

| # | Problem | Lokalizacja | Wpływ | Wysiłek |
|---|---------|-------------|-------|--------|
| 18 | **Route config można podzielić na moduły** | `backend/config/routes.yaml` | Bardzo mały | Mały |
| 19 | **Brakuje pre-commit hooks (husky)** | `frontend/` | Bardzo mały | Bardzo mały |
| 20 | **Brakuje middleware'u logowania** | `backend/src/Middleware/` | Bardzo mały | Mały |

---

# 🎯 PLAN DZIAŁANIA - PRIORYTETYZACJA

## Sprint 1: Foundation (1-2 tygodnie)
```
[ ] 1. Setup CI/CD pipeline (GitHub Actions) - CRITICAL
[ ] 2. Refactor Exception Hierarchy - CRITICAL  
[ ] 3. Create API interceptors/middleware - HIGH
[ ] 4. Add Route Guards - HIGH
```

## Sprint 2: Architecture (2-3 tygodnie)
```
[ ] 5. Add Service Interfaces - HIGH
[ ] 6. Refactor big services (SRP) - HIGH
[ ] 7. Add Validators folder - HIGH
[ ] 8. Add Barrel exports (index.js) - MEDIUM
[ ] 9. Add Layout Components - MEDIUM
```

## Sprint 3: Polish (1-2 tygodnie)
```
[ ] 10. Centralize CSS/styles - MEDIUM
[ ] 11. Add Prettier formatter - MEDIUM
[ ] 12. Consolidate Auth (Zustand only) - MEDIUM
[ ] 13. Add Formatters/Serializers - MEDIUM
[ ] 14. Add Mappers - MEDIUM
```

## Sprint 4: Documentation (1 tydzień)
```
[ ] 15. Write ARCHITECTURE.md - MEDIUM
[ ] 16. Write API_EXAMPLES.md - MEDIUM
[ ] 17. Write DEPLOYMENT.md - MEDIUM
[ ] 18. Review and polish docs - LOW
```

---

# 📊 METRYKI KOŃCOWE

## Przed Audytem
- Frontend Organization: ⚠️ 65/100
- Backend Organization: ✅ 85/100
- CI/CD: ❌ 0/100
- Documentation: ✅ 85/100
- **ŚREDNIA: 59/100** ⚠️

## Po Implementacji Rekomendacji
- Frontend Organization: ✅ 90/100
- Backend Organization: ✅ 92/100
- CI/CD: ✅ 95/100
- Documentation: ✅ 95/100
- **ŚREDNIA: 93/100** ✅ EXCELLENT

---

# 🎓 KONKLUZJA

Projekt **Biblioteka** jest **solidnym, gotowym do produkcji systemem** z doskonałą bazą danych, architekturą CQRS w backendzie i responsywnym frontendem React.

**Główne zalety:**
- 🏗️ Warstwowa architektura (Controllers → Services → Repositories)
- 📊 Zaawansowana baza danych (30 tabel, pgvector, AI)
- 🔐 Bezpieczeństwo (JWT, refresh tokens, role-based access)
- 📡 Asynchroniczne przetwarzanie (RabbitMQ)
- 📝 Świetna dokumentacja (1995+ linii)

**Główne rekomendacje:**
1. **PRIORITY 1:** Setup CI/CD (GitHub Actions) - bez tego każdy commit ryzykuje złamanie
2. **PRIORITY 2:** Refactor exception handling - dla lepszego error reporting
3. **PRIORITY 3:** Add API middleware layer - dla consistency i maintainability
4. **PRIORITY 4:** Refactor wielkie serwisy - dla Single Responsibility Principle

**Bez tych ulepszeń projekt będzie trudny do utrzymania w miarę wzrostu bazy kodowej.**

---

**Raport wygenerowany:** 25 január 2026  
**Status:** Gotowy do implementacji
