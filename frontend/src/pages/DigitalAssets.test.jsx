import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import DigitalAssets from './DigitalAssets'
import { digitalAssetService } from '../services/digitalAssetService'

vi.mock('../services/digitalAssetService', () => ({
  digitalAssetService: {
    list: vi.fn(),
    upload: vi.fn(),
    remove: vi.fn(),
    downloadUrl: vi.fn(() => '/download')
  }
}))

let mockUser = null
vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({ user: mockUser })
}))

describe('DigitalAssets page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('shows permissions message for non-librarian', () => {
    mockUser = { roles: ['ROLE_USER'] }
    render(<DigitalAssets />)
    expect(screen.getByText(/Brak upraw/i)).toBeInTheDocument()
  })

  it('loads assets for a book and allows delete', async () => {
    mockUser = { roles: ['ROLE_LIBRARIAN'] }
    digitalAssetService.list.mockResolvedValue({ data: [{ id: 1, filename: 'file.pdf' }] })
    const { container } = render(<DigitalAssets />)

    await userEvent.type(container.querySelector('input[type="number"]'), '10')
    expect(await screen.findByText('file.pdf')).toBeInTheDocument()

    await userEvent.click(screen.getByRole('button', { name: /Usu/i }))
    expect(digitalAssetService.remove).toHaveBeenCalledWith('10', 1)
  })

  it('uploads file when form is complete', async () => {
    mockUser = { roles: ['ROLE_ADMIN'] }
    digitalAssetService.list.mockResolvedValue({ data: [] })
    const { container } = render(<DigitalAssets />)

    const file = new File(['hello'], 'file.txt', { type: 'text/plain' })
    await userEvent.type(container.querySelector('input[type="number"]'), '7')
    await userEvent.upload(container.querySelector('input[type="file"]'), file)
    await userEvent.click(screen.getByRole('button', { name: /Prze/i }))

    expect(digitalAssetService.upload).toHaveBeenCalledWith('7', file)
  })

  it('shows error when loading assets fails', async () => {
    mockUser = { roles: ['ROLE_LIBRARIAN'] }
    digitalAssetService.list.mockRejectedValue(new Error('Load failed'))
    const { container } = render(<DigitalAssets />)

    await userEvent.type(container.querySelector('input[type="number"]'), '10')
    expect(await screen.findByText(/Load failed/i)).toBeInTheDocument()
  })
})
