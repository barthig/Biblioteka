const ABSOLUTE_URL = /^https?:\/\//i
const API_BASE = (import.meta.env?.VITE_API_URL || '').replace(/\/$/, '')
const API_SECRET = import.meta.env?.VITE_API_SECRET || ''

// Small helper that attaches Authorization headers and returns parsed JSON or throws on error
export async function apiFetch(path, opts = {}) {
  const isAbsolute = ABSOLUTE_URL.test(path)
  const normalizedPath = path.startsWith('/') ? path : `/${path}`
  const url = isAbsolute ? path : (API_BASE ? `${API_BASE}${normalizedPath}` : normalizedPath)

  const token = localStorage.getItem('token')
  console.log(`[apiFetch] path=${path}, token=${token ? token.substring(0, 20) + '...' : 'NULL'}`)
  
  const headers = opts.headers ? { ...opts.headers } : {}
  if (token && !headers['Authorization']) {
    headers['Authorization'] = `Bearer ${token}`
    console.log(`[apiFetch] Authorization header added`)
  }
  // Only use API_SECRET when there's no Bearer token (for machine-to-machine calls)
  if (!token && API_SECRET && !headers['X-API-SECRET']) {
    headers['X-API-SECRET'] = API_SECRET
  }

  console.log(`[apiFetch] Final headers:`, headers)
  const finalOpts = { ...opts, headers }
  const res = await fetch(url, finalOpts)
  if (!res.ok) {
    // Auto-logout on 401 Unauthorized - but only if user was actually logged in
    // and not on the login page (to prevent logout loop)
    
  
    let body = ''
    try { body = await res.text() } catch (e) { /* noop */ }
    let message = body || res.statusText
    let details
    if (body) {
      try {
        const parsed = JSON.parse(body)
        if (parsed && typeof parsed === 'object') {
          // Handle new standardized error response format
          if (parsed.error && typeof parsed.error === 'object') {
            message = parsed.error.message || parsed.error.code || body
            if (parsed.error.details) {
              details = parsed.error.details
            }
          }
            // Handle legacy 'message' field
            else if (typeof parsed.message === 'string' && parsed.message.trim() !== '') {
              message = parsed.message
            }
            // Handle legacy 'error' string field
          else if (typeof parsed.error === 'string' && parsed.error.trim() !== '') {
            message = parsed.error
          }
        }
      } catch (parseErr) {
        // ignore JSON parse failures, fall back to raw body
      }
    }
    // Only auto-logout when token is clearly invalid/expired to avoid
    // immediate redirects on generic 401 Unauthorized responses.
    const lowerMsg = (message || '').toLowerCase()
    const isInvalidToken = lowerMsg.includes('invalid') || lowerMsg.includes('expired')
    const shouldLogout = res.status === 401 && token && isInvalidToken && !window.location.pathname.includes('/login')
    if (shouldLogout) {
      localStorage.removeItem('token')
      localStorage.removeItem('refreshToken')
      window.dispatchEvent(new CustomEvent('auth:unauthorized'))
    }
    const err = new Error(message)
    if (details) {
      err.details = details
    }
    err.status = res.status
    throw err
  }
  // some endpoints may return empty body (204)
  const text = await res.text()
  try { return text ? JSON.parse(text) : null } catch (e) { return text }
}
