import React, { useState, useEffect, useRef } from 'react'
import { useNavigate } from 'react-router-dom'
import { FaSearch, FaTimes } from 'react-icons/fa'
import { bookService } from '../../services/bookService'
import { logger } from '../../utils/logger'

export default function SearchBar({ placeholder = 'Szukaj książek...', onResults, onSearch }) {
  const [query, setQuery] = useState('')
  const [suggestions, setSuggestions] = useState([])
  const [loading, setLoading] = useState(false)
  const [showSuggestions, setShowSuggestions] = useState(false)
  const [resultsCount, setResultsCount] = useState(null)
  const navigate = useNavigate()
  const wrapperRef = useRef(null)
  const timeoutRef = useRef(null)

  useEffect(() => {
    function handleClickOutside(event) {
      if (wrapperRef.current && !wrapperRef.current.contains(event.target)) {
        setShowSuggestions(false)
      }
    }

    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  useEffect(() => {
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current)
    }

    const trimmedQuery = query.trim()

    if (trimmedQuery.length < 2) {
      setSuggestions([])
      setShowSuggestions(false)
      setResultsCount(null)
      if (onResults) {
        onResults([])
      }
      return
    }

    timeoutRef.current = setTimeout(async () => {
      setLoading(true)
      setShowSuggestions(true)
      setSuggestions([])
      try {
        const results = await bookService.search(trimmedQuery)
        const items = Array.isArray(results)
          ? results
          : Array.isArray(results?.items)
            ? results.items
            : []

        setSuggestions(items.slice(0, 5))
        setShowSuggestions(items.length > 0)

        if (onResults) {
          const total = typeof results?.total === 'number' ? results.total : items.length
          setResultsCount(total)
          onResults(items, total)
        }
      } catch (error) {
        logger.error('Search error:', error)
        setSuggestions([])
        setResultsCount(null)
        if (onResults) {
          onResults([])
        }
        setShowSuggestions(false)
      } finally {
        setLoading(false)
      }
    }, 300)

    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current)
      }
    }
  }, [onResults, query])

  function handleSubmit(e) {
    e.preventDefault()
    const trimmedQuery = query.trim()
    setShowSuggestions(false)

    if (!trimmedQuery) {
      return
    }

    if (onSearch) {
      onSearch(trimmedQuery)
    } else {
      navigate(`/books?search=${encodeURIComponent(trimmedQuery)}`)
    }
  }

  function handleClear() {
    setQuery('')
    setSuggestions([])
    setShowSuggestions(false)
    setResultsCount(null)
    if (onResults) {
      onResults([])
    }
  }

  function handleSuggestionClick(book) {
    setShowSuggestions(false)
    setQuery('')
    navigate(`/books/${book.id}`)
  }

  return (
    <div className="search-bar" ref={wrapperRef}>
      <form onSubmit={handleSubmit} className="search-form">
        <div className="search-input-wrapper">
          <FaSearch className="search-icon" />
          <input
            type="text"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder={placeholder}
            className="search-input"
          />
          {query && (
            <button type="button" onClick={handleClear} className="search-clear">
              <FaTimes />
            </button>
          )}
        </div>
        <button type="submit" className="btn btn-primary search-button">
          Szukaj
        </button>
      </form>

      {showSuggestions && suggestions.length > 0 && (
        <div className="search-suggestions">
          {suggestions.map(book => (
            <div
              key={book.id}
              className="search-suggestion-item"
              onClick={() => handleSuggestionClick(book)}
            >
              <div className="suggestion-title">{book.title}</div>
              <div className="suggestion-author">{book.author}</div>
            </div>
          ))}
        </div>
      )}

      {loading && showSuggestions && (
        <div className="search-suggestions">
          <div className="search-suggestion-item">Wyszukiwanie...</div>
        </div>
      )}

      {typeof resultsCount === 'number' && query.trim().length >= 2 && (
        <div className="search-results-count">Wyniki: {resultsCount}</div>
      )}
    </div>
  )
}
