import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import AdminPanel from './AdminPanel'
import { apiFetch } from '../api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('AdminPanel page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    window.confirm = vi.fn(() => true)
    window.prompt = vi.fn(() => 'new-value')
  })

  it('loads and renders users table', async () => {
    apiFetch.mockResolvedValue([
      { id: 1, name: 'Admin User', email: 'admin@example.com', roles: ['ROLE_ADMIN'] }
    ])

    render(<AdminPanel />)

    expect(await screen.findByText('Admin User')).toBeInTheDocument()
    expect(screen.getByText('admin@example.com')).toBeInTheDocument()
  })

  it('searches users and blocks account', async () => {
    apiFetch.mockImplementation((endpoint) => {
      if (endpoint === '/api/users') {
        return Promise.resolve([{ id: 1, name: 'User One', email: 'one@example.com', roles: ['ROLE_USER'], blocked: false }])
      }
      if (endpoint.startsWith('/api/users/search')) {
        return Promise.resolve([{ id: 2, name: 'User Two', email: 'two@example.com', roles: ['ROLE_USER'], blocked: false }])
      }
      if (endpoint === '/api/users/2/block') {
        return Promise.resolve({})
      }
      return Promise.resolve([])
    })

    render(<AdminPanel />)

    const searchInput = await screen.findByPlaceholderText(/Szukaj/i)
    await userEvent.type(searchInput, 'User')

    expect(await screen.findByText('User Two')).toBeInTheDocument()
    const row = screen.getByText('User Two').closest('tr')
    await userEvent.click(within(row).getByRole('button', { name: /Zablokuj/i }))

    expect(apiFetch).toHaveBeenCalledWith('/api/users/2/block', expect.objectContaining({ method: 'POST' }))
  })

  it('updates setting and toggles integration', async () => {
    apiFetch.mockImplementation((endpoint) => {
      if (endpoint === '/api/admin/system/settings') {
        return Promise.resolve([{ key: 'site_name', value: 'Old' }])
      }
      if (endpoint === '/api/admin/system/integrations') {
        return Promise.resolve([{ id: 10, name: 'Slack', type: 'HTTP', endpoint: 'http://example', enabled: false }])
      }
      if (endpoint === '/api/admin/system/backups') {
        return Promise.resolve([])
      }
      if (endpoint === '/api/admin/system/settings/site_name') {
        return Promise.resolve({})
      }
      if (endpoint === '/api/admin/system/integrations/10') {
        return Promise.resolve({})
      }
      return Promise.resolve([])
    })

    render(<AdminPanel />)

    await userEvent.click(screen.getByRole('button', { name: /System/i }))
    expect(await screen.findByText('site_name')).toBeInTheDocument()
    await userEvent.click(screen.getByRole('button', { name: /Edytuj/i }))
    expect(apiFetch).toHaveBeenCalledWith('/api/admin/system/settings/site_name', expect.objectContaining({ method: 'PUT' }))

    const integrationItem = screen.getByText('Slack').closest('li')
    const integrationCheckbox = within(integrationItem).getByRole('checkbox')
    await userEvent.click(integrationCheckbox)
    expect(apiFetch).toHaveBeenCalledWith('/api/admin/system/integrations/10', expect.objectContaining({ method: 'PUT' }))
  })

  it('creates a role and assigns it', async () => {
    apiFetch.mockImplementation((endpoint) => {
      if (endpoint === '/api/admin/system/roles') {
        return Promise.resolve({ roles: [{ roleKey: 'ROLE_USER', name: 'User' }] })
      }
      if (endpoint === '/api/audit-logs?limit=25') {
        return Promise.resolve({ data: [] })
      }
      return Promise.resolve({})
    })

    render(<AdminPanel />)
    await userEvent.click(screen.getByRole('button', { name: /Audyt/i }))

    const roleForm = screen.getByRole('button', { name: /Dodaj rol/i }).closest('form')
    const roleInputs = roleForm.querySelectorAll('input')
    await userEvent.type(roleInputs[0], 'Reporter')
    await userEvent.type(roleForm.querySelector('input[placeholder="np. ROLE_REPORTER"]'), 'ROLE_REPORTER')
    const moduleInput = roleForm.querySelector('input[placeholder="loans,acquisitions"]')
    await userEvent.type(moduleInput, 'reports,loans')
    await userEvent.click(within(roleForm).getByRole('button', { name: /Dodaj rol/i }))

    expect(apiFetch).toHaveBeenCalledWith('/api/admin/system/roles', expect.objectContaining({ method: 'POST' }))

    const assignForm = screen.getByRole('button', { name: /Przypisz rol/i }).closest('form')
    await userEvent.selectOptions(within(assignForm).getByRole('combobox'), 'ROLE_USER')
    await userEvent.type(within(assignForm).getByRole('spinbutton'), '12')
    await userEvent.click(within(assignForm).getByRole('button', { name: /Przypisz rol/i }))

    expect(apiFetch).toHaveBeenCalledWith('/api/admin/system/roles/ROLE_USER/assign', expect.objectContaining({ method: 'POST' }))
  })

  it('shows validation when assigning role without data', async () => {
    apiFetch.mockImplementation((endpoint) => {
      if (endpoint === '/api/admin/system/roles') {
        return Promise.resolve({ roles: [{ roleKey: 'ROLE_USER', name: 'User' }] })
      }
      if (endpoint === '/api/audit-logs?limit=25') {
        return Promise.resolve({ data: [] })
      }
      return Promise.resolve({})
    })

    render(<AdminPanel />)
    await userEvent.click(screen.getByRole('button', { name: /Audyt/i }))
    const assignForm = screen.getByRole('button', { name: /Przypisz rol/i }).closest('form')
    await userEvent.type(within(assignForm).getByRole('spinbutton'), '5')
    await userEvent.click(within(assignForm).getByRole('button', { name: /Przypisz rol/i }))
    expect(await screen.findByText(/Podaj rol/i)).toBeInTheDocument()
  })

  it('creates staff account', async () => {
    apiFetch.mockImplementation((endpoint) => {
      if (endpoint === '/api/admin/system/roles') {
        return Promise.resolve({ roles: [] })
      }
      if (endpoint === '/api/users') {
        return Promise.resolve({})
      }
      return Promise.resolve([])
    })

    const { container } = render(<AdminPanel />)
    await userEvent.click(screen.getByRole('button', { name: /Konta prac/i }))

    const staffForm = container.querySelector('form.form')
    const inputs = staffForm.querySelectorAll('input')
    await userEvent.type(inputs[0], 'Jan Nowak')
    await userEvent.type(inputs[1], 'jan@example.com')
    await userEvent.type(inputs[2], 'temp12345')
    await userEvent.click(within(staffForm).getByRole('button', { name: /Utw/i }))

    expect(apiFetch).toHaveBeenCalledWith('/api/users', expect.objectContaining({ method: 'POST' }))
  })
})
