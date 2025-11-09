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
    return () => {
      mounted = false
    }
  }, [id])

  if (loading) {
    return (
      <div className="page">
        <div className="surface-card empty-state">Ładuję szczegóły książki...</div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="page">
        <div className="surface-card">
          <p className="error">Nie udało się pobrać danych książki: {error}</p>
        </div>
      </div>
    )
  }

  if (!book) {
    return (
      <div className="page">
        <div className="surface-card empty-state">Nie znaleziono książki.</div>
      </div>
    )
  }

  const categories = Array.isArray(book.categories) && book.categories.length
    ? book.categories.map(c => c.name).join(', ')
    : '—'

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>{book.title}</h1>
          <p className="support-copy">Poznaj szczegóły wybranego tytułu, aby zdecydować o wypożyczeniu lub rezerwacji.</p>
        </div>
      </header>

      <article className="surface-card">
        <dl>
          <div className="resource-item__meta">
            <dt>Autor</dt>
            <dd>{book.author?.name ?? 'Autor nieznany'}</dd>
          </div>
          <div className="resource-item__meta">
            <dt>Kategorie</dt>
            <dd>{categories}</dd>
          </div>
          <div className="resource-item__meta">
            <dt>Opis</dt>
            <dd>{book.description ?? 'Brak opisu.'}</dd>
          </div>
          <div className="resource-item__meta">
            <dt>Dostępne egzemplarze</dt>
            <dd>{book.copies ?? 0} / {book.totalCopies ?? book.copies ?? 0}</dd>
          </div>
        </dl>
      </article>
    </div>
  )
}
