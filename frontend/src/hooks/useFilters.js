import { useState, useCallback, useMemo } from 'react'

/**
 * useFilters - Hook for managing filter state
 * 
 * @example
 * const { filters, setFilter, clearFilter, clearAll, hasActiveFilters } = useFilters({
 *   status: '',
 *   category: '',
 *   search: ''
 * });
 */
export function useFilters(initialFilters = {}, options = {}) {
  const { onChange, persistKey } = options

  // Try to load from localStorage if persistKey is provided
  const getInitialState = () => {
    if (persistKey) {
      try {
        const stored = localStorage.getItem(persistKey)
        if (stored) {
          return { ...initialFilters, ...JSON.parse(stored) }
        }
      } catch (e) {
        // eslint-disable-next-line no-console
        console.warn('Failed to load filters from storage:', e)
      }
    }
    return initialFilters
  }

  const [filters, setFilters] = useState(getInitialState)

  // Persist to localStorage when filters change
  const persistFilters = useCallback((newFilters) => {
    if (persistKey) {
      try {
        localStorage.setItem(persistKey, JSON.stringify(newFilters))
      } catch (e) {
        // eslint-disable-next-line no-console
        console.warn('Failed to persist filters:', e)
      }
    }
  }, [persistKey])

  const setFilter = useCallback((key, value) => {
    setFilters(prev => {
      const newFilters = { ...prev, [key]: value }
      persistFilters(newFilters)
      onChange?.(newFilters)
      return newFilters
    })
  }, [onChange, persistFilters])

  const setMultipleFilters = useCallback((updates) => {
    setFilters(prev => {
      const newFilters = { ...prev, ...updates }
      persistFilters(newFilters)
      onChange?.(newFilters)
      return newFilters
    })
  }, [onChange, persistFilters])

  const clearFilter = useCallback((key) => {
    setFilters(prev => {
      const newFilters = { ...prev, [key]: initialFilters[key] ?? '' }
      persistFilters(newFilters)
      onChange?.(newFilters)
      return newFilters
    })
  }, [initialFilters, onChange, persistFilters])

  const clearAll = useCallback(() => {
    setFilters(initialFilters)
    persistFilters(initialFilters)
    onChange?.(initialFilters)
  }, [initialFilters, onChange, persistFilters])

  const resetToDefaults = useCallback(() => {
    if (persistKey) {
      localStorage.removeItem(persistKey)
    }
    setFilters(initialFilters)
    onChange?.(initialFilters)
  }, [initialFilters, persistKey, onChange])

  // Check if any filter has a non-default value
  const hasActiveFilters = useMemo(() => {
    return Object.keys(filters).some(key => {
      const currentValue = filters[key]
      const initialValue = initialFilters[key]
      
      if (Array.isArray(currentValue)) {
        return currentValue.length > 0
      }
      
      return currentValue !== initialValue && currentValue !== '' && currentValue !== null
    })
  }, [filters, initialFilters])

  // Get count of active filters
  const activeFilterCount = useMemo(() => {
    return Object.keys(filters).filter(key => {
      const currentValue = filters[key]
      const initialValue = initialFilters[key]
      
      if (Array.isArray(currentValue)) {
        return currentValue.length > 0
      }
      
      return currentValue !== initialValue && currentValue !== '' && currentValue !== null
    }).length
  }, [filters, initialFilters])

  // Build query string from filters
  const toQueryString = useMemo(() => {
    const params = new URLSearchParams()
    
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== '' && value !== null && value !== undefined) {
        if (Array.isArray(value)) {
          value.forEach(v => params.append(key, v))
        } else {
          params.set(key, String(value))
        }
      }
    })
    
    return params.toString()
  }, [filters])

  return {
    filters,
    setFilter,
    setMultipleFilters,
    clearFilter,
    clearAll,
    resetToDefaults,
    hasActiveFilters,
    activeFilterCount,
    toQueryString
  }
}

/**
 * useSorting - Hook for managing sorting state
 */
export function useSorting(initialSort = { field: '', direction: 'asc' }, options = {}) {
  const { onChange } = options

  const [sort, setSort] = useState(initialSort)

  const setSortField = useCallback((field) => {
    setSort(prev => {
      let newDirection = 'asc'
      
      // Toggle direction if same field
      if (prev.field === field) {
        newDirection = prev.direction === 'asc' ? 'desc' : 'asc'
      }
      
      const newSort = { field, direction: newDirection }
      onChange?.(newSort)
      return newSort
    })
  }, [onChange])

  const setDirection = useCallback((direction) => {
    setSort(prev => {
      const newSort = { ...prev, direction }
      onChange?.(newSort)
      return newSort
    })
  }, [onChange])

  const clearSort = useCallback(() => {
    setSort(initialSort)
    onChange?.(initialSort)
  }, [initialSort, onChange])

  return {
    sortField: sort.field,
    sortDirection: sort.direction,
    sort,
    setSortField,
    setDirection,
    clearSort
  }
}

/**
 * useFilteredData - Combines filters with local data filtering
 */
export function useFilteredData(data = [], filterFn, filters) {
  const filteredData = useMemo(() => {
    if (!filterFn) return data
    return data.filter(item => filterFn(item, filters))
  }, [data, filterFn, filters])

  return filteredData
}

export default useFilters
