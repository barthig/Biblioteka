import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import SystemLogs from './SystemLogs'
import { systemLogService } from '../services/systemLogService'

vi.mock('../services/systemLogService', () => ({
  systemLogService: {
    list: vi.fn()
  }
}))

let mockUser = null
vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({ user: mockUser })
}))

describe('SystemLogs page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('denies access for non-admin', () => {
    mockUser = { roles: ['ROLE_USER'] }
    render(<SystemLogs />)
    expect(screen.getByText(/Brak upraw/i)).toBeInTheDocument()
  })

  it('renders logs for admin', async () => {
    mockUser = { roles: ['ROLE_ADMIN'] }
    systemLogService.list.mockResolvedValue('line1\nline2')
    render(<SystemLogs />)
    expect(await screen.findByText(/line1/)).toBeInTheDocument()
  })

  it('shows error when logs fail to load', async () => {
    mockUser = { roles: ['ROLE_ADMIN'] }
    systemLogService.list.mockRejectedValue(new Error('Load failed'))
    render(<SystemLogs />)
    expect(await screen.findByText(/Load failed/i)).toBeInTheDocument()
  })
})
