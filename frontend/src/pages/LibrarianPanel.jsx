import React, { useEffect, useState } from 'react'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import FeedbackCard from '../components/ui/FeedbackCard'

export default function LibrarianPanel() {
  const { user } = useAuth()
  const isAdmin = user?.roles?.includes('ROLE_ADMIN')
  const [activeTab, setActiveTab] = useState('quickactions')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)
  
  // Quick search
  const [userSearchQuery, setUserSearchQuery] = useState('')
  const [userSearchResults, setUserSearchResults] = useState([])
  const [barcodeInput, setBarcodeInput] = useState('')
  
  // Loan creation form
  const [loanForm, setLoanForm] = useState({
    userId: '',
    bookId: '',
    copyId: '',
    dueDate: ''
  })
  
  // Active loans
  const [loans, setLoans] = useState([])
  const [loanLookupId, setLoanLookupId] = useState('')
  const [loanLookupUserId, setLoanLookupUserId] = useState('')
  const [loanLookupResult, setLoanLookupResult] = useState(null)
  const [loanLookupError, setLoanLookupError] = useState(null)
  
  // Collections
  const [collections, setCollections] = useState([])
  const [collectionForm, setCollectionForm] = useState({
    name: '',
    description: '',
    featured: false,
    displayOrder: 0,
    bookIds: []
  })
  const [editingCollection, setEditingCollection] = useState(null)
  const [collectionBookSearch, setCollectionBookSearch] = useState('')
  const [collectionBookResults, setCollectionBookResults] = useState([])

  // Statistics
  const [stats, setStats] = useState({
    activeLoans: 0,
    overdueLoans: 0,
    totalUsers: 0,
    availableCopies: 0
  })
  const [librarySettings, setLibrarySettings] = useState({
    loanLimitPerUser: '',
    loanDurationDays: '',
    notificationsEnabled: false
  })
  const [librarySettingsLoading, setLibrarySettingsLoading] = useState(false)

  // Reservations & fines
  const [reservations, setReservations] = useState([])
  const [fines, setFines] = useState([])

  // Inventory
  const [inventoryBookId, setInventoryBookId] = useState('')
  const [bookSearchQuery, setBookSearchQuery] = useState('')
  const [availableBooks, setAvailableBooks] = useState([])
  const [copies, setCopies] = useState([])
  const [copyForm, setCopyForm] = useState({ inventoryCode: '', status: 'AVAILABLE', accessType: 'STORAGE', location: '', condition: '' })

  useEffect(() => {
    if (activeTab === 'loans') {
      loadLoans()
    } else if (activeTab === 'stats') {
      loadStats()
      loadLibrarySettings()
    } else if (activeTab === 'reservations') {
      loadReservations()
    } else if (activeTab === 'fines') {
      loadFines()
    }
  }, [activeTab])

  async function searchUser(query) {
    if (!query || query.length < 2) {
      setUserSearchResults([])
      return
    }
    try {
      const data = await apiFetch(`/api/users/search?q=${encodeURIComponent(query)}`)
      setUserSearchResults(Array.isArray(data) ? data : data?.data || [])
    } catch (err) {
      setError(err.message || 'Nie udało się wyszukać użytkownika')
    }
  }

  async function handleBarcodeScan(code) {
    if (!code) return
    setError(null)
    setSuccess(null)
    try {
      // Try to find book copy by barcode
      const copyData = await apiFetch(`/api/admin/copies/barcode/${encodeURIComponent(code)}`)
      if (copyData) {
        setSuccess(`Znaleziono egzemplarz: ${copyData.book?.title || 'Nieznany tytuł'} (Kod: ${code})`)
        // Auto-fill loan form if in that tab
        if (activeTab === 'loans') {
          setLoanForm(prev => ({ ...prev, copyId: copyData.id }))
        }
      }
    } catch (err) {
      setError(err.message || 'Nie znaleziono egzemplarza o tym kodzie kreskowym')
    } finally {
      setBarcodeInput('')
    }
  }

  async function loadLoans() {
    setLoading(true)
    setError(null)
    try {
      const data = await apiFetch('/api/loans')
      setLoans(Array.isArray(data) ? data : data.data || [])
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać wypożyczeń')
    } finally {
      setLoading(false)
    }
  }

  async function loadLoanById() {
    if (!loanLookupId) return
    setLoanLookupError(null)
    setLoanLookupResult(null)
    try {
      const data = await apiFetch(`/api/loans/${loanLookupId}`)
      setLoanLookupResult(Array.isArray(data) ? data : [data])
    } catch (err) {
      setLoanLookupError(err.message || 'Nie udalo sie pobrac wypozyczenia')
    }
  }

  async function loadLoansByUser() {
    if (!loanLookupUserId) return
    setLoanLookupError(null)
    setLoanLookupResult(null)
    try {
      const data = await apiFetch(`/api/loans/user/${loanLookupUserId}`)
      setLoanLookupResult(Array.isArray(data) ? data : data.data || [])
    } catch (err) {
      setLoanLookupError(err.message || 'Nie udalo sie pobrac wypozyczen uzytkownika')
    }
  }

  async function loadStats() {
    setLoading(true)
    setError(null)
    try {
      const data = await apiFetch('/api/reports/usage')
      setStats({
        activeLoans: data.loans || 0,
        overdueLoans: data.overdueLoans || 0,
        totalUsers: data.activeUsers || 0,
        availableCopies: data.availableCopies || 0
      })
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać statystyk')
    } finally {
      setLoading(false)
    }
  }

  async function loadLibrarySettings() {
    setLibrarySettingsLoading(true)
    setError(null)
    try {
      const data = await apiFetch('/api/settings')
      setLibrarySettings({
        loanLimitPerUser: data?.loanLimitPerUser ?? '',
        loanDurationDays: data?.loanDurationDays ?? '',
        notificationsEnabled: !!data?.notificationsEnabled
      })
    } catch (err) {
      setError(err.message || 'Nie udalo sie pobrac ustawien')
    } finally {
      setLibrarySettingsLoading(false)
    }
  }

  async function updateLibrarySettings(e) {
    e.preventDefault()
    setError(null)
    setSuccess(null)
    try {
      const payload = {
        loanLimitPerUser: Number(librarySettings.loanLimitPerUser),
        loanDurationDays: Number(librarySettings.loanDurationDays),
        notificationsEnabled: !!librarySettings.notificationsEnabled
      }
      const data = await apiFetch('/api/settings', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      setLibrarySettings({
        loanLimitPerUser: data?.settings?.loanLimitPerUser ?? payload.loanLimitPerUser,
        loanDurationDays: data?.settings?.loanDurationDays ?? payload.loanDurationDays,
        notificationsEnabled: data?.settings?.notificationsEnabled ?? payload.notificationsEnabled
      })
      setSuccess('Zapisano ustawienia')
    } catch (err) {
      setError(err.message || 'Nie udalo sie zapisac ustawien')
    }
  }

  async function loadReservations() {
    setLoading(true)
    setError(null)
    try {
      const data = await apiFetch('/api/reservations?history=true&limit=50')
      setReservations(Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [])
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać rezerwacji')
    } finally {
      setLoading(false)
    }
  }

  async function cancelReservation(reservationId) {
    if (!confirm('Anulować tę rezerwację?')) return
    setLoading(true)
    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/reservations/${reservationId}`, { method: 'DELETE' })
      setSuccess('Rezerwacja została anulowana')
      loadReservations()
    } catch (err) {
      setError(err.message || 'Nie udało się anulować rezerwacji')
    } finally {
      setLoading(false)
    }
  }

  async function fulfillReservation(reservationId) {
    if (!confirm('Zrealizować tę rezerwację? Użytkownik otrzyma wypożyczenie.')) return
    setLoading(true)
    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/reservations/${reservationId}/fulfill`, { method: 'POST' })
      setSuccess('Rezerwacja została zrealizowana')
      loadReservations()
    } catch (err) {
      setError(err.message || 'Nie udało się zrealizować rezerwacji')
    } finally {
      setLoading(false)
    }
  }

  async function searchBooks(query) {
    if (!query || query.length < 2) {
      setAvailableBooks([])
      return
    }
    try {
      const data = await apiFetch(`/api/books?q=${encodeURIComponent(query)}&limit=10`)
      setAvailableBooks(Array.isArray(data?.data) ? data.data : [])
    } catch (err) {
      console.error('Błąd wyszukiwania książek:', err)
    }
  }

  async function loadFines() {
    setLoading(true)
    setError(null)
    try {
      const data = await apiFetch('/api/fines?limit=50')
      setFines(Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [])
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać opłat')
    } finally {
      setLoading(false)
    }
  }

  async function payFine(fineId) {
    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/fines/${fineId}/pay`, { method: 'POST' })
      setSuccess('Opłata została oznaczona jako opłacona')
      loadFines()
    } catch (err) {
      setError(err.message || 'Nie udało się opłacić należności')
    }
  }

  async function cancelFine(fineId) {
    if (!confirm('Anulować tę należność?')) return
    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/fines/${fineId}`, { method: 'DELETE' })
      setSuccess('Opłata została anulowana')
      loadFines()
    } catch (err) {
      setError(err.message || 'Nie udało się anulować opłaty')
    }
  }

  async function loadCopies(bookId) {
    if (!bookId) return
    setLoading(true)
    setError(null)
    try {
      const data = await apiFetch(`/api/admin/books/${bookId}/copies`)
      setCopies(Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [])
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać egzemplarzy')
    } finally {
      setLoading(false)
    }
  }

  async function addCopy(e) {
    e.preventDefault()
    if (!inventoryBookId) {
      setError('Podaj ID książki, do której dodajesz egzemplarz')
      return
    }
    setLoading(true)
    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/admin/books/${inventoryBookId}/copies`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(copyForm)
      })
      setSuccess('Egzemplarz został dodany')
      setCopyForm({ inventoryCode: '', status: 'AVAILABLE', accessType: 'STORAGE', location: '', condition: '' })
      loadCopies(inventoryBookId)
    } catch (err) {
      setError(err.message || 'Nie udało się dodać egzemplarza')
    } finally {
      setLoading(false)
    }
  }

  async function updateCopy(copy) {
    const status = prompt('Status', copy.status || copy.state || 'AVAILABLE')
    if (status === null) return
    const accessType = prompt('Access type', copy.accessType || 'STORAGE')
    if (accessType === null) return
    const location = prompt('Location', copy.location || '')
    if (location === null) return
    const condition = prompt('Condition', copy.conditionState || copy.condition || '')
    if (condition === null) return

    try {
      const bookId = inventoryBookId || copy.bookId || copy.book?.id
      await apiFetch(`/api/admin/books/${bookId}/copies/${copy.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status, accessType, location, conditionState: condition })
      })
      setSuccess('Zaktualizowano egzemplarz')
      loadCopies(bookId)
    } catch (err) {
      setError(err.message || 'Nie udalo sie zaktualizowac egzemplarza')
    }
  }

  async function deleteCopy(copy) {
    if (!confirm('Usunac egzemplarz?')) return
    try {
      const bookId = inventoryBookId || copy.bookId || copy.book?.id
      await apiFetch(`/api/admin/books/${bookId}/copies/${copy.id}`, { method: 'DELETE' })
      setSuccess('Usunieto egzemplarz')
      loadCopies(bookId)
    } catch (err) {
      setError(err.message || 'Nie udalo sie usunac egzemplarza')
    }
  }

  async function handleCreateLoan(e) {
    e.preventDefault()
    setLoading(true)
    setError(null)
    setSuccess(null)
    
    try {
      await apiFetch('/api/loans', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          userId: parseInt(loanForm.userId),
          bookId: parseInt(loanForm.bookId),
          bookCopyId: parseInt(loanForm.copyId),
          dueDate: loanForm.dueDate
        })
      })
      
      setSuccess('Wypożyczenie zostało utworzone pomyślnie')
      setLoanForm({ userId: '', bookId: '', copyId: '', dueDate: '' })
      loadLoans()
    } catch (err) {
      setError(err.message || 'Nie udało się utworzyć wypożyczenia')
    } finally {
      setLoading(false)
    }
  }

  const [returnModal, setReturnModal] = useState({ show: false, loan: null, fine: null })

  async function handleReturnLoan(loan) {
    // Check if overdue and calculate fine
    const now = new Date()
    const dueDate = new Date(loan.dueAt)
    const isOverdue = now > dueDate && !loan.returnedAt
    
    let fineAmount = 0
    let daysOverdue = 0
    
    if (isOverdue) {
      daysOverdue = Math.floor((now - dueDate) / (1000 * 60 * 60 * 24))
      fineAmount = daysOverdue * 0.50 // 0.50 PLN per day
    }
    
    setReturnModal({
      show: true,
      loan,
      fine: isOverdue ? { amount: fineAmount, days: daysOverdue } : null
    })
  }

  async function confirmReturn() {
    const { loan, fine } = returnModal
    setReturnModal({ show: false, loan: null, fine: null })
    
    setLoading(true)
    setError(null)
    setSuccess(null)

    try {
      const result = await apiFetch(`/api/loans/${loan.id}/return`, {
        method: 'PUT'
      })
      
      if (fine) {
        setSuccess(`Książka została zwrócona. Nałożono karę: ${fine.amount.toFixed(2)} PLN za ${fine.days} dni opóźnienia.`)
      } else {
        setSuccess('Książka została zwrócona')
      }
      
      loadLoans()
    } catch (err) {
      setError(err.message || 'Nie udało się zwrócić książki')
    } finally {
      setLoading(false)
    }
  }

  async function loadCollections() {
    try {
      const data = await apiFetch('/api/collections')
      setCollections(data.collections || [])
    } catch (err) {
      setError(err.message)
    }
  }

  async function searchBooksForCollection(query) {
    if (!query || query.length < 2) {
      setCollectionBookResults([])
      return
    }
    try {
      const data = await apiFetch(`/api/books?q=${encodeURIComponent(query)}`)
      setCollectionBookResults(data.data || [])
    } catch (err) {
      console.error('Book search failed:', err)
    }
  }

  async function saveCollection() {
    setLoading(true)
    setError(null)
    try {
      if (editingCollection) {
        await apiFetch(`/api/collections/${editingCollection}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(collectionForm)
        })
        setSuccess('Kolekcja zaktualizowana')
      } else {
        await apiFetch('/api/collections', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(collectionForm)
        })
        setSuccess('Kolekcja utworzona')
      }
      
      setCollectionForm({ name: '', description: '', featured: false, displayOrder: 0, bookIds: [] })
      setEditingCollection(null)
      loadCollections()
    } catch (err) {
      setError(err.message)
    } finally {
      setLoading(false)
    }
  }

  async function deleteCollection(id) {
    if (!confirm('Czy na pewno usunąć kolekcję?')) return
    
    try {
      await apiFetch(`/api/collections/${id}`, { method: 'DELETE' })
      setSuccess('Kolekcja usunięta')
      loadCollections()
    } catch (err) {
      setError(err.message)
    }
  }

  function toggleBookInCollection(bookId) {
    setCollectionForm(prev => ({
      ...prev,
      bookIds: prev.bookIds.includes(bookId)
        ? prev.bookIds.filter(id => id !== bookId)
        : [...prev.bookIds, bookId]
    }))
  }

  useEffect(() => {
    if (activeTab === 'collections') {
      loadCollections()
    }
  }, [activeTab])

  return (
    <div className="page">
      <PageHeader
        title="Panel bibliotekarza"
        subtitle="Obsługa wypożyczeń i zarządzanie biblioteką"
      />

      <StatGrid>
        <StatCard title="Aktywne wypożyczenia" value={stats.activeLoans ?? 0} subtitle="Do obsługi" />
        <StatCard title="Przeterminowane" value={stats.overdueLoans ?? 0} subtitle="Wymagają kontaktu" />
        <StatCard title="Rezerwacje" value={reservations.length} subtitle="Oczekujące" />
      </StatGrid>

      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}
      {success && <FeedbackCard variant="success">{success}</FeedbackCard>}

      {/* Return Confirmation Modal */}
      {returnModal.show && (
        <div className="modal-overlay" onClick={() => setReturnModal({ show: false, loan: null, fine: null })}>
          <div className="modal-content" onClick={e => e.stopPropagation()}>
            <h3>Potwierdzenie zwrotu</h3>
            <div className="modal-body">
              <p><strong>Książka:</strong> {returnModal.loan?.book?.title || 'N/A'}</p>
              <p><strong>Użytkownik:</strong> {returnModal.loan?.user?.name || returnModal.loan?.user?.email || 'N/A'}</p>
              <p><strong>Data wypożyczenia:</strong> {new Date(returnModal.loan?.borrowedAt).toLocaleDateString()}</p>
              <p><strong>Termin zwrotu:</strong> {new Date(returnModal.loan?.dueAt).toLocaleDateString()}</p>
              
              {returnModal.fine ? (
                <div className="fine-warning">
                  <h4 style={{ color: '#d32f2f', marginTop: '1rem' }}>⚠️ Zwrot po terminie</h4>
                  <p><strong>Dni opóźnienia:</strong> {returnModal.fine.days}</p>
                  <p><strong>Kara do zapłaty:</strong> {returnModal.fine.amount.toFixed(2)} PLN</p>
                  <p style={{ fontSize: '0.9rem', color: '#666' }}>(0.50 PLN za każdy dzień opóźnienia)</p>
                </div>
              ) : (
                <p style={{ color: '#2e7d32', marginTop: '1rem' }}>✓ Zwrot w terminie - brak kary</p>
              )}
            </div>
            <div className="modal-actions">
              <button 
                className="btn btn-secondary"
                onClick={() => setReturnModal({ show: false, loan: null, fine: null })}
              >
                Anuluj
              </button>
              <button 
                className="btn btn-primary"
                onClick={confirmReturn}
              >
                {returnModal.fine ? 'Potwierdź zwrot i nałóż karę' : 'Potwierdź zwrot'}
              </button>
            </div>
          </div>

          <div className="surface-card">
            <h2>Ustawienia biblioteki</h2>
            {librarySettingsLoading && <p>Ladowanie...</p>}
            {!librarySettingsLoading && (
              <form className="form" onSubmit={updateLibrarySettings}>
                <div className="form-row form-row--two">
                  <div className="form-field">
                    <label>Limit wypozyczen na uzytkownika</label>
                    <input
                      type="number"
                      value={librarySettings.loanLimitPerUser}
                      onChange={e => setLibrarySettings(prev => ({ ...prev, loanLimitPerUser: e.target.value }))}
                    />
                  </div>
                  <div className="form-field">
                    <label>Okres wypozyczenia (dni)</label>
                    <input
                      type="number"
                      value={librarySettings.loanDurationDays}
                      onChange={e => setLibrarySettings(prev => ({ ...prev, loanDurationDays: e.target.value }))}
                    />
                  </div>
                </div>
                <div className="form-field checkbox">
                  <label>
                    <input
                      type="checkbox"
                      checked={!!librarySettings.notificationsEnabled}
                      onChange={e => setLibrarySettings(prev => ({ ...prev, notificationsEnabled: e.target.checked }))}
                    />
                    Powiadomienia wlaczone
                  </label>
                </div>
                <button className="btn btn-primary" type="submit">Zapisz ustawienia</button>
              </form>
            )}
          </div>
        </div>
      )}

      {/* Tabs */}
      <div className="tabs">
        <button
          className={`tab ${activeTab === 'quickactions' ? 'tab--active' : ''}`}
          onClick={() => setActiveTab('quickactions')}
        >
          🔍 Szybkie akcje
        </button>
        <button
          className={`tab ${activeTab === 'loans' ? 'tab--active' : ''}`}
          onClick={() => setActiveTab('loans')}
        >
          Wypożyczenia
        </button>
        <button
          className={`tab ${activeTab === 'create' ? 'tab--active' : ''}`}
          onClick={() => setActiveTab('create')}
        >
          Utwórz wypożyczenie
        </button>
        <button
          className={`tab ${activeTab === 'reservations' ? 'tab--active' : ''}`}
          onClick={() => setActiveTab('reservations')}
        >
          Rezerwacje
        </button>
        <button
          className={`tab ${activeTab === 'fines' ? 'tab--active' : ''}`}
          onClick={() => setActiveTab('fines')}
        >
          Opłaty
        </button>
        <button
          className={`tab ${activeTab === 'inventory' ? 'tab--active' : ''}`}
          onClick={() => setActiveTab('inventory')}
        >
          Egzemplarze
        </button>
        <button
          className={`tab ${activeTab === 'collections' ? 'tab--active' : ''}`}
          onClick={() => setActiveTab('collections')}
        >
          📚 Kolekcje
        </button>
        <button
          className={`tab ${activeTab === 'stats' ? 'tab--active' : ''}`}
          onClick={() => setActiveTab('stats')}
        >
          Statystyki
        </button>
      </div>

      {/* Tab Content */}
      {activeTab === 'quickactions' && (
        <div className="surface-card">
          <h2>Szybkie akcje</h2>
          <p className="support-copy">Wyszukaj użytkownika lub zeskanuj kod kreskowy egzemplarza</p>
          
          <div className="form-section" style={{ marginTop: '2rem' }}>
            <div className="form-section__header">
              <span className="form-section__icon">👤</span>
              <h3 className="form-section__title">Wyszukiwanie użytkownika</h3>
            </div>
            <div className="form-field">
              <label>PESEL, numer karty lub imię i nazwisko</label>
              <input
                type="text"
                value={userSearchQuery}
                onChange={(e) => {
                  setUserSearchQuery(e.target.value)
                  searchUser(e.target.value)
                }}
                placeholder="np. 90010112345, L00123 lub Jan Kowalski"
              />
            </div>
            {userSearchResults.length > 0 && (
              <div className="search-results">
                <table className="data-table">
                  <thead>
                    <tr>
                      <th>Imię i nazwisko</th>
                      <th>Email</th>
                      <th>Numer karty</th>
                      <th>Status</th>
                      <th>Akcje</th>
                    </tr>
                  </thead>
                  <tbody>
                    {userSearchResults.map(u => (
                      <tr key={u.id}>
                        <td>{u.name}</td>
                        <td>{u.email}</td>
                        <td>{u.cardNumber || 'Brak'}</td>
                        <td>
                          <span className={`status-pill ${u.accountStatus === 'Aktywne' ? '' : 'is-danger'}`}>
                            {u.accountStatus || 'Nieznany'}
                          </span>
                        </td>
                        <td>
                          <button 
                            className="btn btn-sm btn-primary"
                            onClick={() => window.location.href = `/users/${u.id}/details`}
                            style={{ marginRight: '0.5rem' }}
                          >
                            Szczegóły
                          </button>
                          <button 
                            className="btn btn-sm btn-outline"
                            onClick={() => {
                              setActiveTab('create')
                              setLoanForm(prev => ({ ...prev, userId: u.id }))
                            }}
                          >
                            Wypożycz książkę
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>

          <div className="form-section" style={{ marginTop: '2rem' }}>
            <div className="form-section__header">
              <span className="form-section__icon">📷</span>
              <h3 className="form-section__title">Skanowanie kodu kreskowego</h3>
            </div>
            <div className="form-field">
              <label>Kod kreskowy egzemplarza</label>
              <input
                type="text"
                value={barcodeInput}
                onChange={(e) => setBarcodeInput(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    handleBarcodeScan(barcodeInput)
                  }
                }}
                placeholder="Zeskanuj lub wpisz kod kreskowy"
                autoFocus
              />
              <small className="support-copy">
                💡 Ustaw kursor w tym polu i użyj skanera kodów kreskowych lub wpisz kod ręcznie i naciśnij Enter
              </small>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'loans' && (
        <div className="surface-card">
          <div className="form-section" style={{ marginBottom: '1.5rem' }}>
            <h3>Wyszukiwanie wypozyczen</h3>
            <div className="form-row form-row--two">
              <div className="form-field">
                <label>ID wypozyczenia</label>
                <input value={loanLookupId} onChange={e => setLoanLookupId(e.target.value)} placeholder="np. 12" />
              </div>
              <div className="form-field">
                <label>ID uzytkownika</label>
                <input value={loanLookupUserId} onChange={e => setLoanLookupUserId(e.target.value)} placeholder="np. 5" />
              </div>
            </div>
            <div className="form-actions">
              <button className="btn btn-secondary" type="button" onClick={loadLoanById} disabled={loading || !loanLookupId}>Pobierz wypozyczenie</button>
              <button className="btn btn-secondary" type="button" onClick={loadLoansByUser} disabled={loading || !loanLookupUserId}>Wypozyczenia uzytkownika</button>
            </div>
            {loanLookupError && <p className="error">{loanLookupError}</p>}
            {Array.isArray(loanLookupResult) && loanLookupResult.length > 0 && (
              <ul className="list" style={{ marginTop: '1rem' }}>
                {loanLookupResult.map(loan => (
                  <li key={loan.id || `${loan.user?.id}-${loan.book?.id}`}>
                    <div className="list__title">{loan.book?.title || loan.bookTitle || 'Loan'}</div>
                    <div className="list__meta">Uzytkownik: {loan.user?.email || loan.userId || 'n/a'} | Termin: {loan.dueAt || loan.due_at || 'n/a'}</div>
                  </li>
                ))}
              </ul>
            )}
          </div>
          <h2>Aktywne wypożyczenia</h2>
          {loading && <p>Ładowanie...</p>}
          {!loading && loans.length === 0 && <p>Brak aktywnych wypożyczeń</p>}
          {!loading && loans.length > 0 && (
            <div className="table-container">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Użytkownik</th>
                    <th>Książka</th>
                    <th>Data wypożyczenia</th>
                    <th>Termin zwrotu</th>
                    <th>Status</th>
                    <th>Akcje</th>
                  </tr>
                </thead>
                <tbody>
                  {loans.map(loan => (
                    <tr key={loan.id}>
                      <td>{loan.id}</td>
                      <td>{loan.user?.name || loan.user?.email || 'N/A'}</td>
                      <td>{loan.book?.title || 'N/A'}</td>
                      <td>{new Date(loan.borrowedAt).toLocaleDateString()}</td>
                      <td>{new Date(loan.dueAt).toLocaleDateString()}</td>
                      <td>
                        {loan.returnedAt ? (
                          <span className="badge badge-success">Zwrócono</span>
                        ) : new Date(loan.dueAt) < new Date() ? (
                          <span className="badge badge-danger">Przeterminowane</span>
                        ) : (
                          <span className="badge badge-info">Aktywne</span>
                        )}
                      </td>
                      <td>
                        {!loan.returnedAt && (
                          <button 
                            className="btn btn-sm btn-primary"
                            onClick={() => handleReturnLoan(loan)}
                            disabled={loading}
                          >
                            Zwróć
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {activeTab === 'create' && (
        <div className="surface-card">
          <h2>Utwórz nowe wypożyczenie</h2>
          <form onSubmit={handleCreateLoan} className="form">
            <div className="form-field">
              <label htmlFor="userId">ID użytkownika</label>
              <input
                id="userId"
                type="number"
                value={loanForm.userId}
                onChange={e => setLoanForm({...loanForm, userId: e.target.value})}
                required
              />
            </div>
            
            <div className="form-field">
              <label htmlFor="bookId">ID książki</label>
              <input
                id="bookId"
                type="number"
                value={loanForm.bookId}
                onChange={e => setLoanForm({...loanForm, bookId: e.target.value})}
                required
              />
            </div>
            
            <div className="form-field">
              <label htmlFor="copyId">ID egzemplarza książki</label>
              <input
                id="copyId"
                type="number"
                value={loanForm.copyId}
                onChange={e => setLoanForm({...loanForm, copyId: e.target.value})}
                required
              />
            </div>
            
            <div className="form-field">
              <label htmlFor="dueDate">Termin zwrotu</label>
              <input
                id="dueDate"
                type="date"
                value={loanForm.dueDate}
                onChange={e => setLoanForm({...loanForm, dueDate: e.target.value})}
                required
                min={new Date().toISOString().split('T')[0]}
              />
            </div>
            
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? 'Tworzenie...' : 'Utwórz wypożyczenie'}
            </button>
          </form>
        </div>
      )}

      {activeTab === 'stats' && (
        <div className="grid two-columns">
          <div className="surface-card">
            <h2>Statystyki wypożyczeń</h2>
            {loading && <p>Ładowanie...</p>}
            {!loading && (
              <div className="stats-grid">
                <div className="stat">
                  <span className="stat__label">Aktywne wypożyczenia</span>
                  <span className="stat__value">{stats.activeLoans}</span>
                </div>
                <div className="stat">
                  <span className="stat__label">Przeterminowane</span>
                  <span className="stat__value">{stats.overdueLoans}</span>
                </div>
                <div className="stat">
                  <span className="stat__label">Aktywni użytkownicy</span>
                  <span className="stat__value">{stats.totalUsers}</span>
                </div>
                <div className="stat">
                  <span className="stat__label">Dostępne egzemplarze</span>
                  <span className="stat__value">{stats.availableCopies}</span>
                </div>
              </div>
            )}
          </div>
          
          <div className="surface-card">
            <h2>Szybkie akcje</h2>
            <div className="action-buttons">
              <button className="btn btn-secondary" onClick={loadStats}>
                Odśwież statystyki
              </button>
              <button className="btn btn-secondary" onClick={() => setActiveTab('create')}>
                Nowe wypożyczenie
              </button>
              <button className="btn btn-secondary" onClick={() => setActiveTab('loans')}>
                Zobacz wszystkie wypożyczenia
              </button>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'reservations' && (
        <div className="surface-card">
          <div className="section-header">
            <h2>Rezerwacje</h2>
            <button className="btn btn-secondary" onClick={loadReservations}>Odśwież</button>
          </div>
          {loading && <p>Ładowanie...</p>}
          {!loading && reservations.length === 0 && <p>Brak aktywnych rezerwacji.</p>}
          {!loading && reservations.length > 0 && (
            <div className="table-container">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Użytkownik</th>
                    <th>Książka</th>
                    <th>Status</th>
                    <th>Wygasa</th>
                    <th>Akcje</th>
                  </tr>
                </thead>
                <tbody>
                  {reservations.map(reservation => (
                    <tr key={reservation.id}>
                      <td>{reservation.id}</td>
                      <td>{reservation.user?.email || reservation.userEmail || 'N/A'}</td>
                      <td>{reservation.book?.title || reservation.bookTitle || 'N/A'}</td>
                      <td>{reservation.status || 'nieznany'}</td>
                      <td>{reservation.expiresAt ? new Date(reservation.expiresAt).toLocaleString() : '—'}</td>
                      <td>
                        {reservation.status === 'ACTIVE' && (
                          <>
                            <button className="btn btn-sm btn-primary" onClick={() => fulfillReservation(reservation.id)} disabled={loading}>
                              Zrealizuj
                            </button>
                            {' '}
                            <button className="btn btn-sm" onClick={() => cancelReservation(reservation.id)} disabled={loading}>
                              Anuluj
                            </button>
                          </>
                        )}
                        {reservation.status === 'CANCELLED' && <span className="support-copy">Anulowana</span>}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {activeTab === 'fines' && (
        <div className="surface-card">
          <div className="section-header">
            <h2>Opłaty i kary</h2>
            <button className="btn btn-secondary" onClick={loadFines}>Odśwież</button>
          </div>
          {loading && <p>Ładowanie...</p>}
          {!loading && fines.length === 0 && <p>Brak aktywnych opłat.</p>}
          {!loading && fines.length > 0 && (
            <div className="table-container">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Użytkownik</th>
                    <th>Kwota</th>
                    <th>Status</th>
                    <th>Powód</th>
                    <th>Akcje</th>
                  </tr>
                </thead>
                <tbody>
                  {fines.map(fine => (
                    <tr key={fine.id}>
                      <td>{fine.id}</td>
                      <td>{fine.user?.email || fine.userEmail || 'N/A'}</td>
                      <td>{fine.amount} {fine.currency || 'PLN'}</td>
                      <td>{fine.status || (fine.paidAt ? 'opłacono' : 'należne')}</td>
                      <td>{fine.reason}</td>
                      <td>
                        {!fine.paidAt && (
                          <>
                            <button className="btn btn-sm btn-primary" onClick={() => payFine(fine.id)} disabled={loading}>
                              Oznacz jako opłacone
                            </button>
                            {isAdmin && (
                              <>
                                {' '}
                                <button className="btn btn-sm" onClick={() => cancelFine(fine.id)} disabled={loading}>
                                  Anuluj
                                </button>
                              </>
                            )}
                          </>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {activeTab === 'inventory' && (
        <div className="grid two-columns">
          <div className="surface-card">
            <h2>Egzemplarze książki</h2>
            <div className="form-field" style={{ position: 'relative' }}>
              <label>Wyszukaj książkę po nazwie</label>
              <input
                type="text"
                value={bookSearchQuery}
                onChange={e => {
                  setBookSearchQuery(e.target.value)
                  searchBooks(e.target.value)
                }}
                placeholder="Wpisz tytuł książki..."
              />
              {availableBooks.length > 0 && (
                <div style={{ 
                  position: 'absolute', 
                  top: '100%', 
                  left: 0, 
                  right: 0, 
                  backgroundColor: 'white', 
                  border: '1px solid #ddd', 
                  borderRadius: '4px', 
                  maxHeight: '200px', 
                  overflowY: 'auto', 
                  zIndex: 10000,
                  marginTop: '4px',
                  boxShadow: '0 4px 6px rgba(0,0,0,0.1)'
                }}>
                  {availableBooks.map(book => (
                    <div
                      key={book.id}
                      onClick={() => {
                        setInventoryBookId(book.id)
                        setBookSearchQuery(book.title)
                        setAvailableBooks([])
                        loadCopies(book.id)
                      }}
                      style={{ 
                        padding: '8px 12px', 
                        cursor: 'pointer',
                        borderBottom: '1px solid #eee'
                      }}
                      onMouseEnter={e => e.target.style.backgroundColor = '#f5f5f5'}
                      onMouseLeave={e => e.target.style.backgroundColor = 'white'}
                    >
                      <strong>{book.title}</strong>
                      {book.author?.name && <div style={{ fontSize: '0.875rem', color: '#666' }}>{book.author.name}</div>}
                    </div>
                  ))}
                </div>
              )}
            </div>
            {loading && <p>Ładowanie...</p>}
            {!loading && copies.length > 0 && (
              <ul className="list">
                {copies.map(copy => (
                  <li key={copy.id}>
                    <strong>{copy.inventoryCode || `Egzemplarz #${copy.id}`}</strong>
                    <div className="support-copy">{copy.status || copy.state} • {copy.accessType}</div>
                    {copy.location && <div className="support-copy">Lokalizacja: {copy.location}</div>}
                    <div style={{ display: 'flex', gap: '0.5rem', marginTop: '0.5rem' }}>
                      <button className="btn btn-outline btn-sm" type="button" onClick={() => updateCopy(copy)}>Edytuj</button>
                      <button className="btn btn-danger btn-sm" type="button" onClick={() => deleteCopy(copy)}>Usun</button>
                    </div>
                  </li>
                ))}
              </ul>
            )}
            {!loading && copies.length === 0 && inventoryBookId && <p>Brak egzemplarzy dla podanej książki.</p>}
          </div>

          <div className="surface-card">
            <h2>Dodaj egzemplarz</h2>
            <form className="form" onSubmit={addCopy}>
              <div className="form-field">
                <label>Kod inwentarzowy</label>
                <input value={copyForm.inventoryCode} onChange={e => setCopyForm({ ...copyForm, inventoryCode: e.target.value })} required />
              </div>
              <div className="form-field">
                <label>Status</label>
                <select value={copyForm.status} onChange={e => setCopyForm({ ...copyForm, status: e.target.value })}>
                  <option value="AVAILABLE">Dostępny</option>
                  <option value="RESERVED">Zarezerwowany</option>
                  <option value="BORROWED">Wypożyczony</option>
                  <option value="MAINTENANCE">Niedostępny</option>
                  <option value="WITHDRAWN">Wycofany</option>
                </select>
              </div>
              <div className="form-field">
                <label>Tryb dostępu</label>
                <select value={copyForm.accessType} onChange={e => setCopyForm({ ...copyForm, accessType: e.target.value })}>
                  <option value="STORAGE">Magazyn</option>
                  <option value="OPEN_STACK">Wypożyczalnia</option>
                  <option value="REFERENCE">Czytelnia/Odwoławcze</option>
                </select>
              </div>
              <div className="form-field">
                <label>Lokalizacja</label>
                <input value={copyForm.location} onChange={e => setCopyForm({ ...copyForm, location: e.target.value })} />
              </div>
              <div className="form-field">
                <label>Stan</label>
                <input value={copyForm.condition} onChange={e => setCopyForm({ ...copyForm, condition: e.target.value })} />
              </div>
              <button type="submit" className="btn btn-primary" disabled={!inventoryBookId || loading}>Dodaj egzemplarz</button>
            </form>
          </div>
        </div>
      )}

      {activeTab === 'collections' && (
        <div className="surface-card">
          <h2>Kolekcje książek</h2>
          <p className="support-copy">Twórz i zarządzaj tematycznymi kolekcjami książek dla czytelników</p>

          <div className="collection-form">
            <h3>{editingCollection ? 'Edytuj kolekcję' : 'Nowa kolekcja'}</h3>
            
            <div className="form-field">
              <label>Nazwa kolekcji</label>
              <input
                value={collectionForm.name}
                onChange={e => setCollectionForm({ ...collectionForm, name: e.target.value })}
                placeholder="np. Wakacyjne czytanie, Klasyka literatury"
              />
            </div>

            <div className="form-field">
              <label>Opis</label>
              <textarea
                value={collectionForm.description}
                onChange={e => setCollectionForm({ ...collectionForm, description: e.target.value })}
                placeholder="Krótki opis kolekcji..."
                rows="3"
              />
            </div>

            <div className="form-field">
              <label>
                <input
                  type="checkbox"
                  checked={collectionForm.featured}
                  onChange={e => setCollectionForm({ ...collectionForm, featured: e.target.checked })}
                />
                {' '}Wyróżniona (wyświetlana w Polecanych)
              </label>
            </div>

            <div className="form-field">
              <label>Kolejność wyświetlania</label>
              <input
                type="number"
                value={collectionForm.displayOrder}
                onChange={e => setCollectionForm({ ...collectionForm, displayOrder: parseInt(e.target.value) || 0 })}
              />
            </div>

            <div className="book-selector">
              <h4>Książki w kolekcji</h4>
              <input
                className="book-selector__search"
                value={collectionBookSearch}
                onChange={e => {
                  setCollectionBookSearch(e.target.value)
                  searchBooksForCollection(e.target.value)
                }}
                placeholder="Szukaj książek do dodania..."
              />

              {collectionBookResults.length > 0 && (
                <div className="book-selector__results">
                  {collectionBookResults.map(book => (
                    <div
                      key={book.id}
                      className={`book-selector__item ${collectionForm.bookIds.includes(book.id) ? 'book-selector__item--selected' : ''}`}
                      onClick={() => toggleBookInCollection(book.id)}
                    >
                      <input
                        type="checkbox"
                        checked={collectionForm.bookIds.includes(book.id)}
                        readOnly
                      />
                      <span>{book.title} - {book.author?.name || 'Nieznany autor'}</span>
                    </div>
                  ))}
                </div>
              )}

              {collectionForm.bookIds.length > 0 && (
                <div className="selected-books-list">
                  {collectionForm.bookIds.map(bookId => {
                    const book = collectionBookResults.find(b => b.id === bookId)
                    return (
                      <span key={bookId} className="selected-book-tag">
                        {book?.title || `Książka #${bookId}`}
                        <button onClick={() => toggleBookInCollection(bookId)}>×</button>
                      </span>
                    )
                  })}
                </div>
              )}
            </div>

            <div className="form-actions">
              {editingCollection && (
                <button
                  className="btn btn-secondary"
                  onClick={() => {
                    setEditingCollection(null)
                    setCollectionForm({ name: '', description: '', featured: false, displayOrder: 0, bookIds: [] })
                  }}
                >
                  Anuluj
                </button>
              )}
              <button
                className="btn btn-primary"
                onClick={saveCollection}
                disabled={!collectionForm.name || loading}
              >
                {editingCollection ? 'Zaktualizuj' : 'Utwórz kolekcję'}
              </button>
            </div>
          </div>

          <div className="collections-grid">
            {collections.map(collection => (
              <div key={collection.id} className={`collection-card ${collection.featured ? 'collection-card--featured' : ''}`}>
                <div className="collection-header">
                  <div>
                    <div className="collection-title">{collection.name}</div>
                    <div className="collection-meta">
                      <span>📚 {collection.bookCount} książek</span>
                      <span>👤 {collection.curatedBy}</span>
                      {collection.featured && <span>⭐ Wyróżniona</span>}
                    </div>
                  </div>
                  <div style={{ display: 'flex', gap: '0.5rem' }}>
                    <button
                      className="btn btn-sm btn-secondary"
                      onClick={() => {
                        setEditingCollection(collection.id)
                        setCollectionForm({
                          name: collection.name,
                          description: collection.description,
                          featured: collection.featured,
                          displayOrder: collection.displayOrder,
                          bookIds: collection.books?.map(b => b.id) || []
                        })
                      }}
                    >
                      ✏️
                    </button>
                    <button
                      className="btn btn-sm btn-danger"
                      onClick={() => deleteCollection(collection.id)}
                    >
                      🗑️
                    </button>
                  </div>
                </div>
                
                {collection.description && (
                  <p className="support-copy">{collection.description}</p>
                )}

                <div className="collection-books-preview">
                  {collection.books?.slice(0, 5).map(book => (
                    <div key={book.id} className="collection-book-mini" title={book.title}>
                      📖
                    </div>
                  ))}
                  {collection.books?.length > 5 && (
                    <div className="collection-book-mini">+{collection.books.length - 5}</div>
                  )}
                </div>
              </div>
            ))}

            {collections.length === 0 && !loading && (
              <div className="empty-state">
                Brak kolekcji. Utwórz pierwszą kolekcję używając formularza powyżej.
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  )
}
