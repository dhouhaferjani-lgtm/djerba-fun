'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';

interface Extra {
  id: string;
  name: string;
  description?: string;
  price: number;
  currency: string;
}

interface SelectedExtra {
  extraId: string;
  quantity: number;
}

interface ExtrasSelectionProps {
  extras: Extra[];
  currency: string;
  onSubmit: (selectedExtras: SelectedExtra[]) => void;
  onBack?: () => void;
  defaultSelections?: SelectedExtra[];
}

export function ExtrasSelection({
  extras,
  currency,
  onSubmit,
  onBack,
  defaultSelections = [],
}: ExtrasSelectionProps) {
  const t = useTranslations('booking');

  const [selectedExtras, setSelectedExtras] = useState<Record<string, number>>(() => {
    const initial: Record<string, number> = {};
    defaultSelections.forEach((sel) => {
      initial[sel.extraId] = sel.quantity;
    });
    return initial;
  });

  const handleToggleExtra = (extraId: string) => {
    setSelectedExtras((prev) => {
      const newState = { ...prev };
      if (newState[extraId]) {
        delete newState[extraId];
      } else {
        newState[extraId] = 1;
      }
      return newState;
    });
  };

  const handleQuantityChange = (extraId: string, quantity: number) => {
    if (quantity < 1) {
      setSelectedExtras((prev) => {
        const newState = { ...prev };
        delete newState[extraId];
        return newState;
      });
    } else {
      setSelectedExtras((prev) => ({
        ...prev,
        [extraId]: quantity,
      }));
    }
  };

  const calculateTotal = () => {
    return Object.entries(selectedExtras).reduce((total, [extraId, quantity]) => {
      const extra = extras.find((e) => e.id === extraId);
      return total + (extra?.price || 0) * quantity;
    }, 0);
  };

  const handleSubmit = () => {
    const selections = Object.entries(selectedExtras).map(([extraId, quantity]) => ({
      extraId,
      quantity,
    }));
    onSubmit(selections);
  };

  const formatPrice = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency,
    }).format(amount / 100);
  };

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-900 mb-2">{t('extras_title')}</h2>
        <p className="text-gray-600">{t('extras_subtitle')}</p>
      </div>

      {extras.length === 0 ? (
        <div className="text-center py-12">
          <p className="text-gray-500">{t('no_extras')}</p>
        </div>
      ) : (
        <div className="space-y-4">
          {extras.map((extra) => {
            const isSelected = !!selectedExtras[extra.id];
            const quantity = selectedExtras[extra.id] || 1;

            return (
              <div
                key={extra.id}
                className={`border rounded-lg p-4 transition-all ${
                  isSelected
                    ? 'border-primary bg-primary/5'
                    : 'border-gray-200 hover:border-gray-300'
                }`}
              >
                <div className="flex items-start justify-between gap-4">
                  <div className="flex-1">
                    <div className="flex items-center gap-3">
                      <input
                        type="checkbox"
                        id={`extra-${extra.id}`}
                        checked={isSelected}
                        onChange={() => handleToggleExtra(extra.id)}
                        className="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary"
                      />
                      <label
                        htmlFor={`extra-${extra.id}`}
                        className="font-medium text-gray-900 cursor-pointer"
                      >
                        {extra.name}
                      </label>
                    </div>
                    {extra.description && (
                      <p className="mt-2 ml-8 text-sm text-gray-600">{extra.description}</p>
                    )}
                  </div>

                  <div className="flex items-center gap-4">
                    {isSelected && (
                      <div className="flex items-center gap-2">
                        <button
                          type="button"
                          onClick={() => handleQuantityChange(extra.id, quantity - 1)}
                          className="w-8 h-8 flex items-center justify-center border border-gray-300 rounded-lg hover:bg-gray-50"
                          aria-label="Decrease quantity"
                        >
                          −
                        </button>
                        <span className="w-8 text-center font-medium">{quantity}</span>
                        <button
                          type="button"
                          onClick={() => handleQuantityChange(extra.id, quantity + 1)}
                          className="w-8 h-8 flex items-center justify-center border border-gray-300 rounded-lg hover:bg-gray-50"
                          aria-label="Increase quantity"
                        >
                          +
                        </button>
                      </div>
                    )}
                    <span className="font-semibold text-gray-900 min-w-[80px] text-right">
                      {formatPrice(extra.price)}
                    </span>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Total */}
      {Object.keys(selectedExtras).length > 0 && (
        <div className="border-t pt-4">
          <div className="flex justify-between items-center">
            <span className="font-medium text-gray-900">{t('extras_total')}:</span>
            <span className="text-xl font-bold text-gray-900">{formatPrice(calculateTotal())}</span>
          </div>
        </div>
      )}

      {/* Action Buttons */}
      <div className="flex gap-4 pt-4">
        {onBack && (
          <button
            type="button"
            onClick={onBack}
            className="flex-1 px-6 py-3 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 transition-colors"
          >
            {t('back')}
          </button>
        )}
        <button
          type="button"
          onClick={handleSubmit}
          className="flex-1 px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors"
        >
          {t('continue')}
        </button>
      </div>
    </div>
  );
}
