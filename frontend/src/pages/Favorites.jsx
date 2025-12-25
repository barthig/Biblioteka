import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'
import { useResourceCache } from '../context/ResourceCacheContext'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import SectionCard from '../components/ui/SectionCard'
import FeedbackCard from '../components/ui/FeedbackCard'

export default function Favorites() {
  const { user } = useAuth()
  const [favorites, setFavorites] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [actionError, setActionError] = useState(null)
  const { getCachedResource, setCachedResource, invalidateResource } = useResourceCache()
  const CACHE_KEY = '/api/favorites'
  const CACHE_TTL = 60000

  const lastAdded = Array.isArray(favorites)
    ? favorites
      .map(item => item.createdAt)
      .filter(Boolean)
      .map(value => new Date(value))
      .sort((a, b) => b.getTime() - a.getTime())[0] ?? null
    : null

  useEffect(() => {
    if (!user?.id) {
      setFavorites([])
      setLoading(false)
      invalidateResource('favorites:/api/favorites')
      return
    }

    let active = true
    async function load() {
      const cached = getCachedResource(`favorites:${CACHE_KEY}`, CACHE_TTL)
      if (cached) {
        setFavorites(cached)
        setLoading(false)
        setError(null)
        return
      }

      setLoading(true)
      setError(null)
      try {
        const data = await apiFetch('/api/favorites')
        if (active) {
          const list = Array.isArray(data?.data) ? data.data : []
          setFavorites(list)
          setCachedResource(`favorites:${CACHE_KEY}`, list)
        }
      } catch (err) {
        if (active) setError(err.message || 'Nie udało się pobrać półki ulubionych książek')
      } finally {
        if (active) setLoading(false)
      }
    }

    load()

    // Poll for cache changes every 2 seconds
    const interval = setInterval(() => {
      const cached = getCachedResource(`favorites:${CACHE_KEY}`, CACHE_TTL)
      if (!cached) {
        load()
      }
    }, 2000)

    return () => {
      active = false
      clearInterval(interval)
    }
  }, [getCachedResource, invalidateResource, setCachedResource, user?.id, CACHE_KEY, CACHE_TTL])

  async function removeFavorite(bookId) {
    setActionError(null)
    try {
      await apiFetch(`/api/favorites/${bookId}`, { method: 'DELETE' })
      setFavorites(prev => {
        const next = prev.filter(f => f.book?.id !== bookId)
        setCachedResource(`favorites:${CACHE_KEY}`, next)
        return next
      })
      // Invalidate recommended as favorites affect recommendations
      invalidateResource('recommended:*')
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
      <PageHeader
        title="Moja półka"
        subtitle="Zebrane tytuły, do których chcesz szybko wrócić lub zamówić później."
      />

      <StatGrid>
        <StatCard title="Ulubione tytuły" value={Array.isArray(favorites) ? favorites.length : 0} subtitle="Na półce" />
        <StatCard title="Ostatnio dodane" value={lastAdded ? new Date(lastAdded).toLocaleDateString('pl-PL') : '-'} subtitle="Najświeższy wpis" />
        <StatCard title="Szybka akcja" value="Katalog" subtitle="Dodaj nowe tytuły">
          <Link className="btn btn-ghost" to="/books">Przejdź do katalogu</Link>
        </StatCard>
      </StatGrid>

      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}
      {actionError && <FeedbackCard variant="error">{actionError}</FeedbackCard>}

      {!Array.isArray(favorites) || favorites.length === 0 ? (
        <SectionCard className="empty-state">
          Brak zapisanych tytułów. Otwórz katalog i dodaj książki do ulubionych.
        </SectionCard>
      ) : (
        <SectionCard>
          <ul className="resource-list">
            {favorites.map(fav => (
              <li key={fav.id} className="resource-item">
                <div>
                  <strong>{fav.book?.title ?? 'Nieznana książka'}</strong>
                  <div className="resource-item__meta">
                    <span>Autor: {fav.book?.author?.name ?? '-'}</span>
                    <span>Dodano: {fav.createdAt ? new Date(fav.createdAt).toLocaleDateString('pl-PL') : '-'}</span>
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
        </SectionCard>
      )}
    </div>
  )
}
