import React, { useState } from 'react'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'

export default function BookItem({ book, onBorrowed }) {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const { user } = useAuth()

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
      setError(err.message || 'Failed')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="book-item">
      <div>
        <strong>{book.title}</strong>
        <div className="meta">{book.author?.name ?? 'Unknown author'}</div>
        <div className="meta">Dostępne: {book.copies ?? 0} / {book.totalCopies ?? book.copies ?? 0}</div>
      </div>
      <div>
        <button disabled={loading} onClick={borrow}>{loading ? '...' : 'Borrow'}</button>
        {error && <div className="error">{error}</div>}
      </div>
    </div>
  )
}
