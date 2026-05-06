# Listing Pricing Unit Label — Design Spec

**Date:** 2026-05-06
**Branch:** dev
**Ticket origin:** Live client report — vendor uses person-type names like "Jetski" / "Per jetski" as a workaround for "per machine" pricing, but the public site still labels the headline price "par personne", which is misleading.
**Status:** Approved by Product Owner (2026-05-06) — proceeding to implementation plan.

## 1. Context

Djerba Fun's listing pricing system stores per-person prices keyed by free-form "person types" (Adult, Child, etc.) inside a `pricing` JSON column on `listings`. A vendor on production wanted "per machine" pricing for a jetski rental. The platform offers no such concept, so the vendor labelled the person type "Per jetski" / "Par jetski". This produces a real-world inconsistency:

- **Per-row labels** (Voyageurs / Participants / Price-breakdown) read correctly: _"Par jetski — 35 €"_.
- **Headline price suffix** (listing card, listing detail hero, sticky booking panel, cart, checkout summary, voucher PDF, confirmation email) still renders **"35 € par personne"**.

The client wants the headline suffix to match the per-row label so customers see consistent terminology end-to-end.

## 2. Root cause

`apps/web/src/components/molecules/PriceDisplay.tsx` (lines 65, 74) picks the suffix from a binary boolean `perNight ?? false`, choosing between two i18n keys: `per_person` and `per_night`. There is no third path.

`apps/laravel-api/app/Http/Resources/ListingResource.php::formatPricing()` exposes `pricing_model` (`per_person` | `per_night`) but no field for "what unit is the price expressed in". The same gap exists in the voucher PDF (`VoucherPdfService`) and confirmation email Blade templates.

The vendor's only escape hatch today is labelling the person type itself — which leaks into the right place (the breakdown row) but never reaches the headline suffix.

## 3. Scope

### In scope

- Replace the headline "par personne / per person" suffix with a vendor-supplied **unit label** wherever the suffix appears today, when the vendor has set one.
- Coverage: listing card, listing detail headline, sticky booking panels (mobile + desktop), cart line items, checkout summary, price breakdown table headline, voucher PDF, booking confirmation email, Filament admin/vendor display.
- Vendor-controlled, opt-in per listing. Empty value → identical render to today.

### Out of scope (explicit deferrals)

- New `pricing_model` enum value (`per_unit`).
- Schema.org `priceSpecification` JSON-LD migration to `UnitPriceSpecification`.
- Voyageurs / Participants step text adaptation ("driver" vs "traveller").
- Search / filter UI labels ("price under X / personne").
- Bulk vendor data migration; existing live listings keep their workaround.
- Pricing math changes (still per-row \* quantity).

## 4. Solution: vendor-controlled `unit_label` inside listings.pricing JSON

Add **one optional translatable field** inside the existing `pricing` JSON column on `listings`:

```jsonc
{
  "pricing_model": "per_person",
  "unit_label": { "fr": "par jetski", "en": "per jetski" }, // NEW — optional
  "person_types": [
    /* unchanged */
  ],
}
```

A central helper resolves the displayed suffix per locale:

```
unit_label[locale]            (if vendor has set it for the requested locale)
unit_label[other locale]      (fallback so EN visitor never sees a raw key when only FR is set)
t('per_night')                (when pricing_model = per_night)
t('per_person')               (default — identical to today's behavior)
```

Whitespace-only strings are treated as empty.

### Why this approach

- **No DB migration**: the field lives inside a JSON column the platform already manages.
- **No enum change, no schema lock**: legacy listings render byte-identically.
- **Slot price overrides unaffected**: they match by `person_types[].key`, which we never touch.
- **Server + client share the same fallback contract** via mirrored helpers (PHP + TS).

### Why not the alternatives

- **Add `pricing_model = per_unit`**: structurally cleaner but requires migration validation, Filament dropdown, schema package update, accommodation-pricing-model lock review. More risk for the same end result.
- **Heuristic on min_quantity / single person-type**: brittle; would mis-classify legitimate single-type listings (e.g. adults-only events).
- **Hardcoded fix on the jetski listing slug**: helps no future machine-pricing listing.

## 5. Architecture (component diagram)

