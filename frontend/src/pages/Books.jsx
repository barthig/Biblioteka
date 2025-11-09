import React, { useEffect, useState } from 'react'
import { apiFetch } from '../api'
import BookItem from '../components/BookItem'

export default function Books() {
  const [books, setBooks] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  async function load() {
    setLoading(true)
    setError(null)
    try {
      const data = await apiFetch('/api/books')
      setBooks(data || [])
    } catch (err) {
      if (err.status === 401) {
        setError('Zaloguj się, aby zobaczyć listę książek.')
      } else {
        setError(err.message || 'Nie udało się pobrać listy książek.')
      }
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    load()
  }, [])

  function onBorrowed(borrowedBook) {
    setBooks(prev => prev.filter(b => b.id !== borrowedBook.id))
  }

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Książki</h1>
          <p className="support-copy">Przeglądaj katalog i błyskawicznie wypożycz dostępne egzemplarze.</p>
        </div>
        <button className="btn btn-outline" onClick={load} disabled={loading}>
          {loading ? 'Odświeżanie...' : 'Odśwież listę'}
        </button>
      </header>

      {loading && (
        <div className="surface-card empty-state">Trwa ładowanie książek...</div>
      )}

      {!loading && error && (
        <div className="surface-card">
          <p className="error">{error}</p>
        </div>
      )}

      {!loading && !error && books.length === 0 && (
        <div className="surface-card empty-state">Brak dopasowanych książek.</div>
      )}

      {!loading && !error && books.length > 0 && (
        <div className="books-grid">
          {books.map(book => (
            <BookItem key={book.id} book={book} onBorrowed={onBorrowed} />
          ))}
        </div>
      )}
    </div>
  )
}
