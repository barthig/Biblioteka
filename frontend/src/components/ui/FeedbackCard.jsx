import React from 'react'

export default function FeedbackCard({ variant = 'info', children }) {
  const className = variant === 'success'
    ? 'success'
    : variant === 'error'
      ? 'error'
      : 'support-copy'

  return (
    <div className="surface-card">
      <p className={className}>{children}</p>
    </div>
  )
}
