/**
 * Optional group-discount math for the booking UI.
 *
 * ⚠️ MIRROR of the canonical PHP engine
 * `apps/laravel-api/app/Services/PriceCalculationService::groupDiscountTotal`.
 * Keep the two in LOCKSTEP. The server is always the source of truth for the
 * amount actually charged (the hold's `priceSnapshot`); this helper exists only
 * to render an instant live total in the booking panel BEFORE a hold is created.
 *
 * Model: a tiered listing keeps its normal per-person-type pricing. On top of
 * that, the vendor may set an OPTIONAL flat total for an exact group size in the
 * range 2–5. When the current headcount matches a size that has a total set, the
 * group total replaces the normal total. Otherwise (size 1, an unset size, or
 * 6+) the normal per-person total applies unchanged.
 */

export interface TierLike {
  groupSize?: number;
  tndTotal?: number;
  eurTotal?: number;
  displayTotal?: number;
}

/**
 * Resolve the configured group totals as a size-keyed map for a currency.
 * Prefers the explicit per-currency column; falls back to the server-computed
 * `displayTotal`. Only sizes 2–5 with a positive total are included.
 */
export function groupTierTotals(
  tiers: TierLike[] | null | undefined,
  currency: string
): Record<number, number> {
  const out: Record<number, number> = {};
  if (!tiers || tiers.length === 0) return out;

  const isTnd = String(currency).toUpperCase() === 'TND';

  for (const t of tiers) {
    const size = Number(t.groupSize ?? 0);
    if (size < 2 || size > 5) continue;

    const total = Number((isTnd ? t.tndTotal : t.eurTotal) ?? t.displayTotal ?? 0);
    if (total > 0) out[size] = total;
  }

  return out;
}

/**
 * Final total for `headcount` travellers. Returns the optional group total when
 * the headcount is 2–5 AND a total is configured for that exact size; otherwise
 * returns `normalTotal` (the normal per-person-type total) unchanged.
 */
export function computeGroupDiscountTotal(
  tiers: TierLike[] | null | undefined,
  headcount: number,
  normalTotal: number,
  currency: string
): number {
  if (headcount < 2 || headcount > 5) return normalTotal;

  const totals = groupTierTotals(tiers, currency);
  const groupTotal = totals[headcount];

  if (groupTotal === undefined) return normalTotal;

  return Math.round(groupTotal * 100) / 100;
}
