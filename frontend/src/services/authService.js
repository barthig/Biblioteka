import { apiFetch } from '../api'

export const authService = {
  async refresh(refreshToken) {
    if (!refreshToken) {
      throw new Error('Missing refresh token')
    }
    return await apiFetch('/api/auth/refresh', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refreshToken })
    })
  },

  async logout(refreshToken) {
    return await apiFetch('/api/auth/logout', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refreshToken })
    })
  },

  async logoutAll() {
    return await apiFetch('/api/auth/logout-all', { method: 'POST' })
  },

  async profile() {
    return await apiFetch('/api/auth/profile')
  },

  async legacyProfile() {
    return await apiFetch('/api/profile')
  }
}
