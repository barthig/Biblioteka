import { apiFetch } from '../api'

/**
 * Service for health checks and system status
 */
export const healthService = {
  /**
   * Check API health status
   */
  async checkHealth() {
    return await apiFetch('/health')
  },

  /**
   * Get library hours
   */
  async getLibraryHours() {
    return await apiFetch('/api/library/hours')
  },

  /**
   * Get current alerts
   */
  async getAlerts() {
    return await apiFetch('/api/alerts')
  }
}
