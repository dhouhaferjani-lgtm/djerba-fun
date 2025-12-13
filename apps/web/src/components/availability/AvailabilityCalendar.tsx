'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
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
  const [currentMonth, setCurrentMonth] = useState(new Date());

  const monthStart = startOfMonth(currentMonth);
  const monthEnd = endOfMonth(currentMonth);
  const calendarStart = startOfWeek(monthStart, { weekStartsOn: 1 }); // Monday
  const calendarEnd = endOfWeek(monthEnd, { weekStartsOn: 1 });

  const calendarDays = eachDayOfInterval({ start: calendarStart, end: calendarEnd });

  const getDateStatus = (date: Date): 'available' | 'limited' | 'sold_out' | 'blocked' | null => {
    const dateStr = format(date, 'yyyy-MM-dd');
    const daySlots = slots.filter((slot) => slot.start.startsWith(dateStr));

    if (daySlots.length === 0) return null;

    const hasAvailable = daySlots.some((slot) => slot.status === 'available');
    const hasLimited = daySlots.some((slot) => slot.status === 'limited');
    const allSoldOut = daySlots.every((slot) => slot.status === 'sold_out');
    const allBlocked = daySlots.every((slot) => slot.status === 'blocked');

    if (allBlocked) return 'blocked';
    if (allSoldOut) return 'sold_out';
    if (hasAvailable) return 'available';
    if (hasLimited) return 'limited';
    return null;
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

  const today = startOfDay(new Date());
  const isPastDate = (date: Date) => isBefore(date, today);

  return (
    <div className={`space-y-4 ${className}`}>
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-semibold text-neutral-900">
          {format(currentMonth, 'MMMM yyyy')}
        </h3>
        <div className="flex gap-2">
          <button
            onClick={handlePreviousMonth}
            className="rounded-lg border border-neutral-300 p-2 hover:bg-neutral-50"
            aria-label="Previous month"
          >
            <ChevronLeft className="h-5 w-5" />
          </button>
          <button
            onClick={handleNextMonth}
            className="rounded-lg border border-neutral-300 p-2 hover:bg-neutral-50"
            aria-label="Next month"
          >
            <ChevronRight className="h-5 w-5" />
          </button>
        </div>
      </div>

      {/* Weekday headers */}
      <div className="grid grid-cols-7 gap-1">
        {['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map((day) => (
          <div key={day} className="p-2 text-center text-xs font-medium text-neutral-600">
            {day}
          </div>
        ))}
      </div>

      {/* Calendar grid */}
      <div className="grid grid-cols-7 gap-1">
        {calendarDays.map((day) => {
          const status = getDateStatus(day);
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
            >
              <span className="block">{format(day, 'd')}</span>
              {status && !isPast && isCurrentMonth && (
                <span className="absolute bottom-1 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-current" />
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
