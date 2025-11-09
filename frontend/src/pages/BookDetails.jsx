import React, { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { apiFetch } from '../api'

export default function BookDetails() {
  const { id } = useParams()
  const [book, setBook] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    let mounted = true
    async function load() {
      setLoading(true)
      try {
        const data = await apiFetch(`/api/books/${id}`)
        if (mounted) setBook(data)
      } catch (err) {
        if (mounted) setError(err.message)
      } finally {
        if (mounted) setLoading(false)
      }
    }
    load()
    return () => (mounted = false)
  }, [id])

  if (loading) return <div>Loading...</div>
  if (error) return <div className="error">{error}</div>
  if (!book) return <div>No book found</div>

  return (
    <div>
      <h2>{book.title}</h2>
      <p><strong>Author:</strong> {book.author ?? 'Unknown'}</p>
      <p><strong>Description:</strong> {book.description ?? '—'}</p>
      <p><strong>Available copies:</strong> {book.available ?? '—'}</p>
    </div>
  )
}
