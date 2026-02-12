'use client';

import { useState, useRef } from 'react';
import { useTranslations } from 'next-intl';
import { Button } from '@go-adventure/ui';
import {
  PaymentMethodSelector,
  type PaymentMethod,
} from '@/components/booking/PaymentMethodSelector';
import CheckoutConsents from '@/components/consent/CheckoutConsents';
import CouponInput from '@/components/booking/CouponInput';
import type { Cart } from '@/lib/api/client';
import type { PrimaryContactData } from './PrimaryContactForm';
import { ShieldCheck, ArrowLeft } from 'lucide-react';
import { resolveTranslation } from '@/lib/utils/translate';

interface CartPaymentStepProps {
  cart: Cart;
  primaryContact: PrimaryContactData;
  locale: string;
  onBack: () => void;
  onSubmit: (paymentMethod: PaymentMethod) => void;
  isProcessing: boolean;
  // Consent props
  termsAccepted: boolean;
  onTermsChange: (accepted: boolean) => void;
  marketingAccepted: boolean;
  onMarketingChange: (accepted: boolean) => void;
  termsError?: string;
  setTermsError: (error: string | undefined) => void;
  highlightConsents: boolean;
  setHighlightConsents: (highlight: boolean) => void;
}

export function CartPaymentStep({
  cart,
  primaryContact,
  locale,
  onBack,
  onSubmit,
  isProcessing,
  termsAccepted,
  onTermsChange,
  marketingAccepted,
  onMarketingChange,
  termsError,
  setTermsError,
  highlightConsents,
  setHighlightConsents,
}: CartPaymentStepProps) {
  const t = useTranslations('cart.checkout');
  const tCart = useTranslations('cart');
  const tPayment = useTranslations('payment');
  const tCheckout = useTranslations('checkout');
  const [selectedMethod, setSelectedMethod] = useState<PaymentMethod | undefined>();
  const consentsRef = useRef<HTMLDivElement>(null);
  const paymentRef = useRef<HTMLDivElement>(null);
  const [highlightPayment, setHighlightPayment] = useState(false);

  // Coupon state
  const [couponCode, setCouponCode] = useState<string>('');
  const [couponDiscount, setCouponDiscount] = useState<number>(0);

  // Calculate final total with coupon discount
  const finalTotal = Math.max(0, cart.subtotal - couponDiscount);

  // Get first listing ID for coupon validation (coupons can be listing-specific)
  const firstListingId = cart.items[0]?.listingId || '';

  const formatPrice = (amount: number, currency: string) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency,
    }).format(amount);
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
      dateStyle: 'medium',
      timeStyle: 'short',
    }).format(date);
  };

  const handleSubmit = () => {
    // Validate payment method selection first
    if (!selectedMethod) {
      setHighlightPayment(true);
      paymentRef.current?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      setTimeout(() => setHighlightPayment(false), 3000);
      return;
    }

    // Validate terms acceptance
    if (!termsAccepted) {
      setTermsError(tCheckout('terms_required') || 'You must accept the terms and conditions');
      setHighlightConsents(true);
      consentsRef.current?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      setTimeout(() => setHighlightConsents(false), 3000);
      return;
    }

    // Clear any previous error and submit
    setTermsError(undefined);
    onSubmit(selectedMethod);
  };

  return (
    <div className="space-y-6">
      {/* Order Summary */}
      <div className="bg-gray-50 rounded-lg p-4">
        <h3 className="font-semibold text-gray-900 mb-4">{t('order_summary')}</h3>

        {/* Primary Contact */}
        <div className="mb-4 pb-4 border-b border-gray-200">
          <p className="text-sm text-gray-600 mb-1">{t('primary_contact')}</p>
          <p className="font-medium text-gray-900">
            {primaryContact.firstName} {primaryContact.lastName}
          </p>
          <p className="text-sm text-gray-600">{primaryContact.email}</p>
          {primaryContact.phone && <p className="text-sm text-gray-600">{primaryContact.phone}</p>}
        </div>

        {/* Cart Items */}
        <div className="space-y-3">
          {cart.items.map((item) => {
            const title = resolveTranslation(item.listingTitle, locale) || 'Experience';
            return (
              <div key={item.id} className="flex justify-between items-start text-sm">
                <div className="flex-1">
                  <p className="font-medium text-gray-900">{title}</p>
                  <p className="text-gray-600">{formatDate(item.slotStart)}</p>
                  <p className="text-gray-600">
                    {item.quantity} {tCart('guests')}
                  </p>
                </div>
                <p className="font-medium text-gray-900">
                  {formatPrice(item.total, cart.currency)}
                </p>
              </div>
            );
          })}
        </div>

        {/* Coupon Input */}
        <div className="mt-4 pt-4 border-t border-gray-200">
          <CouponInput
            listingId={firstListingId}
            amount={cart.subtotal}
            onApply={(code, discount) => {
              setCouponCode(code);
              setCouponDiscount(discount);
            }}
            onRemove={() => {
              setCouponCode('');
              setCouponDiscount(0);
            }}
            appliedCode={couponCode}
            appliedDiscount={couponDiscount}
          />
        </div>

        {/* Totals */}
        <div className="mt-4 pt-4 border-t border-gray-200 space-y-2">
          <div className="flex justify-between text-sm">
            <span className="text-gray-600">{tCart('subtotal')}</span>
            <span className="text-gray-900">{formatPrice(cart.subtotal, cart.currency)}</span>
          </div>
          {couponDiscount > 0 && (
            <div className="flex justify-between text-sm">
              <span className="text-success">{tCart('discount')}</span>
              <span className="text-success">-{formatPrice(couponDiscount, cart.currency)}</span>
            </div>
          )}
          <div className="flex justify-between text-lg font-bold">
            <span className="text-gray-900">{tCart('total')}</span>
            <span className="text-gray-900">{formatPrice(finalTotal, cart.currency)}</span>
          </div>
        </div>
      </div>

      {/* Payment Method Selection */}
      <div
        ref={paymentRef}
        className={`bg-white rounded-lg p-4 transition-all duration-300 ease-in-out ${
          highlightPayment
            ? 'bg-green-50 border-2 border-green-500 shadow-lg ring-2 ring-green-400/30 animate-attention'
            : 'border border-gray-200'
        }`}
      >
        <PaymentMethodSelector
          availableMethods={['offline', 'click_to_pay']}
          onSelect={setSelectedMethod}
          selectedMethod={selectedMethod}
        />
      </div>

      {/* Consent Checkboxes */}
      <CheckoutConsents
        ref={consentsRef}
        termsAccepted={termsAccepted}
        onTermsChange={onTermsChange}
        marketingAccepted={marketingAccepted}
        onMarketingChange={onMarketingChange}
        termsError={termsError}
        highlight={highlightConsents}
      />

      {/* Security Badge */}
      <div className="flex items-center gap-2 text-sm text-gray-500">
        <ShieldCheck className="w-4 h-4 text-success" />
        <span>{tCart('secure_checkout')}</span>
      </div>

      {/* Actions */}
      <div className="flex gap-4 pt-4">
        <Button type="button" variant="outline" onClick={onBack} disabled={isProcessing}>
          <ArrowLeft className="w-4 h-4 mr-2" />
          {t('back')}
        </Button>
        <Button className="flex-1" onClick={handleSubmit} disabled={isProcessing}>
          {isProcessing
            ? t('processing')
            : t('pay_now', { amount: formatPrice(finalTotal, cart.currency) })}
        </Button>
      </div>
    </div>
  );
}
