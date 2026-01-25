import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import UserRecommendations from '../../../src/UserRecommendations'
import { apiFetch } from '../api'

vi.mock('../api', () => ({
  apiFetch: vi.fn()
}))

describe('UserRecommendations', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders books when recommendations are available', async () => {
    apiFetch.mockResolvedValue({
      status: 'ok',
      data: [{ id: 1, title: 'Alpha', author: { name: 'Author A' } }]
    })
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <UserRecommendations />
      </MemoryRouter>
    )
    expect(await screen.findByText('Alpha')).toBeInTheDocument()
    expect(screen.getByText(/Author A/i)).toBeInTheDocument()
  })

  it('renders not enough data message', async () => {
    apiFetch.mockResolvedValue({ status: 'not_enough_data', data: [] })
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <UserRecommendations />
      </MemoryRouter>
    )
    expect(await screen.findByText(/Rate a few books/i)).toBeInTheDocument()
  })

  it('renders error state', async () => {
    apiFetch.mockRejectedValue(new Error('Nope'))
    render(
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <UserRecommendations />
      </MemoryRouter>
    )
    expect(await screen.findByText(/Nope/)).toBeInTheDocument()
  })
})


