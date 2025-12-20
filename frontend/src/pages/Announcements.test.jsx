import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import Announcements from './Announcements'
import { apiFetch } from '../api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({ user: null })
}))

describe('Announcements page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders announcements list', async () => {
    apiFetch.mockResolvedValue({
      data: [
        { id: 1, title: 'Alpha', createdAt: '2025-01-01T00:00:00Z', content: 'First' },
        { id: 2, title: 'Beta', createdAt: '2025-01-02T00:00:00Z', content: 'Second' }
      ],
      meta: { totalPages: 1 }
    })

    render(
      <MemoryRouter>
        <Announcements />
      </MemoryRouter>
    )

    expect(await screen.findByText('Alpha')).toBeInTheDocument()
    expect(screen.getByText('Beta')).toBeInTheDocument()
  })
})
