import React, { useState } from 'react'

/**
 * Book cover image with fallback placeholder on error
 */
export default function BookCover({ src = '', title = '', size = '', className = '' }) {
  const [hasError, setHasError] = useState(false)
  const mergedClassName = `book-cover ${size ? `book-cover--${size}` : ''} ${className}`.trim()

  if (!src || hasError) {
    return (
      <div className={`book-cover-placeholder ${mergedClassName}`.trim()} aria-hidden="true">
        {(title || '?').slice(0, 1)}
      </div>
    )
  }

  return (
    <img
      src={src}
      alt={`Okładka: ${title}`}
      loading="lazy"
      className={mergedClassName}
      onError={() => setHasError(true)}
    />
  )
}
