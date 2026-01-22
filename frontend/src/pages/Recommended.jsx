import React, { useEffect, useMemo, useState } from 'react'
import BookItem from '../components/BookItem'
import { apiFetch } from '../api'
import { useResourceCache } from '../context/ResourceCacheContext'
import { useAuth } from '../context/AuthContext'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import SectionCard from '../components/ui/SectionCard'
import FeedbackCard from '../components/ui/FeedbackCard'
import { logger } from '../utils/logger'

const CACHE_TTL = 60000
const CACHE_KEY = 'recommended:/api/books/recommended'

export default function Recommended() {
  const [groups, setGroups] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [actionError, setActionError] = useState(null)
  const [dismissedBooks, setDismissedBooks] = useState(new Set())
  const [lastDismissedId, setLastDismissedId] = useState(null)
  const [expandedBookId, setExpandedBookId] = useState(null)
  const [isMobile, setIsMobile] = useState(window.innerWidth <= 768)
  const { getCachedResource, setCachedResource, invalidateResource } = useResourceCache()
  const { token, user } = useAuth()
  const cacheKey = `recommended:${user?.id ?? 'anon'}:/api/books/recommended`
  const totalBooks = useMemo(() => groups.reduce((sum, group) => sum + (group.books?.length ?? 0), 0), [groups])

  async function dismissBook(bookId) {
    if (!token) return

    try {
      setActionError(null)
      await apiFetch('/api/recommendations/feedback', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ bookId, feedbackType: 'dismiss' })
      })

      setDismissedBooks(prev => new Set([...prev, bookId]))
      setLastDismissedId(bookId)
      invalidateResource(cacheKey)
    } catch (err) {
      setActionError(err.message || 'Nie udało się ukryć rekomendacji')
      logger.error('Failed to dismiss book:', err)
    }
  }

  async function undoDismiss(bookId) {
    if (!token) return
    try {
      setActionError(null)
      await apiFetch(`/api/recommendations/feedback/${bookId}`, { method: 'DELETE' })
      setDismissedBooks(prev => {
        const next = new Set(prev)
        next.delete(bookId)
        return next
      })
      setLastDismissedId(null)
      invalidateResource(cacheKey)
    } catch (err) {
      setActionError(err.message || 'Nie udało się cofnąć ukrycia')
      logger.error('Failed to undo dismiss:', err)
    }
  }

  useEffect(() => {
    const handleResize = () => {
      setIsMobile(window.innerWidth <= 768)
    }
    window.addEventListener('resize', handleResize)
    return () => window.removeEventListener('resize', handleResize)
  }, [])

  useEffect(() => {
    let active = true

    async function load() {
      if (!token) {
        if (active) {
          setGroups([])
          setLoading(false)
          setError(null)
        }
        return
      }

      const cached = getCachedResource(cacheKey, CACHE_TTL)
      if (cached) {
        if (active) {
          setGroups(cached)
          setLoading(false)
        }
        return
      }

      setLoading(true)
      setError(null)

      try {
        const response = await apiFetch('/api/books/recommended')
        const normalized = Array.isArray(response?.groups)
          ? response.groups.filter(group => Array.isArray(group.books))
          : []

        if (active) {
          setGroups(normalized)
          setCachedResource(cacheKey, normalized)
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

    const interval = setInterval(() => {
      const cached = getCachedResource(cacheKey, CACHE_TTL)
      if (!cached) {
        load()
      }
    }, 2000)

    return () => {
      active = false
      clearInterval(interval)
    }
  }, [getCachedResource, setCachedResource, cacheKey, token])

  return (
    <div className="page">
      <PageHeader
        title="Polecane"
        subtitle="Poznaj wybrane książki dobrane do Twoich preferencji."
        actions={token && lastDismissedId ? (
          <button className="btn btn-secondary" type="button" onClick={() => undoDismiss(lastDismissedId)}>
            Cofnij ukrycie
          </button>
        ) : null}
      />

      <StatGrid>
        <StatCard title="Liczba grup" value={groups.length} subtitle="Kategorie rekomendacji" />
        <StatCard title="Propozycje" value={totalBooks} subtitle="Łącznie tytułów" />
        <StatCard title="Ukryte" value={dismissedBooks.size} subtitle="Odrzucone tytuły" />
      </StatGrid>

      {loading && (
        <SectionCard className="empty-state">Ładuję polecane książki...</SectionCard>
      )}

      {!loading && error && <FeedbackCard variant="error">{error}</FeedbackCard>}
      {!loading && actionError && <FeedbackCard variant="error">{actionError}</FeedbackCard>}

      {!loading && !error && (!Array.isArray(groups) || groups.length === 0) && (
        <SectionCard className="empty-state">
          Brak polecanych książek w tym momencie. Wróć do nas wkrótce!
        </SectionCard>
      )}

      {!loading && !error && Array.isArray(groups) && groups.length > 0 && (
        <div className="recommended-groups">
          {groups.map(group => (
            <SectionCard
              key={group.key}
              className="recommended-section"
              header={(
                <div className="recommended-section__header">
                  <h2>{group.label}</h2>
                  {group.description && <p className="support-copy">{group.description}</p>}
                </div>
              )}
            >
              {group.books.length === 0 ? (
                <div className="empty-state">Brak polecanych tytułów w tej kategorii.</div>
              ) : (
                <div className="books-grid">
                  {group.books
                    .filter(book => !dismissedBooks.has(book.id))
                    .map(book => (
                      <div key={book.id} className="book-card--dismissable">
                        {token && (
                          <button
                            className="dismiss-btn"
                            onClick={() => dismissBook(book.id)}
                            title="Nie interesuje mnie"
                            aria-label="Ukryj rekomendację"
                          >
                            &times;
                          </button>
                        )}
                        <BookItem 
                          book={book}
                          compact={isMobile}
                          expanded={expandedBookId === book.id}
                          onToggleExpand={() => setExpandedBookId(expandedBookId === book.id ? null : book.id)}
                        />
                      </div>
                    ))
                  }
                </div>
              )}
            </SectionCard>
          ))}
        </div>
      )}
    </div>
  )
}
