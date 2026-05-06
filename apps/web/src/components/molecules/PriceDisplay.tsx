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
import { useTranslations, useLocale } from 'next-intl';
import { getPricingUnitLabel, type TranslatableMap } from '@/lib/utils/pricing-unit-label';

interface PriceDisplayProps {
  amount: number;
  currency: string;
  size?: 'sm' | 'md' | 'lg';
  showFrom?: boolean;
  perPerson?: boolean;
  perNight?: boolean;
  /**
   * Optional vendor-supplied translatable suffix (e.g. { fr: "par jetski" }).
   * When set and non-empty for the active locale (or any fallback), it
   * overrides the default "per_person" / "per_night" i18n suffix.
   */
  unitLabel?: TranslatableMap;
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
  unitLabel,
  className = '',
}: PriceDisplayProps) {
  const t = useTranslations('common');
  const locale = useLocale();

  // Memoize expensive calculations
  const symbol = useMemo(() => currencySymbols[currency] || currency, [currency]);
  const formattedAmount = useMemo(() => Number(amount).toFixed(2), [amount]);

  // Determine which label to show (perNight takes precedence)
  const showLabel = perNight || perPerson;
  // Resolve label: vendor override → built-in i18n key
  const resolvedLabel = useMemo(() => {
    const override = getPricingUnitLabel({ unitLabel: unitLabel ?? undefined }, locale);
    if (override !== null) return override;
    return t(perNight ? 'per_night' : 'per_person');
  }, [unitLabel, locale, perNight, t]);

  return (
    <div className={`flex flex-col ${className}`}>
      <div className="flex items-baseline gap-1">
        {showFrom && <span className="text-sm font-normal text-neutral-600">{t('from')}</span>}
        <span className={symbolSizeClasses[size]}>{symbol}</span>
        <span className={`font-bold text-primary ${sizeClasses[size]}`}>{formattedAmount}</span>
      </div>
      {showLabel && (
        <span className="text-xs text-neutral-500" data-testid="price-unit-label">
          {resolvedLabel}
        </span>
      )}
    </div>
  );
}

// Memoize to prevent unnecessary re-renders
export const PriceDisplay = memo(PriceDisplayComponent);
