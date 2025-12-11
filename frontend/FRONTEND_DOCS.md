# Frontend Biblioteki - Pełna Dokumentacja

## 📋 Spis Treści

1. [Przegląd](#przegląd)
2. [Architektura](#architektura)
3. [Komponenty](#komponenty)
4. [Usługi API](#usługi-api)
5. [Strony](#strony)
6. [Instalacja](#instalacja)
7. [Struktura Projektu](#struktura-projektu)

## 🎯 Przegląd

Pełnofunkcjonalny frontend aplikacji bibliotecznej zbudowany w React 18 z Vite.

### Funkcjonalności

- ✅ **Katalog książek** - przeglądanie, wyszukiwanie, filtrowanie
- ✅ **Wypożyczenia** - zarządzanie wypożyczeniami, przedłużanie, zwroty
- ✅ **Rezerwacje** - rezerwowanie książek, anulowanie
- ✅ **Ulubione** - lista ulubionych książek
- ✅ **Ogłoszenia** - system ogłoszeń bibliotecznych
- ✅ **Panel administratora** - zarządzanie użytkownikami
- ✅ **Panel bibliotekarza** - zarządzanie wypożyczeniami
- ✅ **Profil użytkownika** - edycja danych, zmiana hasła
- ✅ **Autentykacja** - logowanie, rejestracja, JWT

### Technologie

- **React** 18.2.0 - główny framework
- **React Router** 6.14.1 - routing
- **Vite** 5.0.0 - bundler
- **Axios** - HTTP client
- **date-fns** - formatowanie dat
- **react-icons** - ikony

## 🏗️ Architektura

### Wzorce projektowe

1. **Service Layer Pattern** - warstwa usług API oddzielona od komponentów
2. **Context API** - zarządzanie stanem globalnym (Auth, Cache)
3. **Compound Components** - komponenty złożone (Modal, Pagination)
4. **Render Props** - RequireRole
5. **Custom Hooks** - useAuth, useResourceCache

### Struktura warstw

```
┌─────────────────┐
│     Pages       │ <- Strony/widoki
├─────────────────┤
│   Components    │ <- Komponenty UI
├─────────────────┤
│    Services     │ <- Warstwa API
├─────────────────┤
│    Contexts     │ <- Stan globalny
├─────────────────┤
│      API        │ <- HTTP wrapper
└─────────────────┘
```

## 🧩 Komponenty

### Podstawowe komponenty UI

#### LoadingSpinner
```jsx
import LoadingSpinner from './components/LoadingSpinner'

<LoadingSpinner size="medium" message="Ładowanie..." />
```

**Props:**
- `size`: 'small' | 'medium' | 'large'
- `message`: string (opcjonalne)

#### ErrorMessage
```jsx
import ErrorMessage from './components/ErrorMessage'

<ErrorMessage 
  error="Wystąpił błąd" 
  onDismiss={() => setError(null)} 
/>
```

**Props:**
- `error`: string | Error
- `onDismiss`: () => void

#### SuccessMessage
```jsx
import SuccessMessage from './components/SuccessMessage'

<SuccessMessage 
  message="Operacja zakończona sukcesem" 
  onDismiss={() => setSuccess(null)} 
/>
```

#### Modal
```jsx
import Modal from './components/Modal'

<Modal
  isOpen={isOpen}
  onClose={() => setIsOpen(false)}
  title="Tytuł modala"
  footer={<button onClick={handleSave}>Zapisz</button>}
>
  <p>Zawartość modala</p>
</Modal>
```

#### Pagination
```jsx
import Pagination from './components/Pagination'

<Pagination
  currentPage={currentPage}
  totalPages={10}
  onPageChange={setCurrentPage}
/>
```

#### SearchBar
```jsx
import SearchBar from './components/SearchBar'

<SearchBar
  placeholder="Szukaj książek..."
  onSearch={(query) => console.log(query)}
/>
```

**Funkcje:**
- Autocomplete z sugestiami
- Debouncing (300ms)
- Nawigacja do wyników

#### FilterPanel
```jsx
import FilterPanel from './components/FilterPanel'

<FilterPanel
  filters={filters}
  onFilterChange={setFilters}
  availableFilters={{
    genres: ['Fantasy', 'Sci-Fi'],
    authors: ['J.R.R. Tolkien']
  }}
/>
```

### Karty specjalistyczne

#### AnnouncementCard
```jsx
import AnnouncementCard from './components/AnnouncementCard'

<AnnouncementCard
  announcement={announcement}
  onClick={() => navigate(`/announcements/${announcement.id}`)}
/>
```

**Wyświetla:**
- Typ (info/warning/success/error)
- Przypięcie
- Autora i datę
- Podgląd treści (200 znaków)

#### LoanCard
```jsx
import LoanCard from './components/LoanCard'

<LoanCard
  loan={loan}
  onReturn={(id) => handleReturn(id)}
  onExtend={(id) => handleExtend(id)}
/>
```

**Funkcje:**
- Status wypożyczenia
- Licznik dni do zwrotu
- Ostrzeżenia o zaległościach
- Przycisk zwrotu/przedłużenia
- Limit 3 przedłużeń

#### ReservationCard
```jsx
import ReservationCard from './components/ReservationCard'

<ReservationCard
  reservation={reservation}
  onCancel={(id) => handleCancel(id)}
  onFulfill={(id) => handleFulfill(id)}
/>
```

**Statusy:**
- pending - oczekująca
- ready - gotowa do odbioru
- fulfilled - zrealizowana
- cancelled - anulowana
- expired - wygasła

#### StatCard
```jsx
import StatCard from './components/StatCard'
import { FaBook } from 'react-icons/fa'

<StatCard
  icon={FaBook}
  value={42}
  label="Wypożyczenia"
  trend={15}
  color="primary"
/>
```

#### EmptyState
```jsx
import EmptyState from './components/EmptyState'
import { FaInbox } from 'react-icons/fa'

<EmptyState
  icon={FaInbox}
  title="Brak wyników"
  message="Nie znaleziono książek"
  action={<button>Dodaj książkę</button>}
/>
```

## 🔌 Usługi API

### bookService

```javascript
import { bookService } from './services/bookService'

// Lista książek z filtrami
const books = await bookService.getBooks({
  page: 1,
  limit: 20,
  genre: 'Fantasy',
  author: 'Tolkien',
  availableOnly: true
})

// Pojedyncza książka
const book = await bookService.getBook(bookId)

// Dostępne filtry
const filters = await bookService.getFilters()

// Wyszukiwanie
const results = await bookService.search('hobbit')

// Polecane książki
const recommended = await bookService.getRecommended()

// Popularne
const popular = await bookService.getPopular(10)

// Nowości
const newBooks = await bookService.getNewArrivals(10)

// Sprawdź dostępność
const availability = await bookService.getAvailability(bookId)
```

### loanService

```javascript
import { loanService } from './services/loanService'

// Moje wypożyczenia
const myLoans = await loanService.getMyLoans()

// Wszystkie (admin)
const allLoans = await loanService.getAllLoans({ 
  status: 'active',
  overdue: true 
})

// Nowe wypożyczenie
const loan = await loanService.createLoan(bookId, userId)

// Zwrot
await loanService.returnLoan(loanId)

// Przedłużenie
await loanService.extendLoan(loanId)

// Statystyki
const stats = await loanService.getStatistics()
```

### reservationService

```javascript
import { reservationService } from './services/reservationService'

// Moje rezerwacje
const myReservations = await reservationService.getMyReservations()

// Wszystkie (admin)
const all = await reservationService.getAllReservations({
  status: 'pending'
})

// Nowa rezerwacja
const reservation = await reservationService.createReservation(bookId)

// Anuluj
await reservationService.cancelReservation(reservationId)

// Zrealizuj (bibliotekarz)
await reservationService.fulfillReservation(reservationId)
```

### userService

```javascript
import { userService } from './services/userService'

// Profil
const profile = await userService.getProfile()

// Aktualizacja profilu
await userService.updateProfile({
  name: 'Jan Kowalski',
  email: 'jan@example.com',
  phoneNumber: '123456789'
})

// Zmiana hasła
await userService.changePassword('oldPass', 'newPass')

// Ulubione
const favorites = await userService.getFavorites()
await userService.addFavorite(bookId)
await userService.removeFavorite(favoriteId)

// Użytkownicy (admin)
const users = await userService.getAllUsers({ page: 1, limit: 50 })
```

### announcementService

```javascript
import { announcementService } from './services/announcementService'

// Lista ogłoszeń
const announcements = await announcementService.getAnnouncements({
  page: 1,
  limit: 10,
  type: 'info',
  showOnHomepage: true
})

// Pojedyncze ogłoszenie
const announcement = await announcementService.getAnnouncement(id)

// Utworz (admin)
await announcementService.createAnnouncement({
  title: 'Tytuł',
  content: 'Treść',
  type: 'info',
  isPinned: false
})

// Aktualizuj (admin)
await announcementService.updateAnnouncement(id, { title: 'Nowy tytuł' })

// Opublikuj
await announcementService.publishAnnouncement(id)

// Archiwizuj
await announcementService.archiveAnnouncement(id)

// Usuń
await announcementService.deleteAnnouncement(id)
```

## 📄 Strony

### Dashboard (`/`)
- Hero sekcja z CTA
- Statystyki użytkownika (wypożyczenia, zaległości, ulubione)
- Ogłoszenia (3 najnowsze)
- Popularne książki (6)
- Nowości (6)
- Szybkie akcje

### Books (`/books`)
- Katalog książek z paginacją
- Zaawansowane filtrowanie (gatunek, autor, rok)
- Wyszukiwanie pełnotekstowe
- Faceted search
- Cache z ResourceCacheContext

### BookDetails (`/books/:id`)
- Szczegóły książki
- Dostępność
- Przycisk wypożyczenia/rezerwacji
- Polecane podobne książki

### MyLoans (`/my-loans`)
- Lista wypożyczeń
- Filtrowanie (aktywne/zwrócone/zaległe)
- Przycisk zwrotu
- Przycisk przedłużenia (max 3x)
- Ostrzeżenia o zaległościach

### Reservations (`/reservations`)
- Lista rezerwacji
- Statusy (oczekująca/gotowa/zrealizowana)
- Przycisk anulowania
- Powiadomienia o gotowości

### Favorites (`/favorites`)
- Ulubione książki
- Dodawanie/usuwanie
- Szybkie wypożyczanie

### Profile (`/profile`)
- Dane osobowe (edycja)
- Zmiana hasła
- Historia wypożyczeń

### Announcements (`/announcements`)
- Lista ogłoszeń
- Filtrowanie (typ, archiwalne)
- Paginacja
- Szczegóły ogłoszenia

### AdminPanel (`/admin`)
- Zarządzanie użytkownikami
- Statystyki systemowe
- Zarządzanie książkami (CRUD)

### LibrarianPanel (`/librarian`)
- Zarządzanie wypożyczeniami
- Budżet akwizycji
- Raporty wykorzystania

## 📦 Instalacja

### Wymagania

- Node.js 18+
- npm lub yarn

### Kroki instalacji

```bash
# 1. Przejdź do katalogu frontend
cd frontend

# 2. Zainstaluj zależności
npm install

# 3. Skonfiguruj zmienne środowiskowe (opcjonalne)
# Utwórz plik .env
VITE_API_URL=http://localhost:8000

# 4. Uruchom serwer deweloperski
npm run dev

# 5. Build produkcyjny
npm run build

# 6. Podgląd buildu
npm run preview
```

### Dostępne komendy

```bash
npm run dev        # Serwer deweloperski (localhost:5173)
npm run build      # Build produkcyjny
npm run preview    # Podgląd buildu
npm run lint       # Linting (jeśli skonfigurowany)
```

## 📂 Struktura Projektu

```
frontend/
├── src/
│   ├── api.js                  # HTTP wrapper (apiFetch)
│   ├── App.jsx                 # Główny komponent + routing
│   ├── main.jsx                # Entry point
│   │
│   ├── components/             # Komponenty UI
│   │   ├── AnnouncementCard.jsx
│   │   ├── BookItem.jsx
│   │   ├── EmptyState.jsx
│   │   ├── ErrorMessage.jsx
│   │   ├── FilterPanel.jsx
│   │   ├── LoadingSpinner.jsx
│   │   ├── LoanCard.jsx
│   │   ├── Modal.jsx
│   │   ├── Navbar.jsx
│   │   ├── Pagination.jsx
│   │   ├── RequireRole.jsx
│   │   ├── ReservationCard.jsx
│   │   ├── SearchBar.jsx
│   │   ├── StatCard.jsx
│   │   └── SuccessMessage.jsx
│   │
│   ├── context/                # Context API
│   │   ├── AuthContext.jsx
│   │   └── ResourceCacheContext.jsx
│   │
│   ├── pages/                  # Strony/widoki
│   │   ├── AdminPanel.jsx
│   │   ├── Announcements.jsx
│   │   ├── BookDetails.jsx
│   │   ├── Books.jsx
│   │   ├── Dashboard.jsx
│   │   ├── Favorites.jsx
│   │   ├── LibrarianPanel.jsx
│   │   ├── Login.jsx
│   │   ├── MyLoans.jsx
│   │   ├── Profile.jsx
│   │   ├── Recommended.jsx
│   │   ├── Register.jsx
│   │   └── Reservations.jsx
│   │
│   ├── services/               # Warstwa API
│   │   ├── announcementService.js
│   │   ├── bookService.js
│   │   ├── loanService.js
│   │   ├── reservationService.js
│   │   └── userService.js
│   │
│   └── styles/                 # Style CSS
│       ├── components.css      # Style komponentów
│       ├── main.css            # Główne style
│       └── styles.css          # Style bazowe
│
├── index.html
├── package.json
├── vite.config.js
└── README.md
```

## 🎨 Style

### CSS Variables

```css
:root {
  --primary-color: #2563eb;
  --success-color: #10b981;
  --warning-color: #f59e0b;
  --error-color: #ef4444;
  --border-radius: 8px;
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
```

### Klasy użytkowe

```html
<!-- Buttony -->
<button class="btn btn-primary">Primary</button>
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-success">Success</button>
<button class="btn btn-danger">Danger</button>
<button class="btn btn-outline">Outline</button>
<button class="btn btn-sm">Small</button>
<button class="btn btn-lg">Large</button>

<!-- Alerty -->
<div class="alert alert-error">Błąd</div>
<div class="alert alert-success">Sukces</div>
<div class="alert alert-warning">Ostrzeżenie</div>

<!-- Karty -->
<div class="card">
  <div class="card-header">
    <h2 class="card-title">Tytuł</h2>
  </div>
  <div class="card-body">Zawartość</div>
</div>

<!-- Siatki -->
<div class="books-grid">...</div>
<div class="stats-grid">...</div>

<!-- Margines/padding -->
<div class="mt-2 mb-3 p-2">...</div>
```

## 🔐 Autentykacja

### AuthContext

```jsx
import { useAuth } from './context/AuthContext'

function MyComponent() {
  const { user, token, login, logout, register } = useAuth()

  async function handleLogin() {
    await login('user@example.com', 'password')
  }

  return (
    <div>
      {user && <p>Witaj, {user.name}!</p>}
      {!user && <button onClick={handleLogin}>Zaloguj</button>}
    </div>
  )
}
```

### Zabezpieczanie tras

```jsx
import RequireRole from './components/RequireRole'

<Route
  path="/admin"
  element={
    <RequireRole allowed={['ROLE_ADMIN']}>
      <AdminPanel />
    </RequireRole>
  }
/>
```

## 📊 Cache

### ResourceCacheContext

```jsx
import { useResourceCache } from './context/ResourceCacheContext'

function MyComponent() {
  const { getCachedResource, setCachedResource, invalidateResource } = useResourceCache()

  async function loadData() {
    const cached = getCachedResource('books', 60000) // 60s TTL
    if (cached) return cached

    const data = await fetchBooks()
    setCachedResource('books', data)
    return data
  }

  function handleUpdate() {
    invalidateResource('books*') // Wildcard
  }
}
```

## ✨ Najlepsze praktyki

### 1. Obsługa błędów

```jsx
try {
  const data = await bookService.getBooks()
  setBooks(data)
} catch (error) {
  setError(error.message || 'Wystąpił błąd')
}
```

### 2. Loading states

```jsx
if (loading) return <LoadingSpinner />
if (error) return <ErrorMessage error={error} />
return <div>{/* content */}</div>
```

### 3. Optymistyczne UI

```jsx
async function handleLike(bookId) {
  // Zaktualizuj UI natychmiast
  setLiked(true)
  
  try {
    await bookService.addFavorite(bookId)
  } catch (error) {
    // Cofnij w przypadku błędu
    setLiked(false)
    setError(error.message)
  }
}
```

### 4. Debouncing w search

```jsx
useEffect(() => {
  const timeout = setTimeout(() => {
    performSearch(query)
  }, 300)

  return () => clearTimeout(timeout)
}, [query])
```

## 🚀 Wydajność

### Optymalizacje

1. **Code splitting** - dynamiczne importy
2. **Lazy loading** - React.lazy
3. **Memoizacja** - React.memo, useMemo, useCallback
4. **Cache** - ResourceCacheContext
5. **Paginacja** - zamiast nieskończonego scrollowania
6. **Prefetching** - onMouseEnter w Navbar

### Przykład lazy loading

```jsx
const AdminPanel = React.lazy(() => import('./pages/AdminPanel'))

<Suspense fallback={<LoadingSpinner />}>
  <Route path="/admin" element={<AdminPanel />} />
</Suspense>
```

## 📱 Responsywność

Wszystkie komponenty są w pełni responsywne:

- Mobile: < 640px
- Tablet: 640px - 1024px
- Desktop: > 1024px

```css
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
```

## 🧪 Testowanie (do dodania)

```bash
# Unit tests
npm run test

# E2E tests
npm run test:e2e

# Coverage
npm run test:coverage
```

## 📝 TODO

- [ ] Testy jednostkowe (Jest + React Testing Library)
- [ ] Testy E2E (Playwright/Cypress)
- [ ] Dark mode
- [ ] i18n (wielojęzyczność)
- [ ] PWA (Progressive Web App)
- [ ] Notyfikacje push
- [ ] Eksport do PDF (historia wypożyczeń)
- [ ] Infinite scroll dla książek
- [ ] Drag & drop dla uploadów

## 📄 Licencja

MIT

## 👥 Autorzy

System biblioteczny - Frontend

---

**Pełnofunkcjonalny frontend gotowy do użycia! 🎉**
