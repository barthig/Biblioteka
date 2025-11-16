import React, { useCallback } from 'react'
import { NavLink } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { apiFetch } from '../api'
import { useResourceCache } from '../context/ResourceCacheContext'

const navClass = ({ isActive }) => isActive ? 'sidebar__link is-active' : 'sidebar__link'

export default function Navbar() {
  const { token, logout } = useAuth()
  const { prefetchResource } = useResourceCache()

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
      <div className="sidebar__brand">Biblioteka</div>
      <nav className="sidebar__menu">
        <NavLink to="/" end className={navClass}>Dashboard</NavLink>
        <NavLink to="/books" className={navClass}>Książki</NavLink>
        <NavLink to="/recommended" className={navClass} onMouseEnter={prefetchRecommended} onFocus={prefetchRecommended}>Polecane</NavLink>
        <NavLink to="/my-loans" className={navClass} onMouseEnter={prefetchLoans} onFocus={prefetchLoans}>Wypożyczenia</NavLink>
        {token && (
          <>
            <NavLink to="/reservations" className={navClass} onMouseEnter={prefetchReservations} onFocus={prefetchReservations}>Rezerwacje</NavLink>
            <NavLink to="/favorites" className={navClass} onMouseEnter={prefetchFavorites} onFocus={prefetchFavorites}>Ulubione</NavLink>
            <NavLink to="/profile" className={navClass}>Profil</NavLink>
          </>
        )}
      </nav>

      <div className="sidebar__footer">
        {token ? (
          <button className="btn btn-ghost" onClick={logout}>Wyloguj</button>
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
