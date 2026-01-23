import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'
import { useResourceCache } from '../context/ResourceCacheContext'
import { RatingDisplay } from './StarRating'

export default function BookItem({ book, onBorrowed, compact = false, expanded = false, onToggleExpand }) {
  const [loading, setLoading] = useState(false)
  const [reserveLoading, setReserveLoading] = useState(false)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)
  const [reserved, setReserved] = useState(false)
  const [favorite, setFavorite] = useState(Boolean(book?.isFavorite))
  const [favoriteLoading, setFavoriteLoading] = useState(false)
  const { user } = useAuth()
  const { invalidateResource } = useResourceCache()
  const isLoggedIn = Boolean(user?.id)

  const available = book?.copies ?? 0
  const total = book?.totalCopies ?? available
  const isAvailable = available > 0
  const storageAvailable = book?.storageCopies ?? 0
  const openStackAvailable = book?.openStackCopies ?? 0
  const publisher = book?.publisher
  const publicationYear = book?.publicationYear
  const resourceType = book?.resourceType
  const signature = book?.signature
  const ageGroupLabel = book?.targetAgeGroupLabel ?? book?.targetAgeGroup ?? null

  // Determine availability status
  const getAvailabilityStatus = () => {
    if (available >= 3) return { label: `Dostępne ${available}/${total}`, className: '' }
    if (available > 0) return { label: `Ostatnie egzemplarze (${available}/${total})`, className: 'is-warning' }
    return { label: 'Brak wolnych egzemplarzy', className: 'is-danger' }
  }

  const availabilityStatus = getAvailabilityStatus()

  useEffect(() => {
    setFavorite(Boolean(book?.isFavorite))
  }, [book?.isFavorite])

  async function borrow() {
    if (!user?.id) {
      setError('Musisz być zalogowany, aby wypożyczyć książkę.')
      return
    }
    setSuccess(null)
    setLoading(true)
    setError(null)
    try {
      await apiFetch('/api/loans', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ bookId: book.id, userId: user.id })
      })
      setSuccess('Wypożyczenie zostało zarejestrowane. Odbierz książkę w wypożyczalni.')
      onBorrowed && onBorrowed(book)
    } catch (err) {
      setError(err.message || 'Nie udało się zarejestrować wypożyczenia.')
    } finally {
      setLoading(false)
    }
  }

  async function reserve() {
    if (!user?.id) {
      setError('Musisz być zalogowany, aby zarezerwować książkę.')
      return
    }
    setError(null)
    setSuccess(null)
    setReserveLoading(true)
    try {
      await apiFetch('/api/reservations', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ bookId: book.id })
      })
      setSuccess('Dodano do kolejki rezerwacji. Powiadomimy Cię, gdy egzemplarz będzie dostępny.')
      setReserved(true)
      invalidateResource('reservations:/api/reservations*')
    } catch (err) {
      setError(err.message || 'Nie udało się dodać rezerwacji.')
    } finally {
      setReserveLoading(false)
    }
  }

  async function toggleFavorite() {
    if (!user?.id) {
      setError('Musisz być zalogowany, aby zarządzać ulubionymi.')
      return
    }
    setError(null)
    setSuccess(null)
    setFavoriteLoading(true)
    try {
      if (favorite) {
        await apiFetch(`/api/favorites/${book.id}`, { method: 'DELETE' })
        setFavorite(false)
        invalidateResource('favorites:/api/favorites')
        invalidateResource('recommended:*')
        setSuccess('Usunięto książkę z ulubionych.')
      } else {
        await apiFetch('/api/favorites', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ bookId: book.id })
        })
        setFavorite(true)
        invalidateResource('favorites:/api/favorites')
        invalidateResource('recommended:*')
        setSuccess('Dodano książkę do ulubionych.')
      }
    } catch (err) {
      setError(err.message || 'Nie udało się zaktualizować ulubionych.')
    } finally {
      setFavoriteLoading(false)
    }
  }
  // Compact mode for mobile
  if (compact && !expanded) {
    return (
      <article className="book-item-compact" onClick={onToggleExpand}>
        <h3 className="book-item-compact__title">{book.title}</h3>
        <p className="book-item-compact__author">{book.author?.name ?? 'Autor nieznany'}</p>
        <div className="book-item-compact__actions">
          <button className="btn-expand" onClick={(e) => { e.stopPropagation(); onToggleExpand(); }}>
            ▼ Rozwiń
          </button>
          <span className={`status-pill status-pill--compact ${availabilityStatus.className}`}>
            {available > 0 ? `${available}/${total}` : '0'}
          </span>
        </div>
      </article>
    )
  }
  return (
    <article className={`surface-card book-card ${compact ? 'book-card--expanded' : ''}`}>
      <div className="book-card__cover">
        {(book.coverUrl || book.cover || book.imageUrl) ? (
          <img
            src={book.coverUrl || book.cover || book.imageUrl}
            alt={`Okładka: ${book.title}`}
            loading="lazy"
          />
        ) : (
          <div className="book-cover-placeholder" aria-hidden="true">
            {(book.title || '?').slice(0, 1)}
          </div>
        )}
      </div>
      <div className="book-card__header">
        <Link to={`/books/${book.id}`} className="book-card__title">{book.title}</Link>
      </div>

      <div className="book-card__rating-row">
        <RatingDisplay averageRating={book.averageRating || 0} ratingCount={book.ratingCount || 0} />
        <span className={`status-pill ${availabilityStatus.className}`}>
          {availabilityStatus.label}
        </span>
      </div>

      <div className="book-card__meta-wrap">
        <span>{book.author?.name ?? 'Autor nieznany'}</span>
        {publicationYear && <span>Rok wydania {publicationYear}</span>}
        {resourceType && <span>{resourceType}</span>}
        {book.isbn && <span>ISBN {book.isbn}</span>}
        {ageGroupLabel && <span>Wiek: {ageGroupLabel}</span>}
        {publisher && <span>Wydawca: {publisher}</span>}
        {signature && <span>Sygnatura: {signature}</span>}
        {storageAvailable !== null && <span>Magazyn: {storageAvailable}</span>}
        {openStackAvailable !== null && <span>Wolny dostęp: {openStackAvailable}</span>}
      </div>

      {book.description && (
        <p className="support-copy">{book.description}</p>
      )}

      <div className="book-card__actions-grid">
        {isLoggedIn ? (
          <>
            <button
              className="btn btn-primary"
              disabled={!isAvailable || loading}
              onClick={borrow}
            >
              {loading ? 'Wysyłanie...' : 'Wypożycz egzemplarz'}
            </button>
            <button
              className="btn btn-outline"
              disabled={!isAvailable ? (reserveLoading || reserved) : true}
              onClick={reserve}
              style={{ visibility: !isAvailable ? 'visible' : 'hidden' }}
            >
              {reserveLoading ? 'Przetwarzanie...' : reserved ? 'Zarezerwowano' : 'Dołącz do kolejki'}
            </button>
            <button
              className="btn btn-ghost"
              disabled={favoriteLoading}
              onClick={toggleFavorite}
            >
              {favoriteLoading ? 'Aktualizuję...' : favorite ? 'Usuń z ulubionych' : 'Dodaj do ulubionych'}
            </button>
            <Link to={`/books/${book.id}`} className="btn btn-outline">Szczegóły</Link>
          </>
        ) : (
          <>
            <Link to={`/books/${book.id}`} className="btn btn-outline">Szczegóły</Link>
            <Link to="/login" className="btn btn-primary">Zaloguj się, aby wypożyczyć</Link>
          </>
        )}
      </div>

      {error && <div className="error">{error}</div>}
      {success && <div className="success">{success}</div>}
      
      {compact && (
        <button className="btn-collapse" onClick={onToggleExpand}>
          ▲ Zwiń
        </button>
      )}
    </article>
  )
}
