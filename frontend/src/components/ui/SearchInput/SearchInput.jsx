import React, { useState, useCallback, useRef, useEffect } from 'react'
import PropTypes from 'prop-types'
import './SearchInput.css'

/**
 * SearchInput component with debounce support
 * 
 * @example
 * <SearchInput
 *   value={searchQuery}
 *   onChange={setSearchQuery}
 *   placeholder="Szukaj książek..."
 *   debounceMs={300}
 * />
 */
export default function SearchInput({
  value = '',
  onChange,
  onSearch,
  placeholder = 'Szukaj...',
  debounceMs = 300,
  size = 'md',
  variant = 'default',
  disabled = false,
  autoFocus = false,
  showClearButton = true,
  showSearchIcon = true,
  className = '',
  inputProps = {},
  ...props
}) {
  const [localValue, setLocalValue] = useState(value)
  const debounceRef = useRef(null)
  const inputRef = useRef(null)

  // Sync external value changes
  useEffect(() => {
    setLocalValue(value)
  }, [value])

  // Cleanup debounce on unmount
  useEffect(() => {
    return () => {
      if (debounceRef.current) {
        clearTimeout(debounceRef.current)
      }
    }
  }, [])

  const handleChange = useCallback((e) => {
    const newValue = e.target.value
    setLocalValue(newValue)

    // Clear previous debounce
    if (debounceRef.current) {
      clearTimeout(debounceRef.current)
    }

    // Debounce onChange
    if (debounceMs > 0) {
      debounceRef.current = setTimeout(() => {
        onChange?.(newValue)
      }, debounceMs)
    } else {
      onChange?.(newValue)
    }
  }, [onChange, debounceMs])

  const handleClear = useCallback(() => {
    setLocalValue('')
    onChange?.('')
    inputRef.current?.focus()
  }, [onChange])

  const handleKeyDown = useCallback((e) => {
    if (e.key === 'Enter') {
      // Clear debounce and trigger immediately
      if (debounceRef.current) {
        clearTimeout(debounceRef.current)
      }
      onChange?.(localValue)
      onSearch?.(localValue)
    }
    if (e.key === 'Escape') {
      handleClear()
    }
  }, [localValue, onChange, onSearch, handleClear])

  const classes = [
    'search-input',
    `search-input--${size}`,
    `search-input--${variant}`,
    disabled && 'search-input--disabled',
    localValue && 'search-input--has-value',
    className
  ].filter(Boolean).join(' ')

  return (
    <div className={classes} {...props}>
      {showSearchIcon && (
        <span className="search-input__icon">
          <svg 
            viewBox="0 0 24 24" 
            fill="none" 
            stroke="currentColor" 
            strokeWidth="2" 
            strokeLinecap="round" 
            strokeLinejoin="round"
          >
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
        </span>
      )}
      
      <input
        ref={inputRef}
        type="text"
        value={localValue}
        onChange={handleChange}
        onKeyDown={handleKeyDown}
        placeholder={placeholder}
        disabled={disabled}
        autoFocus={autoFocus}
        className="search-input__input"
        aria-label={placeholder}
        {...inputProps}
      />

      {showClearButton && localValue && (
        <button
          type="button"
          className="search-input__clear"
          onClick={handleClear}
          disabled={disabled}
          aria-label="Wyczyść wyszukiwanie"
        >
          <svg 
            viewBox="0 0 24 24" 
            fill="none" 
            stroke="currentColor" 
            strokeWidth="2" 
            strokeLinecap="round" 
            strokeLinejoin="round"
          >
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
          </svg>
        </button>
      )}
    </div>
  )
}

SearchInput.propTypes = {
  value: PropTypes.string,
  onChange: PropTypes.func,
  onSearch: PropTypes.func,
  placeholder: PropTypes.string,
  debounceMs: PropTypes.number,
  size: PropTypes.oneOf(['sm', 'md', 'lg']),
  variant: PropTypes.oneOf(['default', 'outlined', 'filled']),
  disabled: PropTypes.bool,
  autoFocus: PropTypes.bool,
  showClearButton: PropTypes.bool,
  showSearchIcon: PropTypes.bool,
  className: PropTypes.string,
  inputProps: PropTypes.object
}

/**
 * Hook for search input state management
 */
export function useSearch(initialValue = '', debounceMs = 300) {
  const [value, setValue] = useState(initialValue)
  const [debouncedValue, setDebouncedValue] = useState(initialValue)

  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedValue(value)
    }, debounceMs)

    return () => clearTimeout(timer)
  }, [value, debounceMs])

  const clear = useCallback(() => {
    setValue('')
    setDebouncedValue('')
  }, [])

  return {
    value,
    debouncedValue,
    setValue,
    clear,
    isSearching: value !== debouncedValue
  }
}
