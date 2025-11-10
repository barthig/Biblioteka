import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'

export default function Favorites() {
  const { user } = useAuth()
  const [favorites, setFavorites] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [actionError, setActionError] = useState(null)

  useEffect(() => {
    if (!user?.id) {
      setFavorites([])
      setLoading(false)
      return
    }

    let active = true
    async function load() {
      setLoading(true)
      setError(null)
      try {
        const data = await apiFetch('/api/favorites')
        if (active) setFavorites(Array.isArray(data) ? data : [])
      } catch (err) {
        if (active) setError(err.message || 'Nie udało się pobrać półki ulubionych książek')
      } finally {
        if (active) setLoading(false)
      }
    }

    load()
    return () => { active = false }
  }, [user?.id])

  async function removeFavorite(bookId) {
    setActionError(null)
    try {
      await apiFetch(`/api/favorites/${bookId}`, { method: 'DELETE' })
      setFavorites(prev => prev.filter(f => f.book?.id !== bookId))
    } catch (err) {
      setActionError(err.message || 'Nie udało się usunąć pozycji z ulubionych')
    }
  }

  if (!user?.id) {
    return (
      <div className="page page--centered">
        <div className="surface-card empty-state">
          Aby zapisywać ulubione książki, <Link to="/login">zaloguj się</Link> lub <Link to="/register">załóż konto</Link>.
        </div>
      </div>
    )
  }

  if (loading) {
    return (
      <div className="page">
        <div className="surface-card empty-state">Ładowanie ulubionych tytułów...</div>
      </div>
    )
  }

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Moja półka</h1>
          <p className="support-copy">Zebrane tytuły, do których chcesz szybko wrócić lub zamówić później.</p>
        </div>
      </header>

      {error && (
        <div className="surface-card">
          <p className="error">{error}</p>
        </div>
      )}
      {actionError && (
        <div className="surface-card">
          <p className="error">{actionError}</p>
        </div>
      )}

      {favorites.length === 0 ? (
        <section className="surface-card empty-state">
          Brak zapisanych tytułów. Otwórz katalog i dodaj książki do ulubionych.
        </section>
      ) : (
        <section className="surface-card">
          <ul className="resource-list">
            {favorites.map(fav => (
              <li key={fav.id} className="resource-item">
                <div>
                  <strong>{fav.book?.title ?? 'Nieznana książka'}</strong>
                  <div className="resource-item__meta">
                    <span>Autor: {fav.book?.author?.name ?? '—'}</span>
                    <span>Dodano: {fav.createdAt ? new Date(fav.createdAt).toLocaleDateString() : '—'}</span>
                  </div>
                </div>
                <div className="resource-item__actions">
                  {fav.book?.id && (
                    <Link className="btn btn-ghost" to={`/books/${fav.book.id}`}>Szczegóły</Link>
                  )}
                  {fav.book?.id && (
                    <button type="button" className="btn btn-outline" onClick={() => removeFavorite(fav.book.id)}>Usuń</button>
                  )}
                </div>
              </li>
            ))}
          </ul>
        </section>
      )}
    </div>
  )
}
