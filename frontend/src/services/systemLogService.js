import { apiFetch } from '../api'

export const systemLogService = {
  async list() {
    return await apiFetch('/api/admin/system/logs')
  }
}
