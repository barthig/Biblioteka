import React from 'react'
import PropTypes from 'prop-types'
import './StatusBadge.css'

/**
 * Universal StatusBadge component for displaying status indicators
 * 
 * @example
 * <StatusBadge status="available" />
 * <StatusBadge status="overdue" size="sm" />
 * <StatusBadge status="reserved" pulse />
 * <StatusBadge variant="success" label="Aktywne" />
 */

const STATUS_CONFIG = {
  // Loan statuses
  available: { label: 'Dostępne', variant: 'success' },
  borrowed: { label: 'Wypożyczone', variant: 'info' },
  loaned: { label: 'Wypożyczone', variant: 'info' },
  overdue: { label: 'Przeterminowane', variant: 'danger' },
  returned: { label: 'Zwrócone', variant: 'neutral' },
  
  // Reservation statuses
  reserved: { label: 'Zarezerwowane', variant: 'warning' },
  pending: { label: 'Oczekujące', variant: 'warning' },
  ready: { label: 'Gotowe do odbioru', variant: 'success' },
  expired: { label: 'Wygasłe', variant: 'danger' },
  cancelled: { label: 'Anulowane', variant: 'neutral' },
  fulfilled: { label: 'Zrealizowane', variant: 'success' },
  
  // Book copy statuses
  damaged: { label: 'Uszkodzone', variant: 'warning' },
  lost: { label: 'Zgubione', variant: 'danger' },
  maintenance: { label: 'W naprawie', variant: 'warning' },
  
  // User statuses
  active: { label: 'Aktywny', variant: 'success' },
  inactive: { label: 'Nieaktywny', variant: 'neutral' },
  blocked: { label: 'Zablokowany', variant: 'danger' },
  suspended: { label: 'Zawieszony', variant: 'warning' },
  
  // Payment statuses
  paid: { label: 'Opłacone', variant: 'success' },
  unpaid: { label: 'Nieopłacone', variant: 'danger' },
  partial: { label: 'Częściowo opłacone', variant: 'warning' },
  
  // General
  new: { label: 'Nowe', variant: 'info' },
  published: { label: 'Opublikowane', variant: 'success' },
  draft: { label: 'Szkic', variant: 'neutral' },
  archived: { label: 'Zarchiwizowane', variant: 'neutral' },
  
  // ACTIVE / PREPARED from Reservation entity
  ACTIVE: { label: 'Aktywne', variant: 'info' },
  PREPARED: { label: 'Przygotowane', variant: 'success' },
  CANCELLED: { label: 'Anulowane', variant: 'neutral' },
  FULFILLED: { label: 'Zrealizowane', variant: 'success' },
  EXPIRED: { label: 'Wygasłe', variant: 'danger' }
}

export default function StatusBadge({
  status,
  variant,
  label,
  size = 'md',
  pulse = false,
  outline = false,
  icon,
  className = '',
  ...props
}) {
  // Get config from status or use custom variant/label
  const config = status ? STATUS_CONFIG[status] || STATUS_CONFIG[status.toLowerCase()] : null
  const finalVariant = variant || config?.variant || 'neutral'
  const finalLabel = label || config?.label || status || 'Nieznany'

  const classes = [
    'status-badge',
    `status-badge--${finalVariant}`,
    `status-badge--${size}`,
    outline && 'status-badge--outline',
    pulse && 'status-badge--pulse',
    className
  ].filter(Boolean).join(' ')

  return (
    <span className={classes} {...props}>
      {pulse && <span className="status-badge__pulse-dot" />}
      {icon && <span className="status-badge__icon">{icon}</span>}
      <span className="status-badge__label">{finalLabel}</span>
    </span>
  )
}

StatusBadge.propTypes = {
  status: PropTypes.string,
  variant: PropTypes.oneOf(['success', 'warning', 'danger', 'info', 'neutral']),
  label: PropTypes.string,
  size: PropTypes.oneOf(['xs', 'sm', 'md', 'lg']),
  pulse: PropTypes.bool,
  outline: PropTypes.bool,
  icon: PropTypes.node,
  className: PropTypes.string
}

/**
 * Inline dot indicator for compact displays
 */
export function StatusDot({ status, variant, size = 'md', className = '', ...props }) {
  const config = status ? STATUS_CONFIG[status] || STATUS_CONFIG[status.toLowerCase()] : null
  const finalVariant = variant || config?.variant || 'neutral'

  const classes = [
    'status-dot',
    `status-dot--${finalVariant}`,
    `status-dot--${size}`,
    className
  ].filter(Boolean).join(' ')

  return <span className={classes} {...props} />
}

StatusDot.propTypes = {
  status: PropTypes.string,
  variant: PropTypes.oneOf(['success', 'warning', 'danger', 'info', 'neutral']),
  size: PropTypes.oneOf(['xs', 'sm', 'md', 'lg']),
  className: PropTypes.string
}
