# PPP Backend Test Failures - Deep Analysis & Fix Plan

**Date**: 2025-12-26
**Status**: 39/54 tests passing (72%)
**Remaining Failures**: 15 tests

---

## Executive Summary

After fixing all infrastructure issues (database migrations, factory fields), we have **15 remaining test failures** that fall into two categories:

1. **Test Code Bugs** (13 failures): Incorrect helper method calls - easy fix
2. **Missing Implementations** (2-3 failures): Features not yet implemented

**Good News**: No actual business logic bugs found! The PPP pricing services (GeoPricingService, PriceCalculationService) are working perfectly with 30/30 tests passing.

---

## Category 1: Test Code Bugs (13 failures)

### Root Cause: Incorrect Helper Method Calls

The `createTunisiaHold()` and `createEURHold()` helper methods have this signature:

```php
protected function createTunisiaHold(?Listing $listing = null, array $overrides = []): BookingHold
```

**Correct usage:**

```php
$hold = $this->createTunisiaHold();  // ✅ No params
$hold = $this->createTunisiaHold(null, ['currency' => 'TND']);  // ✅ Null + overrides
```

**Incorrect usage (causing TypeError):**

```php
$hold = $this->createTunisiaHold(['currency' => 'TND']);  // ❌ Array in wrong position
```

### Affected Test Files & Lines

#### BookingServiceTest.php (5 failures)

| Line | Test Method                                               | Issue              |
| ---- | --------------------------------------------------------- | ------------------ |
| 87   | `test_capture_pricing_snapshot_creates_correct_structure` | Array as 1st param |
| 166  | `test_pricing_snapshot_includes_all_required_fields`      | Array as 1st param |
| 211  | `test_booking_with_eur_hold_and_eur_billing`              | Array as 1st param |
| 246  | `test_booking_detects_price_change_tunisia_to_france`     | Array as 1st param |
| 303  | `test_session_id_copied_from_hold_to_booking`             | Array as 1st param |

#### PppPricingIntegrationTest.php (8 failures)

| Line | Test Method                                          | Issue              |
| ---- | ---------------------------------------------------- | ------------------ |
| 67   | `test_verify_billing_tunisia_to_tunisia_no_change`   | Array as 1st param |
| 97   | `test_verify_billing_tunisia_to_france_price_change` | Array as 1st param |
| 130  | `test_verify_billing_france_to_tunisia_price_change` | Array as 1st param |
| 159  | `test_verify_billing_with_expired_hold_returns_410`  | Array as 1st param |
| 532  | (Need to check test name)                            | Array as 1st param |

### Fix Strategy

**Single Find-and-Replace Operation:**

```bash
# Find all occurrences:
grep -n "createTunisiaHold(\[" tests/Unit/Services/BookingServiceTest.php
grep -n "createEURHold(\[" tests/Unit/Services/BookingServiceTest.php
grep -n "createTunisiaHold(\[" tests/Feature/Api/PppPricingIntegrationTest.php
grep -n "createEURHold(\[" tests/Feature/Api/PppPricingIntegrationTest.php

# Replace pattern:
# FROM: $this->createTunisiaHold([
# TO:   $this->createTunisiaHold(null, [
```

**Estimated Impact**: This single fix will resolve 13/15 failing tests.

---

## Category 2: Missing Implementations

### 2.1 CheckoutController::verifyBilling() Endpoint

**Test Failures:**

- `test_verify_billing_tunisia_to_tunisia_no_change`
- `test_verify_billing_tunisia_to_france_price_change`
- `test_verify_billing_france_to_tunisia_price_change`
- `test_verify_billing_with_expired_hold_returns_410`

**Expected Endpoint:** `POST /api/v1/checkout/verify-billing`

**Request Format:**

```json
{
  "hold_id": "uuid",
  "billing_address": {
    "country_code": "TN",
    "city": "Tunis",
    "postal_code": "1000",
    "address_line1": "123 Main St",
    "address_line2": "Apt 5"
  }
}
```

**Expected Response:**

