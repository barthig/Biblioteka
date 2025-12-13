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
  postalCode: '',
  pesel: '',
  cardNumber: '',
  cardExpiry: '',
  accountStatus: '',
  defaultBranch: ''
}

const initialPasswordForm = {
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
}

const initialPinForm = {
  currentPin: '',
  newPin: '',
  confirmPin: ''
}

export default function Profile() {
  const { user } = useAuth()
  const [profile, setProfile] = useState(blankProfile)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)
  const [passwordForm, setPasswordForm] = useState(initialPasswordForm)
  const [pinForm, setPinForm] = useState(initialPinForm)
  const [saving, setSaving] = useState(false)
  const [activeTab, setActiveTab] = useState('security')
  
  // Preferences
  const [newsletter, setNewsletter] = useState(false)
  const [keepHistory, setKeepHistory] = useState(false)
  const [notifications, setNotifications] = useState({
    emailLoans: true,
    emailReservations: true,
    emailFines: true,
    emailAnnouncements: false
  })
  const [preferredContact, setPreferredContact] = useState('email')
  
  // UI preferences
  const [theme, setTheme] = useState('auto')
  const [fontSize, setFontSize] = useState('standard')
  const [language, setLanguage] = useState('pl')
  
  const [profileImage, setProfileImage] = useState(null)
  const [imagePreview, setImagePreview] = useState(null)

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
            postalCode: data.postalCode ?? '',
            pesel: data.pesel ?? '',
            cardNumber: data.cardNumber ?? '',
            cardExpiry: data.cardExpiry ?? '',
            accountStatus: data.accountStatus ?? 'Aktywne',
            defaultBranch: data.defaultBranch ?? ''
          })
          setNewsletter(data.newsletter ?? false)
          setKeepHistory(data.keepHistory ?? false)
          setNotifications({
            emailLoans: data.emailLoans ?? true,
            emailReservations: data.emailReservations ?? true,
            emailFines: data.emailFines ?? true,
            emailAnnouncements: data.emailAnnouncements ?? false
          })
          setPreferredContact(data.preferredContact ?? 'email')
          setTheme(data.theme ?? 'auto')
          setFontSize(data.fontSize ?? 'standard')
          setLanguage(data.language ?? 'pl')
          if (data.profileImage) {
            setImagePreview(data.profileImage)
          }
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

  async function handleContactSubmit(event) {
    event.preventDefault()
    setSuccess(null)
    setError(null)
    setSaving(true)

    try {
      const payload = {
        phoneNumber: profile.phoneNumber,
        addressLine: profile.addressLine,
        city: profile.city,
        postalCode: profile.postalCode,
        preferredContact: preferredContact
      }

      const data = await apiFetch('/api/me/contact', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })

      setSuccess('Dane kontaktowe zostały zaktualizowane')
    } catch (err) {
      setError(err.message || 'Aktualizacja nie powiodła się')
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
    setError(null)
    setSuccess(null)

    if (passwordForm.newPassword !== passwordForm.confirmPassword) {
      setError('Nowe hasło i powtórzone hasło muszą być takie same')
      return
    }

    setSaving(true)

    try {
      await apiFetch('/api/me/password', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          currentPassword: passwordForm.currentPassword,
          newPassword: passwordForm.newPassword
        })
      })
      setSuccess('Hasło zostało zmienione')
      setPasswordForm(initialPasswordForm)
    } catch (err) {
      setError(err.message || 'Nie udało się zmienić hasła')
    } finally {
      setSaving(false)
    }
  }

  function handlePinChange(event) {
    const { name, value } = event.target
    setPinForm(prev => ({ ...prev, [name]: value }))
  }

  async function handlePinSubmit(event) {
    event.preventDefault()
    setError(null)
    setSuccess(null)

    if (pinForm.newPin !== pinForm.confirmPin) {
      setError('Nowy PIN i powtórzony PIN muszą być takie same')
      return
    }

    setSaving(true)

    try {
      await apiFetch('/api/me/pin', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          currentPin: pinForm.currentPin,
          newPin: pinForm.newPin
        })
      })
      setSuccess('PIN został zmieniony')
      setPinForm(initialPinForm)
    } catch (err) {
      setError(err.message || 'Nie udało się zmienić PIN')
    } finally {
      setSaving(false)
    }
  }

  function handleImageChange(event) {
    const file = event.target.files[0]
    if (file) {
      setProfileImage(file)
      const reader = new FileReader()
      reader.onloadend = () => {
        setImagePreview(reader.result)
      }
      reader.readAsDataURL(file)
    }
  }

  async function handlePreferencesSubmit(event) {
    event.preventDefault()
    setSuccess(null)
    setError(null)
    setSaving(true)

    try {
      const payload = {
        defaultBranch: profile.defaultBranch,
        newsletter,
        keepHistory,
        ...notifications
      }

      await apiFetch('/api/me/preferences', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      setSuccess('Preferencje zostały zapisane')
    } catch (err) {
      setError(err.message || 'Nie udało się zapisać preferencji')
    } finally {
      setSaving(false)
    }
  }

  async function handleUIPreferences(event) {
    event.preventDefault()
    setSuccess(null)
    setError(null)
    setSaving(true)

    try {
      await apiFetch('/api/me/ui-preferences', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ theme, fontSize, language })
      })
      setSuccess('Ustawienia interfejsu zostały zapisane')
    } catch (err) {
      setError(err.message || 'Nie udało się zapisać ustawień')
    } finally {
      setSaving(false)
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
          <p className="support-copy">Zarządzaj swoim profilem, bezpieczeństwem i ustawieniami.</p>
        </div>
      </header>

      {error && (
        <div className="surface-card">
          <p className="error">{error}</p>
        </div>
      )}
      {success && (
        <div className="surface-card">
          <p className="success">{success}</p>
        </div>
      )}

      {/* Tabs */}
      <div className="tabs">
        <button
          onClick={() => setActiveTab('security')}
          className={`tab ${activeTab === 'security' ? 'tab--active' : ''}`}
        >
          🔒 Logowanie i bezpieczeństwo
        </button>
        <button
          onClick={() => setActiveTab('contact')}
          className={`tab ${activeTab === 'contact' ? 'tab--active' : ''}`}
        >
          📞 Dane kontaktowe
        </button>
        <button
          onClick={() => setActiveTab('preferences')}
          className={`tab ${activeTab === 'preferences' ? 'tab--active' : ''}`}
        >
          ⭐ Preferencje biblioteczne
        </button>
        <button
          onClick={() => setActiveTab('ui')}
          className={`tab ${activeTab === 'ui' ? 'tab--active' : ''}`}
        >
          🎨 Dostępność i interfejs
        </button>
        <button
          onClick={() => setActiveTab('account')}
          className={`tab ${activeTab === 'account' ? 'tab--active' : ''}`}
        >
          👤 Informacje o koncie
        </button>
      </div>

      {/* Security Tab */}
      {activeTab === 'security' && (
        <div className="form-section">
          <div className="form-section__header">
            <div className="form-section__icon">🔐</div>
            <div>
              <h2 className="form-section__title">Bezpieczeństwo konta</h2>
              <p className="form-section__description">Zarządzaj hasłem, PIN i aktywymi sesjami</p>
            </div>
          </div>

          {/* Change Password */}
          <form onSubmit={handlePasswordSubmit}>
            <h3>Zmiana hasła</h3>
            <div className="form-row">
              <div className="form-field">
                <label htmlFor="password-current">Obecne hasło</label>
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
            </div>
            <div className="form-row form-row--two">
              <div className="form-field">
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
                <small className="support-copy">Min. 8 znaków, zalecane cyfry i znaki specjalne</small>
              </div>
              <div className="form-field">
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
              <button type="submit" className="btn btn-primary" disabled={saving}>
                {saving ? 'Zapisywanie...' : 'Zmień hasło'}
              </button>
            </div>
          </form>

          <hr style={{ margin: '2rem 0', border: 'none', borderTop: '1px solid var(--color-border)' }} />

          {/* Change PIN */}
          <form onSubmit={handlePinSubmit}>
            <h3>PIN do samodzielnego wypożyczania</h3>
            <p className="support-copy">4-cyfrowy kod do kiosków samoobsługowych</p>
            <div className="form-row form-row--two">
              <div className="form-field">
                <label htmlFor="pin-current">Obecny PIN</label>
                <input
                  id="pin-current"
                  name="currentPin"
                  type="password"
                  pattern="[0-9]{4}"
                  maxLength={4}
                  value={pinForm.currentPin}
                  onChange={handlePinChange}
                  required
                />
              </div>
            </div>
            <div className="form-row form-row--two">
              <div className="form-field">
                <label htmlFor="pin-new">Nowy PIN</label>
                <input
                  id="pin-new"
                  name="newPin"
                  type="password"
                  pattern="[0-9]{4}"
                  maxLength={4}
                  value={pinForm.newPin}
                  onChange={handlePinChange}
                  required
                />
              </div>
              <div className="form-field">
                <label htmlFor="pin-confirm">Powtórz nowy PIN</label>
                <input
                  id="pin-confirm"
                  name="confirmPin"
                  type="password"
                  pattern="[0-9]{4}"
                  maxLength={4}
                  value={pinForm.confirmPin}
                  onChange={handlePinChange}
                  required
                />
              </div>
            </div>
            <div className="form-actions">
              <button type="submit" className="btn btn-primary" disabled={saving}>
                {saving ? 'Zapisywanie...' : 'Zmień PIN'}
              </button>
            </div>
          </form>

          <hr style={{ margin: '2rem 0', border: 'none', borderTop: '1px solid var(--color-border)' }} />

          {/* Active Sessions */}
          <div>
            <h3>Aktywne sesje</h3>
            <p className="support-copy">Zarządzaj urządzeniami zalogowanymi do Twojego konta</p>
            <div className="form-actions">
              <button type="button" className="btn btn-ghost">
                Wyloguj ze wszystkich urządzeń
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Contact Tab */}
      {activeTab === 'contact' && (
        <div className="form-section">
          <div className="form-section__header">
            <div className="form-section__icon">📧</div>
            <div>
              <h2 className="form-section__title">Dane kontaktowe</h2>
              <p className="form-section__description">Utrzymuj aktualny adres i telefon dla powiadomień</p>
            </div>
          </div>

          <form onSubmit={handleContactSubmit}>
            <div className="form-row form-row--two">
              <div className="form-field">
                <label htmlFor="contact-phone">Numer telefonu</label>
                <input
                  id="contact-phone"
                  name="phoneNumber"
                  type="tel"
                  value={profile.phoneNumber}
                  onChange={handleProfileChange}
                  placeholder="+48 123 456 789"
                />
              </div>
              <div className="form-field">
                <label htmlFor="contact-method">Preferowana metoda kontaktu</label>
                <select
                  id="contact-method"
                  value={preferredContact}
                  onChange={e => setPreferredContact(e.target.value)}
                >
                  <option value="email">E-mail</option>
                  <option value="sms">SMS</option>
                  <option value="phone">Telefon</option>
                  <option value="mail">Poczta tradycyjna</option>
                </select>
              </div>
            </div>

            <h3 style={{ marginTop: '2rem' }}>Adres zamieszkania</h3>
            <p className="support-copy">
              Zmiana adresu może wymagać ponownej weryfikacji karty przy najbliższej wizycie w bibliotece.
            </p>

            <div className="form-row">
              <div className="form-field">
                <label htmlFor="contact-address">Ulica i numer domu/mieszkania</label>
                <input
                  id="contact-address"
                  name="addressLine"
                  value={profile.addressLine}
                  onChange={handleProfileChange}
                  placeholder="ul. Przykładowa 123/45"
                />
              </div>
            </div>

            <div className="form-row form-row--two">
              <div className="form-field">
                <label htmlFor="contact-city">Miejscowość</label>
                <input
                  id="contact-city"
                  name="city"
                  value={profile.city}
                  onChange={handleProfileChange}
                />
              </div>
              <div className="form-field">
                <label htmlFor="contact-postal">Kod pocztowy</label>
                <input
                  id="contact-postal"
                  name="postalCode"
                  pattern="[0-9]{2}-[0-9]{3}"
                  value={profile.postalCode}
                  onChange={handleProfileChange}
                  placeholder="00-000"
                />
              </div>
            </div>

            <div className="form-actions">
              <button type="submit" className="btn btn-primary" disabled={saving}>
                {saving ? 'Zapisywanie...' : 'Zapisz dane kontaktowe'}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Preferences Tab */}
      {activeTab === 'preferences' && (
        <div className="form-section">
          <div className="form-section__header">
            <div className="form-section__icon">📚</div>
            <div>
              <h2 className="form-section__title">Preferencje biblioteczne</h2>
              <p className="form-section__description">Dostosuj sposób działania biblioteki do swoich potrzeb</p>
            </div>
          </div>

          <form onSubmit={handlePreferencesSubmit}>
            {/* Default Branch */}
            <div className="form-field">
              <label htmlFor="pref-branch">Domyślna filia odbioru</label>
              <select
                id="pref-branch"
                name="defaultBranch"
                value={profile.defaultBranch}
                onChange={handleProfileChange}
              >
                <option value="">Wybierz filię</option>
                <option value="main">Filia Główna - Centrum</option>
                <option value="north">Filia Północna - Osiedle Słoneczne</option>
                <option value="south">Filia Południowa - Park Miejski</option>
                <option value="east">Filia Wschodnia - Galeria Handlowa</option>
              </select>
              <small className="support-copy">Rezerwacje domyślnie trafiają do wybranej filii</small>
            </div>

            <h3 style={{ marginTop: '2rem' }}>Powiadomienia e-mail</h3>
            <div className="checkbox-field">
              <input
                type="checkbox"
                id="notif-loans"
                checked={notifications.emailLoans}
                onChange={e => setNotifications(prev => ({ ...prev, emailLoans: e.target.checked }))}
              />
              <label htmlFor="notif-loans">
                <strong>Przypomnienia o zbliżających się terminach zwrotu</strong>
                <div className="support-copy">Otrzymuj powiadomienie 3 dni przed terminem</div>
              </label>
            </div>

            <div className="checkbox-field">
              <input
                type="checkbox"
                id="notif-reservations"
                checked={notifications.emailReservations}
                onChange={e => setNotifications(prev => ({ ...prev, emailReservations: e.target.checked }))}
              />
              <label htmlFor="notif-reservations">
                <strong>Informacje o dostępności zarezerwowanych książek</strong>
                <div className="support-copy">Powiadomienie, gdy książka czeka na odbiór</div>
              </label>
            </div>

            <div className="checkbox-field">
              <input
                type="checkbox"
                id="notif-fines"
                checked={notifications.emailFines}
                onChange={e => setNotifications(prev => ({ ...prev, emailFines: e.target.checked }))}
              />
              <label htmlFor="notif-fines">
                <strong>Informacje o nowych karach za opóźnienia</strong>
                <div className="support-copy">Alerty o naliczonych opłatach</div>
              </label>
            </div>

            <div className="checkbox-field">
              <input
                type="checkbox"
                id="notif-newsletter"
                checked={newsletter}
                onChange={e => setNewsletter(e.target.checked)}
              />
              <label htmlFor="notif-newsletter">
                <strong>Newsletter biblioteki</strong>
                <div className="support-copy">Nowości, wydarzenia i zmiany w działalności</div>
              </label>
            </div>

            <div className="checkbox-field">
              <input
                type="checkbox"
                id="notif-announcements"
                checked={notifications.emailAnnouncements}
                onChange={e => setNotifications(prev => ({ ...prev, emailAnnouncements: e.target.checked }))}
              />
              <label htmlFor="notif-announcements">
                <strong>Ogłoszenia specjalne</strong>
                <div className="support-copy">Ważne informacje o zmianach godzin otwarcia, remontach itp.</div>
              </label>
            </div>

            <h3 style={{ marginTop: '2rem' }}>Historia wypożyczeń</h3>
            <div className="checkbox-field">
              <input
                type="checkbox"
                id="pref-history"
                checked={keepHistory}
                onChange={e => setKeepHistory(e.target.checked)}
              />
              <label htmlFor="pref-history">
                <strong>Przechowuj historię zwróconych książek</strong>
                <div className="support-copy">
                  Zgodnie z RODO domyślnie nie przechowujemy historii. Zaznacz, aby zobaczyć co przeczytałeś.
                </div>
              </label>
            </div>

            <div className="form-actions">
              <button type="submit" className="btn btn-primary" disabled={saving}>
                {saving ? 'Zapisywanie...' : 'Zapisz preferencje'}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* UI Tab */}
      {activeTab === 'ui' && (
        <div className="form-section">
          <div className="form-section__header">
            <div className="form-section__icon">🎨</div>
            <div>
              <h2 className="form-section__title">Dostępność i interfejs</h2>
              <p className="form-section__description">Dostosuj wygląd i język systemu</p>
            </div>
          </div>

          <form onSubmit={handleUIPreferences}>
            <div className="form-row form-row--two">
              <div className="form-field">
                <label htmlFor="ui-theme">Motyw kolorystyczny</label>
                <select
                  id="ui-theme"
                  value={theme}
                  onChange={e => setTheme(e.target.value)}
                >
                  <option value="auto">Automatyczny (systemowy)</option>
                  <option value="light">Jasny</option>
                  <option value="dark">Ciemny</option>
                  <option value="contrast">Wysoki kontrast (dla słabowidzących)</option>
                </select>
              </div>

              <div className="form-field">
                <label htmlFor="ui-font">Wielkość czcionki</label>
                <select
                  id="ui-font"
                  value={fontSize}
                  onChange={e => setFontSize(e.target.value)}
                >
                  <option value="standard">Standardowa</option>
                  <option value="large">Powiększona</option>
                  <option value="xlarge">Bardzo duża</option>
                </select>
              </div>
            </div>

            <div className="form-field">
              <label htmlFor="ui-lang">Język interfejsu</label>
              <select
                id="ui-lang"
                value={language}
                onChange={e => setLanguage(e.target.value)}
              >
                <option value="pl">Polski</option>
                <option value="en">English</option>
                <option value="ua">Українська</option>
                <option value="de">Deutsch</option>
              </select>
            </div>

            <div className="form-actions">
              <button type="submit" className="btn btn-primary" disabled={saving}>
                {saving ? 'Zapisywanie...' : 'Zapisz ustawienia'}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Account Info Tab */}
      {activeTab === 'account' && (
        <div className="form-section">
          <div className="form-section__header">
            <div className="form-section__icon">ℹ️</div>
            <div>
              <h2 className="form-section__title">Informacje o koncie</h2>
              <p className="form-section__description">Dane tylko do odczytu - zmiana wymaga wizyty z dowodem</p>
            </div>
          </div>

          {/* Profile Image */}
          <div style={{ textAlign: 'center', marginBottom: '2rem' }}>
            {imagePreview ? (
              <img
                src={imagePreview}
                alt="Zdjęcie profilowe"
                style={{
                  width: '120px',
                  height: '120px',
                  borderRadius: '50%',
                  objectFit: 'cover',
                  border: '3px solid var(--color-border)'
                }}
              />
            ) : (
              <div
                style={{
                  width: '120px',
                  height: '120px',
                  borderRadius: '50%',
                  backgroundColor: 'rgba(255, 255, 255, 0.05)',
                  display: 'inline-flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  fontSize: '48px',
                  border: '3px solid var(--color-border)'
                }}
              >
                👤
              </div>
            )}
            <div style={{ marginTop: '1rem' }}>
              <label htmlFor="account-image" className="btn btn-ghost" style={{ cursor: 'pointer' }}>
                Zmień zdjęcie profilowe
              </label>
              <input
                id="account-image"
                type="file"
                accept="image/*"
                onChange={handleImageChange}
                style={{ display: 'none' }}
              />
            </div>
          </div>

          {/* Read-only fields */}
          <div className="form-row form-row--two">
            <div className="form-field form-field--readonly">
              <label>Imię i nazwisko</label>
              <div className="form-field__value">{profile.name || '—'}</div>
              <small className="support-copy">Zmiana wymaga wizyty z dowodem tożsamości</small>
            </div>

            <div className="form-field form-field--readonly">
              <label>Adres e-mail (login)</label>
              <div className="form-field__value">{profile.email || '—'}</div>
              <small className="support-copy">Kontakt z biblioteką w celu zmiany</small>
            </div>
          </div>

          <div className="form-row form-row--two">
            <div className="form-field form-field--readonly">
              <label>Numer karty bibliotecznej</label>
              <div className="form-field__value">{profile.cardNumber || '—'}</div>
            </div>

            <div className="form-field form-field--readonly">
              <label>PESEL</label>
              <div className="form-field__value">{profile.pesel ? `******${profile.pesel.slice(-5)}` : '—'}</div>
            </div>
          </div>

          <div className="form-row form-row--two">
            <div className="form-field form-field--readonly">
              <label>Data ważności konta</label>
              <div className="form-field__value">{profile.cardExpiry || '—'}</div>
            </div>

            <div className="form-field form-field--readonly">
              <label>Status konta</label>
              <div className="form-field__value">
                <span className={`status ${profile.accountStatus === 'Aktywne' ? 'status-active' : 'status-cancelled'}`}>
                  {profile.accountStatus || 'Aktywne'}
                </span>
              </div>
            </div>
          </div>

          <hr style={{ margin: '2rem 0', border: 'none', borderTop: '1px solid var(--color-border)' }} />

          {/* GDPR */}
          <div>
            <h3>Zgody i regulamin</h3>
            <div className="form-actions">
              <a href="/regulamin" className="btn btn-ghost" target="_blank" rel="noopener">
                📄 Regulamin biblioteki
              </a>
              <button type="button" className="btn btn-ghost" style={{ color: 'var(--color-danger)' }}>
                🗑️ Usuń konto
              </button>
            </div>
            <p className="support-copy" style={{ marginTop: '1rem' }}>
              Usunięcie konta jest możliwe po zwróceniu wszystkich książek i uregulowaniu kar.
            </p>
          </div>
        </div>
      )}
    </div>
  )
}