import React, { useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { apiFetch } from '../api'

const initialForm = {
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
  phoneNumber: '',
  addressLine: '',
  city: '',
  postalCode: '',
  privacyConsent: true
}

export default function Register() {
  const auth = useAuth()
  const [form, setForm] = useState(initialForm)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)

  function handleChange(event) {
    const { name, value, type, checked } = event.target
    const nextValue = type === 'checkbox' ? checked : value
    setForm(prev => ({ ...prev, [name]: nextValue }))
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setError(null)
    setSuccess(null)

    if (form.password !== form.confirmPassword) {
      setError('Hasla musza byc identyczne')
      return
    }

    setLoading(true)
    try {
      const payload = {
        name: form.name.trim(),
        email: form.email.trim(),
        password: form.password,
        phoneNumber: form.phoneNumber.trim() || undefined,
        addressLine: form.addressLine.trim() || undefined,
        city: form.city.trim() || undefined,
        postalCode: form.postalCode.trim() || undefined,
        privacyConsent: form.privacyConsent
      }

      const response = await apiFetch('/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })

      const verificationToken = response?.verificationToken
      if (!verificationToken) {
        throw new Error('Brak tokenu weryfikacyjnego w odpowiedzi serwera')
      }

      const verifyResult = await apiFetch(`/api/auth/verify/${verificationToken}`)
      if (verifyResult?.pendingApproval) {
        setSuccess('Konto zostalo utworzone i zweryfikowane. Oczekuje na akceptacje bibliotekarza.')
        return
      }

      const loginResult = await apiFetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: payload.email, password: payload.password })
      })

      if (loginResult?.token) {
        auth.login(loginResult.token)
      } else {
        setSuccess('Konto zostalo zweryfikowane, ale logowanie nie powiodlo sie. Sprobuj ponownie.')
      }
    } catch (err) {
      setError(err.message || 'Rejestracja nie powiodla sie')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="auth-page">
      <div className="auth-container auth-container--wide">
        <div className="auth-card">
          <div className="auth-header">
            <h1>Utwórz konto</h1>
            <p>Dołącz do naszej społeczności czytelników</p>
          </div>
          <form onSubmit={handleSubmit} className="auth-form">
            <div className="form-grid form-grid--two">
              <div className="form-field">
                <label htmlFor="register-name">Imię i nazwisko</label>
                <input
                  id="register-name"
                  name="name"
                  placeholder="Jan Kowalski"
                  value={form.name}
                  onChange={handleChange}
                  required
                />
              </div>
              <div className="form-field">
                <label htmlFor="register-email">Email</label>
                <input
                  id="register-email"
                  name="email"
                  type="email"
                  autoComplete="email"
                  placeholder="twoj@email.com"
                  value={form.email}
                  onChange={handleChange}
                  required
                />
              </div>
              <div className="form-field">
                <label htmlFor="register-password">Hasło</label>
                <input
                  id="register-password"
                  name="password"
                  type="password"
                  autoComplete="new-password"
                  placeholder="••••••••"
                  value={form.password}
                  onChange={handleChange}
                  minLength={8}
                  required
                />
                <p className="field-hint">Minimum 8 znaków</p>
              </div>
              <div className="form-field">
                <label htmlFor="register-confirm">Powtórz hasło</label>
                <input
                  id="register-confirm"
                  name="confirmPassword"
                  type="password"
                  autoComplete="new-password"
                  placeholder="••••••••"
                  value={form.confirmPassword}
                  onChange={handleChange}
                  minLength={8}
                  required
                />
              </div>
              <div className="form-field">
                <label htmlFor="register-phone">Telefon (opcjonalnie)</label>
                <input
                  id="register-phone"
                  name="phoneNumber"
                  placeholder="+48 123 456 789"
                  value={form.phoneNumber}
                  onChange={handleChange}
                />
              </div>
              <div className="form-field">
                <label htmlFor="register-address">Adres (opcjonalnie)</label>
                <input
                  id="register-address"
                  name="addressLine"
                  placeholder="ul. Przykładowa 123"
                  value={form.addressLine}
                  onChange={handleChange}
                />
              </div>
              <div className="form-field">
                <label htmlFor="register-city">Miasto (opcjonalnie)</label>
                <input
                  id="register-city"
                  name="city"
                  placeholder="Warszawa"
                  value={form.city}
                  onChange={handleChange}
                />
              </div>
              <div className="form-field">
                <label htmlFor="register-postal">Kod pocztowy (opcjonalnie)</label>
                <input
                  id="register-postal"
                  name="postalCode"
                  placeholder="00-000"
                  value={form.postalCode}
                  onChange={handleChange}
                />
              </div>
            </div>
            <label className="checkbox-field">
              <input
                type="checkbox"
                name="privacyConsent"
                checked={form.privacyConsent}
                onChange={handleChange}
                required
              />
              <span>Wyrażam zgodę na przetwarzanie danych osobowych</span>
            </label>
            {error && <div className="error-message">{error}</div>}
            {success && <div className="success-message">{success}</div>}
            <button type="submit" className="btn btn-primary btn-block" disabled={loading}>
              {loading ? 'Rejestrowanie...' : 'Utwórz konto'}
            </button>
          </form>

          <div className="auth-footer">
            <p>Masz już konto? <Link to="/login" className="auth-link">Zaloguj się</Link></p>
          </div>
        </div>
      </div>
    </div>
  )
}
