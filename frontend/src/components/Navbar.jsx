import React, { useCallback } from 'react'
import { NavLink } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { apiFetch } from '../api'
import { useResourceCache } from '../context/ResourceCacheContext'

const navClass = ({ isActive }) => isActive ? 'sidebar__link is-active' : 'sidebar__link'

export default function Navbar() {
  const { token, user, logout } = useAuth()
  const { prefetchResource } = useResourceCache()
  const roles = user?.roles || []
  const isAdmin = roles.includes('ROLE_ADMIN')
  const isLibrarian = roles.includes('ROLE_LIBRARIAN') || isAdmin

  const prefetchLoans = useCallback(() => {
    if (!token) return
    prefetchResource('loans:/api/loans', () => apiFetch('/api/loans')).catch(() => {})
  }, [prefetchResource, token])

  const prefetchReservations = useCallback(() => {
    if (!token) return
    prefetchResource('reservations:/api/reservations?history=true', () => apiFetch('/api/reservations?history=true')).catch(() => {})
  }, [prefetchResource, token])

  const prefetchFavorites = useCallback(() => {
    if (!token) return
    prefetchResource('favorites:/api/favorites', () => apiFetch('/api/favorites')).catch(() => {})
  }, [prefetchResource, token])

  const prefetchRecommended = useCallback(() => {
    prefetchResource('recommended:/api/books/recommended', () => apiFetch('/api/books/recommended')).catch(() => {})
  }, [prefetchResource])

  return (
    <aside className="sidebar">
      <div className="sidebar__brand">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{marginRight: '8px'}}>
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
        </svg>
        Biblioteka
      </div>
      <nav className="sidebar__menu">
        <NavLink to="/" end className={navClass}>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
          </svg>
          Strona główna
        </NavLink>
        <NavLink to="/books" className={navClass}>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
          </svg>
          Książki
        </NavLink>
        <NavLink to="/recommended" className={navClass} onMouseEnter={prefetchRecommended} onFocus={prefetchRecommended}>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
          </svg>
          Polecane
        </NavLink>
        <NavLink to="/announcements" className={navClass}>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
          </svg>
          Ogłoszenia
        </NavLink>
        {token && (
          <>
            <NavLink to="/my-loans" className={navClass} onMouseEnter={prefetchLoans} onFocus={prefetchLoans}>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
              </svg>
              Wypożyczenia
            </NavLink>
            <NavLink to="/reservations" className={navClass} onMouseEnter={prefetchReservations} onFocus={prefetchReservations}>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
              Rezerwacje
            </NavLink>
            <NavLink to="/favorites" className={navClass} onMouseEnter={prefetchFavorites} onFocus={prefetchFavorites}>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
              </svg>
              Ulubione
            </NavLink>
            <NavLink to="/profile" className={navClass}>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              Profil
            </NavLink>
            {isLibrarian && (
              <NavLink to="/librarian" className={navClass}>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                  <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Panel bibliotekarza
              </NavLink>
            )}
            {isAdmin && (
              <NavLink to="/admin" className={navClass}>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <circle cx="12" cy="12" r="3"></circle>
                  <path d="M12 1v6m0 6v6m8.66-13.66l-4.24 4.24m-4.24 4.24l-4.24 4.24m13.66-8.66l-4.24-4.24m-4.24-4.24l-4.24-4.24"></path>
                </svg>
                Panel administratora
              </NavLink>
            )}
          </>
        )}
      </nav>

      <div className="sidebar__footer">
        {token ? (
          <>
            <div className="sidebar__user">
              <div className="avatar avatar--sm">
                {user?.name?.charAt(0).toUpperCase() || 'U'}
              </div>
              <span className="sidebar__username">{user?.name || 'Użytkownik'}</span>
            </div>
            <button className="btn btn-ghost" onClick={logout}>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
              </svg>
              Wyloguj
            </button>
          </>
        ) : (
          <>
            <NavLink to="/login" className="btn btn-primary">Zaloguj</NavLink>
            <NavLink to="/register" className="btn btn-outline">Zarejestruj się</NavLink>
          </>
        )}
      </div>
    </aside>
  )
}
