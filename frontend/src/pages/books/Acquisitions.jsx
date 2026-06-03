import React, { useEffect, useMemo, useRef, useState } from 'react'
import { apiFetch } from '../../api'
import { useAuth } from '../../context/AuthContext'
import { acquisitionService } from '../../services/acquisitionService'

const emptySupplierForm = {
  name: '',
  contactEmail: '',
  contactPhone: '',
  city: '',
  notes: ''
}

const emptyOrderForm = {
  supplierId: '',
  budgetId: '',
  title: '',
  totalAmount: '',
  currency: 'PLN',
  expectedAt: '',
  referenceNumber: '',
  description: ''
}

const emptyWeedingForm = {
  bookId: '',
  copyId: '',
  reason: '',
  action: 'WITHDRAW',
  conditionState: '',
  notes: ''
}

const normalizeList = (payload) => {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.items)) return payload.items
  return []
}

const formatMoney = (value, currency = 'PLN') => {
  const amount = Number(value ?? 0)
  if (!Number.isFinite(amount)) return `0.00 ${currency}`
  return `${amount.toFixed(2)} ${currency}`
}

const statusLabels = {
  DRAFT: 'Szkic',
  SUBMITTED: 'Zgłoszone',
  ORDERED: 'Zamówione',
  RECEIVED: 'Przyjęte',
  CANCELLED: 'Anulowane'
}

