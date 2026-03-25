import React from 'react'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import LibrarianPanel from '../../../src/pages/admin/LibrarianPanel'
import { apiFetch } from '../../../src/api'

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

vi.mock('../../../src/context/AuthContext', () => ({
  useAuth: () => ({
    user: { id: 1, roles: ['ROLE_LIBRARIAN'], name: 'Bibliotekarz' }
  })
}))

vi.mock('../../../src/context/ResourceCacheContext', () => ({
  useResourceCache: () => ({
    getCachedResource: vi.fn(() => undefined),
    setCachedResource: vi.fn(),
    invalidateResource: vi.fn(),
    prefetchResource: vi.fn(async (_key, loader) => loader())
  })
}))

vi.mock('../../../src/pages/admin/LibrarianDashboard', () => ({
  default: () => <div>Librarian Dashboard Widget</div>
}))

describe('LibrarianPanel', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders main staff tabs and dashboard by default', async () => {
    render(
      <MemoryRouter initialEntries={['/librarian']} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <LibrarianPanel />
      </MemoryRouter>
    )

    expect(screen.getByRole('heading', { name: /Panel bibliotekarza/i })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Dashboard/i })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Ustawienia/i })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Wypożyczenia/i })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Rezerwacje/i })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Opłaty/i })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Egzemplarze/i })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Kolekcje/i })).toBeInTheDocument()
    expect(screen.getByText('Librarian Dashboard Widget')).toBeInTheDocument()
  })

  it('loads settings tab data from API', async () => {
    apiFetch.mockImplementation(async endpoint => {
      if (endpoint === '/api/statistics/dashboard') {
        return {
          activeLoans: 5,
          overdueLoans: 1,
          totalUsers: 20,
          availableCopies: 50
        }
      }

      if (endpoint === '/api/settings') {
        return {
          loanLimitPerUser: 6,
          loanDurationDays: 30,
          notificationsEnabled: true
        }
      }

      return []
    })

    render(
      <MemoryRouter initialEntries={['/librarian?tab=stats']} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <LibrarianPanel />
      </MemoryRouter>
    )

    expect(await screen.findByRole('heading', { name: /Ustawienia biblioteki/i })).toBeInTheDocument()
    expect(screen.getByDisplayValue('6')).toBeInTheDocument()
    expect(screen.getByDisplayValue('30')).toBeInTheDocument()
  })

  it('loads reservations tab with filtered data', async () => {
    apiFetch.mockResolvedValue({
      data: [
        {
          id: 10,
          status: 'ACTIVE',
          user: { name: 'Jan Kowalski' },
          book: { title: 'Solaris' }
        }
      ]
    })

    render(
      <MemoryRouter initialEntries={['/librarian?tab=reservations&status=ACTIVE']} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <LibrarianPanel />
      </MemoryRouter>
    )

    expect(await screen.findByRole('heading', { name: /Rezerwacje/i })).toBeInTheDocument()
    expect(screen.getByText(/Jan Kowalski/i)).toBeInTheDocument()
    expect(screen.getByText(/Solaris/i)).toBeInTheDocument()
  })
})
