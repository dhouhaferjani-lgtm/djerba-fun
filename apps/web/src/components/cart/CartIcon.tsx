'use client';

import { ShoppingCart } from 'lucide-react';
import { useCartContext } from '@/lib/contexts/CartContext';
import { cn } from '@/lib/utils/cn';

interface CartIconProps {
  locale: string;
  className?: string;
}

export function CartIcon({ locale, className }: CartIconProps) {
  const { itemCount, isLoading } = useCartContext();

  return (
    <a
      href={`/${locale}/cart`}
      className={cn(
        'relative p-2 text-white hover:bg-primary-light rounded-lg transition-colors',
        className
      )}
      aria-label={`Cart (${itemCount} items)`}
    >
      <ShoppingCart className="h-5 w-5" />
      {itemCount > 0 && !isLoading && (
        <span className="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-secondary-cream text-xs font-bold text-primary">
          {itemCount > 9 ? '9+' : itemCount}
        </span>
      )}
    </a>
  );
}
