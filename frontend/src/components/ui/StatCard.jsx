import React from 'react'

export default function StatCard({
  icon: Icon,
  value,
  label,
  title,
  subtitle,
  trend,
  color = 'primary',
  valueClassName = '',
  children,
}) {
  const heading = title || label
  const content = typeof children !== 'undefined' ? children : value

  return (
    <div className={`stat-card stat-card-${color}`}>
      {Icon && <Icon className="stat-icon" />}
      <div className="stat-content">
        <div className={`stat-value ${valueClassName}`.trim()}>
          {content}
          {trend && (
            <span className={`stat-trend ${trend > 0 ? 'trend-up' : 'trend-down'}`}>
              {trend > 0 ? '↑' : '↓'} {Math.abs(trend)}%
            </span>
          )}
        </div>
        {heading && <div className="stat-label">{heading}</div>}
        {subtitle && <div className="stat-subtitle">{subtitle}</div>}
      </div>
    </div>
  )
}