```json
{
  "success": true,
  "pricing": {
    "price_changed": false,
    "original_currency": "TND",
    "original_price": 300,
    "final_currency": "TND",
    "final_price": 300,
    "browse_country": "TN",
    "billing_country": "TN",
    "disclosure_required": false,
    "disclosure_message": null
  }
}
```

**Implementation Required:**

1. Create `CheckoutController.php`
2. Add `verifyBilling(Request $request)` method
3. Logic:
   - Load hold by ID
   - Check if expired → return 410
   - Get billing country from request
   - Compare with hold's pricing_country_code
   - If different:
     - Recalculate price for billing country
     - Return disclosure_required=true with message
   - If same:
     - Return disclosure_required=false

**Files to Create/Modify:**

- `app/Http/Controllers/Api/V1/CheckoutController.php` (NEW)
- `app/Http/Requests/VerifyBillingRequest.php` (NEW)
- `routes/api.php` (ADD route)

---

### 2.2 BookingService::capturePricingSnapshot()

**Test Failures:**

- `test_capture_pricing_snapshot_creates_correct_structure`
- `test_pricing_snapshot_includes_all_required_fields`

**Issue**: The `BookingService::createFromHold()` method needs to call a helper that creates a pricing snapshot.

**Required Snapshot Structure:**

```php
[
    'browse_currency' => 'TND',
    'browse_price' => 300,
    'browse_country' => 'TN',
    'browse_source' => 'ip_geo',
    'final_currency' => 'TND',
    'final_price' => 300,
    'final_country' => 'TN',
    'price_changed' => false,
    'timestamp' => '2025-12-26T10:30:00Z',
]
```

**Implementation Required:**

1. Add protected method `capturePricingSnapshot()` to BookingService
2. Call it in `createFromHold()` before saving booking
3. Extract data from:
   - Hold (browse currency, price, country)
   - Traveler billing address (final country)
   - Compare to detect price_changed
4. Store in `bookings.pricing_snapshot` JSON column

**Files to Modify:**

- `app/Services/BookingService.php` (ADD method)

---

### 2.3 Booking Model - Billing Address Fields

**Test Failures:**

- `test_billing_address_stored_correctly`
- `test_booking_with_billing_address_stores_all_fields`

**Status**: Migration already exists (`2025_12_26_100001_add_billing_and_pricing_to_bookings.php`)

**Need to Verify:**

1. Migration has been run
2. Model has fields in `$fillable`
3. Model has `$casts` for pricing_snapshot

**Files to Check:**

- `app/Models/Booking.php` (verify $fillable, $casts)
- Migration status: `php artisan migrate:status`

---

## Detailed Fix Plan

### Phase 1: Fix Test Code Bugs (Est. 5 minutes)

```bash
# Step 1: Fix BookingServiceTest.php
# Lines to update: 87, 166, 211, 246, 303

# Step 2: Fix PppPricingIntegrationTest.php
# Lines to update: 67, 97, 130, 159, 532

# Run after fix:
php artisan test tests/Unit/Services/BookingServiceTest.php
# Expected: 9/9 passing (was 4/9)
```

**Specific Changes Needed:**

**File**: `tests/Unit/Services/BookingServiceTest.php`

```php
// Line 87 - BEFORE:
$hold = $this->createTunisiaHold([
    'currency' => 'TND',
    ...
]);

// Line 87 - AFTER:
$hold = $this->createTunisiaHold(null, [
    'currency' => 'TND',
    ...
]);

// Repeat for lines 166, 211, 246, 303
```

**File**: `tests/Feature/Api/PppPricingIntegrationTest.php`

```php
// Line 67, 97, 159, 532 - Same pattern
// Line 130 - createEURHold has same signature

// BEFORE:
$hold = $this->createEURHold([
    'currency' => 'EUR',
    ...
]);

// AFTER:
$hold = $this->createEURHold(null, [
    'currency' => 'EUR',
    ...
]);
```

---

### Phase 2: Implement CheckoutController (Est. 15 minutes)

#### 2.1 Create CheckoutController

