import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react'
import { useAuth } from './AuthContext'

interface CartItem {
  cart_id: number
  cart_details_id: number
  book_id: number
  name: string
  author: string
  price: number
  img_src: string
  quantity: number
}

interface CartContextType {
  cartItems: CartItem[]
  totalPrice: number
  addToCart: (bookId: number, price: number) => Promise<void>
  removeFromCart: (cartDetailsId: number) => Promise<void>
  removeAllFromCart: (cartDetailsId: number) => Promise<void>
  fetchCart: () => Promise<void>
  clearCart: () => void
}

const CartContext = createContext<CartContextType | undefined>(undefined)

export const useCart = () => {
  const context = useContext(CartContext)
  if (context === undefined) {
    throw new Error('useCart must be used within a CartProvider')
  }
  return context
}

interface CartProviderProps {
  children: ReactNode
}

export const CartProvider: React.FC<CartProviderProps> = ({ children }) => {
  const [cartItems, setCartItems] = useState<CartItem[]>([])
  const [totalPrice, setTotalPrice] = useState(0)
  const { user } = useAuth()

  const API_BASE = 'https://bookshop-production-075d.up.railway.app'

  const fetchCart = async () => {
    if (!user) return

    try {
      const response = await fetch(`${API_BASE}/getCart.php?user_id=${user.id}`)
      const data = await response.json()
      
      if (data.success) {
        setCartItems(data.cart_items || [])
        setTotalPrice(data.total_price || 0)
      }
    } catch (error) {
      console.error('Error fetching cart:', error)
    }
  }

  const addToCart = async (bookId: number, price: number) => {
    if (!user) return

    try {
      const response = await fetch(`${API_BASE}/addToCart.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          user_id: user.id,
          book_id: bookId,
          price: price
        })
      })

      if (response.ok) {
        await fetchCart()
      }
    } catch (error) {
      console.error('Error adding to cart:', error)
    }
  }

  const removeFromCart = async (cartDetailsId: number) => {
    try {
      const response = await fetch(`${API_BASE}/removeFromCart.php?cart_details_id=${cartDetailsId}`, {
        method: 'POST'
      })

      if (response.ok) {
        await fetchCart()
      }
    } catch (error) {
      console.error('Error removing from cart:', error)
    }
  }

  const removeAllFromCart = async (cartDetailsId: number) => {
    try {
      const response = await fetch(`${API_BASE}/removeAllFromCart.php?cart_details_id=${cartDetailsId}`, {
        method: 'POST'
      })

      if (response.ok) {
        await fetchCart()
      }
    } catch (error) {
      console.error('Error removing all from cart:', error)
    }
  }

  const clearCart = () => {
    setCartItems([])
    setTotalPrice(0)
  }

  useEffect(() => {
    if (user) {
      fetchCart()
    } else {
      clearCart()
    }
  }, [user])

  const value = {
    cartItems,
    totalPrice,
    addToCart,
    removeFromCart,
    removeAllFromCart,
    fetchCart,
    clearCart
  }

  return (
    <CartContext.Provider value={value}>
      {children}
    </CartContext.Provider>
  )
}