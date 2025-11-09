// Small helper that attaches Authorization header when token present and returns parsed JSON or throws on error
export async function apiFetch(path, opts = {}) {
  const url = path.startsWith('http') ? path : path
  const token = localStorage.getItem('token')
  const headers = opts.headers ? { ...opts.headers } : {}
  if (token) headers['Authorization'] = `Bearer ${token}`
  const finalOpts = { ...opts, headers }
  const res = await fetch(url, finalOpts)
  if (!res.ok) {
    let body = ''
    try { body = await res.text() } catch (e) { /* noop */ }
    const err = new Error(body || res.statusText)
    err.status = res.status
    throw err
  }
  // some endpoints may return empty body (204)
  const text = await res.text()
  try { return text ? JSON.parse(text) : null } catch (e) { return text }
}