```
                          listings.pricing JSON
                                  │
              ┌───────────────────┴───────────────────┐
              │                                       │
              ▼                                       ▼
   PHP: PricingUnit::label(...)         Filament: Translatable text input
   (used by ListingResource,            in Vendor + Admin ListingResource
    VoucherPdfService, Blade emails)     above Person-Type-Pricing repeater
              │
              │ JSON: pricing.unitLabel  (camelCase on the wire, translatable map)
              ▼
   packages/schemas: pricing schema gains optional unitLabel
              │
              ▼
   TS: getPricingUnitLabel(pricing, pricingModel, t, locale)
   (used by PriceDisplay → BookingPanel, FixedBookingPanel, ListingCard,
    listing-detail headline, cart card, checkout summary)
```

Two helpers, one contract, identical fallback ladder. No business-logic duplication.

## 6. Component-by-component design

### 6.1 packages/schemas/

- Add optional `unitLabel: translatableSchema.optional()` to whichever pricing schema is exported (likely a `listingPricingSchema` or inline within `listingSchema.pricing`). Keep camelCase on the wire to match existing conventions.
- `pnpm build` so `apps/web` picks up the new type.

### 6.2 apps/laravel-api/

**Helper — single source of truth for fallback ladder:**

- New file: `app/Support/PricingUnit.php` exposing `PricingUnit::label(array $pricing, string $pricingModel, string $locale): string`.
- Inputs: the raw `pricing` array, the `pricing_model` string, and the request locale.
- Outputs: a localized suffix string, ready to display next to a price (e.g. `"par jetski"`, `"par personne"`, `"par nuit"`).
- Trim & null-empty handling at the helper level, not at call sites.

**Resource:**

- `app/Http/Resources/ListingResource.php::formatPricing()` emits `unitLabel` (camelCase, full translatable map `{fr, en}`), or omits it when not set.

**Filament:**

- `app/Filament/Vendor/Resources/ListingResource.php`: add a `Translatable` text field bound to `pricing.unit_label.fr` / `.en`, placed directly above the existing Person Type Pricing repeater (~line 1280). Help text in FR/EN: _"Optionnel — laisser vide pour 'par personne'. Exemples : 'par jetski', 'par quad'."_
- Mirror in `app/Filament/Admin/Resources/ListingResource.php`.
- Add a small column in the listing list table showing the unit label when set, so admins spot misconfigurations.

**Server-rendered artifacts:**

- `app/Services/VoucherPdfService.php`: replace hardcoded suffix with `PricingUnit::label(...)`.
- `resources/views/mail/booking-confirmation.blade.php` (and any voucher Blade): same.

### 6.3 apps/web/

**Helper — mirrors the PHP fallback ladder:**

- New file: `src/lib/utils/pricing-unit.ts`
- `getPricingUnitLabel(pricing: ListingPricing, pricingModel: string, t: TFn, locale: 'fr' | 'en'): string`
- Same fallback chain as PHP: requested locale → other locale → `t('per_night')` if accommodation → `t('per_person')`.

**Replace hardcoded suffix sites:**

- `src/components/molecules/PriceDisplay.tsx:65,74` — accept optional `unitLabel` prop, use helper.
- `src/components/molecules/ListingCard.tsx:148-150` — pass `pricing.unitLabel` through.
- `src/components/booking/BookingPanel.tsx:78` and `FixedBookingPanel.tsx` — same.
- Listing detail headline (`src/app/[locale]/[location]/[slug]/...`) — pass through.
- `src/components/cart/CartItemCard.tsx` and `CartCheckoutSummary.tsx` — pass through.
- `src/components/booking/ExtrasSelection.tsx` — **leave alone**. Extras are a separate pricing-type subsystem and changing them is out of scope.

**i18n:**

- No new keys. Vendor-supplied data is the override; existing keys (`per_person`, `per_night`) remain the fallback.

### 6.4 Seeder update (dev environments only)

`apps/laravel-api/database/seeders/OldSiteListingsSeeder.php` for `jetski-15-30-min`:

- Move `["fr" => "Par jetski", "en" => "Per jetski"]` from `person_types[0].label` into `pricing.unit_label`.
- Restore `person_types[0].label` to `["fr" => "Adulte", "en" => "Adult"]`.

Production data is left untouched. The vendor migrates via Filament at their own pace; until then the old workaround keeps rendering correctly because per-row labels still come from `person_types[].label`.

## 7. Edge cases — covered

