/**
 * Tiered (positional) group-pricing math for the booking UI.
 *
 * ⚠️ MIRROR of the canonical PHP engine
 * `apps/laravel-api/app/Services/PriceCalculationService::calculateTieredTotal`.
 * Keep the two in LOCKSTEP. The server is always the source of truth for the
 * amount actually charged (the hold's `priceSnapshot`); this helper exists only
 * to render an instant live total in the booking panel BEFORE a hold is created.
 *
 * Formula for a group of N travellers:
 *
 *     total(N) = floor(N / K) * T[K] + T[N mod K]      (T[0] = 0)
 *
 * where K = number of tiers and T[k] = the cumulative total for k travellers.
 */

export interface TierLike {
  position?: number;
  tndTotal?: number;
  eurTotal?: number;
  displayTotal?: number;
}

/**
 * Resolve the cumulative tier totals [T1, T2, …] for a currency, sorted by
 * position. Prefers the explicit per-currency column; falls back to the
 * server-computed `displayTotal`.
 */
export function tierCumulativeTotals(
  tiers: TierLike[] | null | undefined,
  currency: string
): number[] {
  if (!tiers || tiers.length === 0) return [];

  const isTnd = String(currency).toUpperCase() === 'TND';

  return [...tiers]
    .sort((a, b) => (a.position ?? 0) - (b.position ?? 0))
    .map((t) => Number((isTnd ? t.tndTotal : t.eurTotal) ?? t.displayTotal ?? 0));
}

/**
 * Total price for `travelers` people under tiered pricing. Returns 0 for an
 * empty tier table or non-positive headcount (matches the PHP guard).
 */
export function computeTieredTotal(
  tiers: TierLike[] | null | undefined,
  travelers: number,
  currency: string
): number {
  const totals = tierCumulativeTotals(tiers, currency);
  const k = totals.length;

  if (k === 0 || travelers <= 0) return 0;

  const fullCycles = Math.floor(travelers / k);
  const remainder = travelers % k;
  const remainderTotal = remainder === 0 ? 0 : (totals[remainder - 1] ?? 0);

  return Math.round((fullCycles * totals[k - 1] + remainderTotal) * 100) / 100;
}
