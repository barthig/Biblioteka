import React, { useEffect, useMemo, useState } from 'react'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'

export default function Dashboard() {
  const [stats, setStats] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const { token } = useAuth()
  const isAuthenticated = Boolean(token)

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

  if (!isAuthenticated) {
    return (
      <div className="page">
        <header className="page-header">
          <div>
            <h1>Panel biblioteki</h1>
            <p className="support-copy">Poznaj naszą placówkę, zanim założysz konto. Poniżej znajdziesz najważniejsze informacje o zasobach, wydarzeniach i zasadach wypożyczeń.</p>
          </div>
        </header>

        <section className="surface-card">
          <h2>O placówce</h2>
          <p>{publicHighlights.facility.description}</p>
          <ul>
            {publicHighlights.facility.services.map(service => (
              <li key={service}>{service}</li>
            ))}
          </ul>
        </section>

        <section className="card-grid card-grid--columns-3">
          <article className="surface-card stat-card">
            <h3>Księgozbiór</h3>
            <span>{publicHighlights.snapshot.collection}</span>
          </article>
          <article className="surface-card stat-card">
            <h3>Czytelnicy</h3>
            <span>{publicHighlights.snapshot.readers}</span>
          </article>
          <article className="surface-card stat-card">
            <h3>Wydarzenia</h3>
            <span>{publicHighlights.snapshot.events}</span>
          </article>
        </section>

        <section className="surface-card">
          <h2>Polecane tytuły</h2>
          <p>Oto część najczęściej wypożyczanych pozycji dostępnych na miejscu:</p>
          <ul>
            {publicHighlights.featuredTitles.map(title => (
              <li key={title}>{title}</li>
            ))}
          </ul>
        </section>

        <section className="surface-card">
          <h2>Aktualności</h2>
          <ul>
            {publicHighlights.announcements.map(note => (
              <li key={note}>{note}</li>
            ))}
          </ul>
        </section>

        <section className="surface-card">
          <h2>Zasady wypożyczeń i zwrotów</h2>
          <ul>
            {publicHighlights.policies.map(policy => (
              <li key={policy}>{policy}</li>
            ))}
          </ul>
          <p className="support-copy">Załóż konto lub zaloguj się, aby rezerwować egzemplarze i śledzić własne wypożyczenia.</p>
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
