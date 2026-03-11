'use client';

import { useState, useEffect, useCallback } from 'react';
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
  isAfter,
  isWithinInterval,
  startOfDay,
  differenceInDays,
  addDays,
} from 'date-fns';
import { ChevronLeft, ChevronRight, Calendar, Moon } from 'lucide-react';
import type { AvailabilitySlot } from '@djerba-fun/schemas';
import { getDateFnsLocale, getLocalizedWeekdays } from '@/lib/date-locale';
import { cn } from '@/lib/utils/cn';

interface DateRange {
  checkIn: Date;
  checkOut: Date;
  nights: number;
}

interface AccommodationDateRangePickerProps {
  slots: AvailabilitySlot[];
  minimumNights?: number;
  maximumNights?: number | null;
  nightlyPrice: number;
  currency: string;
  checkInTime?: string;
  onSelectionChange: (selection: DateRange | null) => void;
  className?: string;
  serviceTypeColor?: string; // For themed border/accent
}

export default function AccommodationDateRangePicker({
  slots,
  minimumNights = 1,
  maximumNights = null,
  nightlyPrice,
  currency,
  checkInTime = '15:00',
  onSelectionChange,
  className = '',
  serviceTypeColor = 'primary',
}: AccommodationDateRangePickerProps) {
  const t = useTranslations('accommodation');
  const locale = useLocale();
  const dateFnsLocale = getDateFnsLocale(locale);
  const weekdays = getLocalizedWeekdays(locale, 1); // Week starts on Monday

  const [currentMonth, setCurrentMonth] = useState<Date | null>(null);
  const [today, setToday] = useState<Date | null>(null);
  const [checkInDate, setCheckInDate] = useState<Date | null>(null);
  const [checkOutDate, setCheckOutDate] = useState<Date | null>(null);
  const [hoverDate, setHoverDate] = useState<Date | null>(null);
  const [selectionMode, setSelectionMode] = useState<'check-in' | 'check-out'>('check-in');
  const [validationMessage, setValidationMessage] = useState<string | null>(null);

  // Initialize dates on client side only to avoid hydration mismatch
  useEffect(() => {
    const now = new Date();
    setCurrentMonth(now);
    setToday(startOfDay(now));
  }, []);

  // Notify parent when selection changes
  useEffect(() => {
    if (checkInDate && checkOutDate) {
      const nights = differenceInDays(checkOutDate, checkInDate);
      onSelectionChange({
        checkIn: checkInDate,
        checkOut: checkOutDate,
        nights,
      });
    } else {
      onSelectionChange(null);
    }
  }, [checkInDate, checkOutDate, onSelectionChange]);

  const getDateStatus = useCallback(
    (date: Date): 'available' | 'blocked' | 'past' | null => {
      if (!today || isBefore(date, today)) return 'past';

      const dateStr = format(date, 'yyyy-MM-dd');
      const daySlots = slots.filter((slot) => slot.start.startsWith(dateStr));

      if (daySlots.length === 0) return null;

      const hasAvailable = daySlots.some(
        (slot) =>
          (slot.status === 'available' || slot.status === 'limited') &&
          (slot.remainingCapacity ?? slot.capacity ?? 0) > 0
      );

      return hasAvailable ? 'available' : 'blocked';
    },
    [slots, today]
  );

  const isDateInRange = useCallback(
    (date: Date): boolean => {
      if (!checkInDate) return false;

      const endDate = checkOutDate || hoverDate;
      if (!endDate) return false;

      return isWithinInterval(date, {
        start: checkInDate,
        end: endDate,
      });
    },
    [checkInDate, checkOutDate, hoverDate]
  );

  const isValidCheckOutDate = useCallback(
    (date: Date): boolean => {
      if (!checkInDate) return false;

      const nights = differenceInDays(date, checkInDate);

      // Must be after check-in
      if (nights <= 0) return false;

      // Check minimum nights
      if (nights < minimumNights) return false;

      // Check maximum nights
      if (maximumNights && nights > maximumNights) return false;

      // Check if all dates in range are available
      for (let i = 0; i < nights; i++) {
        const checkDate = addDays(checkInDate, i);
        const status = getDateStatus(checkDate);
        if (status !== 'available') return false;
      }

      return true;
    },
    [checkInDate, minimumNights, maximumNights, getDateStatus]
  );

  const handleDayClick = (date: Date) => {
    const status = getDateStatus(date);
    if (status !== 'available') return;

    setValidationMessage(null); // Clear previous message

    if (selectionMode === 'check-in') {
      setCheckInDate(date);
      setCheckOutDate(null);
      setSelectionMode('check-out');
    } else {
      // Check-out mode
      if (!checkInDate) return;

      // Same-day click = 1-night stay (auto-advance to next day)
      if (isSameDay(date, checkInDate)) {
        const nextDay = addDays(date, 1);
        const nextDayStatus = getDateStatus(nextDay);

        if (nextDayStatus === 'available' || nextDayStatus === null) {
          // Next day is available (or no slot data, assume OK) - set as checkout
          setCheckOutDate(nextDay);
          setSelectionMode('check-in');
        } else {
          // Next day is blocked - show error
          setValidationMessage(t('next_day_unavailable'));
        }
        return;
      }

      // Clicked before check-in - reset to new check-in
      if (isBefore(date, checkInDate)) {
        setCheckInDate(date);
        setCheckOutDate(null);
        setSelectionMode('check-out');
        return;
      }

      // Normal checkout selection
      if (isAfter(date, checkInDate) && isValidCheckOutDate(date)) {
        setCheckOutDate(date);
        setSelectionMode('check-in');
      } else if (isAfter(date, checkInDate) && !isValidCheckOutDate(date)) {
        // Show why it's invalid
        const nights = differenceInDays(date, checkInDate);
        if (nights < minimumNights) {
          setValidationMessage(t('minimum_stay', { count: minimumNights }));
        } else if (maximumNights && nights > maximumNights) {
          setValidationMessage(t('maximum_stay', { count: maximumNights }));
        } else {
          setValidationMessage(t('blocked_dates_in_range'));
        }
      }
    }
  };

  const handleDayHover = (date: Date) => {
    if (selectionMode === 'check-out' && checkInDate) {
      setHoverDate(date);
    }
  };

  const handleClearSelection = () => {
    setCheckInDate(null);
    setCheckOutDate(null);
    setHoverDate(null);
    setSelectionMode('check-in');
    setValidationMessage(null);
  };

  // Don't render until dates are initialized
  if (!currentMonth || !today) {
    return (
      <div className={cn('space-y-4', className)}>
        <div className="h-96 animate-pulse rounded-lg bg-neutral-100" />
      </div>
    );
  }

  const nextMonth = addMonths(currentMonth, 1);
  const nights = checkInDate && checkOutDate ? differenceInDays(checkOutDate, checkInDate) : 0;
  const totalPrice = nights * nightlyPrice;

  const renderMonth = (month: Date) => {
    const monthStart = startOfMonth(month);
    const monthEnd = endOfMonth(month);
    const calendarStart = startOfWeek(monthStart, { weekStartsOn: 1 });
    const calendarEnd = endOfWeek(monthEnd, { weekStartsOn: 1 });
    const calendarDays = eachDayOfInterval({ start: calendarStart, end: calendarEnd });

    return (
      <div className="flex-1">
        <h4 className="mb-3 text-center font-semibold text-neutral-900">
          {format(month, 'MMMM yyyy', { locale: dateFnsLocale })}
        </h4>

        {/* Weekday headers */}
        <div className="grid grid-cols-7 gap-1 mb-1">
          {weekdays.map((day, index) => (
            <div
              key={`${day}-${index}`}
              className="p-1 text-center text-xs font-medium text-neutral-500"
            >
              {day}
            </div>
          ))}
        </div>

        {/* Calendar grid */}
        <div className="grid grid-cols-7 gap-1">
          {calendarDays.map((day) => {
            const status = getDateStatus(day);
            const isCurrentMonth = isSameMonth(day, month);
            const isCheckIn = checkInDate && isSameDay(day, checkInDate);
            const isCheckOut = checkOutDate && isSameDay(day, checkOutDate);
            const isInRange = isDateInRange(day) && !isCheckIn && !isCheckOut;
            const isDisabled = !isCurrentMonth || status !== 'available';
            const isHoverEnd =
              hoverDate && isSameDay(day, hoverDate) && selectionMode === 'check-out';
            const wouldBeValid =
              selectionMode === 'check-out' && checkInDate && isAfter(day, checkInDate)
                ? isValidCheckOutDate(day)
                : true;

            return (
              <button
                key={day.toISOString()}
                onClick={() => handleDayClick(day)}
                onMouseEnter={() => handleDayHover(day)}
                disabled={isDisabled}
                className={cn(
                  'relative aspect-square rounded-md p-1 text-sm transition-all',
                  !isCurrentMonth && 'opacity-0 pointer-events-none',
                  isCurrentMonth && status === 'available' && 'hover:bg-neutral-100 cursor-pointer',
                  isCurrentMonth &&
                    status === 'blocked' &&
                    'bg-neutral-50 text-neutral-300 line-through cursor-not-allowed',
                  isCurrentMonth && status === 'past' && 'text-neutral-300 cursor-not-allowed',
                  isCheckIn && `bg-${serviceTypeColor}-600 text-white rounded-l-md`,
                  isCheckOut && `bg-${serviceTypeColor}-600 text-white rounded-r-md`,
                  isInRange && `bg-${serviceTypeColor}-100`,
                  isHoverEnd &&
                    !isCheckOut &&
                    wouldBeValid &&
                    `bg-${serviceTypeColor}-200 rounded-r-md`,
                  isHoverEnd && !isCheckOut && !wouldBeValid && 'bg-red-100',
                  selectionMode === 'check-out' &&
                    checkInDate &&
                    isAfter(day, checkInDate) &&
                    !wouldBeValid &&
                    'opacity-50'
                )}
                data-testid={`date-${format(day, 'yyyy-MM-dd')}`}
              >
                <span className="block text-center">{format(day, 'd')}</span>
              </button>
            );
          })}
        </div>
      </div>
    );
  };

  return (
    <div className={cn('space-y-4', className)} data-testid="accommodation-date-picker">
      {/* Navigation */}
      <div className="flex items-center justify-between">
        <button
          onClick={() => setCurrentMonth((prev) => (prev ? subMonths(prev, 1) : new Date()))}
          className="rounded-lg border border-neutral-300 p-2 hover:bg-neutral-50 cursor-pointer"
          aria-label={t('previous_month')}
        >
          <ChevronLeft className="h-5 w-5" />
        </button>
        <button
          onClick={() => setCurrentMonth((prev) => (prev ? addMonths(prev, 1) : new Date()))}
          className="rounded-lg border border-neutral-300 p-2 hover:bg-neutral-50 cursor-pointer"
          aria-label={t('next_month')}
        >
          <ChevronRight className="h-5 w-5" />
        </button>
      </div>

      {/* Two-month calendar */}
      <div className="flex flex-col md:flex-row gap-6">
        {renderMonth(currentMonth)}
        {renderMonth(nextMonth)}
      </div>

      {/* Selection summary */}
      <div className="rounded-lg border border-neutral-200 bg-neutral-50 p-4 space-y-3">
        {/* Date display */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-2">
              <Calendar className="h-4 w-4 text-neutral-500" />
              <div>
                <p className="text-xs text-neutral-500">{t('check_in')}</p>
                <p className="font-medium">
                  {checkInDate
                    ? format(checkInDate, 'EEE, d MMM', { locale: dateFnsLocale })
                    : t('select_date')}
                </p>
              </div>
            </div>

            <div className="h-8 border-l border-neutral-300" />

            <div className="flex items-center gap-2">
              <Calendar className="h-4 w-4 text-neutral-500" />
              <div>
                <p className="text-xs text-neutral-500">{t('check_out')}</p>
                <p className="font-medium">
                  {checkOutDate
                    ? format(checkOutDate, 'EEE, d MMM', { locale: dateFnsLocale })
                    : t('select_date')}
                </p>
              </div>
            </div>
          </div>

          {(checkInDate || checkOutDate) && (
            <button
              onClick={handleClearSelection}
              className="text-sm text-neutral-500 hover:text-neutral-700 underline"
            >
              {t('clear')}
            </button>
          )}
        </div>

        {/* Nights and price */}
        {nights > 0 && (
          <div className="flex items-center justify-between pt-2 border-t border-neutral-200">
            <div className="flex items-center gap-2">
              <Moon className="h-4 w-4 text-neutral-500" />
              <span className="text-sm">
                {nights} {nights === 1 ? t('night') : t('nights')}
              </span>
            </div>
            <div className="text-right">
              <p className="text-xs text-neutral-500">
                {nightlyPrice.toLocaleString(locale)} {currency} × {nights} {t('nights')}
              </p>
              <p className="font-semibold text-lg">
                {totalPrice.toLocaleString(locale)} {currency}
              </p>
            </div>
          </div>
        )}

        {/* Validation messages */}
        {validationMessage && (
          <p className="text-xs text-red-600 flex items-center gap-1">
            <span className="inline-block w-1 h-1 rounded-full bg-red-600" />
            {validationMessage}
          </p>
        )}
        {!validationMessage && selectionMode === 'check-out' && checkInDate && !checkOutDate && (
          <p className="text-xs text-amber-600">{t('select_checkout')}</p>
        )}
        {minimumNights > 1 && !checkOutDate && !validationMessage && (
          <p className="text-xs text-amber-600">{t('minimum_stay', { count: minimumNights })}</p>
        )}
        {maximumNights && !checkOutDate && !validationMessage && (
          <p className="text-xs text-neutral-500">{t('maximum_stay', { count: maximumNights })}</p>
        )}
      </div>

      {/* Check-in time info */}
      {checkInTime && (
        <p className="text-xs text-neutral-500 text-center">
          {t('check_in_time', { time: checkInTime })}
        </p>
      )}
    </div>
  );
}
