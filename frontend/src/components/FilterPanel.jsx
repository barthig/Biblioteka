import React, { useState } from 'react'
import { FaFilter, FaTimes } from 'react-icons/fa'

export default function FilterPanel({ filters, onFilterChange, availableFilters }) {
  const [isOpen, setIsOpen] = useState(false)

  function handleFilterChange(filterKey, value) {
    onFilterChange({
      ...filters,
      [filterKey]: value
    })
  }

  function clearFilters() {
    onFilterChange({})
  }

  const activeFiltersCount = Object.keys(filters).filter(key => filters[key]).length

  return (
    <div className="filter-panel">
      <button 
        className="btn btn-secondary filter-toggle"
        onClick={() => setIsOpen(!isOpen)}
      >
        <FaFilter /> Filtry
        {activeFiltersCount > 0 && (
          <span className="filter-badge">{activeFiltersCount}</span>
        )}
      </button>

      {isOpen && (
        <div className="filter-dropdown">
          <div className="filter-header">
            <h3>Filtry</h3>
            <button className="btn btn-link" onClick={() => setIsOpen(false)}>
              <FaTimes />
            </button>
          </div>

          <div className="filter-content">
            {availableFilters?.genres && (
              <div className="filter-group">
                <label className="filter-label">Gatunek</label>
                <select
                  value={filters.genre || ''}
                  onChange={(e) => handleFilterChange('genre', e.target.value)}
                  className="filter-select"
                >
                  <option value="">Wszystkie</option>
                  {availableFilters.genres.map(genre => (
                    <option key={genre} value={genre}>{genre}</option>
                  ))}
                </select>
              </div>
            )}

            {availableFilters?.authors && (
              <div className="filter-group">
                <label className="filter-label">Autor</label>
                <select
                  value={filters.author || ''}
                  onChange={(e) => handleFilterChange('author', e.target.value)}
                  className="filter-select"
                >
                  <option value="">Wszyscy</option>
                  {availableFilters.authors.map(author => (
                    <option key={author} value={author}>{author}</option>
                  ))}
                </select>
              </div>
            )}

            {availableFilters?.years && (
              <div className="filter-group">
                <label className="filter-label">Rok wydania</label>
                <select
                  value={filters.year || ''}
                  onChange={(e) => handleFilterChange('year', e.target.value)}
                  className="filter-select"
                >
                  <option value="">Wszystkie</option>
                  {availableFilters.years.map(year => (
                    <option key={year} value={year}>{year}</option>
                  ))}
                </select>
              </div>
            )}

            <div className="filter-group">
              <label className="filter-label">
                <input
                  type="checkbox"
                  checked={filters.availableOnly || false}
                  onChange={(e) => handleFilterChange('availableOnly', e.target.checked)}
                />
                {' '}Tylko dostępne
              </label>
            </div>
          </div>

          <div className="filter-footer">
            <button className="btn btn-secondary" onClick={clearFilters}>
              Wyczyść filtry
            </button>
            <button className="btn btn-primary" onClick={() => setIsOpen(false)}>
              Zastosuj
            </button>
          </div>
        </div>
      )}
    </div>
  )
}
