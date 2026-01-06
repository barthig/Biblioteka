import React from 'react'
import { format, differenceInDays } from 'date-fns'
import { pl } from 'date-fns/locale'
import { FaBook, FaClock, FaTimes } from 'react-icons/fa'

export default function ReservationCard({ reservation, onCancel, onFulfill }) {
  const reservedDate = reservation.reservedAt ? new Date(reservation.reservedAt) : null
  const expiresDate = reservation.expiresAt ? new Date(reservation.expiresAt) : null
  
  const daysLeft = expiresDate ? differenceInDays(expiresDate, new Date()) : null
  const isExpired = daysLeft !== null && daysLeft < 0
  const status = reservation.status || 'pending'

  const statusLabels = {
    pending: 'Oczekuje',
    ready: 'Gotowa do odbioru',
    fulfilled: 'Zrealizowana',
    cancelled: 'Anulowana',
    expired: 'Wygasła'
  }

  const statusClass = {
    pending: 'warning',
    ready: 'success',
    fulfilled: 'info',
    cancelled: 'error',
    expired: 'error'
  }[status] || 'info'

  return (
    <div className={`reservation-card reservation-${statusClass}`}>
      <div className="reservation-book-info">
        <FaBook className="reservation-icon" />
        <div>
          <h3>{reservation.book?.title || 'Nieznana książka'}</h3>
          <p className="reservation-author">{reservation.book?.author || ''}</p>
        </div>
      </div>

      <div className="reservation-details">
        <div className="reservation-status">
          Status: <strong>{statusLabels[status]}</strong>
        </div>

        {reservedDate && (
          <div className="reservation-date">
            <FaClock /> Zarezerwowano: {format(reservedDate, 'dd.MM.yyyy HH:mm', { locale: pl })}
          </div>
        )}

        {expiresDate && status === 'pending' && (
          <div className="reservation-expires">
            Wygasa: {format(expiresDate, 'dd.MM.yyyy', { locale: pl })}
            {daysLeft !== null && !isExpired && (
              <span className="days-left">
                (za {daysLeft} dni)
              </span>
            )}
          </div>
        )}

        {status === 'ready' && (
          <div className="reservation-ready">
            Książka gotowa do odbioru!
          </div>
        )}
      </div>

      {status === 'pending' && (
        <div className="reservation-actions">
          {onFulfill && (
            <button 
              className="btn btn-primary btn-sm"
              onClick={() => onFulfill(reservation.id)}
            >
              Odbierz
            </button>
          )}
          {onCancel && (
            <button 
              className="btn btn-danger btn-sm"
              onClick={() => onCancel(reservation.id)}
            >
              <FaTimes /> Anuluj
            </button>
          )}
        </div>
      )}
    </div>
  )
}
