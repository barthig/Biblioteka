import React from 'react'
import { FaCheckCircle, FaTimes } from 'react-icons/fa'

export default function SuccessMessage({ message, onDismiss }) {
  if (!message) return null

  return (
    <div className="success-message">
      <div className="success-icon">
        <FaCheckCircle />
      </div>
      <div className="success-content">
        <p>{message}</p>
      </div>
      {onDismiss && (
        <button className="success-dismiss" onClick={onDismiss} aria-label="Zamknij">
          <FaTimes />
        </button>
      )}
    </div>
  )
}
