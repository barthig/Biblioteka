import React from 'react'

export default function StatCard({ title, value = null, subtitle, children, valueClassName = '' }) {
  return (
    <div className="surface-card stat-card">
      <h3>{title}</h3>
      {value !== null && value !== undefined && (
        <strong className={valueClassName}>{value}</strong>
      )}
      {subtitle && <span>{subtitle}</span>}
      {children}
    </div>
  )
}
