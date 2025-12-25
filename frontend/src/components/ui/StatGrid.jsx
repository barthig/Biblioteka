import React from 'react'

export default function StatGrid({ children }) {
  return (
    <div className="card-grid card-grid--columns-3">
      {children}
    </div>
  )
}
