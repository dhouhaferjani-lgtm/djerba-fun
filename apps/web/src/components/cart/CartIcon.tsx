'use client';

import { useState, useEffect, useRef } from 'react';
import Link from 'next/link';
import { ShoppingCart } from 'lucide-react';
import { useCartContext } from '@/lib/contexts/CartContext';
import { cn } from '@/lib/utils/cn';

interface CartIconProps {
  locale: string;
  className?: string;
}

export function CartIcon({ locale, className }: CartIconProps) {
  const { itemCount, isLoading } = useCartContext();
  const [isAnimating, setIsAnimating] = useState(false);
  const prevCountRef = useRef(itemCount);
  const isMountedRef = useRef(false);

  useEffect(() => {
    // Skip animation on initial mount (prevents flash when page loads with items)
    if (!isMountedRef.current) {
      isMountedRef.current = true;
      prevCountRef.current = itemCount;
      return;
    }

    // Only animate when count INCREASES (not on decrease/removal)
    if (itemCount > prevCountRef.current) {
      setIsAnimating(true);
      const timer = setTimeout(() => setIsAnimating(false), 1000);
      prevCountRef.current = itemCount;
      return () => clearTimeout(timer);
    }

    prevCountRef.current = itemCount;
  }, [itemCount]);

  return (
    <Link
      href={`/${locale}/cart`}
      className={cn(
        'relative p-2 text-white hover:bg-primary-light rounded-lg transition-colors',
        isAnimating && 'animate-bounce',
        className
      )}
      aria-label={`Cart (${itemCount} items)`}
    >
      <ShoppingCart className="h-5 w-5" />
      {itemCount > 0 && !isLoading && (
        <span
          className={cn(
            'absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-white text-xs font-bold text-primary border border-primary/20 transition-all',
            isAnimating && 'scale-150 ring-4 ring-white shadow-lg'
          )}
        >
          {itemCount > 9 ? '9+' : itemCount}
        </span>
      )}
    </Link>
  );
}
