import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'

export default function BookItem({ book, onBorrowed }) {
  const [loading, setLoading] = useState(false)
  const [reserveLoading, setReserveLoading] = useState(false)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)
  const [reserved, setReserved] = useState(false)
  const [favorite, setFavorite] = useState(Boolean(book?.isFavorite))
  const [favoriteLoading, setFavoriteLoading] = useState(false)
  const { user } = useAuth()
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
        setSuccess('Usunięto książkę z ulubionych.')
      } else {
        await apiFetch('/api/favorites', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ bookId: book.id })
        })
        setFavorite(true)
        setSuccess('Dodano książkę do ulubionych.')
      }
    } catch (err) {
      setError(err.message || 'Nie udało się zaktualizować ulubionych.')
    } finally {
      setFavoriteLoading(false)
    }
  }

  return (
    <article className="surface-card book-card">
      <div className="book-card__header">
        <div>
          <Link to={`/books/${book.id}`} className="book-card__title">{book.title}</Link>
          <div className="book-card__meta">
            <span>{book.author?.name ?? 'Autor nieznany'}</span>
            {publicationYear && <span>Rok wydania {publicationYear}</span>}
            {resourceType && <span>{resourceType}</span>}
            {book.isbn && <span>ISBN {book.isbn}</span>}
            {ageGroupLabel && <span>Wiek: {ageGroupLabel}</span>}
          </div>
          {(publisher || signature) && (
            <div className="book-card__meta">
              {publisher && <span>Wydawca: {publisher}</span>}
              {signature && <span>Sygnatura: {signature}</span>}
            </div>
          )}
          {(storageAvailable !== null || openStackAvailable !== null) && (
            <div className="book-card__meta">
              <span>Magazyn: {storageAvailable}</span>
              <span>Wolny dostęp: {openStackAvailable}</span>
            </div>
          )}
        </div>
        <span className={`status-pill ${isAvailable ? '' : 'is-danger'}`}>
          {isAvailable ? `Dostępne ${available}/${total}` : 'Brak wolnych egzemplarzy'}
        </span>
      </div>

      {book.description && (
        <p className="support-copy">{book.description}</p>
      )}

      <div className="resource-item__actions">
        {isLoggedIn ? (
          <>
            <button
              className="btn btn-primary"
              disabled={!isAvailable || loading}
              onClick={borrow}
            >
              {loading ? 'Wysyłanie...' : 'Wypożycz egzemplarz'}
            </button>
            {!isAvailable && (
              <button
                className="btn btn-outline"
                disabled={reserveLoading || reserved}
                onClick={reserve}
              >
                {reserveLoading ? 'Przetwarzanie...' : reserved ? 'Zarezerwowano' : 'Dołącz do kolejki'}
              </button>
            )}
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
    </article>
  )
}
