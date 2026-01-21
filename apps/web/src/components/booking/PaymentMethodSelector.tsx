'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';

export type PaymentMethod = 'mock' | 'offline' | 'click_to_pay' | 'stripe' | 'paypal';

interface PaymentMethodSelectorProps {
  availableMethods: PaymentMethod[];
  onSelect: (method: PaymentMethod) => void;
  selectedMethod?: PaymentMethod;
}

export function PaymentMethodSelector({
  availableMethods,
  onSelect,
  selectedMethod,
}: PaymentMethodSelectorProps) {
  const t = useTranslations('payment');
  const tCheckout = useTranslations('checkout');
  const [selected, setSelected] = useState<PaymentMethod | undefined>(selectedMethod);

  const handleSelect = (method: PaymentMethod) => {
    setSelected(method);
    onSelect(method);
  };

  const methods: Record<
    PaymentMethod,
    {
      label: string;
      description: string;
      icon: string;
    }
  > = {
    mock: {
      label: t('mock'),
      description: t('mock_description'),
      icon: '💳',
    },
    offline: {
      label: t('bank_transfer'),
      description: t('bank_transfer_description'),
      icon: '🏦',
    },
    click_to_pay: {
      label: t('clictopay'),
      description: t('clictopay_description'),
      icon: '💳',
    },
    stripe: {
      label: t('card'),
      description: t('card_description'),
      icon: '💳',
    },
    paypal: {
      label: 'PayPal',
      description: t('paypal_description'),
      icon: '🅿️',
    },
  };

  return (
    <div className="space-y-4">
      <div>
        <h3 className="font-semibold text-lg text-gray-900 mb-2">{t('select_method')}</h3>
        <p className="text-sm text-gray-600">{t('select_method_subtitle')}</p>
      </div>

      <div className="space-y-3">
        {availableMethods.map((method) => {
          const methodInfo = methods[method];
          const isSelected = selected === method;

          return (
            <button
              key={method}
              type="button"
              onClick={() => handleSelect(method)}
              className={`w-full text-left p-4 border rounded-lg transition-all ${
                isSelected
                  ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                  : 'border-gray-200 hover:border-gray-300'
              }`}
              data-testid={`payment-method-${method}`}
            >
              <div className="flex items-start gap-4">
                <div className="text-3xl">{methodInfo.icon}</div>
                <div className="flex-1">
                  <div className="flex items-center gap-2">
                    <input
                      type="radio"
                      name="paymentMethod"
                      checked={isSelected}
                      onChange={() => handleSelect(method)}
                      className="text-primary focus:ring-primary"
                    />
                    <span className="font-medium text-gray-900">{methodInfo.label}</span>
                  </div>
                  <p className="mt-1 text-sm text-gray-600">{methodInfo.description}</p>
                </div>
              </div>
            </button>
          );
        })}
      </div>

      {/* Payment Method Details */}
      {selected === 'offline' && (
        <div className="mt-4 p-4 bg-success-light border border-success/20 rounded-lg">
          <h4 className="font-medium text-success-dark mb-2">{t('bank_transfer_instructions')}</h4>
          <div className="text-sm text-success-dark space-y-1">
            <p>{t('bank_transfer_info')}</p>
            <p className="font-mono mt-2">IBAN: FR76 1234 5678 9012 3456 7890 123</p>
            <p className="font-mono">BIC: ABCDEFGH</p>
            <p className="mt-2 text-xs">{t('bank_transfer_note')}</p>
          </div>
        </div>
      )}

      {selected === 'click_to_pay' && (
        <div className="mt-4 p-4 bg-info-light border border-info/20 rounded-lg">
          <h4 className="font-medium text-info-dark mb-2">{t('secure_payment')}</h4>
          <p className="text-sm text-info-dark">{tCheckout('clictopay_redirect_info')}</p>
        </div>
      )}

      {selected === 'mock' && (
        <div className="mt-4 p-4 bg-warning-light border border-warning/20 rounded-lg">
          <h4 className="font-medium text-warning-dark mb-2">{t('mock_payment')}</h4>
          <p className="text-sm text-warning-dark">{t('mock_payment_info')}</p>
        </div>
      )}
    </div>
  );
}
