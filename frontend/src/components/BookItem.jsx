import React, { useState } from 'react'
import { Link } from 'react-router-dom'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'

export default function BookItem({ book, onBorrowed }) {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const { user } = useAuth()
  const isLoggedIn = Boolean(user?.id)

  const available = book?.copies ?? 0
  const total = book?.totalCopies ?? available
  const isAvailable = available > 0

  async function borrow() {
    if (!user?.id) {
      setError('Musisz być zalogowany, aby wypożyczyć książkę.')
      return
    }
    setLoading(true)
    setError(null)
    try {
      await apiFetch('/api/loans', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ bookId: book.id, userId: user.id })
      })
      onBorrowed && onBorrowed(book)
    } catch (err) {
      setError(err.message || 'Nie udało się zarejestrować wypożyczenia.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <article className="surface-card book-card">
      <div className="book-card__header">
        <div>
          <Link to={`/books/${book.id}`} className="book-card__title">{book.title}</Link>
          <div className="book-card__meta">
            <span>{book.author?.name ?? 'Autor nieznany'}</span>
            {book.isbn && <span>ISBN {book.isbn}</span>}
          </div>
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
    </article>
  )
}
