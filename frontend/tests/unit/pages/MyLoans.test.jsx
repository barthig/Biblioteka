import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import MyLoans from '../../../src/pages/loans/MyLoans'
import { apiFetch } from '../../../src/api'
import { ResourceCacheProvider } from '../../../src/context/ResourceCacheContext'

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

let mockAuth = { token: null, user: null }
vi.mock('../../../src/context/AuthContext', () => ({
  useAuth: () => mockAuth
}))

const renderPage = () => {
  return render(
    <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
      <ResourceCacheProvider>
        <MyLoans />
      </ResourceCacheProvider>
    </MemoryRouter>
  )
}

describe('MyLoans page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mockAuth = { token: null, user: null }
  })

  it('prompts to log in when no token', () => {
    renderPage()
    expect(screen.getByText(/zaloguj/i)).toBeInTheDocument()
    expect(apiFetch).not.toHaveBeenCalled()
  })

  it('renders active and history loans for logged-in user', async () => {
    mockAuth = { token: 'token', user: { id: 1 } }
    apiFetch.mockResolvedValue({
      data: [
        { id: 1, userId: 1, book: { title: 'Active Book' }, dueAt: '2025-02-01', returnedAt: null },
        { id: 2, userId: 1, book: { title: 'Returned Book' }, borrowedAt: '2025-01-01', returnedAt: '2025-01-10' },
        { id: 3, userId: 99, book: { title: 'Other User Book' }, dueAt: '2025-02-05', returnedAt: null }
      ]
    })

    renderPage()

    expect(await screen.findByText('Active Book')).toBeInTheDocument()
    expect(screen.getByText('Returned Book')).toBeInTheDocument()
    expect(screen.queryByText('Other User Book')).not.toBeInTheDocument()
    expect(apiFetch).toHaveBeenCalledWith('/api/me/loans')
  })

  it('shows error when loans fail to load', async () => {
    mockAuth = { token: 'token', user: { id: 1 } }
    apiFetch.mockRejectedValue(new Error('Load failed'))

    renderPage()

    expect(await screen.findByText(/Load failed/i)).toBeInTheDocument()
  })

  it('extends loan for logged-in user', async () => {
    mockAuth = { token: 'token', user: { id: 1 } }
    apiFetch
      .mockResolvedValueOnce({
        data: [
          { id: 1, book: { title: 'Active Book' }, dueAt: '2025-02-01', returnedAt: null, extensionsCount: 0 }
        ]
      })
      .mockResolvedValueOnce({ data: { id: 1, book: { title: 'Active Book' }, dueAt: '2025-02-15', returnedAt: null, extensionsCount: 1 } })

    renderPage()

    expect(await screen.findByText('Active Book')).toBeInTheDocument()
    await userEvent.click(screen.getByRole('button', { name: /Przed/i }))

    expect(apiFetch).toHaveBeenCalledWith('/api/loans/1/extend', expect.objectContaining({ method: 'PUT' }))
  })
})

