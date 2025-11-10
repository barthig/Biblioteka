import React, { useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { apiFetch } from '../api'

export default function Login() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const auth = useAuth()

  async function handleSubmit(e) {
    e.preventDefault()
    setLoading(true)
    setError(null)
    try {
      const data = await apiFetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
      })
      if (data && data.token) {
        auth.login(data.token)
      } else {
        throw new Error('Brak tokenu w odpowiedzi')
      }
    } catch (err) {
      setError(err.message || 'Logowanie nie powiodło się')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="page page--centered">
      <header className="page-header">
        <div>
          <h1>Zaloguj się</h1>
          <p className="support-copy">Uzyskaj dostęp do panelu czytelnika i poczekalni rezerwacji.</p>
        </div>
      </header>

      <div className="surface-card form-card">
        <form onSubmit={handleSubmit}>
          <div>
            <label htmlFor="login-email">Email</label>
            <input
              id="login-email"
              name="email"
              autoComplete="email"
              value={email}
              onChange={e => setEmail(e.target.value)}
              required
            />
          </div>
          <div>
            <label htmlFor="login-password">Hasło</label>
            <input
              id="login-password"
              name="password"
              type="password"
              autoComplete="current-password"
              value={password}
              onChange={e => setPassword(e.target.value)}
              required
            />
          </div>
          <button className="btn btn-primary" disabled={loading}>
            {loading ? 'Logowanie...' : 'Zaloguj'}
          </button>
          <span className="support-copy">
            Nie masz jeszcze konta? <Link to="/register">Zarejestruj się</Link>
          </span>
          {error && <div className="error">{error}</div>}
        </form>
      </div>
    </div>
  )
}
