'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- next-intl Link requires typed routes */
import { useTranslations } from 'next-intl';
import { Button } from '@go-adventure/ui';
import { useCartContext } from '@/lib/contexts/CartContext';
import { CartItemCard } from './CartItemCard';
import { CartSummary } from './CartSummary';
import { ShoppingCart, ArrowRight, Package } from 'lucide-react';
import Link from 'next/link';

interface CartPageProps {
  locale: string;
}

export function CartPage({ locale }: CartPageProps) {
  const t = useTranslations('cart');
  const { cart, isLoading, itemCount, clearCart, isClearingCart } = useCartContext();

  if (isLoading) {
    return (
      <div className="container mx-auto px-4 py-12">
        <div className="animate-pulse">
          <div className="h-8 bg-gray-200 rounded w-48 mb-8"></div>
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div className="lg:col-span-2 space-y-4">
              {[1, 2].map((i) => (
                <div key={i} className="h-40 bg-gray-200 rounded-lg"></div>
              ))}
            </div>
            <div className="h-64 bg-gray-200 rounded-lg"></div>
          </div>
        </div>
      </div>
    );
  }

  if (!cart || itemCount === 0) {
    return (
      <div className="container mx-auto px-4 py-12">
        <div className="text-center py-16">
          <div className="w-24 h-24 mx-auto mb-6 rounded-full bg-gray-100 flex items-center justify-center">
            <ShoppingCart className="w-12 h-12 text-gray-400" />
          </div>
          <h1 className="text-2xl font-bold text-gray-900 mb-3">{t('empty_title')}</h1>
          <p className="text-gray-600 mb-8 max-w-md mx-auto">{t('empty_description')}</p>
          <Link href={`/${locale}/listings`}>
            <Button size="lg">
              <Package className="w-5 h-5 mr-2" />
              {t('browse_experiences')}
            </Button>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="flex items-center justify-between mb-8">
        <h1 className="text-2xl font-bold text-gray-900">
          {t('title')} ({itemCount})
        </h1>
        <Button
          variant="ghost"
          onClick={() => clearCart()}
          disabled={isClearingCart}
          className="text-error hover:text-error-dark hover:bg-error-light"
        >
          {t('clear_cart')}
        </Button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Cart Items */}
        <div className="lg:col-span-2 space-y-4">
          {cart.items.map((item) => (
            <CartItemCard key={item.id} item={item} locale={locale} />
          ))}
        </div>

        {/* Cart Summary */}
        <div className="lg:col-span-1">
          <CartSummary cart={cart} locale={locale} />
        </div>
      </div>
    </div>
  );
}
