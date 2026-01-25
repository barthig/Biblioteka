import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import Notifications from '../../../src/Notifications'

const mockList = vi.fn()
const mockSendTest = vi.fn()

vi.mock('../services/notificationService', () => ({
  notificationService: {
    list: () => mockList(),
    sendTest: () => mockSendTest()
  }
}))

let mockUser = null
vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({ user: mockUser })
}))

describe('Notifications page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mockUser = null
  })

  it('renders empty state when no notifications', async () => {
    mockList.mockResolvedValue({ data: [] })
    render(<Notifications />)
    expect(await screen.findByText(/Brak powiadom/i)).toBeInTheDocument()
  })

  it('shows admin test button for admins', async () => {
    mockUser = { roles: ['ROLE_ADMIN'] }
    mockList.mockResolvedValue({ data: [] })
    render(<Notifications />)
    expect(await screen.findByRole('button', { name: /test/i })).toBeInTheDocument()
  })

  it('shows error when notifications fail to load', async () => {
    mockList.mockRejectedValue(new Error('Load failed'))
    render(<Notifications />)
    expect(await screen.findByText(/Load failed/i)).toBeInTheDocument()
  })
})

