
import React, { useEffect, useMemo, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import toast from 'react-hot-toast'
import { apiFetch } from '../api'
import { useAuth } from '../context/AuthContext'
import LibrarianDashboard from './LibrarianDashboard'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import FeedbackCard from '../components/ui/FeedbackCard'
import { logger } from '../utils/logger'

export default function LibrarianPanel() {
  const { user } = useAuth()
  const [searchParams] = useSearchParams()
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
  const [loanStatusFilter, setLoanStatusFilter] = useState('all')

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
  const [collectionSearch, setCollectionSearch] = useState('')
  const [expandedCollectionId, setExpandedCollectionId] = useState(null)

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
  const [reservationStatusFilter, setReservationStatusFilter] = useState('ACTIVE')
  const [reservationSearch, setReservationSearch] = useState('')
  const [fines, setFines] = useState([])
  const [fineSearch, setFineSearch] = useState('')
  const [expandedFineId, setExpandedFineId] = useState(null)
  const [fineForm, setFineForm] = useState({ userId: '', loanId: '', amount: '', currency: 'PLN', reason: '' })
  const [fineUserQuery, setFineUserQuery] = useState('')
  const [fineUserResults, setFineUserResults] = useState([])
  const [fineUserLoans, setFineUserLoans] = useState([])
  const [expandedLoanId, setExpandedLoanId] = useState(null)
  const [expandedReservationId, setExpandedReservationId] = useState(null)

  const [inventoryBookId, setInventoryBookId] = useState('')
  const [bookSearchQuery, setBookSearchQuery] = useState('')
  const [availableBooks, setAvailableBooks] = useState([])
  const [copies, setCopies] = useState([])
  const [selectedBook, setSelectedBook] = useState(null)
  const [inventorySort, setInventorySort] = useState('bookTitle')
  const [inventorySortDir, setInventorySortDir] = useState('asc')
  const [inventorySortOpen, setInventorySortOpen] = useState(true)
  const [copyForm, setCopyForm] = useState({
    inventoryCode: '',
    status: 'AVAILABLE',
    accessType: 'STORAGE',
    location: '',
    condition: ''
  })
  const [addCopyErrors, setAddCopyErrors] = useState({})
  const [editingCopy, setEditingCopy] = useState(null)
  const [editCopyForm, setEditCopyForm] = useState({
    inventoryCode: '',
    status: 'AVAILABLE',
    accessType: 'STORAGE',
    location: '',
    condition: ''
  })
  const [editErrors, setEditErrors] = useState({})

  const [returnModal, setReturnModal] = useState({ show: false, loan: null, fine: null })

  useEffect(() => {
    const tab = searchParams.get('tab')
    const allowedTabs = ['dashboard', 'stats', 'loans', 'create', 'reservations', 'fines', 'inventory', 'collections']
    if (tab && allowedTabs.includes(tab)) {
      setActiveTab(tab)
    }

    const status = searchParams.get('status')
    if (status) {
      const normalizedStatus = status.trim().toUpperCase()
      if (normalizedStatus === 'ALL') {
        setReservationStatusFilter('ALL')
      } else if (['ACTIVE', 'CANCELLED', 'FULFILLED', 'EXPIRED'].includes(normalizedStatus)) {
        setReservationStatusFilter(normalizedStatus)
      }
    }

    const loan = searchParams.get('loan')
    if (loan) {
      const normalizedLoan = loan.trim().toLowerCase()
      if (normalizedLoan === 'overdue' || normalizedLoan === 'all') {
        setLoanStatusFilter(normalizedLoan)
      }
    }
  }, [searchParams])

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

  // Przeładuj rezerwacje gdy zmienia się filtr statusu
  useEffect(() => {
    if (activeTab === 'reservations') {
      loadReservations()
    }
  }, [reservationStatusFilter])

  const filteredLoans = useMemo(() => {
    const name = loanSearchName.trim().toLowerCase()
    const title = loanSearchTitle.trim().toLowerCase()
    const now = new Date()
    return loans.filter(loan => {
      const userName = (loan.user?.name || loan.userName || '').toString().toLowerCase()
      const bookTitle = (loan.book?.title || loan.bookTitle || '').toString().toLowerCase()
      const nameMatch = !name || userName.includes(name)
      const titleMatch = !title || bookTitle.includes(title)
      if (!nameMatch || !titleMatch) return false

      if (loanStatusFilter === 'overdue') {
        const dueValue = loan.dueAt || loan.dueDate
        if (!dueValue || loan.returnedAt) return false
        return new Date(dueValue) < now
      }

      return true
    })
  }, [loans, loanSearchName, loanSearchTitle, loanStatusFilter])

  const hasLoanFilters = loanSearchName.trim() !== '' || loanSearchTitle.trim() !== '' || loanStatusFilter !== 'all'
  const availableCopies = useMemo(
    () => copies.filter(copy => ((copy.status || copy.state || '').toUpperCase() === 'AVAILABLE')),
    [copies]
  )
  const filteredReservations = useMemo(() => {
    const query = reservationSearch.trim().toLowerCase()
    return reservations.filter(reservation => {
      const statusMatch = reservationStatusFilter === 'ALL'
        ? true
        : (reservation.status || '').toUpperCase() === reservationStatusFilter
      if (!statusMatch) return false
      if (!query) return true
      const nameParts = [
        reservation.user?.name,
        reservation.userName,
        reservation.user?.firstName,
        reservation.user?.lastName,
        reservation.user?.email,
        reservation.userEmail
      ].filter(Boolean)
      const haystack = nameParts.join(' ').toLowerCase()
      return haystack.includes(query)
    })
  }, [reservations, reservationStatusFilter, reservationSearch])
  const filteredCollections = useMemo(() => {
    const query = collectionSearch.trim().toLowerCase()
    if (!query) return collections
    return collections.filter(collection => {
      const haystack = [
        collection.name,
        collection.description,
        collection.curatedBy
      ].filter(Boolean).join(' ').toLowerCase()
      return haystack.includes(query)
    })
  }, [collections, collectionSearch])
  const filteredFines = useMemo(() => {
    const query = fineSearch.trim().toLowerCase()
    if (!query) return fines
    return fines.filter(fine => {
      const nameParts = [
        fine.user?.name,
        fine.userName,
        fine.user?.firstName,
        fine.user?.lastName,
        fine.user?.email,
        fine.userEmail
      ].filter(Boolean)
      const haystack = nameParts.join(' ').toLowerCase()
      return haystack.includes(query)
    })
  }, [fines, fineSearch])
  const inventoryRows = useMemo(() => {
    const bookTitle = selectedBook?.title || copies[0]?.book?.title || 'N/A'
    const bookAuthor = selectedBook?.author?.name || copies[0]?.book?.author?.name || 'N/A'
    const totalCopies = copies.length
    return copies.map(copy => ({
      id: copy.id,
      bookTitle,
      bookAuthor,
      totalCopies,
      inventoryCode: copy.inventoryCode || `Egzemplarz #${copy.id}`,
      status: (copy.status || copy.state || '').toUpperCase() || 'N/A',
      accessType: copy.accessType || 'N/A',
      location: copy.location || '',
      condition: copy.conditionState || copy.condition || ''
    }))
  }, [copies, selectedBook])
  const inventoryCopyById = useMemo(() => {
    const map = new Map()
    copies.forEach(copy => map.set(copy.id, copy))
    return map
  }, [copies])
  const sortedInventoryRows = useMemo(() => {
    const direction = inventorySortDir === 'asc' ? 1 : -1
    const sorted = [...inventoryRows]
    sorted.sort((a, b) => {
      const leftValue = a[inventorySort]
      const rightValue = b[inventorySort]
      if (typeof leftValue === 'number' && typeof rightValue === 'number') {
        return (leftValue - rightValue) * direction
      }
      const left = String(leftValue ?? '').toLowerCase()
      const right = String(rightValue ?? '').toLowerCase()
      if (left < right) return -1 * direction
      if (left > right) return 1 * direction
      return 0
    })
    return sorted
  }, [inventoryRows, inventorySort, inventorySortDir])

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

  async function searchLoanUser(query) {
    if (!query || query.length < 2) {
      setLoanUserResults([])
      return
    }
    try {
      const data = await apiFetch(`/api/users/search?q=${encodeURIComponent(query)}`)
      setLoanUserResults(Array.isArray(data) ? data : data?.data || [])
    } catch (err) {
      setError(err.message || 'Nie udało się wyszukać użytkownika')
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
      setError(err.message || 'Nie udało się wyszukać książki')
    }
  }

  async function loadStats() {
    setLoading(true)
    setError(null)
    try {
      const data = await apiFetch('/api/statistics/dashboard')
      setStats({
        activeLoans: data?.activeLoans ?? 0,
        overdueLoans: data?.overdueLoans ?? 0,
        totalUsers: data?.totalUsers ?? 0,
        availableCopies: data?.availableCopies ?? 0
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
      setError(err.message || 'Nie udało się pobrać ustawień')
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
      setError(err.message || 'Nie udało się zapisać ustawień')
    }
  }

  async function loadReservations() {
    setLoading(true)
    setError(null)
    try {
      // Pobierz rezerwacje zgodnie z wybranym filtrem lub wszystkie jeśli ALL
      const statusParam = reservationStatusFilter === 'ALL' ? '' : `&status=${reservationStatusFilter}`
      const data = await apiFetch(`/api/reservations?history=true&limit=100${statusParam}`)
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

  async function prepareReservation(reservationId) {
    if (!confirm('Oznaczyć rezerwację jako przygotowaną? Użytkownik otrzyma powiadomienie.')) return
    setLoading(true)
    setError(null)
    setSuccess(null)
    try {
      await apiFetch(`/api/reservations/${reservationId}/prepare`, { method: 'POST' })
      setSuccess('Rezerwacja została oznaczona jako przygotowana. Powiadomienie wysłane.')
      loadReservations()
    } catch (err) {
      setError(err.message || 'Nie udało się oznaczyć rezerwacji jako przygotowanej')
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
      logger.error('Błąd wyszukiwania książek:', err)
    }
  }

  async function searchFineUser(query) {
    if (!query || query.length < 2) {
      setFineUserResults([])
      return
    }
    try {
      const data = await apiFetch(`/api/users/search?q=${encodeURIComponent(query)}`)
      setFineUserResults(Array.isArray(data) ? data : data?.data || [])
    } catch (err) {
      setError(err.message || 'Nie udało się wyszukać użytkownika')
    }
  }

  async function loadLoansForUser(userId) {
    if (!userId) {
      setFineUserLoans([])
      return
    }
    try {
      const data = await apiFetch(`/api/loans/user/${userId}?limit=50`)
      const list = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : []
      setFineUserLoans(list)
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać wypożyczeń użytkownika')
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

  async function createFine(e) {
    e.preventDefault()
    if (!fineForm.loanId || !fineForm.amount || !fineForm.reason.trim()) {
      setError('Wybierz wypożyczenie oraz podaj kwotę i powód')
      return
    }
    setLoading(true)
    setError(null)
    setSuccess(null)
    try {
      await apiFetch('/api/fines', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          loanId: Number(fineForm.loanId),
          amount: Number(fineForm.amount),
          currency: fineForm.currency || 'PLN',
          reason: fineForm.reason.trim()
        })
      })
      setSuccess('Opłata została utworzona')
      setFineForm({ userId: '', loanId: '', amount: '', currency: 'PLN', reason: '' })
      setFineUserQuery('')
      setFineUserResults([])
      setFineUserLoans([])
      loadFines()
    } catch (err) {
      setError(err.message || 'Nie udało się utworzyć opłaty')
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
    if (!confirm('Anulować należność?')) return
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
    cancelEditCopy()
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
      setError(err.message || 'Nie udało się pobrać egzemplarzy')
    } finally {
      setLoading(false)
    }
  }

  function startEditCopy(copy) {
    setEditingCopy(copy)
    setEditErrors({})
    setEditCopyForm({
      inventoryCode: copy.inventoryCode || '',
      status: (copy.status || copy.state || 'AVAILABLE').toUpperCase(),
      accessType: (copy.accessType || 'STORAGE').toUpperCase(),
      location: copy.location || '',
      condition: copy.conditionState || copy.condition || ''
    })
  }

  function cancelEditCopy() {
    setEditingCopy(null)
    setEditErrors({})
    setEditCopyForm({
      inventoryCode: '',
      status: 'AVAILABLE',
      accessType: 'STORAGE',
      location: '',
      condition: ''
    })
  }

  function validateEditCopy(form) {
    const errors = {}
    const normalizedCode = form.inventoryCode.trim().toUpperCase()
    if (!normalizedCode) {
      errors.inventoryCode = 'Kod inwentarzowy jest wymagany'
    } else if (normalizedCode.length > 60) {
      errors.inventoryCode = 'Kod inwentarzowy moze miec maksymalnie 60 znakow'
    } else if (!/^[A-Z0-9\\-_.]+$/.test(normalizedCode)) {
      errors.inventoryCode = 'Kod moze zawierac tylko litery, cyfry, -, _ oraz kropke'
    }
    if (!form.status) {
      errors.status = 'Wybierz status'
    }
    if (!form.accessType) {
      errors.accessType = 'Wybierz typ dostepu'
    }
    if (form.location && form.location.length > 120) {
      errors.location = 'Lokalizacja moze miec maksymalnie 120 znakow'
    }
    if (form.condition && form.condition.length > 120) {
      errors.condition = 'Stan moze miec maksymalnie 120 znakow'
    }
    return errors
  }

  function validateAddCopy(form) {
    const errors = {}
    const normalizedCode = form.inventoryCode.trim().toUpperCase()
    if (!normalizedCode) {
      errors.inventoryCode = 'Kod inwentarzowy jest wymagany'
    } else if (normalizedCode.length > 60) {
      errors.inventoryCode = 'Kod inwentarzowy moze miec maksymalnie 60 znakow'
    } else if (!/^[A-Z0-9\\-_.]+$/.test(normalizedCode)) {
      errors.inventoryCode = 'Kod moze zawierac tylko litery, cyfry, -, _ oraz kropke'
    }
    if (!form.status) {
      errors.status = 'Wybierz status'
    }
    if (!form.accessType) {
      errors.accessType = 'Wybierz typ dostepu'
    }
    if (form.location && form.location.length > 120) {
      errors.location = 'Lokalizacja moze miec maksymalnie 120 znakow'
    }
    if (form.condition && form.condition.length > 120) {
      errors.condition = 'Stan moze miec maksymalnie 120 znakow'
    }
    return errors
  }

  function formatFieldError(value) {
    if (!value) return null
    return Array.isArray(value) ? value.join(', ') : value
  }


  async function addCopy(e) {
    e.preventDefault()
    if (!inventoryBookId) {
      setError('Podaj ID ksiazki, do ktorej dodajesz egzemplarz')
      return
    }
    const validationErrors = validateAddCopy(copyForm)
    if (Object.keys(validationErrors).length > 0) {
      setAddCopyErrors(validationErrors)
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
      setAddCopyErrors({})
      loadCopies(inventoryBookId)
    } catch (err) {
      if (err.details && typeof err.details === 'object') {
        setAddCopyErrors(err.details)
      }
      setError(err.message || 'Nie udalo sie dodac egzemplarza')
    } finally {
      setLoading(false)
    }
  }

  async function updateCopy(e) {
    e.preventDefault()
    if (!editingCopy) return
    const validationErrors = validateEditCopy(editCopyForm)
    if (Object.keys(validationErrors).length > 0) {
      setEditErrors(validationErrors)
      return
    }
    setLoading(true)
    setError(null)
    setSuccess(null)
    try {
      const bookId = inventoryBookId || editingCopy.bookId || editingCopy.book?.id
      const payload = {
        inventoryCode: editCopyForm.inventoryCode.trim().toUpperCase(),
        status: editCopyForm.status,
        accessType: editCopyForm.accessType,
        location: editCopyForm.location,
        condition: editCopyForm.condition
      }
      const updated = await apiFetch(`/api/admin/books/${bookId}/copies/${editingCopy.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      setCopies(prev => prev.map(copy => (copy.id === editingCopy.id ? { ...copy, ...updated } : copy)))
      setSuccess('Zaktualizowano egzemplarz')
      cancelEditCopy()
    } catch (err) {
      if (err.details && typeof err.details === 'object') {
        setEditErrors(err.details)
      }
      setError(err.message || 'Nie udalo sie zaktualizowac egzemplarza')
    } finally {
      setLoading(false)
    }
  }

  async function deleteCopy(copy) {
    if (!confirm('Usunąć egzemplarz?')) return
    try {
      const bookId = inventoryBookId || copy.bookId || copy.book?.id
      await apiFetch(`/api/admin/books/${bookId}/copies/${copy.id}`, { method: 'DELETE' })
      setSuccess('Usunięto egzemplarz')
      loadCopies(bookId)
    } catch (err) {
      setError(err.message || 'Nie udało się usunąć egzemplarza')
    }
  }

  async function handleCreateLoan(e) {
    e.preventDefault()
    if (!loanForm.userId || !loanForm.bookId || !loanForm.copyId) {
      setError('Wybierz użytkownika, książkę i egzemplarz')
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

      setSuccess('Wypożyczenie zostało utworzone')
      setLoanForm({ userId: '', bookId: '', copyId: '', dueDate: '' })
      setLoanUserQuery('')
      setLoanUserResults([])
      setLoanBookQuery('')
      setLoanBookResults([])
      loadLoans()
    } catch (err) {
      setError(err.message || 'Nie udało się utworzyć wypożyczenia')
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
      logger.error('Book search failed:', err)
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
    <div className="page librarian-panel">
      <PageHeader
        title="Panel bibliotekarza"
        subtitle="Obsługa wypożyczeń i zarządzanie biblioteką"
      />

      {loading && <FeedbackCard variant="info">Trwa ładowanie danych...</FeedbackCard>}
      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}
      {success && <FeedbackCard variant="success">{success}</FeedbackCard>}

      <div className="tabs">
        <button className={`tab ${activeTab === 'dashboard' ? 'tab--active' : ''}`} onClick={() => setActiveTab('dashboard')}>
          Dashboard
        </button>
        <button className={`tab ${activeTab === 'stats' ? 'tab--active' : ''}`} onClick={() => setActiveTab('stats')}>
          Ustawienia
        </button>
        <button className={`tab ${activeTab === 'loans' ? 'tab--active' : ''}`} onClick={() => setActiveTab('loans')}>
          Wypożyczenia
        </button>
        <button className={`tab ${activeTab === 'create' ? 'tab--active' : ''}`} onClick={() => setActiveTab('create')}>
          Nowe wypożyczenie
        </button>
        <button className={`tab ${activeTab === 'reservations' ? 'tab--active' : ''}`} onClick={() => setActiveTab('reservations')}>
          Rezerwacje
        </button>
        <button className={`tab ${activeTab === 'fines' ? 'tab--active' : ''}`} onClick={() => setActiveTab('fines')}>
          Opłaty
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
              <p><strong>Książka:</strong> {returnModal.loan?.book?.title || 'N/A'}</p>
              <p><strong>Użytkownik:</strong> {returnModal.loan?.user?.name || returnModal.loan?.user?.email || 'N/A'}</p>
              <p><strong>Data wypożyczenia:</strong> {new Date(returnModal.loan?.borrowedAt).toLocaleDateString()}</p>
              <p><strong>Termin zwrotu:</strong> {new Date(returnModal.loan?.dueAt).toLocaleDateString()}</p>
              {returnModal.fine ? (
                <div className="fine-warning">
                  <h4 style={{ color: '#d32f2f', marginTop: '1rem' }}>Zwrot po terminie</h4>
                  <p><strong>Dni opóźnienia:</strong> {returnModal.fine.days}</p>
                  <p><strong>Kara do zapłaty:</strong> {returnModal.fine.amount.toFixed(2)} PLN</p>
                  <p style={{ fontSize: '0.9rem', color: '#666' }}>(0.50 PLN za każdy dzień opóźnienia)</p>
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
            <div className="form-field" style={{ maxWidth: '240px' }}>
              <label>Status</label>
              <select value={loanStatusFilter} onChange={e => setLoanStatusFilter(e.target.value)}>
                <option value="all">Wszystkie</option>
                <option value="overdue">Przeterminowane</option>
              </select>
            </div>
          </div>

          <h2>Aktywne wypożyczenia</h2>
          {loading && <p>Ładowanie...</p>}
          {!loading && filteredLoans.length === 0 && (
            <p>{hasLoanFilters ? 'Brak wyników wyszukiwania.' : 'Brak aktywnych wypożyczeń.'}</p>
          )}
          {!loading && filteredLoans.length > 0 && (
            <div className="compact-list">
              {filteredLoans.map(loan => {
                const isExpanded = expandedLoanId === loan.id
                const userLabel = loan.user?.name || loan.user?.email || 'N/A'
                const bookLabel = loan.book?.title || 'N/A'
                const dueLabel = loan.dueAt ? new Date(loan.dueAt).toLocaleDateString() : '-'
                const borrowedLabel = loan.borrowedAt ? new Date(loan.borrowedAt).toLocaleDateString() : '-'
                const isOverdue = !loan.returnedAt && loan.dueAt && new Date(loan.dueAt) < new Date()
                const statusLabel = loan.returnedAt
                  ? 'Zwracono'
                  : isOverdue
                    ? 'Przeterminowane'
                    : 'Aktywne'
                const detailsId = `loan-details-${loan.id}`

                return (
                  <div key={loan.id} className="compact-card">
                    <button
                      type="button"
                      className="compact-card__header"
                      onClick={() => setExpandedLoanId(isExpanded ? null : loan.id)}
                      aria-expanded={isExpanded}
                      aria-controls={detailsId}
                      aria-label={`${isExpanded ? 'Zwiń' : 'Rozwiń'} wypożyczenie ${bookLabel}`}
                    >
                      <div>
                        <div className="compact-card__title">{bookLabel}</div>
                        <div className="compact-card__subtitle">{userLabel}</div>
                      </div>
                      <div className="compact-card__summary">
                        <span className="compact-card__amount">{dueLabel}</span>
                        <span className={`compact-card__toggle ${isExpanded ? 'is-open' : ''}`} aria-hidden>{isExpanded ? 'v' : '>'}</span>
                      </div>
                    </button>

                    {isExpanded && (
                      <div id={detailsId} className="compact-card__details">
                        <div className="compact-card__row">
                          <span className="label">ID</span>
                          <span className="value">{loan.id}</span>
                        </div>
                        <div className="compact-card__row">
                          <span className="label">Status</span>
                          <span className="value">{statusLabel}</span>
                        </div>
                        <div className="compact-card__row">
                          <span className="label">Data wypożyczenia</span>
                          <span className="value">{borrowedLabel}</span>
                        </div>
                        <div className="compact-card__row">
                          <span className="label">Termin zwrotu</span>
                          <span className="value">{dueLabel}</span>
                        </div>
                        {!loan.returnedAt && (
                          <div className="compact-card__actions">
                            <button className="btn btn-sm btn-primary" onClick={() => handleReturnLoan(loan)} disabled={loading}>
                              Zwróć
                            </button>
                          </div>
                        )}
                      </div>
                    )}
                  </div>
                )
              })}
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
        <div className="surface-card">
            <h2>Ustawienia biblioteki</h2>
            {librarySettingsLoading && <p>Ładowanie...</p>}
            {!librarySettingsLoading && (
              <form className="form" onSubmit={updateLibrarySettings}>
                <div className="form-row form-row--two">
                  <div className="form-field">
                    <label>Limit wypożyczeń na użytkownika</label>
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
                    <label>Długość wypożyczenia (dni)</label>
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
      )}

      {activeTab === 'reservations' && (
        <div className="surface-card">
          <div className="section-header">
            <h2>Rezerwacje</h2>
            <div className="fines-toolbar">
              <label className="sr-only" htmlFor="reservation-search">Filtruj po imieniu i nazwisku</label>
              <input
                id="reservation-search"
                type="search"
                value={reservationSearch}
                onChange={event => setReservationSearch(event.target.value)}
                placeholder="Filtruj po imieniu i nazwisku"
              />
              <label className="sr-only" htmlFor="reservation-status-filter">Status</label>
              <select
                id="reservation-status-filter"
                value={reservationStatusFilter}
                onChange={e => setReservationStatusFilter(e.target.value)}
              >
                <option value="ALL">Wszystkie</option>
                <option value="ACTIVE">Aktywne</option>
                <option value="PREPARED">Przygotowane</option>
                <option value="EXPIRED">Przedawnione</option>
                <option value="FULFILLED">Zrealizowane</option>
                <option value="CANCELLED">Anulowane</option>
              </select>
              <button className="btn btn-secondary" onClick={loadReservations}>Odswiez</button>
            </div>
          </div>
          {loading && <p>Ladowanie...</p>}
          {!loading && filteredReservations.length === 0 && <p>Brak rezerwacji dla wybranego filtra.</p>}
          {!loading && filteredReservations.length > 0 && (
            <div className="compact-list">
              {filteredReservations.map(reservation => {
                const isExpanded = expandedReservationId === reservation.id
                const userLabel = reservation.user?.name || reservation.user?.email || reservation.userEmail || 'N/A'
                const bookLabel = reservation.book?.title || reservation.bookTitle || 'N/A'
                const statusLabel = reservation.status || 'nieznany'
                const expiryLabel = reservation.expiresAt ? new Date(reservation.expiresAt).toLocaleString() : '-'
                const detailsId = `reservation-details-${reservation.id}`
                return (
                  <div key={reservation.id} className="compact-card">
                    <button
                      type="button"
                      className="compact-card__header"
                      onClick={() => setExpandedReservationId(isExpanded ? null : reservation.id)}
                      aria-expanded={isExpanded}
                      aria-controls={detailsId}
                      aria-label={`${isExpanded ? 'Zwin' : 'Rozwin'} rezerwacje ${bookLabel}`}
                    >
                      <div>
                        <div className="compact-card__title">{bookLabel}</div>
                        <div className="compact-card__subtitle">{userLabel}</div>
                      </div>
                      <div className="compact-card__summary">
                        <span className="compact-card__amount">{statusLabel}</span>
                        <span className={`compact-card__toggle ${isExpanded ? 'is-open' : ''}`} aria-hidden>{isExpanded ? 'v' : '>'}</span>
                      </div>
                    </button>

                    {isExpanded && (
                      <div id={detailsId} className="compact-card__details">
                        <div className="compact-card__row">
                          <span className="label">ID</span>
                          <span className="value">{reservation.id}</span>
                        </div>
                        <div className="compact-card__row">
                          <span className="label">Status</span>
                          <span className="value">{statusLabel}</span>
                        </div>
                        <div className="compact-card__row">
                          <span className="label">Wygasa</span>
                          <span className="value">{expiryLabel}</span>
                        </div>
                        <div className="compact-card__actions">
                          {reservation.status === 'ACTIVE' && (
                            <>
                              <button className="btn btn-sm btn-primary" onClick={() => prepareReservation(reservation.id)} disabled={loading}>
                                Oznacz jako przygotowaną
                              </button>
                              <button className="btn btn-sm" onClick={() => cancelReservation(reservation.id)} disabled={loading}>
                                Anuluj
                              </button>
                            </>
                          )}
                          {reservation.status === 'PREPARED' && (
                            <>
                              <button className="btn btn-sm btn-primary" onClick={() => fulfillReservation(reservation.id)} disabled={loading}>
                                Wydaj (utwórz wypożyczenie)
                              </button>
                              <button className="btn btn-sm" onClick={() => cancelReservation(reservation.id)} disabled={loading}>
                                Anuluj
                              </button>
                            </>
                          )}
                          {reservation.status === 'CANCELLED' && <span className="support-copy">Anulowana</span>}
                          {reservation.status === 'FULFILLED' && <span className="support-copy">Zrealizowana</span>}
                          {reservation.status === 'EXPIRED' && <span className="support-copy">Wygasła</span>}
                        </div>
                      </div>
                    )}
                  </div>
                )
              })}
            </div>
          )}
        </div>
      )}

      {activeTab === 'fines' && (
        <div className="surface-card">
          <div className="section-header">
            <h2>Oplaty i kary</h2>
            <div className="fines-toolbar">
              <label className="sr-only" htmlFor="fine-search">Filtruj po imieniu i nazwisku</label>
              <input
                id="fine-search"
                type="search"
                value={fineSearch}
                onChange={event => setFineSearch(event.target.value)}
                placeholder="Filtruj po imieniu i nazwisku"
              />
              <button className="btn btn-secondary" onClick={loadFines}>Odswiez</button>
            </div>
          </div>
          <div className="surface-card" style={{ marginBottom: 'var(--space-3)' }}>
            <h3>Dodaj nowa oplata</h3>
            <form className="form" onSubmit={createFine}>
              <div className="form-row form-row--two">
                <div className="form-field" style={{ position: 'relative' }}>
                  <label>Uzytkownik</label>
                  <input
                    value={fineUserQuery}
                    onChange={e => {
                      const value = e.target.value
                      setFineUserQuery(value)
                      setFineForm(prev => ({ ...prev, userId: '', loanId: '' }))
                      setFineUserLoans([])
                      searchFineUser(value)
                    }}
                    placeholder="Wpisz imie i nazwisko..."
                    required
                  />
                  {fineUserResults.length > 0 && (
                    <div className="dropdown-list">
                      {fineUserResults.map(u => (
                        <div
                          key={u.id}
                          className="dropdown-list__item"
                          onClick={() => {
                            setFineForm(prev => ({ ...prev, userId: String(u.id), loanId: '' }))
                            setFineUserQuery(u.name || u.email || `Uzytkownik #${u.id}`)
                            setFineUserResults([])
                            loadLoansForUser(u.id)
                          }}
                        >
                          <strong>{u.name || u.email || `Uzytkownik #${u.id}`}</strong>
                          {u.email && u.name && <div className="support-copy">{u.email}</div>}
                        </div>
                      ))}
                    </div>
                  )}
                </div>
                <div className="form-field">
                  <label>Wypozyczenie</label>
                  <select
                    value={fineForm.loanId}
                    onChange={e => setFineForm(prev => ({ ...prev, loanId: e.target.value }))}
                    required
                    disabled={fineUserLoans.length === 0}
                  >
                    <option value="">Wybierz wypozyczenie</option>
                    {fineUserLoans.map(loan => (
                      <option key={loan.id} value={loan.id}>
                        {loan.book?.title || 'Ksiazka'} (ID: {loan.id})
                      </option>
                    ))}
                  </select>
                  {fineUserQuery && fineUserLoans.length === 0 && (
                    <small className="support-copy">Brak aktywnych wypozyczen dla wybranego uzytkownika.</small>
                  )}
                </div>
              </div>
              <div className="form-row form-row--two">
                <div className="form-field">
                  <label>Kwota</label>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    value={fineForm.amount}
                    onChange={e => setFineForm(prev => ({ ...prev, amount: e.target.value }))}
                    required
                  />
                </div>
                <div className="form-field">
                  <label>Waluta</label>
                  <select
                    value={fineForm.currency}
                    onChange={e => setFineForm(prev => ({ ...prev, currency: e.target.value }))}
                  >
                    <option value="PLN">PLN</option>
                    <option value="EUR">EUR</option>
                    <option value="USD">USD</option>
                  </select>
                </div>
              </div>
              <div className="form-field">
                <label>Powod</label>
                <input
                  value={fineForm.reason}
                  onChange={e => setFineForm(prev => ({ ...prev, reason: e.target.value }))}
                  placeholder="Np. przetrzymanie"
                  required
                />
              </div>
              <div className="form-actions">
                <button type="submit" className="btn btn-primary" disabled={loading}>
                  Dodaj oplate
                </button>
              </div>
            </form>
          </div>
          {loading && <p>Ladowanie...</p>}
          {!loading && filteredFines.length === 0 && <p>Brak aktywnych oplat.</p>}
          {!loading && filteredFines.length > 0 && (
            <div className="compact-list">
              {filteredFines.map(fine => {
                const isExpanded = expandedFineId === fine.id
                const userLabel = fine.user?.name || fine.userName || fine.user?.email || fine.userEmail || 'N/A'
                const amountLabel = `${fine.amount} ${fine.currency || 'PLN'}`
                const statusLabel = fine.status || (fine.paidAt ? 'oplacono' : 'nalezna')
                const detailsId = `fine-details-${fine.id}`
                return (
                  <div key={fine.id} className="compact-card">
                    <button
                      type="button"
                      className="compact-card__header"
                      onClick={() => setExpandedFineId(isExpanded ? null : fine.id)}
                      aria-expanded={isExpanded}
                      aria-controls={detailsId}
                      aria-label={`${isExpanded ? 'Zwin' : 'Rozwin'} oplaty dla ${userLabel}`}
                    >
                      <div>
                        <div className="compact-card__title">{userLabel}</div>
                        <div className="compact-card__subtitle">{fine.reason || 'Oplata biblioteczna'}</div>
                      </div>
                      <div className="compact-card__summary">
                        <span className="compact-card__amount">{amountLabel}</span>
                        <span className={`compact-card__toggle ${isExpanded ? 'is-open' : ''}`} aria-hidden>{isExpanded ? 'v' : '>'}</span>
                      </div>
                    </button>

                    {isExpanded && (
                      <div id={detailsId} className="compact-card__details">
                        <div className="compact-card__row">
                          <span className="label">ID</span>
                          <span className="value">{fine.id}</span>
                        </div>
                        <div className="compact-card__row">
                          <span className="label">Status</span>
                          <span className="value">{statusLabel}</span>
                        </div>
                        {fine.createdAt && (
                          <div className="compact-card__row">
                            <span className="label">Utworzono</span>
                            <span className="value">{new Date(fine.createdAt).toLocaleDateString('pl-PL')}</span>
                          </div>
                        )}
                        {fine.paidAt && (
                          <div className="compact-card__row">
                            <span className="label">Oplacono</span>
                            <span className="value">{new Date(fine.paidAt).toLocaleDateString('pl-PL')}</span>
                          </div>
                        )}
                        {fine.reason && (
                          <div className="compact-card__row">
                            <span className="label">Powod</span>
                            <span className="value">{fine.reason}</span>
                          </div>
                        )}
                        {!fine.paidAt && (
                          <div className="compact-card__actions">
                            <button className="btn btn-sm btn-primary" onClick={() => payFine(fine.id)} disabled={loading}>
                              Oznacz jako oplacone
                            </button>
                            {isAdmin && (
                              <button className="btn btn-sm" onClick={() => cancelFine(fine.id)} disabled={loading}>
                                Anuluj
                              </button>
                            )}
                          </div>
                        )}
                      </div>
                    )}
                  </div>
                )
              })}
            </div>
          )}
        </div>
      )}

      {activeTab === 'inventory' && (
        <div className="grid two-columns">
          <div className="surface-card">
            <div className="section-header">
              <div>
                <h2>Egzemplarze ksiazek</h2>
                <p className="support-copy">Wybierz ksiazke, aby zobaczyc jej egzemplarze.</p>
              </div>
              <button
                type="button"
                className="btn btn-ghost"
                onClick={() => setInventorySortOpen(prev => !prev)}
                aria-expanded={inventorySortOpen}
                aria-controls="inventory-sort-panel"
              >
                {inventorySortOpen ? 'Ukryj sortowanie' : 'Pokaz sortowanie'}
              </button>
            </div>
            <div className="form-field" style={{ position: 'relative' }}>
              <label>Wybierz ksiazke do dodania egzemplarza</label>
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
                        setSelectedBook(book)
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
            {inventorySortOpen && (
              <div id="inventory-sort-panel" className="inventory-toolbar">
                <div className="form-field">
                  <label>Sortuj</label>
                  <select value={inventorySort} onChange={e => setInventorySort(e.target.value)}>
                    <option value="bookTitle">Tytul</option>
                    <option value="bookAuthor">Autor</option>
                    <option value="totalCopies">Liczba egzemplarzy</option>
                    <option value="inventoryCode">Kod</option>
                    <option value="status">Status</option>
                    <option value="accessType">Dostep</option>
                    <option value="location">Lokalizacja</option>
                  </select>
                </div>
                <div className="form-field">
                  <label>Kierunek</label>
                  <select value={inventorySortDir} onChange={e => setInventorySortDir(e.target.value)}>
                    <option value="asc">Rosnaco</option>
                    <option value="desc">Malejaco</option>
                  </select>
                </div>
              </div>
            )}
            {loading && <p>Ladowanie...</p>}
            {!loading && sortedInventoryRows.length > 0 && (
              <div className="table-container">
                <table className="data-table inventory-table">
                  <thead>
                    <tr>
                      <th>Tytul</th>
                      <th>Autor</th>
                      <th>Egzemplarze</th>
                      <th>Kod</th>
                      <th>Status</th>
                      <th>Dostep</th>
                      <th>Lokalizacja</th>
                      <th>Stan</th>
                      <th>Akcje</th>
                    </tr>
                  </thead>
                  <tbody>
                    {sortedInventoryRows.map(row => {
                      const copy = inventoryCopyById.get(row.id)
                      const isSelected = editingCopy?.id === row.id
                      return (
                      <tr
                        key={row.id}
                        className={isSelected ? 'inventory-row is-selected' : 'inventory-row'}
                        onClick={() => startEditCopy(copy)}
                      >
                        <td>{row.bookTitle}</td>
                        <td>{row.bookAuthor}</td>
                        <td>{row.totalCopies}</td>
                        <td>{row.inventoryCode}</td>
                        <td>{row.status}</td>
                        <td>{row.accessType}</td>
                        <td>{row.location || '-'}</td>
                        <td>{row.condition || '-'}</td>
                        <td>
                          <div className="table-actions">
                            <button
                              className="btn btn-outline btn-sm"
                              type="button"
                              onClick={event => {
                                event.stopPropagation()
                                startEditCopy(copy)
                              }}
                            >
                              Edytuj
                            </button>
                            <button
                              className="btn btn-danger btn-sm"
                              type="button"
                              onClick={event => {
                                event.stopPropagation()
                                deleteCopy(copy)
                              }}
                            >
                              Usun
                            </button>
                          </div>
                        </td>
                      </tr>
                      )
                    })}
                  </tbody>
                </table>
              </div>
            )}
            {!loading && sortedInventoryRows.length === 0 && inventoryBookId && <p>Brak egzemplarzy dla podanej ksiazki.</p>}
            {!loading && sortedInventoryRows.length === 0 && !inventoryBookId && (
              <p>Wybierz ksiazke, aby zobaczyc liste egzemplarzy.</p>
            )}
          </div>

          <div className="surface-card">
            {editingCopy ? (
              <>
                <h2>Edytuj egzemplarz</h2>
                <p className="support-copy">Edytujesz egzemplarz: {editingCopy.inventoryCode || `#${editingCopy.id}`}</p>
                <form className="form" onSubmit={updateCopy}>
                  <div className="form-field">
                    <label>Kod inwentarzowy</label>
                    <input
                      value={editCopyForm.inventoryCode}
                      onChange={e => setEditCopyForm({ ...editCopyForm, inventoryCode: e.target.value })}
                      required
                    />
                    {formatFieldError(editErrors.inventoryCode) && (
                      <div className="error">{formatFieldError(editErrors.inventoryCode)}</div>
                    )}
                  </div>
                  <div className="form-field">
                    <label>Status</label>
                    <select value={editCopyForm.status} onChange={e => setEditCopyForm({ ...editCopyForm, status: e.target.value })}>
                      <option value="AVAILABLE">Dostepny</option>
                      <option value="RESERVED">Zarezerwowany</option>
                      <option value="BORROWED">Wypozyczony</option>
                      <option value="MAINTENANCE">Niedostepny</option>
                      <option value="WITHDRAWN">Wycofany</option>
                    </select>
                    {formatFieldError(editErrors.status) && (
                      <div className="error">{formatFieldError(editErrors.status)}</div>
                    )}
                  </div>
                  <div className="form-field">
                    <label>Tryb dostepu</label>
                    <select value={editCopyForm.accessType} onChange={e => setEditCopyForm({ ...editCopyForm, accessType: e.target.value })}>
                      <option value="STORAGE">Magazyn</option>
                      <option value="OPEN_STACK">Wypozyczalnia</option>
                      <option value="REFERENCE">Czytelnia/Odwolawcze</option>
                    </select>
                    {formatFieldError(editErrors.accessType) && (
                      <div className="error">{formatFieldError(editErrors.accessType)}</div>
                    )}
                  </div>
                  <div className="form-field">
                    <label>Lokalizacja</label>
                    <input
                      value={editCopyForm.location}
                      onChange={e => setEditCopyForm({ ...editCopyForm, location: e.target.value })}
                    />
                    {formatFieldError(editErrors.location) && (
                      <div className="error">{formatFieldError(editErrors.location)}</div>
                    )}
                  </div>
                  <div className="form-field">
                    <label>Stan</label>
                    <input
                      value={editCopyForm.condition}
                      onChange={e => setEditCopyForm({ ...editCopyForm, condition: e.target.value })}
                    />
                    {formatFieldError(editErrors.condition) && (
                      <div className="error">{formatFieldError(editErrors.condition)}</div>
                    )}
                  </div>
                  <div className="form-actions">
                    <button type="button" className="btn btn-secondary" onClick={cancelEditCopy} disabled={loading}>
                      Anuluj
                    </button>
                    <button type="submit" className="btn btn-primary" disabled={loading}>
                      Aktualizuj
                    </button>
                  </div>
                </form>
              </>
            ) : (
              <>
                <h2>Dodaj egzemplarz</h2>
                <form className="form" onSubmit={addCopy}>
                  <div className="form-field">
                    <label>Kod inwentarzowy</label>
                    <input value={copyForm.inventoryCode} onChange={e => setCopyForm({ ...copyForm, inventoryCode: e.target.value })} required />
                    {formatFieldError(addCopyErrors.inventoryCode) && (
                      <div className="error">{formatFieldError(addCopyErrors.inventoryCode)}</div>
                    )}
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
                    {formatFieldError(addCopyErrors.status) && (
                      <div className="error">{formatFieldError(addCopyErrors.status)}</div>
                    )}
                  </div>
                  <div className="form-field">
                    <label>Tryb dostepu</label>
                    <select value={copyForm.accessType} onChange={e => setCopyForm({ ...copyForm, accessType: e.target.value })}>
                      <option value="STORAGE">Magazyn</option>
                      <option value="OPEN_STACK">Wypozyczalnia</option>
                      <option value="REFERENCE">Czytelnia/Odwolawcze</option>
                    </select>
                    {formatFieldError(addCopyErrors.accessType) && (
                      <div className="error">{formatFieldError(addCopyErrors.accessType)}</div>
                    )}
                  </div>
                  <div className="form-field">
                    <label>Lokalizacja</label>
                    <input value={copyForm.location} onChange={e => setCopyForm({ ...copyForm, location: e.target.value })} />
                    {formatFieldError(addCopyErrors.location) && (
                      <div className="error">{formatFieldError(addCopyErrors.location)}</div>
                    )}
                  </div>
                  <div className="form-field">
                    <label>Stan</label>
                    <input value={copyForm.condition} onChange={e => setCopyForm({ ...copyForm, condition: e.target.value })} />
                    {formatFieldError(addCopyErrors.condition) && (
                      <div className="error">{formatFieldError(addCopyErrors.condition)}</div>
                    )}
                  </div>
                  <button type="submit" className="btn btn-primary" disabled={!inventoryBookId || loading}>Dodaj egzemplarz</button>
                </form>
              </>
            )}
          </div>
        </div>
      )}

      {activeTab === 'collections' && (
        <div className="surface-card">
          <div className="section-header">
            <div>
              <h2>Kolekcje ksiazek</h2>
              <p className="support-copy">Tworz i zarzadzaj kolekcjami ksiazek dla czytelnikow</p>
            </div>
            <div className="fines-toolbar">
              <label className="sr-only" htmlFor="collection-search">Filtruj kolekcje</label>
              <input
                id="collection-search"
                type="search"
                value={collectionSearch}
                onChange={event => setCollectionSearch(event.target.value)}
                placeholder="Filtruj kolekcje"
              />
            </div>
          </div>

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

          <div className="compact-list">
            {filteredCollections.map(collection => {
              const isExpanded = expandedCollectionId === collection.id
              const detailsId = `collection-details-${collection.id}`
              return (
                <div key={collection.id} className={`compact-card ${collection.featured ? 'collection-card--featured' : ''}`}>
                  <button
                    type="button"
                    className="compact-card__header"
                    onClick={() => setExpandedCollectionId(isExpanded ? null : collection.id)}
                    aria-expanded={isExpanded}
                    aria-controls={detailsId}
                    aria-label={`${isExpanded ? 'Zwin' : 'Rozwin'} kolekcje ${collection.name}`}
                  >
                    <div>
                      <div className="compact-card__title">{collection.name}</div>
                      <div className="compact-card__subtitle">
                        {collection.curatedBy ? `Kurator: ${collection.curatedBy}` : 'Brak kuratora'}
                      </div>
                    </div>
                    <div className="compact-card__summary">
                      <span className="compact-card__amount">Ksiazek: {collection.bookCount}</span>
                      <span className={`compact-card__toggle ${isExpanded ? 'is-open' : ''}`} aria-hidden>{isExpanded ? 'v' : '>'}</span>
                    </div>
                  </button>

                  {isExpanded && (
                    <div id={detailsId} className="compact-card__details">
                      <div className="compact-card__row">
                        <span className="label">Status</span>
                        <span className="value">{collection.featured ? 'Wyrozniona' : 'Standardowa'}</span>
                      </div>
                      {collection.description && (
                        <div className="compact-card__row">
                          <span className="label">Opis</span>
                          <span className="value">{collection.description}</span>
                        </div>
                      )}
                      <div className="compact-card__row">
                        <span className="label">Ksiazki</span>
                        <span className="value">{collection.bookCount}</span>
                      </div>
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
                      <div className="compact-card__actions">
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
                  )}
                </div>
              )
            })}

            {filteredCollections.length === 0 && !loading && (
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


