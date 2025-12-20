import { describe, it, expect, vi } from 'vitest'
import { catalogService } from './catalogService'
import { apiFetch } from '../api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('catalogService', () => {
  it('exports catalog', async () => {
    apiFetch.mockResolvedValue({})
    await catalogService.exportCatalog()
    expect(apiFetch).toHaveBeenCalledWith('/api/admin/catalog/export')
  })

  it('imports catalog with form data', async () => {
    apiFetch.mockResolvedValue({})
    const file = new File(['data'], 'catalog.csv', { type: 'text/csv' })
    await catalogService.importCatalog(file)
    const [url, opts] = apiFetch.mock.calls[0]
    expect(url).toBe('/api/admin/catalog/import')
    expect(opts.method).toBe('POST')
    expect(typeof opts.body.append).toBe('function')
  })
})
