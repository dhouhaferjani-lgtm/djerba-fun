'use client';

import { createContext, useContext, useEffect, type ReactNode } from 'react';
import {
  useCart,
  useAddToCart,
  useRemoveFromCart,
  useClearCart,
  useExtendCartHolds,
  useMergeCart,
  useUpdateCartItem,
} from '../api/hooks';
import { useAuth } from './AuthContext';
import type { Cart, CartItem } from '../api/client';

interface CartContextValue {
  cart: Cart | null | undefined;
  isLoading: boolean;
  itemCount: number;
  total: number;
  currency: string;
  expiresInSeconds: number;
  isExpired: boolean;

  // Actions
  addItem: (holdId: string) => Promise<void>;
  removeItem: (itemId: string) => Promise<void>;
  updateItem: (
    itemId: string,
    data: {
      primaryContact?: {
        first_name: string;
        last_name: string;
        email: string;
        phone?: string;
      };
      guestNames?: Array<{
        first_name: string;
        last_name: string;
        person_type?: string;
      }>;
      extras?: Array<{
        id: string;
        name: string;
        price: number;
        quantity: number;
      }>;
    }
  ) => Promise<void>;
  clearCart: () => Promise<void>;
  extendHolds: () => Promise<void>;

  // Loading states
  isAddingItem: boolean;
  isRemovingItem: boolean;
  isClearingCart: boolean;
}

const CartContext = createContext<CartContextValue | undefined>(undefined);

export function CartProvider({ children }: { children: ReactNode }) {
  const { isAuthenticated } = useAuth();
  const { data: cart, isLoading, refetch } = useCart();
  const addItemMutation = useAddToCart();
  const removeItemMutation = useRemoveFromCart();
  const updateItemMutation = useUpdateCartItem();
  const clearCartMutation = useClearCart();
  const extendHoldsMutation = useExtendCartHolds();
  const mergeCartMutation = useMergeCart();

  // Merge guest cart when user logs in
  useEffect(() => {
    if (isAuthenticated) {
      const sessionId = localStorage.getItem('cart_session_id');
      if (sessionId) {
        mergeCartMutation.mutate();
      }
    }
  }, [isAuthenticated]);

  // Refetch cart when it might have changed
  useEffect(() => {
    if (!isLoading) {
      refetch();
    }
  }, [isAuthenticated]);

  const addItem = async (holdId: string) => {
    await addItemMutation.mutateAsync(holdId);
  };

  const removeItem = async (itemId: string) => {
    await removeItemMutation.mutateAsync(itemId);
  };

  const updateItem = async (
    itemId: string,
    data: {
      primaryContact?: {
        first_name: string;
        last_name: string;
        email: string;
        phone?: string;
      };
      guestNames?: Array<{
        first_name: string;
        last_name: string;
        person_type?: string;
      }>;
      extras?: Array<{
        id: string;
        name: string;
        price: number;
        quantity: number;
      }>;
    }
  ) => {
    await updateItemMutation.mutateAsync({ itemId, data });
  };

  const clearCartAction = async () => {
    await clearCartMutation.mutateAsync();
  };

  const extendHolds = async () => {
    await extendHoldsMutation.mutateAsync();
  };

  const value: CartContextValue = {
    cart: cart ?? null,
    isLoading,
    itemCount: cart?.itemCount ?? 0,
    total: cart?.subtotal ?? 0,
    currency: cart?.currency ?? 'EUR',
    expiresInSeconds: cart?.expiresInSeconds ?? 0,
    isExpired: cart?.isExpired ?? false,

    addItem,
    removeItem,
    updateItem,
    clearCart: clearCartAction,
    extendHolds,

    isAddingItem: addItemMutation.isPending,
    isRemovingItem: removeItemMutation.isPending,
    isClearingCart: clearCartMutation.isPending,
  };

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCartContext() {
  const context = useContext(CartContext);
  if (context === undefined) {
    throw new Error('useCartContext must be used within a CartProvider');
  }
  return context;
}
