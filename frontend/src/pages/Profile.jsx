import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'

const blankProfile = {
  name: '',
  email: '',
  phoneNumber: '',
  addressLine: '',
  city: '',
  postalCode: ''
}

const initialPasswordForm = {
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
}

export default function Profile() {
  const { user } = useAuth()
  const [profile, setProfile] = useState(blankProfile)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)
  const [passwordSuccess, setPasswordSuccess] = useState(null)
  const [passwordError, setPasswordError] = useState(null)
  const [passwordForm, setPasswordForm] = useState(initialPasswordForm)
  const [saving, setSaving] = useState(false)
  const [changingPassword, setChangingPassword] = useState(false)

  useEffect(() => {
    let active = true

    async function loadProfile() {
      if (!user?.id) {
        setLoading(false)
        return
      }

      try {
        setLoading(true)
        const data = await apiFetch('/api/me')
        if (active && data) {
          setProfile({
            name: data.name ?? '',
            email: data.email ?? '',
            phoneNumber: data.phoneNumber ?? '',
            addressLine: data.addressLine ?? '',
            city: data.city ?? '',
            postalCode: data.postalCode ?? ''
          })
        }
      } catch (err) {
        if (active) {
          setError(err.message || 'Nie udało się pobrać profilu użytkownika')
        }
      } finally {
        if (active) {
          setLoading(false)
        }
      }
    }

    loadProfile()
    return () => {
      active = false
    }
  }, [user?.id])

  function handleProfileChange(event) {
    const { name, value } = event.target
    setProfile(prev => ({ ...prev, [name]: value }))
  }

  async function handleProfileSubmit(event) {
    event.preventDefault()
    setSuccess(null)
    setError(null)
    setSaving(true)

    try {
      const payload = {
        name: profile.name,
        email: profile.email,
        phoneNumber: profile.phoneNumber,
        addressLine: profile.addressLine,
        city: profile.city,
        postalCode: profile.postalCode
      }

      const data = await apiFetch('/api/me', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })

      setProfile({
        name: data.name ?? '',
        email: data.email ?? '',
        phoneNumber: data.phoneNumber ?? '',
        addressLine: data.addressLine ?? '',
        city: data.city ?? '',
        postalCode: data.postalCode ?? ''
      })
      setSuccess('Profil został zaktualizowany')
    } catch (err) {
      setError(err.message || 'Aktualizacja profilu nie powiodła się')
    } finally {
      setSaving(false)
    }
  }

  function handlePasswordChange(event) {
    const { name, value } = event.target
    setPasswordForm(prev => ({ ...prev, [name]: value }))
  }

  async function handlePasswordSubmit(event) {
    event.preventDefault()
    setPasswordError(null)
    setPasswordSuccess(null)

    if (passwordForm.newPassword !== passwordForm.confirmPassword) {
      setPasswordError('Nowe hasło i powtórzone hasło muszą być takie same')
      return
    }

    setChangingPassword(true)

    try {
      await apiFetch('/api/me/password', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          currentPassword: passwordForm.currentPassword,
          newPassword: passwordForm.newPassword
        })
      })
      setPasswordSuccess('Hasło zostało zmienione')
      setPasswordForm(initialPasswordForm)
    } catch (err) {
      setPasswordError(err.message || 'Nie udało się zmienić hasła')
    } finally {
      setChangingPassword(false)
    }
  }

  if (!user?.id) {
    return (
      <div className="page page--centered">
        <div className="surface-card empty-state">
          Aby zarządzać kontem, <Link to="/login">zaloguj się</Link> lub <Link to="/register">utwórz nowe konto</Link>.
        </div>
      </div>
    )
  }

  if (loading) {
    return (
      <div className="page">
        <div className="surface-card empty-state">Ładowanie profilu...</div>
      </div>
    )
  }

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Moje konto</h1>
          <p className="support-copy">Aktualizuj swoje dane kontaktowe, by biblioteka mogła się z Tobą skontaktować.</p>
        </div>
      </header>

      <div className="page-grid">
        <section className="surface-card form-card">
          <h2>Dane profilu</h2>
          <form onSubmit={handleProfileSubmit}>
            <div className="form-grid form-grid--two">
              <div>
                <label htmlFor="profile-name">Imię i nazwisko</label>
                <input
                  id="profile-name"
                  name="name"
                  value={profile.name}
                  onChange={handleProfileChange}
                  required
                />
              </div>
              <div>
                <label htmlFor="profile-email">Email</label>
                <input
                  id="profile-email"
                  name="email"
                  type="email"
                  value={profile.email}
                  onChange={handleProfileChange}
                  required
                />
              </div>
              <div>
                <label htmlFor="profile-phone">Telefon</label>
                <input
                  id="profile-phone"
                  name="phoneNumber"
                  value={profile.phoneNumber}
                  onChange={handleProfileChange}
                />
              </div>
              <div>
                <label htmlFor="profile-address">Adres</label>
                <input
                  id="profile-address"
                  name="addressLine"
                  value={profile.addressLine}
                  onChange={handleProfileChange}
                />
              </div>
              <div>
                <label htmlFor="profile-city">Miasto</label>
                <input
                  id="profile-city"
                  name="city"
                  value={profile.city}
                  onChange={handleProfileChange}
                />
              </div>
              <div>
                <label htmlFor="profile-postal">Kod pocztowy</label>
                <input
                  id="profile-postal"
                  name="postalCode"
                  value={profile.postalCode}
                  onChange={handleProfileChange}
                />
              </div>
            </div>
            <div className="form-actions">
              <button type="submit" className="btn btn-primary" disabled={saving}>
                {saving ? 'Zapisywanie...' : 'Zapisz zmiany'}
              </button>
              {success && <span className="success">{success}</span>}
            </div>
            {error && <div className="error">{error}</div>}
          </form>
        </section>

        <section className="surface-card form-card">
          <h2>Zmień hasło</h2>
          <form onSubmit={handlePasswordSubmit}>
            <div className="form-grid">
              <div>
                <label htmlFor="password-current">Aktualne hasło</label>
                <input
                  id="password-current"
                  name="currentPassword"
                  type="password"
                  value={passwordForm.currentPassword}
                  onChange={handlePasswordChange}
                  autoComplete="current-password"
                  required
                />
              </div>
              <div>
                <label htmlFor="password-new">Nowe hasło</label>
                <input
                  id="password-new"
                  name="newPassword"
                  type="password"
                  value={passwordForm.newPassword}
                  onChange={handlePasswordChange}
                  autoComplete="new-password"
                  minLength={8}
                  required
                />
              </div>
              <div>
                <label htmlFor="password-confirm">Powtórz nowe hasło</label>
                <input
                  id="password-confirm"
                  name="confirmPassword"
                  type="password"
                  value={passwordForm.confirmPassword}
                  onChange={handlePasswordChange}
                  autoComplete="new-password"
                  minLength={8}
                  required
                />
              </div>
            </div>
            <div className="form-actions">
              <button type="submit" className="btn btn-outline" disabled={changingPassword}>
                {changingPassword ? 'Aktualizowanie...' : 'Zmień hasło'}
              </button>
              {passwordSuccess && <span className="success">{passwordSuccess}</span>}
            </div>
            {passwordError && <div className="error">{passwordError}</div>}
          </form>
        </section>
      </div>
    </div>
  )
}
