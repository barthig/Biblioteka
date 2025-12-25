import React from 'react'

export default function SectionCard({ title, subtitle, actions, header, children, className = '' }) {
  return (
    <section className={`surface-card ${className}`.trim()}>
      {header ? (
        header
      ) : (
        (title || actions || subtitle) && (
          <div className="section-header">
            <div>
              {title && <h2>{title}</h2>}
              {subtitle && <p className="support-copy">{subtitle}</p>}
            </div>
            {actions && <div className="section-header__actions">{actions}</div>}
          </div>
        )
      )}
      {children}
    </section>
  )
}
