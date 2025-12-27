'use client';

import { useTranslations } from 'next-intl';
import { format, parseISO } from 'date-fns';
import { Clock, Users } from 'lucide-react';
import type { AvailabilitySlot } from '@go-adventure/schemas';

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

  const availableSlots = slots.filter(
    (slot) => slot.status === 'available' || slot.status === 'limited'
  );

  const formatTime = (dateTimeStr: string) => {
    return format(parseISO(dateTimeStr), 'HH:mm');
  };

  const formatPrice = (amount: number, currency: string) => {
    return `${(amount / 100).toFixed(0)} ${currency}`;
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

  return (
    <div className={`space-y-4 ${className}`}>
      <h4 className="font-semibold text-neutral-900">{t('select_time')}</h4>

      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {availableSlots.map((slot) => {
          const isSelected = selectedSlot?.id === slot.id;
          const remainingText = getRemainingText(slot);

          return (
            <button
              key={slot.id}
              onClick={() => onSlotSelect(slot)}
              data-testid={`time-slot-${formatTime(slot.start)}`}
              className={`
                relative rounded-lg border-2 p-4 text-left transition-all
                ${isSelected ? 'border-primary ring-2 ring-primary ring-opacity-50' : getSlotStatusColor(slot.status)}
              `}
            >
              {/* Time */}
              <div className="mb-2 flex items-center gap-2">
                <Clock className="h-4 w-4 text-neutral-600" />
                <span className="font-semibold text-neutral-900">
                  {formatTime(slot.start)} - {formatTime(slot.end)}
                </span>
              </div>

              {/* Capacity */}
              <div className="mb-2 flex items-center gap-2 text-sm text-neutral-600">
                <Users className="h-4 w-4" />
                <span data-testid="slot-capacity">
                  {slot.remainingCapacity ?? slot.capacity} / {slot.capacity} {t('available')}
                </span>
              </div>

              {/* Price */}
              <div className="font-semibold text-primary">
                {formatPrice(slot.basePrice, slot.currency)}
              </div>

              {/* Limited spots warning */}
              {remainingText && (
                <div className="mt-2 text-xs font-medium text-yellow-700">{remainingText}</div>
              )}

              {/* Selected indicator */}
              {isSelected && (
                <div className="absolute right-2 top-2">
                  <div className="h-5 w-5 rounded-full bg-primary flex items-center justify-center">
                    <svg className="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fillRule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clipRule="evenodd"
                      />
                    </svg>
                  </div>
                </div>
              )}
            </button>
          );
        })}
      </div>
    </div>
  );
}
