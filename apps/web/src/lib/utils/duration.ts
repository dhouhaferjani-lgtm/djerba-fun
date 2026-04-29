/**
 * Slot duration formatting helpers.
 *
 * Two outputs:
 *   - `formatDurationCompact(minutes)` — "1h", "1h 30min", "3h", "30 min".
 *     Used as the visible chip next to a slot's time range. Locale-agnostic
 *     because the unit suffixes are the same in en + fr in the project's
 *     translation files (see `messages/{en,fr}.json` "duration.*").
 *   - `formatDurationVerbose(minutes, t)` — "1 hour", "1 hour 30 minutes",
 *     "3 hours", "30 minutes". Used as the slot button's `aria-label` so
 *     screen readers announce the duration cleanly instead of literally
 *     reading "one h". Routes through next-intl's `t()` function so EN
 *     and FR plurals are handled by ICU MessageFormat.
 *
 * Edge cases handled:
 *   - 0 minutes → empty string (caller decides whether to render anything).
 *   - < 60 minutes → "30 min" (compact) / "30 minutes" (verbose).
 *   - Exact hour multiples → "1h" / "3h" (compact) / "1 hour" / "3 hours" (verbose).
 *   - Mixed → "1h 30min" / "1 hour 30 minutes".
 */

export function formatDurationCompact(minutes: number): string {
  if (!Number.isFinite(minutes) || minutes <= 0) {
    return '';
  }

  const hours = Math.floor(minutes / 60);
  const remaining = minutes % 60;

  if (hours === 0) {
    return `${remaining} min`;
  }
  if (remaining === 0) {
    return `${hours}h`;
  }
  return `${hours}h ${remaining}min`;
}

type Translator = (key: string, values?: Record<string, number | string>) => string;

/**
 * Build an accessible verbose label for a slot's duration.
 *
 * Expects three ICU-plural translation keys under `duration.*`:
 *   - duration.verbose_minutes_only   → "{count, plural, =1 {1 minute} other {# minutes}}"
 *   - duration.verbose_hours_only     → "{count, plural, =1 {1 hour} other {# hours}}"
 *   - duration.verbose_hours_minutes  → composes the two
 */
export function formatDurationVerbose(minutes: number, t: Translator): string {
  if (!Number.isFinite(minutes) || minutes <= 0) {
    return '';
  }

  const hours = Math.floor(minutes / 60);
  const remaining = minutes % 60;

  if (hours === 0) {
    return t('duration.verbose_minutes_only', { count: remaining });
  }
  if (remaining === 0) {
    return t('duration.verbose_hours_only', { count: hours });
  }
  return t('duration.verbose_hours_minutes', { hours, minutes: remaining });
}
