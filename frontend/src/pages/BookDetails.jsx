import React, { useCallback, useEffect, useMemo, useState } from 'react'
import { useParams } from 'react-router-dom'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'

function formatDate(value, withTime = false) {
  if (!value) return '—'
  const date = new Date(value)
  return withTime ? date.toLocaleString() : date.toLocaleDateString()
}

export default function BookDetails() {
  const { id } = useParams()
  const bookId = Number(id)
  const { token } = useAuth()

  const [book, setBook] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  const [favorite, setFavorite] = useState(null)
  const [activeOrder, setActiveOrder] = useState(null)
  const [activeReservation, setActiveReservation] = useState(null)

  const [actionError, setActionError] = useState(null)
  const [actionSuccess, setActionSuccess] = useState(null)
  const [ordering, setOrdering] = useState(false)
  const [reserving, setReserving] = useState(false)
  const [favoriteLoading, setFavoriteLoading] = useState(false)

  const [reviewsState, setReviewsState] = useState({ summary: { average: null, total: 0 }, reviews: [], userReview: null })
  const [reviewsError, setReviewsError] = useState(null)
  const [reviewForm, setReviewForm] = useState({ rating: 5, comment: '' })
  const [reviewPending, setReviewPending] = useState(false)
  const [reviewActionError, setReviewActionError] = useState(null)
  const [reviewActionSuccess, setReviewActionSuccess] = useState(null)

  useEffect(() => {
    let active = true

    async function load() {
      setLoading(true)
      setError(null)
      setActionError(null)
      setActionSuccess(null)
      setFavorite(null)
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

  const loadEngagement = useCallback(async () => {
    if (!token || !bookId) {
      setFavorite(null)
      setActiveOrder(null)
      setActiveReservation(null)
      return
    }

    try {
      const [favorites, orders, reservations] = await Promise.all([
        apiFetch('/api/favorites'),
        apiFetch('/api/orders'),
        apiFetch('/api/reservations'),
      ])

      const fav = Array.isArray(favorites) ? favorites.find(item => item.book?.id === bookId) : null
      const order = Array.isArray(orders) ? orders.find(item => item.book?.id === bookId) : null
      const reservation = Array.isArray(reservations) ? reservations.find(item => item.book?.id === bookId) : null

      setFavorite(fav || null)
      setActiveOrder(order || null)
      setActiveReservation(reservation || null)
    } catch (err) {
      // silent failure for engagement metadata
    }
  }, [bookId, token])

  const loadReviews = useCallback(async () => {
    setReviewsError(null)
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
    } catch (err) {
      setReviewsError(err.message || 'Nie udało się pobrać opinii czytelników')
    }
  }, [id])

  useEffect(() => {
    loadReviews()
  }, [loadReviews])

  useEffect(() => {
    loadEngagement()
  }, [loadEngagement])

  const categories = useMemo(() => {
    if (!book || !Array.isArray(book.categories) || book.categories.length === 0) {
      return '—'
    }
    return book.categories.map(c => c.name).join(', ')
  }, [book])

  const canOrder = Boolean(token && book && (book.copies ?? 0) > 0 && !activeOrder && !activeReservation)
  const canReserve = Boolean(token && book && (book.copies ?? 0) === 0 && !activeReservation && !activeOrder)

  async function handleOrder() {
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
        body: JSON.stringify({ bookId, pickupType: 'SHELF', days: 2 }),
      })
      setActionSuccess('Zamówienie zarejestrowane. Odbierz egzemplarz w ciągu 2 dni roboczych.')
      setActiveOrder(order)
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
      if (favorite && favorite.book?.id) {
        await apiFetch(`/api/favorites/${favorite.book.id}`, { method: 'DELETE' })
        setFavorite(null)
        setActionSuccess('Usunięto książkę z ulubionych.')
      } else {
        const created = await apiFetch('/api/favorites', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ bookId }),
        })
        setFavorite(created)
        setActionSuccess('Dodano książkę do ulubionych.')
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
        </dl>
      </article>

      <section className="surface-card book-actions">
        <h2>Działania</h2>
        {actionError && <p className="error">{actionError}</p>}
        {actionSuccess && <p className="success">{actionSuccess}</p>}
        <div className="form-actions">
          <button
            type="button"
            className="btn btn-primary"
            onClick={handleOrder}
            disabled={!canOrder || ordering}
          >
            {ordering ? 'Przetwarzanie...' : 'Zamów do odbioru'}
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
            {reviewsState.reviews.map(review => (
              <li key={review.id} className="review-item">
                <div className="review-item__header">
                  <strong>{review.user?.name ?? 'Czytelnik'}</strong>
                  <span>Ocena: {review.rating}/5</span>
                </div>
                {review.comment && <p>{review.comment}</p>}
                <div className="resource-item__meta">
                  <span>Dodano: {formatDate(review.updatedAt)}</span>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  )
}
