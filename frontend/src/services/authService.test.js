import { describe, it, expect, beforeEach, vi } from 'vitest'
import { authService } from './authService'
import * as api from '../api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('authService', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('throws when refresh token is missing', async () => {
    await expect(authService.refresh()).rejects.toThrow('Missing refresh token')
    expect(api.apiFetch).not.toHaveBeenCalled()
  })

  it('refreshes token', async () => {
    api.apiFetch.mockResolvedValue({ token: 'new-token' })
    const result = await authService.refresh('refresh-token')

    expect(api.apiFetch).toHaveBeenCalledWith('/api/auth/refresh', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refreshToken: 'refresh-token' })
    })
    expect(result).toEqual({ token: 'new-token' })
  })

  it('logs out with refresh token', async () => {
    api.apiFetch.mockResolvedValue({ ok: true })
    await authService.logout('refresh-token')

    expect(api.apiFetch).toHaveBeenCalledWith('/api/auth/logout', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refreshToken: 'refresh-token' })
    })
  })

  it('logs out all sessions', async () => {
    api.apiFetch.mockResolvedValue({ ok: true })
    await authService.logoutAll()

    expect(api.apiFetch).toHaveBeenCalledWith('/api/auth/logout-all', { method: 'POST' })
  })

  it('fetches profile', async () => {
    api.apiFetch.mockResolvedValue({ id: 1 })
    await authService.profile()

    expect(api.apiFetch).toHaveBeenCalledWith('/api/auth/profile')
  })

  it('fetches legacy profile', async () => {
    api.apiFetch.mockResolvedValue({ id: 1 })
    await authService.legacyProfile()

    expect(api.apiFetch).toHaveBeenCalledWith('/api/profile')
  })
})
