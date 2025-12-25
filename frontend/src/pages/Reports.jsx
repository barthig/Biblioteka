import React, { useEffect, useState } from 'react'
import { reportService } from '../services/reportService'
import { useAuth } from '../context/AuthContext'
import PageHeader from '../components/ui/PageHeader'
import StatGrid from '../components/ui/StatGrid'
import StatCard from '../components/ui/StatCard'
import SectionCard from '../components/ui/SectionCard'
import FeedbackCard from '../components/ui/FeedbackCard'

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
        <SectionCard>Brak uprawnień do raportów.</SectionCard>
      </div>
    )
  }

  const usageLoans = usage?.loans ?? usage?.activeLoans ?? null
  const usageUsers = usage?.activeUsers ?? usage?.users ?? null

  return (
    <div className="page">
      <PageHeader title="Raporty" subtitle="Przegląd metryk biblioteki" />

      <StatGrid>
        <StatCard title="Aktywne wypożyczenia" value={usageLoans ?? '-'} subtitle="Według raportu" />
        <StatCard title="Aktywni użytkownicy" value={usageUsers ?? '-'} subtitle="Ostatni okres" />
        <StatCard title="Raporty" value={[usage, financial, inventory].filter(Boolean).length} subtitle="Załadowane sekcje" />
      </StatGrid>

      {loading && <SectionCard>Ładowanie...</SectionCard>}
      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}

      {!loading && !error && (
        <div className="grid grid-2">
          <SectionCard>
            <h3>Użycie systemu</h3>
            <ul className="list">
              <li>Aktywne wypożyczenia: {usage?.loans ?? usage?.activeLoans ?? '-'}</li>
              <li>Zaległe wypożyczenia: {usage?.overdueLoans ?? '-'}</li>
              <li>Aktywni użytkownicy: {usage?.activeUsers ?? usage?.users ?? '-'}</li>
              <li>Dostępne egzemplarze: {usage?.availableCopies ?? '-'}</li>
            </ul>
          </SectionCard>

          <SectionCard>
            <h3>Finanse</h3>
            {financial ? (
              <ul className="list">
                <li>Przychody: {financial.totalRevenue ?? '-'}</li>
                <li>Koszty: {financial.totalExpenses ?? '-'}</li>
                <li>Saldo: {financial.balance ?? '-'}</li>
              </ul>
            ) : (
              <p>Brak danych finansowych.</p>
            )}
          </SectionCard>

          <SectionCard>
            <h3>Najpopularniejsze tytuły</h3>
            {popular.length === 0 ? (
              <p>Brak danych.</p>
            ) : (
              <ul className="list list--bordered">
                {popular.map(item => (
                  <li key={item.id || item.title}>
                    <div className="list__title">{item.title || item.book?.title}</div>
                    <div className="list__meta">
                      <span>Wypożyczeń: {item.borrowCount ?? item.count ?? '-'}</span>
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </SectionCard>

          <SectionCard>
            <h3>Segmenty czytelników</h3>
            {segments.length === 0 ? (
              <p>Brak danych.</p>
            ) : (
              <ul className="list list--bordered">
                {segments.map(segment => (
                  <li key={segment.segment || segment.name}>
                    <div className="list__title">{segment.segment || segment.name}</div>
                    <div className="list__meta">
                      <span>Użytkowników: {segment.count ?? segment.total ?? '-'}</span>
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </SectionCard>

          <SectionCard>
            <h3>Stan magazynu</h3>
            {inventory ? (
              <ul className="list">
                <li>W magazynie: {inventory.storageCopies ?? '-'}</li>
                <li>W wolnym dostępie: {inventory.openStackCopies ?? '-'}</li>
                <li>Ubytki: {inventory.removedCopies ?? '-'}</li>
              </ul>
            ) : (
              <p>Brak danych o magazynie.</p>
            )}
          </SectionCard>
        </div>
      )}
    </div>
  )
}
