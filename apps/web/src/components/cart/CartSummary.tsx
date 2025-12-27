'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- next-intl Link requires typed routes */
import { useTranslations } from 'next-intl';
import { Button } from '@go-adventure/ui';
import type { Cart } from '@/lib/api/client';
import { ShieldCheck, Clock, ArrowRight, AlertCircle, CheckCircle } from 'lucide-react';
import Link from 'next/link';
import { useEffect, useState } from 'react';
import { useExtendCartHolds } from '@/lib/api/hooks';

interface CartSummaryProps {
  cart: Cart;
  locale: string;
}

export function CartSummary({ cart, locale }: CartSummaryProps) {
  const t = useTranslations('cart');
  const [timeLeft, setTimeLeft] = useState(cart.expiresInSeconds);
  const [extendFeedback, setExtendFeedback] = useState<{
    type: 'success' | 'warning' | 'error';
    message: string;
  } | null>(null);
  const extendHolds = useExtendCartHolds();

  // Countdown timer
  useEffect(() => {
    setTimeLeft(cart.expiresInSeconds);

    const interval = setInterval(() => {
      setTimeLeft((prev) => Math.max(0, prev - 1));
    }, 1000);

    return () => clearInterval(interval);
  }, [cart.expiresInSeconds]);

  const minutes = Math.floor(timeLeft / 60);
  const seconds = timeLeft % 60;

  const handleExtendHolds = async () => {
    setExtendFeedback(null);
    try {
      const result = await extendHolds.mutateAsync();
      if (result.extended > 0 && result.failed === 0) {
        setExtendFeedback({ type: 'success', message: t('holds_extended') });
      } else if (result.extended > 0 && result.failed > 0) {
        setExtendFeedback({ type: 'warning', message: t('some_holds_not_extended') });
      } else {
        setExtendFeedback({ type: 'error', message: t('holds_not_extended') });
      }
      // Clear feedback after 5 seconds
      setTimeout(() => setExtendFeedback(null), 5000);
    } catch {
      setExtendFeedback({ type: 'error', message: t('extend_failed') });
      setTimeout(() => setExtendFeedback(null), 5000);
    }
  };

  // Check if all items have valid holds
  const allHoldsValid = cart.items.every((item) => item.holdValid);

  return (
    <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm sticky top-24">
      {/* Timer */}
      <div className="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
        <div className="flex items-center gap-2 text-gray-600">
          <Clock className="w-5 h-5" />
          <span className="text-sm">{t('reservation_expires')}</span>
        </div>
        <div className="flex items-center gap-2">
          <span
            className={`font-mono font-bold ${timeLeft < 120 ? 'text-error' : 'text-gray-900'}`}
          >
            {String(minutes).padStart(2, '0')}:{String(seconds).padStart(2, '0')}
          </span>
          {timeLeft < 300 && (
            <Button
              variant="ghost"
              size="sm"
              onClick={handleExtendHolds}
              disabled={extendHolds.isPending}
              className="text-primary text-xs"
            >
              {extendHolds.isPending ? t('extending') : t('extend')}
            </Button>
          )}
        </div>
      </div>

      {/* Extend Feedback */}
      {extendFeedback && (
        <div
          className={`flex items-center gap-2 p-3 rounded-lg mb-4 text-sm ${
            extendFeedback.type === 'success'
              ? 'bg-success-light text-success-dark'
              : extendFeedback.type === 'warning'
                ? 'bg-warning-light text-warning-dark'
                : 'bg-error-light text-error-dark'
          }`}
        >
          {extendFeedback.type === 'success' ? (
            <CheckCircle className="w-4 h-4" />
          ) : (
            <AlertCircle className="w-4 h-4" />
          )}
          <span>{extendFeedback.message}</span>
        </div>
      )}

      {/* Summary */}
      <div className="space-y-3 mb-6">
        <div className="flex justify-between text-gray-600">
          <span>{t('items', { count: cart.itemCount })}</span>
          <span>
            {cart.currency} {cart.subtotal.toFixed(2)}
          </span>
        </div>

        {/* Add service fee, taxes etc. here in future */}

        <div className="flex justify-between text-lg font-bold pt-3 border-t border-gray-200">
          <span>{t('total')}</span>
          <span>
            {cart.currency} {cart.subtotal.toFixed(2)}
          </span>
        </div>
      </div>

      {/* Checkout Button */}
      <Link href={`/${locale}/cart/checkout` as any}>
        <Button className="w-full" size="lg" disabled={!allHoldsValid}>
          {t('proceed_to_checkout')}
          <ArrowRight className="w-5 h-5 ml-2" />
        </Button>
      </Link>

      {!allHoldsValid && (
        <p className="text-warning text-sm mt-3 text-center">{t('some_holds_expired')}</p>
      )}

      {/* Trust badges */}
      <div className="mt-6 pt-4 border-t border-gray-200">
        <div className="flex items-center gap-2 text-sm text-gray-500">
          <ShieldCheck className="w-4 h-4 text-success" />
          <span>{t('secure_checkout')}</span>
        </div>
      </div>
    </div>
  );
}
