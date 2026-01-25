import React from 'react'
import PropTypes from 'prop-types'
import { Link, useLocation } from 'react-router-dom'
import './Breadcrumbs.css'

/**
 * Breadcrumbs component for navigation
 * 
 * @example
 * <Breadcrumbs
 *   items={[
 *     { label: 'Home', href: '/' },
 *     { label: 'Katalog', href: '/books' },
 *     { label: 'Fantastyka' }
 *   ]}
 * />
 */
export default function Breadcrumbs({
  items = [],
  separator = '›',
  homeIcon = '🏠',
  showHome = true,
  maxItems = 0,
  className = '',
  ...props
}) {
  if (items.length === 0) return null

  // Add home if needed
  const allItems = showHome && items[0]?.href !== '/'
    ? [{ label: 'Strona główna', href: '/', icon: homeIcon }, ...items]
    : items

  // Truncate middle items if maxItems is set
  let displayItems = allItems
  if (maxItems > 0 && allItems.length > maxItems) {
    const start = allItems.slice(0, 1)
    const end = allItems.slice(-(maxItems - 2))
    displayItems = [...start, { label: '...', isEllipsis: true }, ...end]
  }

  return (
    <nav className={`breadcrumbs ${className}`} aria-label="Breadcrumb" {...props}>
      <ol className="breadcrumbs__list">
        {displayItems.map((item, index) => {
          const isLast = index === displayItems.length - 1
          const isEllipsis = item.isEllipsis

          return (
            <li key={index} className="breadcrumbs__item">
              {!isLast && !isEllipsis && item.href ? (
                <Link to={item.href} className="breadcrumbs__link">
                  {item.icon && <span className="breadcrumbs__icon">{item.icon}</span>}
                  <span className="breadcrumbs__label">{item.label}</span>
                </Link>
              ) : (
                <span 
                  className={`breadcrumbs__current ${isEllipsis ? 'breadcrumbs__ellipsis' : ''}`}
                  aria-current={isLast ? 'page' : undefined}
                >
                  {item.icon && <span className="breadcrumbs__icon">{item.icon}</span>}
                  <span className="breadcrumbs__label">{item.label}</span>
                </span>
              )}
              
              {!isLast && (
                <span className="breadcrumbs__separator" aria-hidden="true">
                  {separator}
                </span>
              )}
            </li>
          )
        })}
      </ol>
    </nav>
  )
}

Breadcrumbs.propTypes = {
  items: PropTypes.arrayOf(PropTypes.shape({
    label: PropTypes.string.isRequired,
    href: PropTypes.string,
    icon: PropTypes.node
  })).isRequired,
  separator: PropTypes.node,
  homeIcon: PropTypes.node,
  showHome: PropTypes.bool,
  maxItems: PropTypes.number,
  className: PropTypes.string
}

/**
 * useBreadcrumbs - Hook for auto-generating breadcrumbs from URL
 */
export function useBreadcrumbs(customLabels = {}) {
  const location = useLocation()
  
  const defaultLabels = {
    '': 'Strona główna',
    'books': 'Katalog',
    'my-loans': 'Moje wypożyczenia',
    'favorites': 'Ulubione',
    'reservations': 'Rezerwacje',
    'profile': 'Profil',
    'settings': 'Ustawienia',
    'admin': 'Panel administratora',
    'users': 'Użytkownicy',
    'loans': 'Wypożyczenia',
    'reports': 'Raporty',
    ...customLabels
  }

  const pathnames = location.pathname.split('/').filter(x => x)

  const items = pathnames.map((value, index) => {
    const href = `/${pathnames.slice(0, index + 1).join('/')}`
    const label = defaultLabels[value] || value.charAt(0).toUpperCase() + value.slice(1)
    
    return { label, href }
  })

  // Last item shouldn't have href (current page)
  if (items.length > 0) {
    items[items.length - 1] = { label: items[items.length - 1].label }
  }

  return items
}
