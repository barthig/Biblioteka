import React, { useEffect, useMemo, useRef, useState } from 'react'
import { apiFetch } from '../../api'
import { useAuth } from '../../context/AuthContext'
import { acquisitionService } from '../../services/acquisitionService'

export default function Acquisitions() {
  const { user } = useAuth()
  const roles = user?.roles || []
  const canManageAcquisitions = roles.includes('ROLE_LIBRARIAN') || roles.includes('ROLE_ADMIN')
  const [suppliers, setSuppliers] = useState([])
  const [budgets, setBudgets] = useState([])
  const [orders, setOrders] = useState([])
  const [weeding, setWeeding] = useState([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [message, setMessage] = useState(null)
  const [budgetSummary, setBudgetSummary] = useState(null)

  const [supplierForm, setSupplierForm] = useState({ name: '', contact: '' })
  const currentYear = String(new Date().getFullYear())
  const [budgetForm, setBudgetForm] = useState({ name: '', fiscalYear: currentYear, allocatedAmount: '', currency: 'PLN' })
  const [orderForm, setOrderForm] = useState({ supplierId: '', title: '', amount: '' })
  const [weedingForm, setWeedingForm] = useState({ bookId: '', reason: '' })
  const [weedingBookQuery, setWeedingBookQuery] = useState('')
  const [weedingBookResults, setWeedingBookResults] = useState([])
  const weedingBookSearchSeqRef = useRef(0)

  const overviewItems = useMemo(() => ([
    {
      label: 'Dostawcy',
      value: suppliers.length,
      caption: 'aktywni partnerzy',
    },
    {
      label: 'Budżety',
      value: budgets.length,
      caption: 'otwarte pule',
    },
    {
      label: 'Zamówienia',
      value: orders.length,
      caption: 'w rejestrze',
    },
    {
      label: 'Ubytki',
      value: weeding.length,
      caption: 'protokóły wycofań',
    },
  ]), [budgets.length, orders.length, suppliers.length, weeding.length])

  useEffect(() => {
    if (canManageAcquisitions) {
      loadAll()
    }
  }, [canManageAcquisitions])

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
      setSuppliers(Array.isArray(suppliersData?.data) ? suppliersData.data : suppliersData || [])
      setBudgets(Array.isArray(budgetsData?.data) ? budgetsData.data : budgetsData || [])
      setOrders(Array.isArray(ordersData?.data) ? ordersData.data : ordersData || [])
      setWeeding(Array.isArray(weedingData?.data) ? weedingData.data : weedingData || [])
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać danych akcesji.')
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
    try {
      await acquisitionService.createSupplier(supplierForm)
      setMessage('Dodano dostawcę.')
      setSupplierForm({ name: '', contact: '' })
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać dostawcy.')
    }
  }

  async function handleCreateBudget(event) {
    event.preventDefault()
    clearMessages()
    try {
      await acquisitionService.createBudget({
        name: budgetForm.name,
        fiscalYear: Number(budgetForm.fiscalYear),
        allocatedAmount: Number(budgetForm.allocatedAmount),
        currency: budgetForm.currency,
      })
      setMessage('Dodano budżet.')
      setBudgetForm({ name: '', fiscalYear: currentYear, allocatedAmount: '', currency: 'PLN' })
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać budżetu.')
    }
  }

  async function handleCreateOrder(event) {
    event.preventDefault()
    clearMessages()
    const orderAmount = Number(orderForm.amount)
    if (!orderForm.supplierId || !orderForm.title.trim() || !Number.isFinite(orderAmount) || orderAmount <= 0) {
      setError('Wybierz dostawcę oraz podaj tytuł i kwotę zamówienia.')
      return
    }
    try {
      await acquisitionService.createOrder({
        supplierId: Number(orderForm.supplierId),
        title: orderForm.title.trim(),
        amount: orderAmount,
      })
      setMessage('Dodano zamówienie.')
      setOrderForm({ supplierId: '', title: '', amount: '' })
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać zamówienia.')
    }
  }

  async function handleReceiveOrder(id) {
    clearMessages()
    try {
      await acquisitionService.receiveOrder(id)
      setMessage('Przyjęto zamówienie.')
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

  async function handleCreateWeeding(event) {
    event.preventDefault()
    clearMessages()
    if (!weedingForm.bookId) {
      setError('Wybierz książkę z listy wyników.')
      return
    }
    if (!weedingForm.reason.trim()) {
      setError('Podaj powód ubytku.')
      return
    }
    try {
      await acquisitionService.createWeeding({
        bookId: Number(weedingForm.bookId),
        reason: weedingForm.reason.trim(),
      })
      setMessage('Dodano protokół ubytków.')
      setWeedingForm({ bookId: '', reason: '' })
      setWeedingBookQuery('')
      setWeedingBookResults([])
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać ubytku.')
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
      setWeedingBookResults(Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [])
    } catch (err) {
      if (requestSeq !== weedingBookSearchSeqRef.current) return
      setError(err.message || 'Nie udało się wyszukać książki.')
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
          <p className="support-copy">Dostawcy, budżety, zamówienia i ubytki.</p>
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
          <h2>Zakupy, budżety i wycofania w jednym widoku</h2>
          <p className="support-copy">
            Szybki podgląd danych i skrócone formularze pomagają wykonać najczęstsze operacje bez przeklikiwania się między ekranami.
          </p>
        </div>
        <div className="acquisitions-hero__chips">
          {overviewItems.map(item => (
            <div key={item.label} className="acquisitions-chip">
              <strong>{item.value}</strong>
              <span>{item.label}</span>
              <small>{item.caption}</small>
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
          <form className="form-row form-row--two acquisitions-form" onSubmit={handleCreateSupplier}>
            <input
              placeholder="Nazwa"
              value={supplierForm.name}
              onChange={e => setSupplierForm(prev => ({ ...prev, name: e.target.value }))}
            />
            <input
              placeholder="Kontakt"
              value={supplierForm.contact}
              onChange={e => setSupplierForm(prev => ({ ...prev, contact: e.target.value }))}
            />
            <button className="btn btn-primary" type="submit">Dodaj</button>
          </form>
          <ul className="list list--bordered">
            {suppliers.map(supplier => (
              <li key={supplier.id || supplier.name}>
                <div className="list__title">{supplier.name}</div>
                <div className="list__meta">{supplier.contact || supplier.email || ''}</div>
              </li>
            ))}
          </ul>
        </section>

        <section className="surface-card acquisitions-panel">
          <h3>Budżety</h3>
          <form className="form-row form-row--two acquisitions-form" onSubmit={handleCreateBudget}>
            <input
              placeholder="Nazwa"
              value={budgetForm.name}
              onChange={e => setBudgetForm(prev => ({ ...prev, name: e.target.value }))}
              required
            />
            <input
              placeholder="Rok"
              type="number"
              min="2000"
              max="2100"
              value={budgetForm.fiscalYear}
              onChange={e => setBudgetForm(prev => ({ ...prev, fiscalYear: e.target.value }))}
              required
            />
            <input
              placeholder="Kwota"
              type="number"
              min="0.01"
              step="0.01"
              value={budgetForm.allocatedAmount}
              onChange={e => setBudgetForm(prev => ({ ...prev, allocatedAmount: e.target.value }))}
              required
            />
            <select
              value={budgetForm.currency}
              onChange={e => setBudgetForm(prev => ({ ...prev, currency: e.target.value }))}
            >
              <option value="PLN">PLN</option>
              <option value="EUR">EUR</option>
              <option value="USD">USD</option>
            </select>
            <button className="btn btn-primary" type="submit">Dodaj</button>
          </form>
          <ul className="list list--bordered">
            {budgets.map(budget => (
              <li key={budget.id || budget.name}>
                <div className="list__title">{budget.name}</div>
                <div className="list__meta">
                  <span>Rok: {budget.fiscalYear ?? '-'}</span>
                  <span>Kwota: {budget.allocatedAmount ?? budget.amount ?? budget.total ?? '-'} {budget.currency ?? 'PLN'}</span>
                  {budget.spentAmount || budget.spent ? <span>Wydano: {budget.spentAmount ?? budget.spent}</span> : null}
                </div>
                <div className="list__actions">
                  <button className="btn btn-outline btn-sm" type="button" onClick={() => handleBudgetSummary(budget.id)}>
                    Podsumowanie
                  </button>
                </div>
              </li>
            ))}
          </ul>
          {budgetSummary && (
            <div className="surface-card acquisitions-summary">
              <strong>Podsumowanie budżetu</strong>
              <pre style={{ whiteSpace: 'pre-wrap' }}>{JSON.stringify(budgetSummary, null, 2)}</pre>
            </div>
          )}
        </section>

        <section className="surface-card surface-card--wide acquisitions-panel acquisitions-panel--wide">
          <h3>Zamówienia</h3>
          <form className="form-row form-row--two acquisitions-form" onSubmit={handleCreateOrder}>
            <select
              value={orderForm.supplierId}
              onChange={e => setOrderForm(prev => ({ ...prev, supplierId: e.target.value }))}
              required
            >
              <option value="">Wybierz dostawcę</option>
              {suppliers.map(supplier => (
                <option key={supplier.id} value={supplier.id}>
                  {supplier.name || `Dostawca #${supplier.id}`}
                </option>
              ))}
            </select>
            <input
              placeholder="Tytuł"
              value={orderForm.title}
              onChange={e => setOrderForm(prev => ({ ...prev, title: e.target.value }))}
              required
            />
            <input
              placeholder="Kwota"
              type="number"
              min="0.01"
              step="0.01"
              value={orderForm.amount}
              onChange={e => setOrderForm(prev => ({ ...prev, amount: e.target.value }))}
              required
            />
            <button className="btn btn-primary" type="submit">Dodaj</button>
          </form>
          <ul className="list list--bordered">
            {orders.map(order => (
              <li key={order.id}>
                <div className="list__title">{order.title || order.name || `Zamówienie ${order.id}`}</div>
                <div className="list__meta">
                  <span>Status: {order.status || '-'}</span>
                  {order.amount ? <span>Kwota: {order.amount}</span> : null}
                </div>
                <div style={{ display: 'flex', gap: '0.5rem' }}>
                  <button className="btn btn-outline btn-sm" type="button" onClick={() => handleReceiveOrder(order.id)}>Przyjmij</button>
                  <button className="btn btn-outline btn-sm" type="button" onClick={() => handleCancelOrder(order.id)}>Anuluj</button>
                </div>
              </li>
            ))}
          </ul>
        </section>

        <section className="surface-card acquisitions-panel">
          <h3>Ubytki</h3>
          <form className="form-row form-row--two acquisitions-form" onSubmit={handleCreateWeeding}>
            <div className="form-field" style={{ position: 'relative' }}>
              <input
                placeholder="Wyszukaj książkę"
                value={weedingBookQuery}
                onChange={e => {
                  const value = e.target.value
                  setWeedingBookQuery(value)
                  setWeedingForm(prev => ({ ...prev, bookId: '' }))
                  searchWeedingBooks(value)
                }}
                required
              />
              {weedingBookResults.length > 0 && (
                <div style={{
                  position: 'absolute',
                  top: '100%',
                  left: 0,
                  right: 0,
                  backgroundColor: 'white',
                  border: '1px solid #ddd',
                  borderRadius: '4px',
                  maxHeight: '220px',
                  overflowY: 'auto',
                  zIndex: 10000,
                  marginTop: '4px',
                  boxShadow: '0 4px 6px rgba(0,0,0,0.1)'
                }}>
                  {weedingBookResults.map(book => (
                    <button
                      key={book.id}
                      type="button"
                      onClick={() => {
                        setWeedingForm(prev => ({ ...prev, bookId: String(book.id) }))
                        setWeedingBookQuery(book.title || `Książka #${book.id}`)
                        setWeedingBookResults([])
                      }}
                      style={{
                        display: 'block',
                        width: '100%',
                        padding: '8px 12px',
                        cursor: 'pointer',
                        border: 0,
                        borderBottom: '1px solid #eee',
                        background: 'white',
                        textAlign: 'left'
                      }}
                    >
                      <strong>{book.title || `Książka #${book.id}`}</strong>
                      {book.author?.name && <div style={{ fontSize: '0.875rem', color: '#666' }}>{book.author.name}</div>}
                    </button>
                  ))}
                </div>
              )}
            </div>
            <input
              placeholder="Powód"
              value={weedingForm.reason}
              onChange={e => setWeedingForm(prev => ({ ...prev, reason: e.target.value }))}
              required
            />
            <button className="btn btn-primary" type="submit">Dodaj</button>
          </form>
          <ul className="list list--bordered">
            {weeding.map(entry => (
              <li key={entry.id || `${entry.bookId}-${entry.reason}`}>
                <div className="list__title">
                  Książka: {entry.book?.title || entry.bookTitle || (entry.bookId ? `Książka #${entry.bookId}` : '-')}
                </div>
                <div className="list__meta">{entry.reason || 'Brak powodu'}</div>
              </li>
            ))}
          </ul>
        </section>
      </div>
    </div>
  )
}
