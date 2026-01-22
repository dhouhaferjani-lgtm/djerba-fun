'use client';

import { useTranslations } from 'next-intl';
import { Dialog } from '@go-adventure/ui';
import { CreditCard, CheckCircle, ShieldCheck } from 'lucide-react';

interface CurrencyNoticeModalProps {
  isOpen: boolean;
  onConfirm: () => void;
  onCancel: () => void;
  amount: number;
  currency: string;
  tndAmount: number;
}

/**
 * Currency Notice Modal
 *
 * Shown before redirecting to Clictopay to explain currency conversion.
 * Reassures international travelers that the TND amount on Clictopay
 * is the equivalent of their EUR amount.
 */
export function CurrencyNoticeModal({
  isOpen,
  onConfirm,
  onCancel,
  amount,
  currency,
  tndAmount,
}: CurrencyNoticeModalProps) {
  const t = useTranslations('checkout');

  const formatCurrency = (value: number, currencyCode: string) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currencyCode,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(value);
  };

  // Show modal for all currencies - it confirms redirect to external payment page
  const isTndPayment = currency === 'TND';

  return (
    <Dialog isOpen={isOpen} onClose={onCancel} size="md" showCloseButton={false}>
      <div className="text-center">
        {/* Icon */}
        <div className="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
          <CreditCard className="w-8 h-8 text-primary" />
        </div>

        {/* Title */}
        <h2 className="text-xl font-bold text-neutral-900 mb-2">{t('currency_notice_title')}</h2>

        {/* Main amount - what user will pay */}
        <div className="mb-4">
          <p className="text-sm text-neutral-500 mb-1">{t('you_will_pay')}</p>
          <p className="text-3xl font-bold text-primary">{formatCurrency(amount, currency)}</p>
        </div>

        {/* Explanation box - different content for TND vs foreign currencies */}
        {isTndPayment ? (
          // Simple confirmation for TND payments
          <div className="bg-neutral-50 rounded-xl p-4 mb-5 text-left">
            <div className="flex items-start gap-3">
              <ShieldCheck className="w-5 h-5 text-info flex-shrink-0 mt-0.5" />
              <p className="text-sm text-neutral-700">{t('currency_notice_tnd_simple')}</p>
            </div>
          </div>
        ) : (
          // Currency conversion explanation for EUR/USD
          <div className="bg-neutral-50 rounded-xl p-4 mb-5 text-left">
            <div className="flex items-start gap-3">
              <ShieldCheck className="w-5 h-5 text-info flex-shrink-0 mt-0.5" />
              <div>
                <p className="text-sm text-neutral-700 mb-2">{t('currency_notice_explanation')}</p>
                <p className="text-sm text-neutral-500">
                  {t('currency_notice_tnd_equivalent', {
                    tndAmount: formatCurrency(tndAmount, 'TND'),
                  })}
                </p>
              </div>
            </div>
          </div>
        )}

        {/* Guarantee badge */}
        <div className="flex items-center gap-2 justify-center text-success mb-6">
          <CheckCircle className="w-5 h-5" />
          <span className="text-sm font-medium">{t('currency_notice_guarantee')}</span>
        </div>

        {/* Action buttons */}
        <div className="flex gap-3">
          <button
            onClick={onCancel}
            className="flex-1 px-4 py-3 border border-neutral-300 text-neutral-700 font-medium rounded-lg hover:bg-neutral-50 transition-colors"
          >
            {t('go_back')}
          </button>
          <button
            onClick={onConfirm}
            className="flex-1 px-4 py-3 bg-primary text-white font-medium rounded-lg hover:bg-primary-dark transition-colors"
          >
            {t('continue_to_payment')}
          </button>
        </div>

        {/* Security note */}
        <p className="text-xs text-neutral-400 mt-4">{t('secure_payment_note')}</p>
      </div>
    </Dialog>
  );
}
