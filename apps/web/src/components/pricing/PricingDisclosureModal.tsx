'use client';

import { useTranslations } from 'next-intl';
import { Button } from '@go-adventure/ui';
import { X, AlertCircle } from 'lucide-react';

export interface PriceChangeInfo {
  oldPrice: number;
  newPrice: number;
  oldCurrency: string;
  newCurrency: string;
  billingCountry: string;
  billingCountryName: string;
}

interface PricingDisclosureModalProps {
  isOpen: boolean;
  priceChange: PriceChangeInfo;
  onAccept: () => void;
  onCancel: () => void;
}

export function PricingDisclosureModal({
  isOpen,
  priceChange,
  onAccept,
  onCancel,
}: PricingDisclosureModalProps) {
  const t = useTranslations('booking');

  if (!isOpen) return null;

  const formatPrice = (amount: number, currency: string) => {
    if (currency === 'EUR' || currency === '€') {
      return `€${amount.toFixed(2)}`;
    }
    if (currency === 'USD' || currency === '$') {
      return `$${amount.toFixed(2)}`;
    }
    if (currency === 'TND') {
      return `${amount.toFixed(2)} TND`;
    }
    return `${amount.toFixed(2)} ${currency}`;
  };

  return (
    <>
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-black bg-opacity-50 z-50"
        onClick={onCancel}
        aria-hidden="true"
      />

      {/* Modal */}
      <div
        className="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="disclosure-modal-title"
      >
        <div
          className="bg-white rounded-lg shadow-xl max-w-md w-full p-6 relative"
          data-testid="price-change-disclosure"
          onClick={(e) => e.stopPropagation()}
        >
          {/* Close button */}
          <button
            onClick={onCancel}
            className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors"
            aria-label="Close"
          >
            <X className="w-5 h-5" />
          </button>

          {/* Icon */}
          <div className="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-yellow-100 rounded-full">
            <AlertCircle className="w-6 h-6 text-yellow-600" />
          </div>

          {/* Title */}
          <h2
            id="disclosure-modal-title"
            className="text-xl font-bold text-gray-900 text-center mb-2"
            data-testid="disclosure-modal-title"
          >
            {t('price_change_title') || 'Price Adjustment'}
          </h2>

          {/* Explanation */}
          <div className="mb-6 space-y-3">
            <p className="text-gray-700 text-center" data-testid="disclosure-modal-explanation">
              {t('price_change_explanation', {
                country: priceChange.billingCountryName,
                currency: priceChange.newCurrency,
              }) ||
                `Your final price has been adjusted to ${priceChange.newCurrency} based on your billing address in ${priceChange.billingCountryName}. We adapt pricing to ensure fair access across regions.`}
            </p>

            {/* Price Comparison */}
            <div className="bg-gray-50 rounded-lg p-4 space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-sm text-gray-600">
                  {t('previous_price') || 'Previous price:'}
                </span>
                <span
                  className="text-sm font-medium text-gray-500 line-through"
                  data-testid="disclosure-old-price"
                >
                  {formatPrice(priceChange.oldPrice, priceChange.oldCurrency)}
                </span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-sm font-semibold text-gray-900">
                  {t('new_price') || 'New price:'}
                </span>
                <span className="text-lg font-bold text-primary" data-testid="disclosure-new-price">
                  {formatPrice(priceChange.newPrice, priceChange.newCurrency)}
                </span>
              </div>
            </div>

            <p className="text-xs text-gray-500 text-center">
              {t('price_lock_note') ||
                'This price will be locked once you accept and will not change during your booking session.'}
            </p>
          </div>

          {/* Action Buttons */}
          <div className="flex gap-3">
            <Button
              variant="outline"
              onClick={onCancel}
              data-testid="disclosure-cancel-button"
              className="flex-1"
            >
              {t('go_back') || 'Go Back'}
            </Button>
            <Button onClick={onAccept} data-testid="disclosure-accept-button" className="flex-1">
              {t('accept_and_continue') || 'Accept & Continue'}
            </Button>
          </div>
        </div>
      </div>
    </>
  );
}
