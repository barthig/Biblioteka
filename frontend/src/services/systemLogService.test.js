import { describe, it, expect, vi } from 'vitest'
import { systemLogService } from './systemLogService'
import { apiFetch } from '../api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('systemLogService', () => {
  it('calls logs endpoint', async () => {
    apiFetch.mockResolvedValue('ok')
    await systemLogService.list()
    expect(apiFetch).toHaveBeenCalledWith('/api/admin/system/logs')
  })
})
