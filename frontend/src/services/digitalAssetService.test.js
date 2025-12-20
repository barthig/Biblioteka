import { describe, it, expect, vi } from 'vitest'
import { digitalAssetService } from './digitalAssetService'
import { apiFetch } from '../api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('digitalAssetService', () => {
  it('lists assets for a book', async () => {
    apiFetch.mockResolvedValue({})
    await digitalAssetService.list(12)
    expect(apiFetch).toHaveBeenCalledWith('/api/admin/books/12/assets')
  })

  it('uploads an asset with form data', async () => {
    apiFetch.mockResolvedValue({})
    const file = new File(['hello'], 'file.txt', { type: 'text/plain' })
    await digitalAssetService.upload(5, file)
    const [url, opts] = apiFetch.mock.calls[0]
    expect(url).toBe('/api/admin/books/5/assets')
    expect(opts.method).toBe('POST')
    expect(typeof opts.body.append).toBe('function')
  })

  it('builds download url', () => {
    expect(digitalAssetService.downloadUrl(3, 9)).toBe('/api/admin/books/3/assets/9')
  })

  it('removes an asset', async () => {
    apiFetch.mockResolvedValue({})
    await digitalAssetService.remove(7, 2)
    expect(apiFetch).toHaveBeenCalledWith('/api/admin/books/7/assets/2', { method: 'DELETE' })
  })
})
