import React from 'react'
import './Skeleton.css'

/**
 * Generic skeleton loader component
 */
export function Skeleton({ width = '100%', height = '20px', borderRadius = '4px', className = '' }) {
  return (
    <div
      className={`skeleton ${className}`}
      style={{ width, height, borderRadius }}
      aria-label="Loading..."
    />
  )
}

/**
 * Skeleton for book item in list
 */
export function BookSkeleton() {
  return (
    <div className="book-item-skeleton">
      <Skeleton width="80px" height="120px" borderRadius="4px" className="book-cover" />
      <div className="book-details">
        <Skeleton width="80%" height="20px" />
        <Skeleton width="60%" height="16px" />
        <Skeleton width="40%" height="16px" />
        <div className="book-meta">
          <Skeleton width="100px" height="14px" />
          <Skeleton width="80px" height="14px" />
        </div>
      </div>
    </div>
  )
}

/**
 * Skeleton for stat card
 */
export function StatCardSkeleton() {
  return (
    <div className="stat-card-skeleton">
      <Skeleton width="100%" height="60px" borderRadius="8px" />
    </div>
  )
}

/**
 * Skeleton for table row
 */
export function TableRowSkeleton({ columns = 4 }) {
  return (
    <tr className="table-row-skeleton">
      {Array.from({ length: columns }).map((_, index) => (
        <td key={index}>
          <Skeleton width="90%" height="16px" />
        </td>
      ))}
    </tr>
  )
}

/**
 * Skeleton for card
 */
export function CardSkeleton() {
  return (
    <div className="card-skeleton">
      <Skeleton width="100%" height="200px" borderRadius="8px" />
      <div style={{ padding: '16px' }}>
        <Skeleton width="70%" height="20px" />
        <Skeleton width="100%" height="16px" />
        <Skeleton width="100%" height="16px" />
        <Skeleton width="50%" height="16px" />
      </div>
    </div>
  )
}
