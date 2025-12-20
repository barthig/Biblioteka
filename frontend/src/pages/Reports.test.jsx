import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import Reports from './Reports'
import { reportService } from '../services/reportService'

vi.mock('../services/reportService', () => ({
  reportService: {
    getUsage: vi.fn(),
    getPopularTitles: vi.fn(),
    getPatronSegments: vi.fn(),
    getFinancialSummary: vi.fn(),
    getInventoryOverview: vi.fn()
  }
}))

let mockUser = null
vi.mock('../context/AuthContext', () => ({
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
    expect(await screen.findByText(/Brak upraw/i)).toBeInTheDocument()
  })

  it('renders reports data', async () => {
    mockUser = { roles: ['ROLE_LIBRARIAN'] }
    reportService.getUsage.mockResolvedValue({ loans: 2, activeUsers: 5, overdueLoans: 1, availableCopies: 10 })
    reportService.getPopularTitles.mockResolvedValue({ data: [{ id: 1, title: 'Alpha', loanCount: 3 }] })
    reportService.getPatronSegments.mockResolvedValue({ data: [{ segment: 'Student', count: 2 }] })
    reportService.getFinancialSummary.mockResolvedValue({ budgetTotal: 1000, expensesTotal: 200, revenueTotal: 50 })
    reportService.getInventoryOverview.mockResolvedValue({ totalCopies: 20, availableCopies: 10, reservations: 2 })

    render(<Reports />)
    expect(await screen.findByText(/Raporty/i)).toBeInTheDocument()
    expect(screen.getByText(/Alpha/)).toBeInTheDocument()
    expect(screen.getByText(/Student/)).toBeInTheDocument()
  })
})
