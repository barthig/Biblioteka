# Plan Ulepszeń do 100/100

**Cel:** Osiągnięcie 100% we wszystkich 14 kryteriach audytu  
**Czas realizacji:** 6-8 godzin  
**Priorytet:** Sortowane według wpływu i prostoty

---

## 🎯 Priorytet 1: Quick Wins (1-2h)

### ✅ 1.1 Toast Notifications (15 min)
**Wpływ:** UX +5%, Funkcjonalność +2%

```bash
cd frontend
npm install react-hot-toast
```

**Implementacja:**
```jsx
// src/App.jsx
import { Toaster } from 'react-hot-toast'

function App() {
  return (
    <>
      <Toaster position="top-right" />
      {/* ... */}
    </>
  )
}

// Użycie:
import toast from 'react-hot-toast'
toast.success('Książka wypożyczona!')
toast.error('Wystąpił błąd')
```

---

### ✅ 1.2 Zmiana Hasła (20 min)
**Wpływ:** Funkcjonalność +5%

**Backend:** `src/Controller/UserController.php`
```php
#[Route('/api/users/me/password', methods: ['PUT'])]
#[OA\Put(
    path: '/api/users/me/password',
    summary: 'Change user password',
    requestBody: new OA\RequestBody(/*...*/)
)]
public function changePassword(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $user = $this->getUser();
    
    // Validate old password
    if (!password_verify($data['oldPassword'], $user->getPassword())) {
        return $this->json(['error' => 'Invalid old password'], 400);
    }
    
    // Set new password
    $user->setPassword(
        $this->passwordHasher->hashPassword($user, $data['newPassword'])
    );
    $this->entityManager->flush();
    
    return $this->json(['message' => 'Password changed successfully']);
}
```

**Frontend:** `src/pages/Profile.jsx`
```jsx
function ChangePasswordForm() {
  const [oldPassword, setOldPassword] = useState('')
  const [newPassword, setNewPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')

  async function handleSubmit(e) {
    e.preventDefault()
    if (newPassword !== confirmPassword) {
      toast.error('Hasła nie pasują')
      return
    }
    
    try {
      await apiFetch('/api/users/me/password', {
        method: 'PUT',
        body: JSON.stringify({ oldPassword, newPassword })
      })
      toast.success('Hasło zmienione!')
    } catch (err) {
      toast.error(err.message)
    }
  }

  return (
    <form onSubmit={handleSubmit}>
      <input type="password" value={oldPassword} onChange={e => setOldPassword(e.target.value)} />
      <input type="password" value={newPassword} onChange={e => setNewPassword(e.target.value)} />
      <input type="password" value={confirmPassword} onChange={e => setConfirmPassword(e.target.value)} />
      <button type="submit">Zmień hasło</button>
    </form>
  )
}
```

---

### ✅ 1.3 HATEOAS Links (20 min)
**Wpływ:** API +5%

**Trait:** `src/Dto/HateoasTrait.php`
```php
<?php

namespace App\Dto;

trait HateoasTrait
{
    private array $links = [];

    public function addLink(string $rel, string $href): void
    {
        $this->links[$rel] = $href;
    }

    public function getLinks(): array
    {
        return $this->links;
    }
}
```

**Użycie w BookDto:**
```php
class BookDto
{
    use HateoasTrait;

    // ... other properties

    public static function fromEntity(Book $book): self
    {
        $dto = new self();
        // ... map properties
        
        $dto->addLink('self', '/api/books/' . $book->getId());
        $dto->addLink('copies', '/api/books/' . $book->getId() . '/copies');
        $dto->addLink('loans', '/api/books/' . $book->getId() . '/loans');
        
        return $dto;
    }
}
```

---

### ✅ 1.4 Admin Documentation (15 min)
**Wpływ:** Dokumentacja API +5%

**Do zrobienia:**
- Dodać OpenAPI attributes do wszystkich endpointów w `AdminUserController.php`
- Dodać przykłady requestów/responses
- Dodać opis permissions

```php
#[Route('/api/admin/users', methods: ['GET'])]
#[OA\Get(
    path: '/api/admin/users',
    summary: 'List all users (Admin only)',
    tags: ['Admin'],
    parameters: [
        new OA\Parameter(
            name: 'page',
            in: 'query',
            description: 'Page number',
            schema: new OA\Schema(type: 'integer', default: 1)
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Success',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/User')),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta')
                ]
            )
        ),
        new OA\Response(response: 403, description: 'Forbidden - Admin role required')
    ]
)]
public function listUsers(Request $request): JsonResponse
{
    // ...
}
```

---

## 🎯 Priorytet 2: Funkcjonalność 100% (2-3h)

### ✅ 2.1 Dashboard Statystyk (45 min)
**Wpływ:** Funkcjonalność +5%

**Backend:** `src/Controller/StatisticsController.php`
```php
#[Route('/api/statistics/dashboard', methods: ['GET'])]
public function dashboard(): JsonResponse
{
    $stats = [
        'activeLoans' => $this->loanRepository->countActive(),
        'overdueLoans' => $this->loanRepository->countOverdue(),
        'pendingReservations' => $this->reservationRepository->countPending(),
        'totalUsers' => $this->userRepository->count([]),
        'popularBooks' => $this->bookRepository->findMostPopular(10),
        'recentActivity' => $this->auditLogRepository->findRecent(20)
    ];
    
    return $this->json($stats);
}
```

