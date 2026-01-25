import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import Acquisitions from '../../../src/Acquisitions'
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
    const supplierSections = screen.getAllByRole('heading', { name: /Dostawcy/i })
    const suppliersCard = supplierSections
      .map(heading => heading.closest('.surface-card'))
      .find(card => card && within(card).queryByPlaceholderText(/Nazwa/i))

    const suppliersScope = within(suppliersCard)
    await userEvent.type(suppliersScope.getByPlaceholderText(/Nazwa/i), 'Supplier B')
    await userEvent.type(suppliersScope.getByPlaceholderText(/Kontakt/i), 'contact')
    await userEvent.click(suppliersScope.getByRole('button', { name: /^Dodaj$/i }))
    expect(acquisitionService.createSupplier).toHaveBeenCalledWith({ name: 'Supplier B', contact: 'contact' })
  })
})

