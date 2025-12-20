import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import Recommended from './Recommended'
import { apiFetch } from '../api'
import { ResourceCacheProvider } from '../context/ResourceCacheContext'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({ token: null })
}))

vi.mock('../components/BookItem', () => ({
  default: ({ book }) => <div data-testid="book-item">{book.title}</div>
}))

const renderPage = () => {
  return render(
    <MemoryRouter>
      <ResourceCacheProvider>
        <Recommended />
      </ResourceCacheProvider>
    </MemoryRouter>
  )
}

describe('Recommended page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
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
