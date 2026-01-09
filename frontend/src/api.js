const ABSOLUTE_URL = /^https?:\/\//i
const API_BASE = (import.meta.env?.VITE_API_URL || '').replace(/\/$/, '')
const API_SECRET = import.meta.env?.VITE_API_SECRET || ''

// Small helper that attaches Authorization headers and returns parsed JSON or throws on error
export async function apiFetch(path, opts = {}) {
  const isAbsolute = ABSOLUTE_URL.test(path)
  const normalizedPath = path.startsWith('/') ? path : `/${path}`
  const url = isAbsolute ? path : (API_BASE ? `${API_BASE}${normalizedPath}` : normalizedPath)

  const token = localStorage.getItem('token')
  const headers = opts.headers ? { ...opts.headers } : {}
  if (token && !headers['Authorization']) {
    headers['Authorization'] = `Bearer ${token}`
  }
  if (API_SECRET && !headers['X-API-SECRET']) {
    headers['X-API-SECRET'] = API_SECRET
  }

  const finalOpts = { ...opts, headers }
  const res = await fetch(url, finalOpts)
  if (!res.ok) {
    let body = ''
    try { body = await res.text() } catch (e) { /* noop */ }
    let message = body || res.statusText
    if (body) {
      try {
        const parsed = JSON.parse(body)
        if (parsed && typeof parsed === 'object') {
          // Handle new standardized error response format
          if (parsed.error && typeof parsed.error === 'object') {
            message = parsed.error.message || parsed.error.code || body
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
    const err = new Error(message)
    err.status = res.status
    throw err
  }
  // some endpoints may return empty body (204)
  const text = await res.text()
  try { return text ? JSON.parse(text) : null } catch (e) { return text }
}
