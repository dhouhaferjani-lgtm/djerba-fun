/**
 * Performance Optimization: React.memo and useMemo applied
 *
 * PriceDisplay is frequently rendered in listing grids, cards, and checkout flows.
 * Memoization prevents recalculation of formatted prices on parent re-renders.
 *
 * Benefits:
 * - Reduces unnecessary price formatting calculations
 * - Better performance in listing grids
 * - Optimized for frequently updating components
 */

import { memo, useMemo } from 'react';
import { useTranslations } from 'next-intl';

interface PriceDisplayProps {
  amount: number;
  currency: string;
  size?: 'sm' | 'md' | 'lg';
  showFrom?: boolean;
  perPerson?: boolean;
  perNight?: boolean;
  className?: string;
}

const currencySymbols: Record<string, string> = {
  TND: 'TND',
  EUR: '€',
  USD: '$',
  GBP: '£',
  CAD: '$',
};

// Size classes for the price amount (larger, bold)
const sizeClasses = {
  sm: 'text-xl',
  md: 'text-2xl',
  lg: 'text-4xl',
};

// Size classes for currency symbol (smaller, semibold)
const symbolSizeClasses = {
  sm: 'text-sm font-semibold text-primary',
  md: 'text-base font-semibold text-primary',
  lg: 'text-xl font-semibold text-primary',
};

function PriceDisplayComponent({
  amount,
  currency,
  size = 'md',
  showFrom = false,
  perPerson = true,
  perNight = false,
  className = '',
}: PriceDisplayProps) {
  const t = useTranslations('common');

  // Memoize expensive calculations
  const symbol = useMemo(() => currencySymbols[currency] || currency, [currency]);
  const formattedAmount = useMemo(() => Number(amount).toFixed(2), [amount]);

  // Determine which label to show (perNight takes precedence)
  const showLabel = perNight || perPerson;
  const labelKey = perNight ? 'per_night' : 'per_person';

  return (
    <div className={`flex flex-col ${className}`}>
      <div className="flex items-baseline gap-1">
        {showFrom && <span className="text-sm font-normal text-neutral-600">{t('from')}</span>}
        <span className={symbolSizeClasses[size]}>{symbol}</span>
        <span className={`font-bold text-primary ${sizeClasses[size]}`}>{formattedAmount}</span>
      </div>
      {showLabel && <span className="text-xs text-neutral-500">{t(labelKey)}</span>}
    </div>
  );
}

// Memoize to prevent unnecessary re-renders
export const PriceDisplay = memo(PriceDisplayComponent);
