// API module - barrel exports
export { api, apiClient, setTokens, clearTokens, getAccessToken } from './client'
export { default } from './client'

/**
 * Legacy compatibility wrapper.
 * All services import `apiFetch` – this delegates to the new apiClient
 * while keeping the same (path, opts) signature.
 */
import { apiClient } from './client'

export async function apiFetch(path, opts = {}) {
  const method = opts.method || 'GET'

  // apiClient expects body as a raw object (it stringifies internally)
  let body = opts.body
  if (typeof body === 'string') {
    try { body = JSON.parse(body) } catch { /* keep as-is */ }
  }

  return apiClient(path, {
    ...opts,
    method,
    body,
  })
}
