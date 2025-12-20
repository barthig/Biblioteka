import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import LibrarianPanel from './LibrarianPanel'
import { apiFetch } from '../api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

let mockUser = null
vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({ user: mockUser })
}))

describe('LibrarianPanel page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders stats tab and loads data', async () => {
    mockUser = { roles: ['ROLE_LIBRARIAN'] }
    apiFetch.mockImplementation((endpoint) => {
      if (endpoint === '/api/reports/usage') {
        return Promise.resolve({ loans: 2, overdueLoans: 1, activeUsers: 4, availableCopies: 8 })
      }
      return Promise.resolve({ data: [] })
    })

    render(<LibrarianPanel />)
    await userEvent.click(screen.getByRole('button', { name: /Statystyki/i }))

    expect(await screen.findByText(/Aktywne/i)).toBeInTheDocument()
    expect(apiFetch).toHaveBeenCalledWith('/api/reports/usage')
  })
})
