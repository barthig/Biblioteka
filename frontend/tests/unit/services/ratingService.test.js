import { describe, it, expect, vi } from 'vitest'
import { ratingService } from '../../../src/services/ratingService'
import { apiFetch } from '../../../src/services/api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('ratingService', () => {
  it('requests user ratings', async () => {
    apiFetch.mockResolvedValue({})
    await ratingService.getMyRatings()
    expect(apiFetch).toHaveBeenCalledWith('/api/users/me/ratings')
  })

  it('deletes rating by book and rating id', async () => {
    apiFetch.mockResolvedValue({})
    await ratingService.deleteRating(10, 22)
    expect(apiFetch).toHaveBeenCalledWith('/api/books/10/ratings/22', { method: 'DELETE' })
  })
})

