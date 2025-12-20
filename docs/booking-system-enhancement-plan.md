# Booking System Enhancement Plan

> **Status**: All Phases Complete
> **Created**: 2025-12-16
> **Last Updated**: 2025-12-17
> **Scope**: Multi-guest booking, age-based pricing, UX improvements

---

## Table of Contents

1. [Overview](#overview)
2. [Current State Analysis](#current-state-analysis)
3. [Phase 1: Dedicated Checkout Page](#phase-1-dedicated-checkout-page)
4. [Phase 2: Age-Based Pricing](#phase-2-age-based-pricing)
5. [Phase 3: Multi-Guest Booking](#phase-3-multi-guest-booking)
6. [Phase 4: UX Improvements (Modal for Availability)](#phase-4-ux-improvements)
7. [Implementation Order](#implementation-order)

---

## Overview

### Goals

1. **Dedicated checkout page** - Persist booking state across refresh via URL
2. **Age-based pricing** - Support adult/child/infant pricing tiers
3. **Multi-guest booking** - Collect traveler info for each guest (family bookings)
4. **Better UX** - Modal/popup for availability selection instead of inline expansion

### Use Case Example

A father booking a desert tour for his family:

- 2 Adults (himself + wife) @ €100 each
- 2 Children (ages 8 and 12) @ €50 each
- 1 Infant (age 2) @ Free

**Total**: €300 instead of flat €100 × 5 = €500

---

## Current State Analysis

### What Already Exists (Schema Level)

The Zod schema in `packages/schemas/src/index.ts` already defines age-based pricing:

```typescript
export const personTypeSchema = z.object({
  key: z.string(),                    // "adult", "child", "infant"
  label: translatableSchema,          // {en: "Adult", fr: "Adulte"}
  price: z.number().int().nonnegative(),
  minAge: z.number().int().nonnegative().nullable(),
  maxAge: z.number().int().positive().nullable(),
  minQuantity: z.number().int().nonnegative().default(0),
  maxQuantity: z.number().int().positive().nullable(),
});

export const pricingSchema = z.object({
  base: z.number().int().nonnegative(),
  currency: z.string().length(3),
  personTypes: z.array(personTypeSchema),  // ALREADY DEFINED!
  groupDiscount: z.object({...}).nullable(),
});
```

### What's Missing

| Component             | Current                       | Needed                                 |
| --------------------- | ----------------------------- | -------------------------------------- |
| **Hold/Booking**      | Stores `quantity` only        | Need `breakdown: {adult: 2, child: 1}` |
| **Price Calculation** | `price × quantity`            | Sum of `personType.price × count`      |
| **Frontend Form**     | Single traveler info          | N forms for N guests                   |
| **API Validation**    | Single `traveler_info` object | Array of travelers                     |
| **UI Components**     | No modal/dialog               | Need reusable Dialog component         |

---

## Phase 1: Dedicated Checkout Page

### Problem

Booking wizard embedded in listing page. Refresh = lost progress.

### Solution

Create `/checkout/[holdId]` page that fetches all data from URL parameter.

### Files to Modify

| File                                                              | Change                          |
| ----------------------------------------------------------------- | ------------------------------- |
| `apps/laravel-api/routes/api.php`                                 | Add `GET /holds/{hold}` route   |
| `apps/laravel-api/app/Http/Controllers/Api/V1/HoldController.php` | Add `showById()` method         |
| `apps/laravel-api/app/Http/Resources/BookingHoldResource.php`     | Include listing + slot data     |
| `apps/web/src/lib/api/client.ts`                                  | Add `getHold(holdId)` method    |
| `apps/web/src/lib/api/hooks.ts`                                   | Add `useHold(holdId)` hook      |
| `apps/web/src/app/[locale]/checkout/[holdId]/page.tsx`            | Create new page                 |
| `apps/web/src/app/[locale]/checkout/page.tsx`                     | Delete (obsolete)               |
| `apps/web/src/app/[locale]/listings/[slug]/page.tsx`              | Redirect to checkout after hold |

### URL Flow

```
/listings/tour-name → Book Now → /checkout/<hold-uuid>
                                      ↓
                      Fetch hold + listing + slot from API
                                      ↓
                      BookingWizard renders with persisted state
```

---

## Phase 2: Age-Based Pricing

### Database Changes

**Migration: Add breakdown field to holds and bookings**

```php
// add_person_type_breakdown_to_holds_and_bookings.php
Schema::table('booking_holds', function (Blueprint $table) {
    $table->json('person_type_breakdown')->nullable();
    // Example: {"adult": 2, "child": 1, "infant": 1}
});

Schema::table('bookings', function (Blueprint $table) {
    $table->json('person_type_breakdown')->nullable();
});
```

### Backend Changes

**New Service: `app/Services/PriceCalculationService.php`**

```php
class PriceCalculationService
{
    public function calculateTotal(
        Listing $listing,
        array $breakdown  // ["adult" => 2, "child" => 1]
    ): array {
        $personTypes = $listing->pricing['personTypes'] ?? [];
        $subtotal = 0;
        $details = [];

        foreach ($breakdown as $typeKey => $quantity) {
            $type = collect($personTypes)->firstWhere('key', $typeKey);
            if ($type && $quantity > 0) {
                $lineTotal = $type['price'] * $quantity;
                $subtotal += $lineTotal;
                $details[] = [
                    'type' => $typeKey,
                    'label' => $type['label'],
                    'price' => $type['price'],
                    'quantity' => $quantity,
                    'total' => $lineTotal,
                ];
            }
        }

        // Apply group discount if applicable
        $totalGuests = array_sum($breakdown);
        $discount = $this->calculateGroupDiscount($listing, $totalGuests, $subtotal);

        return [
            'breakdown' => $details,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $subtotal - $discount,
        ];
    }
}
```

**Update: `CreateHoldRequest.php`**

```php
public function rules(): array
{
    return [
        'slot_id' => ['required', 'integer', Rule::exists('availability_slots', 'id')...],
        'session_id' => ['nullable', 'string', 'max:255'],
        // NEW: Person type breakdown instead of just quantity
        'person_types' => ['required', 'array', 'min:1'],
        'person_types.adult' => ['nullable', 'integer', 'min:0'],
        'person_types.child' => ['nullable', 'integer', 'min:0'],
        'person_types.infant' => ['nullable', 'integer', 'min:0'],
    ];
}
```

### Frontend Changes

**New Component: `PersonTypeSelector.tsx`**

```typescript
interface PersonTypeSelectorProps {
  personTypes: PersonType[]; // From listing.pricing.personTypes
  value: Record<string, number>; // { adult: 2, child: 1 }
  onChange: (breakdown: Record<string, number>) => void;
  currency: string;
  maxCapacity: number; // From slot.remainingCapacity
}

// Renders:
// Adults (18+)    €100    [-] 2 [+]
// Children (4-17) €50     [-] 1 [+]
// Infants (0-3)   Free    [-] 0 [+]
// ─────────────────────────────────
// Total: 3 guests          €250
```

**Update: Listing detail page**

Replace simple quantity selector with `PersonTypeSelector` component.

### Files to Modify

| File                                                               | Change                             |
| ------------------------------------------------------------------ | ---------------------------------- |
| `apps/laravel-api/database/migrations/`                            | New migration for breakdown fields |
| `apps/laravel-api/app/Models/BookingHold.php`                      | Add `person_type_breakdown` cast   |
| `apps/laravel-api/app/Models/Booking.php`                          | Add `person_type_breakdown` cast   |
| `apps/laravel-api/app/Services/PriceCalculationService.php`        | NEW                                |
| `apps/laravel-api/app/Http/Requests/CreateHoldRequest.php`         | Accept person_types object         |
| `apps/laravel-api/app/Http/Controllers/Api/V1/HoldController.php`  | Use price calculation              |
| `apps/laravel-api/app/Http/Resources/AvailabilitySlotResource.php` | Include personTypes                |
| `apps/web/src/components/booking/PersonTypeSelector.tsx`           | NEW                                |
| `apps/web/src/app/[locale]/listings/[slug]/page.tsx`               | Use PersonTypeSelector             |
| `apps/web/src/components/booking/BookingReview.tsx`                | Show price breakdown               |

---

## Phase 3: Multi-Guest Booking

### Concept

When booking for 2 adults + 1 child, collect traveler info for each:

```
Traveler 1 (Adult)
├── First Name: John
├── Last Name: Doe
├── Email: john@example.com
├── Date of Birth: 1985-03-15
└── Phone: +1234567890

Traveler 2 (Adult)
├── First Name: Jane
├── Last Name: Doe
├── Email: jane@example.com
├── Date of Birth: 1987-07-22
└── Phone: (optional)

Traveler 3 (Child)
├── First Name: Tommy
├── Last Name: Doe
├── Date of Birth: 2016-09-10
└── (no email/phone for minors)
```

### Backend Changes

**Update: `CreateBookingRequest.php`**

```php
public function rules(): array
{
    return [
        'hold_id' => ['required', 'exists:booking_holds,id'],
        'session_id' => ['nullable', 'string'],
        // Array of travelers instead of single object
        'travelers' => ['required', 'array', 'min:1'],
        'travelers.*.first_name' => ['required', 'string', 'max:255'],
        'travelers.*.last_name' => ['required', 'string', 'max:255'],
        'travelers.*.email' => ['nullable', 'email', 'max:255'],
        'travelers.*.phone' => ['nullable', 'string', 'max:50'],
        'travelers.*.date_of_birth' => ['nullable', 'date'],
        'travelers.*.person_type' => ['required', 'string', 'in:adult,child,infant'],
    ];
}
```

**Update: `BookingService.php`**

```php
public function createFromHold(
    BookingHold $hold,
    array $travelers,  // Array instead of single object
    array $extras = [],
    ?int $authenticatedUserId = null
): Booking {
    // Validate traveler count matches hold breakdown
    $expectedCount = array_sum($hold->person_type_breakdown ?? []);
    if (count($travelers) !== $expectedCount) {
        throw new ValidationException("Expected {$expectedCount} travelers");
    }

    return DB::transaction(function () use ($hold, $travelers, $extras, $authenticatedUserId) {
        $booking = Booking::create([
            // ...existing fields
            'travelers' => $travelers,  // Store full array
            'person_type_breakdown' => $hold->person_type_breakdown,
        ]);
        // ...
    });
}
```

### Frontend Changes

**New Component: `MultiTravelerForm.tsx`**

```typescript
interface MultiTravelerFormProps {
  personTypeBreakdown: Record<string, number>; // { adult: 2, child: 1 }
  personTypes: PersonType[]; // For labels and age ranges
  onSubmit: (travelers: TravelerInfo[]) => void;
  defaultValues?: TravelerInfo[];
}

// Renders accordion or stepper:
// ┌─ Traveler 1 (Adult) ─────────────────┐
// │ First Name: [________]               │
// │ Last Name:  [________]               │
// │ Email:      [________]               │
// │ Phone:      [________]               │
// │ Birth Date: [________]               │
// └──────────────────────────────────────┘
// ┌─ Traveler 2 (Adult) ─────────────────┐
// │ ...                                  │
// └──────────────────────────────────────┘
```

### Files to Modify

| File                                                          | Change                               |
| ------------------------------------------------------------- | ------------------------------------ |
| `apps/laravel-api/app/Http/Requests/CreateBookingRequest.php` | Array validation                     |
| `apps/laravel-api/app/Services/BookingService.php`            | Handle travelers array               |
| `apps/laravel-api/app/Models/Booking.php`                     | Rename `traveler_info` → `travelers` |
| `apps/web/src/components/booking/MultiTravelerForm.tsx`       | NEW                                  |
| `apps/web/src/components/booking/BookingWizard.tsx`           | Replace TravelerInfoForm             |
| `apps/web/src/components/booking/BookingReview.tsx`           | Show all travelers                   |

---

## Phase 4: UX Improvements

### Problem

"Check Availability" expands inline, requiring scroll. Not user-friendly.

### Solution

Modal/popup for availability selection on mobile, keep sticky sidebar on desktop.

### Industry Standard

- **Airbnb Experiences**: Sticky sidebar (desktop) + bottom sheet (mobile)
- **GetYourGuide**: Modal overlay for date selection
- **Viator**: Right sidebar drawer

### Implementation

**New Component: `packages/ui/src/components/Dialog/Dialog.tsx`**

```typescript
export interface DialogProps {
  isOpen: boolean;
  onClose: () => void;
  title?: string;
  children: React.ReactNode;
  size?: 'sm' | 'md' | 'lg' | 'full';
}

export function Dialog({ isOpen, onClose, title, children, size = 'md' }: DialogProps) {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      {/* Backdrop */}
      <div className="absolute inset-0 bg-black/50" onClick={onClose} />
      {/* Content */}
      <div className={`relative bg-white rounded-xl shadow-xl ${sizeClasses[size]}`}>
        {title && <div className="p-4 border-b font-semibold">{title}</div>}
        <div className="p-4">{children}</div>
      </div>
    </div>
  );
}
```

**New Component: `BookingPanel.tsx`**

Responsive wrapper that renders:

- **Desktop (>1024px)**: Sticky sidebar (current behavior)
- **Mobile (<1024px)**: Dialog modal

```typescript
export function BookingPanel({ listing, children }: BookingPanelProps) {
  const [isOpen, setIsOpen] = useState(false);
  const isMobile = useMediaQuery('(max-width: 1024px)');

  if (isMobile) {
    return (
      <>
        {/* Floating Book Now button */}
        <div className="fixed bottom-0 left-0 right-0 p-4 bg-white border-t lg:hidden">
          <Button onClick={() => setIsOpen(true)} className="w-full">
            Check Availability
          </Button>
        </div>
        {/* Modal */}
        <Dialog isOpen={isOpen} onClose={() => setIsOpen(false)} size="full">
          {children}
        </Dialog>
      </>
    );
  }

  return (
    <Card className="sticky top-20">
      {children}
    </Card>
  );
}
```

### Files to Modify

| File                                                 | Change                   |
| ---------------------------------------------------- | ------------------------ |
| `packages/ui/src/components/Dialog/Dialog.tsx`       | NEW                      |
| `packages/ui/src/components/Dialog/index.ts`         | NEW                      |
| `packages/ui/src/index.ts`                           | Export Dialog            |
| `apps/web/src/components/booking/BookingPanel.tsx`   | NEW (responsive wrapper) |
| `apps/web/src/app/[locale]/listings/[slug]/page.tsx` | Use BookingPanel         |
| `apps/web/src/hooks/useMediaQuery.ts`                | NEW (if not exists)      |

---

## Implementation Order

### Recommended Sequence

```
Phase 1: Dedicated Checkout Page (Foundation)
    ↓
Phase 4: UX Improvements (Better availability selection)
    ↓
Phase 2: Age-Based Pricing (Core feature)
    ↓
Phase 3: Multi-Guest Booking (Depends on Phase 2)
```

### Why This Order?

1. **Phase 1 first**: Establishes URL-based state persistence, needed for all other phases
2. **Phase 4 second**: UX improvement is independent and high-impact
3. **Phase 2 third**: Age-based pricing is the foundation for multi-guest
4. **Phase 3 last**: Requires Phase 2's person types to know how many forms to show

### Estimated Effort

| Phase     | Effort          | Dependencies |
| --------- | --------------- | ------------ |
| Phase 1   | 2-3 hours       | None         |
| Phase 4   | 2-3 hours       | None         |
| Phase 2   | 4-6 hours       | Phase 1      |
| Phase 3   | 3-4 hours       | Phase 2      |
| **Total** | **11-16 hours** |              |

---

## Vendor Extras Management (Future)

Not included in this plan but mentioned: vendors should be able to manage extras (add-ons like equipment rental, meals, etc.). This can be added as Phase 5 after the core booking enhancements are complete.

---

## Checklist

- [x] Phase 1: Dedicated checkout page (COMPLETED 2025-12-16)
  - [x] Backend: Add GET /holds/{id} endpoint
  - [x] Backend: Include listing + slot in hold response
  - [x] Frontend: Create /checkout/[holdId] page
  - [x] Frontend: Redirect from listing page after hold creation

- [x] Phase 4: UX improvements (COMPLETED 2025-12-16)
  - [x] Create Dialog component in @go-adventure/ui
  - [x] Create BookingPanel responsive wrapper
  - [x] Create useMediaQuery hook
  - [x] Update listing page to use BookingPanel

- [x] Phase 2: Age-based pricing (COMPLETED 2025-12-16)
  - [x] Migration: Add person_type_breakdown fields
  - [x] Backend: Create PriceCalculationService
  - [x] Backend: Update hold creation to accept breakdown
  - [x] Frontend: Create PersonTypeSelector component
  - [x] Frontend: Update listing page to use PersonTypeSelector
  - [x] Frontend: Update BookingReview to show breakdown

- [x] Phase 3: Multi-guest booking (COMPLETED 2025-12-17)
  - [x] Frontend: Create MultiTravelerForm component
  - [x] Frontend: Update BookingWizard to use MultiTravelerForm
  - [x] Frontend: Update BookingReview to show all travelers
  - [x] Backend: Update CreateBookingRequest for travelers array
  - [x] Backend: Update BookingService for multiple travelers
  - [x] Backend: Store travelers array in Booking model (migration + model update)
  - [x] Frontend: Update API client to send all travelers

- [x] UX Improvements (COMPLETED 2025-12-17)
  - [x] Dialog: Add bottomSheet variant for mobile (slides up from bottom)
  - [x] BookingPanel: Use bottomSheet on mobile with drag handle
  - [x] Button text: Changed from "Book Now" to "Continue" (clearer intent)
  - [x] Step indicator: Added BookingStepIndicator (Date → Time → Guests)
  - [x] Translations: Added step_date, step_time, step_guests keys (en/fr)
