import React, { useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { apiFetch } from '../api'
import PageHeader from '../components/ui/PageHeader'
import SectionCard from '../components/ui/SectionCard'
import FeedbackCard from '../components/ui/FeedbackCard'

const initialForm = {
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
  phoneNumber: '',
  addressLine: '',
  city: '',
  postalCode: '',
  privacyConsent: true,
  tastePrompt: ''
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
        postalCode: form.postalCode.trim() || undefined,
        privacyConsent: form.privacyConsent
      }

      const tastePrompt = form.tastePrompt.trim()
      if (tastePrompt) {
        payload.tastePrompt = tastePrompt
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
        setSuccess('Konto zostało utworzone i zweryfikowane. Oczekuje na akceptację bibliotekarza.')
        return
      }

      const loginResult = await apiFetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: payload.email, password: payload.password })
      })

      if (loginResult?.token) {
        auth.login(loginResult.token, loginResult.refreshToken)
      } else {
        setSuccess('Konto zostało zweryfikowane, ale logowanie nie powiodło się. Spróbuj ponownie.')
      }
    } catch (err) {
      setError(err.message || 'Rejestracja nie powiodła się')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="page">
      <PageHeader
        title="Utwórz konto"
        subtitle="Dołącz do naszej społeczności czytelników."
      />

      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}
      {success && <FeedbackCard variant="success">{success}</FeedbackCard>}

      <SectionCard>
        <form onSubmit={handleSubmit} className="form-grid form-grid--two">
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
            <label htmlFor="register-email">E-mail</label>
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
          <div className="form-field form-field--full">
            <label htmlFor="register-taste">Co lubisz czytać? (opcjonalnie)</label>
            <textarea
              id="register-taste"
              name="tastePrompt"
              placeholder="Np. kryminały w deszczowym Londynie albo fantasy z magią"
              value={form.tastePrompt}
              onChange={handleChange}
              rows={3}
            />
            <p className="field-hint">To pomoże uruchomić rekomendacje AI od pierwszego dnia.</p>
          </div>

          <label className="checkbox-field form-field--full">
            <input
              type="checkbox"
              name="privacyConsent"
              checked={form.privacyConsent}
              onChange={handleChange}
              required
            />
            <span>Wyrażam zgodę na przetwarzanie danych osobowych</span>
          </label>

          <div className="form-actions form-field--full">
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? 'Rejestrowanie...' : 'Utwórz konto'}
            </button>
            <Link to="/login" className="btn btn-outline">
              Mam konto
            </Link>
          </div>
        </form>
      </SectionCard>
    </div>
  )
}
