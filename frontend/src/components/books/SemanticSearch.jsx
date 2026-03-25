import React, { useState } from 'react'
import { apiFetch } from '../../api'

export default function SemanticSearch() {
  const [query, setQuery] = useState('')
  const [loading, setLoading] = useState(false)
  const [results, setResults] = useState([])
  const [error, setError] = useState('')

  async function handleSearch(event) {
    event.preventDefault()
    const trimmed = query.trim()

    if (!trimmed) {
      setError('Wpisz opis lub temat, którego szukasz.')
      setResults([])
      return
    }

    setLoading(true)
    setError('')

    try {
      const response = await apiFetch('/api/recommend', {
        method: 'POST',
        noRetry: true,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query: trimmed })
      })
      setResults(Array.isArray(response?.data) ? response.data : [])
    } catch (err) {
      setError(err?.message || 'Wyszukiwanie semantyczne nie powiodBo si.')
      setResults([])
    } finally {
      setLoading(false)
    }
  }

  return (
    <section className="semantic-search">
      <form onSubmit={handleSearch}>
        <label htmlFor="semantic-query">Zapytanie AI</label>
        <input
          id="semantic-query"
          type="text"
          value={query}
          placeholder="np. książki o podróżach kosmicznych i samotności"
          onChange={event => setQuery(event.target.value)}
        />
        <button type="submit" disabled={loading}>
          {loading ? 'Szukam...' : 'Szukaj z AI'}
        </button>
      </form>

      {error ? <p className="semantic-search__error">{error}</p> : null}

      <div className="semantic-search__results">
        {loading ? <p>Aadowanie wynik�w...</p> : null}
        {!loading && results.length === 0 && !error ? (
          <p>Brak wyników. Spróbuj bardziej opisowego zapytania.</p>
        ) : null}
        {results.map(book => (
          <article key={book.id} className="semantic-search__card">
            <h3>{book.title}</h3>
            <p>{book.author?.name || 'Nieznany autor'}</p>
            <p>{book.description || 'Brak opisu.'}</p>
          </article>
        ))}
      </div>
    </section>
  )
}
