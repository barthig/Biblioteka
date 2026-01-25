import { useState, useEffect, useCallback, useRef } from 'react'

/**
 * useDataFetching - Generic data fetching hook with loading, error, and refresh states
 * 
 * @example
 * const { data, loading, error, refetch } = useDataFetching(
 *   () => bookService.getBooks({ page: 1 }),
 *   [page]
 * );
 */
export function useDataFetching(
  fetchFn,
  dependencies = [],
  options = {}
) {
  const {
    immediate = true,
    initialData = null,
    onSuccess,
    onError,
    cacheKey,
    cacheTTL = 5 * 60 * 1000 // 5 minutes
  } = options

  const [data, setData] = useState(initialData)
  const [loading, setLoading] = useState(immediate)
  const [error, setError] = useState(null)
  const [isRefreshing, setIsRefreshing] = useState(false)
  const mountedRef = useRef(true)
  const cacheRef = useRef({})

  const fetchData = useCallback(async (isRefresh = false) => {
    // Check cache first
    if (cacheKey && cacheRef.current[cacheKey]) {
      const cached = cacheRef.current[cacheKey]
      if (Date.now() - cached.timestamp < cacheTTL) {
        setData(cached.data)
        setLoading(false)
        return cached.data
      }
    }

    if (isRefresh) {
      setIsRefreshing(true)
    } else {
      setLoading(true)
    }
    setError(null)

    try {
      const result = await fetchFn()
      
      if (!mountedRef.current) return

      // Cache result
      if (cacheKey) {
        cacheRef.current[cacheKey] = {
          data: result,
          timestamp: Date.now()
        }
      }

      setData(result)
      onSuccess?.(result)
      return result
    } catch (err) {
      if (!mountedRef.current) return

      const errorMessage = err.response?.data?.message || err.message || 'Wystąpił błąd'
      setError(errorMessage)
      onError?.(err)
      throw err
    } finally {
      if (mountedRef.current) {
        setLoading(false)
        setIsRefreshing(false)
      }
    }
  }, [fetchFn, cacheKey, cacheTTL, onSuccess, onError])

  const refetch = useCallback(() => {
    return fetchData(true)
  }, [fetchData])

  const reset = useCallback(() => {
    setData(initialData)
    setError(null)
    setLoading(false)
  }, [initialData])

  useEffect(() => {
    mountedRef.current = true
    
    if (immediate) {
      fetchData()
    }

    return () => {
      mountedRef.current = false
    }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, dependencies)

  return {
    data,
    loading,
    error,
    isRefreshing,
    refetch,
    reset,
    setData,
    execute: fetchData
  }
}

/**
 * useMutation - Hook for data mutation operations (create, update, delete)
 * 
 * @example
 * const { mutate, loading, error } = useMutation(
 *   (bookData) => bookService.createBook(bookData),
 *   { onSuccess: () => refetchBooks() }
 * );
 */
export function useMutation(mutationFn, options = {}) {
  const {
    onSuccess,
    onError,
    onSettled,
    throwOnError = false
  } = options

  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [data, setData] = useState(null)
  const mountedRef = useRef(true)

  useEffect(() => {
    mountedRef.current = true
    return () => {
      mountedRef.current = false
    }
  }, [])

  const mutate = useCallback(async (...args) => {
    setLoading(true)
    setError(null)

    try {
      const result = await mutationFn(...args)
      
      if (!mountedRef.current) return

      setData(result)
      onSuccess?.(result, ...args)
      onSettled?.(result, null, ...args)
      return result
    } catch (err) {
      if (!mountedRef.current) return

      const errorMessage = err.response?.data?.message || err.message || 'Wystąpił błąd'
      setError(errorMessage)
      onError?.(err, ...args)
      onSettled?.(null, err, ...args)
      
      if (throwOnError) {
        throw err
      }
    } finally {
      if (mountedRef.current) {
        setLoading(false)
      }
    }
  }, [mutationFn, onSuccess, onError, onSettled, throwOnError])

  const reset = useCallback(() => {
    setData(null)
    setError(null)
    setLoading(false)
  }, [])

  return {
    mutate,
    loading,
    error,
    data,
    reset,
    isLoading: loading
  }
}

/**
 * useInfiniteScroll - Hook for infinite scrolling data loading
 */
export function useInfiniteScroll(fetchFn, options = {}) {
  const {
    pageSize = 20,
    initialPage = 1,
    getNextPageParam = (lastPage) => lastPage.nextPage,
    threshold = 200
  } = options

  const [pages, setPages] = useState([])
  const [page, setPage] = useState(initialPage)
  const [loading, setLoading] = useState(false)
  const [hasMore, setHasMore] = useState(true)
  const [error, setError] = useState(null)
  const observerRef = useRef(null)

  const allData = pages.flatMap(p => p.data || p.items || p)

  const loadMore = useCallback(async () => {
    if (loading || !hasMore) return

    setLoading(true)
    setError(null)

    try {
      const result = await fetchFn(page, pageSize)
      
      setPages(prev => [...prev, result])
      
      const nextPage = getNextPageParam(result)
      if (nextPage) {
        setPage(nextPage)
      } else {
        setHasMore(false)
      }
    } catch (err) {
      setError(err.message)
    } finally {
      setLoading(false)
    }
  }, [fetchFn, page, pageSize, loading, hasMore, getNextPageParam])

  const reset = useCallback(() => {
    setPages([])
    setPage(initialPage)
    setHasMore(true)
    setError(null)
  }, [initialPage])

  // Intersection observer for automatic loading
  const sentinelRef = useCallback((node) => {
    if (loading) return

    if (observerRef.current) {
      observerRef.current.disconnect()
    }

    observerRef.current = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting && hasMore) {
          loadMore()
        }
      },
      { rootMargin: `${threshold}px` }
    )

    if (node) {
      observerRef.current.observe(node)
    }
  }, [loading, hasMore, loadMore, threshold])

  return {
    data: allData,
    pages,
    loading,
    error,
    hasMore,
    loadMore,
    reset,
    sentinelRef
  }
}

export default useDataFetching
