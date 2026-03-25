import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import Reservations from '../../../src/pages/loans/Reservations'
import { apiFetch } from '../../../src/api'
import { ResourceCacheProvider } from '../../../src/context/ResourceCacheContext'

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

let mockUser = null
vi.mock('../../../src/context/AuthContext', () => ({
  useAuth: () => ({ user: mockUser })
}))

const renderPage = () => {
  return render(
    <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
      <ResourceCacheProvider>
        <Reservations />
      </ResourceCacheProvider>
    </MemoryRouter>
  )
}

describe('Reservations page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mockUser = null
  })

  it('prompts to log in when no user', () => {
    renderPage()
    expect(screen.getByText(/zaloguj/i)).toBeInTheDocument()
  })

  it('renders reservations and allows cancel', async () => {
    mockUser = { id: 1 }
    apiFetch.mockResolvedValue({
      data: [
        { id: 1, status: 'ACTIVE', reservedAt: '2025-01-01', expiresAt: '2025-02-01', user: { id: 1 }, book: { title: 'Alpha' } },
        { id: 2, status: 'FULFILLED', reservedAt: '2025-01-01', fulfilledAt: '2025-01-05', user: { id: 1 }, book: { title: 'Beta' } },
        { id: 3, status: 'ACTIVE', reservedAt: '2025-01-01', expiresAt: '2025-02-01', user: { id: 99 }, book: { title: 'Gamma' } }
      ]
    })

    renderPage()

    expect(await screen.findByText('Alpha')).toBeInTheDocument()
    expect(screen.queryByText('Gamma')).not.toBeInTheDocument()
    await userEvent.click(screen.getByRole('button', { name: /Anuluj/i }))
    expect(apiFetch).toHaveBeenCalledWith('/api/reservations/1', { method: 'DELETE' })
  })

  it('shows error when loading reservations fails', async () => {
    mockUser = { id: 1 }
    apiFetch.mockRejectedValue(new Error('Load failed'))

    renderPage()

    expect(await screen.findByText(/Load failed/i)).toBeInTheDocument()
  })

  it('does not render cancel action for reservation already tied to borrowed copy', async () => {
    mockUser = { id: 1 }
    apiFetch.mockResolvedValue({
      data: [
        {
          id: 12,
          status: 'ACTIVE',
          reservedAt: '2025-01-01',
          expiresAt: '2025-02-01',
          user: { id: 1 },
          book: { title: 'Delta' },
          bookCopy: { id: 10, status: 'BORROWED' }
        }
      ]
    })

    renderPage()

    expect(await screen.findByText('Delta')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /Anuluj/i })).not.toBeInTheDocument()
    expect(screen.getByText(/Egzemplarz jest już przygotowany do realizacji/i)).toBeInTheDocument()
  })
})
