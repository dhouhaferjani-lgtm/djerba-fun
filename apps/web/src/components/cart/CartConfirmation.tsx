'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- next-intl Link requires typed routes */
import { useTranslations } from 'next-intl';
import { Button } from '@go-adventure/ui';
import Link from 'next/link';
import type { Booking } from '@go-adventure/schemas';
import { CheckCircle, Calendar, Users, MapPin, Download, Mail } from 'lucide-react';

// Extended booking type with listingTitle from cart checkout response
interface CartBooking extends Booking {
  listingTitle?: string;
}

interface CartConfirmationProps {
  bookings: CartBooking[];
  totalAmount: number;
  currency: string;
  locale: string;
  primaryEmail?: string;
}

export function CartConfirmation({
  bookings,
  totalAmount,
  currency,
  locale,
  primaryEmail,
}: CartConfirmationProps) {
  const t = useTranslations('cart.checkout');
  const tCart = useTranslations('cart');

  const formatPrice = (amount: number, curr: string) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: curr,
    }).format(amount);
  };

  const formatDate = (dateString: string | undefined) => {
    if (!dateString) return 'Not available';
    try {
      const date = new Date(dateString);
      if (isNaN(date.getTime())) return 'Not available';
      return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'full',
        timeStyle: 'short',
      }).format(date);
    } catch {
      return 'Not available';
    }
  };

  return (
    <div className="max-w-2xl mx-auto">
      <div className="text-center space-y-6">
        {/* Success Animation */}
        <div className="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full animate-in zoom-in duration-300">
          <CheckCircle className="w-12 h-12 text-green-600" />
        </div>

        {/* Title */}
        <div>
          <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('confirmation_title')}</h1>
          <p className="text-lg text-gray-600">
            {t('confirmation_subtitle', { count: bookings.length })}
          </p>
        </div>

        {/* Total Amount */}
        <div className="bg-primary/5 border border-primary/20 rounded-lg p-6">
          <p className="text-sm text-gray-600 mb-1">{t('total_paid')}</p>
          <p className="text-3xl font-bold text-primary">{formatPrice(totalAmount, currency)}</p>
        </div>

        {/* Bookings List */}
        <div className="bg-white border border-gray-200 rounded-lg overflow-hidden text-left">
          <div className="p-4 border-b border-gray-200 bg-gray-50">
            <h2 className="font-semibold text-gray-900">
              {t('your_bookings', { count: bookings.length })}
            </h2>
          </div>
          <div className="divide-y divide-gray-200">
            {bookings.map((booking) => {
              const bookingNumber = booking.bookingNumber || booking.code || booking.id;
              const guestCount = booking.quantity || booking.guests || 1;
              const startDate =
                booking.startsAt ||
                booking.availabilitySlot?.start ||
                booking.confirmedAt ||
                booking.createdAt;

              return (
                <div key={booking.id} className="p-4 space-y-3">
                  <div className="flex items-start justify-between">
                    <div>
                      <p className="font-semibold text-gray-900">
                        {booking.listingTitle || 'Experience'}
                      </p>
                      <p className="text-sm text-primary font-mono">#{bookingNumber}</p>
                    </div>
                    <span className="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                      {t('status_confirmed')}
                    </span>
                  </div>
                  <div className="grid grid-cols-2 gap-3 text-sm">
                    <div className="flex items-center gap-2 text-gray-600">
                      <Calendar className="w-4 h-4" />
                      <span>{formatDate(startDate)}</span>
                    </div>
                    <div className="flex items-center gap-2 text-gray-600">
                      <Users className="w-4 h-4" />
                      <span>
                        {guestCount} {tCart('guests')}
                      </span>
                    </div>
                  </div>
                  <div className="flex justify-between items-center pt-2 border-t border-gray-100">
                    <span className="text-sm text-gray-600">{tCart('total')}</span>
                    <span className="font-medium text-gray-900">
                      {formatPrice(booking.totalAmount, booking.currency)}
                    </span>
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Email Notice */}
        {primaryEmail && (
          <div className="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
            <Mail className="w-5 h-5 text-green-600 flex-shrink-0" />
            <p className="text-sm text-green-800 text-left">
              {t('email_confirmation_sent', { email: primaryEmail })}
            </p>
          </div>
        )}

        {/* Action Buttons */}
        <div className="flex flex-col sm:flex-row gap-4 pt-4">
          <Link href={`/${locale}/dashboard/bookings` as any} className="flex-1">
            <Button className="w-full">{t('view_all_bookings')}</Button>
          </Link>
          <Link href={`/${locale}/listings` as any} className="flex-1">
            <Button variant="outline" className="w-full">
              {t('continue_browsing')}
            </Button>
          </Link>
        </div>

        {/* Tips */}
        <div className="bg-gray-50 rounded-lg p-4 text-left">
          <h3 className="font-medium text-gray-900 mb-2">{t('whats_next')}</h3>
          <ul className="text-sm text-gray-600 space-y-2">
            <li className="flex items-start gap-2">
              <span className="text-primary">•</span>
              <span>{t('tip_check_email')}</span>
            </li>
            <li className="flex items-start gap-2">
              <span className="text-primary">•</span>
              <span>{t('tip_save_booking')}</span>
            </li>
            <li className="flex items-start gap-2">
              <span className="text-primary">•</span>
              <span>{t('tip_arrive_early')}</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  );
}
