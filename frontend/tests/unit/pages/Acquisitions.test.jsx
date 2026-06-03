import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import Acquisitions from '../../../src/pages/books/Acquisitions'
import { acquisitionService } from '../../../src/services/acquisitionService'

vi.mock('../../../src/services/acquisitionService', () => ({
  acquisitionService: {
    listSuppliers: vi.fn(),
    listBudgets: vi.fn(),
    listOrders: vi.fn(),
    listWeeding: vi.fn(),
    getBudgetSummary: vi.fn(),
    createSupplier: vi.fn(),
    createBudget: vi.fn(),
    addExpense: vi.fn(),
    createOrder: vi.fn(),
    receiveOrder: vi.fn(),
    cancelOrder: vi.fn(),
    createWeeding: vi.fn()
  }
}))

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

let mockUser = null
let mockToken = null
vi.mock('../../../src/context/AuthContext', () => ({
  useAuth: () => ({ user: mockUser, token: mockToken })
}))

describe('Acquisitions page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mockToken = 'test-token'
    acquisitionService.listSuppliers.mockResolvedValue({
      data: [{ id: 1, name: 'Supplier A', contactEmail: 'test@example.com', active: true }]
    })
    acquisitionService.listBudgets.mockResolvedValue({
      data: [{ id: 5, name: 'Nowości', fiscalYear: '2026', allocatedAmount: '1000.00', spentAmount: '200.00', currency: 'PLN' }]
    })
    acquisitionService.listOrders.mockResolvedValue({ data: [] })
    acquisitionService.listWeeding.mockResolvedValue({ data: [] })
  })

  it('denies access for non-staff user', () => {
    mockUser = { roles: ['ROLE_USER'] }
    mockToken = 'test-token'
    render(<Acquisitions />)
    expect(screen.getByText(/Brak uprawnień/i)).toBeInTheDocument()
  })

  it('loads suppliers and allows librarian to create supplier', async () => {
    mockUser = { roles: ['ROLE_LIBRARIAN'] }
    acquisitionService.createSupplier.mockResolvedValue({})
    render(<Acquisitions />)

    expect(await screen.findAllByText('Supplier A')).not.toHaveLength(0)
    const suppliersCard = screen.getByRole('heading', { name: /Dostawcy/i }).closest('.surface-card')
    const suppliersScope = within(suppliersCard)

    await userEvent.type(suppliersScope.getByPlaceholderText(/Nazwa dostawcy/i), 'Supplier B')
    await userEvent.type(suppliersScope.getByPlaceholderText(/E-mail kontaktowy/i), 'contact@example.com')
    await userEvent.click(suppliersScope.getByRole('button', { name: /Dodaj dostawcę/i }))

    expect(acquisitionService.createSupplier).toHaveBeenCalledWith({
      name: 'Supplier B',
      contactEmail: 'contact@example.com',
      contactPhone: undefined,
      city: undefined,
      notes: undefined
    })
  })

  it('creates order with backend-compatible amount and budget fields', async () => {
    mockUser = { roles: ['ROLE_LIBRARIAN'] }
    acquisitionService.createOrder.mockResolvedValue({})
    render(<Acquisitions />)

    expect(await screen.findAllByText('Supplier A')).not.toHaveLength(0)
    const ordersCard = screen.getByRole('heading', { name: /Zamówienia/i }).closest('.surface-card')
    const ordersScope = within(ordersCard)

    await userEvent.selectOptions(ordersScope.getByDisplayValue('Wybierz dostawcę'), '1')
    await userEvent.selectOptions(ordersScope.getByDisplayValue('Bez budżetu'), '5')
    await userEvent.type(ordersScope.getByPlaceholderText(/Tytuł zamówienia/i), 'Pakiet nowości')
    await userEvent.type(ordersScope.getByPlaceholderText(/^Kwota$/i), '123.45')
    await userEvent.click(ordersScope.getByRole('button', { name: /Dodaj zamówienie/i }))

    expect(acquisitionService.createOrder).toHaveBeenCalledWith(expect.objectContaining({
      supplierId: 1,
      budgetId: 5,
      title: 'Pakiet nowości',
      totalAmount: 123.45,
      currency: 'PLN'
    }))
  })

  it('adds manual budget expense with backend default type', async () => {
    mockUser = { roles: ['ROLE_LIBRARIAN'] }
    acquisitionService.addExpense.mockResolvedValue({})
    render(<Acquisitions />)

    expect(await screen.findAllByText('Nowości')).not.toHaveLength(0)
    const budgetsCard = screen.getByRole('heading', { name: /Budżety/i }).closest('.surface-card')
    const budgetsScope = within(budgetsCard)

    await userEvent.selectOptions(budgetsScope.getByDisplayValue('Wybierz budżet'), '5')
    await userEvent.type(budgetsScope.getByPlaceholderText(/Kwota wydatku/i), '50')
    await userEvent.type(budgetsScope.getByPlaceholderText(/Opis wydatku/i), 'Transport')
    await userEvent.click(budgetsScope.getByRole('button', { name: /Zaksięguj wydatek/i }))

    expect(acquisitionService.addExpense).toHaveBeenCalledWith(5, {
      amount: 50,
      description: 'Transport'
    })
  })

  it('allows admin to open budget summary', async () => {
    mockUser = { roles: ['ROLE_ADMIN'] }
    acquisitionService.getBudgetSummary.mockResolvedValue({ allocated: 1000, spent: 300, remaining: 700, currency: 'PLN' })

    render(<Acquisitions />)

    expect(await screen.findAllByText('Nowości')).not.toHaveLength(0)
    await userEvent.click(screen.getByRole('button', { name: /Podsumowanie/i }))

    expect(acquisitionService.getBudgetSummary).toHaveBeenCalledWith(5)
    expect(await screen.findAllByText(/Pozostało/i)).not.toHaveLength(0)
    expect(await screen.findByText(/700.00 PLN/i)).toBeInTheDocument()
  })
})
