import React, { Suspense, lazy, useMemo } from 'react'
import { useLocation, useSearchParams } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import FeedbackCard from '../../components/ui/FeedbackCard'

const AdminPanel = lazy(() => import('./AdminPanel'))
const LibrarianPanel = lazy(() => import('./LibrarianPanel'))

function resolveSection(pathname, requestedSection, isAdmin) {
  const routeDefault = pathname.startsWith('/admin') ? 'admin' : 'operations'
  const normalized =
    requestedSection === 'admin'
      ? 'admin'
      : requestedSection === 'operations'
        ? 'operations'
        : routeDefault

  if (normalized === 'admin' && !isAdmin) {
    return 'operations'
  }

  return normalized
}

export default function StaffPanel() {
  const { user } = useAuth()
  const [searchParams, setSearchParams] = useSearchParams()
  const location = useLocation()
  const roles = user?.roles || []
  const isAdmin = roles.includes('ROLE_ADMIN')
  const isLibrarian = roles.includes('ROLE_LIBRARIAN') || isAdmin
  const section = resolveSection(location.pathname, searchParams.get('section'), isAdmin)

  const availableSections = useMemo(() => {
    const sections = []

    if (isLibrarian) {
      sections.push({
        key: 'operations',
        label: 'Obsługa biblioteki',
        description: 'Wypożyczenia, zwroty, rezerwacje, opłaty i egzemplarze.',
      })
    }

    if (isAdmin) {
      sections.push({
        key: 'admin',
        label: 'Administracja',
        description: 'Użytkownicy, role, audyt, integracje i konfiguracja systemu.',
      })
    }

    return sections
  }, [isAdmin, isLibrarian])

  function handleSectionChange(nextSection) {
    const nextParams = new URLSearchParams(searchParams)
    nextParams.set('section', nextSection)
    setSearchParams(nextParams, { replace: false })
  }

  if (!isLibrarian) {
    return (
      <div className="page">
        <FeedbackCard variant="error">Brak uprawnień do panelu personelu.</FeedbackCard>
      </div>
    )
  }

  return (
    <div className="page staff-panel">
      <div className="surface-card" style={{ marginBottom: 'var(--space-4)' }}>
        <div className="section-header" style={{ alignItems: 'flex-start' }}>
          <div>
            <h2>Panel personelu</h2>
          </div>
          <div className="tabs" role="tablist" aria-label="Sekcje panelu personelu">
            {availableSections.map(item => (
              <button
                key={item.key}
                type="button"
                className={`tab ${section === item.key ? 'tab--active' : ''}`}
                role="tab"
                aria-selected={section === item.key}
                onClick={() => handleSectionChange(item.key)}
                title={item.description}
              >
                {item.label}
              </button>
            ))}
          </div>
        </div>
      </div>

      <Suspense fallback={<div className="surface-card">Ładowanie panelu...</div>}>
        {section === 'admin' ? <AdminPanel /> : <LibrarianPanel />}
      </Suspense>
    </div>
  )
}
