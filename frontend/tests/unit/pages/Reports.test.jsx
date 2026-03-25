import React from 'react'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import Reports from '../../../src/pages/admin/Reports'
import { reportService } from '../../../src/services/reportService'

vi.mock('../../../src/services/reportService', () => ({
  reportService: {
    getUsage: vi.fn(),
    getPopularTitles: vi.fn(),
    getPatronSegments: vi.fn(),
    getFinancialSummary: vi.fn(),
    getInventoryOverview: vi.fn()
  }
}))

let mockUser = null
vi.mock('../../../src/context/AuthContext', () => ({
  useAuth: () => ({ user: mockUser })
}))

describe('Reports page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('denies access when not librarian', async () => {
    mockUser = { roles: ['ROLE_USER'] }
    reportService.getUsage.mockResolvedValue({})
    reportService.getPopularTitles.mockResolvedValue({ data: [] })
    reportService.getPatronSegments.mockResolvedValue({ data: [] })
    reportService.getFinancialSummary.mockResolvedValue({})
    reportService.getInventoryOverview.mockResolvedValue({})

    render(<Reports />)

    expect(await screen.findByText(/Brak uprawnień do raportów/i)).toBeInTheDocument()
  })

  it('renders statistics and report data', async () => {
    mockUser = { roles: ['ROLE_LIBRARIAN'] }
    reportService.getUsage.mockResolvedValue({ totalLoans: 5, activeLoans: 2 })
    reportService.getPopularTitles.mockResolvedValue({ items: [{ bookId: 1, title: 'Alpha', loanCount: 3 }] })
    reportService.getPatronSegments.mockResolvedValue({ segments: [{ membershipGroup: 'Student', totalUsers: 2 }] })
    reportService.getFinancialSummary.mockResolvedValue({
      budgets: { allocated: 1000, spent: 200, remaining: 800, currency: 'PLN' },
      fines: { outstanding: 10, collected: 5, currency: 'PLN' }
    })
    reportService.getInventoryOverview.mockResolvedValue({
      copies: [{ status: 'AVAILABLE', total: 10 }],
      totalCopies: 20,
      borrowedPercentage: 50
    })

    render(<Reports />)

    expect(await screen.findByRole('heading', { level: 1, name: /Raporty/i })).toBeInTheDocument()

    await waitFor(() => {
      expect(screen.queryByText(/Ładowanie/i)).not.toBeInTheDocument()
    })

    expect(screen.getAllByText(/Aktywne wypożyczenia/i).length).toBeGreaterThan(0)
    expect(screen.getAllByText(/Użytkownicy/i).length).toBeGreaterThan(0)
    expect(screen.getByRole('heading', { level: 1, name: /Raporty/i })).toBeInTheDocument()
    expect(screen.getAllByText('2').length).toBeGreaterThan(0)
    expect(screen.getAllByText('5').length).toBeGreaterThan(0)
    expect(screen.getByText(/Alpha/)).toBeInTheDocument()
    expect(screen.getByText(/Student/)).toBeInTheDocument()
    expect(screen.getByText(/Budżet przydzielony: 1000 PLN/i)).toBeInTheDocument()
    expect(screen.getByText(/Łącznie egzemplarzy: 20/i)).toBeInTheDocument()
  })

  it('shows error when reports fail to load', async () => {
    mockUser = { roles: ['ROLE_LIBRARIAN'] }
    reportService.getUsage.mockRejectedValue(new Error('Load failed'))
    reportService.getPopularTitles.mockResolvedValue({ data: [] })
    reportService.getPatronSegments.mockResolvedValue({ data: [] })
    reportService.getFinancialSummary.mockResolvedValue({})
    reportService.getInventoryOverview.mockResolvedValue({})

    render(<Reports />)

    expect(await screen.findByText(/Load failed/i)).toBeInTheDocument()
  })
})
