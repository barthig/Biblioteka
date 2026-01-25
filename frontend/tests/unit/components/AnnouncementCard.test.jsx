import { render, screen, fireEvent } from '@testing-library/react'
import { describe, it, expect, vi } from 'vitest'
import AnnouncementCard from '../../../src/components/books/AnnouncementCard'

describe('AnnouncementCard', () => {
  const baseAnnouncement = {
    id: 1,
    title: 'Nowe zasady',
    content: 'Pełna treść ogłoszenia bibliotecznego',
    type: 'warning',
    createdAt: '2024-10-01T10:15:00Z',
    createdBy: { name: 'Bibliotekarz' },
    isPinned: false
  }

  it('renders type label, title and author', () => {
    render(<AnnouncementCard announcement={baseAnnouncement} />)

    expect(screen.getByText(/Ostrzeżenie/)).toBeInTheDocument()
    expect(screen.getByText('Nowe zasady')).toBeInTheDocument()
    expect(screen.getByText(/Bibliotekarz/)).toBeInTheDocument()
  })

  it('shows pinned badge when announcement is pinned', () => {
    render(<AnnouncementCard announcement={{ ...baseAnnouncement, isPinned: true }} />)

    expect(screen.getByText(/Przypięte/)).toBeInTheDocument()
  })

  it('truncates long content to 200 characters', () => {
    const longContent = 'a'.repeat(250)
    render(<AnnouncementCard announcement={{ ...baseAnnouncement, content: longContent }} />)

    expect(screen.getByText(/\.\.\.$/)).toBeInTheDocument()
  })

  it('invokes onClick handler when card is clicked', () => {
    const onClick = vi.fn()
    render(<AnnouncementCard announcement={baseAnnouncement} onClick={onClick} />)

    fireEvent.click(screen.getByText('Nowe zasady'))
    expect(onClick).toHaveBeenCalledTimes(1)
  })
})

