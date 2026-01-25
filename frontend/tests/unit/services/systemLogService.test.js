import { describe, it, expect, vi } from 'vitest'
import { systemLogService } from '../../../src/services/systemLogService'
import { apiFetch } from '../../../src/api'

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

describe('systemLogService', () => {
  it('calls logs endpoint', async () => {
    apiFetch.mockResolvedValue('ok')
    await systemLogService.list()
    expect(apiFetch).toHaveBeenCalledWith('/api/admin/system/logs')
  })
})

