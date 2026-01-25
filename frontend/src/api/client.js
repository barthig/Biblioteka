/**
 * API Client with interceptors, retry logic, and error handling
 * Centralized API communication layer
 */

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'

// ============================================
// CONFIGURATION
// ============================================
const config = {
  baseURL: API_BASE_URL,
  timeout: 30000,
  retryAttempts: 3,
  retryDelay: 1000,
  enableLogging: import.meta.env.DEV,
}

// ============================================
// TOKEN MANAGEMENT
// ============================================
let accessToken = localStorage.getItem('token')
let refreshToken = localStorage.getItem('refreshToken')
let isRefreshing = false
let refreshSubscribers = []

export const setTokens = (access, refresh) => {
  accessToken = access
  refreshToken = refresh
  if (access) localStorage.setItem('token', access)
  if (refresh) localStorage.setItem('refreshToken', refresh)
}

export const clearTokens = () => {
  accessToken = null
  refreshToken = null
  localStorage.removeItem('token')
  localStorage.removeItem('refreshToken')
}

export const getAccessToken = () => accessToken

// ============================================
// INTERCEPTORS
// ============================================

/**
 * Request interceptors - modify request before sending
 */
const requestInterceptors = [
  // Add auth header
  (options) => {
    if (accessToken && !options.headers?.['Authorization']) {
      options.headers = {
        ...options.headers,
        'Authorization': `Bearer ${accessToken}`,
      }
    }
    return options
  },
  
  // Add content-type
  (options) => {
    if (!options.headers?.['Content-Type'] && options.body && !(options.body instanceof FormData)) {
      options.headers = {
        ...options.headers,
        'Content-Type': 'application/json',
      }
    }
    return options
  },
  
  // Add request ID for tracing
  (options) => {
    options.headers = {
      ...options.headers,
      'X-Request-ID': crypto.randomUUID(),
    }
    return options
  },
]

/**
 * Response interceptors - process response after receiving
 */
const responseInterceptors = [
  // Log response (dev only)
  async (response, url, startTime) => {
    if (config.enableLogging) {
      const duration = Date.now() - startTime
      console.log(`[API] ${response.status} ${url} (${duration}ms)`)
    }
    return response
  },
]

/**
 * Error interceptors - handle errors
 */
const errorInterceptors = [
  // Log errors
  async (error, url) => {
    if (config.enableLogging) {
      console.error(`[API Error] ${url}:`, error.message)
    }
    return error
  },
]

// ============================================
// REFRESH TOKEN LOGIC
// ============================================
const subscribeTokenRefresh = (callback) => {
  refreshSubscribers.push(callback)
}

const onTokenRefreshed = (newToken) => {
  refreshSubscribers.forEach((callback) => callback(newToken))
  refreshSubscribers = []
}

const refreshAccessToken = async () => {
  if (!refreshToken) {
    throw new Error('No refresh token available')
  }
  
  const response = await fetch(`${config.baseURL}/api/auth/refresh`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ refresh_token: refreshToken }),
  })
  
  if (!response.ok) {
    clearTokens()
    window.location.href = '/login'
    throw new Error('Token refresh failed')
  }
  
  const data = await response.json()
  setTokens(data.token, data.refresh_token || refreshToken)
  return data.token
}

// ============================================
// RETRY LOGIC
// ============================================
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

const shouldRetry = (error, attempt, options) => {
  if (attempt >= config.retryAttempts) return false
  if (options.noRetry) return false
  
  // Retry on network errors
  if (error.name === 'TypeError' && error.message === 'Failed to fetch') {
    return true
  }
  
  // Retry on 5xx errors
  if (error.status >= 500 && error.status < 600) {
    return true
  }
  
  // Retry on 429 (rate limit)
  if (error.status === 429) {
    return true
  }
  
  return false
}

// ============================================
// MAIN API CLIENT
// ============================================
export const apiClient = async (endpoint, options = {}) => {
  const url = endpoint.startsWith('http') ? endpoint : `${config.baseURL}${endpoint}`
  const startTime = Date.now()
  
  // Apply request interceptors
  let requestOptions = { ...options }
  for (const interceptor of requestInterceptors) {
    requestOptions = interceptor(requestOptions)
  }
  
  // Stringify body if needed
  if (requestOptions.body && typeof requestOptions.body === 'object' && !(requestOptions.body instanceof FormData)) {
    requestOptions.body = JSON.stringify(requestOptions.body)
  }
  
  let attempt = 0
  let lastError
  
  while (attempt < config.retryAttempts) {
    attempt++
    
    try {
      const controller = new AbortController()
      const timeoutId = setTimeout(() => controller.abort(), config.timeout)
      
      const response = await fetch(url, {
        ...requestOptions,
        signal: controller.signal,
      })
      
      clearTimeout(timeoutId)
      
      // Apply response interceptors
      for (const interceptor of responseInterceptors) {
        await interceptor(response, url, startTime)
      }
      
      // Handle 401 - Token expired
      if (response.status === 401 && !options.noRefresh) {
        if (!isRefreshing) {
          isRefreshing = true
          try {
            const newToken = await refreshAccessToken()
            isRefreshing = false
            onTokenRefreshed(newToken)
            // Retry original request with new token
            requestOptions.headers = {
              ...requestOptions.headers,
              'Authorization': `Bearer ${newToken}`,
            }
            return apiClient(endpoint, { ...options, noRefresh: true })
          } catch (refreshError) {
            isRefreshing = false
            throw refreshError
          }
        } else {
          // Wait for token refresh
          return new Promise((resolve, reject) => {
            subscribeTokenRefresh((newToken) => {
              requestOptions.headers = {
                ...requestOptions.headers,
                'Authorization': `Bearer ${newToken}`,
              }
              apiClient(endpoint, { ...options, noRefresh: true })
                .then(resolve)
                .catch(reject)
            })
          })
        }
      }
      
      // Parse response
      const contentType = response.headers.get('content-type')
      let data
      
      if (contentType?.includes('application/json')) {
        data = await response.json()
      } else {
        data = await response.text()
      }
      
      if (!response.ok) {
        const error = new Error(data?.message || `HTTP ${response.status}`)
        error.status = response.status
        error.data = data
        error.response = response
        throw error
      }
      
      return data
      
    } catch (error) {
      lastError = error
      
      // Apply error interceptors
      for (const interceptor of errorInterceptors) {
        await interceptor(error, url)
      }
      
      if (shouldRetry(error, attempt, options)) {
        const delay = config.retryDelay * Math.pow(2, attempt - 1) // Exponential backoff
        if (config.enableLogging) {
          console.log(`[API] Retrying ${url} in ${delay}ms (attempt ${attempt + 1}/${config.retryAttempts})`)
        }
        await sleep(delay)
        continue
      }
      
      throw error
    }
  }
  
  throw lastError
}

// ============================================
// CONVENIENCE METHODS
// ============================================
export const api = {
  get: (endpoint, options = {}) => 
    apiClient(endpoint, { ...options, method: 'GET' }),
  
  post: (endpoint, body, options = {}) => 
    apiClient(endpoint, { ...options, method: 'POST', body }),
  
  put: (endpoint, body, options = {}) => 
    apiClient(endpoint, { ...options, method: 'PUT', body }),
  
  patch: (endpoint, body, options = {}) => 
    apiClient(endpoint, { ...options, method: 'PATCH', body }),
  
  delete: (endpoint, options = {}) => 
    apiClient(endpoint, { ...options, method: 'DELETE' }),
}

export default api
