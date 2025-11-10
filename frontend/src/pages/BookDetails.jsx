import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useParams } from 'react-router-dom'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'
import { useResourceCache } from '../context/ResourceCacheContext'

function formatDate(value, withTime = false) {
  if (!value) return '—'
  const date = new Date(value)
  return withTime ? date.toLocaleString() : date.toLocaleDateString()
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
  const { token } = useAuth()

  const [book, setBook] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  const [favorite, setFavorite] = useState(false)
  const [activeOrder, setActiveOrder] = useState(null)
  const [activeReservation, setActiveReservation] = useState(null)

  const [actionError, setActionError] = useState(null)
  const [actionSuccess, setActionSuccess] = useState(null)
  const [ordering, setOrdering] = useState(false)
  const [reserving, setReserving] = useState(false)
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
  const { getCachedResource, setCachedResource, invalidateResource } = useResourceCache()
  const REVIEWS_CACHE_TTL = 60000
  const ORDERS_CACHE_TTL = 45000

  useEffect(() => {
    let active = true

    async function load() {
      setLoading(true)
      setError(null)
      setActionError(null)
      setActionSuccess(null)
      setFavorite(false)
      setActiveOrder(null)
      setActiveReservation(null)
      try {
        const data = await apiFetch(`/api/books/${id}`)
        if (active) {
          setBook(data)
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
    setActiveOrder(null)
    setActiveReservation(null)
    setEngagementFetched(false)
    engagementFetchRef.current = false
  }, [bookId, token])

  const ensureEngagementLoaded = useCallback(async () => {
    if (!token || engagementFetchRef.current || engagementFetched) {
      return
    }

    engagementFetchRef.current = true
    const ordersCacheKey = 'orders:/api/orders'
    const reservationsCacheKey = 'reservations:/api/reservations'
    const cachedOrders = getCachedResource(ordersCacheKey, ORDERS_CACHE_TTL)
    const cachedReservations = getCachedResource(reservationsCacheKey, ORDERS_CACHE_TTL)

    if (cachedOrders && Array.isArray(cachedOrders)) {
      const orderMatch = cachedOrders.find(item => item?.book?.id === bookId)
      if (orderMatch) {
        setActiveOrder(orderMatch)
      }
    }

    if (cachedReservations && Array.isArray(cachedReservations)) {
      const reservationMatch = cachedReservations.find(item => item?.book?.id === bookId)
      if (reservationMatch) {
        setActiveReservation(reservationMatch)
      }
    }

    if (cachedOrders && cachedReservations) {
      engagementFetchRef.current = false
      setEngagementFetched(true)
      setEngagementLoading(false)
      return
    }

    setEngagementLoading(true)

    try {
      const [ordersResult, reservationsResult] = await Promise.allSettled([
        cachedOrders ? Promise.resolve(cachedOrders) : apiFetch('/api/orders'),
        cachedReservations ? Promise.resolve(cachedReservations) : apiFetch('/api/reservations'),
      ])

      const ordersList = ordersResult.status === 'fulfilled' && Array.isArray(ordersResult.value)
        ? ordersResult.value
        : null
      const reservationsList = reservationsResult.status === 'fulfilled' && Array.isArray(reservationsResult.value)
        ? reservationsResult.value
        : null

      if (ordersList) {
        setCachedResource(ordersCacheKey, ordersList)
        if (aliveRef.current) {
          const orderMatch = ordersList.find(item => item?.book?.id === bookId)
          if (orderMatch) {
            setActiveOrder(orderMatch)
          }
        }
      }

      if (reservationsList) {
        setCachedResource(reservationsCacheKey, reservationsList)
        if (aliveRef.current) {
          const reservationMatch = reservationsList.find(item => item?.book?.id === bookId)
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

  const storageAvailable = book ? (book.storageCopies ?? 0) > 0 : false
  const openStackAvailable = book ? (book.openStackCopies ?? 0) > 0 : false
  const anyAvailable = book ? (book.copies ?? 0) > 0 : false

  const canOrderStorage = Boolean(token && storageAvailable && !activeOrder && !activeReservation)
  const canOrderOpenStack = Boolean(token && openStackAvailable && !activeOrder && !activeReservation)
  const canReserve = Boolean(token && book && !anyAvailable && !activeReservation && !activeOrder)

  async function handleOrder(pickupType) {
    if (!token) {
      setActionError('Zaloguj się, aby zamówić książkę do odbioru.')
      return
    }
    setOrdering(true)
    setActionError(null)
    setActionSuccess(null)
    try {
      const order = await apiFetch('/api/orders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ bookId, pickupType, days: 2 }),
      })

      const successMessage = pickupType === 'STORAGE_DESK'
        ? 'Zamówienie z magazynu przyjęte. Książka będzie czekała w wypożyczalni.'
        : 'Zlecono odłożenie egzemplarza z półki. Odbierz go w wypożyczalni w ciągu 2 dni.'

      setActionSuccess(successMessage)
      setActiveOrder(order)
      setEngagementFetched(true)
      invalidateResource('orders:/api/orders*')
      setBook(prev => {
        if (!prev) return prev
        const copies = Math.max(0, (prev.copies ?? 0) - 1)
        const storageCopies = pickupType === 'STORAGE_DESK'
          ? Math.max(0, (prev.storageCopies ?? 0) - 1)
          : prev.storageCopies ?? 0
        const openStackCopies = pickupType === 'OPEN_SHELF'
          ? Math.max(0, (prev.openStackCopies ?? 0) - 1)
          : prev.openStackCopies ?? 0

        return {
          ...prev,
          copies,
          storageCopies,
          openStackCopies,
        }
      })
    } catch (err) {
      setActionError(err.message || 'Nie udało się złożyć zamówienia')
    } finally {
      setOrdering(false)
    }
  }

  async function handleReservation() {
    if (!token) {
      setActionError('Zaloguj się, aby dołączyć do kolejki rezerwacji.')
      return
    }
    setReserving(true)
    setActionError(null)
    setActionSuccess(null)
    try {
      const reservation = await apiFetch('/api/reservations', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ bookId }),
      })
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
      } else {
        const created = await apiFetch('/api/favorites', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ bookId }),
        })
        setFavorite(Boolean(created))
        setActionSuccess('Dodano książkę do ulubionych.')
        invalidateResource('favorites:/api/favorites')
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
        <div className="surface-card empty-state">Ładuję szczegóły książki...</div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="page">
        <div className="surface-card">
          <p className="error">{error}</p>
        </div>
      </div>
    )
  }

  if (!book) {
    return (
      <div className="page">
        <div className="surface-card empty-state">Nie znaleziono książki.</div>
      </div>
    )
  }

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>{book.title}</h1>
          <p className="support-copy">Sprawdź dostępność, zamów egzemplarz do odbioru i przeczytaj opinie czytelników.</p>
        </div>
      </header>

      <article className="surface-card">
        <dl>
          <div className="resource-item__meta">
            <dt>Autor</dt>
            <dd>{book.author?.name ?? 'Autor nieznany'}</dd>
          </div>
          <div className="resource-item__meta">
            <dt>Kategorie</dt>
            <dd>{categories}</dd>
          </div>
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
      </article>

      <section className="surface-card book-actions">
        <h2>Działania</h2>
        {actionError && <p className="error">{actionError}</p>}
        {actionSuccess && <p className="success">{actionSuccess}</p>}
        <div
          className="form-actions"
          onMouseEnter={ensureEngagementLoaded}
          onFocusCapture={ensureEngagementLoaded}
        >
          <button
            type="button"
            className="btn btn-primary"
            onClick={() => handleOrder('STORAGE_DESK')}
            disabled={!canOrderStorage || ordering}
          >
            {ordering ? 'Przetwarzanie...' : 'Zamów z magazynu'}
          </button>
          <button
            type="button"
            className="btn btn-outline"
            onClick={() => handleOrder('OPEN_SHELF')}
            disabled={!canOrderOpenStack || ordering}
          >
            {ordering ? 'Przetwarzanie...' : 'Poproś o odłożenie z półki'}
          </button>
          <button
            type="button"
            className="btn btn-outline"
            onClick={handleReservation}
            disabled={!canReserve || reserving}
          >
            {reserving ? 'Przetwarzanie...' : 'Dołącz do kolejki'}
          </button>
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
          Egzemplarze magazynowe przygotuje dla Ciebie bibliotekarz. Jeżeli wybierzesz opcję półki, pracownik odłoży wybrany egzemplarz z wolnego dostępu do wypożyczalni.
        </p>
        {token && engagementLoading && (
          <p className="support-copy">Sprawdzam Twoje aktywne zamówienia i rezerwacje...</p>
        )}
        {!storageAvailable && openStackAvailable && (
          <p className="support-copy">Brak egzemplarzy w magazynie — najłatwiej skorzystać z opcji „Poproś o odłożenie z półki” lub podejść osobiście.</p>
        )}
        {!token && (
          <p className="support-copy">Zaloguj się, aby zamawiać, rezerwować i dodawać książki do ulubionych.</p>
        )}
        {activeOrder && (
          <p className="support-copy">Masz aktywne zamówienie na ten tytuł. Termin odbioru: {formatDate(activeOrder.pickupDeadline, true)}.</p>
        )}
        {activeReservation && (
          <p className="support-copy">
            Masz aktywną rezerwację na ten tytuł. Zarezerwowano: {formatDate(activeReservation.reservedAt, true)}.
            {activeReservation.expiresAt && ` Wygasa: ${formatDate(activeReservation.expiresAt, true)}.`}
          </p>
        )}
      </section>

      <section className="surface-card">
        <h2>Oceny i opinie</h2>
        {reviewsError && <p className="error">{reviewsError}</p>}
        <div className="review-summary">
          <div>
            <strong>Średnia ocena:</strong> {reviewsState.summary.average ?? 'Brak ocen'}
          </div>
          <div>
            <strong>Liczba opinii:</strong> {reviewsState.summary.total}
          </div>
        </div>

        <form onSubmit={submitReview} className="form-grid review-form">
          <div>
            <label htmlFor="review-rating">Ocena (1-5)</label>
            <select
              id="review-rating"
              value={reviewForm.rating}
              onChange={event => setReviewForm({ ...reviewForm, rating: Number(event.target.value) })}
              disabled={reviewPending}
            >
              {[5, 4, 3, 2, 1].map(value => (
                <option key={value} value={value}>{value}</option>
              ))}
            </select>
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
      </section>
    </div>
  )
}
