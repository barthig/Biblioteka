import { describe, it, expect, vi, beforeEach } from 'vitest'
import { acquisitionService } from './acquisitionService'
import { apiFetch } from '../api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('acquisitionService', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('handles suppliers endpoints', async () => {
    apiFetch.mockResolvedValue({})
    await acquisitionService.listSuppliers()
    await acquisitionService.createSupplier({ name: 'A' })
    await acquisitionService.updateSupplier(2, { name: 'B' })
    await acquisitionService.deactivateSupplier(3)

    expect(apiFetch).toHaveBeenNthCalledWith(1, '/api/admin/acquisitions/suppliers')
    expect(apiFetch).toHaveBeenNthCalledWith(2, '/api/admin/acquisitions/suppliers', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name: 'A' })
    })
    expect(apiFetch).toHaveBeenNthCalledWith(3, '/api/admin/acquisitions/suppliers/2', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name: 'B' })
    })
    expect(apiFetch).toHaveBeenNthCalledWith(4, '/api/admin/acquisitions/suppliers/3', {
      method: 'DELETE'
    })
  })

  it('handles budgets endpoints', async () => {
    apiFetch.mockResolvedValue({})
    await acquisitionService.listBudgets()
    await acquisitionService.createBudget({ name: 'Budget', amount: 10 })
    await acquisitionService.updateBudget(4, { amount: 20 })
    await acquisitionService.addExpense(5, { amount: 2 })

    expect(apiFetch).toHaveBeenNthCalledWith(1, '/api/admin/acquisitions/budgets')
    expect(apiFetch).toHaveBeenNthCalledWith(2, '/api/admin/acquisitions/budgets', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name: 'Budget', amount: 10 })
    })
    expect(apiFetch).toHaveBeenNthCalledWith(3, '/api/admin/acquisitions/budgets/4', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ amount: 20 })
    })
    expect(apiFetch).toHaveBeenNthCalledWith(4, '/api/admin/acquisitions/budgets/5/expenses', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ amount: 2 })
    })
  })

  it('handles orders endpoints', async () => {
    apiFetch.mockResolvedValue({})
    await acquisitionService.listOrders()
    await acquisitionService.createOrder({ title: 'Book', amount: 1 })
    await acquisitionService.updateOrderStatus(7, { status: 'SENT' })
    await acquisitionService.receiveOrder(8)
    await acquisitionService.cancelOrder(9)

    expect(apiFetch).toHaveBeenNthCalledWith(1, '/api/admin/acquisitions/orders')
    expect(apiFetch).toHaveBeenNthCalledWith(2, '/api/admin/acquisitions/orders', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ title: 'Book', amount: 1 })
    })
    expect(apiFetch).toHaveBeenNthCalledWith(3, '/api/admin/acquisitions/orders/7/status', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: 'SENT' })
    })
    expect(apiFetch).toHaveBeenNthCalledWith(4, '/api/admin/acquisitions/orders/8/receive', {
      method: 'POST'
    })
    expect(apiFetch).toHaveBeenNthCalledWith(5, '/api/admin/acquisitions/orders/9/cancel', {
      method: 'POST'
    })
  })

  it('handles weeding endpoints', async () => {
    apiFetch.mockResolvedValue({})
    await acquisitionService.listWeeding()
    await acquisitionService.createWeeding({ bookId: 2 })
    expect(apiFetch).toHaveBeenNthCalledWith(1, '/api/admin/acquisitions/weeding')
    expect(apiFetch).toHaveBeenNthCalledWith(2, '/api/admin/acquisitions/weeding', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ bookId: 2 })
    })
  })
})
