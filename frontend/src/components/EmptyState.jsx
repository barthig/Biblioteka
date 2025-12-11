import React from 'react'
import { FaInbox } from 'react-icons/fa'

export default function EmptyState({ 
  icon: Icon = FaInbox, 
  title = 'Brak danych', 
  message, 
  action 
}) {
  return (
    <div className="empty-state">
      <Icon className="empty-state-icon" />
      <h3 className="empty-state-title">{title}</h3>
      {message && <p className="empty-state-message">{message}</p>}
      {action && (
        <div className="empty-state-action">
          {action}
        </div>
      )}
    </div>
  )
}
