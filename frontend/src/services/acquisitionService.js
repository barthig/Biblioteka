import { apiFetch } from '../api'

export const acquisitionService = {
  // Suppliers
  async listSuppliers() {
    return await apiFetch('/api/admin/acquisitions/suppliers')
  },
  async createSupplier(payload) {
    return await apiFetch('/api/admin/acquisitions/suppliers', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
  },
  async updateSupplier(id, payload) {
    return await apiFetch(`/api/admin/acquisitions/suppliers/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
  },
  async deactivateSupplier(id) {
    return await apiFetch(`/api/admin/acquisitions/suppliers/${id}`, {
      method: 'DELETE'
    })
  },

  // Budgets
  async listBudgets() {
    return await apiFetch('/api/admin/acquisitions/budgets')
  },
  async createBudget(payload) {
    return await apiFetch('/api/admin/acquisitions/budgets', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
  },
  async updateBudget(id, payload) {
    return await apiFetch(`/api/admin/acquisitions/budgets/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
  },
  async addExpense(budgetId, payload) {
    return await apiFetch(`/api/admin/acquisitions/budgets/${budgetId}/expenses`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
  },

  // Orders
  async listOrders() {
    return await apiFetch('/api/admin/acquisitions/orders')
  },
  async createOrder(payload) {
    return await apiFetch('/api/admin/acquisitions/orders', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
  },
  async updateOrderStatus(id, payload) {
    return await apiFetch(`/api/admin/acquisitions/orders/${id}/status`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
  },
  async receiveOrder(id) {
    return await apiFetch(`/api/admin/acquisitions/orders/${id}/receive`, {
      method: 'POST'
    })
  },
  async cancelOrder(id) {
    return await apiFetch(`/api/admin/acquisitions/orders/${id}/cancel`, {
      method: 'POST'
    })
  },

  // Weeding
  async listWeeding() {
    return await apiFetch('/api/admin/acquisitions/weeding')
  },
  async createWeeding(payload) {
    return await apiFetch('/api/admin/acquisitions/weeding', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
  }
}
