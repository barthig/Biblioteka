import React from 'react'
import { format, differenceInDays } from 'date-fns'
import { pl } from 'date-fns/locale'
import { FaBook, FaCalendarAlt, FaClock, FaRedo } from 'react-icons/fa'

export default function LoanCard({ loan, onReturn, onExtend }) {
  const dueDate = loan.dueAt ? new Date(loan.dueAt) : null
  const borrowedDate = loan.borrowedAt ? new Date(loan.borrowedAt) : null
  const returnedDate = loan.returnedAt ? new Date(loan.returnedAt) : null
  
  const daysLeft = dueDate ? differenceInDays(dueDate, new Date()) : null
  const isOverdue = daysLeft !== null && daysLeft < 0
  const isReturned = !!returnedDate

  const statusClass = isReturned ? 'returned' : isOverdue ? 'overdue' : daysLeft <= 3 ? 'warning' : 'active'

  return (
    <div className={`loan-card loan-${statusClass}`}>
      <div className="loan-book-info">
        <FaBook className="loan-icon" />
        <div>
          <h3>{loan.book?.title || 'Nieznana książka'}</h3>
          <p className="loan-author">{loan.book?.author || ''}</p>
        </div>
      </div>

      <div className="loan-details">
        {borrowedDate && (
          <div className="loan-date">
            <FaCalendarAlt /> Wypożyczono: {format(borrowedDate, 'dd.MM.yyyy', { locale: pl })}
          </div>
        )}
        
        {dueDate && !isReturned && (
          <div className="loan-due-date">
            <FaClock /> Termin zwrotu: {format(dueDate, 'dd.MM.yyyy', { locale: pl })}
            {daysLeft !== null && (
              <span className={`days-left ${isOverdue ? 'overdue' : ''}`}>
                {isOverdue 
                  ? `Przekroczono o ${Math.abs(daysLeft)} dni` 
                  : `Pozostało ${daysLeft} dni`}
              </span>
            )}
          </div>
        )}

        {returnedDate && (
          <div className="loan-returned">
            Zwrócono: {format(returnedDate, 'dd.MM.yyyy', { locale: pl })}
          </div>
        )}

        {loan.extensionsCount > 0 && (
          <div className="loan-extensions">
            <FaRedo /> Przedłużono {loan.extensionsCount} razy
          </div>
        )}
      </div>

      {!isReturned && (
        <div className="loan-actions">
          {onExtend && (
            <button 
              className="btn btn-secondary btn-sm"
              onClick={() => onExtend(loan.id)}
              disabled={loan.extensionsCount >= 3}
            >
              <FaRedo /> Przedłuż
            </button>
          )}
          {onReturn && (
            <button 
              className="btn btn-primary btn-sm"
              onClick={() => onReturn(loan.id)}
            >
              Zwróć
            </button>
          )}
        </div>
      )}
    </div>
  )
}
