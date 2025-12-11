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
    <div className="page page--centered">
      <header className="page-header">
        <div>
          <h1>Utworz konto</h1>
          <p className="support-copy">Zaloz konto czytelnika, aby rezerwowac i zarzadzac wypozyczeniami online.</p>
        </div>
      </header>

      <div className="surface-card form-card">
        <form onSubmit={handleSubmit}>
          <div className="form-grid form-grid--two">
            <div>
              <label htmlFor="register-name">Imie i nazwisko</label>
              <input
                id="register-name"
                name="name"
                value={form.name}
                onChange={handleChange}
                required
              />
            </div>
            <div>
              <label htmlFor="register-email">Email</label>
              <input
                id="register-email"
                name="email"
                type="email"
                autoComplete="email"
                value={form.email}
                onChange={handleChange}
                required
              />
            </div>
            <div>
              <label htmlFor="register-password">Haslo</label>
              <input
                id="register-password"
                name="password"
                type="password"
                autoComplete="new-password"
                value={form.password}
                onChange={handleChange}
                minLength={8}
                required
              />
              <p className="field-hint">Minimum 8 znakow, w tym litery i cyfra.</p>
            </div>
            <div>
              <label htmlFor="register-confirm">Powtorz haslo</label>
              <input
                id="register-confirm"
                name="confirmPassword"
                type="password"
                autoComplete="new-password"
                value={form.confirmPassword}
                onChange={handleChange}
                minLength={8}
                required
              />
            </div>
            <div>
              <label htmlFor="register-phone">Telefon</label>
              <input
                id="register-phone"
                name="phoneNumber"
                value={form.phoneNumber}
                onChange={handleChange}
                placeholder="Opcjonalnie"
              />
            </div>
            <div>
              <label htmlFor="register-address">Adres</label>
              <input
                id="register-address"
                name="addressLine"
                value={form.addressLine}
                onChange={handleChange}
                placeholder="Ulica i numer"
              />
            </div>
            <div>
              <label htmlFor="register-city">Miasto</label>
              <input
                id="register-city"
                name="city"
                value={form.city}
                onChange={handleChange}
              />
            </div>
            <div>
              <label htmlFor="register-postal">Kod pocztowy</label>
              <input
                id="register-postal"
                name="postalCode"
                value={form.postalCode}
                onChange={handleChange}
              />
            </div>
          </div>
          <label className="checkbox">
            <input
              type="checkbox"
              name="privacyConsent"
              checked={form.privacyConsent}
              onChange={handleChange}
              required
            />
            <span>Wyrazam zgode na przetwarzanie danych w celu prowadzenia konta czytelnika.</span>
          </label>
          <div className="form-actions">
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? 'Rejestrowanie...' : 'Zarejestruj sie'}
            </button>
            <span className="support-copy">
              Masz juz konto? <Link to="/login">Zaloguj sie</Link>
            </span>
          </div>
          {error && <div className="error">{error}</div>}
          {success && <div className="success">{success}</div>}
        </form>
      </div>
    </div>
  )
}
