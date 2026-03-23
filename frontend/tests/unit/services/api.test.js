import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { apiFetch } from '../../../src/api'

// Mock fetch globally
globalThis.fetch = vi.fn()

function createMockResponse({ ok = true, status = 200, data = {}, text, headers = { 'content-type': 'application/json' } } = {}) {
  return {
    ok,
    status,
    headers: {
      get: (name) => headers[name.toLowerCase()] ?? headers[name] ?? null
    },
    json: async () => data,
    text: async () => (text ?? JSON.stringify(data))
  }
}

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
      globalThis.fetch.mockResolvedValue(createMockResponse({ status: 200, data: mockData }))

      const result = await apiFetch('/api/test')

      expect(globalThis.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/test'),
        expect.objectContaining({
          headers: expect.any(Object)
        })
      )
      expect(result).toEqual(mockData)
    })

    it('should include auth token from localStorage', async () => {
      localStorage.setItem('token', 'test-token-123')
      
      globalThis.fetch.mockResolvedValue(createMockResponse({ status: 200, data: {} }))

      await apiFetch('/api/test')

      expect(globalThis.fetch).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          headers: expect.objectContaining({
            'Authorization': 'Bearer test-token-123'
          })
        })
      )
    })

    it('should handle POST request with body', async () => {
      globalThis.fetch.mockResolvedValue(createMockResponse({ status: 201, data: { id: 1 } }))

      const body = { name: 'New Item' }
      await apiFetch('/api/items', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      })

      expect(globalThis.fetch).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify(body)
        })
      )
    })

    it('should throw error on 404', async () => {
      globalThis.fetch.mockResolvedValue(createMockResponse({
        ok: false,
        status: 404,
        data: { error: 'Not found' }
      }))

      await expect(apiFetch('/api/notfound')).rejects.toThrow()
    })

    it('should throw error on 500', async () => {
      globalThis.fetch.mockResolvedValue(createMockResponse({
        ok: false,
        status: 500,
        data: { error: 'Server error' }
      }))

      await expect(apiFetch('/api/error')).rejects.toThrow()
    })

    it('should handle 401 Unauthorized', async () => {
      globalThis.fetch.mockResolvedValue(createMockResponse({
        ok: false,
        status: 401,
        data: { error: 'Unauthorized' }
      }))

      await expect(apiFetch('/api/protected')).rejects.toThrow()
    })

    it('should return null for empty response', async () => {
      globalThis.fetch.mockResolvedValue(createMockResponse({
        status: 204,
        data: null,
        text: '',
        headers: {}
      }))

      const result = await apiFetch('/api/delete')
      expect(result).toBeNull()
    })
  })
})

