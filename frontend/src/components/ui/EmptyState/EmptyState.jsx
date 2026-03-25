import React from 'react'
import PropTypes from 'prop-types'
import { Link } from 'react-router-dom'
import './EmptyState.css'

export default function EmptyState({
  icon,
  title,
  description,
  action,
  actions = [],
  variant = 'default',
  size = 'md',
  className = '',
  children,
  ...props
}) {
  const allActions = action ? [action, ...actions] : actions
  const classes = [
    'empty-state',
    `empty-state--${variant}`,
    `empty-state--${size}`,
    className,
  ].filter(Boolean).join(' ')

  return (
    <div className={classes} {...props}>
      {icon && (
        <div className="empty-state__icon">
          {typeof icon === 'string' ? <span>{icon}</span> : icon}
        </div>
      )}

      {title && <h3 className="empty-state__title">{title}</h3>}
      {description && <p className="empty-state__description">{description}</p>}
      {children && <div className="empty-state__content">{children}</div>}

      {allActions.length > 0 && (
        <div className="empty-state__actions">
          {allActions.map((act, index) => {
            const buttonClass = `empty-state__action-btn empty-state__action-btn--${act.variant || (index === 0 ? 'primary' : 'secondary')}`

            if (act.href) {
              return (
                <Link key={index} to={act.href} className={buttonClass}>
                  {act.icon && <span className="empty-state__action-icon">{act.icon}</span>}
                  {act.label}
                </Link>
              )
            }

            return (
              <button
                key={index}
                className={buttonClass}
                onClick={act.onClick}
                disabled={act.disabled}
              >
                {act.icon && <span className="empty-state__action-icon">{act.icon}</span>}
                {act.label}
              </button>
            )
          })}
        </div>
      )}
    </div>
  )
}

EmptyState.propTypes = {
  icon: PropTypes.node,
  title: PropTypes.string,
  description: PropTypes.string,
  action: PropTypes.shape({
    label: PropTypes.string.isRequired,
    href: PropTypes.string,
    onClick: PropTypes.func,
    variant: PropTypes.oneOf(['primary', 'secondary', 'ghost']),
    icon: PropTypes.node,
    disabled: PropTypes.bool,
  }),
  actions: PropTypes.arrayOf(PropTypes.shape({
    label: PropTypes.string.isRequired,
    href: PropTypes.string,
    onClick: PropTypes.func,
    variant: PropTypes.oneOf(['primary', 'secondary', 'ghost']),
    icon: PropTypes.node,
    disabled: PropTypes.bool,
  })),
  variant: PropTypes.oneOf(['default', 'card', 'inline']),
  size: PropTypes.oneOf(['sm', 'md', 'lg']),
  className: PropTypes.string,
  children: PropTypes.node,
}

export function NoDataEmptyState(props) {
  return (
    <EmptyState
      icon="📭"
      title="Brak danych"
      description="Nie znaleziono żadnych wyników."
      {...props}
    />
  )
}

export function NoSearchResultsEmptyState({ query, onClear, ...props }) {
  return (
    <EmptyState
      icon="🔍"
      title="Brak wyników"
      description={query ? `Nie znaleziono wyników dla "${query}".` : 'Nie znaleziono wyników dla podanych kryteriów.'}
      action={onClear ? { label: 'Wyczyść filtry', onClick: onClear, variant: 'secondary' } : undefined}
      {...props}
    />
  )
}

export function ErrorEmptyState({ message, onRetry, ...props }) {
  return (
    <EmptyState
      icon="⚠️"
      title="Wystąpił błąd"
      description={message || 'Nie udało się załadować danych. Spróbuj ponownie.'}
      variant="card"
      action={onRetry ? { label: 'Spróbuj ponownie', onClick: onRetry } : undefined}
      {...props}
    />
  )
}

export function NoLoansEmptyState(props) {
  return (
    <EmptyState
      icon="📚"
      title="Brak wypożyczeń"
      description="Nie masz żadnych aktywnych wypożyczeń."
      action={{ label: 'Przeglądaj katalog', href: '/books' }}
      {...props}
    />
  )
}

export function NoFavoritesEmptyState(props) {
  return (
    <EmptyState
      icon="❤️"
      title="Brak ulubionych"
      description="Nie dodałeś jeszcze żadnych książek do ulubionych."
      action={{ label: 'Odkryj książki', href: '/books' }}
      {...props}
    />
  )
}

export function NoReservationsEmptyState(props) {
  return (
    <EmptyState
      icon="📋"
      title="Brak rezerwacji"
      description="Nie masz żadnych aktywnych rezerwacji."
      action={{ label: 'Szukaj książek', href: '/books' }}
      {...props}
    />
  )
}
