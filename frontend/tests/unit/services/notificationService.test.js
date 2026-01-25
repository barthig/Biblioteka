import { describe, it, expect, vi } from 'vitest'
import { notificationService } from '../../../src/services/notificationService'
import { apiFetch } from '../../../src/api'

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

describe('notificationService', () => {
  it('lists notifications', async () => {
    apiFetch.mockResolvedValue({})
    await notificationService.list()
    expect(apiFetch).toHaveBeenCalledWith('/api/notifications')
  })

  it('sends test notification', async () => {
    apiFetch.mockResolvedValue({})
    await notificationService.sendTest('email', 'test@example.com', 'Test message')
    expect(apiFetch).toHaveBeenCalledWith('/api/notifications/test', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        channel: 'email',
        target: 'test@example.com',
        message: 'Test message'
      })
    })
  })
})

