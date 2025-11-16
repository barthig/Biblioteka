import React, { useEffect, useState } from 'react'
import BookItem from '../components/BookItem'
import { apiFetch } from '../api'
import { useResourceCache } from '../context/ResourceCacheContext'

const CACHE_TTL = 60000
const CACHE_KEY = 'recommended:/api/books/recommended'

export default function Recommended() {
  const [groups, setGroups] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const { getCachedResource, setCachedResource } = useResourceCache()

  useEffect(() => {
    let active = true

    async function load() {
      setLoading(true)
      setError(null)

      const cached = getCachedResource(CACHE_KEY, CACHE_TTL)
      if (cached) {
        if (active) {
          setGroups(cached)
          setLoading(false)
        }
        return
      }

      try {
        const response = await apiFetch('/api/books/recommended')
        const normalized = Array.isArray(response?.groups)
          ? response.groups.filter(group => Array.isArray(group.books))
          : []

        if (active) {
          setGroups(normalized)
          setCachedResource(CACHE_KEY, normalized)
        }
      } catch (err) {
        if (active) {
          setError(err.message || 'Nie udało się pobrać propozycji książek.')
        }
      } finally {
        if (active) {
          setLoading(false)
        }
      }
    }

    load()

    return () => {
      active = false
    }
  }, [getCachedResource, setCachedResource])

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Polecane</h1>
          <p className="support-copy">Poznaj wybrane książki dobrane do wieku czytelników.</p>
        </div>
      </header>

      {loading && (
        <div className="surface-card empty-state">Ładuję polecane książki...</div>
      )}

      {!loading && error && (
        <div className="surface-card">
          <p className="error">{error}</p>
        </div>
      )}

      {!loading && !error && groups.length === 0 && (
        <div className="surface-card empty-state">
          Brak polecanych książek w tym momencie. Wróć do nas wkrótce!
        </div>
      )}

      {!loading && !error && groups.length > 0 && (
        <div className="recommended-groups">
          {groups.map(group => (
            <section key={group.key} className="surface-card recommended-section">
              <div className="recommended-section__header">
                <h2>{group.label}</h2>
                {group.description && <p className="support-copy">{group.description}</p>}
              </div>

              {group.books.length === 0 ? (
                <div className="empty-state">Brak polecanych tytułów w tej kategorii.</div>
              ) : (
                <div className="books-grid">
                  {group.books.map(book => (
                    <BookItem key={book.id} book={book} />
                  ))}
                </div>
              )}
            </section>
          ))}
        </div>
      )}
    </div>
  )
}
