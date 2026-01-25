import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, within, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import LibrarianPanel from '../../../src/LibrarianPanel'
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

    const { container } = render(<LibrarianPanel />)
    await userEvent.click(screen.getByRole('button', { name: /Statystyki/i }))

    expect(await screen.findByRole('heading', { name: /Statystyki wypozyczen/i })).toBeInTheDocument()
    await waitFor(() => {
      expect(apiFetch).toHaveBeenCalledWith('/api/statistics/dashboard')
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

    const { container } = render(<LibrarianPanel />)
    await userEvent.click(screen.getByRole('button', { name: /Rezerwacje/i }))
    expect(await screen.findByText('Alpha')).toBeInTheDocument()

    await userEvent.click(screen.getByRole('button', { name: /Rozwin rezerwacje Alpha/i }))
    await userEvent.click(screen.getByRole('button', { name: /Zrealizuj/i }))
    expect(apiFetch).toHaveBeenCalledWith('/api/reservations/10/fulfill', { method: 'POST' })

    await userEvent.click(screen.getByRole('button', { name: /Anuluj/i }))
    expect(apiFetch).toHaveBeenCalledWith('/api/reservations/10', { method: 'DELETE' })
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

    const { container } = render(<LibrarianPanel />)
    await userEvent.click(screen.getByRole('button', { name: /Op/ }))
    expect(await screen.findByText(/Late/)).toBeInTheDocument()

    await userEvent.click(screen.getByRole('button', { name: /Rozwin oplaty dla u@example.com/i }))
    await userEvent.click(screen.getByRole('button', { name: /Oznacz/i }))
    expect(apiFetch).toHaveBeenCalledWith('/api/fines/7/pay', { method: 'POST' })

    await userEvent.click(screen.getByRole('button', { name: /Anuluj/i }))
    expect(apiFetch).toHaveBeenCalledWith('/api/fines/7', { method: 'DELETE' })
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

    const { container } = render(<LibrarianPanel />)
    await userEvent.click(screen.getByRole('button', { name: /Egz/i }))
    await userEvent.type(screen.getByPlaceholderText(/Wpisz tytu/i), 'Al')
    await userEvent.click(await screen.findByText('Alpha'))

    expect(await screen.findByText(/INV-1/i)).toBeInTheDocument()
    const inventoryForm = container.querySelector('form.form')
    await userEvent.type(inventoryForm.querySelector('input[required]'), 'INV-2')
    await userEvent.click(within(inventoryForm).getByRole('button', { name: /Dodaj egz/i }))

    expect(apiFetch).toHaveBeenCalledWith('/api/admin/books/1/copies', expect.objectContaining({ method: 'POST' }))
  })
})

