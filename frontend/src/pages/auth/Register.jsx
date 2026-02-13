import React, { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import { apiFetch } from '../../api'
import PageHeader from '../../components/ui/PageHeader'
import SectionCard from '../../components/ui/SectionCard'
import FeedbackCard from '../../components/ui/FeedbackCard'

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

const PASSWORD_RULE = /^(?=.*[a-ząćęłńóśźż])(?=.*[A-ZĄĆĘŁŃÓŚŹŻ])(?=.*\d).{10,}$/
const POSTAL_CODE_RULE = /^\d{2}-\d{3}$/
const EMAIL_RULE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

export default function Register() {
  const auth = useAuth()
  const navigate = useNavigate()
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

    const name = form.name.trim()
    const email = form.email.trim()
    const password = form.password
    const confirmPassword = form.confirmPassword
    const postalCode = form.postalCode.trim()
    const phoneNumber = form.phoneNumber.trim()
    const addressLine = form.addressLine.trim()
    const city = form.city.trim()
    const tastePrompt = form.tastePrompt.trim()

    if (name.length < 2) {
      setError('Imię i nazwisko musi mieć co najmniej 2 znaki')
      return
    }

    if (!EMAIL_RULE.test(email)) {
      setError('Podaj poprawny adres e-mail')
      return
    }

    if (form.password !== form.confirmPassword) {
      setError('Hasła muszą być identyczne')
      return
    }

    if (!PASSWORD_RULE.test(password)) {
      setError('Hasło musi mieć min. 10 znaków, małą i dużą literę (także polską) oraz cyfrę')
      return
    }

    if (postalCode && !POSTAL_CODE_RULE.test(postalCode)) {
      setError('Kod pocztowy musi być w formacie 00-000')
      return
    }

    if (phoneNumber.length > 30) {
      setError('Numer telefonu nie może przekraczać 30 znaków')
      return
    }

    if (addressLine.length > 255) {
      setError('Adres nie może przekraczać 255 znaków')
      return
    }

    if (city.length > 100) {
      setError('Miasto nie może przekraczać 100 znaków')
      return
    }

    if (tastePrompt.length > 500) {
      setError('Opis preferencji nie może przekraczać 500 znaków')
      return
    }

    if (!form.privacyConsent) {
      setError('Musisz wyrazić zgodę na przetwarzanie danych osobowych')
      return
    }

    setLoading(true)
    try {
      const payload = {
        name,
        email,
        password,
        phoneNumber: phoneNumber || undefined,
        addressLine: addressLine || undefined,
        city: city || undefined,
        postalCode: postalCode || undefined,
        privacyConsent: form.privacyConsent
      }

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
      } else {
        setSuccess('Konto zostało utworzone i zweryfikowane. Możesz się teraz zalogować.')
      }

      // Przekieruj po 2 sekundach na stronę logowania
      setTimeout(() => {
        navigate('/login')
      }, 2000)
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
              minLength={10}
              pattern="(?=.*[a-ząćęłńóśźż])(?=.*[A-ZĄĆĘŁŃÓŚŹŻ])(?=.*\d).{10,}"
              title="Minimum 10 znaków, mała i duża litera (także z polskimi znakami) oraz cyfra"
              required
            />
            <p className="field-hint">Minimum 10 znaków, mała i duża litera (także z polskimi znakami) oraz cyfra</p>
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
              minLength={10}
              pattern="(?=.*[a-ząćęłńóśźż])(?=.*[A-ZĄĆĘŁŃÓŚŹŻ])(?=.*\d).{10,}"
              title="Minimum 10 znaków, mała i duża litera (także z polskimi znakami) oraz cyfra"
              required
            />
          </div>
          <div className="form-field">
            <label htmlFor="register-phone">Telefon</label>
            <input
              id="register-phone"
              name="phoneNumber"
              placeholder="+48 123 456 789"
              value={form.phoneNumber}
              onChange={handleChange}
            />
          </div>
          <div className="form-field">
            <label htmlFor="register-address">Adres</label>
            <input
              id="register-address"
              name="addressLine"
              placeholder="ul. Przykładowa 123"
              value={form.addressLine}
              onChange={handleChange}
            />
          </div>
          <div className="form-field">
            <label htmlFor="register-city">Miasto</label>
            <input
              id="register-city"
              name="city"
              placeholder="Warszawa"
              value={form.city}
              onChange={handleChange}
            />
          </div>
          <div className="form-field">
            <label htmlFor="register-postal">Kod pocztowy</label>
            <input
              id="register-postal"
              name="postalCode"
              type="text"
              inputMode="numeric"
              maxLength={6}
              placeholder="00-000"
              value={form.postalCode}
              onChange={e => {
                // automatycznie formatuje: dwie cyfry, myślnik, trzy cyfry
                let v = e.target.value.replace(/[^\d]/g, '')
                if (v.length > 2) v = v.slice(0,2) + '-' + v.slice(2,5)
                if (v.length > 6) v = v.slice(0,6)
                setForm(prev => ({ ...prev, postalCode: v }))
              }}
              required
            />
          </div>
          <div className="form-field form-field--full">
            <label htmlFor="register-taste">Co lubisz czytać?</label>
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