**File**: `app/Http/Controllers/Api/V1/CheckoutController.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyBillingRequest;
use App\Models\BookingHold;
use App\Services\GeoPricingService;
use App\Services\PriceCalculationService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly GeoPricingService $geoPricingService,
        private readonly PriceCalculationService $priceCalculationService
    ) {}

    /**
     * Verify billing address and detect price changes.
     *
     * @param VerifyBillingRequest $request
     * @return JsonResponse
     */
    public function verifyBilling(VerifyBillingRequest $request): JsonResponse
    {
        $hold = BookingHold::findOrFail($request->input('hold_id'));

        // Check if hold has expired
        if ($hold->expires_at < now()) {
            return response()->json([
                'error' => 'Hold has expired',
                'code' => 'HOLD_EXPIRED',
            ], 410);
        }

        $billingCountry = $request->input('billing_address.country_code');
        $browseCountry = $hold->pricing_country_code;

        // Get currencies for both countries
        $browseCurrency = $hold->currency;
        $billingCurrency = $this->geoPricingService->getCurrencyForCountry($billingCountry);

        // Detect price change
        $priceChanged = $browseCurrency !== $billingCurrency;

        // Recalculate price if changed
        $finalPrice = $hold->price_snapshot;
        if ($priceChanged) {
            // Recalculate with billing country currency
            $finalPrice = $this->priceCalculationService->calculateTotal(
                $hold->listing,
                $hold->person_type_breakdown ?? ['adult' => $hold->quantity],
                $billingCurrency
            );
        }

        $disclosureMessage = $priceChanged
            ? "Your price has been adjusted to {$billingCurrency} based on your billing country ({$billingCountry}). We adapt pricing to ensure fair access across regions."
            : null;

        return response()->json([
            'success' => true,
            'pricing' => [
                'price_changed' => $priceChanged,
                'original_currency' => $browseCurrency,
                'original_price' => $hold->price_snapshot,
                'final_currency' => $billingCurrency,
                'final_price' => $finalPrice,
                'browse_country' => $browseCountry,
                'billing_country' => $billingCountry,
                'disclosure_required' => $priceChanged,
                'disclosure_message' => $disclosureMessage,
            ],
        ]);
    }
}
```

#### 2.2 Create Request Validator

**File**: `app/Http/Requests/VerifyBillingRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyBillingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hold_id' => ['required', 'uuid', 'exists:booking_holds,id'],
            'billing_address' => ['required', 'array'],
            'billing_address.country_code' => ['required', 'string', 'size:2'],
            'billing_address.city' => ['required', 'string', 'max:255'],
            'billing_address.postal_code' => ['nullable', 'string', 'max:20'],
            'billing_address.address_line1' => ['nullable', 'string', 'max:255'],
            'billing_address.address_line2' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

#### 2.3 Add Route

**File**: `routes/api.php`

```php
// Add inside the v1 route group:
Route::post('/checkout/verify-billing', [CheckoutController::class, 'verifyBilling']);
```

---

### Phase 3: Implement BookingService::capturePricingSnapshot (Est. 10 minutes)

**File**: `app/Services/BookingService.php`

Add this protected method:

```php
/**
 * Capture pricing snapshot for transparency and audit.
 *
 * @param BookingHold $hold
 * @param array $billingAddress
 * @return array
 */
