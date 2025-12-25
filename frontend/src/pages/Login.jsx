import React, { useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { apiFetch } from '../api'
import PageHeader from '../components/ui/PageHeader'
import SectionCard from '../components/ui/SectionCard'
import FeedbackCard from '../components/ui/FeedbackCard'

export default function Login() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const auth = useAuth()
  const location = useLocation()
  const navigate = useNavigate()

  const from = location.state?.from?.pathname || '/'
  const message = location.state?.message

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
        auth.login(data.token, data.refreshToken)
        navigate(from, { replace: true })
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
    <div className="page">
      <PageHeader
        title="Zaloguj się"
        subtitle="Uzyskaj dostęp do swojego konta i historii wypożyczeń."
      />

      {message && <FeedbackCard>{message}</FeedbackCard>}
      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}

      <SectionCard>
        <form onSubmit={handleSubmit} className="form-grid">
          <div className="form-field">
            <label htmlFor="login-email">Adres e-mail</label>
            <input
              id="login-email"
              name="email"
              type="email"
              autoComplete="email"
              placeholder="twoj@email.com"
              value={email}
              onChange={e => setEmail(e.target.value)}
              required
            />
          </div>
          <div className="form-field">
            <label htmlFor="login-password">Hasło</label>
            <input
              id="login-password"
              name="password"
              type="password"
              autoComplete="current-password"
              placeholder="••••••••"
              value={password}
              onChange={e => setPassword(e.target.value)}
              required
            />
          </div>
          <div className="form-actions">
            <button className="btn btn-primary" type="submit" disabled={loading}>
              {loading ? 'Logowanie...' : 'Zaloguj się'}
            </button>
            <Link to="/register" className="btn btn-outline">
              Zarejestruj
            </Link>
          </div>
        </form>
      </SectionCard>
    </div>
  )
}
