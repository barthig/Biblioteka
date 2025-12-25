import React, { useEffect, useMemo, useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'
import { useResourceCache } from '../context/ResourceCacheContext'
import OnboardingModal from '../components/OnboardingModal'
import UserRecommendations from '../components/UserRecommendations'

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

  const publicNewArrivals = useMemo(() => ([
    { title: 'Cisza nad jeziorem', author: 'Maria Nowicka' },
    { title: 'Miasto bez mapy', author: 'Paweł Zieliński' },
    { title: 'Opowieści z północy', author: 'Agnieszka Kowal' },
    { title: 'Atlas wspomnień', author: 'Tomasz Wieczorek' },
  ]), [])

  const publicEvents = useMemo(() => ([
    {
      date: '12 MAJ',
      title: 'Spotkanie autorskie',
      description: 'Rozmowa z twórcami literatury popularnonaukowej oraz sesja pytań i odpowiedzi.',
    },
    {
      date: '21 MAJ',
      title: 'Klub dyskusyjny',
      description: 'Wspólne omówienie książki miesiąca z bibliotekarzem prowadzącym.',
    },
  ]), [])

  const publicHighlights = useMemo(() => ({
    facility: {
      name: 'Biblioteka Miejska w Poznaniu',
      description: 'Od ponad 30 lat wspieramy mieszkańców w odkrywaniu literatury i rozwijaniu pasji czytelniczych. Oferujemy wygodne strefy pracy, bogatą kolekcję książek oraz cykliczne warsztaty dla różnych grup wiekowych.',
      services: [
        '11 czytelni tematycznych i strefa coworkingowa',
        'Program "Pierwsza książka" dla najmłodszych czytelników',
        'Wsparcie bibliotekarzy w doborze lektur i pracy badawczej',
      ],
    },
    featuredTitles: [
      'Lalka - Bolesław Prus',
      'Cień wiatru - Carlos Ruiz Zafón',
      'Ziemia obiecana - Władysław Reymont',
      'Sapiens. Od zwierząt do bogów - Yuval Noah Harari',
      'Laboratorium przyszłości - polskie reportaże naukowe',
    ],
    announcements: [
      'Warsztaty kreatywnego pisania w każdą sobotę o 11:00 (obowiązują zapisy).',
      'Wieczór gier planszowych - ostatni piątek miesiąca, wstęp wolny.',
      'Pilotażowa wypożyczalnia e-booków startuje od 1 grudnia 2025 r.',
    ],
    policies: [
      'Standardowy okres wypożyczenia: 21 dni z możliwością jednokrotnego przedłużenia online.',
      'Rezerwacje wygasają po 48 godzinach od powiadomienia o dostępności egzemplarza.',
      'Opłata za przetrzymanie wynosi 1,50 zł za każdy rozpoczęty dzień - wpływy przeznaczamy na zakup nowych tytułów.',
    ],
    snapshot: {
      readers: 'Ponad 6 200 stałych czytelników korzysta z zasobów placówki.',
      collection: 'Ponad 48 000 woluminów rozlokowanych w filiach i magazynach.',
      events: 'Ponad 40 wydarzeń rocznie - spotkania autorskie, kluby dyskusyjne, warsztaty technologiczne.',
    },
  }), [])

  const handlePublicSearch = (event) => {
    event.preventDefault()
    if (searchQuery.trim()) {
      navigate(`/books?search=${encodeURIComponent(searchQuery.trim())}`)
    }
  }

  useEffect(() => {
    if (!isAuthenticated) {
      document.body.classList.add('public-home-view')
      return () => document.body.classList.remove('public-home-view')
    }
    document.body.classList.remove('public-home-view')
    return undefined
  }, [isAuthenticated])

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

          if (user && !user.onboardingCompleted) {
            setShowOnboarding(true)
          }
        }
      } catch (err) {
        if (mounted) {
          const statusCode = err?.status === 401 ? '401 (wymagane logowanie)' : (err?.status ?? err?.message ?? 'blad')
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

  if (!isAuthenticated) {
    return (
      <div className="landing-page public-home">
        <main>
          <section className="hero-guest" aria-labelledby="hero-title">
            <div className="hero-guest__content">
              <p className="hero-guest__eyebrow">Biblioteka miejska online</p>
              <h1 id="hero-title">Znajdź książkę, której szukasz w kilka sekund</h1>
              <p className="hero-guest__lead">
                Wyszukuj po tytule, autorze lub temacie i sprawdzaj dostępność bez wychodzenia z domu.
              </p>
              <form className="hero-guest__search" role="search" onSubmit={handlePublicSearch}>
                <label className="sr-only" htmlFor="public-search">Szukaj w katalogu</label>
                <input
                  id="public-search"
                  type="search"
                  name="q"
                  placeholder="Szukaj tytułu, autora lub tematu..."
                  value={searchQuery}
                  onChange={event => setSearchQuery(event.target.value)}
                />
                <button type="submit" className="btn btn-primary">Szukaj</button>
              </form>
              <div className="hero-guest__filters" role="group" aria-label="Szybkie filtry">
                <button type="button" className="filter-pill">Książki</button>
                <button type="button" className="filter-pill">Audiobooki</button>
                <button type="button" className="filter-pill">Filmy</button>
              </div>
            </div>
          </section>

          <section className="info-bar" aria-label="Informacje podstawowe">
            <div className="info-bar__item">
              <span className="info-bar__icon" aria-hidden>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <circle cx="12" cy="12" r="9"></circle>
                  <path d="M12 7v5l3 3"></path>
                </svg>
              </span>
              <div>
                <p>Dzisiaj otwarte</p>
                <strong>
                  <time dateTime="08:00">8:00</time> - <time dateTime="18:00">18:00</time>
                </strong>
              </div>
            </div>
            <div className="info-bar__item">
              <span className="info-bar__icon" aria-hidden>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M21 10c0 5-9 11-9 11s-9-6-9-11a9 9 0 1 1 18 0z"></path>
                  <circle cx="12" cy="10" r="3"></circle>
                </svg>
              </span>
              <div>
                <p>Adres biblioteki</p>
                <strong>ul. Wiosenna 12, 60-101 Poznań</strong>
              </div>
            </div>
          </section>

          <section id="catalog" className="section-block" aria-labelledby="new-title">
            <div className="section-heading">
              <h2 id="new-title">Nowości w księgozbiorze</h2>
              <Link to="/books" className="section-link">Zobacz cały katalog</Link>
            </div>
            <div className="books-grid">
              {publicNewArrivals.map(item => (
                <article key={item.title} className="book-card">
                  <div className="book-card__cover" aria-hidden="true" />
                  <div className="book-card__body">
                    <h3>{item.title}</h3>
                    <p>{item.author}</p>
                    <button type="button" className="btn btn-ghost" aria-label={`Sprawdź dostępność: ${item.title}`}>
                      Sprawdź dostępność
                    </button>
                  </div>
                </article>
              ))}
            </div>
          </section>

          <section id="events" className="section-block" aria-labelledby="events-title">
            <div className="section-heading">
              <h2 id="events-title">Nadchodzące wydarzenia</h2>
              <Link to="/announcements" className="section-link">Pełny kalendarz</Link>
            </div>
            <div className="events-list">
              {publicEvents.map(event => (
                <article key={event.title} className="event-card">
                  <div className="event-card__date">
                    <span>{event.date}</span>
                  </div>
                  <div className="event-card__content">
                    <h3>{event.title}</h3>
                    <p>{event.description}</p>
                  </div>
                </article>
              ))}
            </div>
          </section>
        </main>

        <footer id="contact" className="public-footer">
          <div className="public-footer__links" aria-label="Linki pomocnicze">
            <a href="/terms">Regulamin</a>
            <a href="/privacy">Polityka prywatności</a>
            <a href="/accessibility">Deklaracja Dostępności</a>
          </div>
          <div className="public-footer__contact">
            <div>
              <strong>Kontakt</strong>
              <p>tel. (61) 123 45 67</p>
              <p>kontakt@biblioteka.pl</p>
            </div>
            <div className="public-footer__social" aria-label="Media społecznościowe">
              <a href="https://facebook.com" aria-label="Facebook">Fb</a>
              <a href="https://instagram.com" aria-label="Instagram">Ig</a>
              <a href="https://youtube.com" aria-label="YouTube">Yt</a>
            </div>
          </div>
          <p className="public-footer__copyright">
            © 2025 Biblioteka Publiczna. Wszelkie prawa zastrzeżone.
          </p>
        </footer>
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
      case 'due_soon': return '!'
      case 'overdue': return '!!'
      case 'ready': return 'OK'
      case 'fine': return '$'
      default: return 'i'
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

  if (isAuthenticated && !isLibrarian && !isAdmin) {
    return (
      <div className="page">
        <header className="page-header">
          <div>
            <h1>Witaj, {user?.name || 'Czytelniku'}!</h1>
            <p className="support-copy">Twoje centrum dowodzenia - szybki podgląd wypożyczeń, rezerwacji i nowości.</p>
          </div>
        </header>

        <div className="surface-card" style={{ marginBottom: 'var(--space-4)' }}>
          <h2 style={{ marginBottom: 'var(--space-3)' }}>Szybka wyszukiwarka</h2>
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

        {alerts.length > 0 && (
          <div className="surface-card" style={{ marginBottom: 'var(--space-4)' }}>
            <h2 style={{ marginBottom: 'var(--space-3)' }}>Ważne powiadomienia</h2>
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

        <div className="surface-card" style={{ marginBottom: 'var(--space-4)' }}>
          <h2 style={{ marginBottom: 'var(--space-3)' }}>AI rekomendacje</h2>
          <UserRecommendations />
        </div>

        {libraryHours && (
          <div className="surface-card">
            <h2 style={{ marginBottom: 'var(--space-3)' }}>Godziny otwarcia</h2>
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

  if (isAuthenticated && isLibrarian && !isAdmin) {
    return (
      <div className="page">
        <header className="page-header">
          <div>
            <h1>Panel Bibliotekarza</h1>
            <p className="support-copy">Zadania na dziś i szybki dostęp do kluczowych funkcji.</p>
          </div>
        </header>

        <div className="surface-card" style={{ marginBottom: 'var(--space-4)' }}>
          <h2 style={{ marginBottom: 'var(--space-3)' }}>Zadania na dziś</h2>
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

        <div className="surface-card">
          <h2 style={{ marginBottom: 'var(--space-3)' }}>Szybkie akcje</h2>
          <div className="form-actions">
            <Link to="/librarian-panel" className="btn btn-primary">Panel obsługi</Link>
            <Link to="/books" className="btn btn-ghost">Katalog książek</Link>
            <Link to="/announcements" className="btn btn-ghost">Ogłoszenia</Link>
          </div>
        </div>
      </div>
    )
  }

  if (isAuthenticated && isAdmin) {
    return (
      <div className="page">
        <header className="page-header">
          <div>
            <h1>Panel Administratora</h1>
            <p className="support-copy">Statystyki ogólne i zarządzanie systemem.</p>
          </div>
        </header>

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

        <div className="surface-card" style={{ marginBottom: 'var(--space-4)' }}>
          <h2 style={{ marginBottom: 'var(--space-3)' }}>Statystyki bazy danych</h2>
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

        <div className="surface-card">
          <h2 style={{ marginBottom: 'var(--space-3)' }}>Narzędzia administracyjne</h2>
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
