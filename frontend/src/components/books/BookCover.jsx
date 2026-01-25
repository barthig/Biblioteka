import React, { useState } from 'react'

/**
 * Book cover image with fallback placeholder on error
 */
export default function BookCover({ src, title, className = '' }) {
  const [hasError, setHasError] = useState(false)

  if (!src || hasError) {
    return (
      <div className={`book-cover-placeholder ${className}`.trim()} aria-hidden="true">
        {(title || '?').slice(0, 1)}
      </div>
    )
  }

  return (
    <img
      src={src}
      alt={`Okładka: ${title}`}
      loading="lazy"
      className={className}
      onError={() => setHasError(true)}
    />
  )
}
