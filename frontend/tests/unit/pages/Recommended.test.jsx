import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import Recommended from '../../../src/pages/dashboard/Recommended'
import { apiFetch } from '../../../src/api'
import { ResourceCacheProvider } from '../../../src/context/ResourceCacheContext'

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

let mockAuth = { token: null, user: null }
vi.mock('../../../src/context/AuthContext', () => ({
  useAuth: () => mockAuth
}))

vi.mock('../../../src/components/BookItem', () => ({
  default: ({ book }) => <div data-testid="book-item">{book.title}</div>
}))

const renderPage = () => {
  return render(
    <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
      <ResourceCacheProvider>
        <Recommended />
      </ResourceCacheProvider>
    </MemoryRouter>
  )
}

describe('Recommended page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mockAuth = { token: 'token', user: { id: 1 } }
  })

  it('renders recommended groups', async () => {
    apiFetch.mockResolvedValue({
      groups: [
        { key: 'group-1', label: 'Group A', books: [{ id: 1, title: 'Alpha' }] }
      ]
    })

    renderPage()

    expect(await screen.findByText('Group A')).toBeInTheDocument()
    expect(screen.getByText('Alpha')).toBeInTheDocument()
  })
})


