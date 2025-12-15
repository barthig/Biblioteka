import { apiFetch } from '../api'

export const notificationService = {
  async list() {
    return await apiFetch('/api/notifications')
  },

  async sendTest() {
    return await apiFetch('/api/notifications/test', { method: 'POST' })
  }
}
