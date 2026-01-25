import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import CatalogAdmin from '../../../src/pages/admin/CatalogAdmin'
import { catalogService } from '../../../src/services/catalogService'
import { apiFetch } from '../../../src/api'

vi.mock('../../../src/services/catalogService', () => ({
  catalogService: {
    importCatalog: vi.fn(),
    exportCatalog: vi.fn()
  }
}))

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

let mockUser = null
vi.mock('../../../src/context/AuthContext', () => ({
  useAuth: () => ({ user: mockUser })
}))

describe('CatalogAdmin page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    apiFetch.mockResolvedValue({ data: [] })
  })

  it('denies access for non-admin', () => {
    mockUser = { roles: ['ROLE_USER'] }
    render(<CatalogAdmin />)
    expect(screen.getByText(/Brak upraw/i)).toBeInTheDocument()
  })

  it('validates import without file', async () => {
    mockUser = { roles: ['ROLE_ADMIN'] }
    render(<CatalogAdmin />)
    await userEvent.click(screen.getByRole('button', { name: /Import\/Eksport/i }))
    await userEvent.click(screen.getByRole('button', { name: /Importuj/i }))
    expect(screen.getByText(/Wybierz plik/i)).toBeInTheDocument()
  })

  it('exports catalog', async () => {
    mockUser = { roles: ['ROLE_ADMIN'] }
    catalogService.exportCatalog.mockResolvedValue({})
    render(<CatalogAdmin />)
    await userEvent.click(screen.getByRole('button', { name: /Import\/Eksport/i }))
    await userEvent.click(screen.getByRole('button', { name: /Eksportuj/i }))
    expect(catalogService.exportCatalog).toHaveBeenCalled()
  })

  it('imports catalog file', async () => {
    mockUser = { roles: ['ROLE_ADMIN'] }
    catalogService.importCatalog.mockResolvedValue({})
    const { container } = render(<CatalogAdmin />)
    await userEvent.click(screen.getByRole('button', { name: /Import\/Eksport/i }))
    const file = new File(['data'], 'catalog.csv', { type: 'text/csv' })
    await userEvent.upload(container.querySelector('input[type="file"]'), file)
    await userEvent.click(screen.getByRole('button', { name: /Importuj/i }))
    expect(catalogService.importCatalog).toHaveBeenCalledWith(file)
  })
})

