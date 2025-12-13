'use client';

import { useTranslations } from 'next-intl';
import type { TravelerInfo, ListingSummary, AvailabilitySlot } from '@go-adventure/schemas';

interface Extra {
  id: string;
  name: string;
  quantity: number;
  price: number;
}

interface BookingReviewProps {
  listing: ListingSummary;
  slot: AvailabilitySlot;
  travelerInfo: TravelerInfo;
  extras: Extra[];
  currency: string;
  onEditTraveler?: () => void;
  onEditExtras?: () => void;
  onConfirm: () => void;
  onBack?: () => void;
  isProcessing?: boolean;
}

export function BookingReview({
  listing,
  slot,
  travelerInfo,
  extras,
  currency,
  onEditTraveler,
  onEditExtras,
  onConfirm,
  onBack,
  isProcessing = false,
}: BookingReviewProps) {
  const t = useTranslations('booking');

  const formatPrice = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency,
    }).format(amount / 100);
  };

  const formatDateTime = (datetime: string) => {
    const date = new Date(datetime);
    return new Intl.DateTimeFormat('en-US', {
      dateStyle: 'full',
      timeStyle: 'short',
    }).format(date);
  };

  const calculateSubtotal = () => {
    return slot.price;
  };

  const calculateExtrasTotal = () => {
    return extras.reduce((total, extra) => total + extra.price * extra.quantity, 0);
  };

  const calculateTotal = () => {
    return calculateSubtotal() + calculateExtrasTotal();
  };

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-900 mb-2">{t('review_title')}</h2>
        <p className="text-gray-600">{t('review_subtitle')}</p>
      </div>

      {/* Listing Info */}
      <div className="bg-white border border-gray-200 rounded-lg p-6">
        <h3 className="font-semibold text-lg text-gray-900 mb-4">{t('activity_details')}</h3>
        <div className="space-y-3">
          <div>
            <span className="font-medium text-gray-700">{listing.title}</span>
          </div>
          <div className="text-sm text-gray-600">
            <p>
              <span className="font-medium">{t('date_time')}:</span> {formatDateTime(slot.start)}
            </p>
            <p className="mt-1">
              <span className="font-medium">{t('duration')}:</span>{' '}
              {listing.duration
                ? `${listing.duration.value} ${listing.duration.unit}`
                : t('not_specified')}
            </p>
            <p className="mt-1">
              <span className="font-medium">{t('location')}:</span> {listing.location.name}
            </p>
          </div>
        </div>
      </div>

      {/* Traveler Information */}
      <div className="bg-white border border-gray-200 rounded-lg p-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-semibold text-lg text-gray-900">{t('traveler_info')}</h3>
          {onEditTraveler && (
            <button
              type="button"
              onClick={onEditTraveler}
              className="text-sm text-primary hover:text-primary/80 font-medium"
            >
              {t('edit')}
            </button>
          )}
        </div>
        <div className="space-y-2 text-sm text-gray-600">
          <p>
            {travelerInfo.firstName} {travelerInfo.lastName}
          </p>
          <p>{travelerInfo.email}</p>
          {travelerInfo.phone && <p>{travelerInfo.phone}</p>}
          {travelerInfo.specialRequests && (
            <div className="mt-3 pt-3 border-t">
              <p className="font-medium text-gray-700">{t('special_requests')}:</p>
              <p className="mt-1">{travelerInfo.specialRequests}</p>
            </div>
          )}
        </div>
      </div>

      {/* Extras */}
      {extras.length > 0 && (
        <div className="bg-white border border-gray-200 rounded-lg p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-semibold text-lg text-gray-900">{t('extras')}</h3>
            {onEditExtras && (
              <button
                type="button"
                onClick={onEditExtras}
                className="text-sm text-primary hover:text-primary/80 font-medium"
              >
                {t('edit')}
              </button>
            )}
          </div>
          <div className="space-y-2">
            {extras.map((extra) => (
              <div key={extra.id} className="flex justify-between text-sm">
                <span className="text-gray-600">
                  {extra.name} × {extra.quantity}
                </span>
                <span className="font-medium text-gray-900">
                  {formatPrice(extra.price * extra.quantity)}
                </span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Price Breakdown */}
      <div className="bg-gray-50 border border-gray-200 rounded-lg p-6">
        <h3 className="font-semibold text-lg text-gray-900 mb-4">{t('price_breakdown')}</h3>
        <div className="space-y-3">
          <div className="flex justify-between text-sm">
            <span className="text-gray-600">{t('base_price')}</span>
            <span className="font-medium text-gray-900">{formatPrice(calculateSubtotal())}</span>
          </div>
          {extras.length > 0 && (
            <div className="flex justify-between text-sm">
              <span className="text-gray-600">{t('extras_total')}</span>
              <span className="font-medium text-gray-900">
                {formatPrice(calculateExtrasTotal())}
              </span>
            </div>
          )}
          <div className="border-t pt-3">
            <div className="flex justify-between">
              <span className="font-bold text-gray-900">{t('total')}</span>
              <span className="text-xl font-bold text-primary">
                {formatPrice(calculateTotal())}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Action Buttons */}
      <div className="flex gap-4 pt-4">
        {onBack && (
          <button
            type="button"
            onClick={onBack}
            disabled={isProcessing}
            className="flex-1 px-6 py-3 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {t('back')}
          </button>
        )}
        <button
          type="button"
          onClick={onConfirm}
          disabled={isProcessing}
          className="flex-1 px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {isProcessing ? t('processing') : t('confirm_and_pay')}
        </button>
      </div>
    </div>
  );
}
