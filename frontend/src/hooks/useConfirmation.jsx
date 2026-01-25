import React, { useState, useCallback, createContext, useContext, useMemo } from 'react'
import { ConfirmModal } from '../components/ui/Modal'

/**
 * useConfirmation - Hook for managing confirmation dialogs
 * 
 * @example
 * const { confirm, ConfirmDialog } = useConfirmation();
 * 
 * const handleDelete = async () => {
 *   const confirmed = await confirm({
 *     title: 'Usuń książkę',
 *     message: 'Czy na pewno chcesz usunąć tę książkę?',
 *     confirmText: 'Usuń',
 *     variant: 'danger'
 *   });
 *   
 *   if (confirmed) {
 *     await deleteBook(id);
 *   }
 * };
 */
export function useConfirmation() {
  const [state, setState] = useState({
    isOpen: false,
    title: '',
    message: '',
    confirmText: 'Potwierdź',
    cancelText: 'Anuluj',
    variant: 'primary',
    resolve: null
  })

  const confirm = useCallback((options) => {
    return new Promise((resolve) => {
      setState({
        isOpen: true,
        title: options.title || 'Potwierdź',
        message: options.message || 'Czy na pewno chcesz kontynuować?',
        confirmText: options.confirmText || 'Potwierdź',
        cancelText: options.cancelText || 'Anuluj',
        variant: options.variant || 'primary',
        resolve
      })
    })
  }, [])

  const handleConfirm = useCallback(() => {
    state.resolve?.(true)
    setState(prev => ({ ...prev, isOpen: false }))
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [state.resolve])

  const handleCancel = useCallback(() => {
    state.resolve?.(false)
    setState(prev => ({ ...prev, isOpen: false }))
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [state.resolve])

  // Shorthand methods
  const confirmDelete = useCallback((itemName = 'ten element') => {
    return confirm({
      title: 'Potwierdź usunięcie',
      message: `Czy na pewno chcesz usunąć ${itemName}? Ta operacja jest nieodwracalna.`,
      confirmText: 'Usuń',
      variant: 'danger'
    })
  }, [confirm])

  const confirmAction = useCallback((message, title = 'Potwierdź') => {
    return confirm({
      title,
      message,
      variant: 'primary'
    })
  }, [confirm])

  const confirmDangerous = useCallback((message, title = 'Uwaga') => {
    return confirm({
      title,
      message,
      confirmText: 'Potwierdź',
      variant: 'danger'
    })
  }, [confirm])

  // Component to render in your app
  const ConfirmDialog = useMemo(() => (
    <ConfirmModal
      isOpen={state.isOpen}
      onClose={handleCancel}
      onConfirm={handleConfirm}
      title={state.title}
      message={state.message}
      confirmText={state.confirmText}
      cancelText={state.cancelText}
      variant={state.variant}
    />
  ), [state, handleCancel, handleConfirm])

  return {
    confirm,
    confirmDelete,
    confirmAction,
    confirmDangerous,
    ConfirmDialog,
    isConfirming: state.isOpen
  }
}

// Context for global confirmation
const ConfirmationContext = createContext(null)

/**
 * ConfirmationProvider - Provides global confirmation context
 * 
 * @example
 * // In App.jsx
 * <ConfirmationProvider>
 *   <App />
 * </ConfirmationProvider>
 * 
 * // In any component
 * const { confirm } = useConfirmationContext();
 */
export function ConfirmationProvider({ children }) {
  const confirmation = useConfirmation()

  return (
    <ConfirmationContext.Provider value={confirmation}>
      {children}
      {confirmation.ConfirmDialog}
    </ConfirmationContext.Provider>
  )
}

export function useConfirmationContext() {
  const context = useContext(ConfirmationContext)
  if (!context) {
    throw new Error('useConfirmationContext must be used within ConfirmationProvider')
  }
  return context
}

export default useConfirmation
