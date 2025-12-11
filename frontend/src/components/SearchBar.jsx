import React, { useState, useEffect, useRef } from 'react'
import { useNavigate } from 'react-router-dom'
import { FaSearch, FaTimes } from 'react-icons/fa'
import { bookService } from '../services/bookService'

export default function SearchBar({ placeholder = 'Szukaj książek...', onSearch }) {
  const [query, setQuery] = useState('')
  const [suggestions, setSuggestions] = useState([])
  const [loading, setLoading] = useState(false)
  const [showSuggestions, setShowSuggestions] = useState(false)
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

    if (query.length < 2) {
      setSuggestions([])
      setShowSuggestions(false)
      return
    }

    timeoutRef.current = setTimeout(async () => {
      setLoading(true)
      try {
        const results = await bookService.search(query)
        setSuggestions(results.slice(0, 5))
        setShowSuggestions(true)
      } catch (error) {
        console.error('Search error:', error)
        setSuggestions([])
      } finally {
        setLoading(false)
      }
    }, 300)

    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current)
      }
    }
  }, [query])

  function handleSubmit(e) {
    e.preventDefault()
    setShowSuggestions(false)
    
    if (onSearch) {
      onSearch(query)
    } else {
      navigate(`/books?search=${encodeURIComponent(query)}`)
    }
  }

  function handleClear() {
    setQuery('')
    setSuggestions([])
    setShowSuggestions(false)
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
    </div>
  )
}
