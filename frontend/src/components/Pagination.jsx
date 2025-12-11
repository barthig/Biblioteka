import React from 'react'

export default function Pagination({ currentPage, totalPages, onPageChange }) {
  if (totalPages <= 1) return null

  const pages = []
  const maxPagesToShow = 5
  let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2))
  let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1)

  if (endPage - startPage < maxPagesToShow - 1) {
    startPage = Math.max(1, endPage - maxPagesToShow + 1)
  }

  if (startPage > 1) {
    pages.push(
      <button key={1} onClick={() => onPageChange(1)} className="pagination-btn">
        1
      </button>
    )
    if (startPage > 2) {
      pages.push(<span key="start-ellipsis" className="pagination-ellipsis">...</span>)
    }
  }

  for (let i = startPage; i <= endPage; i++) {
    pages.push(
      <button
        key={i}
        onClick={() => onPageChange(i)}
        className={`pagination-btn ${i === currentPage ? 'active' : ''}`}
      >
        {i}
      </button>
    )
  }

  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      pages.push(<span key="end-ellipsis" className="pagination-ellipsis">...</span>)
    }
    pages.push(
      <button key={totalPages} onClick={() => onPageChange(totalPages)} className="pagination-btn">
        {totalPages}
      </button>
    )
  }

  return (
    <div className="pagination">
      <button
        onClick={() => onPageChange(currentPage - 1)}
        disabled={currentPage === 1}
        className="pagination-btn pagination-prev"
      >
        ← Poprzednia
      </button>
      {pages}
      <button
        onClick={() => onPageChange(currentPage + 1)}
        disabled={currentPage === totalPages}
        className="pagination-btn pagination-next"
      >
        Następna →
      </button>
    </div>
  )
}
