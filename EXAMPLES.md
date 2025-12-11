# 📘 Przykłady Użycia - Frontend API

## 🎯 Spis Treści

1. [Inicjalizacja](#inicjalizacja)
2. [Autoryzacja](#autoryzacja)
3. [Książki](#książki)
4. [Wypożyczenia](#wypożyczenia)
5. [Rezerwacje](#rezerwacje)
6. [Użytkownik](#użytkownik)
7. [Ogłoszenia](#ogłoszenia)
8. [Obsługa błędów](#obsługa-błędów)

---

## Inicjalizacja

### Import serwisów

```javascript
import { bookService } from './services/bookService'
import { loanService } from './services/loanService'
import { reservationService } from './services/reservationService'
import { userService } from './services/userService'
import { announcementService } from './services/announcementService'
```

---

## Autoryzacja

### Logowanie

```javascript
import { useAuth } from './context/AuthContext'

function LoginPage() {
  const { login, error, loading } = useAuth()

  async function handleLogin(email, password) {
    try {
      await login(email, password)
      // Przekierowanie po zalogowaniu
      navigate('/dashboard')
    } catch (err) {
      console.error('Login failed:', err)
    }
  }

  return (
    <form onSubmit={(e) => {
      e.preventDefault()
      handleLogin(email, password)
    }}>
      {/* Formularz */}
    </form>
  )
}
```

### Rejestracja

```javascript
const { register } = useAuth()

async function handleRegister(formData) {
  try {
    await register({
      email: formData.email,
      password: formData.password,
      name: formData.name,
      phoneNumber: formData.phone
    })
    navigate('/login')
  } catch (err) {
    setError(err.message)
  }
}
```

### Wylogowanie

```javascript
const { logout } = useAuth()

function handleLogout() {
  logout()
  navigate('/login')
}
```

### Sprawdzanie autoryzacji

```javascript
const { user, token } = useAuth()

if (!user) {
  return <Navigate to="/login" />
}

// Sprawdzanie ról
const isAdmin = user?.roles?.includes('ROLE_ADMIN')
const isLibrarian = user?.roles?.includes('ROLE_LIBRARIAN')
```

---

## Książki

### Lista książek z filtrowaniem

```javascript
import { useState, useEffect } from 'react'
import { bookService } from './services/bookService'

function BooksPage() {
  const [books, setBooks] = useState([])
  const [loading, setLoading] = useState(true)
  const [filters, setFilters] = useState({
    page: 1,
    limit: 20,
    genre: '',
    author: '',
    availableOnly: false
  })

  useEffect(() => {
    async function loadBooks() {
      setLoading(true)
      try {
        const data = await bookService.getBooks(filters)
        setBooks(data.items || data)
      } catch (err) {
        console.error(err)
      } finally {
        setLoading(false)
      }
    }

    loadBooks()
  }, [filters])

  return (
    <div>
      {/* Renderuj książki */}
    </div>
  )
}
```

### Wyszukiwanie książek

```javascript
import { SearchBar } from './components/SearchBar'

function BooksPage() {
  const [query, setQuery] = useState('')
  const [results, setResults] = useState([])

  async function handleSearch(searchQuery) {
    try {
      const data = await bookService.search(searchQuery)
      setResults(data)
    } catch (err) {
      console.error(err)
    }
  }

  return (
    <div>
      <SearchBar onSearch={handleSearch} />
      {/* Wyniki */}
    </div>
  )
}
```

### Szczegóły książki

```javascript
import { useParams } from 'react-router-dom'

function BookDetailsPage() {
  const { id } = useParams()
  const [book, setBook] = useState(null)
  const [availability, setAvailability] = useState(null)

  useEffect(() => {
    async function loadBook() {
      try {
        const [bookData, availData] = await Promise.all([
          bookService.getBook(id),
          bookService.getAvailability(id)
        ])
        setBook(bookData)
        setAvailability(availData)
      } catch (err) {
        console.error(err)
      }
    }

    loadBook()
  }, [id])

  return (
    <div>
      <h1>{book?.title}</h1>
      <p>Dostępnych: {availability?.availableCopies || 0}</p>
    </div>
  )
}
```

### Polecane książki

```javascript
function RecommendedPage() {
  const [recommended, setRecommended] = useState([])

  useEffect(() => {
    async function loadRecommended() {
      try {
        const data = await bookService.getRecommended()
        setRecommended(data)
      } catch (err) {
        console.error(err)
      }
    }

    loadRecommended()
  }, [])

  return <BookGrid books={recommended} />
}
```

### Popularne książki

```javascript
async function loadPopular() {
  const popular = await bookService.getPopular(10)
  setPopularBooks(popular)
}
```

### Nowości

```javascript
async function loadNewBooks() {
  const newBooks = await bookService.getNewArrivals(10)
  setNewBooks(newBooks)
}
```

### Dostępne filtry

```javascript
const [availableFilters, setAvailableFilters] = useState({})

useEffect(() => {
  async function loadFilters() {
    const filters = await bookService.getFilters()
    setAvailableFilters(filters)
    // filters = { genres: [...], authors: [...], years: [...] }
  }

  loadFilters()
}, [])
```

---

## Wypożyczenia

### Moje wypożyczenia

```javascript
import { LoanCard } from './components/LoanCard'

function MyLoansPage() {
  const [loans, setLoans] = useState([])

  useEffect(() => {
    async function loadLoans() {
      try {
        const data = await loanService.getMyLoans()
        setLoans(data)
      } catch (err) {
        console.error(err)
      }
    }

    loadLoans()
  }, [])

  return (
    <div>
      {loans.map(loan => (
        <LoanCard
          key={loan.id}
          loan={loan}
          onReturn={handleReturn}
          onExtend={handleExtend}
        />
      ))}
    </div>
  )
}
```

### Wypożyczenie książki

```javascript
async function handleBorrow(bookId) {
  try {
    const loan = await loanService.createLoan(bookId, user.id)
    setSuccess('Książka została wypożyczona!')
    // Odśwież listę wypożyczeń
    loadLoans()
  } catch (err) {
    setError(err.message || 'Nie udało się wypożyczyć książki')
  }
}
```

### Zwrot książki

```javascript
async function handleReturn(loanId) {
  if (!confirm('Czy na pewno chcesz zwrócić tę książkę?')) return

  try {
    await loanService.returnLoan(loanId)
    setSuccess('Książka została zwrócona')
    loadLoans()
  } catch (err) {
    setError(err.message)
  }
}
```

### Przedłużenie wypożyczenia

```javascript
async function handleExtend(loanId) {
  try {
    const updatedLoan = await loanService.extendLoan(loanId)
    setSuccess(`Przedłużono do ${formatDate(updatedLoan.dueDate)}`)
    loadLoans()
  } catch (err) {
    setError(err.message || 'Nie można przedłużyć wypożyczenia')
  }
}
```

### Statystyki wypożyczeń

```javascript
const [stats, setStats] = useState(null)

async function loadStats() {
  const data = await loanService.getStatistics()
  setStats(data)
  // stats = { 
  //   activeLoans: 3, 
  //   overdueLoans: 1, 
  //   totalBorrowed: 42 
  // }
}
```

### Wszystkie wypożyczenia (Admin)

```javascript
// Panel administratora
const [allLoans, setAllLoans] = useState([])

async function loadAllLoans() {
  const data = await loanService.getAllLoans({
    status: 'active',
    overdue: true,
    page: 1,
    limit: 50
  })
  setAllLoans(data.items)
}
```

---

## Rezerwacje

### Moje rezerwacje

```javascript
import { ReservationCard } from './components/ReservationCard'

function ReservationsPage() {
  const [reservations, setReservations] = useState([])

  useEffect(() => {
    async function loadReservations() {
      const data = await reservationService.getMyReservations()
      setReservations(data)
    }

    loadReservations()
  }, [])

  return (
    <div>
      {reservations.map(reservation => (
        <ReservationCard
          key={reservation.id}
          reservation={reservation}
          onCancel={handleCancel}
        />
      ))}
    </div>
  )
}
```

### Rezerwacja książki

```javascript
async function handleReserve(bookId) {
  try {
    const reservation = await reservationService.createReservation(bookId)
    setSuccess('Książka została zarezerwowana')
    navigate('/reservations')
  } catch (err) {
    setError(err.message || 'Nie udało się zarezerwować książki')
  }
}
```

### Anulowanie rezerwacji

```javascript
async function handleCancel(reservationId) {
  if (!confirm('Czy na pewno chcesz anulować rezerwację?')) return

  try {
    await reservationService.cancelReservation(reservationId)
    setSuccess('Rezerwacja została anulowana')
    loadReservations()
  } catch (err) {
    setError(err.message)
  }
}
```

### Realizacja rezerwacji (Bibliotekarz)

```javascript
async function handleFulfill(reservationId) {
  try {
    const loan = await reservationService.fulfillReservation(reservationId)
    setSuccess('Rezerwacja została zrealizowana')
    // Przekieruj do wypożyczeń
    navigate(`/loans/${loan.id}`)
  } catch (err) {
    setError(err.message)
  }
}
```

---

## Użytkownik

### Profil użytkownika

```javascript
function ProfilePage() {
  const [profile, setProfile] = useState(null)

  useEffect(() => {
    async function loadProfile() {
      const data = await userService.getProfile()
      setProfile(data)
    }

    loadProfile()
  }, [])

  return (
    <form onSubmit={handleUpdate}>
      <input value={profile?.name} onChange={...} />
      <input value={profile?.email} onChange={...} />
      {/* Inne pola */}
    </form>
  )
}
```

### Aktualizacja profilu

```javascript
async function handleUpdate(formData) {
  try {
    await userService.updateProfile({
      name: formData.name,
      email: formData.email,
      phoneNumber: formData.phone,
      addressLine: formData.address,
      city: formData.city,
      postalCode: formData.postalCode
    })
    setSuccess('Profil został zaktualizowany')
  } catch (err) {
    setError(err.message)
  }
}
```

### Zmiana hasła

```javascript
const [passwordForm, setPasswordForm] = useState({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
})

async function handleChangePassword() {
  if (passwordForm.newPassword !== passwordForm.confirmPassword) {
    setError('Hasła nie są identyczne')
    return
  }

  try {
    await userService.changePassword(
      passwordForm.currentPassword,
      passwordForm.newPassword
    )
    setSuccess('Hasło zostało zmienione')
    setPasswordForm({ currentPassword: '', newPassword: '', confirmPassword: '' })
  } catch (err) {
    setError(err.message || 'Nie udało się zmienić hasła')
  }
}
```

### Ulubione książki

```javascript
function FavoritesPage() {
  const [favorites, setFavorites] = useState([])

  useEffect(() => {
    async function loadFavorites() {
      const data = await userService.getFavorites()
      setFavorites(data)
    }

    loadFavorites()
  }, [])

  async function handleAddFavorite(bookId) {
    try {
      await userService.addFavorite(bookId)
      setSuccess('Dodano do ulubionych')
      loadFavorites()
    } catch (err) {
      setError(err.message)
    }
  }

  async function handleRemoveFavorite(favoriteId) {
    try {
      await userService.removeFavorite(favoriteId)
      setSuccess('Usunięto z ulubionych')
      loadFavorites()
    } catch (err) {
      setError(err.message)
    }
  }

  return (
    <div>
      {favorites.map(fav => (
        <BookCard
          key={fav.id}
          book={fav.book}
          onRemove={() => handleRemoveFavorite(fav.id)}
        />
      ))}
    </div>
  )
}
```

---

## Ogłoszenia

### Lista ogłoszeń

```javascript
import { AnnouncementCard } from './components/AnnouncementCard'

function AnnouncementsPage() {
  const [announcements, setAnnouncements] = useState([])
  const [filters, setFilters] = useState({
    page: 1,
    limit: 10,
    type: '',
    showOnHomepage: false
  })

  useEffect(() => {
    async function loadAnnouncements() {
      const data = await announcementService.getAnnouncements(filters)
      setAnnouncements(data.items || data)
    }

    loadAnnouncements()
  }, [filters])

  return (
    <div>
      {announcements.map(announcement => (
        <AnnouncementCard
          key={announcement.id}
          announcement={announcement}
          onClick={() => navigate(`/announcements/${announcement.id}`)}
        />
      ))}
    </div>
  )
}
```

### Szczegóły ogłoszenia

```javascript
function AnnouncementDetailPage() {
  const { id } = useParams()
  const [announcement, setAnnouncement] = useState(null)

  useEffect(() => {
    async function loadAnnouncement() {
      const data = await announcementService.getAnnouncement(id)
      setAnnouncement(data)
    }

    loadAnnouncement()
  }, [id])

  return (
    <div>
      <h1>{announcement?.title}</h1>
      <p>{announcement?.content}</p>
    </div>
  )
}
```

### Tworzenie ogłoszenia (Admin)

```javascript
async function handleCreate(formData) {
  try {
    const announcement = await announcementService.createAnnouncement({
      title: formData.title,
      content: formData.content,
      type: formData.type, // 'info' | 'warning' | 'success' | 'error'
      isPinned: formData.isPinned,
      showOnHomepage: formData.showOnHomepage
    })
    setSuccess('Ogłoszenie zostało utworzone')
    navigate(`/announcements/${announcement.id}`)
  } catch (err) {
    setError(err.message)
  }
}
```

### Publikowanie ogłoszenia

```javascript
async function handlePublish(announcementId) {
  try {
    await announcementService.publishAnnouncement(announcementId)
    setSuccess('Ogłoszenie zostało opublikowane')
    loadAnnouncements()
  } catch (err) {
    setError(err.message)
  }
}
```

### Archiwizowanie ogłoszenia

```javascript
async function handleArchive(announcementId) {
  try {
    await announcementService.archiveAnnouncement(announcementId)
    setSuccess('Ogłoszenie zostało zarchiwizowane')
    loadAnnouncements()
  } catch (err) {
    setError(err.message)
  }
}
```

---

## Obsługa błędów

### Globalna obsługa błędów

```javascript
import { ErrorMessage } from './components/ErrorMessage'

function MyComponent() {
  const [error, setError] = useState(null)

  async function fetchData() {
    try {
      const data = await bookService.getBooks()
      // ...
    } catch (err) {
      setError(err.message || 'Wystąpił nieoczekiwany błąd')
    }
  }

  return (
    <div>
      {error && <ErrorMessage error={error} onDismiss={() => setError(null)} />}
      {/* Zawartość */}
    </div>
  )
}
```

### Obsługa błędów HTTP

```javascript
// api.js automatycznie obsługuje błędy HTTP

// 401 Unauthorized - automatyczne przekierowanie do /login
// 403 Forbidden - wyświetlenie błędu dostępu
// 404 Not Found - wyświetlenie błędu
// 500 Server Error - wyświetlenie błędu serwera

// Przykład customowej obsługi:
try {
  const data = await bookService.getBook(id)
} catch (err) {
  if (err.status === 404) {
    navigate('/404')
  } else if (err.status === 403) {
    setError('Brak dostępu do tej książki')
  } else {
    setError(err.message)
  }
}
```

### Loading states

```javascript
import { LoadingSpinner } from './components/LoadingSpinner'

function MyComponent() {
  const [loading, setLoading] = useState(true)
  const [data, setData] = useState(null)

  useEffect(() => {
    async function fetchData() {
      setLoading(true)
      try {
        const result = await bookService.getBooks()
        setData(result)
      } catch (err) {
        setError(err.message)
      } finally {
        setLoading(false)
      }
    }

    fetchData()
  }, [])

  if (loading) return <LoadingSpinner message="Ładowanie książek..." />
  if (error) return <ErrorMessage error={error} />
  if (!data) return <EmptyState title="Brak danych" />

  return <div>{/* Renderuj dane */}</div>
}
```

### Success messages

```javascript
import { SuccessMessage } from './components/SuccessMessage'

function MyComponent() {
  const [success, setSuccess] = useState(null)

  async function handleAction() {
    try {
      await someService.doSomething()
      setSuccess('Operacja zakończona sukcesem!')
      setTimeout(() => setSuccess(null), 3000) // Auto-dismiss po 3s
    } catch (err) {
      setError(err.message)
    }
  }

  return (
    <div>
      {success && <SuccessMessage message={success} onDismiss={() => setSuccess(null)} />}
      {/* Zawartość */}
    </div>
  )
}
```

---

## 🎯 Best Practices

### 1. Zawsze używaj try-catch

```javascript
async function fetchData() {
  try {
    const data = await service.getData()
    setData(data)
  } catch (err) {
    setError(err.message)
  }
}
```

### 2. Zarządzaj stanem loading

```javascript
const [loading, setLoading] = useState(false)

async function fetchData() {
  setLoading(true)
  try {
    // ...
  } finally {
    setLoading(false)
  }
}
```

### 3. Czyść efekty

```javascript
useEffect(() => {
  let cancelled = false

  async function fetchData() {
    const data = await service.getData()
    if (!cancelled) {
      setData(data)
    }
  }

  fetchData()

  return () => {
    cancelled = true
  }
}, [])
```

### 4. Używaj cache gdy możliwe

```javascript
import { useResourceCache } from './context/ResourceCacheContext'

const { getCachedResource, setCachedResource } = useResourceCache()

async function fetchData() {
  const cached = getCachedResource('books', 60000) // 60s TTL
  if (cached) {
    setData(cached)
    return
  }

  const data = await bookService.getBooks()
  setCachedResource('books', data)
  setData(data)
}
```

### 5. Debouncing dla search

```javascript
useEffect(() => {
  const timeout = setTimeout(() => {
    if (query.length >= 2) {
      performSearch(query)
    }
  }, 300)

  return () => clearTimeout(timeout)
}, [query])
```

---

## 📚 Dodatkowe Przykłady

### Kompletny komponent ze wszystkimi elementami

```javascript
import { useState, useEffect } from 'react'
import { bookService } from './services/bookService'
import LoadingSpinner from './components/LoadingSpinner'
import ErrorMessage from './components/ErrorMessage'
import SuccessMessage from './components/SuccessMessage'
import EmptyState from './components/EmptyState'
import Pagination from './components/Pagination'

function BooksPage() {
  const [books, setBooks] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)
  const [currentPage, setCurrentPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)

  useEffect(() => {
    loadBooks()
  }, [currentPage])

  async function loadBooks() {
    setLoading(true)
    setError(null)

    try {
      const data = await bookService.getBooks({ page: currentPage, limit: 20 })
      setBooks(data.items)
      setTotalPages(data.totalPages)
    } catch (err) {
      setError(err.message || 'Nie udało się załadować książek')
    } finally {
      setLoading(false)
    }
  }

  if (loading) {
    return <LoadingSpinner message="Ładowanie książek..." />
  }

  return (
    <div className="books-page">
      {error && <ErrorMessage error={error} onDismiss={() => setError(null)} />}
      {success && <SuccessMessage message={success} onDismiss={() => setSuccess(null)} />}

      {books.length === 0 ? (
        <EmptyState
          title="Brak książek"
          message="Nie znaleziono żadnych książek"
        />
      ) : (
        <>
          <div className="books-grid">
            {books.map(book => (
              <BookItem key={book.id} book={book} />
            ))}
          </div>

          <Pagination
            currentPage={currentPage}
            totalPages={totalPages}
            onPageChange={setCurrentPage}
          />
        </>
      )}
    </div>
  )
}

export default BooksPage
```

---

**To wszystko! Frontend jest w pełni funkcjonalny i gotowy do użycia.** 🚀
