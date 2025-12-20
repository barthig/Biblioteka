import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import Acquisitions from './Acquisitions'
import { acquisitionService } from '../services/acquisitionService'

vi.mock('../services/acquisitionService', () => ({
  acquisitionService: {
    listSuppliers: vi.fn(),
    listBudgets: vi.fn(),
    listOrders: vi.fn(),
    listWeeding: vi.fn(),
    createSupplier: vi.fn(),
    createBudget: vi.fn(),
    createOrder: vi.fn(),
    receiveOrder: vi.fn(),
    cancelOrder: vi.fn(),
    createWeeding: vi.fn()
  }
}))

let mockUser = null
vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({ user: mockUser })
}))

describe('Acquisitions page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    acquisitionService.listSuppliers.mockResolvedValue({ data: [{ id: 1, name: 'Supplier A', contact: 'test' }] })
    acquisitionService.listBudgets.mockResolvedValue({ data: [] })
    acquisitionService.listOrders.mockResolvedValue({ data: [] })
    acquisitionService.listWeeding.mockResolvedValue({ data: [] })
  })

  it('denies access for non-admin', () => {
    mockUser = { roles: ['ROLE_USER'] }
    render(<Acquisitions />)
    expect(screen.getByText(/Brak upraw/i)).toBeInTheDocument()
  })

  it('loads suppliers and allows create supplier', async () => {
    mockUser = { roles: ['ROLE_ADMIN'] }
    acquisitionService.createSupplier.mockResolvedValue({})
    render(<Acquisitions />)

    expect(await screen.findByText('Supplier A')).toBeInTheDocument()
    await userEvent.type(screen.getByPlaceholderText(/Nazwa/i), 'Supplier B')
    await userEvent.type(screen.getByPlaceholderText(/Kontakt/i), 'contact')
    await userEvent.click(screen.getByRole('button', { name: /^Dodaj$/i }))
    expect(acquisitionService.createSupplier).toHaveBeenCalledWith({ name: 'Supplier B', contact: 'contact' })
  })
})
