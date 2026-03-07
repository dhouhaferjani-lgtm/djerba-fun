'use client';

import { useTranslations } from 'next-intl';
import { useMemo } from 'react';
import type {
  TravelerInfo,
  ListingSummary,
  AvailabilitySlot,
  PersonType,
} from '@djerba-fun/schemas';
import CouponInput from './CouponInput';
import { PriceBreakdownTable, type PriceBreakdownItem } from './PriceBreakdownTable';

interface Extra {
  id: string;
  name: string;
  quantity: number;
  price: number;
}

interface ExtendedTraveler extends TravelerInfo {
  personType?: string;
}

interface BookingReviewProps {
  listing: ListingSummary;
  slot: AvailabilitySlot;
  travelerInfo: TravelerInfo;
  allTravelers?: ExtendedTraveler[];
  extras: Extra[];
  currency: string;
  quantity: number;
  personTypeBreakdown?: Record<string, number>;
  couponCode?: string;
  couponDiscount?: number;
  onCouponApply?: (code: string, discountAmount: number) => void;
  onCouponRemove?: () => void;
  onEditTraveler?: () => void;
  onEditExtras?: () => void;
  onConfirm: () => void;
  onBack?: () => void;
  isProcessing?: boolean;
  locale?: string;
  isBillingOnly?: boolean;
}