protected function capturePricingSnapshot(BookingHold $hold, array $billingAddress): array
{
    $browseCountry = $hold->pricing_country_code;
    $browseCurrency = $hold->currency;
    $browsePrice = $hold->price_snapshot;

    $billingCountry = $billingAddress['country_code'] ?? $browseCountry;

    // Determine final currency based on billing country
    $finalCurrency = $browseCurrency;
    $finalPrice = $browsePrice;

    // Check if billing country would result in different currency
    $billingCurrency = $this->geoPricingService->getCurrencyForCountry($billingCountry);

    if ($billingCurrency !== $browseCurrency) {
        $finalCurrency = $billingCurrency;
        // Recalculate price with billing country currency
        $finalPrice = $this->priceCalculationService->calculateTotal(
            $hold->listing,
            $hold->person_type_breakdown ?? ['adult' => $hold->quantity],
            $billingCurrency
        );
    }

    return [
        'browse_currency' => $browseCurrency,
        'browse_price' => $browsePrice,
        'browse_country' => $browseCountry,
        'browse_source' => $hold->pricing_source ?? 'ip_geo',
        'final_currency' => $finalCurrency,
        'final_price' => $finalPrice,
        'final_country' => $billingCountry,
        'price_changed' => $browseCurrency !== $finalCurrency,
        'timestamp' => now()->toIso8601String(),
    ];
}
```

Then update `createFromHold()` to call this method:

```php
public function createFromHold(BookingHold $hold, array $travelers): Booking
{
    // ... existing code ...

    // Extract billing address from first traveler
    $billingAddress = $travelers[0]['billing_address'] ?? [];

    // Capture pricing snapshot
    $pricingSnapshot = $this->capturePricingSnapshot($hold, $billingAddress);

    $booking = Booking::create([
        // ... existing fields ...
        'pricing_snapshot' => $pricingSnapshot,
        'billing_country_code' => $billingAddress['country_code'] ?? null,
        'billing_city' => $billingAddress['city'] ?? null,
        'billing_postal_code' => $billingAddress['postal_code'] ?? null,
        'billing_address_line1' => $billingAddress['address_line1'] ?? null,
        'billing_address_line2' => $billingAddress['address_line2'] ?? null,
    ]);

    // ... rest of method ...
}
```

---

### Phase 4: Verify Booking Model Configuration

**File**: `app/Models/Booking.php`

Ensure these fields are in `$fillable`:

```php
protected $fillable = [
    // ... existing fields ...
    'billing_country_code',
    'billing_city',
    'billing_postal_code',
    'billing_address_line1',
    'billing_address_line2',
    'pricing_snapshot',
    'session_id',  // For guest checkout
];
```

Ensure `$casts` includes:

```php
protected function casts(): array
{
    return [
        // ... existing casts ...
        'pricing_snapshot' => 'array',
        'billing_contact' => 'array',
    ];
}
```

---

## Testing Strategy

### After Phase 1 (Fix Test Code):

```bash
php artisan test tests/Unit/Services/BookingServiceTest.php
# Expected: 9/9 passing (currently 4/9)
```

### After Phase 2 (CheckoutController):

```bash
php artisan test tests/Feature/Api/PppPricingIntegrationTest.php --filter=verify_billing
# Expected: 4 tests passing
```

### After Phase 3 (Pricing Snapshot):

```bash
php artisan test tests/Unit/Services/BookingServiceTest.php --filter=pricing_snapshot
# Expected: 2 tests passing
```

### Final Verification:

```bash
php artisan test tests/Unit/Services/
php artisan test tests/Feature/Api/PppPricingIntegrationTest.php
# Expected: 54/54 passing (100%)
```

---

## Risk Assessment

**Low Risk Fixes:**

- Phase 1 (test code bugs): Zero risk, pure test code
- Phase 4 (model config): Low risk, just ensuring fields are fillable

**Medium Risk Implementations:**

- Phase 2 (CheckoutController): New endpoint, but simple logic
- Phase 3 (Pricing snapshot): Modifies BookingService but additive only

**Mitigation:**

- All changes are additive (no breaking changes)
- Comprehensive tests already written (TDD RED → GREEN)
- Each phase can be verified independently

---

## Success Criteria

✅ All 54 backend tests passing (100%)
✅ No test code bugs remaining
✅ All PPP pricing features implemented
✅ CheckoutController endpoint working
✅ Pricing snapshot captured correctly
✅ Billing address stored in database
✅ Ready for frontend integration

---

## Next Steps

1. **Immediate**: Fix test code bugs (Phase 1) - 5 minutes
2. **Short-term**: Implement missing features (Phases 2-4) - 30 minutes
3. **Verification**: Run full test suite - 5 minutes
4. **Integration**: Merge with frontend implementation (agent a463530)
5. **E2E Testing**: Run Playwright tests with full stack

---

## Notes

- **No business logic bugs found** - All core PPP pricing logic is correct!
- **GeoPricingService**: 19/19 tests passing ✅
- **PriceCalculationService**: 11/11 tests passing ✅
- **Infrastructure**: All database/factory issues resolved ✅
- **Only missing**: Controller endpoints and snapshot capture method

This is an excellent TDD outcome - we wrote tests first, they revealed exactly what's missing, and the fixes are straightforward and low-risk.
