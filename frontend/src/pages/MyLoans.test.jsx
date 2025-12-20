import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import MyLoans from './MyLoans'
import { apiFetch } from '../api'
import { ResourceCacheProvider } from '../context/ResourceCacheContext'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

let mockAuth = { token: null, user: null }
vi.mock('../context/AuthContext', () => ({
  useAuth: () => mockAuth
}))

const renderPage = () => {
  return render(
    <MemoryRouter>
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
  })

  it('renders active and history loans for logged-in user', async () => {
    mockAuth = { token: 'token', user: { id: 1 } }
    apiFetch.mockResolvedValue({
      data: [
        { id: 1, book: { title: 'Active Book' }, dueAt: '2025-02-01', returnedAt: null },
        { id: 2, book: { title: 'Returned Book' }, borrowedAt: '2025-01-01', returnedAt: '2025-01-10' }
      ]
    })

    renderPage()

    expect(await screen.findByText('Active Book')).toBeInTheDocument()
    expect(screen.getByText('Returned Book')).toBeInTheDocument()
  })
})
