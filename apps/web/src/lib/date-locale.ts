import { enUS, fr } from 'date-fns/locale';
import type { Locale as DateFnsLocale } from 'date-fns';

/**
 * Maps next-intl locale codes to date-fns locales
 */
export function getDateFnsLocale(locale: string): DateFnsLocale {
  switch (locale) {
    case 'fr':
      return fr;
    case 'en':
      return enUS;
    // Arabic support can be added when needed: case 'ar': return ar;
    default:
      return enUS;
  }
}

/**
 * Get localized weekday names (short format)
 * @param locale - The locale code (fr, en, ar)
 * @param weekStartsOn - 0 (Sunday) or 1 (Monday)
 * @returns Array of 7 weekday abbreviations
 */
export function getLocalizedWeekdays(locale: string, weekStartsOn: 0 | 1 = 1): string[] {
  const dateFnsLocale = getDateFnsLocale(locale);
  const weekdays = dateFnsLocale.localize?.day
    ? Array.from({ length: 7 }, (_, i) => dateFnsLocale.localize!.day(i, { width: 'abbreviated' }))
    : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

  // Rotate array if week starts on Monday
  if (weekStartsOn === 1) {
    return [...weekdays.slice(1), weekdays[0]];
  }

  return weekdays;
}
