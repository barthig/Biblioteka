import React, { useEffect, useState } from 'react'
import { reportService } from '../services/reportService'
import { useAuth } from '../context/AuthContext'

export default function Reports() {
  const { user } = useAuth()
  const isLibrarian = user?.roles?.includes('ROLE_LIBRARIAN') || user?.roles?.includes('ROLE_ADMIN')
  const [usage, setUsage] = useState(null)
  const [popular, setPopular] = useState([])
  const [segments, setSegments] = useState([])
  const [financial, setFinancial] = useState(null)
  const [inventory, setInventory] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    let active = true
    async function load() {
      setLoading(true)
      setError(null)
      try {
        const [usageData, popularData, segmentsData, financialData, inventoryData] = await Promise.all([
          reportService.getUsage(),
          reportService.getPopularTitles(),
          reportService.getPatronSegments(),
          reportService.getFinancialSummary(),
          reportService.getInventoryOverview()
        ])
        if (!active) return
        setUsage(usageData || null)
        setPopular(Array.isArray(popularData?.data) ? popularData.data : Array.isArray(popularData) ? popularData : [])
        setSegments(Array.isArray(segmentsData?.data) ? segmentsData.data : Array.isArray(segmentsData) ? segmentsData : [])
        setFinancial(financialData || null)
        setInventory(inventoryData || null)
      } catch (err) {
        if (active) setError(err.message || 'Nie udało się pobrać raportów')
      } finally {
        if (active) setLoading(false)
      }
    }
    load()
    return () => { active = false }
  }, [])

  if (!isLibrarian) {
    return (
      <div className="page">
        <div className="surface-card">Brak uprawnień do raportów.</div>
      </div>
    )
  }

  return (
    <div className="page">
      <header className="page-header">
        <div>
          <h1>Raporty</h1>
          <p>Przegląd metryk biblioteki</p>
        </div>
      </header>

      {loading && <div className="surface-card">Ładowanie…</div>}
      {error && <div className="surface-card error">{error}</div>}

      {!loading && !error && (
        <div className="grid grid-2">
          <div className="surface-card">
            <h3>Użycie systemu</h3>
            <ul className="list">
              <li>Aktywne wypożyczenia: {usage?.loans ?? usage?.activeLoans ?? '-'}</li>
              <li>Zaległe wypożyczenia: {usage?.overdueLoans ?? '-'}</li>
              <li>Aktywni użytkownicy: {usage?.activeUsers ?? usage?.users ?? '-'}</li>
              <li>Dostępne egzemplarze: {usage?.availableCopies ?? '-'}</li>
            </ul>
          </div>

          <div className="surface-card">
            <h3>Finanse</h3>
            {financial ? (
              <ul className="list">
                <li>Budżet: {financial.budgetTotal ?? '-'}</li>
                <li>Wydatki: {financial.expensesTotal ?? '-'}</li>
                <li>Przychody: {financial.revenueTotal ?? '-'}</li>
              </ul>
            ) : (
              <p>Brak danych</p>
            )}
          </div>

          <div className="surface-card">
            <h3>Segmenty czytelników</h3>
            {segments.length === 0 ? (
              <p>Brak danych</p>
            ) : (
              <ul className="list">
                {segments.map((s) => (
                  <li key={s.segment || s.name}>
                    {s.segment || s.name}: {s.count ?? s.total ?? 0}
                  </li>
                ))}
              </ul>
            )}
          </div>

          <div className="surface-card">
            <h3>Magazyn</h3>
            {inventory ? (
              <ul className="list">
                <li>Egzemplarze łącznie: {inventory.totalCopies ?? '-'}</li>
                <li>Dostępne: {inventory.availableCopies ?? '-'}</li>
                <li>Zarezerwowane: {inventory.reservedCopies ?? inventory.reservations ?? '-'}</li>
              </ul>
            ) : (
              <p>Brak danych</p>
            )}
          </div>

          <div className="surface-card surface-card--wide">
            <h3>Najpopularniejsze tytuły</h3>
            {popular.length === 0 ? (
              <p>Brak danych</p>
            ) : (
              <ul className="list list--bordered">
                {popular.slice(0, 10).map((book) => (
                  <li key={book.id || book.bookId}>
                    <div className="list__title">{book.title}</div>
                    <div className="list__meta">
                      <span>{book.author?.name || book.authorName || 'Autor nieznany'}</span>
                      {book.loanCount && <span>Wypożyczenia: {book.loanCount}</span>}
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>
      )}
    </div>
  )
}
