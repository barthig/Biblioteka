import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import RolesAndAudit from '../../../../src/components/admin/RolesAndAudit'

describe('RolesAndAudit', () => {
  const baseProps = {
    roles: [],
    auditLogs: [],
    users: [],
    loading: false,
    rolesLoaded: true,
    loadRolesAndAudit: vi.fn(),
    roleForm: { name: '', roleKey: '', modules: '', description: '' },
    setRoleForm: vi.fn(),
    createRole: vi.fn((event) => event.preventDefault()),
    updateRole: vi.fn(),
    assignForm: { roleKey: '', userId: '' },
    setAssignForm: vi.fn(),
    assignRole: vi.fn((event) => event.preventDefault()),
    entityAuditForm: { entityType: '', entityId: '' },
    setEntityAuditForm: vi.fn(),
    entityAuditLogs: [],
    entityAuditLoading: false,
    loadEntityAudit: vi.fn()
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('submits create role form', async () => {
    const props = {
      ...baseProps,
      roleForm: { name: 'Reporter', roleKey: 'ROLE_REPORTER', modules: 'reports', description: '' }
    }
    render(<RolesAndAudit {...props} />)
    await userEvent.click(screen.getByRole('button', { name: /Dodaj ro/i }))
    expect(props.createRole).toHaveBeenCalled()
  })

  it('triggers update role for list item', async () => {
    const props = {
      ...baseProps,
      roles: [{ roleKey: 'ROLE_TEST', name: 'Test' }]
    }
    render(<RolesAndAudit {...props} />)
    await userEvent.click(screen.getByRole('button', { name: /Edytuj ro/i }))
    expect(props.updateRole).toHaveBeenCalled()
  })

  it('loads entity audit on button click', async () => {
    render(<RolesAndAudit {...baseProps} />)
    await userEvent.click(screen.getByRole('button', { name: /Pobierz/i }))
    expect(baseProps.loadEntityAudit).toHaveBeenCalled()
  })
})

