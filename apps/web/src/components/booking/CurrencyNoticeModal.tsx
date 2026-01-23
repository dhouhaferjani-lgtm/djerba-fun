'use client';

import { useTranslations } from 'next-intl';
import { Dialog } from '@go-adventure/ui';
import { Lock, ShieldCheck, CheckCircle, CreditCard } from 'lucide-react';
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
 * Currency Notice Modal - Enhanced for European Users
 *
 * Designed to build trust and confidence for European visitors (99% of users)
 * who may be unfamiliar with Clictopay (Tunisia's national payment gateway).
 *
 * Features:
 * - Prominent EUR display with TND as secondary
 * - Trust badges (SSL, 3D Secure, GDPR)
 * - Comparison to familiar EU payment systems (iDEAL, Bancontact, SOFORT)
 * - Clear step-by-step "What happens next" flow
 * - Enhanced security guarantees
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

  const isForeignCurrency = currency !== 'TND';
  const formattedAmount = formatCurrency(amount, currency);
  const formattedTnd = formatCurrency(tndAmount, 'TND');

  return (
    <Dialog isOpen={isOpen} onClose={onCancel} size="md" showCloseButton={true}>
      <div className="text-center">
        {/* Header with lock icon */}
        <div className="w-14 h-14 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
          <CreditCard className="w-7 h-7 text-primary" />
        </div>

        <h2 className="text-xl font-bold text-neutral-900 mb-1">{t('secure_payment_title')}</h2>

        {/* Amount Display - EUR prominent, TND secondary */}
        <div className="bg-neutral-50 rounded-xl p-4 mb-4">
          <p className="text-sm text-neutral-500 mb-1">{t('you_will_pay')}</p>
          <p className="text-3xl font-bold text-primary">{formattedAmount}</p>
          {isForeignCurrency && (
            <p className="text-sm text-neutral-400 mt-1">
              {t('approx_tnd', { tndAmount: formattedTnd })}
            </p>
          )}
        </div>

        {/* Trust Badges Row */}
        <div className="flex items-center justify-center gap-2 mb-4 flex-wrap">
          <div className="flex items-center gap-1.5 px-2.5 py-1.5 bg-neutral-100 rounded-lg">
            <Lock className="w-3.5 h-3.5 text-neutral-600" />
            <span className="text-xs font-medium text-neutral-600">{t('trust_ssl')}</span>
          </div>
          <div className="flex items-center gap-1.5 px-2.5 py-1.5 bg-neutral-100 rounded-lg">
            <ShieldCheck className="w-3.5 h-3.5 text-neutral-600" />
            <span className="text-xs font-medium text-neutral-600">{t('trust_3ds')}</span>
          </div>
          <Image
            src="/images/payment/visa.svg"
            alt="Visa"
            width={36}
            height={24}
            className="h-6 w-auto"
          />
          <Image
            src="/images/payment/mastercard.svg"
            alt="Mastercard"
            width={36}
            height={24}
            className="h-6 w-auto"
          />
        </div>

        {/* How it works */}
        <div className="bg-blue-50 rounded-xl p-4 mb-4 text-left">
          <p className="text-sm font-semibold text-blue-900 mb-1.5">{t('how_it_works_title')}</p>
          <p className="text-sm text-blue-800">{t('how_it_works_general')}</p>
        </div>

        {/* What happens next - Step by step */}
        <div className="bg-neutral-50 rounded-xl p-4 mb-4 text-left">
          <p className="text-sm font-semibold text-neutral-800 mb-2">
            {t('what_happens_next_title')}
          </p>
          <ol className="space-y-1.5">
            <li className="flex items-start gap-2 text-sm text-neutral-600">
              <span className="flex-shrink-0 w-5 h-5 bg-primary/10 text-primary rounded-full flex items-center justify-center text-xs font-medium">
                1
              </span>
              <span>{t('step_1')}</span>
            </li>
            <li className="flex items-start gap-2 text-sm text-neutral-600">
              <span className="flex-shrink-0 w-5 h-5 bg-primary/10 text-primary rounded-full flex items-center justify-center text-xs font-medium">
                2
              </span>
              <span>{t('step_2')}</span>
            </li>
            <li className="flex items-start gap-2 text-sm text-neutral-600">
              <span className="flex-shrink-0 w-5 h-5 bg-primary/10 text-primary rounded-full flex items-center justify-center text-xs font-medium">
                3
              </span>
              <span>{t('step_3')}</span>
            </li>
            <li className="flex items-start gap-2 text-sm text-neutral-600">
              <span className="flex-shrink-0 w-5 h-5 bg-primary/10 text-primary rounded-full flex items-center justify-center text-xs font-medium">
                4
              </span>
              <span>{t('step_4')}</span>
            </li>
          </ol>
        </div>

        {/* Guarantees */}
        <div className="space-y-1.5 mb-5">
          <div className="flex items-center gap-2 justify-center text-success">
            <CheckCircle className="w-4 h-4" />
            <span className="text-sm font-medium">
              {t('guarantee_charge', { amount: formattedAmount })}
            </span>
          </div>
          <div className="flex items-center gap-2 justify-center text-neutral-600">
            <CheckCircle className="w-4 h-4" />
            <span className="text-sm">{t('guarantee_official')}</span>
          </div>
        </div>

        {/* Action buttons */}
        <div className="space-y-3">
          <button
            onClick={onConfirm}
            className="w-full px-4 py-3.5 bg-primary text-white font-semibold rounded-lg hover:bg-primary-dark transition-colors flex items-center justify-center gap-2"
          >
            <Lock className="w-4 h-4" />
            {t('pay_securely_button', { amount: formattedAmount })}
          </button>
          <button
            onClick={onCancel}
            className="w-full px-4 py-2.5 text-neutral-600 font-medium hover:text-neutral-800 transition-colors"
          >
            {t('go_back')}
          </button>
        </div>

        {/* Security footer */}
        <p className="text-xs text-neutral-400 mt-4 flex items-center justify-center gap-1">
          <Lock className="w-3 h-3" />
          {t('secured_by_smt')}
        </p>
      </div>
    </Dialog>
  );
}
