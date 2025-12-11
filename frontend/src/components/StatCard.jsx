import React from 'react'

export default function StatCard({ icon: Icon, value, label, trend, color = 'primary' }) {
  return (
    <div className={`stat-card stat-card-${color}`}>
      {Icon && <Icon className="stat-icon" />}
      <div className="stat-content">
        <div className="stat-value">
          {value}
          {trend && (
            <span className={`stat-trend ${trend > 0 ? 'trend-up' : 'trend-down'}`}>
              {trend > 0 ? '↑' : '↓'} {Math.abs(trend)}%
            </span>
          )}
        </div>
        <div className="stat-label">{label}</div>
      </div>
    </div>
  )
}
