'use client';

import { useTranslations } from 'next-intl';
import { Minus, Plus, Users, AlertTriangle } from 'lucide-react';

interface TravelerCountSelectorProps {
  value: number;
  onChange: (count: number) => void;
  maxCapacity: number;
  minTravelers?: number;
}

/**
 * Single "number of travellers" stepper for tiered-pricing listings (which have
 * no person types). Mirrors PersonTypeSelector's capacity indicator and testid
 * conventions so e2e selectors stay predictable.
 */
export function TravelerCountSelector({
  value,
  onChange,
  maxCapacity,
  minTravelers = 1,
}: TravelerCountSelectorProps) {
  const t = useTranslations('booking');

  const canIncrement = value < maxCapacity;
  const canDecrement = value > minTravelers;

  const handleChange = (delta: number) => {
    const next = Math.min(maxCapacity, Math.max(minTravelers, value + delta));
    onChange(next);
  };

  return (
    <div className="space-y-4">
      {/* Capacity indicator (mirrors PersonTypeSelector) */}
      <div
        className="bg-success-light border border-success/20 rounded-lg p-3 flex items-center justify-between"
        data-testid="capacity-indicator"
      >
        <div className="flex items-center gap-2">
          <Users className="h-5 w-5 text-success" />
          <span className="text-sm font-medium text-success-dark">{t('available_capacity')}</span>
        </div>
        <div className="text-right">
          <div className="text-lg font-bold text-success-dark">
            {maxCapacity - value} / {maxCapacity}
          </div>
          <div className="text-xs text-success-dark/80">{t('spots_remaining')}</div>
        </div>
      </div>

      {/* Single traveller-count row */}
      <div className="flex items-center justify-between border border-neutral-200 rounded-lg p-3">
        <div className="flex-1">
          <div className="font-medium text-neutral-900">{t('traveler_count_label')}</div>
          <div className="text-sm text-neutral-500">{t('group_of', { count: value })}</div>
        </div>

        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={() => handleChange(-1)}
            disabled={!canDecrement}
            className="p-2 rounded-full border border-neutral-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-neutral-50 transition-colors cursor-pointer"
            aria-label={t('traveler_count_label')}
            data-testid="traveler-count-decrement"
          >
            <Minus className="h-4 w-4" />
          </button>
          <span className="w-8 text-center font-medium" data-testid="traveler-count">
            {value}
          </span>
          <button
            type="button"
            onClick={() => handleChange(1)}
            disabled={!canIncrement}
            className="p-2 rounded-full border border-neutral-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-neutral-50 transition-colors cursor-pointer"
            aria-label={t('traveler_count_label')}
            data-testid="traveler-count-increment"
          >
            <Plus className="h-4 w-4" />
          </button>
        </div>
      </div>

      {maxCapacity - value <= 3 && value < maxCapacity && (
        <div className="border-t border-neutral-200 pt-4">
          <p className="text-sm text-warning font-medium flex items-center gap-1">
            <AlertTriangle className="h-4 w-4" />
            {t('only_x_spots_left', { count: maxCapacity - value })}
          </p>
        </div>
      )}
    </div>
  );
}

TravelerCountSelector.displayName = 'TravelerCountSelector';
