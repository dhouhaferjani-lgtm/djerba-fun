'use client';

import { useTranslations } from 'next-intl';
import { Clock, Users } from 'lucide-react';
import type { AvailabilitySlot } from '@djerba-fun/schemas';
import { formatDurationCompact, formatDurationVerbose } from '@/lib/utils/duration';

/**
 * Whether two slots in this picker would render the same headline price.
 * When prices diverge (e.g. a 1-hour at 50 TND vs a 3-hour at 120 TND in the
 * same day), the picker prints the per-slot price next to each option so the
 * customer sees the duration → price relationship at a glance.
 */
function pricesAreUniform(slots: AvailabilitySlot[]): boolean {
  if (slots.length < 2) return true;
  const first = slots[0]?.displayPrice;
  return slots.every((s) => (s.displayPrice ?? null) === (first ?? null));
}

interface TimeSlotPickerProps {
  slots: AvailabilitySlot[];
  selectedSlot?: AvailabilitySlot;
  onSlotSelect: (slot: AvailabilitySlot) => void;
  className?: string;
}

export default function TimeSlotPicker({
  slots,
  selectedSlot,
  onSlotSelect,
  className = '',
}: TimeSlotPickerProps) {
  const t = useTranslations('availability');
  // The duration formatter routes through ICU plurals at the message-tree root,
  // so we use the global `t` (no namespace) to access "duration.*" keys.
  const tDuration = useTranslations();

  const availableSlots = slots.filter(
    (slot) => slot.status === 'available' || slot.status === 'limited'
  );

  // Display the slot's time-of-day exactly as the vendor configured it. The
  // API exposes both `start` (UTC ISO timestamp, e.g. 2026-05-02T09:00:00+00:00)
  // and `startTime` (plain "HH:MM:SS" string). For an opening-hour label, only
  // the latter is correct — parsing the timestamp and formatting in the
  // browser's local timezone shifts every slot by the viewer's UTC offset
  // (e.g. +1h in Tunisia), which is wrong for "tour starts at 09:00".
  const formatTime = (slot: AvailabilitySlot, which: 'start' | 'end'): string => {
    const direct = which === 'start' ? slot.startTime : slot.endTime;
    if (typeof direct === 'string' && direct.length >= 5) {
      return direct.slice(0, 5);
    }
    // Defensive fallback if `startTime`/`endTime` are absent: pull HH:mm out
    // of the ISO string without applying any timezone math.
    const iso = which === 'start' ? slot.start : slot.end;
    const match = (iso ?? '').match(/T(\d{2}:\d{2})/);
    return match ? match[1] : '';
  };

  const getSlotStatusColor = (status: string) => {
    switch (status) {
      case 'available':
        return 'border-success bg-success-light hover:bg-success-light/80';
      case 'limited':
        return 'border-warning bg-warning-light hover:bg-warning-light/80';
      default:
        return 'border-neutral-300 bg-neutral-50';
    }
  };

  const getRemainingText = (slot: AvailabilitySlot) => {
    const remaining = slot.remainingCapacity ?? slot.capacity;
    if (slot.status === 'limited' && remaining > 0) {
      return t('remaining_spots', { count: remaining });
    }
    return null;
  };

  if (availableSlots.length === 0) {
    return (
      <div className={`text-center py-8 ${className}`}>
        <p className="text-neutral-600">{t('no_slots_available')}</p>
      </div>
    );
  }

  // Only show the per-slot price chip when slots actually diverge (e.g.
  // 1-hour vs 3-hour duration with their own price overrides). When every
  // slot inherits the same listing-level price, the existing booking-panel
  // headline already displays it — repeating it here would be visual noise.
  const showPerSlotPrice = !pricesAreUniform(availableSlots);

  return (
    <div className={`space-y-4 ${className}`}>
      <h4 className="font-semibold text-neutral-900">{t('select_time')}</h4>

      <div className="space-y-2">
        {availableSlots.map((slot) => {
          const isSelected = selectedSlot?.id === slot.id;
          const remainingText = getRemainingText(slot);
          const priceForSlot =
            slot.displayPrice !== undefined && slot.displayPrice !== null
              ? Math.round(slot.displayPrice)
              : null;
          const currencySymbol = slot.displayCurrency === 'TND' ? 'TND' : '€';

          // Iteration-3 duration display. Driven by the per-rule `showDuration`
          // flag the API resolves from `availability_rules.show_duration`.
          // Compact label sits inside the chip; verbose label is folded into
          // the slot button's accessible name so screen readers announce
          // "1 hour" rather than literally reading "one h".
          const durationMinutes = slot.durationMinutes ?? 0;
          const renderDuration = !!slot.showDuration && durationMinutes > 0;
          const durationCompact = renderDuration ? formatDurationCompact(durationMinutes) : '';
          const durationVerbose = renderDuration
            ? formatDurationVerbose(durationMinutes, tDuration)
            : '';

          const ariaLabel = [
            `${formatTime(slot, 'start')} – ${formatTime(slot, 'end')}`,
            durationVerbose,
            `${slot.remainingCapacity ?? slot.capacity} / ${slot.capacity} ${t('available')}`,
          ]
            .filter(Boolean)
            .join(', ');

          return (
            <button
              key={slot.id}
              onClick={() => onSlotSelect(slot)}
              data-testid="time-slot"
              data-slot-time={formatTime(slot, 'start')}
              data-slot-price={priceForSlot ?? ''}
              data-slot-duration={renderDuration ? durationMinutes : ''}
              aria-label={ariaLabel}
              className={`
                w-full rounded-lg border-2 p-3 text-left transition-all cursor-pointer
                ${isSelected ? 'border-primary ring-2 ring-primary ring-opacity-50' : getSlotStatusColor(slot.status)}
              `}
            >
              <div className="flex items-center justify-between gap-3">
                {/* Time + (optional) duration chip */}
                <div className="flex items-center gap-2 min-w-0">
                  <Clock className="h-4 w-4 text-neutral-600 shrink-0" />
                  <span className="font-semibold text-neutral-900 whitespace-nowrap">
                    {formatTime(slot, 'start')} – {formatTime(slot, 'end')}
                  </span>
                  {renderDuration && (
                    <span
                      data-testid="slot-duration"
                      className="text-xs font-medium text-neutral-600 whitespace-nowrap"
                      aria-hidden="true"
                    >
                      · {durationCompact}
                    </span>
                  )}
                </div>

                {/* Capacity + price + selected indicator on the right */}
                <div className="flex items-center gap-2 shrink-0">
                  {showPerSlotPrice && priceForSlot !== null && (
                    <span
                      data-testid="slot-price"
                      className="text-sm font-semibold text-primary whitespace-nowrap"
                    >
                      {currencySymbol === '€'
                        ? `${currencySymbol}${priceForSlot}`
                        : `${priceForSlot} ${currencySymbol}`}
                    </span>
                  )}
                  <span
                    data-testid="slot-capacity"
                    className="flex items-center gap-1.5 text-sm font-medium text-neutral-700"
                  >
                    <Users className="h-4 w-4" />
                    {slot.remainingCapacity ?? slot.capacity} / {slot.capacity} {t('available')}
                  </span>
                  {isSelected && (
                    <span className="h-5 w-5 rounded-full bg-primary flex items-center justify-center">
                      <svg className="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path
                          fillRule="evenodd"
                          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                          clipRule="evenodd"
                        />
                      </svg>
                    </span>
                  )}
                </div>
              </div>

              {/* Limited spots warning sits as a second row inside the button */}
              {remainingText && (
                <div className="mt-2 text-xs font-medium text-warning-dark">{remainingText}</div>
              )}
            </button>
          );
        })}
      </div>
    </div>
  );
}
