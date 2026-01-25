import React, { useEffect, useState } from 'react'
import { reportService } from '../../services/reportService'
import { useAuth } from '../../context/AuthContext'
import PageHeader from '../../components/ui/PageHeader'
import StatGrid from '../../components/ui/StatGrid'
import StatCard from '../../components/ui/StatCard'
import SectionCard from '../../components/ui/SectionCard'
import FeedbackCard from '../../components/ui/FeedbackCard'

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
        const normalizedPopular = Array.isArray(popularData?.items)
          ? popularData.items
          : Array.isArray(popularData?.data)
            ? popularData.data
            : Array.isArray(popularData)
              ? popularData
              : []
        const normalizedSegments = Array.isArray(segmentsData?.segments)
          ? segmentsData.segments
          : Array.isArray(segmentsData?.data)
            ? segmentsData.data
            : Array.isArray(segmentsData)
              ? segmentsData
              : []

        setUsage(usageData || null)
        setPopular(normalizedPopular)
        setSegments(normalizedSegments)
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

  const usageLoans = usage?.activeLoans ?? usage?.loans ?? usage?.totalLoans ?? null
  const usageTotalLoans = usage?.totalLoans ?? usage?.loans ?? null
  const usageUsers = segments.length > 0
    ? segments.reduce((sum, segment) => sum + (segment.totalUsers ?? segment.count ?? 0), 0)
    : (usage?.activeUsers ?? usage?.users ?? null)
  const inventoryBreakdown = Array.isArray(inventory?.copies) ? inventory.copies : null
  const legacyStorage = inventory?.storageCopies
  const legacyOpenStack = inventory?.openStackCopies
  const legacyRemoved = inventory?.removedCopies
  const legacyTotal = [legacyStorage, legacyOpenStack, legacyRemoved].every(value => typeof value === 'number')
    ? legacyStorage + legacyOpenStack + legacyRemoved
    : null
  const inventoryTotalCopies = inventory?.totalCopies ?? (inventoryBreakdown
    ? inventoryBreakdown.reduce((sum, row) => sum + (row.total ?? 0), 0)
    : legacyTotal)
  const inventoryAvailableCopies = inventoryBreakdown
    ? inventoryBreakdown.reduce((sum, row) => (row.status === 'AVAILABLE' ? sum + (row.total ?? 0) : sum), 0)
    : (inventory?.availableCopies ?? (legacyStorage != null && legacyOpenStack != null ? legacyStorage + legacyOpenStack : null))

  return (
    <div className="page">
      <PageHeader title="Raporty" subtitle="Przegląd metryk biblioteki" />

      <StatGrid>
        <StatCard title="Aktywne wypożyczenia" value={usageLoans ?? '-'} subtitle="Według raportu" />
        <StatCard title="Użytkownicy" value={usageUsers ?? '-'} subtitle="Z segmentów" />
        <StatCard title="Raporty" value={[usage, financial, inventory, popular.length, segments.length].filter(Boolean).length} subtitle="Załadowane sekcje" />
      </StatGrid>

      {loading && <SectionCard>Ładowanie...</SectionCard>}
      {error && <FeedbackCard variant="error">{error}</FeedbackCard>}

      {!loading && !error && (
        <div className="grid grid-2">
          <SectionCard>
            <h3>Użycie systemu</h3>
            <ul className="list">
              <li>Aktywne wypożyczenia: {usageLoans ?? '-'}</li>
              <li>Wszystkie wypożyczenia: {usageTotalLoans ?? '-'}</li>
              <li>Użytkownicy: {usageUsers ?? '-'}</li>
              <li>Dostępne egzemplarze: {inventoryAvailableCopies ?? '-'}</li>
            </ul>
          </SectionCard>

          <SectionCard>
            <h3>Finanse</h3>
            {financial ? (
              <ul className="list">
                {financial.budgets ? (
                  <>
                    <li>Budżet przydzielony: {financial.budgets.allocated} {financial.budgets.currency}</li>
                    <li>Budżet wydany: {financial.budgets.spent} {financial.budgets.currency}</li>
                    <li>Budżet pozostały: {financial.budgets.remaining} {financial.budgets.currency}</li>
                  </>
                ) : (
                  <>
                    <li>Przychody: {financial.totalRevenue ?? '-'}</li>
                    <li>Koszty: {financial.totalExpenses ?? '-'}</li>
                    <li>Saldo: {financial.balance ?? '-'}</li>
                  </>
                )}
                {financial.fines && (
                  <>
                    <li>Kary nieopłacone: {financial.fines.outstanding} {financial.fines.currency}</li>
                    <li>Kary opłacone: {financial.fines.collected} {financial.fines.currency}</li>
                  </>
                )}
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
                  <li key={item.id || item.bookId || item.title}>
                    <div className="list__title">{item.title || item.book?.title}</div>
                    <div className="list__meta">
                      <span>Wypożyczeń: {item.loanCount ?? item.borrowCount ?? item.count ?? '-'}</span>
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
                  <li key={segment.membershipGroup || segment.segment || segment.name}>
                    <div className="list__title">{segment.membershipGroup || segment.segment || segment.name}</div>
                    <div className="list__meta">
                      <span>Użytkowników: {segment.totalUsers ?? segment.count ?? segment.total ?? '-'}</span>
                      {segment.blockedUsers != null && (
                        <span> • Zablokowani: {segment.blockedUsers}</span>
                      )}
                      {segment.activeLoans != null && (
                        <span> • Aktywne wypożyczenia: {segment.activeLoans}</span>
                      )}
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </SectionCard>

          <SectionCard>
            <h3>Stan magazynu</h3>
            {inventory ? (
              <>
                <ul className="list">
                  <li>Łącznie egzemplarzy: {inventoryTotalCopies ?? '-'}</li>
                  <li>Wypożyczone (%): {inventory?.borrowedPercentage ?? '-'}</li>
                  <li>Dostępne: {inventoryAvailableCopies ?? '-'}</li>
                </ul>
                {inventoryBreakdown && inventoryBreakdown.length > 0 && (
                  <ul className="list list--bordered">
                    {inventoryBreakdown.map(item => (
                      <li key={item.status}>
                        <div className="list__title">{item.status}</div>
                        <div className="list__meta">
                          <span>Liczba: {item.total}</span>
                        </div>
                      </li>
                    ))}
                  </ul>
                )}
              </>
            ) : (
              <p>Brak danych o magazynie.</p>
            )}
          </SectionCard>
        </div>
      )}
    </div>
  )
}
