import { describe, it, expect, vi } from 'vitest'
import { notificationService } from './notificationService'
import { apiFetch } from '../api'

vi.mock('../api', () => ({
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
    await notificationService.sendTest()
    expect(apiFetch).toHaveBeenCalledWith('/api/notifications/test', { method: 'POST' })
  })
})
