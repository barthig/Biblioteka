import React, { useEffect, useMemo, useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'
import { useResourceCache } from '../context/ResourceCacheContext'
import OnboardingModal from '../components/OnboardingModal'

export default function Dashboard() {
  const [stats, setStats] = useState(null)
  const [alerts, setAlerts] = useState([])
  const [libraryHours, setLibraryHours] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [searchQuery, setSearchQuery] = useState('')
  const [showOnboarding, setShowOnboarding] = useState(false)
  const { token, user } = useAuth()
  const navigate = useNavigate()
  const isAuthenticated = Boolean(token)
  const isLibrarian = user?.roles?.includes('ROLE_LIBRARIAN')
  const isAdmin = user?.roles?.includes('ROLE_ADMIN')
  const { prefetchResource } = useResourceCache()
  const prefetchScheduledRef = useRef(false)

  const publicHighlights = useMemo(() => ({
    facility: {
      name: 'Biblioteka Miejska w Poznaniu',
      description: 'Od ponad 30 lat wspieramy mieszkańców w odkrywaniu literatury i rozwijaniu pasji czytelniczych. Oferujemy wygodne strefy pracy, bogatą kolekcję książek oraz cykliczne warsztaty dla różnych grup wiekowych.',
      services: [
        '11 czytelni tematycznych i strefa coworkingowa',
        'Program „Pierwsza książka” dla najmłodszych czytelników',
        'Wsparcie bibliotekarzy w doborze lektur i pracy badawczej',
      ],
    },
    featuredTitles: [
      'Lalka — Bolesław Prus',
      'Cień wiatru — Carlos Ruiz Zafón',
      'Ziemia obiecana — Władysław Reymont',
      'Sapiens. Od zwierząt do bogów — Yuval Noah Harari',
      'Laboratorium przyszłości — polskie reportaże naukowe',
    ],
    announcements: [
      'Warsztaty kreatywnego pisania w każdą sobotę o 11:00 (obowiązują zapisy).',
      'Wieczór gier planszowych — ostatni piątek miesiąca, wstęp wolny.',
      'Pilotażowa wypożyczalnia e-booków startuje od 1 grudnia 2025 r.',
    ],
    policies: [
      'Standardowy okres wypożyczenia: 21 dni z możliwością jednokrotnego przedłużenia online.',
      'Rezerwacje wygasają po 48 godzinach od powiadomienia o dostępności egzemplarza.',
      'Opłata za przetrzymanie wynosi 1,50 zł za każdy rozpoczęty dzień — wpływy przeznaczamy na zakup nowych tytułów.',
    ],
    snapshot: {
      readers: 'Ponad 6 200 stałych czytelników korzysta z zasobów placówki.',
      collection: 'Ponad 48 000 woluminów rozlokowanych w filiach i magazynach.',
      events: 'Ponad 40 wydarzeń rocznie — spotkania autorskie, kluby dyskusyjne, warsztaty technologiczne.',
    },
  }), [])

  useEffect(() => {
    if (!isAuthenticated) {
      setStats(null)
      setAlerts([])
      setLibraryHours(null)
      setError(null)
      setLoading(false)
      prefetchScheduledRef.current = false
      return
    }

    let mounted = true
    async function load() {
      setLoading(true)
      try {
        const [dashboardData, alertsData, hoursData] = await Promise.all([
          apiFetch('/api/dashboard'),
          apiFetch('/api/alerts').catch(() => []),
          apiFetch('/api/library/hours').catch(() => null)
        ])
        
        if (mounted) {
          setStats(dashboardData)
          setAlerts(Array.isArray(alertsData) ? alertsData : [])
          setLibraryHours(hoursData)
          setError(null)
          
          // Check if user needs onboarding
          if (user && !user.onboardingCompleted) {
            setShowOnboarding(true)
          }
        }
      } catch (err) {
        if (mounted) {
          const statusCode = err?.status === 401 ? '401 (wymagane logowanie)' : (err?.status ?? err?.message ?? 'błąd')
          setError(statusCode)
        }
      } finally {
        if (mounted) setLoading(false)
      }
    }
    load()
    return () => {
      mounted = false
    }
  }, [isAuthenticated, user])

  useEffect(() => {
    if (!isAuthenticated || prefetchScheduledRef.current) {
      return
    }

    prefetchScheduledRef.current = true

    const tasks = [
      prefetchResource('reservations:/api/reservations?history=true', () => apiFetch('/api/reservations?history=true')),
      prefetchResource('favorites:/api/favorites', () => apiFetch('/api/favorites')),
      prefetchResource('loans:/api/loans', () => apiFetch('/api/loans')),
    ]

    tasks.forEach(promise => promise.catch(() => {}))
  }, [isAuthenticated, prefetchResource])

  if (!isAuthenticated) {
    return (
      <div className="landing-page">
        {/* Hero Section */}
        <section className="hero-section">
          <div className="hero-content">
            <h1 className="hero-title">Odkryj świat literatury</h1>
            <p className="hero-subtitle">
              Twoja centralna platforma do odkrywania, wypożyczania i zarządzania książkami
            </p>
            <a href="/books" className="hero-button">
              Przeglądaj książki →
            </a>
          </div>
        </section>

        {/* Features Section */}
        <section className="features-section">
          <h2 className="features-title">Wszystko czego potrzebujesz</h2>
          <p className="features-subtitle">Kompleksowe rozwiązanie do zarządzania biblioteką</p>
          
          <div className="features-grid">
            <article className="feature-card">
              <div className="feature-icon feature-icon--calendar">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                  <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
              </div>
              <h3 className="feature-title">Katalog książek</h3>
              <p className="feature-description">
                Przeglądaj bogatą kolekcję książek w jednym miejscu. Wyszukuj po tytule, autorze lub kategorii.
              </p>
            </article>

            <article className="feature-card">
              <div className="feature-icon feature-icon--notifications">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                  <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
              </div>
              <h3 className="feature-title">Powiadomienia</h3>
              <p className="feature-description">
                Otrzymuj powiadomienia o dostępności zarezerwowanych książek i zbliżających się terminach zwrotu.
              </p>
            </article>

            <article className="feature-card">
              <div className="feature-icon feature-icon--management">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                  <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
              </div>
              <h3 className="feature-title">Łatwe zarządzanie</h3>
              <p className="feature-description">
                Intuicyjny panel pozwala szybko wypożyczać książki, przedłużać terminy i zarządzać rezerwacjami.
              </p>
            </article>
          </div>
        </section>
      </div>
    )
  }

  if (loading) {
    return (
      <div className="page">
        <div className="surface-card empty-state">Ładuję dane panelu...</div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="page">
        <div className="surface-card">
          <h2>Panel biblioteki</h2>
          <p className="error">Nie udało się pobrać statystyk (kod: {error}). Spróbuj ponownie później.</p>
        </div>
      </div>
    )
  }

  const books = stats?.booksCount ?? '—'
  const users = stats?.usersCount ?? '—'
  const loans = stats?.loansCount ?? '—'
  const reservations = stats?.reservationsQueue ?? '—'

  const handleQuickSearch = (e) => {
    e.preventDefault()
    if (searchQuery.trim()) {
      navigate(`/books?search=${encodeURIComponent(searchQuery.trim())}`)
    }
  }

  const getAlertIcon = (type) => {
    switch (type) {
      case 'due_soon': return '⏰'
      case 'overdue': return '🚨'
      case 'ready': return '✅'
      case 'fine': return '💰'
      default: return 'ℹ️'
    }
  }

  const getAlertClass = (type) => {
    switch (type) {
      case 'due_soon': return 'alert-warning'
      case 'overdue': return 'alert-danger'
      case 'ready': return 'alert-success'
      case 'fine': return 'alert-danger'
      default: return 'alert-info'
    }
  }

  // User Dashboard
  if (isAuthenticated && !isLibrarian && !isAdmin) {
    return (
      <div className="page">
        <header className="page-header">
          <div>
            <h1>Witaj, {user?.name || 'Czytelniku'}! 👋</h1>
            <p className="support-copy">Twoje centrum dowodzenia - szybki podgląd wypożyczeń, rezerwacji i nowości.</p>
          </div>
        </header>

        {/* Quick Search */}
        <div className="surface-card" style={{ marginBottom: 'var(--space-4)' }}>
          <h2 style={{ marginBottom: 'var(--space-3)' }}>🔍 Szybka wyszukiwarka</h2>
          <form onSubmit={handleQuickSearch} className="form-row">
            <input
              type="text"
              placeholder="Wpisz tytuł, autora lub ISBN..."
              value={searchQuery}
              onChange={e => setSearchQuery(e.target.value)}
              style={{ flex: 1 }}
            />
            <button type="submit" className="btn btn-primary">
              Szukaj
            </button>
          </form>
        </div>

        {/* Alerts */}
        {alerts.length > 0 && (
          <div className="surface-card" style={{ marginBottom: 'var(--space-4)' }}>
            <h2 style={{ marginBottom: 'var(--space-3)' }}>🔔 Ważne powiadomienia</h2>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--space-2)' }}>
              {alerts.map((alert, index) => (
                <div key={index} className={`alert ${getAlertClass(alert.type)}`}>
                  <span style={{ fontSize: '1.2rem', marginRight: 'var(--space-2)' }}>
                    {getAlertIcon(alert.type)}
                  </span>
                  <div style={{ flex: 1 }}>
                    <strong>{alert.title}</strong>
                    {alert.message && <p style={{ margin: '0.25rem 0 0', fontSize: '0.9rem' }}>{alert.message}</p>}
                  </div>
                  {alert.action && (
                    <Link to={alert.action.link} className="btn btn-ghost">
                      {alert.action.label}
                    </Link>
                  )}
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Stats Summary */}
        <div className="card-grid card-grid--columns-3" style={{ marginBottom: 'var(--space-4)' }}>
          <Link to="/my-loans" className="surface-card stat-card" style={{ textDecoration: 'none' }}>
            <h3>Wypożyczone książki</h3>
            <strong style={{ color: '#5ce1e6' }}>{stats?.activeLoans ?? 0}</strong>
            <span>Aktualne wypożyczenia</span>
          </Link>
          <Link to="/reservations" className="surface-card stat-card" style={{ textDecoration: 'none' }}>
            <h3>Rezerwacje</h3>
            <strong style={{ color: '#667eea' }}>{stats?.activeReservations ?? 0}</strong>
            <span>Oczekujące</span>
          </Link>
          <Link to="/favorites" className="surface-card stat-card" style={{ textDecoration: 'none' }}>
            <h3>Ulubione</h3>
            <strong style={{ color: '#ff6838' }}>{stats?.favoritesCount ?? 0}</strong>
            <span>Oznaczone serduszkiem</span>
          </Link>
        </div>

        {/* Library Hours */}
        {libraryHours && (
          <div className="surface-card">
            <h2 style={{ marginBottom: 'var(--space-3)' }}>🕒 Godziny otwarcia</h2>
            <div style={{ display: 'grid', gap: 'var(--space-2)' }}>
              {Object.entries(libraryHours).map(([day, hours]) => (
                <div key={day} style={{ display: 'flex', justifyContent: 'space-between', padding: '0.5rem 0', borderBottom: '1px solid var(--color-border)' }}>
                  <strong>{day}</strong>
                  <span>{hours}</span>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    )
  }

  // Librarian Dashboard
  if (isAuthenticated && isLibrarian && !isAdmin) {
    return (
      <div className="page">
        <header className="page-header">
          <div>
            <h1>Panel Bibliotekarza 📚</h1>
            <p className="support-copy">Zadania na dziś i szybki dostęp do kluczowych funkcji.</p>
          </div>
        </header>

        {/* Today's Tasks */}
        <div className="surface-card" style={{ marginBottom: 'var(--space-4)' }}>
          <h2 style={{ marginBottom: 'var(--space-3)' }}>✅ Zadania na dziś</h2>
          <div className="card-grid card-grid--columns-3">
            <div className="stat-card">
              <h3>Rezerwacje do przygotowania</h3>
              <strong style={{ color: '#667eea' }}>{stats?.pendingReservations ?? 0}</strong>
              <Link to="/librarian-panel" className="btn btn-ghost">Realizuj</Link>
            </div>
            <div className="stat-card">
              <h3>Przetrzymane zwroty</h3>
              <strong style={{ color: '#ff6838' }}>{stats?.overdueLoans ?? 0}</strong>
              <Link to="/librarian-panel" className="btn btn-ghost">Wyświetl</Link>
            </div>
            <div className="stat-card">
              <h3>Niewydane rezerwacje</h3>
              <strong style={{ color: '#f59e0b' }}>{stats?.expiredReservations ?? 0}</strong>
              <Link to="/librarian-panel" className="btn btn-ghost">Sprawdź</Link>
            </div>
          </div>
        </div>

        {/* Quick Actions */}
        <div className="surface-card">
          <h2 style={{ marginBottom: 'var(--space-3)' }}>⚡ Szybkie akcje</h2>
          <div className="form-actions">
            <Link to="/librarian-panel" className="btn btn-primary">Panel obsługi</Link>
            <Link to="/books" className="btn btn-ghost">Katalog książek</Link>
            <Link to="/announcements" className="btn btn-ghost">Ogłoszenia</Link>
          </div>
        </div>
      </div>
    )
  }

  // Admin Dashboard
  if (isAuthenticated && isAdmin) {
    return (
      <div className="page">
        <header className="page-header">
          <div>
            <h1>Panel Administratora ⚙️</h1>
            <p className="support-copy">Statystyki ogólne i zarządzanie systemem.</p>
          </div>
        </header>

        {/* System Stats */}
        <div className="card-grid card-grid--columns-3" style={{ marginBottom: 'var(--space-4)' }}>
          <div className="surface-card stat-card">
            <h3>Aktywni użytkownicy</h3>
            <strong style={{ color: '#5ce1e6' }}>{stats?.activeUsers ?? 0}</strong>
            <span>Obecnie online</span>
          </div>
          <div className="surface-card stat-card">
            <h3>Obciążenie serwera</h3>
            <strong style={{ color: '#667eea' }}>{stats?.serverLoad ?? '—'}%</strong>
            <span>Wykorzystanie CPU</span>
          </div>
          <div className="surface-card stat-card">
            <h3>Transakcje dzisiaj</h3>
            <strong style={{ color: '#ff6838' }}>{stats?.transactionsToday ?? 0}</strong>
            <span>Wypożyczenia + zwroty</span>
          </div>
        </div>

        {/* Database Stats */}
        <div className="surface-card" style={{ marginBottom: 'var(--space-4)' }}>
          <h2 style={{ marginBottom: 'var(--space-3)' }}>📊 Statystyki bazy danych</h2>
          <div className="card-grid card-grid--columns-3">
            <div className="stat-card">
              <h3>Książki w katalogu</h3>
              <strong>{books}</strong>
            </div>
            <div className="stat-card">
              <h3>Zarejestrowani czytelnicy</h3>
              <strong>{users}</strong>
            </div>
            <div className="stat-card">
              <h3>Aktywne wypożyczenia</h3>
              <strong>{loans}</strong>
            </div>
          </div>
        </div>

        {/* Admin Actions */}
        <div className="surface-card">
          <h2 style={{ marginBottom: 'var(--space-3)' }}>🛠️ Narzędzia administracyjne</h2>
          <div className="form-actions">
            <Link to="/admin/users" className="btn btn-primary">Zarządzanie użytkownikami</Link>
            <Link to="/admin/config" className="btn btn-ghost">Konfiguracja systemu</Link>
            <Link to="/admin/reports" className="btn btn-ghost">Raporty</Link>
            <Link to="/admin/backup" className="btn btn-ghost">Kopia zapasowa</Link>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Panel biblioteki</h1>
          <p className="support-copy">Szybki podgląd bieżącej kondycji kolekcji, aktywnych użytkowników oraz rotacji zasobów.</p>
        </div>
      </header>

      <section className="card-grid card-grid--columns-3">
        <article className="surface-card stat-card">
          <h3>Książki w katalogu</h3>
          <strong>{books}</strong>
          <span>Widocznych w aktualnej ofercie biblioteki</span>
        </article>
        <article className="surface-card stat-card">
          <h3>Aktywni czytelnicy</h3>
          <strong>{users}</strong>
          <span>Z rolami czytelnika lub bibliotekarza</span>
        </article>
        <article className="surface-card stat-card">
          <h3>Wypożyczenia</h3>
          <strong>{loans}</strong>
          <span>Aktywnych transakcji wypożyczeń</span>
        </article>
      </section>

      <section className="surface-card stat-card">
        <h3>Kolejka rezerwacji</h3>
        <strong>{reservations}</strong>
        <span>Liczba oczekujących na zwrot egzemplarza</span>
      </section>
      
      {showOnboarding && (
        <OnboardingModal onComplete={() => setShowOnboarding(false)} />
      )}
    </div>
  )
}
