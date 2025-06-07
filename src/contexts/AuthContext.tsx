import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react'

interface User {
  id: number
  name: string
  email: string
  role: string
  created_at: string
}

interface AuthContextType {
  user: User | null
  token: string | null
  login: (userData: User, token: string) => void
  logout: () => void
  isAuthenticated: boolean
  isAdmin: boolean
}

const AuthContext = createContext<AuthContextType | undefined>(undefined)

export const useAuth = () => {
  const context = useContext(AuthContext)
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider')
  }
  return context
}

interface AuthProviderProps {
  children: ReactNode
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null)
  const [token, setToken] = useState<string | null>(null)

  useEffect(() => {
    const savedUser = localStorage.getItem('user')
    const savedToken = localStorage.getItem('token')
    
    if (savedUser && savedToken) {
      setUser(JSON.parse(savedUser))
      setToken(savedToken)
    }
  }, [])

  const login = (userData: User, userToken: string) => {
    setUser(userData)
    setToken(userToken)
    localStorage.setItem('user', JSON.stringify(userData))
    localStorage.setItem('token', userToken)
  }

  const logout = () => {
    setUser(null)
    setToken(null)
    localStorage.removeItem('user')
    localStorage.removeItem('token')
  }

  const isAuthenticated = !!user && !!token
  const isAdmin = user?.role === 'admin'

  const value = {
    user,
    token,
    login,
    logout,
    isAuthenticated,
    isAdmin
  }

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  )
}