import React, { useEffect, useState } from 'react'
import { apiFetch } from '../api'
import BookItem from '../components/BookItem'

export default function Books() {
  const [books, setBooks] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [query, setQuery] = useState('')
  const [lastQuery, setLastQuery] = useState('')

  async function load(searchTerm) {
    const rawTerm = typeof searchTerm === 'string' ? searchTerm : query
    const finalTerm = rawTerm.trim()
    setLoading(true)
    setError(null)
    setLastQuery(finalTerm)
    try {
      const endpoint = finalTerm ? `/api/books?q=${encodeURIComponent(finalTerm)}` : '/api/books'
      const data = await apiFetch(endpoint)
      setBooks(data || [])
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać listy książek.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    load('')
  }, [])

  function onBorrowed(borrowedBook) {
    setBooks(prev => prev.filter(b => b.id !== borrowedBook.id))
  }

  function handleSearch(event) {
    event.preventDefault()
    load(query)
  }

  function handleClear() {
    setQuery('')
    load('')
  }

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Książki</h1>
          <p className="support-copy">Przeglądaj katalog i błyskawicznie wypożycz dostępne egzemplarze.</p>
        </div>
        <button className="btn btn-outline" onClick={() => load()} disabled={loading}>
          {loading ? 'Odświeżanie...' : 'Odśwież listę'}
        </button>
      </header>

      <form className="book-search" onSubmit={handleSearch}>
        <div className="book-search__field">
          <input
            type="search"
            placeholder="Szukaj po tytule, autorze, kategorii lub ISBN..."
            value={query}
            onChange={event => setQuery(event.target.value)}
            aria-label="Szukaj książek"
          />
        </div>
        <div className="book-search__actions">
          <button type="submit" className="btn btn-primary" disabled={loading}>
            Szukaj
          </button>
          {query && (
            <button type="button" className="btn btn-outline" onClick={handleClear} disabled={loading}>
              Wyczyść
            </button>
          )}
        </div>
      </form>

      {loading && (
        <div className="surface-card empty-state">Trwa ładowanie książek...</div>
      )}

      {!loading && error && (
        <div className="surface-card">
          <p className="error">{error}</p>
        </div>
      )}

      {!loading && !error && books.length === 0 && (
        <div className="surface-card empty-state">
          {lastQuery
            ? `Brak wyników dla frazy „${lastQuery}”.`
            : 'Brak książek w katalogu.'}
        </div>
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
