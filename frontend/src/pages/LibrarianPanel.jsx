import React, { useEffect, useState } from 'react'
import { apiFetch } from '../api'

export default function LibrarianPanel() {
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [budgets, setBudgets] = useState([])
  const [reports, setReports] = useState(null)

  useEffect(() => {
    async function load() {
      setLoading(true)
      setError(null)
      try {
        const [budgetsRes, reportsRes] = await Promise.all([
          apiFetch('/api/admin/acquisitions/budgets'),
          apiFetch('/api/reports/usage')
        ])
        setBudgets(Array.isArray(budgetsRes) ? budgetsRes : [])
        setReports(reportsRes || null)
      } catch (err) {
        setError(err.message || 'Nie udalo sie pobrac danych panelu bibliotekarza')
      } finally {
        setLoading(false)
      }
    }
    load()
  }, [])

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Panel bibliotekarza</h1>
          <p className="support-copy">Podsumowanie budzetow i wykorzystania biblioteki.</p>
        </div>
      </header>

      {error && <div className="error">{error}</div>}

      <div className="grid two-columns">
        <section className="surface-card">
          <h2>Budzet akwizycji</h2>
          {loading && <p>Loading...</p>}
          {!loading && (
            <ul className="list">
              {budgets.length === 0 && <li>Brak zdefiniowanych budzetow.</li>}
              {budgets.map(budget => (
                <li key={budget.id}>
                  <div className="list-row">
                    <div>
                      <strong>{budget.name || 'Budzet'}</strong>
                      <div className="support-copy">{budget.fiscalYear || 'Rok n/d'}</div>
                    </div>
                    <div className="support-copy">
                      Przydzielono: {budget.allocatedAmount ?? 'n/d'}
                    </div>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </section>

        <section className="surface-card">
          <h2>Raport wykorzystania</h2>
          {loading && <p>Loading...</p>}
          {!loading && (
            <div className="stats-grid">
              <div className="stat">
                <span className="stat__label">Wypozyczenia</span>
                <span className="stat__value">{reports?.loans ?? 0}</span>
              </div>
              <div className="stat">
                <span className="stat__label">Rezerwacje</span>
                <span className="stat__value">{reports?.reservations ?? 0}</span>
              </div>
              <div className="stat">
                <span className="stat__label">Uzytkownicy aktywni</span>
                <span className="stat__value">{reports?.activeUsers ?? 0}</span>
              </div>
            </div>
          )}
        </section>
      </div>
    </div>
  )
}