**Frontend:** `src/pages/Dashboard.jsx`
```jsx
function LibrarianDashboard() {
  const [stats, setStats] = useState(null)

  useEffect(() => {
    loadStats()
  }, [])

  async function loadStats() {
    const data = await apiFetch('/api/statistics/dashboard')
    setStats(data)
  }

  if (!stats) return <div>Ładowanie...</div>

  return (
    <div className="dashboard">
      <StatGrid>
        <StatCard label="Aktywne wypożyczenia" value={stats.activeLoans} />
        <StatCard label="Zaległe zwroty" value={stats.overdueLoans} alert />
        <StatCard label="Rezerwacje" value={stats.pendingReservations} />
        <StatCard label="Użytkownicy" value={stats.totalUsers} />
      </StatGrid>
      
      <SectionCard title="Popularne książki">
        {stats.popularBooks.map(book => (
          <BookItem key={book.id} book={book} />
        ))}
      </SectionCard>
      
      <SectionCard title="Ostatnia aktywność">
        <ActivityLog items={stats.recentActivity} />
      </SectionCard>
    </div>
  )
}
```

---

### ✅ 2.2 Export CSV (30 min)
**Wpływ:** Funkcjonalność +3%

**Backend:** `src/Controller/ExportController.php`
```php
#[Route('/api/books/export', methods: ['GET'])]
public function exportBooks(): Response
{
    $books = $this->bookRepository->findAll();
    
    $csv = fopen('php://temp', 'r+');
    fputcsv($csv, ['ID', 'Tytuł', 'Autor', 'ISBN', 'Rok', 'Kategoria']);
    
    foreach ($books as $book) {
        fputcsv($csv, [
            $book->getId(),
            $book->getTitle(),
            $book->getAuthorName(),
            $book->getIsbn(),
            $book->getPublicationYear(),
            $book->getCategory()?->getName()
        ]);
    }
    
    rewind($csv);
    $content = stream_get_contents($csv);
    fclose($csv);
    
    return new Response($content, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="books.csv"'
    ]);
}
```

**Frontend:**
```jsx
async function exportBooks() {
  const response = await fetch('http://localhost:8000/api/books/export', {
    headers: { 'Authorization': `Bearer ${token}` }
  })
  
  const blob = await response.blob()
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'books.csv'
  a.click()
}
```

---

### ✅ 2.3 Skeleton Loaders (30 min)
**Wpływ:** UX +5%

**Component:** `src/components/ui/Skeleton.jsx`
```jsx
export function Skeleton({ width = '100%', height = '20px', borderRadius = '4px' }) {
  return (
    <div
      className="skeleton"
      style={{ width, height, borderRadius }}
    />
  )
}

export function BookSkeleton() {
  return (
    <div className="book-item">
      <Skeleton width="80px" height="120px" />
      <div>
        <Skeleton width="200px" height="20px" />
        <Skeleton width="150px" height="16px" />
        <Skeleton width="100px" height="16px" />
      </div>
    </div>
  )
}
```

**CSS:** `src/styles/skeleton.css`
```css
.skeleton {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
}

@keyframes loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
```

**Użycie:**
```jsx
{loading ? (
  <>
    <BookSkeleton />
    <BookSkeleton />
    <BookSkeleton />
  </>
) : (
  books.map(book => <BookItem key={book.id} book={book} />)
)}
```

---

## 🎯 Priorytet 3: Architektura & Kod (2-3h)

### ✅ 3.1 State Management - Zustand (30 min)
**Wpływ:** Architektura +5%

```bash
npm install zustand
```

**Store:** `src/store/authStore.js`
```javascript
import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export const useAuthStore = create(
  persist(
    (set) => ({
      user: null,
      token: null,
      isAuthenticated: false,
      
      login: (user, token) => set({ user, token, isAuthenticated: true }),
      logout: () => set({ user: null, token: null, isAuthenticated: false }),
      updateUser: (user) => set({ user })
    }),
    { name: 'auth-storage' }
  )
)
```

**Użycie:**
```jsx
function Header() {
  const { user, isAuthenticated, logout } = useAuthStore()
  
  if (!isAuthenticated) return null
  
  return (
    <div>
      Welcome, {user.firstName}
      <button onClick={logout}>Logout</button>
    </div>
  )
}
```

---

### ✅ 3.2 Domain Events (45 min)
**Wpływ:** Architektura +5%, Kod +3%

**Event:** `src/Event/BookBorrowedEvent.php`
```php
<?php

namespace App\Event;

use App\Entity\Loan;
use Symfony\Contracts\EventDispatcher\Event;

final class BookBorrowedEvent extends Event
{
    public function __construct(
        private readonly Loan $loan
    ) {}

    public function getLoan(): Loan
    {
        return $this->loan;
    }
}
```