export function BookingReview({
  listing,
  slot,
  travelerInfo,
  allTravelers,
  extras,
  currency,
  quantity,
  personTypeBreakdown,
  couponCode,
  couponDiscount,
  onCouponApply,
  onCouponRemove,
  onEditTraveler,
  onEditExtras,
  onConfirm,
  onBack,
  isProcessing = false,
  locale = 'en',
  isBillingOnly = false,
}: BookingReviewProps) {
  const t = useTranslations('booking');
  const tDashboard = useTranslations('dashboard');

  // Get person types from listing pricing
  const getPersonTypes = (): PersonType[] => {
    const pricing = listing.pricing || {};
    const personTypes = pricing.personTypes;

    // Get base price for fallback
    const basePrice =
      pricing.displayPrice || pricing.tndPrice || slot.displayPrice || slot.tndPrice || 0;
    const numericPrice = typeof basePrice === 'string' ? parseFloat(basePrice) : basePrice;

    if (personTypes && Array.isArray(personTypes) && personTypes.length > 0) {
      // Use actual prices from API (displayPrice, tndPrice, or price)
      const typesWithPrices = personTypes.map((pt) => ({
        ...pt,
        price: Number(pt.displayPrice ?? pt.tndPrice ?? pt.price ?? numericPrice),
      }));

      return typesWithPrices;
    }

    // Fallback: only return adult with base price (don't invent child/infant pricing)
    // This matches the backend's fallback behavior in PriceCalculationService.php
    return [
      {
        key: 'adult',
        label: { en: 'Adult', fr: 'Adulte' },
        price: numericPrice,
        minAge: 18,
        maxAge: null,
        minQuantity: 0,
        maxQuantity: null,
      },
    ];
  };

  const getPersonTypeLabel = (type: PersonType): string => {
    if (typeof type.label === 'string') return type.label;
    const labelObj = type.label as { en?: string; fr?: string };
    return labelObj[locale as 'en' | 'fr'] || labelObj.en || type.key;
  };

  const formatPrice = (amount: number) => {
    // Prices are stored as whole amounts (e.g., 65 = €65), not cents
    const currencyCode = currency || 'EUR';
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currencyCode,
    }).format(amount);
  };

  const formatDateTime = (datetime: string) => {
    const date = new Date(datetime);
    return new Intl.DateTimeFormat('en-US', {
      dateStyle: 'full',
      timeStyle: 'short',
    }).format(date);
  };

  // Calculate subtotal from breakdown if available, otherwise use simple quantity * price
  const calculateSubtotal = () => {
    if (personTypeBreakdown && Object.keys(personTypeBreakdown).length > 0) {
      const personTypes = getPersonTypes();
      let total = 0;

      for (const type of personTypes) {
        const qty = personTypeBreakdown[type.key] || 0;
        const price = type.price ?? 0;
        total += price * qty;
      }

      return total;
    }

    // Fallback: simple quantity * display price (dual pricing)
    const unitPrice =
      slot.displayPrice ||
      slot.tndPrice ||
      listing.pricing?.displayPrice ||
      listing.pricing?.tndPrice ||
      0;
    return unitPrice * quantity;
  };

  // Get breakdown details for display
  const getBreakdownDetails = () => {
    if (!personTypeBreakdown || Object.keys(personTypeBreakdown).length === 0) {
      return null;
    }
    const personTypes = getPersonTypes();
    return personTypes
      .filter((type) => (personTypeBreakdown[type.key] || 0) > 0)
      .map((type) => ({
        label: getPersonTypeLabel(type),
        quantity: personTypeBreakdown[type.key] || 0,
        price: type.price ?? 0,
        total: (type.price ?? 0) * (personTypeBreakdown[type.key] || 0),
      }));
  };

  const calculateExtrasTotal = () => {
    return extras.reduce((total, extra) => total + extra.price * extra.quantity, 0);
  };

  const calculateTotal = () => {
    const subtotal = calculateSubtotal() + calculateExtrasTotal();
    const discount = couponDiscount || 0;
    return Math.max(0, subtotal - discount);
  };

  // Build breakdown items for PriceBreakdownTable
  const breakdownItems = useMemo((): PriceBreakdownItem[] => {
    const items: PriceBreakdownItem[] = [];
    const personTypes = getPersonTypes();

    // Add person types with qty > 0
    if (personTypeBreakdown && Object.keys(personTypeBreakdown).length > 0) {
      for (const type of personTypes) {
        const qty = personTypeBreakdown[type.key] || 0;
        if (qty > 0) {
          items.push({
            type: 'person',
            key: type.key,
            label: getPersonTypeLabel(type),
            quantity: qty,
            unitPrice: type.price ?? 0,
            subtotal: (type.price ?? 0) * qty,
          });
        }
      }
    } else if (quantity > 0) {
      // Fallback: show as single line item
      const unitPrice =
        slot.displayPrice ||
        slot.tndPrice ||
        listing.pricing?.displayPrice ||
        listing.pricing?.tndPrice ||
        0;
      items.push({
        type: 'person',
        key: 'guests',
        label: t('guests') || 'Guests',
        quantity: quantity,
        unitPrice: unitPrice,
        subtotal: unitPrice * quantity,
      });
    }

    // Add extras with qty > 0
    for (const extra of extras) {
      if (extra.quantity > 0) {
        items.push({
          type: 'extra',
          key: extra.id,
          label: extra.name,
          quantity: extra.quantity,
          unitPrice: extra.price,
          subtotal: extra.price * extra.quantity,
        });
      }
    }

    return items;
  }, [personTypeBreakdown, extras, quantity, slot, listing.pricing, locale]);

  // Calculate total for breakdown table (before coupon)
  const breakdownTotal = useMemo(() => {
    return breakdownItems.reduce((sum, item) => sum + item.subtotal, 0);
  }, [breakdownItems]);

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
            <span className="font-medium text-gray-700">
              {typeof listing.title === 'string'
                ? listing.title
                : (listing.title as { en?: string; fr?: string })?.en ||
                  (listing.title as { en?: string; fr?: string })?.fr ||
                  'Activity'}
            </span>
          </div>
          <div className="text-sm text-gray-600">
            <p>
              <span className="font-medium">{t('date_time')}:</span> {formatDateTime(slot.start)}
            </p>
            {listing.duration && (
              <p className="mt-1">
                <span className="font-medium">{t('duration')}:</span>{' '}
                {`${listing.duration.value} ${listing.duration.unit}`}
              </p>
            )}
            {listing.location && (
              <p className="mt-1">
                <span className="font-medium">{t('location')}:</span>{' '}
                {(listing.location as { name?: string; address?: string }).name ||
                  (listing.location as { name?: string; address?: string }).address ||
                  t('not_specified')}
              </p>
            )}
          </div>
        </div>
      </div>

      {/* Billing Contact / Traveler Information */}
      <div className="bg-white border border-gray-200 rounded-lg p-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-semibold text-lg text-gray-900">
            {isBillingOnly ? t('billing_contact') || 'Billing Contact' : t('traveler_info')}
          </h3>
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

        {/* Billing-only mode: show single contact with note about participant entry */}
        {isBillingOnly ? (
          <div className="space-y-4">
            <div className="space-y-2 text-sm text-gray-600">
              <p className="font-medium text-gray-900">
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
            {quantity > 1 && (
              <div className="mt-3 p-3 bg-success-light border border-success/20 rounded-lg">
                <p className="text-sm text-success-dark">
                  {t('participant_entry_note') ||
                    `You'll be able to enter names for all ${quantity} participants after completing the booking.`}
                </p>
              </div>
            )}
          </div>
        ) : allTravelers && allTravelers.length > 1 ? (
          <div className="space-y-4">
            {allTravelers.map((traveler, index) => {
              const personType = traveler.personType
                ? getPersonTypes().find((pt) => pt.key === traveler.personType)
                : null;
              const typeLabel = personType ? getPersonTypeLabel(personType) : null;

              return (
                <div key={index} className={index > 0 ? 'pt-3 border-t border-gray-100' : ''}>
                  <div className="flex items-center gap-2 mb-1">
                    <span className="text-sm font-medium text-gray-900">
                      {traveler.firstName} {traveler.lastName}
                    </span>
                    {index === 0 && (
                      <span className="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded">
                        Primary Contact
                      </span>
                    )}
                    {typeLabel && (
                      <span className="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                        {typeLabel}
                      </span>
                    )}
                  </div>
                  {index === 0 && (
                    <div className="space-y-1 text-sm text-gray-600">
                      <p>{traveler.email}</p>
                      {traveler.phone && <p>{traveler.phone}</p>}
                    </div>
                  )}
                </div>
              );
            })}
            {travelerInfo.specialRequests && (
              <div className="pt-3 border-t border-gray-100">
                <p className="text-sm font-medium text-gray-700">{t('special_requests')}:</p>
                <p className="text-sm text-gray-600 mt-1">{travelerInfo.specialRequests}</p>
              </div>
            )}
          </div>
        ) : (
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
        )}
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

      {/* Coupon Input */}
      {onCouponApply && onCouponRemove && (
        <div>
          <CouponInput
            listingId={listing.id}
            amount={calculateSubtotal() + calculateExtrasTotal()}
            onApply={onCouponApply}
            onRemove={onCouponRemove}
            appliedCode={couponCode}
            appliedDiscount={couponDiscount}
          />
        </div>
      )}

      {/* Price Breakdown */}
      <div className="bg-gray-50 border border-gray-200 rounded-lg p-6">
        {breakdownItems.length > 0 ? (
          <>
            <PriceBreakdownTable
              items={breakdownItems}
              currency={currency}
              total={breakdownTotal}
              compact={false}
              showTitle={true}
            />
            {/* Coupon Discount - shown separately after breakdown */}
            {couponDiscount && couponDiscount > 0 && (
              <div className="mt-4 pt-4 border-t border-neutral-200">
                <div className="flex justify-between text-sm">
                  <span className="text-success font-medium">{tDashboard('discount')}</span>
                  <span className="font-medium text-success">-{formatPrice(couponDiscount)}</span>
                </div>
                <div className="flex justify-between mt-3 pt-3 border-t border-neutral-200">
                  <span className="font-bold text-gray-900">{t('grand_total')}</span>
                  <span className="text-xl font-bold text-primary" data-testid="review-total-price">
                    {formatPrice(calculateTotal())}
                  </span>
                </div>
              </div>
            )}
          </>
        ) : (
          <div className="space-y-3">
            <h3 className="font-semibold text-lg text-gray-900 mb-4">{t('price_breakdown')}</h3>
            <div className="flex justify-between text-sm">
              <span className="text-gray-600">
                {t('base_price')} × {quantity} {quantity === 1 ? 'guest' : 'guests'}
              </span>
              <span className="font-medium text-gray-900">{formatPrice(calculateSubtotal())}</span>
            </div>
            {couponDiscount && couponDiscount > 0 && (
              <div className="flex justify-between text-sm">
                <span className="text-success">{tDashboard('discount')}</span>
                <span className="font-medium text-success">-{formatPrice(couponDiscount)}</span>
              </div>
            )}
            <div className="border-t pt-3">
              <div className="flex justify-between">
                <span className="font-bold text-gray-900">{t('total')}</span>
                <span className="text-xl font-bold text-primary" data-testid="review-total-price">
                  {formatPrice(calculateTotal())}
                </span>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Action Buttons */}
      <div className="flex gap-4 pt-4">
        {onBack && (
          <button
            type="button"
            onClick={onBack}
            disabled={isProcessing}
            data-testid="back-to-billing"
            className="flex-1 px-6 py-3 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {t('back')}
          </button>
        )}
        <button
          type="button"
          onClick={onConfirm}
          disabled={isProcessing}
          data-testid="create-hold-button"
          className="flex-1 px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {isProcessing ? t('processing') : t('confirm_and_pay')}
        </button>
      </div>
    </div>
  );
}
