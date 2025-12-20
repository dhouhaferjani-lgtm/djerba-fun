'use client';

import { useTranslations, useLocale } from 'next-intl';
import { Button } from '@go-adventure/ui';
import { useCartContext } from '@/lib/contexts/CartContext';
import type { CartItem } from '@/lib/api/client';
import { Trash2, Calendar, Clock, Users, AlertTriangle } from 'lucide-react';
import { format, parseISO } from 'date-fns';

interface CartItemCardProps {
  item: CartItem;
  locale: string;
}

export function CartItemCard({ item, locale }: CartItemCardProps) {
  const t = useTranslations('cart');
  const { removeItem, isRemovingItem } = useCartContext();

  const title = item.listingTitle[locale] || item.listingTitle['en'] || 'Activity';
  const startDate = item.slotStart ? parseISO(item.slotStart) : null;
  const endDate = item.slotEnd ? parseISO(item.slotEnd) : null;

  // Format person type breakdown for display
  const formatPersonTypes = () => {
    if (!item.personTypeBreakdown) return `${item.quantity} ${t('guests')}`;

    const parts = [];
    for (const [type, count] of Object.entries(item.personTypeBreakdown)) {
      if (count > 0) {
        parts.push(`${count} ${t(`person_type.${type}`, { defaultValue: type })}`);
      }
    }
    return parts.join(', ');
  };

  return (
    <div className="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
      <div className="flex gap-4">
        {/* Placeholder image */}
        <div className="w-24 h-24 bg-gray-200 rounded-lg flex-shrink-0 flex items-center justify-center">
          <Calendar className="w-8 h-8 text-gray-400" />
        </div>

        <div className="flex-1 min-w-0">
          <div className="flex items-start justify-between gap-2">
            <h3 className="font-semibold text-gray-900 truncate">{title}</h3>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => removeItem(item.id)}
              disabled={isRemovingItem}
              className="text-gray-400 hover:text-red-600 flex-shrink-0"
            >
              <Trash2 className="w-4 h-4" />
            </Button>
          </div>

          <div className="mt-2 space-y-1 text-sm text-gray-600">
            {startDate && (
              <div className="flex items-center gap-2">
                <Calendar className="w-4 h-4" />
                <span>{format(startDate, 'EEE, MMM d, yyyy')}</span>
              </div>
            )}

            {startDate && endDate && (
              <div className="flex items-center gap-2">
                <Clock className="w-4 h-4" />
                <span>
                  {format(startDate, 'HH:mm')} - {format(endDate, 'HH:mm')}
                </span>
              </div>
            )}

            <div className="flex items-center gap-2">
              <Users className="w-4 h-4" />
              <span>{formatPersonTypes()}</span>
            </div>
          </div>

          {/* Hold validity warning */}
          {!item.holdValid && (
            <div className="mt-2 flex items-center gap-2 text-amber-600 text-sm">
              <AlertTriangle className="w-4 h-4" />
              <span>{t('hold_expired')}</span>
            </div>
          )}
        </div>

        {/* Price */}
        <div className="text-right flex-shrink-0">
          <p className="font-semibold text-gray-900">
            {item.currency} {item.total.toFixed(2)}
          </p>
          {item.extrasTotal > 0 && (
            <p className="text-xs text-gray-500">
              +{item.currency} {item.extrasTotal.toFixed(2)} {t('extras')}
            </p>
          )}
        </div>
      </div>
    </div>
  );
}
