/**
 * API Client with interceptors, retry logic, and error handling
 * Centralized API communication layer
 */
/// <reference types="vite/client" />
import { logger } from '../utils/logger'

const API_BASE_URL = import.meta.env.VITE_API_URL || ''

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
// TEXT NORMALIZATION (MOJIBAKE FIX)
// ============================================
const CP1252_TO_BYTE = {
  0x20AC: 0x80,
  0x201A: 0x82,
  0x0192: 0x83,
  0x201E: 0x84,
  0x2026: 0x85,
  0x2020: 0x86,
  0x2021: 0x87,
  0x02C6: 0x88,
  0x2030: 0x89,
  0x0160: 0x8A,
  0x2039: 0x8B,
  0x0152: 0x8C,
  0x017D: 0x8E,
  0x2018: 0x91,
  0x2019: 0x92,
  0x201C: 0x93,
  0x201D: 0x94,
  0x2022: 0x95,
  0x2013: 0x96,
  0x2014: 0x97,
  0x02DC: 0x98,
  0x2122: 0x99,
  0x0161: 0x9A,
  0x203A: 0x9B,
  0x0153: 0x9C,
  0x017E: 0x9E,
  0x0178: 0x9F,
}

const MOJIBAKE_PATTERN = /(?:Ã.|Å.|Ä.|â.|ďż˝.)/

const PRE_NORMALIZATION_REPLACEMENTS = [
  ['Ãąâ€š', 'ł'],
  ['Ã„â€¦', 'ą'],
  ['Ã„â€¡', 'ć'],
  ['Ã„â„¢', 'ę'],
  ['Ã…â€š', 'Ł'],
  ['Ã…â€ž', 'Ń'],
  ['Ã…â€º', 'Ś'],
  ['Ã…Âº', 'Ź'],
  ['Ã…Â»', 'Ż'],
  ['Ã…Â¼', 'ż'],
  ['Ã…Â„', 'ń'],
  ['Ã…›', 'ś'],
  ['Ã…º', 'ź'],
  ['Ã³', 'ó'],
  ['Ã“', 'Ó'],
  ['Ãąâ€º', 'ś'],
  ['Ãąâ€ž', 'ń'],
  ['Ãąâ€¦', 'ą'],
  ['Ã„â€ž', 'Ą'],
]

const charToByte = (charCode) => {
  if (charCode >= 0 && charCode <= 0xFF) {
    return charCode
  }
  return CP1252_TO_BYTE[charCode] ?? null
}

const decodeMisdecodedUtf8 = (value) => {
  const bytes = []
  for (const char of value) {
    const byte = charToByte(char.charCodeAt(0))
    if (byte === null) {
      return value
    }
    bytes.push(byte)
  }

  try {
    return new TextDecoder('utf-8').decode(new Uint8Array(bytes))
  } catch {
    return value
  }
}

const normalizeMojibakeString = (value) => {
  if (typeof value !== 'string' || !MOJIBAKE_PATTERN.test(value)) {
    return value
  }

  let normalized = value
  for (const [bad, good] of PRE_NORMALIZATION_REPLACEMENTS) {
    if (normalized.includes(bad)) {
      normalized = normalized.split(bad).join(good)
    }
  }

  for (let i = 0; i < 2; i++) {
    const decoded = decodeMisdecodedUtf8(normalized)
    if (!decoded || decoded === normalized) {
      break
    }
    normalized = decoded
    if (!MOJIBAKE_PATTERN.test(normalized)) {
      break
    }
  }

  return normalized
}

const normalizePayloadStrings = (payload) => {
  if (typeof payload === 'string') {
    return normalizeMojibakeString(payload)
  }

  if (Array.isArray(payload)) {
    return payload.map(normalizePayloadStrings)
  }

  if (payload && typeof payload === 'object') {
    const normalizedObject = {}
    for (const [key, value] of Object.entries(payload)) {
      normalizedObject[key] = normalizePayloadStrings(value)
    }
    return normalizedObject
  }

  return payload
}

// ============================================
// TOKEN MANAGEMENT
// ============================================
let accessToken = localStorage.getItem('token')
let refreshToken = localStorage.getItem('refreshToken')
let isRefreshing = false
let refreshSubscribers = []

const getStoredToken = (key) => {
  try {
    return localStorage.getItem(key)
  } catch {
    return null
  }
}

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
    const token = accessToken || getStoredToken('token')
    if (token && !options.headers?.['Authorization']) {
      accessToken = token
      options.headers = {
        ...options.headers,
        'Authorization': `Bearer ${token}`,
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
      logger.log(`[API] ${response.status} ${url} (${duration}ms)`)
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
    const isUnexpectedError = !error?.status || error.status >= 500
    if (config.enableLogging && isUnexpectedError) {
      logger.error(`[API Error] ${url}:`, error.message)
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

const isAuthEndpoint = (endpoint) => {
  return [
    '/api/auth/login',
    '/api/auth/register',
    '/api/auth/refresh',
    '/api/auth/logout',
    '/api/auth/logout-all',
    '/api/test-login',
  ].some((path) => endpoint.startsWith(path))
}
const refreshAccessToken = async () => {
  refreshToken = refreshToken || getStoredToken('refreshToken')
  if (!refreshToken) {
    throw new Error('No refresh token available')
  }
  
  const response = await fetch(`${config.baseURL}/api/auth/refresh`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ refreshToken }),
  })
  
  if (!response.ok) {
    clearTokens()
    // Intentional full reload: this runs outside the React component tree
    // where useNavigate() is unavailable
    window.location.href = '/login'
    throw new Error('Token refresh failed')
  }
  
  const data = await response.json()
  setTokens(data.token, data.refreshToken || refreshToken)
  return data.token
}

// ============================================
// RETRY LOGIC
// ============================================
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

const shouldRetry = (error, attempt, options) => {
  if (attempt >= config.retryAttempts) return false
  if (options.noRetry) return false
  if (isAuthEndpoint(options.endpoint ?? '')) return false
  
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
  /** @type {any} */
  let requestOptions = { ...options, endpoint }
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
      if (response.status === 401 && !options.noRefresh && !isAuthEndpoint(endpoint)) {
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
      const contentType = response.headers?.get?.('content-type') ?? null
      let data
      
      if (response.status === 204) {
        data = null
      } else if (contentType?.includes('application/json')) {
        data = await response.json()
      } else {
        const text = await response.text()
        data = text === '' ? null : text
      }

      data = normalizePayloadStrings(data)
      
      if (!response.ok) {
        /** @type {any} */
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
          logger.log(`[API] Retrying ${url} in ${delay}ms (attempt ${attempt + 1}/${config.retryAttempts})`)
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
