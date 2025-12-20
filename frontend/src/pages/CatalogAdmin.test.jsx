import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import CatalogAdmin from './CatalogAdmin'
import { catalogService } from '../services/catalogService'

vi.mock('../services/catalogService', () => ({
  catalogService: {
    importCatalog: vi.fn(),
    exportCatalog: vi.fn()
  }
}))

let mockUser = null
vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({ user: mockUser })
}))

describe('CatalogAdmin page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('denies access for non-admin', () => {
    mockUser = { roles: ['ROLE_USER'] }
    render(<CatalogAdmin />)
    expect(screen.getByText(/Brak upraw/i)).toBeInTheDocument()
  })

  it('validates import without file', async () => {
    mockUser = { roles: ['ROLE_ADMIN'] }
    render(<CatalogAdmin />)
    await userEvent.click(screen.getByRole('button', { name: /Importuj/i }))
    expect(screen.getByText(/Wybierz plik/i)).toBeInTheDocument()
  })

  it('exports catalog', async () => {
    mockUser = { roles: ['ROLE_ADMIN'] }
    catalogService.exportCatalog.mockResolvedValue({})
    render(<CatalogAdmin />)
    await userEvent.click(screen.getByRole('button', { name: /Eksportuj/i }))
    expect(catalogService.exportCatalog).toHaveBeenCalled()
  })

  it('imports catalog file', async () => {
    mockUser = { roles: ['ROLE_ADMIN'] }
    catalogService.importCatalog.mockResolvedValue({})
    render(<CatalogAdmin />)
    const file = new File(['data'], 'catalog.csv', { type: 'text/csv' })
    await userEvent.upload(screen.getByLabelText(/Plik katalogu/i), file)
    await userEvent.click(screen.getByRole('button', { name: /Importuj/i }))
    expect(catalogService.importCatalog).toHaveBeenCalledWith(file)
  })
})
