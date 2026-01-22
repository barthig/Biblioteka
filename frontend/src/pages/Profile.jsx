import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import toast from 'react-hot-toast'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'
import { ratingService } from '../services/ratingService'
import { applyUiPreferences, storeUiPreferences } from '../utils/uiPreferences'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import FeedbackCard from '../components/ui/FeedbackCard'
import SectionCard from '../components/ui/SectionCard'

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

export default function Profile() {
  const { user, refreshSession, logoutAll, fetchAuthProfile } = useAuth()
  const [profile, setProfile] = useState(blankProfile)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)
  const [passwordError, setPasswordError] = useState(null)
  const [passwordForm, setPasswordForm] = useState(initialPasswordForm)
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
  const [ratings, setRatings] = useState([])
  const [ratingsError, setRatingsError] = useState(null)
  const [ratingsLoading, setRatingsLoading] = useState(false)
  const [fees, setFees] = useState([])
  const [feesError, setFeesError] = useState(null)
  const [feesLoading, setFeesLoading] = useState(false)
  const [feesPayingId, setFeesPayingId] = useState(null)
  const [feesExpanded, setFeesExpanded] = useState(false)
  const [feeSearch, setFeeSearch] = useState('')
  const [sessionStatus, setSessionStatus] = useState(null)
  const [sessionLoading, setSessionLoading] = useState(false)

  async function refreshRatings() {
    if (!user?.id) return
    setRatingsLoading(true)
    try {
      const data = await ratingService.getMyRatings()
      const list = Array.isArray(data?.ratings)
        ? data.ratings
        : Array.isArray(data?.data)
          ? data.data
          : Array.isArray(data)
            ? data
            : []
      setRatings(list)
      setRatingsError(null)
    } catch (err) {
      const statusInfo = err?.status ? ` (HTTP ${err.status})` : ''
      setRatingsError(`${err?.message || 'Nie udalo sie pobrac ocen'}${statusInfo}`)
    } finally {
      setRatingsLoading(false)
    }
  }

  async function refreshFees() {
    setFeesLoading(true)
    setFeesError(null)
    try {
      const data = await apiFetch('/api/me/fees')
      const list = Array.isArray(data?.data)
        ? data.data
        : Array.isArray(data)
          ? data
          : []
      setFees(list)
    } catch (err) {
      const statusInfo = err?.status ? ` (HTTP ${err.status})` : ''
      setFeesError(`${err?.message || 'Nie udalo sie pobrac oplat'}${statusInfo}`)
    } finally {
      setFeesLoading(false)
    }
  }

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
  useEffect(() => {
    if (activeTab !== 'ratings' || !user?.id) return
    refreshRatings()
  }, [activeTab, user?.id])
  useEffect(() => {
    if (activeTab !== 'fees' || !user?.id) return
    refreshFees()
  }, [activeTab, user?.id])

  const normalizedFeeSearch = feeSearch.trim().toLowerCase()
  const filteredFees = normalizedFeeSearch
    ? fees.filter(fee => {
      const name = [
        fee.user?.name,
        fee.userName,
        fee.user?.firstName,
        fee.user?.lastName,
        fee.user?.fullName,
      ].filter(Boolean).join(' ').toLowerCase()
      return name.includes(normalizedFeeSearch)
    })
    : fees

  

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
    setPasswordError(null)

    if (passwordForm.newPassword !== passwordForm.confirmPassword) {
      setPasswordError('Nowe hasło i powtórzone hasło muszą być takie same')
      return
    }

    if (passwordForm.newPassword.length < 6) {
      setPasswordError('Nowe hasło musi mieć minimum 6 znaków')
      return
    }

    setSaving(true)

    try {
      await apiFetch('/api/users/me/password', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          oldPassword: passwordForm.currentPassword,
          newPassword: passwordForm.newPassword
        })
      })
      toast.success('Hasło zostało zmienione!')
      setPasswordError(null)
      setPasswordForm(initialPasswordForm)
    } catch (err) {
      setPasswordError(err.message || 'Nie udało się zmienić hasła')
    } finally {
      setSaving(false)
    }
  }

  async function handleRefreshSession() {
    setSessionLoading(true)
    setSessionStatus(null)
    setError(null)
    try {
      await refreshSession()
      setSessionStatus('Sesja została odświeżona')
    } catch (err) {
      setError(err.message || 'Nie udało się odświeżyć sesji')
    } finally {
      setSessionLoading(false)
    }
  }

  async function handleVerifySession() {
    setSessionLoading(true)
    setSessionStatus(null)
    setError(null)
    try {
      const data = await fetchAuthProfile()
      const label = data?.email ? `Sesja aktywna: ${data.email}` : 'Sesja aktywna'
      setSessionStatus(label)
    } catch (err) {
      setError(err.message || 'Nie udało się zweryfikować sesji')
    } finally {
      setSessionLoading(false)
    }
  }

  async function handleLogoutAllSessions() {
    setSessionLoading(true)
    setSessionStatus(null)
    setError(null)
    try {
      await logoutAll()
    } catch (err) {
      setError(err.message || 'Nie udało się wylogować wszystkich sesji')
    } finally {
      setSessionLoading(false)
    }
  }

  async function handleDeleteRating(bookId, ratingId) {
    setRatingsError(null)
    try {
      await ratingService.deleteRating(bookId, ratingId)
      setRatings(prev => prev.filter(r => r.id !== ratingId))
      refreshRatings()
    } catch (err) {
      setRatingsError(err.message || 'Nie udało się usunąć oceny')
    }
  }

  async function handlePayFee(feeId) {
    setFeesPayingId(feeId)
    setFeesError(null)
    try {
      await apiFetch(`/api/me/fees/${feeId}/pay`, { method: 'POST' })
      toast.success('Platnosc zostala zarejestrowana')
      refreshFees()
    } catch (err) {
      setFeesError(err.message || 'Nie udalo sie oplacic oplaty')
    } finally {
      setFeesPayingId(null)
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
      applyUiPreferences({ theme, fontSize, language })
      storeUiPreferences({ theme, fontSize, language })
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
      <PageHeader
        title="Moje konto"
        subtitle="Zarządzaj swoim profilem, bezpieczeństwem i ustawieniami."
      />

      <StatGrid>
        <StatCard title="Status konta" value={profile.accountStatus || 'Aktywne'} />
        <StatCard title="Ważność karty" value={profile.cardExpiry || '-'} />
        <StatCard title="Domyślna filia" value={profile.defaultBranch || '-'} />
      </StatGrid>

      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}
      {success && <FeedbackCard variant="success">{success}</FeedbackCard>}

      {/* Tabs */}
      <div className="tabs">
        <button
          onClick={() => setActiveTab('security')}
          className={`tab ${activeTab === 'security' ? 'tab--active' : ''}`}
        >
          Logowanie i bezpieczeństwo
        </button>
        <button
          onClick={() => setActiveTab('contact')}
          className={`tab ${activeTab === 'contact' ? 'tab--active' : ''}`}
        >
          Dane kontaktowe
        </button>
        <button
          onClick={() => setActiveTab('preferences')}
          className={`tab ${activeTab === 'preferences' ? 'tab--active' : ''}`}
        >
          Preferencje biblioteczne
        </button>
        <button
          onClick={() => setActiveTab('ui')}
          className={`tab ${activeTab === 'ui' ? 'tab--active' : ''}`}
        >
          Dostępność i interfejs
        </button>
        <button
          onClick={() => setActiveTab('account')}
          className={`tab ${activeTab === 'account' ? 'tab--active' : ''}`}
        >
          Informacje o koncie
        </button>
        <button
          onClick={() => setActiveTab('ratings')}
          className={`tab ${activeTab === 'ratings' ? 'tab--active' : ''}`}
        >
          Twoje oceny
        </button>
        <button
          onClick={() => setActiveTab('fees')}
          className={`tab ${activeTab === 'fees' ? 'tab--active' : ''}`}
        >
          Oplaty i platnosci
        </button>
      </div>

      {/* Security Tab */}
      {activeTab === 'security' && (
        <div className="form-section">
          <div className="form-section__header">
            <div className="form-section__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="18" height="18" role="img" focusable="false">
                <path d="M12 3l7 3v6c0 5.25-3.5 7.75-7 9-3.5-1.25-7-3.75-7-9V6l7-3z" fill="none" stroke="currentColor" strokeWidth="1.6" />
                <path d="M9 12l2 2 4-4" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </div>
            <div>
              <h2 className="form-section__title">Bezpieczeństwo konta</h2>
              <p className="form-section__description">Zarzadzaj haslem i aktywnymi sesjami</p>
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
              {passwordError && <p className="error">{passwordError}</p>}
              <button type="submit" className="btn btn-primary" disabled={saving}>
                {saving ? 'Zapisywanie...' : 'Zmień hasło'}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Contact Tab */}
      {activeTab === 'contact' && (
        <div className="form-section">
          <div className="form-section__header">
            <div className="form-section__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="18" height="18" role="img" focusable="false">
                <path d="M4 6c0-1.1.9-2 2-2h3l1 4-2 1c.6 1.3 1.7 2.4 3 3l1-2 4 1v3c0 1.1-.9 2-2 2h-1c-5.5 0-10-4.5-10-10V6z" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" />
              </svg>
            </div>
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
            <div className="form-section__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="18" height="18" role="img" focusable="false">
                <path d="M4 6h10M4 12h16M4 18h10" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
                <circle cx="16" cy="6" r="2" fill="none" stroke="currentColor" strokeWidth="1.6" />
                <circle cx="8" cy="18" r="2" fill="none" stroke="currentColor" strokeWidth="1.6" />
                <circle cx="14" cy="12" r="2" fill="none" stroke="currentColor" strokeWidth="1.6" />
              </svg>
            </div>
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
            <div className="form-section__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="18" height="18" role="img" focusable="false">
                <rect x="3" y="4" width="18" height="12" rx="2" ry="2" fill="none" stroke="currentColor" strokeWidth="1.6" />
                <path d="M8 20h8M12 16v4" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
              </svg>
            </div>
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
                  {/* <option value="contrast">Wysoki kontrast (dla słabowidzących)</option> */}
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
              </select>
              <small className="support-copy">
                Tłumaczenia interfejsu są w przygotowaniu — na razie zapisujemy tylko preferencję języka.
              </small>
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
            <div className="form-section__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="18" height="18" role="img" focusable="false">
                <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" strokeWidth="1.6" />
                <path d="M12 10v6M12 7h.01" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
              </svg>
            </div>
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
                U
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
                Regulamin biblioteki
              </a>
              <button type="button" className="btn btn-ghost" style={{ color: 'var(--color-danger)' }}>
                Usuń konto
              </button>
            </div>
            <p className="support-copy" style={{ marginTop: '1rem' }}>
              Usunięcie konta jest możliwe po zwróceniu wszystkich książek i uregulowaniu kar.
            </p>
          </div>
        </div>
      )}

      {activeTab === 'ratings' && (
        <SectionCard title="Twoje oceny">
          <div className="form-actions">
            <button type="button" className="btn btn-secondary" onClick={refreshRatings} disabled={ratingsLoading}>
              {ratingsLoading ? 'Odswiezanie...' : 'Odswiez oceny'}
            </button>
          </div>
        {ratingsError && <p className="error">{ratingsError}</p>}
        {ratings.length === 0 ? (
          <p>Nie masz jeszcze ocen.</p>
        ) : (
          <ul className="list list--bordered">
            {ratings.map(r => (
              <li key={r.id}>
                <div className="list__title">{r.book?.title || 'Książka'}</div>
                <div className="list__meta">
                  <span>Ocena: {r.rating}/5</span>
                  {r.createdAt && <span>{new Date(r.createdAt).toLocaleDateString('pl-PL')}</span>}
                </div>
                {r.id && r.book?.id && (
                  <button className="btn btn-outline btn-sm" onClick={() => handleDeleteRating(r.book.id, r.id)}>
                    Usuń ocenę
                  </button>
                )}
              </li>
            ))}
          </ul>
        )}
        </SectionCard>
      )}

      {activeTab === 'fees' && (
        <SectionCard
          className="fees-accordion"
          header={(
            <button
              type="button"
              className="fees-accordion__header"
              onClick={() => setFeesExpanded(prev => !prev)}
              aria-expanded={feesExpanded}
              aria-controls="fees-panel"
              aria-label={`${feesExpanded ? 'Zwin' : 'Rozwin'} oplaty i platnosci`}
            >
              <div>
                <h2>Oplaty i platnosci</h2>
                <p className="support-copy">Kliknij, aby {feesExpanded ? 'zwinac' : 'rozwinac'} sekcje oplat i kar.</p>
              </div>
              <div className="fees-accordion__meta">
                <span className="fees-accordion__count">{fees.length} pozycji</span>
                <span className={`fees-accordion__chevron ${feesExpanded ? 'is-open' : ''}`} aria-hidden>⌄</span>
              </div>
            </button>
          )}
        >
          <div
            id="fees-panel"
            className={`fees-accordion__body ${feesExpanded ? 'is-open' : ''}`}
            hidden={!feesExpanded}
          >
            <div className="fees-toolbar">
              <button type="button" className="btn btn-secondary" onClick={refreshFees} disabled={feesLoading}>
                {feesLoading ? 'Odswiezanie...' : 'Odswiez oplaty'}
              </button>
              <div className="fees-filter">
                <label htmlFor="fee-search" className="sr-only">Filtruj po imieniu i nazwisku</label>
                <input
                  id="fee-search"
                  type="search"
                  value={feeSearch}
                  onChange={event => setFeeSearch(event.target.value)}
                  placeholder="Filtruj po imieniu i nazwisku"
                />
              </div>
            </div>
            <div className="fees-note">
              <p className="support-copy">
                Aby oplacic zaleglosci, wybierz oplate z listy i ureguluj platnosc online lub postepuj zgodnie z instrukcja.
              </p>
            </div>
            <div className="surface-card fees-payment-card">
              <h3>Instrukcja platnosci</h3>
              <p className="support-copy">
                W tytule przelewu podaj numer karty lub identyfikator oplaty. Platnosci online sa ksiegowane zwykle w 1-2 dni robocze.
              </p>
              <div className="form-row form-row--two">
                <div className="form-field form-field--readonly">
                  <label>Odbiorca</label>
                  <div className="form-field__value">Miejska Biblioteka Publiczna</div>
                </div>
                <div className="form-field form-field--readonly">
                  <label>Numer konta</label>
                  <div className="form-field__value">PL00 0000 0000 0000 0000 0000 0000</div>
                </div>
              </div>
              <div className="form-row form-row--two">
                <div className="form-field form-field--readonly">
                  <label>Tytul przelewu</label>
                  <div className="form-field__value">Oplata biblioteczna / {profile.cardNumber || 'Numer karty'}</div>
                </div>
                <div className="form-field form-field--readonly">
                  <label>Przyklad</label>
                  <div className="form-field__value">Oplata biblioteczna / 123456</div>
                </div>
              </div>
              <div className="form-actions">
                <button type="button" className="btn btn-ghost">
                  Przejdz do platnosci online
                </button>
              </div>
            </div>
            {feesError && <p className="error">{feesError}</p>}
            {filteredFees.length === 0 ? (
              <p>Brak aktywnych oplat do uregulowania.</p>
            ) : (
              <ul className="fees-list">
                {filteredFees.map(fee => (
                  <li key={fee.id} className="fees-row">
                    <div className="fees-row__main">
                      <div className="fees-row__title">{fee.reason || 'Oplata biblioteczna'}</div>
                      <div className="fees-row__meta">
                        <span className="fees-row__amount">{fee.amount} {fee.currency || 'PLN'}</span>
                        {fee.createdAt && <span>{new Date(fee.createdAt).toLocaleDateString('pl-PL')}</span>}
                        {fee.paidAt && <span className="fees-row__status">Oplacona</span>}
                      </div>
                    </div>
                    {!fee.paidAt && fee.id && (
                      <div className="fees-row__actions">
                        <button
                          className="btn btn-primary btn-sm"
                          onClick={() => handlePayFee(fee.id)}
                          disabled={feesPayingId === fee.id}
                        >
                          {feesPayingId === fee.id ? 'Przetwarzanie...' : 'Ureguluj online'}
                        </button>
                      </div>
                    )}
                  </li>
                ))}
              </ul>
            )}
          </div>
        </SectionCard>
      )}
    </div>
  )
}