| #   | Case                                                             | Handling                                                                                                   |
| --- | ---------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| 1   | Vendor leaves `unit_label` empty                                 | Falls back to `t('per_person')`. Identical to today.                                                       |
| 2   | Vendor sets only FR                                              | EN visitor sees the FR string (defensive fallback to other-locale before `t()`).                           |
| 3   | Vendor pastes whitespace                                         | Trimmed at helper; treated as empty.                                                                       |
| 4   | Long string ("par grande motomarine 4 places")                   | CSS truncate with `title` tooltip on cards; full text on detail/cart/PDF.                                  |
| 5   | Vendor clears the field                                          | Saves empty → reverts to `t('per_person')` everywhere.                                                     |
| 6   | Tour with Adulte / Enfant (no `unit_label`)                      | Headline: `par personne`. Untouched.                                                                       |
| 7   | Accommodation (`pricing_model = per_night`)                      | Headline: `par nuit`. Untouched.                                                                           |
| 8   | Multiple machine variants ("Jetski 1 place" + "Jetski 2 places") | Headline uses single listing-level `unit_label`; per-row breakdown still uses each `person_types[].label`. |
| 9   | Slot price overrides (`AvailabilitySlot.price_overrides`)        | Untouched. Match-by-`key` preserved.                                                                       |
| 10  | Mixed cart (jetski + tour + hotel)                               | Each line resolves its own `unit_label` independently.                                                     |
| 11  | Coupons / multi-currency / discounts                             | Unaffected — pricing math unchanged.                                                                       |
| 12  | Voyageurs / Participants step                                    | Unchanged — out of scope (deferred).                                                                       |

## 8. BDD acceptance criteria

```gherkin
Feature: Listing headline shows the vendor-defined pricing unit

  Background:
    Given a vendor logged into Filament

  Scenario: Setting a custom unit label updates the public site headline
    Given a "nautical" listing "Jetski 30 min" with person_types [{ key: "adult", label: "Adulte/Adult", tnd_price: 105, eur_price: 35 }]
      And no pricing.unit_label is set
     When I open the public listing detail in French
     Then I see "35 € par personne" in the headline price
     When the vendor sets pricing.unit_label to "par jetski" / "per jetski"
      And I reload the public listing detail in French
     Then I see "35 € par jetski" in the headline price
     When I switch to English
     Then I see "35 € per jetski" in the headline price

  Scenario: Empty unit label preserves legacy behavior
    Given a tour listing with adult/child person_types
      And no pricing.unit_label is set
     When I open the public listing detail in French
     Then I see "par personne" suffix on the headline price
      And I see "par personne" on the listing card in browse
      And I see "par personne" in the cart line item

  Scenario: Accommodation is unaffected
    Given an accommodation with pricing_model = "per_night"
     When I open the public listing detail
     Then I see "par nuit" in the headline price

  Scenario: Locale fallback when only FR is set
    Given a listing with pricing.unit_label = { fr: "par jetski" }
      And no English value
     When an English visitor opens the listing
     Then they see "par jetski" (FR fallback) and never the raw key

  Scenario: Suffix is consistent across surfaces
    Given a listing with pricing.unit_label = "par jetski" / "per jetski"
     When I add it to my cart and reach checkout
     Then the line item, cart summary, headline, and confirmation email
          all display "par jetski" (FR) / "per jetski" (EN)

  Scenario: Voucher PDF mirrors the website
    Given a confirmed booking on a jetski listing with unit_label set
     When I download the voucher PDF
     Then the price suffix reads "par jetski" / "per jetski"

  Scenario: Whitespace is treated as empty
    Given a vendor pastes "   " into the unit label field
     When the listing is rendered publicly
     Then the headline shows "par personne"
```

## 9. Test plan

### Backend (PHPUnit)

- `tests/Feature/Api/ListingResourcePricingUnitTest.php`
  - `pricing.unitLabel` is exposed when set
  - `pricing.unitLabel` is omitted when not set
  - Empty/whitespace not exposed
- `tests/Unit/Support/PricingUnitTest.php`
  - Returns FR string for FR locale when set
  - Falls back to other locale when requested locale missing
  - Falls back to `per_night` when `pricing_model = per_night`
  - Falls back to `per_person` otherwise
  - Trims whitespace and treats as empty
- `tests/Feature/Mail/BookingConfirmationPricingUnitTest.php`
  - Confirmation email contains the unit label when set
- `tests/Unit/Services/VoucherPdfServiceTest.php` (extend existing if present)
  - Voucher PDF rendering uses the helper

### Frontend (Playwright BDD)

- New: `apps/web/tests/e2e/listing-pricing-unit.spec.ts` covering all scenarios in §8.
- Run on Desktop Chrome + Mobile Chrome (per existing project config).

### Regression sweep (mandatory before commit)

