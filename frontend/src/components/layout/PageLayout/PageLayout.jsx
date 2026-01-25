import React from 'react'
import PropTypes from 'prop-types'
import './PageLayout.css'

/**
 * PageLayout - Standard page layout wrapper
 * 
 * @example
 * <PageLayout
 *   title="Moje wypożyczenia"
 *   description="Lista twoich aktywnych wypożyczeń"
 *   actions={<button>Nowe wypożyczenie</button>}
 * >
 *   <LoansTable />
 * </PageLayout>
 */
export function PageLayout({
  title,
  description,
  actions,
  breadcrumbs,
  variant = 'default',
  maxWidth = 'xl',
  className = '',
  children,
  ...props
}) {
  const classes = [
    'page-layout',
    `page-layout--${variant}`,
    `page-layout--max-${maxWidth}`,
    className
  ].filter(Boolean).join(' ')

  return (
    <div className={classes} {...props}>
      {breadcrumbs && (
        <nav className="page-layout__breadcrumbs" aria-label="Breadcrumb">
          {breadcrumbs}
        </nav>
      )}

      {(title || description || actions) && (
        <header className="page-layout__header">
          <div className="page-layout__header-content">
            {title && <h1 className="page-layout__title">{title}</h1>}
            {description && <p className="page-layout__description">{description}</p>}
          </div>
          {actions && <div className="page-layout__actions">{actions}</div>}
        </header>
      )}

      <main className="page-layout__content">
        {children}
      </main>
    </div>
  )
}

PageLayout.propTypes = {
  title: PropTypes.node,
  description: PropTypes.node,
  actions: PropTypes.node,
  breadcrumbs: PropTypes.node,
  variant: PropTypes.oneOf(['default', 'fluid', 'narrow']),
  maxWidth: PropTypes.oneOf(['sm', 'md', 'lg', 'xl', '2xl', 'full']),
  className: PropTypes.string,
  children: PropTypes.node
}

/**
 * PageSection - Section within a page
 */
export function PageSection({
  title,
  description,
  actions,
  variant = 'default',
  className = '',
  children,
  ...props
}) {
  const classes = [
    'page-section',
    `page-section--${variant}`,
    className
  ].filter(Boolean).join(' ')

  return (
    <section className={classes} {...props}>
      {(title || description || actions) && (
        <div className="page-section__header">
          <div className="page-section__header-content">
            {title && <h2 className="page-section__title">{title}</h2>}
            {description && <p className="page-section__description">{description}</p>}
          </div>
          {actions && <div className="page-section__actions">{actions}</div>}
        </div>
      )}
      <div className="page-section__content">
        {children}
      </div>
    </section>
  )
}

PageSection.propTypes = {
  title: PropTypes.node,
  description: PropTypes.node,
  actions: PropTypes.node,
  variant: PropTypes.oneOf(['default', 'card', 'bordered']),
  className: PropTypes.string,
  children: PropTypes.node
}

/**
 * SplitLayout - Two-column layout
 */
export function SplitLayout({
  sidebar,
  sidebarPosition = 'left',
  sidebarWidth = '280px',
  className = '',
  children,
  ...props
}) {
  const classes = [
    'split-layout',
    `split-layout--sidebar-${sidebarPosition}`,
    className
  ].filter(Boolean).join(' ')

  return (
    <div 
      className={classes} 
      style={{ '--sidebar-width': sidebarWidth }}
      {...props}
    >
      <aside className="split-layout__sidebar">
        {sidebar}
      </aside>
      <main className="split-layout__main">
        {children}
      </main>
    </div>
  )
}

SplitLayout.propTypes = {
  sidebar: PropTypes.node,
  sidebarPosition: PropTypes.oneOf(['left', 'right']),
  sidebarWidth: PropTypes.string,
  className: PropTypes.string,
  children: PropTypes.node
}

/**
 * CardGrid - Responsive card grid
 */
export function CardGrid({
  columns = { sm: 1, md: 2, lg: 3, xl: 4 },
  gap = 'md',
  className = '',
  children,
  ...props
}) {
  const style = {
    '--grid-cols-sm': columns.sm || 1,
    '--grid-cols-md': columns.md || 2,
    '--grid-cols-lg': columns.lg || 3,
    '--grid-cols-xl': columns.xl || 4
  }

  const classes = [
    'card-grid',
    `card-grid--gap-${gap}`,
    className
  ].filter(Boolean).join(' ')

  return (
    <div className={classes} style={style} {...props}>
      {children}
    </div>
  )
}

CardGrid.propTypes = {
  columns: PropTypes.shape({
    sm: PropTypes.number,
    md: PropTypes.number,
    lg: PropTypes.number,
    xl: PropTypes.number
  }),
  gap: PropTypes.oneOf(['sm', 'md', 'lg']),
  className: PropTypes.string,
  children: PropTypes.node
}
