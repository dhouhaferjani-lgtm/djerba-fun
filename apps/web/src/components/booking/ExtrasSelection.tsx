'use client';

import { useState, useMemo } from 'react';
import { useTranslations } from 'next-intl';
import type {
  ListingExtraForBooking,
  ExtraPricingType,
  ExtraCategory,
} from '@go-adventure/schemas';

interface SelectedExtra {
  id: string; // listing_extra_id
  quantity: number;
}

interface ExtrasSelectionProps {
  extras: ListingExtraForBooking[];
  currency: string;
  personTypeBreakdown?: Record<string, number>;
  onSubmit: (selectedExtras: SelectedExtra[]) => void;
  onBack?: () => void;
  defaultSelections?: SelectedExtra[];
}

const CATEGORY_ICONS: Record<string, string> = {
  equipment: '🛠️',
  meal: '🍽️',
  insurance: '🛡️',
  upgrade: '⬆️',
  merchandise: '🛍️',
  transport: '🚐',
  accessibility: '♿',
  other: '📦',
};

// Pricing type label keys for translations
const PRICING_TYPE_TRANSLATION_KEYS: Record<ExtraPricingType, string> = {
  per_person: 'per_traveller',
  per_booking: 'for_entire_group',
  per_unit: 'per_item',
  per_person_type: 'varies_by_age',
};

