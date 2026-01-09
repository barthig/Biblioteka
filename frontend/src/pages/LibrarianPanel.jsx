
import React, { useEffect, useMemo, useState } from 'react'
import toast from 'react-hot-toast'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'
import LibrarianDashboard from './LibrarianDashboard'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import FeedbackCard from '../components/ui/FeedbackCard'

export default function LibrarianPanel() {
  const { user } = useAuth()
  const isAdmin = user?.roles?.includes('ROLE_ADMIN')
  const [activeTab, setActiveTab] = useState('dashboard')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)

  const [loanForm, setLoanForm] = useState({
    userId: '',
    bookId: '',
    copyId: '',
    dueDate: ''
  })
  const [loanUserQuery, setLoanUserQuery] = useState('')
  const [loanUserResults, setLoanUserResults] = useState([])
  const [loanBookQuery, setLoanBookQuery] = useState('')
  const [loanBookResults, setLoanBookResults] = useState([])

  const [loans, setLoans] = useState([])
  const [loanSearchName, setLoanSearchName] = useState('')
  const [loanSearchTitle, setLoanSearchTitle] = useState('')

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

  const [reservations, setReservations] = useState([])
  const [fines, setFines] = useState([])

  const [inventoryBookId, setInventoryBookId] = useState('')
  const [bookSearchQuery, setBookSearchQuery] = useState('')
  const [availableBooks, setAvailableBooks] = useState([])
  const [copies, setCopies] = useState([])
  const [copyForm, setCopyForm] = useState({
    inventoryCode: '',
    status: 'AVAILABLE',
    accessType: 'STORAGE',
    location: '',
    condition: ''
  })

  const [returnModal, setReturnModal] = useState({ show: false, loan: null, fine: null })

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
    } else if (activeTab === 'collections') {
      loadCollections()
    }
  }, [activeTab])

  const filteredLoans = useMemo(() => {
    const name = loanSearchName.trim().toLowerCase()
    const title = loanSearchTitle.trim().toLowerCase()
    if (!name && !title) return loans
    return loans.filter(loan => {
      const userName = (loan.user?.name || loan.userName || '').toString().toLowerCase()
      const bookTitle = (loan.book?.title || loan.bookTitle || '').toString().toLowerCase()
      const nameMatch = !name || userName.includes(name)
      const titleMatch = !title || bookTitle.includes(title)
      return nameMatch && titleMatch
    })
  }, [loans, loanSearchName, loanSearchTitle])

  const hasLoanFilters = loanSearchName.trim() !== '' || loanSearchTitle.trim() !== ''
  const availableCopies = useMemo(
    () => copies.filter(copy => ((copy.status || copy.state || '').toUpperCase() === 'AVAILABLE')),
    [copies]
  )

  async function loadLoans() {
    setLoading(true)
    setError(null)
    try {
      const data = await apiFetch('/api/loans')
      setLoans(Array.isArray(data) ? data : data.data || [])
    } catch (err) {
      setError(err.message || 'Nie udalo sie pobrac wypozyczen')
    } finally {
      setLoading(false)
    }
  }

  async function searchLoanUser(query) {
    if (!query || query.length < 2) {
      setLoanUserResults([])
      return
    }
    try {
      const data = await apiFetch(`/api/users/search?q=${encodeURIComponent(query)}`)
      setLoanUserResults(Array.isArray(data) ? data : data?.data || [])
    } catch (err) {
      setError(err.message || 'Nie udalo sie wyszukac uzytkownika')
    }
  }

  async function searchLoanBook(query) {
    if (!query || query.length < 2) {
      setLoanBookResults([])
      return
    }
    try {
      const data = await apiFetch(`/api/books?q=${encodeURIComponent(query)}&limit=10`)
      setLoanBookResults(Array.isArray(data?.data) ? data.data : [])
    } catch (err) {
      setError(err.message || 'Nie udalo sie wyszukac ksiazki')
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
      setError(err.message || 'Nie udalo sie pobrac statystyk')
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
      setError(err.message || 'Nie udalo sie pobrac rezerwacji')
    } finally {
      setLoading(false)
    }
  }

  async function cancelReservation(reservationId) {
    if (!confirm('Anulowac te rezerwacje?')) return
    setLoading(true)
    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/reservations/${reservationId}`, { method: 'DELETE' })
      setSuccess('Rezerwacja zostala anulowana')
      loadReservations()
    } catch (err) {
      setError(err.message || 'Nie udalo sie anulowac rezerwacji')
    } finally {
      setLoading(false)
    }
  }

  async function fulfillReservation(reservationId) {
    if (!confirm('Zrealizowac te rezerwacje? Uzytkownik otrzyma wypozyczenie.')) return
    setLoading(true)
    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/reservations/${reservationId}/fulfill`, { method: 'POST' })
      setSuccess('Rezerwacja zostala zrealizowana')
      loadReservations()
    } catch (err) {
      setError(err.message || 'Nie udalo sie zrealizowac rezerwacji')
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
      console.error('Blad wyszukiwania ksiazek:', err)
    }
  }

  async function loadFines() {
    setLoading(true)
    setError(null)
    try {
      const data = await apiFetch('/api/fines?limit=50')
      setFines(Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [])
    } catch (err) {
      setError(err.message || 'Nie udalo sie pobrac oplat')
    } finally {
      setLoading(false)
    }
  }

  async function payFine(fineId) {
    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/fines/${fineId}/pay`, { method: 'POST' })
      setSuccess('Oplata zostala oznaczona jako oplacona')
      loadFines()
    } catch (err) {
      setError(err.message || 'Nie udalo sie oplacic naleznosci')
    }
  }

  async function cancelFine(fineId) {
    if (!confirm('Anulowac naleznosc?')) return
    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/fines/${fineId}`, { method: 'DELETE' })
      setSuccess('Oplata zostala anulowana')
      loadFines()
    } catch (err) {
      setError(err.message || 'Nie udalo sie anulowac oplaty')
    }
  }

  async function loadCopies(bookId) {
    if (!bookId) return
    setLoading(true)
    setError(null)
    try {
      const data = await apiFetch(`/api/admin/books/${bookId}/copies`)
      const items = Array.isArray(data?.items)
        ? data.items
        : Array.isArray(data?.data)
          ? data.data
          : Array.isArray(data)
            ? data
            : []
      setCopies(items)
    } catch (err) {
      setError(err.message || 'Nie udalo sie pobrac egzemplarzy')
    } finally {
      setLoading(false)
    }
  }

  async function addCopy(e) {
    e.preventDefault()
    if (!inventoryBookId) {
      setError('Podaj ID ksiazki, do ktorej dodajesz egzemplarz')
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
      setSuccess('Egzemplarz zostal dodany')
      setCopyForm({ inventoryCode: '', status: 'AVAILABLE', accessType: 'STORAGE', location: '', condition: '' })
      loadCopies(inventoryBookId)
    } catch (err) {
      setError(err.message || 'Nie udalo sie dodac egzemplarza')
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
    if (!loanForm.userId || !loanForm.bookId || !loanForm.copyId) {
      setError('Wybierz uzytkownika, ksiazke i egzemplarz')
      return
    }
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

      setSuccess('Wypozyczenie zostalo utworzone')
      setLoanForm({ userId: '', bookId: '', copyId: '', dueDate: '' })
      setLoanUserQuery('')
      setLoanUserResults([])
      setLoanBookQuery('')
      setLoanBookResults([])
      loadLoans()
    } catch (err) {
      setError(err.message || 'Nie udalo sie utworzyc wypozyczenia')
    } finally {
      setLoading(false)
    }
  }

  async function handleReturnLoan(loan) {
    const now = new Date()
    const dueDate = new Date(loan.dueAt)
    const isOverdue = now > dueDate && !loan.returnedAt

    let fineAmount = 0
    let daysOverdue = 0

    if (isOverdue) {
      daysOverdue = Math.floor((now - dueDate) / (1000 * 60 * 60 * 24))
      fineAmount = daysOverdue * 0.50
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
      await apiFetch(`/api/loans/${loan.id}/return`, { method: 'PUT' })
      if (fine) {
        setSuccess(`Zwrot po terminie. Kara: ${fine.amount.toFixed(2)} PLN za ${fine.days} dni.`)
      } else {
        setSuccess('Ksiazka zostala zwrocona')
      }
      loadLoans()
    } catch (err) {
      setError(err.message || 'Nie udalo sie zwrocic ksiazki')
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
    if (!confirm('Czy na pewno usunac kolekcje?')) return

    try {
      await apiFetch(`/api/collections/${id}`, { method: 'DELETE' })
      setSuccess('Kolekcja usunieta')
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

  return (
    <div className="page">
      <PageHeader
        title="Panel bibliotekarza"
        subtitle="Obsluga wypozyczen i zarzadzanie biblioteka"
      />

      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}
      {success && <FeedbackCard variant="success">{success}</FeedbackCard>}

      <div className="tabs">
        <button className={`tab ${activeTab === 'dashboard' ? 'tab--active' : ''}`} onClick={() => setActiveTab('dashboard')}>
          Dashboard
        </button>
        <button className={`tab ${activeTab === 'loans' ? 'tab--active' : ''}`} onClick={() => setActiveTab('loans')}>
          Wypozyczenia
        </button>
        <button className={`tab ${activeTab === 'create' ? 'tab--active' : ''}`} onClick={() => setActiveTab('create')}>
          Nowe wypozyczenie
        </button>
        <button className={`tab ${activeTab === 'reservations' ? 'tab--active' : ''}`} onClick={() => setActiveTab('reservations')}>
          Rezerwacje
        </button>
        <button className={`tab ${activeTab === 'fines' ? 'tab--active' : ''}`} onClick={() => setActiveTab('fines')}>
          Oplaty
        </button>
        <button className={`tab ${activeTab === 'inventory' ? 'tab--active' : ''}`} onClick={() => setActiveTab('inventory')}>
          Egzemplarze
        </button>
        <button className={`tab ${activeTab === 'collections' ? 'tab--active' : ''}`} onClick={() => setActiveTab('collections')}>
          Kolekcje
        </button>
      </div>

      {activeTab === 'dashboard' && <LibrarianDashboard />}

      {returnModal.show && (
        <div className="modal-overlay" onClick={() => setReturnModal({ show: false, loan: null, fine: null })}>
          <div className="modal-content" onClick={e => e.stopPropagation()}>
            <h3>Potwierdzenie zwrotu</h3>
            <div className="modal-body">
              <p><strong>Ksiazka:</strong> {returnModal.loan?.book?.title || 'N/A'}</p>
              <p><strong>Uzytkownik:</strong> {returnModal.loan?.user?.name || returnModal.loan?.user?.email || 'N/A'}</p>
              <p><strong>Data wypozyczenia:</strong> {new Date(returnModal.loan?.borrowedAt).toLocaleDateString()}</p>
              <p><strong>Termin zwrotu:</strong> {new Date(returnModal.loan?.dueAt).toLocaleDateString()}</p>
              {returnModal.fine ? (
                <div className="fine-warning">
                  <h4 style={{ color: '#d32f2f', marginTop: '1rem' }}>Zwrot po terminie</h4>
                  <p><strong>Dni opoznienia:</strong> {returnModal.fine.days}</p>
                  <p><strong>Kara do zaplaty:</strong> {returnModal.fine.amount.toFixed(2)} PLN</p>
                  <p style={{ fontSize: '0.9rem', color: '#666' }}>(0.50 PLN za kazdy dzien opoznienia)</p>
                </div>
              ) : (
                <p style={{ color: '#2e7d32', marginTop: '1rem' }}>Zwrot w terminie - brak kary</p>
              )}
            </div>
            <div className="modal-actions">
              <button className="btn btn-secondary" onClick={() => setReturnModal({ show: false, loan: null, fine: null })}>
                Anuluj
              </button>
              <button className="btn btn-primary" onClick={confirmReturn}>
                {returnModal.fine ? 'Potwierdz zwrot i naloz kare' : 'Potwierdz zwrot'}
              </button>
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
                <label>Imie i nazwisko</label>
                <input value={loanSearchName} onChange={e => setLoanSearchName(e.target.value)} placeholder="np. Jan Kowalski" />
              </div>
              <div className="form-field">
                <label>Tytul ksiazki</label>
                <input value={loanSearchTitle} onChange={e => setLoanSearchTitle(e.target.value)} placeholder="np. Lalka" />
              </div>
            </div>
          </div>

          <h2>Aktywne wypozyczenia</h2>
          {loading && <p>Ladowanie...</p>}
          {!loading && filteredLoans.length === 0 && (
            <p>{hasLoanFilters ? 'Brak wynikow wyszukiwania.' : 'Brak aktywnych wypozyczen.'}</p>
          )}
          {!loading && filteredLoans.length > 0 && (
            <div className="table-container">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Uzytkownik</th>
                    <th>Ksiazka</th>
                    <th>Data wypozyczenia</th>
                    <th>Termin zwrotu</th>
                    <th>Status</th>
                    <th>Akcje</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredLoans.map(loan => (
                    <tr key={loan.id}>
                      <td>{loan.id}</td>
                      <td>{loan.user?.name || loan.user?.email || 'N/A'}</td>
                      <td>{loan.book?.title || 'N/A'}</td>
                      <td>{new Date(loan.borrowedAt).toLocaleDateString()}</td>
                      <td>{new Date(loan.dueAt).toLocaleDateString()}</td>
                      <td>
                        {loan.returnedAt ? (
                          <span className="badge badge-success">Zwracono</span>
                        ) : new Date(loan.dueAt) < new Date() ? (
                          <span className="badge badge-danger">Przeterminowane</span>
                        ) : (
                          <span className="badge badge-info">Aktywne</span>
                        )}
                      </td>
                      <td>
                        {!loan.returnedAt && (
                          <button className="btn btn-sm btn-primary" onClick={() => handleReturnLoan(loan)} disabled={loading}>
                            Zwroc
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
          <h2>Utworz nowe wypozyczenie</h2>
          <form onSubmit={handleCreateLoan} className="form">
            <div className="form-field" style={{ position: 'relative' }}>
              <label>Uzytkownik</label>
              <input
                value={loanUserQuery}
                onChange={e => {
                  const value = e.target.value
                  setLoanUserQuery(value)
                  setLoanForm(prev => ({ ...prev, userId: '' }))
                  searchLoanUser(value)
                }}
                placeholder="Wpisz imie i nazwisko..."
                required
              />
              {loanUserResults.length > 0 && (
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
                  {loanUserResults.map(u => (
                    <div
                      key={u.id}
                      onClick={() => {
                        setLoanForm(prev => ({ ...prev, userId: String(u.id) }))
                        setLoanUserQuery(u.name || u.email || `Uzytkownik #${u.id}`)
                        setLoanUserResults([])
                      }}
                      style={{
                        padding: '8px 12px',
                        cursor: 'pointer',
                        borderBottom: '1px solid #eee'
                      }}
                      onMouseEnter={e => e.target.style.backgroundColor = '#f5f5f5'}
                      onMouseLeave={e => e.target.style.backgroundColor = 'white'}
                    >
                      <strong>{u.name || u.email || `Uzytkownik #${u.id}`}</strong>
                      {u.email && u.name && <div style={{ fontSize: '0.875rem', color: '#666' }}>{u.email}</div>}
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div className="form-field" style={{ position: 'relative' }}>
              <label>Tytul ksiazki</label>
              <input
                value={loanBookQuery}
                onChange={e => {
                  const value = e.target.value
                  setLoanBookQuery(value)
                  setLoanForm(prev => ({ ...prev, bookId: '', copyId: '' }))
                  searchLoanBook(value)
                }}
                placeholder="Wpisz tytul ksiazki..."
                required
              />
              {loanBookResults.length > 0 && (
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
                  {loanBookResults.map(book => (
                    <div
                      key={book.id}
                      onClick={() => {
                        setLoanForm(prev => ({ ...prev, bookId: String(book.id), copyId: '' }))
                        setLoanBookQuery(book.title || `Ksiazka #${book.id}`)
                        setLoanBookResults([])
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
                      <strong>{book.title || `Ksiazka #${book.id}`}</strong>
                      {book.author?.name && <div style={{ fontSize: '0.875rem', color: '#666' }}>{book.author.name}</div>}
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div className="form-field">
              <label>Egzemplarz</label>
              <select value={loanForm.copyId} onChange={e => setLoanForm({ ...loanForm, copyId: e.target.value })} required>
                <option value="">Wybierz egzemplarz</option>
                {availableCopies.length === 0 && (
                  <option value="" disabled>Brak dostepnych egzemplarzy</option>
                )}
                {availableCopies.map(copy => (
                  <option key={copy.id} value={copy.id}>
                    {copy.inventoryCode || `Egzemplarz #${copy.id}`}
                  </option>
                ))}
              </select>
            </div>

            <div className="form-field">
              <label htmlFor="dueDate">Termin zwrotu</label>
              <input
                id="dueDate"
                type="date"
                value={loanForm.dueDate}
                onChange={e => setLoanForm({ ...loanForm, dueDate: e.target.value })}
                required
                min={new Date().toISOString().split('T')[0]}
              />
            </div>

            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? 'Tworzenie...' : 'Utworz wypozyczenie'}
            </button>
          </form>
        </div>
      )}

      {activeTab === 'stats' && (
        <div className="grid two-columns">
          <div className="surface-card">
            <h2>Statystyki wypozyczen</h2>
            {loading && <p>Ladowanie...</p>}
            {!loading && (
              <div className="stats-grid">
                <div className="stat">
                  <span className="stat__label">Aktywne wypozyczenia</span>
                  <span className="stat__value">{stats.activeLoans}</span>
                </div>
                <div className="stat">
                  <span className="stat__label">Przeterminowane</span>
                  <span className="stat__value">{stats.overdueLoans}</span>
                </div>
                <div className="stat">
                  <span className="stat__label">Aktywni uzytkownicy</span>
                  <span className="stat__value">{stats.totalUsers}</span>
                </div>
                <div className="stat">
                  <span className="stat__label">Dostepne egzemplarze</span>
                  <span className="stat__value">{stats.availableCopies}</span>
                </div>
              </div>
            )}
          </div>

          <div className="surface-card">
            <h2>Szybkie akcje</h2>
            <div className="action-buttons">
              <button className="btn btn-secondary" onClick={loadStats}>
                Odswiez statystyki
              </button>
              <button className="btn btn-secondary" onClick={() => setActiveTab('create')}>
                Nowe wypozyczenie
              </button>
              <button className="btn btn-secondary" onClick={() => setActiveTab('loans')}>
                Zobacz wypozyczenia
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
                      min="1"
                      max="20"
                      value={librarySettings.loanLimitPerUser}
                      onChange={e => setLibrarySettings(prev => ({ ...prev, loanLimitPerUser: e.target.value }))}
                      required
                    />
                  </div>
                  <div className="form-field">
                    <label>Dlugosc wypozyczenia (dni)</label>
                    <input
                      type="number"
                      min="7"
                      max="60"
                      value={librarySettings.loanDurationDays}
                      onChange={e => setLibrarySettings(prev => ({ ...prev, loanDurationDays: e.target.value }))}
                      required
                    />
                  </div>
                </div>
                <div className="checkbox-field">
                  <input
                    type="checkbox"
                    id="notifications-enabled"
                    checked={librarySettings.notificationsEnabled}
                    onChange={e => setLibrarySettings(prev => ({ ...prev, notificationsEnabled: e.target.checked }))}
                  />
                  <label htmlFor="notifications-enabled">Powiadomienia systemowe</label>
                </div>
                <div className="form-actions">
                  <button type="submit" className="btn btn-primary" disabled={librarySettingsLoading}>
                    Zapisz ustawienia
                  </button>
                </div>
              </form>
            )}
          </div>
        </div>
      )}

      {activeTab === 'reservations' && (
        <div className="surface-card">
          <div className="section-header">
            <h2>Rezerwacje</h2>
            <button className="btn btn-secondary" onClick={loadReservations}>Odswiez</button>
          </div>
          {loading && <p>Ladowanie...</p>}
          {!loading && reservations.length === 0 && <p>Brak aktywnych rezerwacji.</p>}
          {!loading && reservations.length > 0 && (
            <div className="table-container">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Uzytkownik</th>
                    <th>Ksiazka</th>
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
                      <td>{reservation.expiresAt ? new Date(reservation.expiresAt).toLocaleString() : '-'}</td>
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
            <h2>Oplaty i kary</h2>
            <button className="btn btn-secondary" onClick={loadFines}>Odswiez</button>
          </div>
          {loading && <p>Ladowanie...</p>}
          {!loading && fines.length === 0 && <p>Brak aktywnych oplat.</p>}
          {!loading && fines.length > 0 && (
            <div className="table-container">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Uzytkownik</th>
                    <th>Kwota</th>
                    <th>Status</th>
                    <th>Powod</th>
                    <th>Akcje</th>
                  </tr>
                </thead>
                <tbody>
                  {fines.map(fine => (
                    <tr key={fine.id}>
                      <td>{fine.id}</td>
                      <td>{fine.user?.email || fine.userEmail || 'N/A'}</td>
                      <td>{fine.amount} {fine.currency || 'PLN'}</td>
                      <td>{fine.status || (fine.paidAt ? 'oplacono' : 'nalezna')}</td>
                      <td>{fine.reason}</td>
                      <td>
                        {!fine.paidAt && (
                          <>
                            <button className="btn btn-sm btn-primary" onClick={() => payFine(fine.id)} disabled={loading}>
                              Oznacz jako oplacone
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
            <h2>Egzemplarze ksiazki</h2>
            <div className="form-field" style={{ position: 'relative' }}>
              <label>Wyszukaj ksiazke po nazwie</label>
              <input
                type="text"
                value={bookSearchQuery}
                onChange={e => {
                  setBookSearchQuery(e.target.value)
                  searchBooks(e.target.value)
                }}
                placeholder="Wpisz tytul ksiazki..."
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
            {loading && <p>Ladowanie...</p>}
            {!loading && copies.length > 0 && (
              <ul className="list">
                {copies.map(copy => (
                  <li key={copy.id}>
                    <strong>{copy.inventoryCode || `Egzemplarz #${copy.id}`}</strong>
                    <div className="support-copy">{copy.status || copy.state} - {copy.accessType}</div>
                    {copy.location && <div className="support-copy">Lokalizacja: {copy.location}</div>}
                    <div style={{ display: 'flex', gap: '0.5rem', marginTop: '0.5rem' }}>
                      <button className="btn btn-outline btn-sm" type="button" onClick={() => updateCopy(copy)}>Edytuj</button>
                      <button className="btn btn-danger btn-sm" type="button" onClick={() => deleteCopy(copy)}>Usun</button>
                    </div>
                  </li>
                ))}
              </ul>
            )}
            {!loading && copies.length === 0 && inventoryBookId && <p>Brak egzemplarzy dla podanej ksiazki.</p>}
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
                  <option value="AVAILABLE">Dostepny</option>
                  <option value="RESERVED">Zarezerwowany</option>
                  <option value="BORROWED">Wypozyczony</option>
                  <option value="MAINTENANCE">Niedostepny</option>
                  <option value="WITHDRAWN">Wycofany</option>
                </select>
              </div>
              <div className="form-field">
                <label>Tryb dostepu</label>
                <select value={copyForm.accessType} onChange={e => setCopyForm({ ...copyForm, accessType: e.target.value })}>
                  <option value="STORAGE">Magazyn</option>
                  <option value="OPEN_STACK">Wypozyczalnia</option>
                  <option value="REFERENCE">Czytelnia/Odwolawcze</option>
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
          <h2>Kolekcje ksiazek</h2>
          <p className="support-copy">Tworz i zarzadzaj kolekcjami ksiazek dla czytelnikow</p>

          <div className="collection-form">
            <h3>{editingCollection ? 'Edytuj kolekcje' : 'Nowa kolekcja'}</h3>

            <div className="form-field">
              <label>Nazwa kolekcji</label>
              <input
                value={collectionForm.name}
                onChange={e => setCollectionForm({ ...collectionForm, name: e.target.value })}
                placeholder="np. Wakacyjne czytanie"
              />
            </div>

            <div className="form-field">
              <label>Opis</label>
              <textarea
                value={collectionForm.description}
                onChange={e => setCollectionForm({ ...collectionForm, description: e.target.value })}
                placeholder="Krotki opis kolekcji..."
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
                {' '}Wyrozniona (wyswietlana w Polecanych)
              </label>
            </div>

            <div className="form-field">
              <label>Kolejnosc wyswietlania</label>
              <input
                type="number"
                value={collectionForm.displayOrder}
                onChange={e => setCollectionForm({ ...collectionForm, displayOrder: parseInt(e.target.value) || 0 })}
              />
            </div>

            <div className="book-selector">
              <h4>Ksiazki w kolekcji</h4>
              <input
                className="book-selector__search"
                value={collectionBookSearch}
                onChange={e => {
                  setCollectionBookSearch(e.target.value)
                  searchBooksForCollection(e.target.value)
                }}
                placeholder="Szukaj ksiazek do dodania..."
              />

              {collectionBookResults.length > 0 && (
                <div className="book-selector__results">
                  {collectionBookResults.map(book => (
                    <div
                      key={book.id}
                      className={`book-selector__item ${collectionForm.bookIds.includes(book.id) ? 'book-selector__item--selected' : ''}`}
                      onClick={() => toggleBookInCollection(book.id)}
                    >
                      <input type="checkbox" checked={collectionForm.bookIds.includes(book.id)} readOnly />
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
                        {book?.title || `Ksiazka #${bookId}`}
                        <button onClick={() => toggleBookInCollection(bookId)}>-</button>
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
              <button className="btn btn-primary" onClick={saveCollection} disabled={!collectionForm.name || loading}>
                {editingCollection ? 'Zaktualizuj' : 'Utworz kolekcje'}
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
                      <span>Ksiazek: {collection.bookCount}</span>
                      <span>Kurator: {collection.curatedBy}</span>
                      {collection.featured && <span>Wyrozniona</span>}
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
                      Edytuj
                    </button>
                    <button className="btn btn-sm btn-danger" onClick={() => deleteCollection(collection.id)}>
                      Usun
                    </button>
                  </div>
                </div>

                {collection.description && (
                  <p className="support-copy">{collection.description}</p>
                )}

                <div className="collection-books-preview">
                  {collection.books?.slice(0, 5).map(book => (
                    <div key={book.id} className="collection-book-mini" title={book.title}>
                      K
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
                Brak kolekcji. Utworz pierwsza kolekcje uzywajac formularza powyzej.
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  )
}

