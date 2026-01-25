import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import StatCard from '../../../src/StatCard'

const Icon = () => <svg data-testid="icon" />

describe('StatCard', () => {
  it('renders value, label, and icon', () => {
    render(<StatCard icon={Icon} value="10" label="Loans" />)
    expect(screen.getByText('10')).toBeInTheDocument()
    expect(screen.getByText('Loans')).toBeInTheDocument()
    expect(screen.getByTestId('icon')).toBeInTheDocument()
  })

  it('renders trend indicator', () => {
    render(<StatCard value="8" label="Users" trend={-5} />)
    expect(screen.getByText(/5%/)).toBeInTheDocument()
    expect(document.querySelector('.trend-down')).toBeTruthy()
  })
})

