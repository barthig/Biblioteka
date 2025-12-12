import React, { useEffect, useMemo, useRef, useState } from 'react'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'
import { useResourceCache } from '../context/ResourceCacheContext'

export default function Dashboard() {
  const [stats, setStats] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const { token, user } = useAuth()
  const isAuthenticated = Boolean(token)
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
      setError(null)
      setLoading(false)
      prefetchScheduledRef.current = false
      return
    }

    let mounted = true
    async function load() {
      setLoading(true)
      try {
        const data = await apiFetch('/api/dashboard')
        if (mounted) {
          setStats(data)
          setError(null)
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
  }, [isAuthenticated])

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
    </div>
  )
}
