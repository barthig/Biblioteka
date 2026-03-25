import React from 'react'
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
    fireEvent.click(screen.getByRole('button', { name: /Szukaj z AI/i }))
    expect(screen.getByText(/Wpisz opis, klimat albo temat/i)).toBeInTheDocument()
  })

  it('renders results after search', async () => {
    apiFetch.mockResolvedValue({
      data: [{ id: 1, title: 'Alpha', author: { name: 'Author A' }, description: 'Desc' }],
      meta: { aiAvailable: false, mode: 'semantic-hybrid-local' }
    })
    render(<SemanticSearch />)
    fireEvent.change(screen.getByLabelText(/Zapytanie AI/i), { target: { value: 'space' } })
    fireEvent.click(screen.getByRole('button', { name: /Szukaj z AI/i }))
    expect(await screen.findByText('Alpha')).toBeInTheDocument()
    expect(screen.getByText('Desc')).toBeInTheDocument()
    expect(screen.getByText(/lokalne wyniki semantyczno-hybrydowe/i)).toBeInTheDocument()
  })

  it('renders error on failure', async () => {
    apiFetch.mockRejectedValue(new Error('Wyszukiwanie nie powiodło się.'))
    render(<SemanticSearch />)
    fireEvent.change(screen.getByLabelText(/Zapytanie AI/i), { target: { value: 'space' } })
    fireEvent.click(screen.getByRole('button', { name: /Szukaj z AI/i }))
    expect(await screen.findByText(/Wyszukiwanie nie powiodło się/i)).toBeInTheDocument()
  })
})
