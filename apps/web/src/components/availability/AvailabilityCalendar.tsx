'use client';

import { useState, useEffect } from 'react';
import { useTranslations, useLocale } from 'next-intl';
import {
  format,
  startOfMonth,
  endOfMonth,
  eachDayOfInterval,
  isSameMonth,
  isSameDay,
  addMonths,
  subMonths,
  startOfWeek,
  endOfWeek,
  isBefore,
  startOfDay,
} from 'date-fns';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { AvailabilitySlot } from '@go-adventure/schemas';
import { getDateFnsLocale, getLocalizedWeekdays } from '@/lib/date-locale';

interface AvailabilityCalendarProps {
  slots: AvailabilitySlot[];
  onDateSelect: (date: Date) => void;
  selectedDate?: Date;
  className?: string;
}

export default function AvailabilityCalendar({
  slots,
  onDateSelect,
  selectedDate,
  className = '',
}: AvailabilityCalendarProps) {
  const t = useTranslations('availability');
  const locale = useLocale();
  const dateFnsLocale = getDateFnsLocale(locale);
  const weekdays = getLocalizedWeekdays(locale, 1); // Week starts on Monday

  const [currentMonth, setCurrentMonth] = useState<Date | null>(null);
  const [today, setToday] = useState<Date | null>(null);

  // Initialize dates on client side only to avoid hydration mismatch
  useEffect(() => {
    const now = new Date();
    setCurrentMonth(now);
    setToday(startOfDay(now));
  }, []);

  // Don't render until dates are initialized
  if (!currentMonth || !today) {
    return (
      <div className={`space-y-4 ${className}`}>
        <div className="h-96 animate-pulse rounded-lg bg-neutral-100" />
      </div>
    );
  }

  const monthStart = startOfMonth(currentMonth);
  const monthEnd = endOfMonth(currentMonth);
  const calendarStart = startOfWeek(monthStart, { weekStartsOn: 1 }); // Monday
  const calendarEnd = endOfWeek(monthEnd, { weekStartsOn: 1 });

  const calendarDays = eachDayOfInterval({ start: calendarStart, end: calendarEnd });

  const getDateStatus = (date: Date): 'available' | 'limited' | 'sold_out' | 'blocked' | null => {
    const dateStr = format(date, 'yyyy-MM-dd');
    const daySlots = slots.filter((slot) => slot.start.startsWith(dateStr));

    if (daySlots.length === 0) return null;

    // Calculate total remaining capacity for this date
    const totalRemaining = daySlots.reduce((total, slot) => {
      return total + (slot.remainingCapacity ?? slot.capacity ?? 0);
    }, 0);

    // If no capacity left, it's sold out regardless of slot status
    if (totalRemaining === 0) return 'sold_out';

    const allBlocked = daySlots.every((slot) => slot.status === 'blocked');
    if (allBlocked) return 'blocked';

    // Check for available slots with remaining capacity
    const hasAvailable = daySlots.some(
      (slot) => slot.status === 'available' && (slot.remainingCapacity ?? slot.capacity ?? 0) > 0
    );
    const hasLimited = daySlots.some(
      (slot) => slot.status === 'limited' && (slot.remainingCapacity ?? slot.capacity ?? 0) > 0
    );

    if (hasAvailable) return 'available';
    if (hasLimited) return 'limited';

    // Fallback: if slots exist but no capacity, mark as sold out
    return 'sold_out';
  };

  const getRemainingCapacity = (date: Date): number => {
    const dateStr = format(date, 'yyyy-MM-dd');
    const daySlots = slots.filter((slot) => slot.start.startsWith(dateStr));

    if (daySlots.length === 0) return 0;

    // Sum up remaining capacity from all slots for this date
    return daySlots.reduce((total, slot) => {
      return total + (slot.remainingCapacity || 0);
    }, 0);
  };

  const getStatusColor = (status: string | null): string => {
    switch (status) {
      case 'available':
        return 'bg-green-100 text-green-900 hover:bg-green-200';
      case 'limited':
        return 'bg-yellow-100 text-yellow-900 hover:bg-yellow-200';
      case 'sold_out':
        return 'bg-neutral-100 text-neutral-400 cursor-not-allowed';
      case 'blocked':
        return 'bg-neutral-50 text-neutral-300 cursor-not-allowed';
      default:
        return 'bg-white text-neutral-400';
    }
  };

  const handlePreviousMonth = () => {
    setCurrentMonth((prev) => subMonths(prev, 1));
  };

  const handleNextMonth = () => {
    setCurrentMonth((prev) => addMonths(prev, 1));
  };

  const handleDayClick = (date: Date) => {
    const status = getDateStatus(date);
    if (status && status !== 'sold_out' && status !== 'blocked') {
      onDateSelect(date);
    }
  };

  const isPastDate = (date: Date) => isBefore(date, today);

  return (
    <div className={`space-y-4 ${className}`}>
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-semibold text-neutral-900">
          {format(currentMonth, 'MMMM yyyy', { locale: dateFnsLocale })}
        </h3>
        <div className="flex gap-2">
          <button
            onClick={handlePreviousMonth}
            className="rounded-lg border border-neutral-300 p-2 hover:bg-neutral-50"
            aria-label={t('aria_labels.previous_month')}
          >
            <ChevronLeft className="h-5 w-5" />
          </button>
          <button
            onClick={handleNextMonth}
            className="rounded-lg border border-neutral-300 p-2 hover:bg-neutral-50"
            aria-label={t('aria_labels.next_month')}
          >
            <ChevronRight className="h-5 w-5" />
          </button>
        </div>
      </div>

      {/* Weekday headers */}
      <div className="grid grid-cols-7 gap-1">
        {weekdays.map((day, index) => (
          <div
            key={`${day}-${index}`}
            className="p-2 text-center text-xs font-medium text-neutral-600"
          >
            {day}
          </div>
        ))}
      </div>

      {/* Calendar grid */}
      <div className="grid grid-cols-7 gap-1">
        {calendarDays.map((day) => {
          const status = getDateStatus(day);
          const remainingCapacity = getRemainingCapacity(day);
          const isCurrentMonth = isSameMonth(day, currentMonth);
          const isSelected = selectedDate && isSameDay(day, selectedDate);
          const isPast = isPastDate(day);
          const isDisabled = isPast || !status || status === 'sold_out' || status === 'blocked';

          return (
            <button
              key={day.toISOString()}
              onClick={() => handleDayClick(day)}
              disabled={isDisabled}
              className={`
                relative aspect-square rounded-lg p-2 text-sm transition-colors
                ${!isCurrentMonth ? 'opacity-40' : ''}
                ${isSelected ? 'ring-2 ring-primary' : ''}
                ${isPast ? 'cursor-not-allowed opacity-50' : ''}
                ${isCurrentMonth && !isPast ? getStatusColor(status) : 'text-neutral-400'}
              `}
              data-testid={`date-${format(day, 'yyyy-MM-dd')}`}
            >
              <span className="block">{format(day, 'd')}</span>

              {/* Capacity badge - small number only */}
              {remainingCapacity > 0 && !isPast && isCurrentMonth && (
                <span
                  className={`absolute bottom-0.5 right-0.5 text-[7px] leading-none font-semibold px-0.5 py-0.5 rounded ${
                    status === 'limited'
                      ? 'bg-yellow-300/90 text-yellow-950'
                      : 'bg-green-300/90 text-green-950'
                  }`}
                  title={`${remainingCapacity} ${t('spots_left_short')}`}
                >
                  {remainingCapacity}
                </span>
              )}
            </button>
          );
        })}
      </div>

      {/* Legend */}
      <div className="flex flex-wrap gap-4 text-xs">
        <div className="flex items-center gap-2">
          <div className="h-4 w-4 rounded bg-green-100 border border-green-300" />
          <span className="text-neutral-600">{t('status.available')}</span>
        </div>
        <div className="flex items-center gap-2">
          <div className="h-4 w-4 rounded bg-yellow-100 border border-yellow-300" />
          <span className="text-neutral-600">{t('status.limited')}</span>
        </div>
        <div className="flex items-center gap-2">
          <div className="h-4 w-4 rounded bg-neutral-100 border border-neutral-300" />
          <span className="text-neutral-600">{t('status.sold_out')}</span>
        </div>
      </div>
    </div>
  );
}
