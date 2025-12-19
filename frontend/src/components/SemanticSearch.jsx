import React, { useState } from 'react'
import { apiFetch } from '../api'

export default function SemanticSearch() {
  const [query, setQuery] = useState('')
  const [loading, setLoading] = useState(false)
  const [results, setResults] = useState([])
  const [error, setError] = useState('')

  async function handleSearch(event) {
    event.preventDefault()
    const trimmed = query.trim()
    if (!trimmed) {
      setError('Please enter a search query.')
      setResults([])
      return
    }

    setLoading(true)
    setError('')
    try {
      const response = await apiFetch('/api/recommend', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query: trimmed })
      })
      setResults(Array.isArray(response?.data) ? response.data : [])
    } catch (err) {
      setError(err?.message || 'Search failed.')
      setResults([])
    } finally {
      setLoading(false)
    }
  }

  return (
    <section className="semantic-search">
      <form onSubmit={handleSearch}>
        <label htmlFor="semantic-query">Search prompt</label>
        <input
          id="semantic-query"
          type="text"
          value={query}
          placeholder="books about space travel"
          onChange={(event) => setQuery(event.target.value)}
        />
        <button type="submit" disabled={loading}>
          {loading ? 'Searching...' : 'Search'}
        </button>
      </form>

      {error ? <p className="semantic-search__error">{error}</p> : null}

      <div className="semantic-search__results">
        {loading ? <p>Loading results...</p> : null}
        {!loading && results.length === 0 && !error ? (
          <p>No results yet. Try a search.</p>
        ) : null}
        {results.map((book) => (
          <article key={book.id} className="semantic-search__card">
            <h3>{book.title}</h3>
            <p>{book.author?.name || 'Unknown author'}</p>
            <p>{book.description || 'No description available.'}</p>
          </article>
        ))}
      </div>
    </section>
  )
}
