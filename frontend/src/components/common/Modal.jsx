import React from 'react'

export default function Modal({ isOpen, onClose, title, children, footer }) {
  if (!isOpen) return null

  const titleId = 'modal-title-' + Math.random().toString(36).substr(2, 9)

  return (
    <div className="modal-overlay" onClick={onClose} role="presentation">
      <div 
        className="modal-content" 
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby={titleId}
      >
        <div className="modal-header">
          <h2 id={titleId}>{title}</h2>
          <button className="modal-close" onClick={onClose} aria-label="Zamknij">
            ×
          </button>
        </div>
        <div className="modal-body">
          {children}
        </div>
        {footer && (
          <div className="modal-footer">
            {footer}
          </div>
        )}
      </div>
    </div>
  )
}
