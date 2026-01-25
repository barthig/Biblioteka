import { describe, it, expect, vi, beforeEach } from 'vitest'
import { announcementService } from '../../../src/services/announcementService'
import { apiFetch } from '../../../src/services/api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('announcementService', () => {
  beforeEach(() => {
    vi.mocked(apiFetch).mockReset()
    vi.mocked(apiFetch).mockResolvedValue({})
  })

  it('builds query params when fetching announcements', async () => {
    await announcementService.getAnnouncements({ type: 'info', search: '' })

    expect(apiFetch).toHaveBeenCalledWith('/api/announcements?type=info')
  })

  it('fetches single announcement', async () => {
    await announcementService.getAnnouncement(5)

    expect(apiFetch).toHaveBeenCalledWith('/api/announcements/5')
  })

  it('sends body when creating announcement', async () => {
    const payload = { title: 'Nowe godziny', content: 'Opis' }
    await announcementService.createAnnouncement(payload)

    expect(apiFetch).toHaveBeenCalledWith('/api/announcements', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
  })

  it('updates and deletes announcements', async () => {
    await announcementService.updateAnnouncement(3, { title: 'X' })
    await announcementService.deleteAnnouncement(4)

    expect(apiFetch).toHaveBeenNthCalledWith(1, '/api/announcements/3', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ title: 'X' })
    })
    expect(apiFetch).toHaveBeenNthCalledWith(2, '/api/announcements/4', {
      method: 'DELETE'
    })
  })

  it('publishes and archives announcements', async () => {
    await announcementService.publishAnnouncement(7)
    await announcementService.archiveAnnouncement(8)

    expect(apiFetch).toHaveBeenNthCalledWith(1, '/api/announcements/7/publish', { method: 'POST' })
    expect(apiFetch).toHaveBeenNthCalledWith(2, '/api/announcements/8/archive', { method: 'POST' })
  })
})

