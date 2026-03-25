import React from 'react'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import LibrarianDashboard from '../../../src/pages/admin/LibrarianDashboard'
import { apiFetch } from '../../../src/api'
import { ResourceCacheProvider } from '../../../src/context/ResourceCacheContext'

vi.mock('../../../src/api', () => ({
  apiFetch: vi.fn()
}))

vi.mock('react-hot-toast', () => ({
  default: { error: vi.fn() }
}))

describe('LibrarianDashboard page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders statistic cards after load', async () => {
    apiFetch.mockResolvedValue({
      activeLoans: 3,
      overdueLoans: 1,
      pendingReservations: 2,
      totalUsers: 10,
      totalBooks: 20,
      availableCopies: 15,
      popularBooks: [],
      recentActivity: []
    })

    render(
      <ResourceCacheProvider>
        <LibrarianDashboard />
      </ResourceCacheProvider>
    )

    expect(await screen.findByText('Panel bibliotekarza')).toBeInTheDocument()
    expect(await screen.findByText('3')).toBeInTheDocument()
    expect(screen.getByText(/^Aktywne wypożyczenia$/i)).toBeInTheDocument()
    expect(screen.getByText(/^Zaległe zwroty$/i)).toBeInTheDocument()
    expect(screen.getByText(/^Rezerwacje$/i)).toBeInTheDocument()
    expect(screen.getByText(/^Użytkownicy$/i)).toBeInTheDocument()
    expect(screen.getAllByText(/Książki/i).length).toBeGreaterThan(0)
    expect(screen.getByText(/^Dostępne egzemplarze$/i)).toBeInTheDocument()
    expect(screen.getByText('15')).toBeInTheDocument()
  })
})
