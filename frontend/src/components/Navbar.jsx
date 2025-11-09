import React from 'react'
import { NavLink } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

const navClass = ({ isActive }) => isActive ? 'sidebar__link is-active' : 'sidebar__link'

export default function Navbar() {
  const { token, logout } = useAuth()

  return (
    <aside className="sidebar">
      <div className="sidebar__brand">Biblioteka</div>
      <nav className="sidebar__menu">
        <NavLink to="/" end className={navClass}>Dashboard</NavLink>
        <NavLink to="/books" className={navClass}>Książki</NavLink>
        <NavLink to="/my-loans" className={navClass}>Wypożyczenia</NavLink>
      </nav>

      <div className="sidebar__footer">
        {token ? (
          <button className="btn btn-ghost" onClick={logout}>Wyloguj</button>
        ) : (
          <NavLink to="/login" className="btn btn-primary">Zaloguj</NavLink>
        )}
      </div>
    </aside>
  )
}
