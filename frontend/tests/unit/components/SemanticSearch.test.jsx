import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'
import SemanticSearch from '../../../src/components/books/SemanticSearch'
import { apiFetch } from '../../../src/api'

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

describe('SemanticSearch', () => {
  it('shows validation error on empty query', () => {
    render(<SemanticSearch />)
    fireEvent.click(screen.getByRole('button', { name: /Search/i }))
    expect(screen.getByText(/Please enter a search query/i)).toBeInTheDocument()
  })

  it('renders results after search', async () => {
    apiFetch.mockResolvedValue({
      data: [{ id: 1, title: 'Alpha', author: { name: 'Author A' }, description: 'Desc' }]
    })
    render(<SemanticSearch />)
    fireEvent.change(screen.getByLabelText(/Search prompt/i), { target: { value: 'space' } })
    fireEvent.click(screen.getByRole('button', { name: /Search/i }))
    expect(await screen.findByText('Alpha')).toBeInTheDocument()
    expect(screen.getByText('Desc')).toBeInTheDocument()
  })

  it('renders error on failure', async () => {
    apiFetch.mockRejectedValue(new Error('Search failed.'))
    render(<SemanticSearch />)
    fireEvent.change(screen.getByLabelText(/Search prompt/i), { target: { value: 'space' } })
    fireEvent.click(screen.getByRole('button', { name: /Search/i }))
    expect(await screen.findByText(/Search failed/i)).toBeInTheDocument()
  })
})