export function ExtrasSelection({
  extras,
  currency,
  personTypeBreakdown = {},
  onSubmit,
  onBack,
  defaultSelections = [],
}: ExtrasSelectionProps) {
  const t = useTranslations('booking');

  const [selectedExtras, setSelectedExtras] = useState<Record<string, number>>(() => {
    const initial: Record<string, number> = {};
    // Initialize with required/auto-add extras
    extras.forEach((extra) => {
      if (extra.isRequired || extra.autoAdd) {
        initial[extra.id] = extra.minQuantity > 0 ? extra.minQuantity : 1;
      }
    });
    // Apply default selections
    defaultSelections.forEach((sel) => {
      initial[sel.id] = sel.quantity;
    });
    return initial;
  });

  const totalGuests = useMemo(() => {
    return Object.values(personTypeBreakdown).reduce((sum, count) => sum + count, 0) || 1;
  }, [personTypeBreakdown]);

  const handleToggleExtra = (extraId: string, extra: ListingExtraForBooking) => {
    if (extra.isRequired) return; // Can't toggle required extras

    setSelectedExtras((prev) => {
      const newState = { ...prev };
      if (newState[extraId]) {
        delete newState[extraId];
      } else {
        newState[extraId] = extra.minQuantity > 0 ? extra.minQuantity : 1;
      }
      return newState;
    });
  };

  const handleQuantityChange = (
    extraId: string,
    quantity: number,
    extra: ListingExtraForBooking
  ) => {
    const minQty = extra.minQuantity || 0;
    const maxQty = extra.maxQuantity || 999;

    if (quantity < minQty) {
      if (!extra.isRequired) {
        setSelectedExtras((prev) => {
          const newState = { ...prev };
          delete newState[extraId];
          return newState;
        });
      }
      return;
    }

    if (quantity > maxQty) {
      return;
    }

    setSelectedExtras((prev) => ({
      ...prev,
      [extraId]: quantity,
    }));
  };

  const calculateExtraSubtotal = (extra: ListingExtraForBooking, quantity: number): number => {
    const price = currency === 'TND' ? extra.priceTnd : extra.priceEur;

    switch (extra.pricingType) {
      case 'per_person':
        return price * totalGuests;
      case 'per_booking':
        return price * quantity;
      case 'per_unit':
        return price * quantity;
      case 'per_person_type':
        // For per_person_type, we need to calculate based on breakdown
        if (extra.personTypePrices && Object.keys(personTypeBreakdown).length > 0) {
          const currencyKey = currency.toLowerCase() as 'tnd' | 'eur';
          return Object.entries(personTypeBreakdown).reduce((total, [type, count]) => {
            const typePrices = extra.personTypePrices?.[type.toLowerCase()];
            const typePrice = typePrices?.[currencyKey] ?? price;
            return total + typePrice * count;
          }, 0);
        }
        return price * totalGuests;
      default:
        return price * quantity;
    }
  };

  const calculateTotal = () => {
    return Object.entries(selectedExtras).reduce((total, [extraId, quantity]) => {
      const extra = extras.find((e) => e.id === extraId);
      if (!extra) return total;
      return total + calculateExtraSubtotal(extra, quantity);
    }, 0);
  };

  const handleSubmit = () => {
    const selections = Object.entries(selectedExtras).map(([id, quantity]) => ({
      id,
      quantity,
    }));
    onSubmit(selections);
  };

  const formatPrice = (amount: number) => {
    const currencyCode = currency || 'EUR';
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currencyCode,
    }).format(amount);
  };

  const getPricingDescription = (extra: ListingExtraForBooking): string => {
    const price = currency === 'TND' ? extra.priceTnd : extra.priceEur;

    switch (extra.pricingType) {
      case 'per_person':
        return `${formatPrice(price)} ${t('per_traveller')} (${totalGuests} ${t('guests') || 'guests'})`;
      case 'per_booking':
        return `${formatPrice(price)} ${t('for_entire_group')}`;
      case 'per_unit':
        return `${formatPrice(price)} ${t('per_item')}`;
      case 'per_person_type':
        return t('variable_pricing') || 'Variable pricing by person type';
      default:
        return formatPrice(price);
    }
  };

  // Filter extras based on shouldDisplay
  const visibleExtras = useMemo(() => {
    return extras.filter((extra) => {
      // If shouldDisplay is explicitly false, hide it
      if (extra.shouldDisplay === false) return false;
      return true;
    });
  }, [extras]);

  // Count hidden extras
  const hiddenExtrasCount = extras.length - visibleExtras.length;

  // Group visible extras by category
  const groupedExtras = useMemo(() => {
    const groups: Record<string, ListingExtraForBooking[]> = {};
    visibleExtras.forEach((extra) => {
      const category = extra.category || 'other';
      if (!groups[category]) {
        groups[category] = [];
      }
      groups[category].push(extra);
    });
    return groups;
  }, [visibleExtras]);

  // Calculate units needed for capacity-based extras
  const getUnitsNeeded = (extra: ListingExtraForBooking): number => {
    if (!extra.capacityPerUnit || extra.capacityPerUnit <= 0) {
      return 1;
    }
    return Math.ceil(totalGuests / extra.capacityPerUnit);
  };

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-900 mb-2">{t('extras_title')}</h2>
        <p className="text-gray-600">{t('extras_subtitle')}</p>
        {hiddenExtrasCount > 0 && (
          <p className="text-sm text-gray-500 mt-2">
            {t('hidden_extras', { count: hiddenExtrasCount }) ||
              `${hiddenExtrasCount} extras not available for your group size`}
          </p>
        )}
      </div>

      {visibleExtras.length === 0 ? (
        <div className="text-center py-12">
          <p className="text-gray-500">{t('no_extras')}</p>
        </div>
      ) : (
        <div className="space-y-6">
          {Object.entries(groupedExtras).map(([category, categoryExtras]) => (
            <div key={category}>
              <h3 className="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <span>{CATEGORY_ICONS[category] || '📦'}</span>
                {t(`category_${category}`) || category.charAt(0).toUpperCase() + category.slice(1)}
              </h3>

              <div className="space-y-3">
                {categoryExtras.map((extra) => {
                  const isSelected = !!selectedExtras[extra.id];
                  const quantity = selectedExtras[extra.id] || 1;
                  const subtotal = isSelected ? calculateExtraSubtotal(extra, quantity) : 0;

                  return (
                    <div
                      key={extra.id}
                      className={`border rounded-lg p-4 transition-all ${
                        isSelected
                          ? 'border-primary bg-primary/5'
                          : 'border-gray-200 hover:border-gray-300'
                      } ${extra.isRequired ? 'ring-2 ring-warning/20' : ''}`}
                    >
                      <div className="flex items-start justify-between gap-4">
                        <div className="flex-1">
                          <div className="flex items-center gap-3">
                            <input
                              type="checkbox"
                              id={`extra-${extra.id}`}
                              checked={isSelected}
                              onChange={() => handleToggleExtra(extra.id, extra)}
                              disabled={extra.isRequired}
                              className="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed"
                            />
                            <label
                              htmlFor={`extra-${extra.id}`}
                              className={`font-medium text-gray-900 ${!extra.isRequired ? 'cursor-pointer' : ''}`}
                            >
                              {extra.name}
                              {extra.isFeatured && (
                                <span className="ml-2 px-2 py-0.5 bg-warning-light text-warning-dark text-xs rounded-full">
                                  {t('recommended') || 'Recommended'}
                                </span>
                              )}
                              {extra.isRequired && (
                                <span className="ml-2 px-2 py-0.5 bg-error-light text-error-dark text-xs rounded-full">
                                  {t('required') || 'Required'}
                                </span>
                              )}
                            </label>
                          </div>

                          {extra.shortDescription && (
                            <p className="mt-2 ml-8 text-sm text-gray-600">
                              {extra.shortDescription}
                            </p>
                          )}

                          <div className="mt-2 ml-8 text-sm text-gray-500">
                            {getPricingDescription(extra)}
                            {extra.trackInventory && extra.inventoryCount !== null && (
                              <span
                                className={`ml-2 ${extra.inventoryCount <= 5 ? 'text-warning' : ''}`}
                              >
                                ({extra.inventoryCount} {t('available') || 'available'})
                              </span>
                            )}
                            {extra.capacityPerUnit && extra.capacityPerUnit > 0 && (
                              <span className="ml-2 text-gray-500">
                                ({extra.capacityPerUnit} {t('people_per_unit') || 'people per unit'}
                                )
                              </span>
                            )}
                          </div>
                          {extra.capacityPerUnit && totalGuests > extra.capacityPerUnit && (
                            <div className="mt-1 ml-8 text-xs text-amber-600">
                              {t('requires_units', { count: getUnitsNeeded(extra) }) ||
                                `Requires ${getUnitsNeeded(extra)} units for your group of ${totalGuests}`}
                            </div>
                          )}
                        </div>

                        <div className="flex items-center gap-4">
                          {isSelected &&
                            extra.allowQuantityChange &&
                            extra.pricingType !== 'per_person' && (
                              <div className="flex items-center gap-2">
                                <button
                                  type="button"
                                  onClick={() =>
                                    handleQuantityChange(extra.id, quantity - 1, extra)
                                  }
                                  disabled={quantity <= (extra.minQuantity || 1)}
                                  className="w-8 h-8 flex items-center justify-center border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                                  aria-label="Decrease quantity"
                                >
                                  −
                                </button>
                                <span className="w-8 text-center font-medium">{quantity}</span>
                                <button
                                  type="button"
                                  onClick={() =>
                                    handleQuantityChange(extra.id, quantity + 1, extra)
                                  }
                                  disabled={
                                    extra.maxQuantity !== null && quantity >= extra.maxQuantity
                                  }
                                  className="w-8 h-8 flex items-center justify-center border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                                  aria-label="Increase quantity"
                                >
                                  +
                                </button>
                              </div>
                            )}
                          <div className="text-right min-w-[100px]">
                            <span className="font-semibold text-gray-900">
                              {isSelected
                                ? formatPrice(subtotal)
                                : formatPrice(currency === 'TND' ? extra.priceTnd : extra.priceEur)}
                            </span>
                            {isSelected && extra.pricingType !== 'per_booking' && (
                              <div className="text-xs text-gray-500">
                                {t(
                                  PRICING_TYPE_TRANSLATION_KEYS[
                                    extra.pricingType as ExtraPricingType
                                  ]
                                )}
                              </div>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          ))}
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
            className="flex-1 px-6 py-3 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer"
          >
            {t('back')}
          </button>
        )}
        <button
          type="button"
          onClick={handleSubmit}
          className="flex-1 px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors cursor-pointer"
          data-testid="continue-to-billing"
        >
          {t('continue')}
        </button>
      </div>
    </div>
  );
}