export default function Acquisitions() {
  const { user, token } = useAuth()
  const roles = user?.roles || []
  const canManageAcquisitions = roles.includes('ROLE_LIBRARIAN') || roles.includes('ROLE_ADMIN')
  const currentYear = String(new Date().getFullYear())

  const [suppliers, setSuppliers] = useState([])
  const [budgets, setBudgets] = useState([])
  const [orders, setOrders] = useState([])
  const [weeding, setWeeding] = useState([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [message, setMessage] = useState(null)
  const [budgetSummary, setBudgetSummary] = useState(null)
  const [editingBudgetId, setEditingBudgetId] = useState(null)
  const [budgetEditForm, setBudgetEditForm] = useState({ name: '', fiscalYear: '', allocatedAmount: '', currency: 'PLN' })
  const [budgetExpenseForm, setBudgetExpenseForm] = useState({ budgetId: '', amount: '', description: '' })
  const [budgetExpenseSubmitting, setBudgetExpenseSubmitting] = useState(false)

  const [supplierForm, setSupplierForm] = useState(emptySupplierForm)
  const [budgetForm, setBudgetForm] = useState({ name: '', fiscalYear: currentYear, allocatedAmount: '', currency: 'PLN' })
  const [orderForm, setOrderForm] = useState(emptyOrderForm)
  const [weedingForm, setWeedingForm] = useState(emptyWeedingForm)
  const [weedingBookQuery, setWeedingBookQuery] = useState('')
  const [weedingBookResults, setWeedingBookResults] = useState([])
  const [weedingCopies, setWeedingCopies] = useState([])
  const [selectedWeedingBook, setSelectedWeedingBook] = useState(null)
  const weedingBookSearchSeqRef = useRef(0)

  const activeSuppliers = useMemo(() => suppliers.filter(supplier => supplier.active !== false), [suppliers])
  const openBudgets = useMemo(() => budgets.filter(budget => Number(budget.allocatedAmount ?? budget.amount ?? 0) > 0), [budgets])
  const pendingOrders = useMemo(() => orders.filter(order => !['RECEIVED', 'CANCELLED'].includes(order.status)), [orders])

  const overviewItems = useMemo(() => ([
    { label: 'Dostawcy', value: activeSuppliers.length, caption: 'aktywni partnerzy' },
    { label: 'Budżety', value: budgets.length, caption: 'pule finansowania' },
    { label: 'Zamówienia', value: pendingOrders.length, caption: 'do obsługi' },
    { label: 'Ubytki', value: weeding.length, caption: 'protokoły wycofań' },
  ]), [activeSuppliers.length, budgets.length, pendingOrders.length, weeding.length])

  useEffect(() => {
    if (canManageAcquisitions && token) {
      loadAll()
    } else if (canManageAcquisitions && !token) {
      setError('Sesja wygasła albo nie jesteś zalogowany. Zaloguj się ponownie, aby zarządzać akcesjami.')
    }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [canManageAcquisitions, token])

  async function loadAll() {
    setLoading(true)
    setError(null)
    try {
      const [suppliersData, budgetsData, ordersData, weedingData] = await Promise.all([
        acquisitionService.listSuppliers(),
        acquisitionService.listBudgets(),
        acquisitionService.listOrders(),
        acquisitionService.listWeeding(),
      ])
      setSuppliers(normalizeList(suppliersData))
      setBudgets(normalizeList(budgetsData))
      setOrders(normalizeList(ordersData))
      setWeeding(normalizeList(weedingData))
    } catch (err) {
      if (err.status === 401 || err.message === 'No refresh token available' || err.message === 'Token refresh failed') {
        setError('Sesja wygasła. Zaloguj się ponownie, aby pobrać dane akcesji.')
      } else {
        setError(err.message || 'Nie udało się pobrać danych akcesji.')
      }
    } finally {
      setLoading(false)
    }
  }

  function clearMessages() {
    setError(null)
    setMessage(null)
  }

  async function handleBudgetSummary(id) {
    clearMessages()
    try {
      const data = await acquisitionService.getBudgetSummary(id)
      setBudgetSummary(data)
      setMessage('Pobrano podsumowanie budżetu.')
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać podsumowania budżetu.')
    }
  }

  async function handleCreateSupplier(event) {
    event.preventDefault()
    clearMessages()
    if (!supplierForm.name.trim()) {
      setError('Podaj nazwę dostawcy.')
      return
    }

    try {
      await acquisitionService.createSupplier({
        name: supplierForm.name.trim(),
        contactEmail: supplierForm.contactEmail.trim() || undefined,
        contactPhone: supplierForm.contactPhone.trim() || undefined,
        city: supplierForm.city.trim() || undefined,
        notes: supplierForm.notes.trim() || undefined
      })
      setMessage('Dodano dostawcę.')
      setSupplierForm(emptySupplierForm)
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać dostawcy.')
    }
  }

  async function handleCreateBudget(event) {
    event.preventDefault()
    clearMessages()
    const allocatedAmount = Number(budgetForm.allocatedAmount)
    if (!budgetForm.name.trim() || !budgetForm.fiscalYear || !Number.isFinite(allocatedAmount) || allocatedAmount <= 0) {
      setError('Podaj nazwę, rok i dodatnią kwotę budżetu.')
      return
    }

    try {
      await acquisitionService.createBudget({
        name: budgetForm.name.trim(),
        fiscalYear: String(budgetForm.fiscalYear),
        allocatedAmount,
        currency: budgetForm.currency,
      })
      setMessage('Dodano budżet.')
      setBudgetForm({ name: '', fiscalYear: currentYear, allocatedAmount: '', currency: 'PLN' })
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać budżetu.')
    }
  }

  function startBudgetEdit(budget) {
    setEditingBudgetId(budget.id)
    setBudgetEditForm({
      name: budget.name || '',
      fiscalYear: budget.fiscalYear || currentYear,
      allocatedAmount: String(budget.allocatedAmount ?? budget.amount ?? ''),
      currency: budget.currency || 'PLN'
    })
    clearMessages()
  }

  async function handleUpdateBudget(event) {
    event.preventDefault()
    clearMessages()
    if (!editingBudgetId) return

    const allocatedAmount = Number(budgetEditForm.allocatedAmount)
    if (!budgetEditForm.name.trim() || !budgetEditForm.fiscalYear || !Number.isFinite(allocatedAmount) || allocatedAmount <= 0) {
      setError('Podaj nazwę, rok i dodatnią kwotę budżetu.')
      return
    }

    try {
      await acquisitionService.updateBudget(editingBudgetId, {
        name: budgetEditForm.name.trim(),
        fiscalYear: String(budgetEditForm.fiscalYear),
        allocatedAmount,
        currency: budgetEditForm.currency
      })
      setMessage('Budżet został zaktualizowany.')
      setEditingBudgetId(null)
      setBudgetEditForm({ name: '', fiscalYear: '', allocatedAmount: '', currency: 'PLN' })
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się zaktualizować budżetu.')
    }
  }

  async function handleAddBudgetExpense(event) {
    event.preventDefault()
    if (budgetExpenseSubmitting) return
    clearMessages()
    const amount = Number(budgetExpenseForm.amount)
    if (!budgetExpenseForm.budgetId || !Number.isFinite(amount) || amount <= 0 || !budgetExpenseForm.description.trim()) {
      setError('Wybierz budżet, podaj dodatnią kwotę i opis wydatku.')
      return
    }

    try {
      setBudgetExpenseSubmitting(true)
      await acquisitionService.addExpense(Number(budgetExpenseForm.budgetId), {
        amount,
        description: budgetExpenseForm.description.trim()
      })
      setMessage('Wydatek został dodany do budżetu.')
      setBudgetExpenseForm({ budgetId: '', amount: '', description: '' })
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać wydatku do budżetu.')
    } finally {
      setBudgetExpenseSubmitting(false)
    }
  }

  async function handleCreateOrder(event) {
    event.preventDefault()
    clearMessages()
    const totalAmount = Number(orderForm.totalAmount)
    if (!orderForm.supplierId || !orderForm.title.trim() || !Number.isFinite(totalAmount) || totalAmount <= 0) {
      setError('Wybierz dostawcę oraz podaj tytuł i kwotę zamówienia.')
      return
    }

    try {
      await acquisitionService.createOrder({
        supplierId: Number(orderForm.supplierId),
        budgetId: orderForm.budgetId ? Number(orderForm.budgetId) : undefined,
        title: orderForm.title.trim(),
        totalAmount,
        currency: orderForm.currency,
        expectedAt: orderForm.expectedAt || undefined,
        referenceNumber: orderForm.referenceNumber.trim() || undefined,
        description: orderForm.description.trim() || undefined
      })
      setMessage('Dodano zamówienie.')
      setOrderForm(emptyOrderForm)
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać zamówienia.')
    }
  }

  async function handleReceiveOrder(order) {
    clearMessages()
    try {
      await acquisitionService.receiveOrder(order.id, {
        expenseAmount: order.totalAmount,
        expenseDescription: `Zakup książek - zamówienie #${order.id}`
      })
      setMessage('Przyjęto zamówienie i zaktualizowano budżet, jeśli był przypisany.')
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się przyjąć zamówienia.')
    }
  }

  async function handleCancelOrder(id) {
    clearMessages()
    try {
      await acquisitionService.cancelOrder(id)
      setMessage('Anulowano zamówienie.')
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się anulować zamówienia.')
    }
  }

  async function searchWeedingBooks(query) {
    const term = (query || '').trim()
    const requestSeq = ++weedingBookSearchSeqRef.current
    if (term.length < 2) {
      setWeedingBookResults([])
      return
    }

    try {
      const data = await apiFetch(`/api/books?q=${encodeURIComponent(term)}&limit=10`)
      if (requestSeq !== weedingBookSearchSeqRef.current) return
      setWeedingBookResults(normalizeList(data))
    } catch (err) {
      if (requestSeq !== weedingBookSearchSeqRef.current) return
      setError(err.message || 'Nie udało się wyszukać książki.')
    }
  }

  async function selectWeedingBook(book) {
    setSelectedWeedingBook(book)
    setWeedingBookQuery(book.title || `Książka #${book.id}`)
    setWeedingBookResults([])
    setWeedingForm(prev => ({ ...prev, bookId: String(book.id), copyId: '' }))
    setWeedingCopies([])

    try {
      const data = await apiFetch(`/api/admin/books/${book.id}/copies`)
      setWeedingCopies(normalizeList(data))
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać egzemplarzy książki.')
    }
  }

  async function handleCreateWeeding(event) {
    event.preventDefault()
    clearMessages()
    if (!weedingForm.bookId || !weedingForm.copyId) {
      setError('Wybierz książkę oraz konkretny egzemplarz do wycofania.')
      return
    }
    if (!weedingForm.reason.trim()) {
      setError('Podaj powód ubytku.')
      return
    }

    try {
      await acquisitionService.createWeeding({
        bookId: Number(weedingForm.bookId),
        copyId: Number(weedingForm.copyId),
        reason: weedingForm.reason.trim(),
        action: weedingForm.action || undefined,
        conditionState: weedingForm.conditionState || undefined,
        notes: weedingForm.notes.trim() || undefined
      })
      setMessage('Dodano protokół ubytku i wycofano egzemplarz.')
      setWeedingForm(emptyWeedingForm)
      setWeedingBookQuery('')
      setWeedingBookResults([])
      setWeedingCopies([])
      setSelectedWeedingBook(null)
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać ubytku.')
    }
  }

  if (!canManageAcquisitions) {
    return (
      <div className="page">
        <div className="surface-card">Brak uprawnień do akcesji.</div>
      </div>
    )
  }

  return (
    <div className="page acquisitions-page">
      <header className="page-header">
        <div>
          <h1>Akcesje</h1>
          <p className="support-copy">Realna obsługa dostawców, budżetów, zamówień i ubytków.</p>
        </div>
        <div className="page-header__actions">
          <button type="button" className="btn btn-outline" onClick={loadAll} disabled={loading}>
            {loading ? 'Odświeżanie...' : 'Odśwież dane'}
          </button>
        </div>
      </header>

      <section className="surface-card acquisitions-hero">
        <div>
          <p className="acquisitions-hero__eyebrow">Panel operacyjny</p>
          <h2>Zakupy i wycofania z kontrolą budżetu</h2>
          <p className="support-copy">
            Formularze zapisują dane w backendzie i korzystają z tych samych kontraktów API, które obsługują testy funkcjonalne.
          </p>
        </div>
        <div className="card-grid card-grid--columns-3">
          {overviewItems.map(item => (
            <div key={item.label} className="stat-card">
              <div className="stat-title">{item.label}</div>
              <div className="stat-value">{item.value}</div>
              <div className="stat-subtitle">{item.caption}</div>
            </div>
          ))}
        </div>
      </section>

      {loading && <div className="surface-card empty-state">Ładowanie danych akcesji...</div>}
      {error && <div className="surface-card empty-state acquisitions-status acquisitions-status--error"><p className="error">{error}</p></div>}
      {message && <div className="surface-card empty-state acquisitions-status acquisitions-status--success"><p className="success">{message}</p></div>}

      <div className="acquisitions-layout">
        <section className="surface-card acquisitions-panel">
          <h3>Dostawcy</h3>
          <form className="form acquisitions-form" onSubmit={handleCreateSupplier}>
            <input placeholder="Nazwa dostawcy" value={supplierForm.name} onChange={e => setSupplierForm(prev => ({ ...prev, name: e.target.value }))} required />
            <input placeholder="E-mail kontaktowy" type="email" value={supplierForm.contactEmail} onChange={e => setSupplierForm(prev => ({ ...prev, contactEmail: e.target.value }))} />
            <input placeholder="Telefon" value={supplierForm.contactPhone} onChange={e => setSupplierForm(prev => ({ ...prev, contactPhone: e.target.value }))} />
            <input placeholder="Miasto" value={supplierForm.city} onChange={e => setSupplierForm(prev => ({ ...prev, city: e.target.value }))} />
            <textarea placeholder="Notatki" value={supplierForm.notes} onChange={e => setSupplierForm(prev => ({ ...prev, notes: e.target.value }))} />
            <button className="btn btn-primary" type="submit">Dodaj dostawcę</button>
          </form>
          <ul className="list list--bordered">
            {suppliers.map(supplier => (
              <li key={supplier.id || supplier.name}>
                <div className="list__title">{supplier.name}</div>
                <div className="list__meta">
                  <span>{supplier.contactEmail || 'brak e-maila'}</span>
                  {supplier.contactPhone && <span>{supplier.contactPhone}</span>}
                  {supplier.city && <span>{supplier.city}</span>}
                  {supplier.active === false && <span>nieaktywny</span>}
                </div>
              </li>
            ))}
          </ul>
        </section>

        <section className="surface-card acquisitions-panel">
          <h3>Budżety</h3>
          <p className="support-copy">Dodawaj pule finansowania, koryguj limity i rejestruj ręczne wydatki bez przechodzenia do raportów.</p>
          <form className="form acquisitions-form" onSubmit={handleCreateBudget}>
            <input placeholder="Nazwa budżetu" value={budgetForm.name} onChange={e => setBudgetForm(prev => ({ ...prev, name: e.target.value }))} required />
            <div className="form-row form-row--two">
              <input placeholder="Rok" type="number" min="2000" max="2100" value={budgetForm.fiscalYear} onChange={e => setBudgetForm(prev => ({ ...prev, fiscalYear: e.target.value }))} required />
              <select value={budgetForm.currency} onChange={e => setBudgetForm(prev => ({ ...prev, currency: e.target.value }))}>
                <option value="PLN">PLN</option>
                <option value="EUR">EUR</option>
                <option value="USD">USD</option>
              </select>
            </div>
            <input placeholder="Kwota przydzielona" type="number" min="0.01" step="0.01" value={budgetForm.allocatedAmount} onChange={e => setBudgetForm(prev => ({ ...prev, allocatedAmount: e.target.value }))} required />
            <button className="btn btn-primary" type="submit">Dodaj budżet</button>
          </form>
          <form className="form acquisitions-form acquisitions-form--subtle" onSubmit={handleAddBudgetExpense}>
            <strong>Dodaj wydatek do budżetu</strong>
            <select value={budgetExpenseForm.budgetId} onChange={e => setBudgetExpenseForm(prev => ({ ...prev, budgetId: e.target.value }))} required>
              <option value="">Wybierz budżet</option>
              {budgets.map(budget => (
                <option key={budget.id} value={budget.id}>{budget.name} ({budget.currency || 'PLN'})</option>
              ))}
            </select>
            <div className="form-row form-row--two">
              <input placeholder="Kwota wydatku" type="number" min="0.01" step="0.01" value={budgetExpenseForm.amount} onChange={e => setBudgetExpenseForm(prev => ({ ...prev, amount: e.target.value }))} required />
              <input placeholder="Opis wydatku" value={budgetExpenseForm.description} onChange={e => setBudgetExpenseForm(prev => ({ ...prev, description: e.target.value }))} required />
            </div>
            <button className="btn btn-outline" type="submit" disabled={budgetExpenseSubmitting}>
              {budgetExpenseSubmitting ? 'Zapisywanie...' : 'Zaksięguj wydatek'}
            </button>
          </form>
          <ul className="list list--bordered">
            {budgets.map(budget => {
              const allocated = Number(budget.allocatedAmount ?? budget.amount ?? 0)
              const spent = Number(budget.spentAmount ?? budget.spent ?? 0)
              const remaining = allocated - spent
              return (
                <li key={budget.id || budget.name}>
                  <div className="list__title">{budget.name}</div>
                  <div className="list__meta">
                    <span>Rok: {budget.fiscalYear ?? '-'}</span>
                    <span>Przydzielono: {formatMoney(allocated, budget.currency)}</span>
                    <span>Wydano: {formatMoney(spent, budget.currency)}</span>
                    <span>Pozostało: {formatMoney(remaining, budget.currency)}</span>
                  </div>
                  <div className="list__actions">
                    <button className="btn btn-outline btn-sm" type="button" onClick={() => handleBudgetSummary(budget.id)}>
                      Podsumowanie
                    </button>
                    <button className="btn btn-outline btn-sm" type="button" onClick={() => startBudgetEdit(budget)}>
                      Edytuj
                    </button>
                  </div>
                  {editingBudgetId === budget.id && (
                    <form className="form acquisitions-form acquisitions-form--inline" onSubmit={handleUpdateBudget}>
                      <input placeholder="Nazwa budżetu" value={budgetEditForm.name} onChange={e => setBudgetEditForm(prev => ({ ...prev, name: e.target.value }))} required />
                      <div className="form-row form-row--two">
                        <input placeholder="Rok" type="number" min="2000" max="2100" value={budgetEditForm.fiscalYear} onChange={e => setBudgetEditForm(prev => ({ ...prev, fiscalYear: e.target.value }))} required />
                        <select value={budgetEditForm.currency} onChange={e => setBudgetEditForm(prev => ({ ...prev, currency: e.target.value }))}>
                          <option value="PLN">PLN</option>
                          <option value="EUR">EUR</option>
                          <option value="USD">USD</option>
                        </select>
                      </div>
                      <input placeholder="Kwota przydzielona" type="number" min="0.01" step="0.01" value={budgetEditForm.allocatedAmount} onChange={e => setBudgetEditForm(prev => ({ ...prev, allocatedAmount: e.target.value }))} required />
                      <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
                        <button className="btn btn-primary btn-sm" type="submit">Zapisz budżet</button>
                        <button className="btn btn-outline btn-sm" type="button" onClick={() => setEditingBudgetId(null)}>Anuluj</button>
                      </div>
                    </form>
                  )}
                </li>
              )
            })}
          </ul>
          {budgetSummary && (
            <div className="surface-card acquisitions-summary">
              <strong>Podsumowanie budżetu</strong>
              <div className="compact-card__row"><span className="label">Przydzielono</span><span className="value">{formatMoney(budgetSummary.allocated ?? budgetSummary.allocatedAmount, budgetSummary.currency)}</span></div>
              <div className="compact-card__row"><span className="label">Wydano</span><span className="value">{formatMoney(budgetSummary.spent ?? budgetSummary.spentAmount, budgetSummary.currency)}</span></div>
              <div className="compact-card__row"><span className="label">Pozostało</span><span className="value">{formatMoney(budgetSummary.remaining ?? budgetSummary.remainingAmount, budgetSummary.currency)}</span></div>
            </div>
          )}
        </section>

        <section className="surface-card surface-card--wide acquisitions-panel acquisitions-panel--wide">
          <h3>Zamówienia</h3>
          <form className="form acquisitions-form" onSubmit={handleCreateOrder}>
            <div className="form-row form-row--two">
              <select value={orderForm.supplierId} onChange={e => setOrderForm(prev => ({ ...prev, supplierId: e.target.value }))} required>
                <option value="">Wybierz dostawcę</option>
                {activeSuppliers.map(supplier => (
                  <option key={supplier.id} value={supplier.id}>{supplier.name || `Dostawca #${supplier.id}`}</option>
                ))}
              </select>
              <select
                value={orderForm.budgetId}
                onChange={e => {
                  const budget = budgets.find(item => String(item.id) === e.target.value)
                  setOrderForm(prev => ({ ...prev, budgetId: e.target.value, currency: budget?.currency || prev.currency }))
                }}
              >
                <option value="">Bez budżetu</option>
                {openBudgets.map(budget => (
                  <option key={budget.id} value={budget.id}>{budget.name} ({budget.currency || 'PLN'})</option>
                ))}
              </select>
            </div>
            <input placeholder="Tytuł zamówienia" value={orderForm.title} onChange={e => setOrderForm(prev => ({ ...prev, title: e.target.value }))} required />
            <div className="form-row form-row--two">
              <input placeholder="Kwota" type="number" min="0.01" step="0.01" value={orderForm.totalAmount} onChange={e => setOrderForm(prev => ({ ...prev, totalAmount: e.target.value }))} required />
              <select value={orderForm.currency} onChange={e => setOrderForm(prev => ({ ...prev, currency: e.target.value }))}>
                <option value="PLN">PLN</option>
                <option value="EUR">EUR</option>
                <option value="USD">USD</option>
              </select>
            </div>
            <div className="form-row form-row--two">
              <input placeholder="Numer referencyjny" value={orderForm.referenceNumber} onChange={e => setOrderForm(prev => ({ ...prev, referenceNumber: e.target.value }))} />
              <input type="date" value={orderForm.expectedAt} onChange={e => setOrderForm(prev => ({ ...prev, expectedAt: e.target.value }))} />
            </div>
            <textarea placeholder="Opis zamówienia" value={orderForm.description} onChange={e => setOrderForm(prev => ({ ...prev, description: e.target.value }))} />
            <button className="btn btn-primary" type="submit">Dodaj zamówienie</button>
          </form>
          <ul className="list list--bordered">
            {orders.map(order => (
              <li key={order.id}>
                <div className="list__title">{order.title || `Zamówienie #${order.id}`}</div>
                <div className="list__meta">
                  <span>Status: {statusLabels[order.status] || order.status || '-'}</span>
                  <span>Dostawca: {order.supplier?.name || '-'}</span>
                  <span>Budżet: {order.budget?.name || 'brak'}</span>
                  <span>Kwota: {formatMoney(order.totalAmount ?? order.amount, order.currency)}</span>
                </div>
                {!['RECEIVED', 'CANCELLED'].includes(order.status) && (
                  <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
                    <button className="btn btn-outline btn-sm" type="button" onClick={() => handleReceiveOrder(order)}>Przyjmij</button>
                    <button className="btn btn-outline btn-sm" type="button" onClick={() => handleCancelOrder(order.id)}>Anuluj</button>
                  </div>
                )}
              </li>
            ))}
          </ul>
        </section>

        <section className="surface-card acquisitions-panel">
          <h3>Ubytki</h3>
          <form className="form acquisitions-form" onSubmit={handleCreateWeeding}>
            <div className="form-field" style={{ position: 'relative' }}>
              <input
                placeholder="Wyszukaj książkę"
                value={weedingBookQuery}
                onChange={e => {
                  const value = e.target.value
                  setWeedingBookQuery(value)
                  setSelectedWeedingBook(null)
                  setWeedingCopies([])
                  setWeedingForm(prev => ({ ...prev, bookId: '', copyId: '' }))
                  searchWeedingBooks(value)
                }}
                required
              />
              {weedingBookResults.length > 0 && (
                <div className="autocomplete-menu">
                  {weedingBookResults.map(book => (
                    <button key={book.id} type="button" className="autocomplete-menu__item" onClick={() => selectWeedingBook(book)}>
                      <strong>{book.title || `Książka #${book.id}`}</strong>
                      {book.author?.name && <span>{book.author.name}</span>}
                    </button>
                  ))}
                </div>
              )}
            </div>
            {selectedWeedingBook && (
              <select value={weedingForm.copyId} onChange={e => setWeedingForm(prev => ({ ...prev, copyId: e.target.value }))} required>
                <option value="">Wybierz egzemplarz</option>
                {weedingCopies.map(copy => (
                  <option key={copy.id} value={copy.id}>
                    {copy.inventoryCode || `Egzemplarz #${copy.id}`} - {copy.status || copy.state || 'status nieznany'}
                  </option>
                ))}
              </select>
            )}
            <input placeholder="Powód ubytku" value={weedingForm.reason} onChange={e => setWeedingForm(prev => ({ ...prev, reason: e.target.value }))} required />
            <div className="form-row form-row--two">
              <select value={weedingForm.conditionState} onChange={e => setWeedingForm(prev => ({ ...prev, conditionState: e.target.value }))}>
                <option value="">Stan egzemplarza</option>
                <option value="DAMAGED">Uszkodzony</option>
                <option value="LOST">Zagubiony</option>
                <option value="OUTDATED">Nieaktualny</option>
              </select>
              <select value={weedingForm.action} onChange={e => setWeedingForm(prev => ({ ...prev, action: e.target.value }))}>
                <option value="WITHDRAW">Wycofanie</option>
                <option value="DISPOSAL">Utylizacja</option>
                <option value="TRANSFER">Przekazanie</option>
              </select>
            </div>
            <textarea placeholder="Uwagi" value={weedingForm.notes} onChange={e => setWeedingForm(prev => ({ ...prev, notes: e.target.value }))} />
            <button className="btn btn-primary" type="submit">Dodaj ubytek</button>
          </form>
          <ul className="list list--bordered">
            {weeding.map(entry => (
              <li key={entry.id || `${entry.bookId}-${entry.reason}`}>
                <div className="list__title">{entry.book?.title || entry.bookTitle || (entry.bookId ? `Książka #${entry.bookId}` : '-')}</div>
                <div className="list__meta">
                  <span>{entry.bookCopy?.inventoryCode || entry.copy?.inventoryCode || 'egzemplarz bez kodu'}</span>
                  <span>{entry.reason || 'Brak powodu'}</span>
                </div>
              </li>
            ))}
          </ul>
        </section>
      </div>
    </div>
  )
}