**Listener:** `src/EventSubscriber/BookBorrowedSubscriber.php`
```php
<?php

namespace App\EventSubscriber;

use App\Event\BookBorrowedEvent;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class BookBorrowedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            BookBorrowedEvent::class => 'onBookBorrowed'
        ];
    }

    public function onBookBorrowed(BookBorrowedEvent $event): void
    {
        $loan = $event->getLoan();
        
        // Send notification
        $this->notificationService->sendLoanConfirmation($loan);
        
        // Update stats
        // Log audit
        // etc.
    }
}
```

**Dispatch:**
```php
// In LoanService
$loan = $this->createLoan($user, $book);
$this->eventDispatcher->dispatch(new BookBorrowedEvent($loan));
```

---

### ✅ 3.3 PHPDoc Coverage (45 min)
**Wpływ:** Kod +5%, Dokumentacja +2%

**Przykład:**
```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Book;
use App\Entity\User;
use App\Entity\Loan;
use App\Exception\LoanLimitExceededException;
use App\Exception\BookNotAvailableException;

/**
 * Service for managing book loans.
 *
 * Handles the complete lifecycle of book loans including creation,
 * extension, return, and fine calculation.
 */
final class LoanService
{
    /**
     * Creates a new loan for the given user and book.
     *
     * @param User $user The user borrowing the book
     * @param Book $book The book being borrowed
     * @param int $durationDays Loan duration in days (default: 14)
     *
     * @return Loan The created loan
     *
     * @throws LoanLimitExceededException When user has reached max active loans
     * @throws BookNotAvailableException When book has no available copies
     */
    public function createLoan(User $user, Book $book, int $durationDays = 14): Loan
    {
        // Implementation
    }

    /**
     * Calculates the fine for an overdue loan.
     *
     * Fine calculation:
     * - Days 1-7: 1 PLN per day
     * - Days 8-14: 2 PLN per day
     * - Days 15+: 5 PLN per day
     *
     * @param Loan $loan The overdue loan
     *
     * @return float The fine amount in PLN
     */
    public function calculateFine(Loan $loan): float
    {
        // Implementation
    }
}
```

**Generowanie dokumentacji:**
```bash
cd backend
vendor/bin/phpdoc -d src -t docs/api
```

---

## 🎯 Priorytet 4: Opcjonalne Ulepszenia (3-4h)

### ✅ 4.1 API Versioning (30 min)
```php
// config/routes/api_v1.yaml
api_v1:
  resource: ../src/Controller/
  type: attribute
  prefix: /api/v1
```

### ✅ 4.2 Rate Limiting (20 min)
```bash
composer require symfony/rate-limiter
```

### ✅ 4.3 TypeScript Migration (4h+)
```bash
npm install -D typescript @types/react @types/react-dom
```

### ✅ 4.4 E2E Tests (3h+)
```bash
npm install -D playwright
npx playwright install
```

---

## 📊 Podsumowanie Wpływu

| Zmiana | Czas | Wpływ | Priorytet |
|--------|------|-------|-----------|
| Toast Notifications | 15 min | UX +5% | ⭐⭐⭐ |
| Zmiana hasła | 20 min | Funkcja +5% | ⭐⭐⭐ |
| HATEOAS | 20 min | API +5% | ⭐⭐⭐ |
| Admin Docs | 15 min | Docs +5% | ⭐⭐⭐ |
| Dashboard | 45 min | Funkcja +5% | ⭐⭐⭐ |
| Export CSV | 30 min | Funkcja +3% | ⭐⭐ |
| Skeleton | 30 min | UX +5% | ⭐⭐⭐ |
| Zustand | 30 min | Arch +5% | ⭐⭐ |
| Domain Events | 45 min | Arch +5% | ⭐⭐ |
| PHPDoc | 45 min | Kod +5% | ⭐⭐ |

**Total:** ~4.5h → **100/100 w kluczowych kryteriach**

---

## 🚀 Plan Implementacji

### Dzień 1 (2h) - Quick Wins
1. Toast notifications (15 min)
2. Zmiana hasła (20 min)
3. HATEOAS links (20 min)
4. Admin documentation (15 min)
5. Skeleton loaders (30 min)
6. Export CSV (30 min)

### Dzień 2 (2h) - Funkcjonalność
1. Dashboard statystyk (45 min)
2. State management Zustand (30 min)
3. Domain events (45 min)

### Dzień 3 (2h) - Jakość
1. PHPDoc coverage (45 min)
2. Refactoring długich metod (45 min)
3. Final testing & documentation (30 min)

---

## ✅ Checklist

- [ ] Toast notifications
- [ ] Zmiana hasła
- [ ] HATEOAS links
- [ ] Admin API docs
- [ ] Dashboard statystyk
- [ ] Export CSV
- [ ] Skeleton loaders
- [ ] Zustand state management
- [ ] Domain events
- [ ] PHPDoc coverage
- [ ] Refactoring długich metod
- [ ] API versioning (optional)
- [ ] Rate limiting (optional)
- [ ] TypeScript (optional)

**Po realizacji:** Re-audit → Ocena 100/100 ✅
