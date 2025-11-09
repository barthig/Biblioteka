import React, { useState } from 'react'
import { apiFetch } from '../api'

export default function BookItem({ book, onBorrowed }) {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)

  async function borrow() {
    setLoading(true)
    setError(null)
    try {
      await apiFetch('/api/loans', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ bookId: book.id })
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
        <div className="meta">{book.author ?? 'Unknown'}</div>
      </div>
      <div>
        <button disabled={loading} onClick={borrow}>{loading ? '...' : 'Borrow'}</button>
        {error && <div className="error">{error}</div>}
      </div>
    </div>
  )
}
