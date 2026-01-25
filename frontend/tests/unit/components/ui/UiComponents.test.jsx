import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import FeedbackCard from '../../../../src/components/ui/FeedbackCard'
import StatGrid from '../../../../src/components/ui/StatGrid'
import SectionCard from '../../../../src/components/ui/SectionCard'
import PageHeader from '../../../../src/components/ui/PageHeader'
import { Skeleton, TableRowSkeleton } from '../../../../src/components/ui/Skeleton'

describe('ui components', () => {
  it('renders FeedbackCard content', () => {
    render(<FeedbackCard variant="error">Oops</FeedbackCard>)
    expect(screen.getByText('Oops')).toBeInTheDocument()
  })

  it('renders StatGrid children', () => {
    render(
      <StatGrid>
        <div>Child</div>
      </StatGrid>
    )
    expect(screen.getByText('Child')).toBeInTheDocument()
  })

  it('renders SectionCard title and actions', () => {
    render(
      <SectionCard title="Title" actions={<button>Action</button>}>
        <div>Body</div>
      </SectionCard>
    )
    expect(screen.getByText('Title')).toBeInTheDocument()
    expect(screen.getByText('Action')).toBeInTheDocument()
  })

  it('renders PageHeader title', () => {
    render(<PageHeader title="Header" subtitle="Sub" />)
    expect(screen.getByText('Header')).toBeInTheDocument()
  })

  it('renders Skeleton with aria label', () => {
    render(<Skeleton />)
    expect(screen.getByLabelText('Loading...')).toBeInTheDocument()
  })

  it('renders TableRowSkeleton columns', () => {
    render(
      <table>
        <tbody>
          <TableRowSkeleton columns={2} />
        </tbody>
      </table>
    )
    expect(screen.getAllByLabelText('Loading...')).toHaveLength(2)
  })
})

