import React, { useState } from 'react'

export function StarRating({ rating, onRate, readonly = false, size = 'medium' }) {
  const [hoverRating, setHoverRating] = useState(0)
  
  const sizeClass = size === 'large' ? 'star-rating--large' : size === 'small' ? 'star-rating--small' : ''
  
  return (
    <div className={`star-rating ${sizeClass}`}>
      {[1, 2, 3, 4, 5].map(star => (
        <button
          key={star}
          type="button"
          className={`star ${star <= (hoverRating || rating) ? 'star--filled' : ''}`}
          onClick={() => !readonly && onRate?.(star)}
          onMouseEnter={() => !readonly && setHoverRating(star)}
          onMouseLeave={() => !readonly && setHoverRating(0)}
          disabled={readonly}
          aria-label={`${star} gwiazdek`}
        >
          ★
        </button>
      ))}
    </div>
  )
}

export function RatingDisplay({ averageRating, ratingCount }) {
  if (!averageRating && !ratingCount) {
    return <span className="rating-display rating-display--empty">Brak ocen</span>
  }
  
  return (
    <div className="rating-display">
      <StarRating rating={averageRating || 0} readonly size="small" />
      <span className="rating-display__score">{averageRating?.toFixed(1) || '—'}</span>
      <span className="rating-display__count">({ratingCount || 0})</span>
    </div>
  )
}
