import { describe, it, expect, vi, beforeEach } from 'vitest'
import { catalogService } from '../../../src/services/catalogService'
import { apiFetch } from '../../../src/api'

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

describe('catalogService', () => {
  beforeEach(() => {
    apiFetch.mockClear()
  })

  it('exports catalog', async () => {
    apiFetch.mockResolvedValue({})
    await catalogService.exportCatalog()
    expect(apiFetch).toHaveBeenCalledWith('/api/admin/catalog/export')
  })

  it('imports catalog with form data', async () => {
    apiFetch.mockResolvedValue({})
    const file = new File(['data'], 'catalog.csv', { type: 'text/csv' })
    await catalogService.importCatalog(file)
    const [url, opts] = apiFetch.mock.calls.at(-1)
    expect(url).toBe('/api/admin/catalog/import')
    expect(opts.method).toBe('POST')
    expect(typeof opts.body.append).toBe('function')
  })
})

