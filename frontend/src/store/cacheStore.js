import { create } from 'zustand'

/**
 * Zustand store for resource caching.
 * Replaces ResourceCacheContext for better performance.
 */
export const useCacheStore = create((set, get) => ({
  // State: Map of resource key -> { data, timestamp }
  cache: {},

  // Actions
  getCached: (key, ttl = 60000) => {
    const { cache } = get()
    const entry = cache[key]
    
    if (!entry) return null
    
    const age = Date.now() - entry.timestamp
    if (age > ttl) {
      // Expired, remove it
      set((state) => {
        const newCache = { ...state.cache }
        delete newCache[key]
        return { cache: newCache }
      })
      return null
    }
    
    return entry.data
  },

  setCache: (key, data) => {
    set((state) => ({
      cache: {
        ...state.cache,
        [key]: {
          data,
          timestamp: Date.now()
        }
      }
    }))
  },

  invalidate: (key) => {
    set((state) => {
      const newCache = { ...state.cache }
      delete newCache[key]
      return { cache: newCache }
    })
  },

  invalidatePattern: (pattern) => {
    set((state) => {
      const newCache = { ...state.cache }
      Object.keys(newCache).forEach((key) => {
        if (key.includes(pattern)) {
          delete newCache[key]
        }
      })
      return { cache: newCache }
    })
  },

  clearAll: () => {
    set({ cache: {} })
  }
}))
