import React, { createContext, useContext, useMemo, useRef } from 'react'

const ResourceCacheContext = createContext({
  getCachedResource: () => undefined,
  setCachedResource: () => undefined,
  invalidateResource: () => undefined,
  prefetchResource: () => Promise.resolve(undefined),
})

export function ResourceCacheProvider({ children }) {
  const cacheRef = useRef(new Map())
  const pendingRef = useRef(new Map())

  const api = useMemo(() => {
    function getCachedResource(key, ttlMs = 60000) {
      const entry = cacheRef.current.get(key)
      if (!entry) {
        return undefined
      }

      if (typeof ttlMs === 'number' && ttlMs >= 0) {
        const age = Date.now() - entry.timestamp
        if (age > ttlMs) {
          cacheRef.current.delete(key)
          return undefined
        }
      }

      return entry.value
    }

    function setCachedResource(key, value) {
      cacheRef.current.set(key, { value, timestamp: Date.now() })
    }

    async function prefetchResource(key, loader, ttlMs = 60000) {
      if (typeof loader !== 'function') {
        throw new Error('prefetchResource requires a loader function')
      }

      const cached = getCachedResource(key, ttlMs)
      if (typeof cached !== 'undefined') {
        return cached
      }

      const pending = pendingRef.current.get(key)
      if (pending) {
        return pending
      }

      const request = (async () => {
        try {
          const value = await loader()
          setCachedResource(key, value)
          return value
        } finally {
          pendingRef.current.delete(key)
        }
      })()

      pendingRef.current.set(key, request)
      return request
    }

    function invalidateResource(matchKey) {
      if (!matchKey) {
        cacheRef.current.clear()
        pendingRef.current.clear()
        return
      }

      const isPrefix = matchKey.endsWith('*')
      const compareKey = isPrefix ? matchKey.slice(0, -1) : matchKey

      for (const key of Array.from(cacheRef.current.keys())) {
        if (isPrefix ? key.startsWith(compareKey) : key === compareKey) {
          cacheRef.current.delete(key)
        }
      }

      for (const key of Array.from(pendingRef.current.keys())) {
        if (isPrefix ? key.startsWith(compareKey) : key === compareKey) {
          pendingRef.current.delete(key)
        }
      }
    }

    return { getCachedResource, setCachedResource, invalidateResource, prefetchResource }
  }, [])

  return (
    <ResourceCacheContext.Provider value={api}>
      {children}
    </ResourceCacheContext.Provider>
  )
}

export function useResourceCache() {
  return useContext(ResourceCacheContext)
}
