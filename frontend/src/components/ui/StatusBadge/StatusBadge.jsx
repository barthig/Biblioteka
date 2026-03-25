import React from 'react'
import PropTypes from 'prop-types'
import './StatusBadge.css'

const STATUS_CONFIG = {
  available: { label: 'Dostępne', variant: 'success' },
  borrowed: { label: 'Wypożyczone', variant: 'info' },
  loaned: { label: 'Wypożyczone', variant: 'info' },
  overdue: { label: 'Przeterminowane', variant: 'danger' },
  returned: { label: 'Zwrócone', variant: 'neutral' },

  reserved: { label: 'Zarezerwowane', variant: 'warning' },
  pending: { label: 'Oczekujące', variant: 'warning' },
  ready: { label: 'Gotowe do odbioru', variant: 'success' },
  expired: { label: 'Wygasłe', variant: 'danger' },
  cancelled: { label: 'Anulowane', variant: 'neutral' },
  fulfilled: { label: 'Zrealizowane', variant: 'success' },

  damaged: { label: 'Uszkodzone', variant: 'warning' },
  lost: { label: 'Zgubione', variant: 'danger' },
  maintenance: { label: 'W naprawie', variant: 'warning' },

  active: { label: 'Aktywny', variant: 'success' },
  inactive: { label: 'Nieaktywny', variant: 'neutral' },
  blocked: { label: 'Zablokowany', variant: 'danger' },
  suspended: { label: 'Zawieszony', variant: 'warning' },

  paid: { label: 'Opłacone', variant: 'success' },
  unpaid: { label: 'Nieopłacone', variant: 'danger' },
  partial: { label: 'Częściowo opłacone', variant: 'warning' },

  new: { label: 'Nowe', variant: 'info' },
  published: { label: 'Opublikowane', variant: 'success' },
  draft: { label: 'Szkic', variant: 'neutral' },
  archived: { label: 'Zarchiwizowane', variant: 'neutral' },

  ACTIVE: { label: 'Aktywne', variant: 'info' },
  PREPARED: { label: 'Przygotowane', variant: 'success' },
  CANCELLED: { label: 'Anulowane', variant: 'neutral' },
  FULFILLED: { label: 'Zrealizowane', variant: 'success' },
  EXPIRED: { label: 'Wygasłe', variant: 'danger' }
}

export default function StatusBadge({
  status = null,
  variant = null,
  label = null,
  size = 'md',
  pulse = false,
  outline = false,
  icon = null,
  className = '',
  ...props
}) {
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
