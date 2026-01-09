import { create } from 'zustand'
import { persist } from 'zustand/middleware'

/**
 * Zustand store for authentication state.
 * Replaces AuthContext for better performance and simpler API.
 */
export const useAuthStore = create(
  persist(
    (set, get) => ({
      // State
      user: null,
      token: null,
      refreshToken: null,
      isAuthenticated: false,

      // Actions
      login: (user, token, refreshToken = null) => {
        set({
          user,
          token,
          refreshToken,
          isAuthenticated: true
        })
        
        if (token) {
          localStorage.setItem('token', token)
        }
        if (refreshToken) {
          localStorage.setItem('refreshToken', refreshToken)
        }
      },

      logout: () => {
        set({
          user: null,
          token: null,
          refreshToken: null,
          isAuthenticated: false
        })
        
        localStorage.removeItem('token')
        localStorage.removeItem('refreshToken')
      },

      updateUser: (userData) => {
        set((state) => ({
          user: state.user ? { ...state.user, ...userData } : userData
        }))
      },

      setToken: (token) => {
        set({ token })
        if (token) {
          localStorage.setItem('token', token)
        }
      },

      // Selectors
      hasRole: (role) => {
        const { user } = get()
        return user?.roles?.includes(role) ?? false
      },

      hasAnyRole: (roles) => {
        const { user } = get()
        return roles.some(role => user?.roles?.includes(role)) ?? false
      }
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        refreshToken: state.refreshToken,
        isAuthenticated: state.isAuthenticated
      })
    }
  )
)
