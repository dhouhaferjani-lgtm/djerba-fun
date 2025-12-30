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
  className?: string;
}

const currencySymbols: Record<string, string> = {
  TND: 'د.ت',
  EUR: '€',
  USD: '$',
  GBP: '£',
  CAD: '$',
};

const sizeClasses = {
  sm: 'text-lg',
  md: 'text-2xl',
  lg: 'text-3xl',
};

function PriceDisplayComponent({
  amount,
  currency,
  size = 'md',
  showFrom = false,
  perPerson = true,
  className = '',
}: PriceDisplayProps) {
  const t = useTranslations('common');

  // Memoize expensive calculations
  const symbol = useMemo(() => currencySymbols[currency] || currency, [currency]);
  const formattedAmount = useMemo(() => Number(amount).toFixed(2), [amount]);

  return (
    <div className={`flex flex-col ${className}`}>
      <div className="flex items-baseline gap-1">
        {showFrom && <span className="text-sm font-normal text-neutral-600">{t('from')}</span>}
        <span className={`font-bold text-primary ${sizeClasses[size]}`}>
          {symbol}
          {formattedAmount}
        </span>
      </div>
      {perPerson && <span className="text-xs text-neutral-500">{t('per_person')}</span>}
    </div>
  );
}

// Memoize to prevent unnecessary re-renders
export const PriceDisplay = memo(PriceDisplayComponent);
