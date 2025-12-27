'use client';

import { useMemo } from 'react';
import { useTranslations } from 'next-intl';
import { Minus, Plus, Users, AlertTriangle } from 'lucide-react';
import { PriceDisplay } from '@/components/molecules/PriceDisplay';

interface PersonType {
  key: string;
  label: { en: string; fr: string } | string;
  price?: number;
  minAge: number | null;
  maxAge: number | null;
  minQuantity: number;
  maxQuantity: number | null;
}

interface PersonTypeSelectorProps {
  personTypes: PersonType[];
  value: Record<string, number>;
  onChange: (breakdown: Record<string, number>) => void;
  currency: string;
  maxCapacity: number;
  locale?: string;
}

export function PersonTypeSelector({
  personTypes,
  value,
  onChange,
  currency,
  maxCapacity,
  locale = 'en',
}: PersonTypeSelectorProps) {
  const t = useTranslations('booking');

  // Get label for a person type
  const getLabel = (type: PersonType): string => {
    if (typeof type.label === 'string') {
      return type.label;
    }
    return type.label[locale as keyof typeof type.label] || type.label.en || type.key;
  };

  // Get age range text for a person type
  const getAgeRange = (type: PersonType): string => {
    if (type.minAge !== null && type.maxAge !== null) {
      return `(${type.minAge}-${type.maxAge})`;
    }
    if (type.minAge !== null) {
      return `(${type.minAge}+)`;
    }
    if (type.maxAge !== null) {
      return `(0-${type.maxAge})`;
    }
    return '';
  };

  // Calculate totals
  const totals = useMemo(() => {
    let totalGuests = 0;
    let totalPrice = 0;

    for (const type of personTypes) {
      const quantity = value[type.key] || 0;
      totalGuests += quantity;
      totalPrice += (type.price ?? 0) * quantity;
    }

    return { totalGuests, totalPrice };
  }, [personTypes, value]);

  // Check if we can add more of this type
  const canIncrement = (type: PersonType): boolean => {
    const currentTotal = totals.totalGuests;
    if (currentTotal >= maxCapacity) return false;

    const currentQuantity = value[type.key] || 0;
    if (type.maxQuantity !== null && currentQuantity >= type.maxQuantity) return false;

    return true;
  };

  // Check if we can remove this type
  const canDecrement = (type: PersonType): boolean => {
    const currentQuantity = value[type.key] || 0;
    if (currentQuantity <= 0) return false;
    if (currentQuantity <= type.minQuantity) return false;

    return true;
  };

  // Handle quantity change for a type
  const handleQuantityChange = (typeKey: string, delta: number) => {
    const newValue = { ...value };
    const newQuantity = Math.max(0, (newValue[typeKey] || 0) + delta);
    newValue[typeKey] = newQuantity;
    onChange(newValue);
  };

  return (
    <div className="space-y-4">
      {/* Prominent capacity indicator */}
      <div
        className="bg-green-50 border border-green-200 rounded-lg p-3 flex items-center justify-between"
        data-testid="capacity-indicator"
      >
        <div className="flex items-center gap-2">
          <Users className="h-5 w-5 text-green-600" />
          <span className="text-sm font-medium text-green-900">{t('available_capacity')}</span>
        </div>
        <div className="text-right">
          <div className="text-lg font-bold text-green-900">
            {maxCapacity - totals.totalGuests} / {maxCapacity}
          </div>
          <div className="text-xs text-green-700">{t('spots_remaining')}</div>
        </div>
      </div>

      {/* Person type rows */}
      <div className="space-y-3">
        {personTypes.map((type) => {
          const quantity = value[type.key] || 0;
          const label = getLabel(type);
          const ageRange = getAgeRange(type);

          return (
            <div
              key={type.key}
              className="flex items-center justify-between border border-neutral-200 rounded-lg p-3"
            >
              {/* Type info */}
              <div className="flex-1">
                <div className="font-medium text-neutral-900">
                  {label}{' '}
                  {ageRange && <span className="text-neutral-500 font-normal">{ageRange}</span>}
                </div>
                <div className="text-sm text-neutral-500">
                  {(type.price ?? 0) > 0 ? (
                    <PriceDisplay
                      amount={type.price ?? 0}
                      currency={currency}
                      size="sm"
                      perPerson={false}
                    />
                  ) : (
                    <span className="text-green-600 font-medium">{t('person_types.free')}</span>
                  )}
                </div>
              </div>

              {/* Quantity controls */}
              <div className="flex items-center gap-3">
                <button
                  type="button"
                  onClick={() => handleQuantityChange(type.key, -1)}
                  disabled={!canDecrement(type)}
                  className="p-2 rounded-full border border-neutral-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-neutral-50 transition-colors"
                  aria-label={`Decrease ${label}`}
                  data-testid={`person-type-${type.key}-decrement`}
                >
                  <Minus className="h-4 w-4" />
                </button>
                <span
                  className="w-8 text-center font-medium"
                  data-testid={`person-type-${type.key}-count`}
                >
                  {quantity}
                </span>
                <button
                  type="button"
                  onClick={() => handleQuantityChange(type.key, 1)}
                  disabled={!canIncrement(type)}
                  className="p-2 rounded-full border border-neutral-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-neutral-50 transition-colors"
                  aria-label={`Increase ${label}`}
                  data-testid={`person-type-${type.key}-increment`}
                >
                  <Plus className="h-4 w-4" />
                </button>
              </div>
            </div>
          );
        })}
      </div>

      {/* Totals */}
      <div className="border-t border-neutral-200 pt-4">
        <div className="flex items-center justify-between">
          <div className="text-neutral-600">
            {t('total')}:{' '}
            <span className="font-medium text-neutral-900">
              {t('guests_count', { count: totals.totalGuests })}
            </span>
          </div>
          <PriceDisplay
            amount={totals.totalPrice}
            currency={currency}
            size="md"
            perPerson={false}
            data-testid="total-price"
          />
        </div>
        {maxCapacity - totals.totalGuests <= 3 && totals.totalGuests < maxCapacity && (
          <p className="text-sm text-orange-600 font-medium mt-2 flex items-center gap-1">
            <AlertTriangle className="h-4 w-4" />
            {t('only_x_spots_left', { count: maxCapacity - totals.totalGuests })}
          </p>
        )}
        {totals.totalGuests > maxCapacity && (
          <div
            className="text-sm text-red-600 font-medium mt-2 flex items-center gap-1"
            data-testid="capacity-error"
          >
            <AlertTriangle className="h-4 w-4" />
            {t('exceeds_capacity', { max: maxCapacity })}
          </div>
        )}
      </div>
    </div>
  );
}

PersonTypeSelector.displayName = 'PersonTypeSelector';
