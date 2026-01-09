import React, { useEffect, useRef, useState } from 'react'
import toast from 'react-hot-toast'
import { apiFetch } from '../api'
import BookItem from '../components/BookItem'
import { BookSkeleton } from '../components/ui/Skeleton'
import { useResourceCache } from '../context/ResourceCacheContext'
import { useAuth } from '../context/AuthContext'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import SectionCard from '../components/ui/SectionCard'
import FeedbackCard from '../components/ui/FeedbackCard'
import Pagination from '../components/Pagination'

const initialFilters = {
  authorId: '',
  categoryId: '',
  publisher: '',
  resourceType: '',
  signature: '',
  yearFrom: '',
  yearTo: '',
  availableOnly: false,
  ageGroup: '',
}

const DEFAULT_LIMIT = 20

export default function Books() {
  const { user } = useAuth()
  const [books, setBooks] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [query, setQuery] = useState('')
  const [lastQuery, setLastQuery] = useState('')
  const [hadNonQueryFilters, setHadNonQueryFilters] = useState(false)
  const [filters, setFilters] = useState(() => ({ ...initialFilters }))
  const [facets, setFacets] = useState({
    authors: [],
    categories: [],
    publishers: [],
    resourceTypes: [],
    years: { min: null, max: null },
    ageGroups: [],
  })
  const [showAdvanced, setShowAdvanced] = useState(false)
  const [page, setPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [totalItems, setTotalItems] = useState(0)
  const [limit, setLimit] = useState(DEFAULT_LIMIT)
  const filtersRef = useRef(filters)
  const lastCacheKeyRef = useRef(null)
  const { getCachedResource, setCachedResource, invalidateResource } = useResourceCache()
  const LIST_CACHE_TTL = 60000
  const FACETS_CACHE_TTL = 300000

  useEffect(() => {
    filtersRef.current = filters
  }, [filters])

  async function load(searchTerm, providedFilters = filtersRef.current, options = {}) {
    const rawTerm = typeof searchTerm === 'string' ? searchTerm : query
    const finalTerm = rawTerm.trim()
    const activeFilters = providedFilters ?? filters
    const targetPage = options.page ?? page
    const targetLimit = options.limit ?? limit
    const params = new URLSearchParams()

    params.set('page', String(targetPage))
    params.set('limit', String(targetLimit))

    if (finalTerm) {
      params.set('q', finalTerm)
    }

    if (activeFilters.authorId) {
      params.set('authorId', activeFilters.authorId)
    }

    if (activeFilters.categoryId) {
      params.set('categoryId', activeFilters.categoryId)
    }

    if (activeFilters.publisher && activeFilters.publisher.trim() !== '') {
      params.set('publisher', activeFilters.publisher.trim())
    }

    if (activeFilters.resourceType) {
      params.set('resourceType', activeFilters.resourceType)
    }

    if (activeFilters.signature && activeFilters.signature.trim() !== '') {
      params.set('signature', activeFilters.signature.trim())
    }

    if (activeFilters.ageGroup) {
      params.set('ageGroup', activeFilters.ageGroup)
    }

    if (activeFilters.yearFrom && `${activeFilters.yearFrom}`.trim() !== '') {
      params.set('yearFrom', `${activeFilters.yearFrom}`.trim())
    }

    if (activeFilters.yearTo && `${activeFilters.yearTo}`.trim() !== '') {
      params.set('yearTo', `${activeFilters.yearTo}`.trim())
    }

    if (activeFilters.availableOnly) {
      params.set('available', 'true')
    }

    const endpoint = `/api/books?${params.toString()}`
    const cacheKey = `books:${endpoint}`
    lastCacheKeyRef.current = cacheKey
    const hasNonQueryFilters = Boolean(
      (activeFilters.authorId && activeFilters.authorId !== '') ||
      (activeFilters.categoryId && activeFilters.categoryId !== '') ||
      (activeFilters.publisher && activeFilters.publisher.trim() !== '') ||
      (activeFilters.resourceType && activeFilters.resourceType !== '') ||
      (activeFilters.signature && activeFilters.signature.trim() !== '') ||
      (activeFilters.ageGroup && activeFilters.ageGroup !== '') ||
      (activeFilters.yearFrom && activeFilters.yearFrom !== '') ||
      (activeFilters.yearTo && activeFilters.yearTo !== '') ||
      activeFilters.availableOnly
    )
    const forceReload = Boolean(options.force)

    if (!forceReload) {
      const cached = getCachedResource(cacheKey, LIST_CACHE_TTL)
      if (cached) {
        const cachedItems = Array.isArray(cached)
          ? cached
          : Array.isArray(cached.items)
            ? cached.items
            : []
        const cachedMeta = Array.isArray(cached) ? null : cached.meta
        setBooks(cachedItems)
        setLoading(false)
        setError(null)
        setLastQuery(finalTerm)
        setHadNonQueryFilters(hasNonQueryFilters)
        if (cachedMeta) {
          setPage(cachedMeta.page ?? targetPage)
          setTotalPages(cachedMeta.totalPages ?? 1)
          setTotalItems(cachedMeta.total ?? cachedItems.length)
          setLimit(cachedMeta.limit ?? targetLimit)
        } else {
          setPage(targetPage)
          setTotalPages(1)
          setTotalItems(cachedItems.length)
        }
        return
      }
    }

    setLoading(true)
    setError(null)
    setHadNonQueryFilters(hasNonQueryFilters)
    setLastQuery(finalTerm)
    try {
      const data = await apiFetch(endpoint)
      const list = Array.isArray(data?.data)
        ? data.data
        : Array.isArray(data)
          ? data
          : []
      const meta = data?.meta || {}
      setBooks(list)
      setPage(meta.page ?? targetPage)
      setTotalPages(meta.totalPages ?? 1)
      setTotalItems(meta.total ?? list.length)
      setLimit(meta.limit ?? targetLimit)
      setCachedResource(cacheKey, { items: list, meta })
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać listy książek.')
    } finally {
      setLoading(false)
    }
  }

  async function loadFacets() {
    const cacheKey = 'books:filters'
    const cached = getCachedResource(cacheKey, FACETS_CACHE_TTL)
    if (cached) {
      setFacets(cached)
      return
    }

    try {
      const data = await apiFetch('/api/books/filters')
      const normalized = {
        authors: Array.isArray(data?.authors) ? data.authors : [],
        categories: Array.isArray(data?.categories) ? data.categories : [],
        publishers: Array.isArray(data?.publishers) ? data.publishers : [],
        resourceTypes: Array.isArray(data?.resourceTypes) ? data.resourceTypes : [],
        years: data?.years ?? { min: null, max: null },
        ageGroups: Array.isArray(data?.ageGroups) ? data.ageGroups : [],
      }
      setFacets(normalized)
      setCachedResource(cacheKey, normalized)
    } catch (err) {
      console.warn('Nie udało się pobrać metadanych filtrów książek:', err)
    }
  }

  useEffect(() => {
    load('', { ...initialFilters }, { page: 1 })
    loadFacets()
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function onBorrowed(borrowedBook) {
    setBooks(prev => {
      const next = prev.map(b => {
        if (b.id === borrowedBook.id) {
          return {
            ...b,
            copies: Math.max(0, (b.copies ?? 0) - 1)
          }
        }
        return b
      })
      if (lastCacheKeyRef.current) {
        setCachedResource(lastCacheKeyRef.current, { items: next, meta: { page, totalPages, total: totalItems, limit } })
      }
      return next
    })
  }

  function handleSearch(event) {
    event.preventDefault()
    setPage(1)
    load(query, filtersRef.current, { page: 1 })
  }

  function handleClear() {
    setQuery('')
    const resetFilters = { ...initialFilters }
    setFilters(resetFilters)
    filtersRef.current = resetFilters
    invalidateResource('books:/api/books*')
    setPage(1)
    load('', resetFilters, { force: true, page: 1 })
  }

  function handleFilterChange(event) {
    const { name, value, type, checked } = event.target
    const nextValue = type === 'checkbox' ? checked : value
    setFilters(prev => {
      const next = { ...prev, [name]: nextValue }
      filtersRef.current = next
      return next
    })
  }

  function handlePageChange(nextPage) {
    if (nextPage === page || nextPage < 1 || nextPage > totalPages) return
    setPage(nextPage)
    load(query, filtersRef.current, { page: nextPage })
  }

  const filtersDirty = Boolean(
    (filters.authorId && filters.authorId !== '') ||
    (filters.categoryId && filters.categoryId !== '') ||
    (filters.publisher && filters.publisher.trim() !== '') ||
    (filters.resourceType && filters.resourceType !== '') ||
    (filters.signature && filters.signature.trim() !== '') ||
    (filters.ageGroup && filters.ageGroup !== '') ||
    (filters.yearFrom && filters.yearFrom !== '') ||
    (filters.yearTo && filters.yearTo !== '') ||
    filters.availableOnly
  )

  const canClear = query.trim() !== '' || filtersDirty
  const activeFilterCount = [
    filters.authorId,
    filters.categoryId,
    filters.publisher?.trim(),
    filters.resourceType,
    filters.signature?.trim(),
    filters.ageGroup,
    filters.yearFrom,
    filters.yearTo,
    filters.availableOnly ? 'available' : ''
  ].filter(Boolean).length
  const resultCount = totalItems || books.length

  const isLibrarianOrAdmin = user && (user.roles?.includes('ROLE_LIBRARIAN') || user.roles?.includes('ROLE_ADMIN'))

  async function handleExportCSV() {
    try {
      const token = localStorage.getItem('token')
      const response = await fetch('http://localhost:8000/api/books/export', {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })

      if (!response.ok) {
        throw new Error('Eksport nie powiódł się')
      }

      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `books_export_${new Date().toISOString().split('T')[0]}.csv`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      window.URL.revokeObjectURL(url)
      
      toast.success('Eksport zakończony pomyślnie!')
    } catch (err) {
      toast.error('Nie udało się wyeksportować książek')
    }
  }

  return (
    <div className="page">
      <PageHeader
        title="Książki"
        subtitle="Przeglądaj katalog i błyskawicznie wypożycz dostępne egzemplarze."
        actions={(
          <div style={{ display: 'flex', gap: '8px' }}>
            {isLibrarianOrAdmin && (
              <button className="btn btn-outline" onClick={handleExportCSV}>
                Eksportuj CSV
              </button>
            )}
            <button className="btn btn-outline" onClick={() => load(undefined, undefined, { force: true, page: 1 })} disabled={loading}>
              {loading ? 'Odświeżanie...' : 'Odśwież listę'}
            </button>
          </div>
        )}
      />

      <StatGrid>
        <StatCard title="Wyniki" value={loading ? '-' : resultCount} subtitle="Łącznie pozycji" />
        <StatCard title="Filtry" value={activeFilterCount} subtitle="Aktywne kryteria" />
        <StatCard title="Strona" value={`${page} / ${totalPages}`} subtitle={`Limit ${limit}`} />
        <StatCard title="Fraza" value={lastQuery ? `"${lastQuery}"` : '-'} subtitle="Ostatnie wyszukiwanie" />
      </StatGrid>

      <form className="book-search" onSubmit={handleSearch}>
        <div className="book-search__row">
          <div className="book-search__field">
            <input
              type="search"
              name="q"
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
            {canClear && (
              <button type="button" className="btn btn-outline" onClick={handleClear} disabled={loading}>
                Wyczyść
              </button>
            )}
            <button
              type="button"
              className="btn btn-ghost"
              onClick={() => setShowAdvanced(prev => !prev)}
            >
              {showAdvanced ? 'Ukryj zaawansowane' : 'Pokaż zaawansowane filtry'}
            </button>
          </div>
        </div>

        {showAdvanced && (
          <div className="book-search__advanced" role="group" aria-label="Filtry zaawansowane">
            <div className="book-search__group">
              <label htmlFor="filter-author">Autor</label>
              <select
                id="filter-author"
                name="authorId"
                value={filters.authorId}
                onChange={handleFilterChange}
              >
                <option value="">Dowolny</option>
                {facets.authors.map(author => (
                  <option key={author.id} value={author.id}>{author.name}</option>
                ))}
              </select>
            </div>

            <div className="book-search__group">
              <label htmlFor="filter-category">Kategoria</label>
              <select
                id="filter-category"
                name="categoryId"
                value={filters.categoryId}
                onChange={handleFilterChange}
              >
                <option value="">Dowolna</option>
                {facets.categories.map(category => (
                  <option key={category.id} value={category.id}>{category.name}</option>
                ))}
              </select>
            </div>

            <div className="book-search__group">
              <label htmlFor="filter-publisher">Wydawca</label>
              <input
                id="filter-publisher"
                name="publisher"
                type="text"
                value={filters.publisher}
                onChange={handleFilterChange}
                placeholder="np. Wydawnictwo"
              />
            </div>

            <div className="book-search__group">
              <label htmlFor="filter-resourceType">Typ zasobu</label>
              <select
                id="filter-resourceType"
                name="resourceType"
                value={filters.resourceType}
                onChange={handleFilterChange}
              >
                <option value="">Dowolny</option>
                {facets.resourceTypes.map(type => (
                  <option key={type} value={type}>{type}</option>
                ))}
              </select>
            </div>

            <div className="book-search__group">
              <label htmlFor="filter-ageGroup">Przedział wiekowy</label>
              <select
                id="filter-ageGroup"
                name="ageGroup"
                value={filters.ageGroup}
                onChange={handleFilterChange}
              >
                <option value="">Dowolny</option>
                {facets.ageGroups.map(group => (
                  <option key={group.value} value={group.value}>{group.label}</option>
                ))}
              </select>
            </div>

            <div className="book-search__group book-search__group--range">
              <label>Rok wydania</label>
              <div className="book-search__range">
                <input
                  name="yearFrom"
                  type="number"
                  min={facets.years.min ?? undefined}
                  max={facets.years.max ?? undefined}
                  value={filters.yearFrom}
                  onChange={handleFilterChange}
                  placeholder="od"
                />
                <span>–</span>
                <input
                  name="yearTo"
                  type="number"
                  min={facets.years.min ?? undefined}
                  max={facets.years.max ?? undefined}
                  value={filters.yearTo}
                  onChange={handleFilterChange}
                  placeholder="do"
                />
              </div>
            </div>

            <div className="book-search__group">
              <label htmlFor="filter-signature">Sygnatura</label>
              <input
                id="filter-signature"
                name="signature"
                type="text"
                value={filters.signature}
                onChange={handleFilterChange}
                placeholder="np. SIG-001"
              />
            </div>

            <div className="book-search__group book-search__group--checkbox">
              <label className="checkbox">
                <input
                  type="checkbox"
                  name="availableOnly"
                  checked={filters.availableOnly}
                  onChange={handleFilterChange}
                />
                <span>Tylko dostępne egzemplarze</span>
              </label>
            </div>
          </div>
        )}
      </form>

      {loading && (
        <div className="books-loading">
          <BookSkeleton />
          <BookSkeleton />
          <BookSkeleton />
          <BookSkeleton />
          <BookSkeleton />
        </div>
      )}

      {!loading && error && <FeedbackCard variant="error">{error}</FeedbackCard>}

      {!loading && !error && books.length === 0 && (
        <SectionCard className="empty-state">
          {lastQuery
            ? `Brak wyników dla frazy „${lastQuery}”.`
            : hadNonQueryFilters
              ? 'Brak książek spełniających wybrane filtry.'
              : 'Brak książek w katalogu.'}
        </SectionCard>
      )}

      {!loading && !error && books.length > 0 && (
        <>
          <div className="books-grid">
            {books.map(book => (
              <BookItem key={book.id} book={book} onBorrowed={onBorrowed} />
            ))}
          </div>
          <Pagination currentPage={page} totalPages={totalPages} onPageChange={handlePageChange} />
        </>
      )}
    </div>
  )
}
