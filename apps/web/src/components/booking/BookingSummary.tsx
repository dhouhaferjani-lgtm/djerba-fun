'use client';

import { useTranslations } from 'next-intl';
import { Calendar, Clock, Users, MapPin } from 'lucide-react';
import { format, parseISO } from 'date-fns';
import { enUS, fr } from 'date-fns/locale';
import { useLocale } from 'next-intl';
import { resolveTranslation } from '@/lib/utils/translate';
import { normalizeMediaUrl } from '@/lib/utils/image';
import type {
  BookingHold,
  ListingSummary,
  AvailabilitySlot,
  ListingExtraForBooking,
} from '@go-adventure/schemas';

interface SelectedExtra {
  id: string;
  name: string;
  quantity: number;
  price: number;
}

interface BookingSummaryProps {
  hold: BookingHold;
  listing: ListingSummary;
  slot: AvailabilitySlot;
  selectedExtras?: SelectedExtra[];
  currency: string;
}

export function BookingSummary({
  hold,
  listing,
  slot,
  selectedExtras = [],
  currency,
}: BookingSummaryProps) {
  const t = useTranslations('booking');
  const tCommon = useTranslations('common');
  const locale = useLocale();
  const dateLocale = locale === 'fr' ? fr : enUS;

  // Resolve translated fields
  const tr = (field: any) => resolveTranslation(field, locale);
  const listingTitle = tr(listing.title);
  const listingLocation =
    typeof listing.location === 'string' ? listing.location : tr(listing.location);

  // Calculate prices (check listing.pricing first, then slot, matching BookingReview logic)
  const pricing = listing.pricing || {};
  const rawBasePrice =
    pricing.displayPrice || pricing.tndPrice || slot.displayPrice || slot.tndPrice || 0;
  const basePrice = typeof rawBasePrice === 'string' ? parseFloat(rawBasePrice) : rawBasePrice;
  const quantity = hold.quantity || 1;

  // Get breakdown details from listing.pricing.personTypes + hold.personTypeBreakdown
  const getBreakdownDetails = () => {
    const breakdown = (hold as any).personTypeBreakdown;
    const personTypes = (pricing as any).personTypes;

    // Only show breakdown if BOTH are available
    if (!breakdown || !personTypes || !Array.isArray(personTypes) || personTypes.length === 0) {
      return null;
    }

    return personTypes
      .filter((type: any) => (breakdown[type.key] || 0) > 0)
      .map((type: any) => ({
        key: type.key,
        label:
          typeof type.label === 'string'
            ? type.label
            : type.label?.[locale] || type.label?.en || type.key,
        quantity: breakdown[type.key] || 0,
        price: Number(type.displayPrice ?? type.tndPrice ?? type.price ?? 0),
      }));
  };

  // Calculate subtotal from breakdown details (accurate) or fallback to priceSnapshot/simple calc
  const breakdownDetails = getBreakdownDetails();
  const subtotal = breakdownDetails
    ? breakdownDetails.reduce((sum, item) => sum + item.price * item.quantity, 0)
    : (hold as any).priceSnapshot != null
      ? Number((hold as any).priceSnapshot)
      : basePrice * quantity;

  const extrasTotal = selectedExtras.reduce((total, extra) => {
    return total + extra.price * extra.quantity;
  }, 0);

  const total = subtotal + extrasTotal;

  // Format date and time
  const startDate = parseISO(slot.start);
  const endTime = parseISO(slot.end);
  const formattedDate = format(startDate, 'EEEE, MMMM d, yyyy', { locale: dateLocale });
  const formattedTime = `${format(startDate, 'HH:mm')} - ${format(endTime, 'HH:mm')}`;

  // Get main listing image
  const mainImage = listing.media?.[0];

  return (
    <div className="bg-white rounded-lg border border-neutral-200 overflow-hidden">
      {/* Listing Preview */}
      {mainImage && (
        <div className="relative h-48 w-full">
          <img
            src={normalizeMediaUrl(mainImage.url)}
            alt={mainImage.alt || listing.title}
            className="w-full h-full object-cover"
          />
        </div>
      )}

      <div className="p-6 space-y-6">
        {/* Listing Title */}
        <div>
          <h3 className="text-xl font-bold text-neutral-900 mb-1">{listingTitle}</h3>
          {listingLocation && (
            <div className="flex items-center gap-1 text-sm text-neutral-600">
              <MapPin className="h-4 w-4" />
              <span>{listingLocation}</span>
            </div>
          )}
        </div>

        {/* Booking Details */}
        <div className="space-y-3 border-t border-neutral-200 pt-4">
          <h4 className="font-semibold text-neutral-900">{t('booking_details')}</h4>

          {/* Date */}
          <div className="flex items-start gap-3">
            <Calendar className="h-5 w-5 text-neutral-600 flex-shrink-0 mt-0.5" />
            <div>
              <div className="text-sm font-medium text-neutral-900">{t('date')}</div>
              <div className="text-sm text-neutral-600">{formattedDate}</div>
            </div>
          </div>

          {/* Time */}
          <div className="flex items-start gap-3">
            <Clock className="h-5 w-5 text-neutral-600 flex-shrink-0 mt-0.5" />
            <div>
              <div className="text-sm font-medium text-neutral-900">{t('time')}</div>
              <div className="text-sm text-neutral-600">{formattedTime}</div>
            </div>
          </div>

          {/* Guests */}
          <div className="flex items-start gap-3">
            <Users className="h-5 w-5 text-neutral-600 flex-shrink-0 mt-0.5" />
            <div>
              <div className="text-sm font-medium text-neutral-900">{t('guests')}</div>
              <div className="text-sm text-neutral-600">
                {breakdownDetails
                  ? breakdownDetails.map((item, idx, arr) => (
                      <span key={item.key}>
                        {item.quantity} {item.label}
                        {idx < arr.length - 1 ? ', ' : ''}
                      </span>
                    ))
                  : `${quantity} ${quantity === 1 ? t('guest') : t('guests')}`}
              </div>
            </div>
          </div>
        </div>

        {/* Price Breakdown */}
        <div className="space-y-3 border-t border-neutral-200 pt-4">
          <h4 className="font-semibold text-neutral-900">{t('price_breakdown')}</h4>

          {/* Price breakdown by person type OR simple display */}
          {breakdownDetails ? (
            breakdownDetails.map((item) => (
              <div key={item.key} className="flex justify-between text-sm">
                <span className="text-neutral-600">
                  {item.label} × {item.quantity}
                  {item.price > 0 && (
                    <span className="text-neutral-400 ml-1">
                      @ {currency} {item.price.toFixed(2)}
                    </span>
                  )}
                </span>
                <span className="font-medium text-neutral-900">
                  {currency} {(item.price * item.quantity).toFixed(2)}
                </span>
              </div>
            ))
          ) : (
            <div className="flex justify-between text-sm">
              <span className="text-neutral-600">
                {currency} {basePrice.toFixed(2)} × {quantity}
              </span>
              <span className="font-medium text-neutral-900">
                {currency} {subtotal.toFixed(2)}
              </span>
            </div>
          )}

          {/* Extras */}
          {selectedExtras.map((extra) => (
            <div key={extra.id} className="flex justify-between text-sm">
              <span className="text-neutral-600">
                {extra.name} × {extra.quantity}
              </span>
              <span className="font-medium text-neutral-900">
                {currency} {(extra.price * extra.quantity).toFixed(2)}
              </span>
            </div>
          ))}

          {/* Total */}
          <div className="flex justify-between text-base font-bold text-neutral-900 border-t border-neutral-200 pt-3">
            <span>{t('total')}</span>
            <span>
              {currency} {total.toFixed(2)}
            </span>
          </div>
        </div>

        {/* Trust badges */}
        <div className="bg-success-light rounded-lg p-4 border border-success/20">
          <div className="flex items-start gap-2">
            <svg
              className="h-5 w-5 text-success flex-shrink-0 mt-0.5"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fillRule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clipRule="evenodd"
              />
            </svg>
            <div className="text-sm">
              <p className="font-medium text-success-dark">{t('secure_booking')}</p>
              <p className="text-success-dark/80 text-xs mt-1">{t('secure_booking_message')}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
