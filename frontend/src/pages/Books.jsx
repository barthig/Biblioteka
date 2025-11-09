import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
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
        setError(err.message || 'Nie udało się pobrać listy książek')
      }
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { load() }, [])

  function onBorrowed(borrowedBook) {
    // Optionally mark book as borrowed locally
    setBooks(prev => prev.filter(b => b.id !== borrowedBook.id))
  }

  if (loading) return <div>Loading books...</div>
  if (error) return <div className="error">Error: {error}</div>

  return (
    <div>
      <h2>Books</h2>
      {books.length === 0 ? (
        <div>No books found.</div>
      ) : (
        <div className="books-list">
          {books.map(b => (
            <div key={b.id} className="book-row">
              <Link to={`/books/${b.id}`} className="book-link">{b.title}</Link>
              <BookItem book={b} onBorrowed={onBorrowed} />
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
