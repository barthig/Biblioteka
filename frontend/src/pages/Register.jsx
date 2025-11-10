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
  postalCode: ''
}

export default function Register() {
  const auth = useAuth()
  const [form, setForm] = useState(initialForm)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)

  function handleChange(event) {
    const { name, value } = event.target
    setForm(prev => ({ ...prev, [name]: value }))
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setError(null)

    if (form.password !== form.confirmPassword) {
      setError('Hasła muszą być identyczne')
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
        postalCode: form.postalCode.trim() || undefined
      }

      const response = await apiFetch('/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })

      if (response?.token) {
        auth.login(response.token)
      } else {
        throw new Error('Brak tokenu w odpowiedzi serwera')
      }
    } catch (err) {
      setError(err.message || 'Rejestracja nie powiodła się')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="page page--centered">
      <header className="page-header">
        <div>
          <h1>Utwórz konto</h1>
          <p className="support-copy">Załóż konto czytelnika, aby rezerwować i zarządzać wypożyczeniami online.</p>
        </div>
      </header>

      <div className="surface-card form-card">
        <form onSubmit={handleSubmit}>
          <div className="form-grid form-grid--two">
            <div>
              <label htmlFor="register-name">Imię i nazwisko</label>
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
              <label htmlFor="register-password">Hasło</label>
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
            </div>
            <div>
              <label htmlFor="register-confirm">Powtórz hasło</label>
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
          <div className="form-actions">
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? 'Rejestrowanie...' : 'Zarejestruj się'}
            </button>
            <span className="support-copy">
              Masz już konto? <Link to="/login">Zaloguj się</Link>
            </span>
          </div>
          {error && <div className="error">{error}</div>}
        </form>
      </div>
    </div>
  )
}
