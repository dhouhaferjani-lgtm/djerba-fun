'use client';

import { useTranslations } from 'next-intl';
import { useLocale } from 'next-intl';
import { Calendar, Clock, Users, Package } from 'lucide-react';
import { format, parseISO } from 'date-fns';
import { enUS, fr } from 'date-fns/locale';
import { resolveTranslation } from '@/lib/utils/translate';
import type { Cart } from '@/lib/api/client';

interface CartCheckoutSummaryProps {
  cart: Cart | null | undefined;
  currency: string;
}

export function CartCheckoutSummary({ cart, currency }: CartCheckoutSummaryProps) {
  const t = useTranslations('cart');
  const tBooking = useTranslations('booking');
  const locale = useLocale();
  const dateLocale = locale === 'fr' ? fr : enUS;

  if (!cart || cart.itemCount === 0) {
    return (
      <div className="bg-white rounded-lg border border-neutral-200 p-6">
        <p className="text-neutral-600 text-center">{t('empty_title')}</p>
      </div>
    );
  }

  const tr = (field: any) => resolveTranslation(field, locale);

  return (
    <div className="bg-white rounded-lg border border-neutral-200 overflow-hidden">
      <div className="p-6 space-y-6">
        {/* Order Summary Title */}
        <div>
          <h3 className="text-xl font-bold text-neutral-900">{t('checkout.order_summary')}</h3>
        </div>

        {/* Cart Items */}
        <div className="space-y-4 border-t border-neutral-200 pt-4">
          {cart.items.map((item, index) => {
            // CartItem has flattened data (not nested hold/listing/slot)
            const listingTitle = tr(item.listingTitle);

            // Format date and time
            const startDate = item.slotStart ? parseISO(item.slotStart) : null;
            const endTime = item.slotEnd ? parseISO(item.slotEnd) : null;
            const formattedDate = startDate
              ? format(startDate, 'MMM d, yyyy', { locale: dateLocale })
              : '';
            const formattedTime =
              startDate && endTime
                ? `${format(startDate, 'HH:mm')} - ${format(endTime, 'HH:mm')}`
                : '';

            // Get quantity from person type breakdown or item.quantity
            const quantity =
              item.personTypeBreakdown && typeof item.personTypeBreakdown === 'object'
                ? Object.values(item.personTypeBreakdown).reduce(
                    (sum: number, qty) => sum + (Number(qty) || 0),
                    0
                  )
                : item.quantity || 1;

            return (
              <div
                key={item.id}
                className={`${index > 0 ? 'border-t border-neutral-200 pt-4' : ''}`}
              >
                {/* Listing Title */}
                <h4 className="font-semibold text-neutral-900 mb-2">{listingTitle}</h4>

                {/* Listing Details */}
                <div className="space-y-2 text-sm">
                  <div className="flex items-center gap-2 text-neutral-600">
                    <Calendar className="h-4 w-4" />
                    <span>{formattedDate}</span>
                  </div>

                  <div className="flex items-center gap-2 text-neutral-600">
                    <Clock className="h-4 w-4" />
                    <span>{formattedTime}</span>
                  </div>

                  <div className="flex items-center gap-2 text-neutral-600">
                    <Users className="h-4 w-4" />
                    <span>
                      {quantity} {quantity === 1 ? tBooking('guest') : tBooking('guests')}
                    </span>
                  </div>
                </div>

                {/* Price Breakdown (for single item, show details) */}
                {cart.itemCount === 1 && (
                  <div className="mt-3 space-y-1 text-sm border-t border-neutral-100 pt-3">
                    {/* For accommodation: show nights breakdown */}
                    {item.pricingModel === 'per_night' && item.nights && item.nightlyRate ? (
                      <div className="flex justify-between text-neutral-600">
                        <span>
                          {t('night')} × {item.nights}
                        </span>
                        <span>
                          {currency} {(item.nightlyRate * item.nights).toFixed(2)}
                        </span>
                      </div>
                    ) : (
                      /* For other service types: show person type breakdown */
                      item.personTypeBreakdown &&
                      typeof item.personTypeBreakdown === 'object' &&
                      Object.entries(item.personTypeBreakdown).map(([type, qty]) => {
                        if (!qty || Number(qty) === 0) return null;
                        const typeLabel =
                          type === 'adult'
                            ? t('person_type.adult')
                            : type === 'child'
                              ? t('person_type.child')
                              : type === 'infant'
                                ? t('person_type.infant')
                                : type;
                        return (
                          <div key={type} className="flex justify-between text-neutral-600">
                            <span className="capitalize">
                              {typeLabel} × {qty}
                            </span>
                            <span>
                              {currency}{' '}
                              {(
                                ((item.personTypePricing?.[type] ?? item.unitPrice) || 0) *
                                Number(qty)
                              ).toFixed(2)}
                            </span>
                          </div>
                        );
                      })
                    )}
                  </div>
                )}

                {/* Extras (for single item, show details) */}
                {cart.itemCount === 1 && item.extras && item.extras.length > 0 && (
                  <div className="mt-3 space-y-1 text-sm border-t border-neutral-100 pt-3">
                    <div className="flex items-center gap-1 text-neutral-700 font-medium mb-1">
                      <Package className="h-3 w-3" />
                      <span>{t('extras')}</span>
                    </div>
                    {item.extras.map(
                      (extra: { id: string; name?: string; price?: number; quantity?: number }) => (
                        <div key={extra.id} className="flex justify-between text-neutral-600">
                          <span>
                            {extra.name || 'Extra'} × {extra.quantity || 1}
                          </span>
                          <span>
                            {currency} {((extra.price || 0) * (extra.quantity || 1)).toFixed(2)}
                          </span>
                        </div>
                      )
                    )}
                  </div>
                )}

                {/* Item Price */}
                <div className="mt-3 flex justify-between text-sm font-medium">
                  <span className="text-neutral-600">{tBooking('subtotal')}</span>
                  <span className="flex items-baseline gap-1">
                    <span className="text-xs font-medium text-neutral-600">{currency}</span>
                    <span className="font-bold text-neutral-900">
                      {(item.subtotal || item.total || 0).toFixed(2)}
                    </span>
                  </span>
                </div>
              </div>
            );
          })}
        </div>

        {/* Price Breakdown */}
        <div className="space-y-3 border-t border-neutral-200 pt-4">
          <h4 className="font-semibold text-neutral-900">{tBooking('price_breakdown')}</h4>

          {/* Subtotal */}
          <div className="flex justify-between text-sm">
            <span className="text-neutral-600">{tBooking('subtotal')}</span>
            <span className="flex items-baseline gap-1">
              <span className="text-xs font-medium text-neutral-600">{currency}</span>
              <span className="font-bold text-neutral-900">{(cart.subtotal || 0).toFixed(2)}</span>
            </span>
          </div>

          {/* Total */}
          <div className="flex justify-between text-base font-bold text-neutral-900 border-t border-neutral-200 pt-3">
            <span>{tBooking('total')}</span>
            <span className="flex items-baseline gap-1">
              <span className="text-sm font-semibold">{currency}</span>
              <span className="text-lg font-bold">{(cart.subtotal || 0).toFixed(2)}</span>
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
              <p className="font-medium text-success-dark">{tBooking('secure_booking')}</p>
              <p className="text-success-dark/80 text-xs mt-1">
                {tBooking('secure_booking_message')}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
