import React from 'react'
import { FaExclamationTriangle, FaTimes } from 'react-icons/fa'

export default function ErrorMessage({ error, onDismiss }) {
  if (!error) return null

  const message = typeof error === 'string' ? error : error.message || 'Wystąpił nieoczekiwany błąd'

  return (
    <div className="error-message">
      <div className="error-icon">
        <FaExclamationTriangle />
      </div>
      <div className="error-content">
        <p>{message}</p>
      </div>
      {onDismiss && (
        <button className="error-dismiss" onClick={onDismiss} aria-label="Zamknij">
          <FaTimes />
        </button>
      )}
    </div>
  )
}
