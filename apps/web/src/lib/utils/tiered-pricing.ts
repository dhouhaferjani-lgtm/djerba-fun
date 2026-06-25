/**
 * Optional group-discount math for the booking UI — greedy "circle" packing.
 *
 * ⚠️ MIRROR of the canonical PHP engine
 * `apps/laravel-api/app/Services/PriceCalculationService::groupDiscountTotal`.
 * Keep the two in LOCKSTEP. The server is always the source of truth for the
 * amount actually charged (the hold's `priceSnapshot`); this helper renders an
 * instant live total + breakdown in the booking panel BEFORE a hold is created.
 *
 * Model: a tiered listing keeps its normal per-person-type pricing. On top of
 * that, the vendor sets OPTIONAL flat totals for group sizes 2–5. For a given
 * headcount we repeatedly apply the LARGEST configured group price that fits,
 * let the remainder cycle back through the group prices, and charge any final
 * leftover (smaller than the smallest configured group) as individuals at the
 * base per-person rate. A headcount below the smallest configured group keeps
 * normal per-person-type pricing (so adult/child rates still apply).
 */

export interface TierLike {
  groupSize?: number;
  tndTotal?: number;
  eurTotal?: number;
  displayTotal?: number;
}

/** One packed group bundle, e.g. {size:3, count:2} = two groups of three. */
export interface GroupBundle {
  size: number;
  count: number;
  unitTotal: number;
}

/** Decomposition of a headcount into group bundles + leftover individuals. */
export interface GroupPacking {
  applies: boolean; // true when at least one group bundle is used
  bundles: GroupBundle[]; // largest size first
  leftover: number; // individuals charged at the base per-person rate
  total: number; // bundles + leftover * basePerPerson, rounded to 2dp
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
 * Greedy "circle" packing of `headcount` into the configured group bundles plus
 * leftover individuals. `applies` is false when no group bundle is used (no
 * tiers, or headcount below the smallest configured group) — in which case the
 * caller keeps normal per-person-type pricing.
 */
export function packGroupTiers(
  tiers: TierLike[] | null | undefined,
  headcount: number,
  currency: string,
  basePerPerson: number
): GroupPacking {
  const totals = groupTierTotals(tiers, currency);
  const sizes = Object.keys(totals)
    .map(Number)
    .sort((a, b) => b - a); // largest first

  if (sizes.length === 0 || headcount < 1) {
    return { applies: false, bundles: [], leftover: Math.max(0, headcount), total: 0 };
  }

  const smallest = Math.min(...sizes);

  // Below the smallest configured group -> no bundle applies.
  if (headcount < smallest) {
    return { applies: false, bundles: [], leftover: headcount, total: 0 };
  }

  let remaining = headcount;
  let total = 0;
  const counts = new Map<number, number>();

  while (remaining >= smallest) {
    const g = sizes.find((s) => s <= remaining)!; // always exists (remaining >= smallest)
    counts.set(g, (counts.get(g) ?? 0) + 1);
    total += totals[g];
    remaining -= g;
  }

  if (remaining > 0) {
    total += remaining * basePerPerson;
  }

  const bundles = [...counts.entries()]
    .sort((a, b) => b[0] - a[0])
    .map(([size, count]) => ({ size, count, unitTotal: totals[size] }));

  return { applies: true, bundles, leftover: remaining, total: Math.round(total * 100) / 100 };
}

/**
 * Final total for `headcount` travellers under greedy group packing. Returns
 * the packed total when at least one group bundle applies, otherwise
 * `normalTotal` (the normal per-person-type total) unchanged.
 */
export function computeGroupDiscountTotal(
  tiers: TierLike[] | null | undefined,
  headcount: number,
  normalTotal: number,
  currency: string,
  basePerPerson: number
): number {
  const packing = packGroupTiers(tiers, headcount, currency, basePerPerson);

  return packing.applies ? packing.total : normalTotal;
}
