/**
 * Mirror of apps/laravel-api/app/Support/PricingUnitLabel.php — keep in lockstep.
 *
 * Resolves the optional vendor-supplied unit label (e.g. "par jetski") for the
 * requested locale. Falls back: requested locale → any non-empty locale → null.
 * Whitespace-only strings are treated as empty.
 */

// Zod's translatableSchema.partial().passthrough() produces an object whose
// extra keys are typed `unknown`. We accept that shape — the runtime check
// below filters non-strings.
export type TranslatableMap = { [k: string]: unknown } | null | undefined;

interface PricingShape {
  unitLabel?: TranslatableMap;
  // snake_case escape hatch for any caller passing the raw API payload pre-camel
  unit_label?: TranslatableMap;
}

export function getPricingUnitLabel(
  pricing: PricingShape | null | undefined,
  locale: string
): string | null {
  if (!pricing) return null;

  const map = pricing.unitLabel ?? pricing.unit_label ?? null;
  if (!map) return null;

  const requested = map[locale];
  if (typeof requested === 'string' && requested.trim() !== '') {
    return requested.trim();
  }

  for (const value of Object.values(map)) {
    if (typeof value === 'string' && value.trim() !== '') {
      return value.trim();
    }
  }

  return null;
}
