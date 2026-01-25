import { describe, it, expect, vi } from 'vitest'
import { reportService } from '../../../src/services/reportService'
import { apiFetch } from '../../../src/services/api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('reportService', () => {
  it('requests usage report', async () => {
    apiFetch.mockResolvedValue({})
    await reportService.getUsage()
    expect(apiFetch).toHaveBeenCalledWith('/api/reports/usage')
  })

  it('requests export report', async () => {
    apiFetch.mockResolvedValue({})
    await reportService.export()
    expect(apiFetch).toHaveBeenCalledWith('/api/reports/export')
  })

  it('requests popular titles', async () => {
    apiFetch.mockResolvedValue({})
    await reportService.getPopularTitles()
    expect(apiFetch).toHaveBeenCalledWith('/api/reports/circulation/popular')
  })

  it('requests patron segments', async () => {
    apiFetch.mockResolvedValue({})
    await reportService.getPatronSegments()
    expect(apiFetch).toHaveBeenCalledWith('/api/reports/patrons/segments')
  })

  it('requests financial summary', async () => {
    apiFetch.mockResolvedValue({})
    await reportService.getFinancialSummary()
    expect(apiFetch).toHaveBeenCalledWith('/api/reports/financial')
  })

  it('requests inventory overview', async () => {
    apiFetch.mockResolvedValue({})
    await reportService.getInventoryOverview()
    expect(apiFetch).toHaveBeenCalledWith('/api/reports/inventory')
  })
})

