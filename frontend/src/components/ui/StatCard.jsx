import React from 'react'

export default function StatCard({
  icon: Icon = null,
  value = null,
  label = null,
  title = null,
  subtitle = null,
  trend = null,
  color = 'primary',
  valueClassName = '',
  children = undefined,
}) {
  const heading = title || label
  const content = children ?? value

  return (
    <div className={`stat-card stat-card-${color}`}>
      {Icon && <Icon className="stat-icon" />}
      <div className="stat-content">
        <div className={`stat-value ${valueClassName}`.trim()}>
          {content}
          {trend && (
            <span className={`stat-trend ${trend > 0 ? 'trend-up' : 'trend-down'}`}>
              {trend > 0 ? '�' : '�'} {Math.abs(trend)}%
            </span>
          )}
        </div>
        {heading && <div className="stat-label">{heading}</div>}
        {subtitle && <div className="stat-subtitle">{subtitle}</div>}
      </div>
    </div>
  )
}
