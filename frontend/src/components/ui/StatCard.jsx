import React from 'react'

export default function StatCard({ title, value = null, subtitle, children, valueClassName = '', alert = false }) {
  const titleId = `stat-${title.toLowerCase().replace(/\s+/g, '-')}`
  
  return (
    <div 
      className="surface-card stat-card" 
      role="region" 
      aria-labelledby={titleId}
      aria-live={alert ? "polite" : undefined}
    >
      <h3 id={titleId}>{title}</h3>
      {value !== null && value !== undefined && (
        <strong className={valueClassName} aria-label={`${title}: ${value}`}>{value}</strong>
      )}
      {subtitle && <span className="stat-card__subtitle">{subtitle}</span>}
      {children}
    </div>
  )
}
