'use client';

import { useTranslations } from 'next-intl';
import { useMemo } from 'react';

export interface PriceBreakdownItem {
  type: 'person' | 'extra';
  key: string;
  label: string;
  quantity: number;
  unitPrice: number;
  subtotal: number;
}

interface PriceBreakdownTableProps {
  items: PriceBreakdownItem[];
  currency: string;
  total: number;
  compact?: boolean;
  showTitle?: boolean;
  className?: string;
}

export function PriceBreakdownTable({
  items,
  currency,
  total,
  compact = false,
  showTitle = true,
  className = '',
}: PriceBreakdownTableProps) {
  const t = useTranslations('price_breakdown');

  // Filter out items with zero quantity
  const visibleItems = useMemo(() => items.filter((item) => item.quantity > 0), [items]);

  // Separate person types and extras
  const personItems = visibleItems.filter((item) => item.type === 'person');
  const extraItems = visibleItems.filter((item) => item.type === 'extra');

  if (visibleItems.length === 0) {
    return null;
  }

  const formatPrice = (price: number) => {
    return `${currency} ${price.toFixed(2)}`;
  };

  // Mobile/Compact layout - stacked cards
  if (compact) {
    return (
      <div className={`space-y-2 ${className}`}>
        {showTitle && <h4 className="text-sm font-semibold text-neutral-700 mb-2">{t('title')}</h4>}

        {/* Person types */}
        {personItems.map((item) => (
          <div key={item.key} className="flex justify-between items-center py-1.5 text-sm">
            <div className="flex-1">
              <span className="text-neutral-800">{item.label}</span>
              <span className="text-neutral-500 mx-1">x{item.quantity}</span>
              <span className="text-neutral-400 text-xs">@ {formatPrice(item.unitPrice)}</span>
            </div>
            <span className="font-medium text-neutral-900">{formatPrice(item.subtotal)}</span>
          </div>
        ))}

        {/* Extras (only if any selected) */}
        {extraItems.length > 0 && (
          <>
            {extraItems.map((item) => (
              <div key={item.key} className="flex justify-between items-center py-1.5 text-sm">
                <div className="flex-1">
                  <span className="text-neutral-800">{item.label}</span>
                  <span className="text-neutral-500 mx-1">x{item.quantity}</span>
                  <span className="text-neutral-400 text-xs">@ {formatPrice(item.unitPrice)}</span>
                </div>
                <span className="font-medium text-neutral-900">{formatPrice(item.subtotal)}</span>
              </div>
            ))}
          </>
        )}

        {/* Total */}
        <div className="flex justify-between items-center pt-2 border-t border-neutral-200">
          <span className="font-semibold text-neutral-900">{t('total')}</span>
          <span className="font-bold text-lg text-primary">{formatPrice(total)}</span>
        </div>
      </div>
    );
  }

  // Desktop layout - full table
  return (
    <div className={`${className}`}>
      {showTitle && <h4 className="text-sm font-semibold text-neutral-700 mb-3">{t('title')}</h4>}

      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-neutral-200">
            <th className="text-left py-2 pr-2 font-medium text-neutral-600">{t('type')}</th>
            <th className="text-center py-2 px-2 font-medium text-neutral-600 w-12">{t('qty')}</th>
            <th className="text-right py-2 px-2 font-medium text-neutral-600">{t('unit_price')}</th>
            <th className="text-right py-2 pl-2 font-medium text-neutral-600">{t('subtotal')}</th>
          </tr>
        </thead>
        <tbody>
          {/* Person types */}
          {personItems.map((item) => (
            <tr key={item.key} className="border-b border-neutral-100">
              <td className="py-2 pr-2 text-neutral-800">{item.label}</td>
              <td className="py-2 px-2 text-center text-neutral-600">{item.quantity}</td>
              <td className="py-2 px-2 text-right text-neutral-600">
                {formatPrice(item.unitPrice)}
              </td>
              <td className="py-2 pl-2 text-right font-medium text-neutral-900">
                {formatPrice(item.subtotal)}
              </td>
            </tr>
          ))}

          {/* Extras (only if any selected) */}
          {extraItems.map((item) => (
            <tr key={item.key} className="border-b border-neutral-100">
              <td className="py-2 pr-2 text-neutral-800">{item.label}</td>
              <td className="py-2 px-2 text-center text-neutral-600">{item.quantity}</td>
              <td className="py-2 px-2 text-right text-neutral-600">
                {formatPrice(item.unitPrice)}
              </td>
              <td className="py-2 pl-2 text-right font-medium text-neutral-900">
                {formatPrice(item.subtotal)}
              </td>
            </tr>
          ))}
        </tbody>
        <tfoot>
          <tr className="border-t-2 border-neutral-300">
            <td colSpan={3} className="py-3 pr-2 text-right font-semibold text-neutral-900">
              {t('total')}
            </td>
            <td className="py-3 pl-2 text-right font-bold text-lg text-primary">
              {formatPrice(total)}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  );
}
