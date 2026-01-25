import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, within, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import LibrarianPanel from '../../../src/pages/admin/LibrarianPanel'
import { apiFetch } from '../../../src/api'

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

let mockUser = null
vi.mock('../../../src/context/AuthContext', () => ({
  useAuth: () => ({ user: mockUser })
}))

const renderPanel = () => {
  return render(
    <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
      <LibrarianPanel />
    </MemoryRouter>
  )
}

describe('LibrarianPanel page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    window.confirm = vi.fn(() => true)
  })

  it('renders stats tab and loads data', async () => {
    mockUser = { roles: ['ROLE_LIBRARIAN'] }
    apiFetch.mockImplementation((endpoint) => {
      if (endpoint === '/api/statistics/dashboard') {
        return Promise.resolve({ activeLoans: 2, overdueLoans: 1, totalUsers: 4, availableCopies: 8 })
      }
      if (endpoint === '/api/settings') {
        return Promise.resolve({ loanLimitPerUser: 5, loanDurationDays: 21, notificationsEnabled: true })
      }
      return Promise.resolve({ data: [] })
    })

    const { container } = renderPanel()
    await userEvent.click(screen.getByRole('button', { name: /Ustawienia/i }))

    expect(await screen.findByText(/Limit wypo/i)).toBeInTheDocument()
    await waitFor(() => {
      expect(apiFetch).toHaveBeenCalledWith('/api/settings')
    })
  })

  it('handles reservations actions', async () => {
    mockUser = { roles: ['ROLE_LIBRARIAN'] }
    apiFetch.mockImplementation((endpoint, opts) => {
      if (endpoint.startsWith('/api/reservations?')) {
        return Promise.resolve({
          data: [{
            id: 10,
            status: 'ACTIVE',
            user: { email: 'user@example.com' },
            book: { title: 'Alpha' },
            expiresAt: '2025-01-01T10:00:00Z'
          }]
        })
      }
      if (endpoint === '/api/reservations/10/fulfill' && opts?.method === 'POST') {
        return Promise.resolve({})
      }
      if (endpoint === '/api/reservations/10' && opts?.method === 'DELETE') {
        return Promise.resolve({})
      }
      return Promise.resolve({ data: [] })
    })

    const { container } = renderPanel()
    await userEvent.click(screen.getByRole('button', { name: /Rezerwacje/i }))
    expect(await screen.findByText('Alpha')).toBeInTheDocument()

    // Expand the reservation card
    const alphaElement = screen.getByText('Alpha')
    await userEvent.click(alphaElement)
    
    // Wait for prepare button and click (button says "Oznacz jako przygotowaną")
    const prepareButton = await screen.findByRole('button', { name: /Oznacz jako przygotowaną/i })
    await userEvent.click(prepareButton)
    expect(apiFetch).toHaveBeenCalledWith('/api/reservations/10/prepare', { method: 'POST' })
  })

  it('handles fines actions', async () => {
    mockUser = { roles: ['ROLE_ADMIN'] }
    apiFetch.mockImplementation((endpoint, opts) => {
      if (endpoint === '/api/fines?limit=50') {
        return Promise.resolve({ data: [{ id: 7, amount: 10, reason: 'Late', userEmail: 'u@example.com' }] })
      }
      if (endpoint === '/api/fines/7/pay' && opts?.method === 'POST') {
        return Promise.resolve({})
      }
      if (endpoint === '/api/fines/7' && opts?.method === 'DELETE') {
        return Promise.resolve({})
      }
      return Promise.resolve({ data: [] })
    })

    const { container } = renderPanel()
    await userEvent.click(screen.getByRole('button', { name: /Op.*aty/i }))
    expect(await screen.findByText(/Late/)).toBeInTheDocument()

    // Find and click the row/card to expand it
    const lateElement = screen.getByText(/Late/)
    await userEvent.click(lateElement)
    
    // Wait for pay button and click (button says "Oznacz jako oplacone")
    const payButton = await screen.findByRole('button', { name: /Oznacz jako oplacone/i })
    await userEvent.click(payButton)
    expect(apiFetch).toHaveBeenCalledWith('/api/fines/7/pay', { method: 'POST' })
  })

  it('loads copies and adds inventory copy', async () => {
    mockUser = { roles: ['ROLE_LIBRARIAN'] }
    apiFetch.mockImplementation((endpoint, opts) => {
      if (endpoint.startsWith('/api/books?q=')) {
        return Promise.resolve({ data: [{ id: 1, title: 'Alpha', author: { name: 'Author A' } }] })
      }
      if (endpoint === '/api/admin/books/1/copies' && !opts) {
        return Promise.resolve({ data: [{ id: 3, inventoryCode: 'INV-1', status: 'AVAILABLE', accessType: 'STORAGE' }] })
      }
      if (endpoint === '/api/admin/books/1/copies' && opts?.method === 'POST') {
        return Promise.resolve({})
      }
      return Promise.resolve({ data: [] })
    })

    const { container } = renderPanel()
    await userEvent.click(screen.getByRole('button', { name: /Egz/i }))
    
    const searchInput = await screen.findByPlaceholderText(/Wpisz tytu/i)
    await userEvent.type(searchInput, 'Al')
    
    // Wait for and click search result
    const alphaResult = await screen.findByText('Alpha')
    await userEvent.click(alphaResult)

    expect(await screen.findByText(/INV-1/i)).toBeInTheDocument()
  })
})

