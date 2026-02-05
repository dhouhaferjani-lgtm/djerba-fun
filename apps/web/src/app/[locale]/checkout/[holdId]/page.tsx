'use client';

import { useEffect, useRef } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { useAddToCart } from '@/lib/api/hooks';
import { useTranslations } from 'next-intl';

/**
 * Backwards compatibility redirect for old checkout URLs.
 *
 * This page handles URLs like /checkout/{holdId} by:
 * 1. Adding the hold to the cart (if not already there)
 * 2. Redirecting to the unified cart checkout at /cart/checkout
 *
 * This ensures users with bookmarked old checkout URLs still work,
 * and maintains a single checkout experience via the cart.
 */
export default function CheckoutRedirect() {
  const params = useParams();
  const router = useRouter();
  const t = useTranslations('checkout');
  const holdId = params?.holdId as string;
  const locale = params?.locale as string;
  const addToCartMutation = useAddToCart();
  const hasAttemptedRef = useRef(false);

  useEffect(() => {
    // Prevent multiple attempts (React strict mode double-mount)
    if (hasAttemptedRef.current) return;
    hasAttemptedRef.current = true;

    const redirectToCart = async () => {
      if (holdId) {
        try {
          // Add the hold to cart - this is idempotent, won't duplicate
          await addToCartMutation.mutateAsync(holdId);
        } catch (error) {
          // Log but don't block redirect - the hold may already be in cart
          // or may have expired, either way redirect to cart checkout
          console.warn('Could not add hold to cart:', error);
        }

        // Always redirect to unified cart checkout
        router.replace(`/${locale}/cart/checkout`);
      }
    };

    redirectToCart();
  }, [holdId, locale, router, addToCartMutation]);

  return (
    <div className="min-h-[60vh] flex items-center justify-center">
      <div className="text-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4" />
        <p className="text-gray-600">{t('redirecting') || 'Redirecting to checkout...'}</p>
      </div>
    </div>
  );
}
