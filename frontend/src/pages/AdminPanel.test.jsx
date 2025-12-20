import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import AdminPanel from './AdminPanel'
import { apiFetch } from '../api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('AdminPanel page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('loads and renders users table', async () => {
    apiFetch.mockResolvedValue([
      { id: 1, name: 'Admin User', email: 'admin@example.com', roles: ['ROLE_ADMIN'] }
    ])

    render(<AdminPanel />)

    expect(await screen.findByText('Admin User')).toBeInTheDocument()
    expect(screen.getByText('admin@example.com')).toBeInTheDocument()
  })
})
