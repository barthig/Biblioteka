import React from 'react'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import AdminPanel from '../../../src/pages/admin/AdminPanel'
import { apiFetch } from '../../../src/api'

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

vi.mock('../../../src/context/ResourceCacheContext', () => ({
  useResourceCache: () => ({
    getCachedResource: vi.fn(() => undefined),
    setCachedResource: vi.fn(),
    invalidateResource: vi.fn(),
    prefetchResource: vi.fn(async (_key, loader) => loader())
  })
}))

vi.mock('../../../src/services/loanService', () => ({
  loanService: {
    getAllLoans: vi.fn(async () => []),
    updateLoan: vi.fn(),
    returnLoan: vi.fn(),
    extendLoan: vi.fn(),
    deleteLoan: vi.fn()
  }
}))

vi.mock('../../../src/components/admin/UserManagement', () => ({
  default: () => <div>UserManagement</div>
}))

vi.mock('../../../src/components/admin/SystemSettings', () => ({
  default: () => <div>SystemSettings</div>
}))

vi.mock('../../../src/components/admin/RolesAndAudit', () => ({
  default: () => <div>RolesAndAudit</div>
}))

vi.mock('../../../src/components/admin/LoanManagement', () => ({
  default: () => <div>LoanManagement</div>
}))

describe('AdminPanel', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders statistic cards with values from API', async () => {
    apiFetch.mockImplementation(async endpoint => {
      if (endpoint === '/api/dashboard') {
        return {
          booksCount: 120,
          usersCount: 45,
          loansCount: 12,
          reservationsQueue: 7,
          transactionsToday: 5,
          activeUsers: 18
        }
      }

      if (endpoint === '/api/users') {
        return [{ id: 1 }, { id: 2 }, { id: 3 }]
      }

      return []
    })

    render(<AdminPanel />)

    expect(await screen.findByRole('heading', { name: /Panel administratora/i })).toBeInTheDocument()

    await waitFor(() => {
      expect(screen.getByText('120')).toBeInTheDocument()
      expect(screen.getByText('45')).toBeInTheDocument()
      expect(screen.getByText('12')).toBeInTheDocument()
      expect(screen.getByText('7')).toBeInTheDocument()
      expect(screen.getByText('5')).toBeInTheDocument()
      expect(screen.getByText('18')).toBeInTheDocument()
    })

    expect(screen.getByText(/^Książki$/i)).toBeInTheDocument()
    expect(screen.getByText(/^Czytelnicy$/i)).toBeInTheDocument()
    expect(screen.getAllByText(/^Wypożyczenia$/i).length).toBeGreaterThan(0)
    expect(screen.getByText(/^Rezerwacje$/i)).toBeInTheDocument()
    expect(screen.getByText(/^Transakcje dziś$/i)).toBeInTheDocument()
    expect(screen.getByText(/^Aktywni dziś$/i)).toBeInTheDocument()
  })
})
