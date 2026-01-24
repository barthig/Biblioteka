import { apiFetch } from '../api'

export const notificationService = {
  async list() {
    return await apiFetch('/api/notifications')
  },

  async sendTest(channel, target, message = null) {
    const body = { channel, target }
    if (message) {
      body.message = message
    }
    return await apiFetch('/api/notifications/test', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    })
  }
}
