import React, { useEffect, useState } from 'react'
import { apiFetch } from '../api'

export default function LibrarianPanel() {
  const [activeTab, setActiveTab] = useState('loans')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)
  
  // Loan creation form
  const [loanForm, setLoanForm] = useState({
    userId: '',
    copyId: '',
    dueDate: ''
  })
  
  // Active loans
  const [loans, setLoans] = useState([])
  
  // Statistics
  const [stats, setStats] = useState({
    activeLoans: 0,
    overdueLoans: 0,
    totalUsers: 0,
    availableCopies: 0
  })

  useEffect(() => {
    if (activeTab === 'loans') {
      loadLoans()
    } else if (activeTab === 'stats') {
      loadStats()
    }
  }, [activeTab])

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
          bookCopyId: parseInt(loanForm.copyId),
          dueDate: loanForm.dueDate
        })
      })
      
      setSuccess('Wypożyczenie zostało utworzone pomyślnie')
      setLoanForm({ userId: '', copyId: '', dueDate: '' })
      loadLoans()
    } catch (err) {
      setError(err.message || 'Nie udało się utworzyć wypożyczenia')
    } finally {
      setLoading(false)
    }
  }

  async function handleReturnLoan(loanId) {
    if (!confirm('Czy na pewno chcesz oznaczyć tę książkę jako zwróconą?')) return
    
    setLoading(true)
    setError(null)
    setSuccess(null)
    
    try {
      await apiFetch(`/api/loans/${loanId}/return`, {
        method: 'POST'
      })
      
      setSuccess('Książka została zwrócona')
      loadLoans()
    } catch (err) {
      setError(err.message || 'Nie udało się zwrócić książki')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Panel bibliotekarza</h1>
          <p className="support-copy">Obsługa wypożyczeń i zarządzanie biblioteką</p>
        </div>
      </header>

      {error && <div className="error-message">{error}</div>}
      {success && <div className="success-message">{success}</div>}

      {/* Tabs */}
      <div className="tabs">
        <button 
          className={`tab ${activeTab === 'loans' ? 'active' : ''}`}
          onClick={() => setActiveTab('loans')}
        >
          Wypożyczenia
        </button>
        <button 
          className={`tab ${activeTab === 'create' ? 'active' : ''}`}
          onClick={() => setActiveTab('create')}
        >
          Utwórz wypożyczenie
        </button>
        <button 
          className={`tab ${activeTab === 'stats' ? 'active' : ''}`}
          onClick={() => setActiveTab('stats')}
        >
          Statystyki
        </button>
      </div>

      {/* Tab Content */}
      {activeTab === 'loans' && (
        <div className="surface-card">
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
                      <td>{loan.copy?.book?.title || 'N/A'}</td>
                      <td>{new Date(loan.borrowedAt).toLocaleDateString()}</td>
                      <td>{new Date(loan.dueDate).toLocaleDateString()}</td>
                      <td>
                        {loan.returnedAt ? (
                          <span className="badge badge-success">Zwrócono</span>
                        ) : new Date(loan.dueDate) < new Date() ? (
                          <span className="badge badge-danger">Przeterminowane</span>
                        ) : (
                          <span className="badge badge-info">Aktywne</span>
                        )}
                      </td>
                      <td>
                        {!loan.returnedAt && (
                          <button 
                            className="btn btn-sm btn-primary"
                            onClick={() => handleReturnLoan(loan.id)}
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
    </div>
  )
}
