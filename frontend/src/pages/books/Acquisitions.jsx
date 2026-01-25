import React, { useEffect, useState } from 'react'
import { acquisitionService } from '../services/acquisitionService'
import { useAuth } from '../context/AuthContext'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import FeedbackCard from '../components/ui/FeedbackCard'

export default function Acquisitions() {
  const { user } = useAuth()
  const isAdmin = user?.roles?.includes('ROLE_ADMIN')
  const [suppliers, setSuppliers] = useState([])
  const [budgets, setBudgets] = useState([])
  const [orders, setOrders] = useState([])
  const [weeding, setWeeding] = useState([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [message, setMessage] = useState(null)
  const [budgetSummary, setBudgetSummary] = useState(null)

  const [supplierForm, setSupplierForm] = useState({ name: '', contact: '' })
  const [budgetForm, setBudgetForm] = useState({ name: '', amount: '' })
  const [orderForm, setOrderForm] = useState({ supplierId: '', title: '', amount: '' })
  const [weedingForm, setWeedingForm] = useState({ bookId: '', reason: '' })

  useEffect(() => {
    if (isAdmin) {
      loadAll()
    }
  }, [isAdmin])

  async function loadAll() {
    setLoading(true)
    setError(null)
    try {
      const [suppliersData, budgetsData, ordersData, weedingData] = await Promise.all([
        acquisitionService.listSuppliers(),
        acquisitionService.listBudgets(),
        acquisitionService.listOrders(),
        acquisitionService.listWeeding()
      ])
      setSuppliers(Array.isArray(suppliersData?.data) ? suppliersData.data : suppliersData || [])
      setBudgets(Array.isArray(budgetsData?.data) ? budgetsData.data : budgetsData || [])
      setOrders(Array.isArray(ordersData?.data) ? ordersData.data : ordersData || [])
      setWeeding(Array.isArray(weedingData?.data) ? weedingData.data : weedingData || [])
    } catch (err) {
      setError(err.message || 'Nie udało się pobrać danych akcesji')
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
      setMessage('Pobrano podsumowanie budzetu')
    } catch (err) {
      setError(err.message || 'Nie udalo sie pobrac podsumowania budzetu')
    }
  }

  async function handleCreateSupplier(e) {
    e.preventDefault()
    clearMessages()
    try {
      await acquisitionService.createSupplier(supplierForm)
      setMessage('Dodano dostawcę')
      setSupplierForm({ name: '', contact: '' })
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać dostawcy')
    }
  }

  async function handleCreateBudget(e) {
    e.preventDefault()
    clearMessages()
    try {
      await acquisitionService.createBudget({ ...budgetForm, amount: Number(budgetForm.amount) })
      setMessage('Dodano budżet')
      setBudgetForm({ name: '', amount: '' })
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać budżetu')
    }
  }

  async function handleCreateOrder(e) {
    e.preventDefault()
    clearMessages()
    try {
      await acquisitionService.createOrder({
        supplierId: supplierForm.id || Number(orderForm.supplierId),
        title: orderForm.title,
        amount: Number(orderForm.amount)
      })
      setMessage('Dodano zamówienie')
      setOrderForm({ supplierId: '', title: '', amount: '' })
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać zamówienia')
    }
  }

  async function handleReceiveOrder(id) {
    clearMessages()
    try {
      await acquisitionService.receiveOrder(id)
      setMessage('Przyjęto zamówienie')
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się przyjąć zamówienia')
    }
  }

  async function handleCancelOrder(id) {
    clearMessages()
    try {
      await acquisitionService.cancelOrder(id)
      setMessage('Anulowano zamówienie')
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się anulować zamówienia')
    }
  }

  async function handleCreateWeeding(e) {
    e.preventDefault()
    clearMessages()
    try {
      await acquisitionService.createWeeding({ bookId: Number(weedingForm.bookId), reason: weedingForm.reason })
      setMessage('Dodano protokół ubytków')
      setWeedingForm({ bookId: '', reason: '' })
      await loadAll()
    } catch (err) {
      setError(err.message || 'Nie udało się dodać ubytku')
    }
  }

  if (!isAdmin) {
    return (
      <div className="page">
        <div className="surface-card">Brak uprawnień do akcesji.</div>
      </div>
    )
  }

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Akcesje</h1>
          <p className="support-copy">Dostawcy, budżety, zamówienia, ubytki</p>
        </div>
      </header>

      <div className="card-grid card-grid--columns-3">
        <div className="surface-card stat-card">
          <h3>Dostawcy</h3>
          <strong>{suppliers.length}</strong>
          <span>Aktywni partnerzy</span>
        </div>
        <div className="surface-card stat-card">
          <h3>Budżety</h3>
          <strong>{budgets.length}</strong>
          <span>Aktywne pule</span>
        </div>
        <div className="surface-card stat-card">
          <h3>Zamówienia</h3>
          <strong>{orders.length}</strong>
          <span>Rejestr</span>
        </div>
      </div>

      {loading && <div className="surface-card">Ładowanie...</div>}
      {error && (
        <div className="surface-card">
          <p className="error">{error}</p>
        </div>
      )}
      {message && (
        <div className="surface-card">
          <p className="success">{message}</p>
        </div>
      )}

      <div className="grid grid-2">
        <div className="surface-card">
          <h3>Dostawcy</h3>
          <form className="form-row" onSubmit={handleCreateSupplier}>
            <input placeholder="Nazwa" value={supplierForm.name} onChange={e => setSupplierForm(prev => ({ ...prev, name: e.target.value }))} />
            <input placeholder="Kontakt" value={supplierForm.contact} onChange={e => setSupplierForm(prev => ({ ...prev, contact: e.target.value }))} />
            <button className="btn btn-primary" type="submit">Dodaj</button>
          </form>
          <ul className="list list--bordered">
            {suppliers.map(s => (
              <li key={s.id || s.name}>
                <div className="list__title">{s.name}</div>
                <div className="list__meta">{s.contact || s.email || ''}</div>
              </li>
            ))}
          </ul>
        </div>

        <div className="surface-card">
          <h3>Budżety</h3>
          <form className="form-row" onSubmit={handleCreateBudget}>
            <input placeholder="Nazwa" value={budgetForm.name} onChange={e => setBudgetForm(prev => ({ ...prev, name: e.target.value }))} />
            <input placeholder="Kwota" type="number" value={budgetForm.amount} onChange={e => setBudgetForm(prev => ({ ...prev, amount: e.target.value }))} />
            <button className="btn btn-primary" type="submit">Dodaj</button>
          </form>
          <ul className="list list--bordered">
            {budgets.map(b => (
              <li key={b.id || b.name}>
                <div className="list__title">{b.name}</div>
                <div className="list__meta">
                  <span>Kwota: {b.amount ?? b.total ?? '-'}</span>
                  {b.spent && <span>Wydano: {b.spent}</span>}
                </div>
                <div className="list__actions">
                  <button className="btn btn-outline btn-sm" type="button" onClick={() => handleBudgetSummary(b.id)}>Podsumowanie</button>
                </div>
              </li>
            ))}
          </ul>
          {budgetSummary && (
            <div className="surface-card" style={{ marginTop: '1rem' }}>
              <strong>Podsumowanie budzetu</strong>
              <pre style={{ whiteSpace: 'pre-wrap' }}>{JSON.stringify(budgetSummary, null, 2)}</pre>
            </div>
          )}
        </div>

        <div className="surface-card surface-card--wide">
          <h3>Zamówienia</h3>
          <form className="form-row" onSubmit={handleCreateOrder}>
            <input placeholder="ID dostawcy" value={orderForm.supplierId} onChange={e => setOrderForm(prev => ({ ...prev, supplierId: e.target.value }))} />
            <input placeholder="Tytuł" value={orderForm.title} onChange={e => setOrderForm(prev => ({ ...prev, title: e.target.value }))} />
            <input placeholder="Kwota" type="number" value={orderForm.amount} onChange={e => setOrderForm(prev => ({ ...prev, amount: e.target.value }))} />
            <button className="btn btn-primary" type="submit">Dodaj</button>
          </form>
          <ul className="list list--bordered">
            {orders.map(o => (
              <li key={o.id}>
                <div className="list__title">{o.title || o.name || `Zamówienie ${o.id}`}</div>
                <div className="list__meta">
                  <span>Status: {o.status || '-'}</span>
                  {o.amount && <span>Kwota: {o.amount}</span>}
                </div>
                <div style={{ display: 'flex', gap: '0.5rem' }}>
                  <button className="btn btn-outline btn-sm" type="button" onClick={() => handleReceiveOrder(o.id)}>Przyjmij</button>
                  <button className="btn btn-outline btn-sm" type="button" onClick={() => handleCancelOrder(o.id)}>Anuluj</button>
                </div>
              </li>
            ))}
          </ul>
        </div>

        <div className="surface-card">
          <h3>Ubytki</h3>
          <form className="form-row" onSubmit={handleCreateWeeding}>
            <input placeholder="ID książki" value={weedingForm.bookId} onChange={e => setWeedingForm(prev => ({ ...prev, bookId: e.target.value }))} />
            <input placeholder="Powód" value={weedingForm.reason} onChange={e => setWeedingForm(prev => ({ ...prev, reason: e.target.value }))} />
            <button className="btn btn-primary" type="submit">Dodaj</button>
          </form>
          <ul className="list list--bordered">
            {weeding.map(w => (
              <li key={w.id || `${w.bookId}-${w.reason}`}>
                <div className="list__title">Książka: {w.bookId || w.book?.id}</div>
                <div className="list__meta">{w.reason || 'Brak powodu'}</div>
              </li>
            ))}
          </ul>
        </div>
      </div>
    </div>
  )
}
