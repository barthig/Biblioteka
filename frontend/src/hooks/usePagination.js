import { useState, useCallback, useMemo } from 'react'

/**
 * usePagination - Hook for pagination logic
 * 
 * @example
 * const {
 *   page, pageSize, totalPages,
 *   goToPage, nextPage, prevPage, setPageSize
 * } = usePagination({ total: 100, pageSize: 10 });
 */
export function usePagination(options = {}) {
  const {
    initialPage = 1,
    initialPageSize = 10,
    total = 0,
    onChange
  } = options

  const [page, setPage] = useState(initialPage)
  const [pageSize, setPageSizeState] = useState(initialPageSize)

  const totalPages = useMemo(() => {
    return Math.max(1, Math.ceil(total / pageSize))
  }, [total, pageSize])

  const hasNextPage = page < totalPages
  const hasPrevPage = page > 1

  const goToPage = useCallback((newPage) => {
    const validPage = Math.max(1, Math.min(newPage, totalPages))
    setPage(validPage)
    onChange?.({ page: validPage, pageSize })
  }, [totalPages, pageSize, onChange])

  const nextPage = useCallback(() => {
    if (hasNextPage) {
      goToPage(page + 1)
    }
  }, [hasNextPage, page, goToPage])

  const prevPage = useCallback(() => {
    if (hasPrevPage) {
      goToPage(page - 1)
    }
  }, [hasPrevPage, page, goToPage])

  const firstPage = useCallback(() => {
    goToPage(1)
  }, [goToPage])

  const lastPage = useCallback(() => {
    goToPage(totalPages)
  }, [goToPage, totalPages])

  const setPageSize = useCallback((newSize) => {
    setPageSizeState(newSize)
    // Reset to first page when changing page size
    setPage(1)
    onChange?.({ page: 1, pageSize: newSize })
  }, [onChange])

  // Calculate offset for API calls
  const offset = (page - 1) * pageSize

  // Generate page numbers for pagination UI
  const pageNumbers = useMemo(() => {
    const pages = []
    const maxVisible = 5
    
    let start = Math.max(1, page - Math.floor(maxVisible / 2))
    let end = Math.min(totalPages, start + maxVisible - 1)
    
    if (end - start < maxVisible - 1) {
      start = Math.max(1, end - maxVisible + 1)
    }

    if (start > 1) {
      pages.push(1)
      if (start > 2) pages.push('...')
    }

    for (let i = start; i <= end; i++) {
      pages.push(i)
    }

    if (end < totalPages) {
      if (end < totalPages - 1) pages.push('...')
      pages.push(totalPages)
    }

    return pages
  }, [page, totalPages])

  // Reset pagination
  const reset = useCallback(() => {
    setPage(initialPage)
    setPageSizeState(initialPageSize)
  }, [initialPage, initialPageSize])

  return {
    page,
    pageSize,
    total,
    totalPages,
    offset,
    hasNextPage,
    hasPrevPage,
    pageNumbers,
    goToPage,
    nextPage,
    prevPage,
    firstPage,
    lastPage,
    setPageSize,
    reset
  }
}

/**
 * usePaginatedData - Combines pagination with data fetching
 */
export function usePaginatedData(fetchFn, options = {}) {
  const {
    initialPage = 1,
    initialPageSize = 10,
    dependencies = []
  } = options

  const [data, setData] = useState([])
  const [total, setTotal] = useState(0)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)

  const pagination = usePagination({
    initialPage,
    initialPageSize,
    total
  })

  const fetchData = useCallback(async () => {
    setLoading(true)
    setError(null)

    try {
      const result = await fetchFn({
        page: pagination.page,
        pageSize: pagination.pageSize,
        offset: pagination.offset
      })

      setData(result.data || result.items || [])
      setTotal(result.total || result.totalItems || 0)
    } catch (err) {
      setError(err.message)
    } finally {
      setLoading(false)
    }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [fetchFn, pagination.page, pagination.pageSize, ...dependencies])

  return {
    data,
    loading,
    error,
    pagination,
    refetch: fetchData
  }
}

export default usePagination
