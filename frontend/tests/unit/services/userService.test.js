import { describe, it, expect, vi, beforeEach } from 'vitest'
import { userService } from '../../../src/services/userService'
import { apiFetch } from '../../../src/api'

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

describe('userService', () => {
  beforeEach(() => {
    vi.mocked(apiFetch).mockReset()
    vi.mocked(apiFetch).mockResolvedValue({})
  })

  it('fetches and updates profile', async () => {
    await userService.getProfile()
    await userService.updateProfile({ name: 'Jan' })

    expect(apiFetch).toHaveBeenNthCalledWith(1, '/api/me')
    expect(apiFetch).toHaveBeenNthCalledWith(2, '/api/me', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name: 'Jan' })
    })
  })

  it('changes password', async () => {
    await userService.changePassword('old', 'new')

    expect(apiFetch).toHaveBeenCalledWith('/api/me/password', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ currentPassword: 'old', newPassword: 'new' })
    })
  })

  it('manages favorites', async () => {
    await userService.getFavorites()
    await userService.addFavorite(10)
    await userService.removeFavorite(20)

    expect(apiFetch).toHaveBeenNthCalledWith(1, '/api/favorites')
    expect(apiFetch).toHaveBeenNthCalledWith(2, '/api/favorites', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ bookId: 10 })
    })
    expect(apiFetch).toHaveBeenNthCalledWith(3, '/api/favorites/20', {
      method: 'DELETE'
    })
  })

  it('fetches users with filters', async () => {
    await userService.getAllUsers({ role: 'admin', search: '' })

    expect(apiFetch).toHaveBeenCalledWith('/api/users?role=admin')
  })
})

