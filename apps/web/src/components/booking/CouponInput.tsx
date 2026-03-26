'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { useValidateCoupon } from '@/lib/api/hooks';

interface CouponInputProps {
  listingId: string;
  amount: number;
  currency: string;
  onApply: (code: string, discountAmount: number) => void;
  onRemove: () => void;
  appliedCode?: string;
  appliedDiscount?: number;
}

function formatPrice(value: number, currency: string): string {
  if (currency === 'EUR' || currency === '€') {
    return `€${value.toFixed(2)}`;
  }
  if (currency === 'USD' || currency === '$') {
    return `$${value.toFixed(2)}`;
  }
  if (currency === 'TND') {
    return `${value.toFixed(2)} TND`;
  }
  return `${value.toFixed(2)} ${currency}`;
}

export default function CouponInput({
  listingId,
  amount,
  currency,
  onApply,
  onRemove,
  appliedCode,
  appliedDiscount,
}: CouponInputProps) {
  const t = useTranslations('coupon');
  const [code, setCode] = useState('');
  const [error, setError] = useState('');
  const validateCoupon = useValidateCoupon();

  const handleApply = async () => {
    if (!code.trim()) {
      return;
    }

    setError('');

    try {
      const result = await validateCoupon.mutateAsync({
        code: code.trim().toUpperCase(),
        listingId,
        amount,
      });

      if (result.data.valid && result.data.discountAmount) {
        onApply(code.trim().toUpperCase(), result.data.discountAmount);
        setCode('');
      } else {
        setError(result.data.message || t('invalid'));
      }
    } catch {
      setError(t('invalid'));
    }
  };

  const handleRemove = () => {
    onRemove();
    setCode('');
    setError('');
  };

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      handleApply();
    }
  };

  if (appliedCode && appliedDiscount) {
    return (
      <div
        className="border border-success/20 bg-success-light rounded-lg p-4"
        data-testid="coupon-applied"
      >
        <div className="flex items-center justify-between mb-2">
          <div className="flex items-center gap-2">
            <svg className="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
              <path
                fillRule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clipRule="evenodd"
              />
            </svg>
            <span className="font-semibold text-success-dark">{t('applied')}</span>
          </div>
          <button
            onClick={handleRemove}
            className="text-success hover:text-success-dark text-sm font-medium"
            data-testid="coupon-remove-button"
          >
            {t('remove')}
          </button>
        </div>
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm text-success-dark">
              Code: <span className="font-mono font-semibold">{appliedCode}</span>
            </p>
          </div>
          <div className="text-right">
            <p className="text-sm text-success">{t('discount')}</p>
            <p className="text-lg font-bold text-success-dark">
              -{formatPrice(appliedDiscount, currency)}
            </p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div>
      <label htmlFor="coupon" className="block text-sm font-medium text-gray-700 mb-2">
        {t('enter_code')}
      </label>
      <div className="flex gap-2">
        <div className="flex-1">
          <input
            type="text"
            id="coupon"
            value={code}
            onChange={(e) => setCode(e.target.value.toUpperCase())}
            onKeyPress={handleKeyPress}
            placeholder="SUMMER2025"
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent uppercase font-mono"
            disabled={validateCoupon.isPending}
            data-testid="coupon-code-input"
          />
          {error && (
            <p className="text-sm text-error mt-1" data-testid="coupon-error">
              {error}
            </p>
          )}
        </div>
        <button
          onClick={handleApply}
          disabled={!code.trim() || validateCoupon.isPending}
          className="px-6 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          data-testid="coupon-apply-button"
        >
          {validateCoupon.isPending ? (
            <svg
              className="animate-spin h-5 w-5"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                className="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="4"
              />
              <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              />
            </svg>
          ) : (
            t('apply')
          )}
        </button>
      </div>
    </div>
  );
}
