import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import Reservations from './Reservations'
import { apiFetch } from '../api'
import { ResourceCacheProvider } from '../context/ResourceCacheContext'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

let mockUser = null
vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({ user: mockUser })
}))

const renderPage = () => {
  return render(
    <MemoryRouter>
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
        { id: 1, status: 'ACTIVE', reservedAt: '2025-01-01', expiresAt: '2025-02-01', book: { title: 'Alpha' } },
        { id: 2, status: 'FULFILLED', reservedAt: '2025-01-01', fulfilledAt: '2025-01-05', book: { title: 'Beta' } }
      ]
    })

    renderPage()

    expect(await screen.findByText('Alpha')).toBeInTheDocument()
    await userEvent.click(screen.getByRole('button', { name: /Anuluj/i }))
    expect(apiFetch).toHaveBeenCalledWith('/api/reservations/1', { method: 'DELETE' })
  })

  it('shows error when loading reservations fails', async () => {
    mockUser = { id: 1 }
    apiFetch.mockRejectedValue(new Error('Load failed'))

    renderPage()

    expect(await screen.findByText(/Load failed/i)).toBeInTheDocument()
  })
})
