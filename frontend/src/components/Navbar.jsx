import React from 'react'
import { NavLink } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function Navbar() {
  const { token, logout } = useAuth()

  return (
    <aside className="sidebar">
      <div className="brand">Biblioteka</div>
      <nav className="menu">
        <NavLink to="/" end className={({isActive})=> isActive? 'active' : ''}>Dashboard</NavLink>
        <NavLink to="/books" className={({isActive})=> isActive? 'active' : ''}>Books</NavLink>
        <NavLink to="/my-loans" className={({isActive})=> isActive? 'active' : ''}>My Loans</NavLink>
      </nav>

      <div className="sidebar-footer">
        {token ? (
          <button onClick={logout}>Logout</button>
        ) : (
          <NavLink to="/login">Login</NavLink>
        )}
      </div>
    </aside>
  )
}
