import React from 'react'
import PropTypes from 'prop-types'
import { Link } from 'react-router-dom'
import { StatusBadge } from '../ui/StatusBadge'
import StarRating from './StarRating'
import BookCover from './BookCover'
import './BookCard.css'

/**
 * BookCard - Universal book card component with multiple variants
 * 
 * @example
 * <BookCard
 *   book={book}
 *   variant="grid"
 *   showRating
 *   showAvailability
 *   actions={['borrow', 'reserve', 'favorite']}
 * />
 */
export default function BookCard({
  book,
  variant = 'grid',
  showRating = true,
  showAvailability = true,
  showDescription = false,
  showCategories = false,
  linkable = true,
  actions = [],
  onBorrow,
  onReserve,
  onFavorite,
  onRemoveFavorite,
  isFavorite = false,
  className = '',
  ...props
}) {
  const {
    id,
    title,
    author,
    coverUrl,
    averageRating,
    reviewCount,
    availableCopies,
    totalCopies,
    categories,
    description
    // isbn - reserved for future use
  } = book

  const authorName = typeof author === 'string' ? author : author?.name || 'Nieznany autor'
  
  const getAvailabilityStatus = () => {
    if (availableCopies === undefined) return null
    if (availableCopies > 0) return 'available'
    return 'unavailable'
  }

  const getAvailabilityText = () => {
    if (availableCopies === undefined) return ''
    if (availableCopies > 0) {
      return `${availableCopies} z ${totalCopies} dostępnych`
    }
    return 'Brak dostępnych egzemplarzy'
  }

  const handleAction = (action, e) => {
    e.preventDefault()
    e.stopPropagation()
    
    switch (action) {
      case 'borrow':
        onBorrow?.(book)
        break
      case 'reserve':
        onReserve?.(book)
        break
      case 'favorite':
        isFavorite ? onRemoveFavorite?.(book) : onFavorite?.(book)
        break
      default:
        break
    }
  }

  const classes = [
    'book-card',
    `book-card--${variant}`,
    className
  ].filter(Boolean).join(' ')

  const content = (
    <>
      <div className="book-card__cover">
        <BookCover 
          src={coverUrl} 
          alt={title}
          size={variant === 'compact' ? 'sm' : 'md'}
        />
        {showAvailability && availableCopies !== undefined && (
          <span className={`book-card__availability-badge book-card__availability-badge--${getAvailabilityStatus()}`}>
            {availableCopies > 0 ? availableCopies : '0'}
          </span>
        )}
      </div>

      <div className="book-card__content">
        <h3 className="book-card__title" title={title}>
          {title}
        </h3>
        
        <p className="book-card__author" title={authorName}>
          {authorName}
        </p>

        {showRating && averageRating !== undefined && (
          <div className="book-card__rating">
            <StarRating rating={averageRating} readonly size="sm" />
            {reviewCount !== undefined && (
              <span className="book-card__review-count">({reviewCount})</span>
            )}
          </div>
        )}

        {showCategories && categories?.length > 0 && (
          <div className="book-card__categories">
            {categories.slice(0, 3).map((cat, index) => (
              <span key={index} className="book-card__category">
                {typeof cat === 'string' ? cat : cat.name}
              </span>
            ))}
            {categories.length > 3 && (
              <span className="book-card__category book-card__category--more">
                +{categories.length - 3}
              </span>
            )}
          </div>
        )}

        {showDescription && description && (
          <p className="book-card__description">
            {description.length > 150 ? description.substring(0, 150) + '...' : description}
          </p>
        )}

        {showAvailability && variant !== 'compact' && (
          <div className="book-card__availability">
            <StatusBadge 
              status={getAvailabilityStatus()} 
              size="sm"
            />
            <span className="book-card__availability-text">
              {getAvailabilityText()}
            </span>
          </div>
        )}

        {actions.length > 0 && (
          <div className="book-card__actions">
            {actions.includes('borrow') && availableCopies > 0 && (
              <button 
                className="book-card__action book-card__action--primary"
                onClick={(e) => handleAction('borrow', e)}
              >
                Wypożycz
              </button>
            )}
            {actions.includes('reserve') && availableCopies === 0 && (
              <button 
                className="book-card__action book-card__action--secondary"
                onClick={(e) => handleAction('reserve', e)}
              >
                Zarezerwuj
              </button>
            )}
            {actions.includes('favorite') && (
              <button 
                className={`book-card__action book-card__action--icon ${isFavorite ? 'book-card__action--active' : ''}`}
                onClick={(e) => handleAction('favorite', e)}
                aria-label={isFavorite ? 'Usuń z ulubionych' : 'Dodaj do ulubionych'}
              >
                {isFavorite ? '❤️' : '🤍'}
              </button>
            )}
          </div>
        )}
      </div>
    </>
  )

  if (linkable) {
    return (
      <Link to={`/books/${id}`} className={classes} {...props}>
        {content}
      </Link>
    )
  }

  return (
    <div className={classes} {...props}>
      {content}
    </div>
  )
}

BookCard.propTypes = {
  book: PropTypes.shape({
    id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
    title: PropTypes.string.isRequired,
    author: PropTypes.oneOfType([
      PropTypes.string,
      PropTypes.shape({ name: PropTypes.string })
    ]),
    coverUrl: PropTypes.string,
    averageRating: PropTypes.number,
    reviewCount: PropTypes.number,
    availableCopies: PropTypes.number,
    totalCopies: PropTypes.number,
    categories: PropTypes.array,
    description: PropTypes.string,
    isbn: PropTypes.string
  }).isRequired,
  variant: PropTypes.oneOf(['grid', 'list', 'compact']),
  showRating: PropTypes.bool,
  showAvailability: PropTypes.bool,
  showDescription: PropTypes.bool,
  showCategories: PropTypes.bool,
  linkable: PropTypes.bool,
  actions: PropTypes.arrayOf(PropTypes.oneOf(['borrow', 'reserve', 'favorite'])),
  onBorrow: PropTypes.func,
  onReserve: PropTypes.func,
  onFavorite: PropTypes.func,
  onRemoveFavorite: PropTypes.func,
  isFavorite: PropTypes.bool,
  className: PropTypes.string
}

/**
 * BookCardGrid - Grid layout for book cards
 */
export function BookCardGrid({ books, columns = { sm: 2, md: 3, lg: 4 }, ...cardProps }) {
  const style = {
    '--grid-cols-sm': columns.sm || 2,
    '--grid-cols-md': columns.md || 3,
    '--grid-cols-lg': columns.lg || 4
  }

  return (
    <div className="book-card-grid" style={style}>
      {books.map(book => (
        <BookCard key={book.id} book={book} variant="grid" {...cardProps} />
      ))}
    </div>
  )
}

/**
 * BookCardList - List layout for book cards
 */
export function BookCardList({ books, ...cardProps }) {
  return (
    <div className="book-card-list">
      {books.map(book => (
        <BookCard key={book.id} book={book} variant="list" {...cardProps} />
      ))}
    </div>
  )
}
