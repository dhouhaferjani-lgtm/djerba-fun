'use client';

import { useTranslations, useLocale } from 'next-intl';
import { Moon, Home, Plus, Receipt } from 'lucide-react';
import { cn } from '@/lib/utils/cn';

interface SelectedExtra {
  id: string;
  name: string;
  price: number;
  quantity: number;
}

interface NightlyPriceDisplayProps {
  nights: number;
  nightlyPrice: number;
  currency: string;
  guests?: number;
  maxGuests?: number;
  selectedExtras?: SelectedExtra[];
  className?: string;
  showBreakdown?: boolean;
}

export default function NightlyPriceDisplay({
  nights,
  nightlyPrice,
  currency,
  guests,
  maxGuests,
  selectedExtras = [],
  className = '',
  showBreakdown = true,
}: NightlyPriceDisplayProps) {
  const t = useTranslations('accommodation');
  const locale = useLocale();

  const accommodationTotal = nights * nightlyPrice;
  const extrasTotal = selectedExtras.reduce((sum, extra) => sum + extra.price * extra.quantity, 0);
  const grandTotal = accommodationTotal + extrasTotal;

  const formatPrice = (price: number) => {
    return price.toLocaleString(locale, {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    });
  };

  if (nights <= 0) {
    return (
      <div className={cn('rounded-lg border border-neutral-200 bg-neutral-50 p-4', className)}>
        <div className="flex items-center gap-2 text-neutral-500">
          <Moon className="h-5 w-5" />
          <span>{t('select_dates_to_see_price')}</span>
        </div>
        <p className="mt-2 text-2xl font-semibold">
          {formatPrice(nightlyPrice)} {currency}
          <span className="text-sm font-normal text-neutral-500"> / {t('night')}</span>
        </p>
      </div>
    );
  }

  return (
    <div className={cn('rounded-lg border border-neutral-200 bg-white', className)}>
      {/* Header with nightly rate */}
      <div className="border-b border-neutral-100 p-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Home className="h-5 w-5 text-amber-600" />
            <span className="font-medium">{t('whole_property')}</span>
          </div>
          <p className="text-neutral-600">
            {formatPrice(nightlyPrice)} {currency} <span className="text-sm">/ {t('night')}</span>
          </p>
        </div>
        {guests && maxGuests && (
          <p className="mt-1 text-sm text-neutral-500">
            {t('guests_count', { count: guests, max: maxGuests })}
          </p>
        )}
      </div>

      {/* Breakdown */}
      {showBreakdown && (
        <div className="p-4 space-y-3">
          {/* Accommodation */}
          <div className="flex items-center justify-between text-sm">
            <div className="flex items-center gap-2">
              <Moon className="h-4 w-4 text-neutral-400" />
              <span>
                {formatPrice(nightlyPrice)} {currency} × {nights}{' '}
                {nights === 1 ? t('night') : t('nights')}
              </span>
            </div>
            <span className="font-medium">
              {formatPrice(accommodationTotal)} {currency}
            </span>
          </div>

          {/* Extras */}
          {selectedExtras.length > 0 && (
            <>
              <div className="border-t border-neutral-100 pt-3">
                <div className="flex items-center gap-2 mb-2">
                  <Plus className="h-4 w-4 text-neutral-400" />
                  <span className="text-sm text-neutral-600">{t('extras')}</span>
                </div>
                {selectedExtras.map((extra) => (
                  <div key={extra.id} className="flex items-center justify-between text-sm ml-6">
                    <span className="text-neutral-600">
                      {extra.name}
                      {extra.quantity > 1 && ` × ${extra.quantity}`}
                    </span>
                    <span>
                      {formatPrice(extra.price * extra.quantity)} {currency}
                    </span>
                  </div>
                ))}
              </div>
            </>
          )}

          {/* Total */}
          <div className="border-t border-neutral-200 pt-3 mt-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Receipt className="h-5 w-5 text-neutral-600" />
                <span className="font-semibold">{t('total')}</span>
              </div>
              <span className="text-xl font-bold">
                {formatPrice(grandTotal)} {currency}
              </span>
            </div>
          </div>
        </div>
      )}

      {/* Compact view (no breakdown) */}
      {!showBreakdown && (
        <div className="p-4">
          <div className="flex items-center justify-between">
            <span className="text-neutral-600">
              {nights} {nights === 1 ? t('night') : t('nights')}
            </span>
            <span className="text-xl font-bold">
              {formatPrice(grandTotal)} {currency}
            </span>
          </div>
        </div>
      )}
    </div>
  );
}
