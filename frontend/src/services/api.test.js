import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { apiFetch } from './api'

// Mock fetch globally
global.fetch = vi.fn()

describe('api', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  describe('apiFetch', () => {
    it('should make successful GET request', async () => {
      const mockData = { id: 1, name: 'Test' }
      global.fetch.mockResolvedValue({
        ok: true,
        status: 200,
        json: async () => mockData,
        text: async () => JSON.stringify(mockData)
      })

      const result = await apiFetch('/api/test')

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/test'),
        expect.objectContaining({
          method: 'GET'
        })
      )
      expect(result).toEqual(mockData)
    })

    it('should include auth token from localStorage', async () => {
      localStorage.setItem('token', 'test-token-123')
      
      global.fetch.mockResolvedValue({
        ok: true,
        status: 200,
        json: async () => ({}),
        text: async () => '{}'
      })

      await apiFetch('/api/test')

      expect(global.fetch).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          headers: expect.objectContaining({
            'Authorization': 'Bearer test-token-123'
          })
        })
      )
    })

    it('should handle POST request with body', async () => {
      global.fetch.mockResolvedValue({
        ok: true,
        status: 201,
        json: async () => ({ id: 1 }),
        text: async () => '{"id":1}'
      })

      const body = { name: 'New Item' }
      await apiFetch('/api/items', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      })

      expect(global.fetch).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify(body)
        })
      )
    })

    it('should throw error on 404', async () => {
      global.fetch.mockResolvedValue({
        ok: false,
        status: 404,
        statusText: 'Not Found',
        json: async () => ({ error: 'Not found' }),
        text: async () => '{"error":"Not found"}'
      })

      await expect(apiFetch('/api/notfound')).rejects.toThrow()
    })

    it('should throw error on 500', async () => {
      global.fetch.mockResolvedValue({
        ok: false,
        status: 500,
        statusText: 'Internal Server Error',
        json: async () => ({ error: 'Server error' }),
        text: async () => '{"error":"Server error"}'
      })

      await expect(apiFetch('/api/error')).rejects.toThrow()
    })

    it('should handle 401 Unauthorized', async () => {
      global.fetch.mockResolvedValue({
        ok: false,
        status: 401,
        statusText: 'Unauthorized',
        json: async () => ({ error: 'Unauthorized' }),
        text: async () => '{"error":"Unauthorized"}'
      })

      await expect(apiFetch('/api/protected')).rejects.toThrow()
    })

    it('should return null for empty response', async () => {
      global.fetch.mockResolvedValue({
        ok: true,
        status: 204,
        json: async () => null,
        text: async () => ''
      })

      const result = await apiFetch('/api/delete')
      expect(result).toBeNull()
    })
  })
})
