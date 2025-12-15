import { apiFetch } from '../api'

export const reportService = {
  async getUsage() {
    return await apiFetch('/api/reports/usage')
  },

  async export() {
    return await apiFetch('/api/reports/export')
  },

  async getPopularTitles() {
    return await apiFetch('/api/reports/circulation/popular')
  },

  async getPatronSegments() {
    return await apiFetch('/api/reports/patrons/segments')
  },

  async getFinancialSummary() {
    return await apiFetch('/api/reports/financial')
  },

  async getInventoryOverview() {
    return await apiFetch('/api/reports/inventory')
  }
}