- `make test-api`
- `pnpm exec playwright test -g "listing|booking|cart|checkout"`
- Manual: jetski (custom label), tour (Adulte/Enfant), accommodation (per_night), mixed cart — each in fr + en, desktop + mobile.
- Open voucher PDF for jetski + tour bookings.
- Open Mailpit confirmation email for jetski + tour bookings.
- Filament vendor: set unit label, save, reload, clear, save again — round-trip persistence.
- SEO sanity: view-source on jetski listing, confirm JSON-LD still validates (deferred to update `priceSpecification` shape).

## 10. Verification before declaring done

Per the live-production zero-regression policy:

1. All tests above green (no skipped, no flaky).
2. Manual verification matrix complete (4 listing types × 2 locales × 2 viewports + PDF + email + Filament round-trip).
3. Regression spec list:
   - Booking flow (slot select → hold → checkout → payment → voucher) on jetski + tour: end-to-end, no errors.
   - Cart with multiple listings: each suffix correct independently.
   - Coupon application: pricing math unchanged.
   - Currency switch (TND ↔ EUR): suffix unchanged, number switches as today.
4. Diff review: no untouched pricing math, no untouched i18n keys, helper has no business logic beyond fallback.

## 11. Critical files (read/modify)

**Read-only (already correct):**

- `/Users/otospexmob/djerba-fun/CLAUDE.md` — zero-regression policy, line 5–84.
- `/Users/otospexmob/djerba-fun/apps/laravel-api/database/migrations/2026_03_09_120000_add_accommodation_pricing_model.php` — confirms current `pricing_model` enum.
- `/Users/otospexmob/djerba-fun/apps/laravel-api/app/Models/Listing.php` lines 37–43 (accommodation lock), 309 (`pricing` cast).
- `/Users/otospexmob/djerba-fun/apps/laravel-api/app/Filament/Concerns/ResolvesListingPersonTypes.php` — pattern reference for translatable resolution.

**Modify:**

- `packages/schemas/src/listing.ts` (or equivalent) — extend pricing schema.
- `apps/laravel-api/app/Support/PricingUnit.php` — new helper.
- `apps/laravel-api/app/Http/Resources/ListingResource.php` — emit `unitLabel`.
- `apps/laravel-api/app/Filament/Vendor/Resources/ListingResource.php` — add Translatable field.
- `apps/laravel-api/app/Filament/Admin/Resources/ListingResource.php` — same.
- `apps/laravel-api/app/Services/VoucherPdfService.php` — use helper.
- `apps/laravel-api/resources/views/mail/booking-confirmation.blade.php` — use helper.
- `apps/laravel-api/database/seeders/OldSiteListingsSeeder.php` — migrate jetski seed data.
- `apps/web/src/lib/utils/pricing-unit.ts` — new TS helper.
- `apps/web/src/components/molecules/PriceDisplay.tsx` — wire helper.
- `apps/web/src/components/molecules/ListingCard.tsx` — pass through.
- `apps/web/src/components/booking/BookingPanel.tsx`, `FixedBookingPanel.tsx` — pass through.
- `apps/web/src/components/cart/CartItemCard.tsx`, `CartCheckoutSummary.tsx` — pass through.
- `apps/web/src/app/[locale]/[location]/[slug]/listing-detail-client.tsx` — pass through.

**New tests:**

- `apps/laravel-api/tests/Feature/Api/ListingResourcePricingUnitTest.php`
- `apps/laravel-api/tests/Unit/Support/PricingUnitTest.php`
- `apps/laravel-api/tests/Feature/Mail/BookingConfirmationPricingUnitTest.php`
- `apps/web/tests/e2e/listing-pricing-unit.spec.ts`

## 12. Risk register

| Risk                                                                  | Likelihood     | Mitigation                                                                                                                                                    |
| --------------------------------------------------------------------- | -------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| A pricing site is missed and still says "par personne" inconsistently | Medium         | Spec lists every site; central helper means one place to debug; E2E spec asserts every surface.                                                               |
| Voucher PDF / email rendering regressed                               | Low            | Dedicated tests; Mailpit manual verification; rollback by removing helper call (suffix string is the only diff).                                              |
| Schema rebuild forgotten → frontend can't see new optional field      | Low            | `pnpm build` in execution plan; tsc `--noEmit` confirms before merge.                                                                                         |
| Vendor enters HTML/JS in field (XSS)                                  | Low            | Filament `Translatable` already escapes; React renders as text; PDF rendering escapes. Add explicit `htmlspecialchars`-style sanitization at helper boundary. |
| Schema.org JSON-LD becomes semantically wrong                         | Low (cosmetic) | Out of scope, tracked in §3 deferrals.                                                                                                                        |

---

End of spec.
