import React, { createContext, useContext, useState, useCallback, useRef, useEffect } from 'react'
import { createPortal } from 'react-dom'
import PropTypes from 'prop-types'
import './Toast.css'

// Toast context
const ToastContext = createContext(null)

/**
 * useToast - Hook for showing toast notifications
 * 
 * @example
 * const toast = useToast();
 * 
 * toast.success('Operacja zakończona pomyślnie');
 * toast.error('Wystąpił błąd');
 * toast.warning('Uwaga!');
 * toast.info('Informacja');
 */
export function useToast() {
  const context = useContext(ToastContext)
  if (!context) {
    throw new Error('useToast must be used within a ToastProvider')
  }
  return context
}

/**
 * Toast component
 */
function Toast({
  id,
  message,
  title,
  variant = 'info',
  duration = 5000,
  onClose,
  action
}) {
  const [isExiting, setIsExiting] = useState(false)
  const timerRef = useRef(null)

  const handleClose = useCallback(() => {
    setIsExiting(true)
    setTimeout(() => {
      onClose(id)
    }, 200)
  }, [id, onClose])

  useEffect(() => {
    if (duration > 0) {
      timerRef.current = setTimeout(handleClose, duration)
    }

    return () => {
      if (timerRef.current) {
        clearTimeout(timerRef.current)
      }
    }
  }, [duration, handleClose])

  // Pause timer on hover
  const handleMouseEnter = () => {
    if (timerRef.current) {
      clearTimeout(timerRef.current)
    }
  }

  const handleMouseLeave = () => {
    if (duration > 0) {
      timerRef.current = setTimeout(handleClose, duration)
    }
  }

  const icons = {
    success: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
        <polyline points="22 4 12 14.01 9 11.01" />
      </svg>
    ),
    error: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <circle cx="12" cy="12" r="10" />
        <line x1="15" y1="9" x2="9" y2="15" />
        <line x1="9" y1="9" x2="15" y2="15" />
      </svg>
    ),
    warning: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
        <line x1="12" y1="9" x2="12" y2="13" />
        <line x1="12" y1="17" x2="12.01" y2="17" />
      </svg>
    ),
    info: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <circle cx="12" cy="12" r="10" />
        <line x1="12" y1="16" x2="12" y2="12" />
        <line x1="12" y1="8" x2="12.01" y2="8" />
      </svg>
    )
  }

  return (
    <div
      className={`toast toast--${variant} ${isExiting ? 'toast--exiting' : ''}`}
      role="alert"
      onMouseEnter={handleMouseEnter}
      onMouseLeave={handleMouseLeave}
    >
      <span className="toast__icon">{icons[variant]}</span>
      
      <div className="toast__content">
        {title && <strong className="toast__title">{title}</strong>}
        <p className="toast__message">{message}</p>
      </div>

      {action && (
        <button 
          className="toast__action"
          onClick={() => {
            action.onClick?.()
            handleClose()
          }}
        >
          {action.label}
        </button>
      )}

      <button className="toast__close" onClick={handleClose} aria-label="Zamknij">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
          <line x1="18" y1="6" x2="6" y2="18" />
          <line x1="6" y1="6" x2="18" y2="18" />
        </svg>
      </button>
    </div>
  )
}

Toast.propTypes = {
  id: PropTypes.string.isRequired,
  message: PropTypes.string.isRequired,
  title: PropTypes.string,
  variant: PropTypes.oneOf(['success', 'error', 'warning', 'info']),
  duration: PropTypes.number,
  onClose: PropTypes.func.isRequired,
  action: PropTypes.shape({
    label: PropTypes.string.isRequired,
    onClick: PropTypes.func
  })
}

/**
 * ToastContainer - renders toasts
 */
function ToastContainer({ toasts, onClose, position = 'top-right' }) {
  if (toasts.length === 0) return null

  return createPortal(
    <div className={`toast-container toast-container--${position}`}>
      {toasts.map(toast => (
        <Toast key={toast.id} {...toast} onClose={onClose} />
      ))}
    </div>,
    document.body
  )
}

/**
 * ToastProvider - Provides toast context to app
 * 
 * @example
 * // In App.jsx
 * <ToastProvider>
 *   <App />
 * </ToastProvider>
 */
export function ToastProvider({ 
  children, 
  position = 'top-right',
  maxToasts = 5 
}) {
  const [toasts, setToasts] = useState([])
  const toastIdCounter = useRef(0)

  const removeToast = useCallback((id) => {
    setToasts(prev => prev.filter(t => t.id !== id))
  }, [])

  const addToast = useCallback((options) => {
    const id = `toast-${++toastIdCounter.current}`
    
    const toast = {
      id,
      message: typeof options === 'string' ? options : options.message,
      title: options.title,
      variant: options.variant || 'info',
      duration: options.duration ?? 5000,
      action: options.action
    }

    setToasts(prev => {
      const newToasts = [...prev, toast]
      // Remove oldest if exceeds max
      if (newToasts.length > maxToasts) {
        return newToasts.slice(-maxToasts)
      }
      return newToasts
    })

    return id
  }, [maxToasts])

  const success = useCallback((message, options = {}) => {
    return addToast({ ...options, message, variant: 'success' })
  }, [addToast])

  const error = useCallback((message, options = {}) => {
    return addToast({ ...options, message, variant: 'error', duration: options.duration ?? 8000 })
  }, [addToast])

  const warning = useCallback((message, options = {}) => {
    return addToast({ ...options, message, variant: 'warning' })
  }, [addToast])

  const info = useCallback((message, options = {}) => {
    return addToast({ ...options, message, variant: 'info' })
  }, [addToast])

  const dismiss = useCallback((id) => {
    if (id) {
      removeToast(id)
    } else {
      setToasts([])
    }
  }, [removeToast])

  const value = {
    toast: addToast,
    success,
    error,
    warning,
    info,
    dismiss
  }

  return (
    <ToastContext.Provider value={value}>
      {children}
      <ToastContainer toasts={toasts} onClose={removeToast} position={position} />
    </ToastContext.Provider>
  )
}

ToastProvider.propTypes = {
  children: PropTypes.node.isRequired,
  position: PropTypes.oneOf([
    'top-left', 'top-center', 'top-right',
    'bottom-left', 'bottom-center', 'bottom-right'
  ]),
  maxToasts: PropTypes.number
}

export default useToast
