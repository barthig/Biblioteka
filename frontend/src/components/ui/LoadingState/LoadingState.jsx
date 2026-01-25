import React from 'react'
import PropTypes from 'prop-types'
import './LoadingState.css'

/**
 * LoadingState component for displaying loading indicators
 * 
 * @example
 * <LoadingState />
 * <LoadingState variant="spinner" size="lg" />
 * <LoadingState variant="skeleton" rows={5} />
 */
export default function LoadingState({
  variant = 'spinner',
  size = 'md',
  text,
  overlay = false,
  className = '',
  rows = 3,
  ...props
}) {
  const classes = [
    'loading-state',
    `loading-state--${variant}`,
    `loading-state--${size}`,
    overlay && 'loading-state--overlay',
    className
  ].filter(Boolean).join(' ')

  if (variant === 'skeleton') {
    return (
      <div className={classes} {...props}>
        {Array.from({ length: rows }).map((_, index) => (
          <div key={index} className="loading-state__skeleton-row">
            <div className="loading-state__skeleton-line loading-state__skeleton-line--full" />
          </div>
        ))}
      </div>
    )
  }

  if (variant === 'skeleton-card') {
    return (
      <div className={classes} {...props}>
        <div className="loading-state__skeleton-card">
          <div className="loading-state__skeleton-image" />
          <div className="loading-state__skeleton-content">
            <div className="loading-state__skeleton-line loading-state__skeleton-line--title" />
            <div className="loading-state__skeleton-line loading-state__skeleton-line--subtitle" />
            <div className="loading-state__skeleton-line loading-state__skeleton-line--text" />
          </div>
        </div>
      </div>
    )
  }

  if (variant === 'skeleton-table') {
    return (
      <div className={classes} {...props}>
        <div className="loading-state__skeleton-table-header">
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="loading-state__skeleton-line loading-state__skeleton-line--header" />
          ))}
        </div>
        {Array.from({ length: rows }).map((_, rowIndex) => (
          <div key={rowIndex} className="loading-state__skeleton-table-row">
            {Array.from({ length: 5 }).map((_, colIndex) => (
              <div key={colIndex} className="loading-state__skeleton-line loading-state__skeleton-line--cell" />
            ))}
          </div>
        ))}
      </div>
    )
  }

  if (variant === 'dots') {
    return (
      <div className={classes} {...props}>
        <div className="loading-state__dots">
          <span className="loading-state__dot" />
          <span className="loading-state__dot" />
          <span className="loading-state__dot" />
        </div>
        {text && <span className="loading-state__text">{text}</span>}
      </div>
    )
  }

  if (variant === 'pulse') {
    return (
      <div className={classes} {...props}>
        <div className="loading-state__pulse" />
        {text && <span className="loading-state__text">{text}</span>}
      </div>
    )
  }

  // Default: spinner
  return (
    <div className={classes} {...props}>
      <div className="loading-state__spinner">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle 
            cx="12" cy="12" r="10" 
            stroke="currentColor" 
            strokeWidth="3"
            strokeLinecap="round"
            className="loading-state__spinner-track"
          />
          <path 
            d="M12 2a10 10 0 0 1 10 10" 
            stroke="currentColor" 
            strokeWidth="3"
            strokeLinecap="round"
            className="loading-state__spinner-arc"
          />
        </svg>
      </div>
      {text && <span className="loading-state__text">{text}</span>}
    </div>
  )
}

LoadingState.propTypes = {
  variant: PropTypes.oneOf(['spinner', 'dots', 'pulse', 'skeleton', 'skeleton-card', 'skeleton-table']),
  size: PropTypes.oneOf(['sm', 'md', 'lg', 'xl']),
  text: PropTypes.string,
  overlay: PropTypes.bool,
  className: PropTypes.string,
  rows: PropTypes.number
}

/**
 * Inline loading indicator
 */
export function InlineLoader({ className = '', ...props }) {
  return (
    <LoadingState 
      variant="dots" 
      size="sm" 
      className={`loading-state--inline ${className}`}
      {...props}
    />
  )
}

/**
 * Button loading indicator
 */
export function ButtonLoader({ className = '', ...props }) {
  return (
    <LoadingState 
      variant="spinner" 
      size="sm" 
      className={`loading-state--button ${className}`}
      {...props}
    />
  )
}

/**
 * Full page loading overlay
 */
export function PageLoader({ text = 'Ładowanie...', ...props }) {
  return (
    <LoadingState 
      variant="spinner" 
      size="xl" 
      overlay
      text={text}
      {...props}
    />
  )
}

/**
 * Table skeleton loader
 */
export function TableLoader({ rows = 5, ...props }) {
  return (
    <LoadingState 
      variant="skeleton-table" 
      rows={rows}
      {...props}
    />
  )
}

/**
 * Card skeleton loader
 */
export function CardLoader(props) {
  return (
    <LoadingState 
      variant="skeleton-card"
      {...props}
    />
  )
}
