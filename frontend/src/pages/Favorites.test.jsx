import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import Favorites from './Favorites'
import { apiFetch } from '../api'
import { ResourceCacheProvider } from '../context/ResourceCacheContext'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

let mockUser = null
vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({ user: mockUser })
}))

const renderPage = (user) => {
  mockUser = user
  return render(
    <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
      <ResourceCacheProvider>
        <Favorites />
      </ResourceCacheProvider>
    </MemoryRouter>
  )
}

describe('Favorites page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('prompts to log in when user is not set', () => {
    renderPage(null)
    expect(screen.getByText(/zaloguj/i)).toBeInTheDocument()
  })

  it('renders favorite items for logged-in user', async () => {
    apiFetch.mockResolvedValue({
      data: [
        { id: 1, createdAt: '2025-01-01T10:00:00Z', book: { id: 10, title: 'Alpha', author: { name: 'Author A' } } }
      ]
    })

    renderPage({ id: 123 })

    expect(await screen.findByText('Alpha')).toBeInTheDocument()
    expect(screen.getByText(/Author A/i)).toBeInTheDocument()
  })

  it('shows error when favorites fail to load', async () => {
    apiFetch.mockRejectedValue(new Error('Load failed'))
    renderPage({ id: 123 })

    expect(await screen.findByText(/Load failed/i)).toBeInTheDocument()
  })
})

