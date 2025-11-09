import React, { useEffect, useState } from 'react'
import { apiFetch } from '../api'

export default function Dashboard() {
  const [stats, setStats] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
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
          const statusCode = err?.status ?? err?.message ?? 'błąd'
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
  }, [])

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
