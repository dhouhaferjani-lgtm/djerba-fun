'use client';

import { useTranslations } from 'next-intl';
import { Info } from 'lucide-react';
import { useState } from 'react';

interface PriceDisplayProps {
  amount: number;
  currency: string;
  location?: string; // e.g., "Tunisia", "France"
  showLocationHint?: boolean;
  showCurrencyInfo?: boolean;
  className?: string;
  'data-testid'?: string;
}

export function PriceDisplay({
  amount,
  currency,
  location,
  showLocationHint = false,
  showCurrencyInfo = false,
  className = '',
  'data-testid': testId = 'listing-price',
}: PriceDisplayProps) {
  const t = useTranslations('pricing');
  const [showTooltip, setShowTooltip] = useState(false);

  const formatPrice = (value: number, curr: string) => {
    if (curr === 'EUR' || curr === '€') {
      return `€${value.toFixed(2)}`;
    }
    if (curr === 'USD' || curr === '$') {
      return `$${value.toFixed(2)}`;
    }
    if (curr === 'TND') {
      return `${value.toFixed(2)} TND`;
    }
    return `${value.toFixed(2)} ${curr}`;
  };

  return (
    <div className={`flex flex-col gap-1 ${className}`}>
      <div className="flex items-center gap-2">
        <span className="text-2xl font-bold text-primary" data-testid={testId}>
          {formatPrice(amount, currency)}
        </span>

        {showCurrencyInfo && (
          <div className="relative">
            <button
              type="button"
              onClick={() => setShowTooltip(!showTooltip)}
              onMouseEnter={() => setShowTooltip(true)}
              onMouseLeave={() => setShowTooltip(false)}
              className="text-gray-400 hover:text-gray-600 transition-colors"
              data-testid="currency-info-tooltip"
              aria-label="Currency information"
            >
              <Info className="w-5 h-5" />
            </button>

            {showTooltip && (
              <div
                className="absolute z-10 left-0 top-full mt-2 w-64 p-3 bg-white border border-gray-200 rounded-lg shadow-lg text-sm"
                data-testid="currency-tooltip-content"
              >
                <p className="text-gray-700">
                  {t('ppp_explanation', { currency }) ||
                    `Prices are shown in ${currency} based on your location. We use purchasing power parity to ensure fair pricing across regions.`}
                </p>
              </div>
            )}
          </div>
        )}
      </div>

      {showLocationHint && location && (
        <div
          className="flex items-center gap-1 text-sm text-gray-600"
          data-testid="price-location-hint"
        >
          <span>
            {t('price_shown_in', { currency, location }) ||
              `Price shown in ${currency} (based on your location: ${location})`}
          </span>
        </div>
      )}
    </div>
  );
}
