import React from 'react'
import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import NotFound from '../../../src/pages/NotFound'

describe('NotFound page', () => {
  it('renders Polish 404 content and return link', () => {
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <NotFound />
      </MemoryRouter>
    )

    expect(screen.getByText('404')).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: /Strona nie znaleziona/i })).toBeInTheDocument()
    expect(screen.getByText(/Strona, której szukasz, nie istnieje/i)).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /Wróć na stronę główną/i })).toHaveAttribute('href', '/')
  })
})
