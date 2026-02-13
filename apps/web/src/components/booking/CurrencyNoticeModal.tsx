'use client';

import { useTranslations } from 'next-intl';
import { Dialog } from '@go-adventure/ui';
import { Lock, CheckCircle, Info } from 'lucide-react';
import Image from 'next/image';

interface CurrencyNoticeModalProps {
  isOpen: boolean;
  onConfirm: () => void;
  onCancel: () => void;
  amount: number;
  currency: string;
  tndAmount: number;
}

/**
 * Payment Confirmation Modal
 *
 * Clean, professional design that reassures users about currency conversion.
 * For EUR users: Shows EUR amount prominently with TND equivalent and explanation.
 * For TND users: Shows simpler confirmation without currency confusion messaging.
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

  const formattedAmount = formatCurrency(amount, currency);
  const formattedTnd = formatCurrency(tndAmount, 'TND');
  const isTndUser = currency === 'TND';

  return (
    <Dialog isOpen={isOpen} onClose={onCancel} size="md" showCloseButton={true}>
      <div className="text-center">
        {/* Header */}
        <h2 className="text-xl font-bold text-neutral-900 mb-6">{t('confirm_payment_title')}</h2>

        {/* Amount Display */}
        <div className="mb-6">
          <p className="text-sm text-neutral-500 mb-2">{t('you_will_be_charged')}</p>
          <p className="text-4xl font-bold text-primary mb-1">{formattedAmount}</p>
          {!isTndUser && (
            <p className="text-sm text-neutral-500">
              ({t('equivalent_to')} {formattedTnd})
            </p>
          )}
        </div>

        {/* Payment Notice */}
        <div className="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6 text-left">
          <div className="flex gap-3">
            <Info className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
            <p className="text-sm text-blue-800">
              {isTndUser
                ? t('currency_notice_message_tnd', { amount: formattedAmount })
                : t('currency_notice_message', {
                    eurAmount: formattedAmount,
                    tndAmount: formattedTnd,
                  })}
            </p>
          </div>
        </div>

        {/* Trust indicators */}
        <div className="flex items-center justify-center gap-3 mb-6">
          <div className="flex items-center gap-1.5 text-neutral-500">
            <Lock className="w-4 h-4" />
            <span className="text-xs">{t('trust_ssl')}</span>
          </div>
          <span className="text-neutral-300">|</span>
          <Image
            src="/images/payment/visa.svg"
            alt="Visa"
            width={32}
            height={20}
            className="h-5 w-auto"
          />
          <Image
            src="/images/payment/mastercard.svg"
            alt="Mastercard"
            width={32}
            height={20}
            className="h-5 w-auto"
          />
        </div>

        {/* Guarantee */}
        <div className="flex items-center justify-center gap-2 text-success mb-6">
          <CheckCircle className="w-4 h-4" />
          <span className="text-sm font-medium">{t('no_hidden_fees')}</span>
        </div>

        {/* Action buttons */}
        <div className="space-y-3">
          <button
            onClick={onConfirm}
            className="w-full px-4 py-3.5 bg-primary text-white font-semibold rounded-lg hover:bg-primary-dark transition-colors"
          >
            {t('confirm_and_pay')}
          </button>
          <button
            onClick={onCancel}
            className="w-full px-4 py-2.5 text-neutral-500 font-medium hover:text-neutral-700 transition-colors"
          >
            {t('go_back')}
          </button>
        </div>

        {/* Footer */}
        <p className="text-xs text-neutral-400 mt-4">{t('secured_by_smt')}</p>
      </div>
    </Dialog>
  );
}
