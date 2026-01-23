import React, { useEffect, useMemo, useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'
import { useResourceCache } from '../context/ResourceCacheContext'
import OnboardingModal from '../components/OnboardingModal'
import UserRecommendations from '../components/UserRecommendations'
import SectionCard from '../components/ui/SectionCard'
import { bookService } from '../services/bookService'

export default function Dashboard() {
  const [stats, setStats] = useState(null)
  const [alerts, setAlerts] = useState([])
  const [libraryHours, setLibraryHours] = useState(null)
  const [dashboardAnnouncements, setDashboardAnnouncements] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [searchQuery, setSearchQuery] = useState('')
  const [showOnboarding, setShowOnboarding] = useState(false)
  const { token, user } = useAuth()
  const navigate = useNavigate()
  const isAuthenticated = Boolean(token)
  const isLibrarian = user?.roles?.includes('ROLE_LIBRARIAN')
  const isAdmin = user?.roles?.includes('ROLE_ADMIN')
  const { getCachedResource, setCachedResource, prefetchResource } = useResourceCache()
  const prefetchScheduledRef = useRef(false)
  const [expandedAnnouncements, setExpandedAnnouncements] = useState(() => new Set())
  const [publicAnnouncements, setPublicAnnouncements] = useState([])
  const DASHBOARD_CACHE_TTL = 30000
  const PUBLIC_ANNOUNCEMENTS_TTL = 30000

  const [publicNewArrivals, setPublicNewArrivals] = useState([])
  const [publicNewArrivalsLoading, setPublicNewArrivalsLoading] = useState(false)
  const [publicNewArrivalsError, setPublicNewArrivalsError] = useState(null)

  function handleCheckAvailabilityPublic(item) {
    if (item?.id) {
      navigate(`/books/${item.id}`)
      return
    }
    const title = item?.title || ''
    if (!title) return
    navigate(`/books?search=${encodeURIComponent(title)}`)
  }

  // Public latest announcements derived from API (non-event)
  const publicLatestAnnouncements = useMemo(() => {
    return publicAnnouncements
      .filter(item => !item?.eventAt)
      .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
      .slice(0, 3)
  }, [publicAnnouncements])

  const upcomingEvents = useMemo(() => {
    const now = Date.now()
    return dashboardAnnouncements
      .filter(item => item?.eventAt && new Date(item.eventAt).getTime() > now)
      .sort((a, b) => new Date(a.eventAt) - new Date(b.eventAt))
      .slice(0, 3)
  }, [dashboardAnnouncements])

  const publicUpcomingEvents = useMemo(() => {
    const now = Date.now()
    return publicAnnouncements
      .filter(item => item?.eventAt && new Date(item.eventAt).getTime() > now)
      .sort((a, b) => new Date(a.eventAt) - new Date(b.eventAt))
      .slice(0, 2)
  }, [publicAnnouncements])

  const latestAnnouncements = useMemo(() => {
    return dashboardAnnouncements
      .filter(item => !item?.eventAt)
      .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
      .slice(0, 3)
  }, [dashboardAnnouncements])

  const formatDateTime = (value) => new Date(value).toLocaleString('pl-PL', { dateStyle: 'medium', timeStyle: 'short' })
  const formatDate = (value) => new Date(value).toLocaleDateString('pl-PL')
  const formatPublicEventDate = (value) => new Date(value).toLocaleDateString('pl-PL', { day: '2-digit', month: 'short' }).toUpperCase()
  const formatPublicEventTime = (value) => new Date(value).toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' })
  const CONTENT_LIMIT = 140

  const toggleExpanded = (key) => {
    setExpandedAnnouncements(prev => {
      const next = new Set(prev)
      if (next.has(key)) {
        next.delete(key)
      } else {
        next.add(key)
      }
      return next
    })
  }

  const getAnnouncementText = (item) => item?.content || item?.description || ''
  const truncateText = (text) => {
    if (!text || text.length <= CONTENT_LIMIT) return text
    return `${text.slice(0, CONTENT_LIMIT).trimEnd()}...`
  }

  const renderAnnouncementsList = (items, emptyText, dateFormatter, dateValue) => {
    if (items.length === 0) {
      return <p className="support-copy">{emptyText}</p>
    }

    return (
      <ul className="list list--bordered">
        {items.map((item, index) => {
          const itemKey = item?.id ?? `${item?.title || 'item'}-${index}`
          const content = getAnnouncementText(item)
          const isExpanded = expandedAnnouncements.has(itemKey)
          const shouldToggle = content.length > CONTENT_LIMIT
          const descriptionId = `announcement-desc-${String(itemKey).replace(/\\s+/g, '-')}`

          return (
            <li key={itemKey}>
              <div className="list__title">{item.title}</div>
              <div className="list__meta">{dateFormatter(dateValue(item))}</div>
              {item.location && (
                <div className="list__meta">Lokalizacja: {item.location}</div>
              )}
              {content && (
                <div className="list__content">
                  <p id={descriptionId} className="support-copy">
                    {isExpanded ? content : truncateText(content)}
                  </p>
                  {shouldToggle && (
                    <button
                      type="button"
                      className="btn btn-ghost"
                      onClick={() => toggleExpanded(itemKey)}
                      aria-expanded={isExpanded}
                      aria-controls={descriptionId}
                    >
                      {isExpanded ? 'Zwin' : 'Rozwin'}
                    </button>
                  )}
                </div>
              )}
            </li>
          )
        })}
      </ul>
    )
  }

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
    if (isAuthenticated) {
      setPublicAnnouncements([])
      return
    }

    let mounted = true
    async function loadPublicAnnouncements() {
      const cacheKey = 'dashboard:/api/announcements?homepage=true&limit=6'
      const cached = getCachedResource(cacheKey, PUBLIC_ANNOUNCEMENTS_TTL)
      if (cached) {
        if (mounted) {
          setPublicAnnouncements(cached)
        }
        return
      }
      try {
        const result = await apiFetch('/api/announcements?homepage=true&limit=6')
        if (mounted) {
          const list = Array.isArray(result?.data) ? result.data : []
          setPublicAnnouncements(list)
          setCachedResource(cacheKey, list)
        }
      } catch {
        if (mounted) {
          setPublicAnnouncements([])
        }
      }
    }

    loadPublicAnnouncements()
    return () => {
      mounted = false
    }
  }, [isAuthenticated])

  // Fetch newest books for guests
  useEffect(() => {
    if (isAuthenticated) return
    let active = true
    setPublicNewArrivalsLoading(true)
    setPublicNewArrivalsError(null)
    bookService.getNewest(4)
      .then(items => { if (active) setPublicNewArrivals(items) })
      .catch(err => { if (active) setPublicNewArrivalsError(err.message || 'Nie udało się pobrać nowości') })
      .finally(() => { if (active) setPublicNewArrivalsLoading(false) })
    return () => { active = false }
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
        const dashboardKey = 'dashboard:/api/dashboard'
        const alertsKey = 'dashboard:/api/alerts'
        const hoursKey = 'dashboard:/api/library/hours'
        const announcementsKey = 'dashboard:/api/announcements?limit=10'

        const cachedDashboard = getCachedResource(dashboardKey, DASHBOARD_CACHE_TTL)
        const cachedAlerts = getCachedResource(alertsKey, DASHBOARD_CACHE_TTL)
        const cachedHours = getCachedResource(hoursKey, DASHBOARD_CACHE_TTL)
        const cachedAnnouncements = getCachedResource(announcementsKey, DASHBOARD_CACHE_TTL)

        const [dashboardData, alertsData, hoursData, announcementsData] = await Promise.all([
          cachedDashboard ?? apiFetch('/api/dashboard'),
          cachedAlerts ?? apiFetch('/api/alerts').catch(() => []),
          cachedHours ?? apiFetch('/api/library/hours').catch(() => null),
          cachedAnnouncements ?? apiFetch('/api/announcements?limit=10').catch(() => ({ data: [] }))
        ])

        if (mounted) {
          setStats(dashboardData)
          setAlerts(Array.isArray(alertsData) ? alertsData : [])
          setLibraryHours(hoursData)
          setDashboardAnnouncements(Array.isArray(announcementsData?.data) ? announcementsData.data : [])
          setError(null)

          setCachedResource(dashboardKey, dashboardData)
          setCachedResource(alertsKey, Array.isArray(alertsData) ? alertsData : [])
          setCachedResource(hoursKey, hoursData)
          setCachedResource(announcementsKey, announcementsData)

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
              {publicNewArrivalsLoading && (
                <article className="book-card skeleton" aria-hidden="true" />
              )}
              {publicNewArrivalsError && !publicNewArrivalsLoading && (
                <div className="empty-state">{publicNewArrivalsError}</div>
              )}
              {!publicNewArrivalsLoading && !publicNewArrivalsError && publicNewArrivals.map(item => (
                <article key={item.id || item.title} className="book-card">
                  <div className="book-card__cover" aria-hidden="true" />
                  <div className="book-card__body">
                    <h3>{item.title}</h3>
                    <p>{item.author || (Array.isArray(item.authors) ? item.authors.join(', ') : '')}</p>
                    <button
                      type="button"
                      className="btn btn-ghost"
                      aria-label={`Sprawdź dostępność: ${item.title}`}
                      onClick={() => handleCheckAvailabilityPublic(item)}
                    >
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
              {publicUpcomingEvents.length === 0 ? (
                <div className="empty-state">Brak nadchodzących wydarzeń.</div>
              ) : (
                publicUpcomingEvents.map(event => (
                  <article key={event.id || event.title} className="event-card">
                    <div className="event-card__date">
                      <span>{formatPublicEventDate(event.eventAt)}</span>
                    </div>
                    <div className="event-card__content">
                      <h3>{event.title || 'Wydarzenie'}</h3>
                      <p className="support-copy">{formatPublicEventTime(event.eventAt)}</p>
                      <p>{event.content || event.description || ''}</p>
                    </div>
                  </article>
                ))
              )}
            </div>
          </section>

          <section id="public-announcements" className="section-block" aria-labelledby="public-announcements-title">
            <div className="section-heading">
              <h2 id="public-announcements-title">Ogłoszenia</h2>
              <Link to="/announcements" className="section-link">Zobacz wszystkie</Link>
            </div>
            <div className="surface-card" style={{ padding: 'var(--space-3)' }}>
              {renderAnnouncementsList(
                publicLatestAnnouncements,
                'Brak nowych ogłoszeń.',
                formatDate,
                (item) => item.createdAt
              )}
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
            <div>
              <strong>Godziny otwarcia</strong>
              <p>Pon-Pt: 8:00-18:00</p>
              <p>Sob: 9:00-14:00</p>
              <p>Nd: nieczynne</p>
            </div>
            <div className="public-footer__social" aria-label="Media społecznościowe">
              <a href="https://facebook.com" aria-label="Facebook">Fb</a>
              <a href="https://instagram.com" aria-label="Instagram">Ig</a>
              <a href="https://youtube.com" aria-label="YouTube">Yt</a>
            </div>
          </div>
          <p className="public-footer__copyright">
            (c) 2025 Biblioteka Publiczna. Wszelkie prawa zastrzeżone.
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

        <div className="grid grid-2" style={{ marginBottom: 'var(--space-4)' }}>
          <SectionCard title="Wydarzenia">
            {renderAnnouncementsList(
              upcomingEvents,
              'Brak nadchodzących wydarzeń.',
              formatDateTime,
              (item) => item.eventAt
            )}
          </SectionCard>
          <SectionCard title="Ogłoszenia">
            {renderAnnouncementsList(
              latestAnnouncements,
              'Brak nowych ogłoszeń.',
              formatDate,
              (item) => item.createdAt
            )}
          </SectionCard>
        </div>
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
              <Link to="/librarian?tab=reservations&status=active" className="btn btn-ghost">Realizuj</Link>
            </div>
            <div className="stat-card">
              <h3>Przetrzymane zwroty</h3>
              <strong style={{ color: '#ff6838' }}>{stats?.overdueLoans ?? 0}</strong>
              <Link to="/librarian?tab=loans&loan=overdue" className="btn btn-ghost">Wyświetl</Link>
            </div>
            <div className="stat-card">
              <h3>Niewydane rezerwacje</h3>
              <strong style={{ color: '#f59e0b' }}>{stats?.preparedReservations ?? 0}</strong>
              <Link to="/librarian?tab=reservations&status=prepared" className="btn btn-ghost">Sprawdź</Link>
            </div>
          </div>
        </div>

        <div className="surface-card">
          <h2 style={{ marginBottom: 'var(--space-3)' }}>Szybkie akcje</h2>
          <div className="form-actions">
            <Link to="/librarian?tab=dashboard" className="btn btn-primary">Panel obsługi</Link>
            <Link to="/books" className="btn btn-ghost">Katalog książek</Link>
            <Link to="/announcements" className="btn btn-ghost">Ogłoszenia</Link>    
            <Link to="/librarian?tab=create" className="btn btn-secondary">Nowe wypożyczenie</Link>
            <Link to="/librarian?tab=loans" className="btn btn-secondary">Zobacz wypożyczenia</Link>
          </div>
        </div>

        <div className="grid grid-2" style={{ marginTop: 'var(--space-4)' }}>
          <SectionCard title="Wydarzenia">
            {renderAnnouncementsList(
              upcomingEvents,
              'Brak nadchodzących wydarzeń.',
              formatDateTime,
              (item) => item.eventAt
            )}
          </SectionCard>
          <SectionCard title="Ogłoszenia">
            {renderAnnouncementsList(
              latestAnnouncements,
              'Brak nowych ogłoszeń.',
              formatDate,
              (item) => item.createdAt
            )}
          </SectionCard>
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

        <div className="grid grid-2" style={{ marginTop: 'var(--space-4)' }}>
          <SectionCard title="Wydarzenia">
            {renderAnnouncementsList(
              upcomingEvents,
              'Brak nadchodzących wydarzeń.',
              formatDateTime,
              (item) => item.eventAt
            )}
          </SectionCard>
          <SectionCard title="Ogłoszenia">
            {renderAnnouncementsList(
              latestAnnouncements,
              'Brak nowych ogłoszeń.',
              formatDate,
              (item) => item.createdAt
            )}
          </SectionCard>
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

      <div className="grid grid-2" style={{ marginTop: 'var(--space-4)' }}>
          <SectionCard title="Wydarzenia">
            {renderAnnouncementsList(
              upcomingEvents,
              'Brak nadchodzących wydarzeń.',
              formatDateTime,
              (item) => item.eventAt
            )}
          </SectionCard>
          <SectionCard title="Ogłoszenia">
            {renderAnnouncementsList(
              latestAnnouncements,
              'Brak nowych ogłoszeń.',
              formatDate,
              (item) => item.createdAt
            )}
          </SectionCard>
      </div>
      
      {showOnboarding && (
        <OnboardingModal onComplete={() => setShowOnboarding(false)} />
      )}
    </div>
  )
}
