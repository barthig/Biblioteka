import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useParams } from 'react-router-dom'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'
import { useResourceCache } from '../context/ResourceCacheContext'
import { StarRating, RatingDisplay } from '../components/StarRating'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import SectionCard from '../components/ui/SectionCard'
import { logger } from '../utils/logger'
import FeedbackCard from '../components/ui/FeedbackCard'

function formatDate(value, withTime = false) {
  if (!value) return '—'
  const date = new Date(value)
  return withTime ? date.toLocaleString('pl-PL') : date.toLocaleDateString('pl-PL')
}

function resolveAvatarUrl(user) {
  if (!user) return null
  return user.avatarUrl ?? user.avatar ?? user.avatarPath ?? null
}

function getInitials(name) {
  if (!name) return '?'
  const trimmed = name.trim()
  if (!trimmed) return '?'
  const parts = trimmed.split(/\s+/)
  const initials = parts
    .filter(Boolean)
    .slice(0, 2)
    .map(part => part[0]?.toUpperCase() ?? '')
    .join('')
  return initials || '?'
}

export default function BookDetails() {
  const { id } = useParams()
  const bookId = Number(id)
  const { token, user } = useAuth()
  const isAuthenticated = Boolean(token || user?.id)

  const [book, setBook] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  const [favorite, setFavorite] = useState(false)
  const [activeReservation, setActiveReservation] = useState(null)

  const [actionError, setActionError] = useState(null)
  const [actionSuccess, setActionSuccess] = useState(null)
  const [reserving, setReserving] = useState(false)
  const [borrowing, setBorrowing] = useState(false)
  const [favoriteLoading, setFavoriteLoading] = useState(false)
  const [engagementLoading, setEngagementLoading] = useState(false)
  const [engagementFetched, setEngagementFetched] = useState(false)
  const engagementFetchRef = useRef(false)
  const aliveRef = useRef(true)

  const [reviewsState, setReviewsState] = useState({ summary: { average: null, total: 0 }, reviews: [], userReview: null })
  const [reviewsError, setReviewsError] = useState(null)
  const [reviewForm, setReviewForm] = useState({ rating: 5, comment: '' })
  const [reviewPending, setReviewPending] = useState(false)
  const [reviewActionError, setReviewActionError] = useState(null)
  const [reviewActionSuccess, setReviewActionSuccess] = useState(null)
  const [ratingData, setRatingData] = useState({ average: 0, count: 0, userRating: null })
  const [ratingsError, setRatingsError] = useState(null)
  const { getCachedResource, setCachedResource, invalidateResource } = useResourceCache()
  const REVIEWS_CACHE_TTL = 60000
  const RESERVATIONS_CACHE_TTL = 45000
  const ratingSummaryAverage = (reviewsState.summary?.total ?? 0) > 0
    ? reviewsState.summary.average
    : (ratingData?.average ?? null)
  const ratingSummaryCount = (reviewsState.summary?.total ?? 0) > 0
    ? reviewsState.summary.total
    : (ratingData?.count ?? 0)


  useEffect(() => {
    let active = true

    async function load() {
      setLoading(true)
      setError(null)
      setActionError(null)
      setActionSuccess(null)
  setFavorite(false)
  setActiveReservation(null)
      try {
        const data = await apiFetch(`/api/books/${id}`)
        if (active) {
          setBook(data)
          setRatingData(prev => ({
            average: typeof data?.averageRating == 'number' ? data.averageRating : (prev.average || 0),
            count: typeof data?.ratingCount == 'number' ? data.ratingCount : (prev.count || 0),
            userRating: prev.userRating || null,
          }))
        }
      } catch (err) {
        if (active) {
          setError(err.message || 'Nie udało się pobrać szczegółów książki')
        }
      } finally {
        if (active) setLoading(false)
      }
    }

    load()
    return () => { active = false }
  }, [id])

  const loadReviews = useCallback(async () => {
    setReviewsError(null)
    const cacheKey = `reviews:${bookId}`
    const cached = getCachedResource(cacheKey, REVIEWS_CACHE_TTL)
    if (cached) {
      setReviewsState(cached)
      setReviewForm({
        rating: cached.userReview?.rating ?? 5,
        comment: cached.userReview?.comment ?? '',
      })
      return
    }
    try {
      const data = await apiFetch(`/api/books/${id}/reviews`)
      const summary = data?.summary ?? { average: null, total: 0 }
      const reviews = Array.isArray(data?.reviews) ? data.reviews : []
      const userReview = data?.userReview ?? null
      setReviewsState({ summary, reviews, userReview })
      setReviewForm({
        rating: userReview?.rating ?? 5,
        comment: userReview?.comment ?? '',
      })
      setCachedResource(cacheKey, { summary, reviews, userReview })
    } catch (err) {
      setReviewsError(err.message || 'Nie udało się pobrać opinii czytelników')
    }
  }, [bookId, getCachedResource, id, setCachedResource])

  useEffect(() => {
    loadReviews()
  }, [loadReviews])

  useEffect(() => {
    if (!id) return
    
    async function loadRatings() {
      setRatingsError(null)
      try {
        const data = await apiFetch(`/api/books/${id}/ratings`)
        setRatingData({
          average: data.average || 0,
          count: data.count || 0,
          userRating: data.userRating || null
        })
      } catch (err) {
        setRatingsError(err.message || 'Nie udaĹ‚o siÄ™ pobraÄ‡ ocen')
        logger.error('Failed to load ratings:', err)
      }
    }
    
    loadRatings()
  }, [id])

  useEffect(() => {
    aliveRef.current = true
    return () => {
      aliveRef.current = false
    }
  }, [])

  useEffect(() => {
    if (!book) return
    setFavorite(Boolean(book.isFavorite))
  }, [book])

  useEffect(() => {
    setActiveReservation(null)
    setEngagementFetched(false)
    engagementFetchRef.current = false
  }, [bookId, token])

  const ensureEngagementLoaded = useCallback(async () => {
    if (!token || engagementFetchRef.current || engagementFetched) {
      return
    }

    engagementFetchRef.current = true
    const reservationsCacheKey = 'reservations:/api/reservations'
    const cachedReservations = getCachedResource(reservationsCacheKey, RESERVATIONS_CACHE_TTL)

    if (cachedReservations && Array.isArray(cachedReservations)) {
      const reservationMatch = cachedReservations.find(item => item?.book?.id === bookId)
      if (reservationMatch) {
        setActiveReservation(reservationMatch)
      }
      engagementFetchRef.current = false
      setEngagementFetched(true)
      setEngagementLoading(false)
      return
    }

    setEngagementLoading(true)

    try {
      const response = await apiFetch('/api/reservations')
      const reservations = Array.isArray(response?.data) ? response.data : []
      if (reservations.length > 0 || response?.data) {
        setCachedResource(reservationsCacheKey, reservations)
        if (aliveRef.current) {
          const reservationMatch = reservations.find(item => item?.book?.id === bookId)
          if (reservationMatch) {
            setActiveReservation(reservationMatch)
          }
        }
      }
    } catch (err) {
      // ignore engagement prefetch errors – buttons will rely on server validation
    } finally {
      engagementFetchRef.current = false
      if (aliveRef.current) {
        setEngagementLoading(false)
        setEngagementFetched(true)
      }
    }
  }, [bookId, engagementFetched, getCachedResource, setCachedResource, token])

  useEffect(() => {
    if (!token || engagementFetched) {
      return
    }

    const timer = setTimeout(() => {
      ensureEngagementLoaded()
    }, 400)

    return () => {
      clearTimeout(timer)
    }
  }, [token, engagementFetched, ensureEngagementLoaded])

  const categories = useMemo(() => {
    if (!book || !Array.isArray(book.categories) || book.categories.length === 0) {
      return '—'
    }
    return book.categories.map(c => c.name).join(', ')
  }, [book])

  const ageGroupLabel = book?.targetAgeGroupLabel ?? book?.targetAgeGroup ?? null

  const anyAvailable = book ? (book.copies ?? 0) > 0 : false

  const canReserve = Boolean(token && book && !anyAvailable && !activeReservation)
  const canBorrow = Boolean(token && user?.id && book && anyAvailable)

  async function handleReservation() {
    if (!token) {
      setActionError('Zaloguj się, aby dołączyć do kolejki rezerwacji.')
      return
    }
    setReserving(true)
    setActionError(null)
    setActionSuccess(null)
    try {
      const response = await apiFetch('/api/reservations', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ bookId }),
      })
      const reservation = response?.data || response
      setActionSuccess('Dodano do kolejki rezerwacji. Powiadomimy Cię, gdy egzemplarz będzie dostępny.')
      setActiveReservation(reservation)
      setEngagementFetched(true)
      invalidateResource('reservations:/api/reservations*')
    } catch (err) {
      setActionError(err.message || 'Nie udało się zarezerwować książki')
    } finally {
      setReserving(false)
    }
  }
  async function handleBorrow() {
    if (!token || !user?.id) {
      setActionError('Zaloguj się, aby wypożyczyć książkę.')
      return
    }
    if (!book) return
    setBorrowing(true)
    setActionError(null)
    setActionSuccess(null)
    try {
      await apiFetch('/api/loans', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ bookId, userId: user.id })
      })
      setActionSuccess('Wypożyczenie utworzone. Sprawdź w "Moje wypożyczenia".')
      setBook(prev => (prev ? { ...prev, copies: Math.max(0, (prev.copies ?? 0) - 1) } : prev))
      invalidateResource('loans:/api/loans*')
      invalidateResource('books:/api/books*')
      invalidateResource('reservations:/api/reservations*')
    } catch (err) {
      setActionError(err.message || 'Nie udało się wypożyczyć książki')
    } finally {
      setBorrowing(false)
    }
  }

  async function toggleFavorite() {
    if (!token) {
      setActionError('Zaloguj się, aby zapisać książkę na wirtualnej półce.')
      return
    }
    setFavoriteLoading(true)
    setActionError(null)
    setActionSuccess(null)
    try {
      if (favorite) {
        await apiFetch(`/api/favorites/${bookId}`, { method: 'DELETE' })
        setFavorite(false)
        setActionSuccess('Usunięto książkę z ulubionych.')
        invalidateResource('favorites:/api/favorites')
        invalidateResource('recommended:*')
      } else {
        const response = await apiFetch('/api/favorites', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ bookId }),
        })
        const created = response?.data || response
        setFavorite(Boolean(created))
        setActionSuccess('Dodano książkę do ulubionych.')
        invalidateResource('favorites:/api/favorites')
        invalidateResource('recommended:*')
      }
    } catch (err) {
      setActionError(err.message || 'Nie udało się zaktualizować ulubionych')
    } finally {
      setFavoriteLoading(false)
    }
  }

  async function submitReview(event) {
    event.preventDefault()
    if (!token) {
      setReviewActionError('Zaloguj się, aby dodać opinię.')
      return
    }
    setReviewPending(true)
    setReviewActionError(null)
    setReviewActionSuccess(null)
    try {
      await apiFetch(`/api/books/${id}/reviews`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ rating: reviewForm.rating, comment: reviewForm.comment }),
      })
      setReviewActionSuccess('Opinia została zapisana.')
      invalidateResource(`reviews:${bookId}`)
      await loadReviews()
      try {
        const data = await apiFetch(`/api/books/${id}/ratings`)
        setRatingData({
          average: data.average || 0,
          count: data.count || 0,
          userRating: data.userRating || null
        })
      } catch {
        // ignore ratings refresh errors
      }

    } catch (err) {
      setReviewActionError(err.message || 'Nie udało się zapisać opinii')
    } finally {
      setReviewPending(false)
    }
  }

  async function deleteReview() {
    if (!token) return
    setReviewPending(true)
    setReviewActionError(null)
    setReviewActionSuccess(null)
    try {
      await apiFetch(`/api/books/${id}/reviews`, { method: 'DELETE' })
      setReviewActionSuccess('Opinia została usunięta.')
      invalidateResource(`reviews:${bookId}`)
      await loadReviews()
    } catch (err) {
      setReviewActionError(err.message || 'Nie udało się usunąć opinii')
    } finally {
      setReviewPending(false)
    }
  }

  if (loading) {
    return (
      <div className="page">
        <SectionCard className="empty-state">Ładuję szczegóły książki...</SectionCard>
      </div>
    )
  }

  if (error) {
    return (
      <div className="page">
        <FeedbackCard variant="error">{error}</FeedbackCard>
      </div>
    )
  }

  if (!book) {
    return (
      <div className="page">
        <SectionCard className="empty-state">Nie znaleziono książki.</SectionCard>
      </div>
    )
  }

  return (
    <div className="page">
      <PageHeader
        title={book.title}
        subtitle="Sprawdź dostępność, dołącz do kolejki rezerwacji i przeczytaj opinie czytelników."
      />

      <StatGrid>
        <StatCard valueClassName="stat-card__value--sm" title="Dostępność" value={anyAvailable ? 'Dostępne' : 'Brak'} subtitle={`${book.copies ?? 0} z ${book.totalCopies ?? book.copies ?? 0}`} />
        <StatCard valueClassName="stat-card__value--sm" title="Oceny" value={ratingSummaryAverage ? ratingSummaryAverage.toFixed(1) : 'Brak'} />
        <StatCard valueClassName="stat-card__value--sm" title="Rezerwacje" value={activeReservation ? 'Aktywna' : (anyAvailable ? 'Niepotrzebna' : 'Dostępna')} />
      </StatGrid>

      <SectionCard>
        <dl>
          <div className="resource-item__meta">
            <dt>Autor</dt>
            <dd>{book.author?.name ?? 'Autor nieznany'}</dd>
          </div>
          <div className="resource-item__meta">
            <dt>Kategorie</dt>
            <dd>{categories}</dd>
          </div>
          {ageGroupLabel && (
            <div className="resource-item__meta">
              <dt>Przedział wiekowy</dt>
              <dd>{ageGroupLabel}</dd>
            </div>
          )}
          <div className="resource-item__meta">
            <dt>ISBN</dt>
            <dd>{book.isbn ?? '—'}</dd>
          </div>
          <div className="resource-item__meta">
            <dt>Wydawca</dt>
            <dd>{book.publisher ?? '—'}</dd>
          </div>
          <div className="resource-item__meta">
            <dt>Rok wydania</dt>
            <dd>{book.publicationYear ?? '—'}</dd>
          </div>
          <div className="resource-item__meta">
            <dt>Typ zasobu</dt>
            <dd>{book.resourceType ?? '—'}</dd>
          </div>
          <div className="resource-item__meta">
            <dt>Sygnatura</dt>
            <dd>{book.signature ?? '—'}</dd>
          </div>
          <div className="resource-item__meta">
            <dt>Opis</dt>
            <dd>{book.description ?? 'Brak opisu.'}</dd>
          </div>
          <div className="resource-item__meta">
            <dt>Dostępne egzemplarze</dt>
            <dd>{book.copies ?? 0} / {book.totalCopies ?? book.copies ?? 0}</dd>
          </div>
          <div className="resource-item__meta">
            <dt>Magazyn (zamknięty dostęp)</dt>
            <dd>{book.storageCopies ?? 0}</dd>
          </div>
          <div className="resource-item__meta">
            <dt>Półki w wolnym dostępie</dt>
            <dd>{book.openStackCopies ?? 0}</dd>
          </div>
        </dl>
        
      </SectionCard>

      <SectionCard title="Działania" className="book-actions">
        {actionError && <p className="error">{actionError}</p>}
        {actionSuccess && <p className="success">{actionSuccess}</p>}
        <div
          className="form-actions"
          onMouseEnter={ensureEngagementLoaded}
          onFocusCapture={ensureEngagementLoaded}
        >{anyAvailable ? (
            <button
              type="button"
              className="btn btn-primary"
              onClick={handleBorrow}
              disabled={!canBorrow || borrowing}
            >
              {borrowing ? 'Przetwarzanie...' : 'Wypożycz'}
            </button>
          ) : (

          <button
            type="button"
            className="btn btn-primary"
            onClick={handleReservation}
            disabled={!canReserve || reserving}
          >
            {reserving ? 'Przetwarzanie...' : 'Dołącz do kolejki rezerwacji'}
          </button>
          )}
          <button
            type="button"
            className="btn btn-ghost"
            onClick={toggleFavorite}
            disabled={favoriteLoading}
          >
            {favorite ? 'Usuń z ulubionych' : 'Dodaj do ulubionych'}
          </button>
        </div>
        <p className="support-copy">
          Jeśli egzemplarze są aktualnie wypożyczone, dołącz do kolejki rezerwacji, aby otrzymać powiadomienie o dostępności.
        </p>
        {token && engagementLoading && (
          <p className="support-copy">Sprawdzam Twoje aktywne rezerwacje...</p>
        )}
        {!token && (
          <p className="support-copy">Zaloguj się, aby rezerwować i dodawać książki do ulubionych.</p>
        )}
        {anyAvailable && (
          <p className="support-copy">Egzemplarze są dostępne od ręki — odwiedź wypożyczalnię, aby wypożyczyć książkę bez oczekiwania.</p>
        )}
        {activeReservation && (
          <p className="support-copy">
            Masz aktywną rezerwację na ten tytuł. Zarezerwowano: {formatDate(activeReservation.reservedAt, true)}.
            {activeReservation.expiresAt && ` Wygasa: ${formatDate(activeReservation.expiresAt, true)}.`}
          </p>
        )}
      </SectionCard>

      <SectionCard title="Oceny i opinie">
        {ratingsError && <p className="error">{ratingsError}</p>}
        {reviewsError && <p className="error">{reviewsError}</p>}
        <div className="review-summary">
          <div>
            <strong>Średnia ocena:</strong> {ratingSummaryAverage ? ratingSummaryAverage.toFixed(1) : 'Brak ocen'}
          </div>
          <div>
            <strong>Liczba ocen:</strong> {ratingSummaryCount ?? 0}
          </div>
        </div>

        {isAuthenticated ? (
          <form onSubmit={submitReview} className="form-grid review-form">
            <div>
              <label>Ocena</label>
              <StarRating
                rating={reviewForm.rating}
                onRate={(value) => setReviewForm({ ...reviewForm, rating: value })}
                size="large"
                readonly={reviewPending}
              />
            </div>
            <div>
              <label htmlFor="review-comment">Opinia</label>
              <textarea
                id="review-comment"
                rows={4}
                value={reviewForm.comment}
                onChange={event => setReviewForm({ ...reviewForm, comment: event.target.value })}
                placeholder="Podziel się wrażeniami (opcjonalne)"
                disabled={reviewPending}
              />
            </div>
            {reviewActionError && <p className="error">{reviewActionError}</p>}
            {reviewActionSuccess && <p className="success">{reviewActionSuccess}</p>}
            <div className="form-actions">
              <button type="submit" className="btn btn-primary" disabled={reviewPending}>
                {reviewsState.userReview ? 'Aktualizuj opinię' : 'Dodaj opinię'}
              </button>
              {reviewsState.userReview && (
                <button type="button" className="btn btn-outline" onClick={deleteReview} disabled={reviewPending}>
                  Usuń opinię
                </button>
              )}
            </div>
          </form>
        ) : (
          <p className="support-copy">Zaloguj się, aby dodać ocenę i opinię.</p>
        )}

        {reviewsState.reviews.length === 0 ? (
          <div className="empty-state">Brak opinii dla tej książki. Bądź pierwszą osobą, która ją oceni.</div>
        ) : (
          <ul className="review-list">
            {reviewsState.reviews.map(review => {
              const reviewerName = (review.user?.name ?? 'Czytelnik').trim() || 'Czytelnik'
              const avatarUrl = resolveAvatarUrl(review.user)
              const initials = getInitials(reviewerName)

              return (
                <li key={review.id} className="review-item">
                  <div className="review-item__header">
                    <div className="review-item__user">
                      {avatarUrl ? (
                        <span className="avatar avatar--sm">
                          <img src={avatarUrl} alt={`Zdjęcie użytkownika ${reviewerName}`} loading="lazy" />
                        </span>
                      ) : (
                        <span className="avatar avatar--sm avatar--fallback" aria-hidden="true">{initials}</span>
                      )}
                      <strong>{reviewerName}</strong>
                    </div>
                    <span className="review-item__rating">Ocena: {review.rating}/5</span>
                  </div>
                  {review.comment && <p>{review.comment}</p>}
                  <div className="resource-item__meta">
                    <span>Dodano: {formatDate(review.updatedAt)}</span>
                  </div>
                </li>
              )
            })}
          </ul>
        )}
      </SectionCard>
    </div>
  )
}





