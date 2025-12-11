import React from 'react'
import { FaSpinner } from 'react-icons/fa'

export default function LoadingSpinner({ size = 'medium', message = 'Ładowanie...' }) {
  const sizeClass = {
    small: 'spinner-small',
    medium: 'spinner-medium',
    large: 'spinner-large'
  }[size] || 'spinner-medium'

  return (
    <div className="loading-spinner">
      <FaSpinner className={`spinner ${sizeClass}`} />
      {message && <p className="spinner-message">{message}</p>}
    </div>
  )
}
