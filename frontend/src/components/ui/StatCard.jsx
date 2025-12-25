import React from 'react'

export default function StatCard({ title, value, subtitle, children }) {
  return (
    <div className="surface-card stat-card">
      <h3>{title}</h3>
      <strong>{value}</strong>
      {subtitle && <span>{subtitle}</span>}
      {children}
    </div>
  )
}
