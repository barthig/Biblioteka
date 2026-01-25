import { useState, useCallback, useEffect } from 'react'

/**
 * useStorage - Hook for localStorage/sessionStorage with automatic JSON serialization
 * 
 * @example
 * const [theme, setTheme] = useStorage('theme', 'light');
 * const [user, setUser] = useStorage('user', null, { storage: sessionStorage });
 */
export function useStorage(key, initialValue, options = {}) {
  const {
    storage = localStorage,
    serialize = JSON.stringify,
    deserialize = JSON.parse,
    onError
  } = options

  // Get initial value from storage or use default
  const getStoredValue = useCallback(() => {
    try {
      const item = storage.getItem(key)
      if (item !== null) {
        return deserialize(item)
      }
    } catch (error) {
      onError?.(error)
      // eslint-disable-next-line no-console
      console.warn(`Error reading localStorage key "${key}":`, error)
    }
    return initialValue
  }, [key, storage, deserialize, initialValue, onError])

  const [storedValue, setStoredValue] = useState(getStoredValue)

  // Update storage when value changes
  const setValue = useCallback((value) => {
    try {
      const valueToStore = value instanceof Function ? value(storedValue) : value
      setStoredValue(valueToStore)
      
      if (valueToStore === undefined || valueToStore === null) {
        storage.removeItem(key)
      } else {
        storage.setItem(key, serialize(valueToStore))
      }
    } catch (error) {
      onError?.(error)
      // eslint-disable-next-line no-console
      console.warn(`Error setting localStorage key "${key}":`, error)
    }
  }, [key, storedValue, storage, serialize, onError])

  // Remove from storage
  const removeValue = useCallback(() => {
    try {
      storage.removeItem(key)
      setStoredValue(initialValue)
    } catch (error) {
      onError?.(error)
      // eslint-disable-next-line no-console
      console.warn(`Error removing localStorage key "${key}":`, error)
    }
  }, [key, storage, initialValue, onError])

  // Sync with storage events (for cross-tab sync)
  useEffect(() => {
    const handleStorageChange = (e) => {
      if (e.key === key && e.storageArea === storage) {
        try {
          setStoredValue(e.newValue ? deserialize(e.newValue) : initialValue)
        } catch (error) {
          onError?.(error)
        }
      }
    }

    window.addEventListener('storage', handleStorageChange)
    return () => window.removeEventListener('storage', handleStorageChange)
  }, [key, storage, deserialize, initialValue, onError])

  return [storedValue, setValue, removeValue]
}

/**
 * useLocalStorage - Shorthand for useStorage with localStorage
 */
export function useLocalStorage(key, initialValue, options = {}) {
  return useStorage(key, initialValue, { ...options, storage: localStorage })
}

/**
 * useSessionStorage - Shorthand for useStorage with sessionStorage
 */
export function useSessionStorage(key, initialValue, options = {}) {
  return useStorage(key, initialValue, { ...options, storage: sessionStorage })
}

/**
 * useStorageState - Object storage with partial updates
 */
export function useStorageState(key, initialState = {}, options = {}) {
  const [state, setState, removeState] = useStorage(key, initialState, options)

  const updateState = useCallback((updates) => {
    setState(prev => ({ ...prev, ...updates }))
  }, [setState])

  const resetState = useCallback(() => {
    setState(initialState)
  }, [setState, initialState])

  return {
    state,
    setState,
    updateState,
    resetState,
    removeState
  }
}

export default useStorage
