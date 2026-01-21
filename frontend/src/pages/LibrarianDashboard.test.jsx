import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import LibrarianDashboard from './LibrarianDashboard'
import { apiFetch } from '../api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

vi.mock('react-hot-toast', () => ({
  default: { error: vi.fn() }
}))

describe('LibrarianDashboard page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders stats after load', async () => {
    apiFetch.mockResolvedValue({
      activeLoans: 3,
      overdueLoans: 1,
      pendingReservations: 2,
      totalUsers: 10,
      totalBooks: 20,
      availableCopies: 15,
      popularBooks: [],
      recentActivity: []
    })

    render(<LibrarianDashboard />)

    expect(await screen.findByText('Dashboard')).toBeInTheDocument()
    expect(screen.getByText('3')).toBeInTheDocument()
  })
})
