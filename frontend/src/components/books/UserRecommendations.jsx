import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { apiFetch } from '../api'
import BookCover from './BookCover'

export default function UserRecommendations() {
  const [books, setBooks] = useState([])
  const [status, setStatus] = useState('ok')
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true

    async function loadRecommendations() {
      setLoading(true)
      setError('')
      try {
        const response = await apiFetch('/api/recommendations/personal')
        if (!active) return

        setStatus(response?.status || 'ok')
        setBooks(Array.isArray(response?.data) ? response.data : [])
      } catch (err) {
        if (active) {
          setError(err?.message || 'Nie udalo sie pobrac rekomendacji.')
        }
      } finally {
        if (active) {
          setLoading(false)
        }
      }
    }

    loadRecommendations()

    return () => {
      active = false
    }
  }, [])

  return (
    <section className="user-recommendations">
      {loading && <div className="surface-card empty-state">Laduje rekomendacje...</div>}

      {!loading && error && (
        <div className="surface-card">
          <p className="error">{error}</p>
        </div>
      )}

      {!loading && !error && status === 'not_enough_data' && (
        <div className="surface-card recommendations-note">
          Rate a few books to get AI recommendations!
        </div>
      )}

      {!loading && !error && books.length === 0 && status !== 'not_enough_data' && (
        <div className="surface-card empty-state">Brak rekomendacji na teraz.</div>
      )}

      {!loading && !error && books.length > 0 && (
        <div className="books-grid">
          {books.map((book) => (
            <article key={book.id} className="surface-card book-cover-card">
              <Link to={`/books/${book.id}`} className="book-cover-card__image">
                <BookCover src={book.coverUrl} title={book.title} />
              </Link>
              <div className="book-cover-card__body">
                <h3 className="book-cover-card__title">{book.title}</h3>
                <p className="book-cover-card__author">{book.author?.name || 'Autor nieznany'}</p>
              </div>
            </article>
          ))}
        </div>
      )}
    </section>
  )
}
