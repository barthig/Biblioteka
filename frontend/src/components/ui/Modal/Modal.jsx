import React, { useEffect, useRef, useCallback } from 'react'
import { createPortal } from 'react-dom'
import PropTypes from 'prop-types'
import './Modal.css'

/**
 * Universal Modal component with variants for different use cases
 * 
 * @example
 * // Confirmation modal
 * <Modal
 *   isOpen={showModal}
 *   onClose={handleClose}
 *   title="Potwierdź akcję"
 *   variant="confirmation"
 *   actions={[
 *     { label: 'Anuluj', variant: 'secondary', onClick: handleCancel },
 *     { label: 'Usuń', variant: 'danger', onClick: handleDelete }
 *   ]}
 * >
 *   <p>Czy na pewno chcesz usunąć tę książkę?</p>
 * </Modal>
 * 
 * // Form modal
 * <Modal
 *   isOpen={showForm}
 *   onClose={handleClose}
 *   title="Dodaj książkę"
 *   variant="form"
 *   size="lg"
 * >
 *   <BookForm onSubmit={handleSubmit} />
 * </Modal>
 */
export default function Modal({
  isOpen = false,
  onClose,
  title,
  children,
  variant = 'default',
  size = 'md',
  actions = [],
  closeOnOverlayClick = true,
  closeOnEscape = true,
  showCloseButton = true,
  className = '',
  footer,
  icon,
  loading = false,
  ...props
}) {
  const modalRef = useRef(null)
  const previousActiveElement = useRef(null)

  // Handle escape key
  const handleKeyDown = useCallback((e) => {
    if (e.key === 'Escape' && closeOnEscape && !loading) {
      onClose?.()
    }
  }, [closeOnEscape, onClose, loading])

  // Handle overlay click
  const handleOverlayClick = useCallback((e) => {
    if (e.target === e.currentTarget && closeOnOverlayClick && !loading) {
      onClose?.()
    }
  }, [closeOnOverlayClick, onClose, loading])

  // Focus management
  useEffect(() => {
    if (isOpen) {
      previousActiveElement.current = document.activeElement
      modalRef.current?.focus()
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = ''
      previousActiveElement.current?.focus()
    }

    return () => {
      document.body.style.overflow = ''
    }
  }, [isOpen])

  // Keyboard event listener
  useEffect(() => {
    if (isOpen) {
      document.addEventListener('keydown', handleKeyDown)
      return () => document.removeEventListener('keydown', handleKeyDown)
    }
  }, [isOpen, handleKeyDown])

  if (!isOpen) return null

  const sizeClass = `modal--${size}`
  const variantClass = `modal--${variant}`

  const modalContent = (
    <div 
      className="modal__overlay"
      onClick={handleOverlayClick}
      aria-modal="true"
      role="dialog"
      aria-labelledby="modal-title"
    >
      <div 
        ref={modalRef}
        className={`modal ${sizeClass} ${variantClass} ${className}`.trim()}
        tabIndex={-1}
        {...props}
      >
        {/* Header */}
        <div className="modal__header">
          {icon && <span className="modal__icon">{icon}</span>}
          {title && <h2 id="modal-title" className="modal__title">{title}</h2>}
          {showCloseButton && (
            <button 
              className="modal__close-btn"
              onClick={onClose}
              disabled={loading}
              aria-label="Zamknij"
            >
              ×
            </button>
          )}
        </div>

        {/* Body */}
        <div className="modal__body">
          {children}
        </div>

        {/* Footer */}
        {(actions.length > 0 || footer) && (
          <div className="modal__footer">
            {footer || (
              <div className="modal__actions">
                {actions.map((action, index) => (
                  <button
                    key={index}
                    className={`modal__action-btn modal__action-btn--${action.variant || 'secondary'}`}
                    onClick={action.onClick}
                    disabled={action.disabled || loading}
                    type={action.type || 'button'}
                  >
                    {action.loading && <span className="modal__btn-spinner" />}
                    {action.label}
                  </button>
                ))}
              </div>
            )}
          </div>
        )}

        {/* Loading overlay */}
        {loading && (
          <div className="modal__loading-overlay">
            <div className="modal__spinner" />
          </div>
        )}
      </div>
    </div>
  )

  return createPortal(modalContent, document.body)
}

Modal.propTypes = {
  isOpen: PropTypes.bool.isRequired,
  onClose: PropTypes.func,
  title: PropTypes.string,
  children: PropTypes.node,
  variant: PropTypes.oneOf(['default', 'confirmation', 'form', 'info', 'warning', 'danger']),
  size: PropTypes.oneOf(['sm', 'md', 'lg', 'xl', 'full']),
  actions: PropTypes.arrayOf(PropTypes.shape({
    label: PropTypes.string.isRequired,
    onClick: PropTypes.func.isRequired,
    variant: PropTypes.oneOf(['primary', 'secondary', 'danger', 'warning', 'ghost']),
    disabled: PropTypes.bool,
    loading: PropTypes.bool,
    type: PropTypes.string
  })),
  closeOnOverlayClick: PropTypes.bool,
  closeOnEscape: PropTypes.bool,
  showCloseButton: PropTypes.bool,
  className: PropTypes.string,
  footer: PropTypes.node,
  icon: PropTypes.node,
  loading: PropTypes.bool
}

/**
 * Confirmation Modal - Specialized modal for confirmations
 */
export function ConfirmModal({
  isOpen,
  onClose,
  onConfirm,
  title = 'Potwierdź akcję',
  message,
  confirmLabel = 'Potwierdź',
  cancelLabel = 'Anuluj',
  variant = 'warning',
  loading = false,
  ...props
}) {
  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={title}
      variant={variant}
      size="sm"
      loading={loading}
      actions={[
        { label: cancelLabel, variant: 'secondary', onClick: onClose },
        { label: confirmLabel, variant: variant === 'danger' ? 'danger' : 'primary', onClick: onConfirm, loading }
      ]}
      {...props}
    >
      <p>{message}</p>
    </Modal>
  )
}

ConfirmModal.propTypes = {
  isOpen: PropTypes.bool.isRequired,
  onClose: PropTypes.func.isRequired,
  onConfirm: PropTypes.func.isRequired,
  title: PropTypes.string,
  message: PropTypes.node.isRequired,
  confirmLabel: PropTypes.string,
  cancelLabel: PropTypes.string,
  variant: PropTypes.oneOf(['warning', 'danger', 'info']),
  loading: PropTypes.bool
}
