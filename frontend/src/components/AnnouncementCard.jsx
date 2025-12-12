import React from 'react'
import { FaClock, FaCalendar, FaUser } from 'react-icons/fa'

export default function AnnouncementCard({ announcement, onClick }) {
  const typeIcons = {
    info: '📢',
    warning: '⚠️',
    success: '✅',
    error: '❌'
  }

  const typeLabels = {
    info: 'Informacja',
    warning: 'Ostrzeżenie',
    success: 'Sukces',
    error: 'Błąd'
  }

  const formattedDate = announcement.createdAt 
    ? new Date(announcement.createdAt).toLocaleDateString('pl-PL', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    : ''

  return (
    <div 
      className={`announcement-card announcement-${announcement.type || 'info'} ${announcement.isPinned ? 'pinned' : ''}`}
      onClick={onClick}
    >
      {announcement.isPinned && (
        <div className="announcement-pin">📌 Przypięte</div>
      )}
      
      <div className="announcement-header">
        <span className="announcement-type">
          {typeIcons[announcement.type] || '📢'} {typeLabels[announcement.type] || 'Informacja'}
        </span>
        {formattedDate && (
          <span className="announcement-date">
            <FaCalendar /> {formattedDate}
          </span>
        )}
      </div>

      <h3 className="announcement-title">{announcement.title}</h3>
      
      <div className="announcement-content">
        {announcement.content && announcement.content.length > 200 
          ? `${announcement.content.substring(0, 200)}...` 
          : announcement.content}
      </div>

      {announcement.createdBy && (
        <div className="announcement-author">
          <FaUser /> {announcement.createdBy.name || announcement.createdBy.email}
        </div>
      )}
    </div>
  )
}
