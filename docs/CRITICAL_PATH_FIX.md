# CRITICAL PATH FIX - One Shot Attempt

> **Goal**: Fix the minimum required to get a working traveler booking flow
> **Time budget**: 6-8 hours
> **Success criteria**: A user can search listings → view detail → select dates → complete booking
> **Failure point**: If Step 1 or 2 fails, STOP and report - rebuild is faster

---

## Pre-Flight Check

Before starting, verify you're in the right state:

```bash
cd /path/to/go-adventure
ls apps/laravel-api apps/web packages/schemas  # All three must exist
```

---

## STEP 1: Fix Migrations (STOP IF THIS FAILS)

**Problem**: Multiple migrations have `$table->uuid()` without column names.

**Find and fix all occurrences:**

```bash
cd apps/laravel-api
grep -rn "\->uuid()" database/migrations/
```

**Fix pattern** - change:

```php
// BROKEN
$table->uuid()->primary();
$table->uuid();

// FIXED
$table->uuid('id')->primary();
$table->uuid('uuid')->unique();
```

**Files likely affected** (verify with grep):

- `*_create_users_table.php` or `*_update_users_table*.php`
- `*_create_traveler_profiles_table.php`
- `*_create_vendor_profiles_table.php`
- `*_create_locations_table.php`
- `*_create_media_table.php`
- `*_create_listings_table.php`
- `*_create_bookings_table.php`
- `*_create_booking_holds_table.php`
- `*_create_availability_*.php`

**Also check for**:

- Foreign key references to non-existent columns
- `foreignUuid()` calls that reference wrong column names

**Verify Step 1:**

```bash
php artisan migrate:fresh --seed
```

✅ **CHECKPOINT**: If migrations run without errors, continue.
❌ **STOP**: If migrations still fail, document the errors and stop. Rebuild is faster.

---

## STEP 2: Fix API Contract - Snake to Camel Case (STOP IF TOO COMPLEX)

**Problem**: Backend returns `snake_case`, frontend expects `camelCase`.

**Decision**: Convert backend to output camelCase (less files to change than frontend).

### 2.1 Create a base resource with camelCase conversion

```php
// app/Http/Resources/BaseResource.php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

abstract class BaseResource extends JsonResource
{
    /**
     * Convert array keys to camelCase recursively
     */
    protected function toCamelCase(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $camelKey = Str::camel($key);
            if (is_array($value)) {
                $result[$camelKey] = $this->toCamelCase($value);
            } else {
                $result[$camelKey] = $value;
            }
        }
        return $result;
    }
}
```

### 2.2 Update all Resources to extend BaseResource

Update each resource file in `app/Http/Resources/`:

```php
// Example: ListingResource.php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

class ListingResource extends BaseResource
{
    public function toArray($request): array
    {
        return $this->toCamelCase([
            'id' => $this->id,
            'slug' => $this->slug,
            'type' => $this->type,
            'title' => $this->getTranslation('title', app()->getLocale()),
            'description' => $this->getTranslation('description', app()->getLocale()),
            'short_description' => $this->getTranslation('short_description', app()->getLocale()),
            'highlights' => $this->getTranslation('highlights', app()->getLocale()),
            'base_price' => $this->base_price,
            'currency' => $this->currency,
            'duration_minutes' => $this->duration_minutes,
            'max_capacity' => $this->max_capacity,
            'min_capacity' => $this->min_capacity,
            'difficulty_level' => $this->difficulty_level,
            'status' => $this->status,
            'featured_image_url' => $this->featured_image_url,
            'location' => $this->whenLoaded('location', fn() => new LocationResource($this->location)),
            'vendor' => $this->whenLoaded('vendor', fn() => new VendorResource($this->vendor)),
            'media' => $this->whenLoaded('media', fn() => MediaResource::collection($this->media)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ]);
    }
}
```

### 2.3 Update all FormRequests to accept camelCase

Add input key transformation to requests:

```php
// app/Http/Requests/BaseFormRequest.php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

abstract class BaseFormRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Convert camelCase input to snake_case for Laravel
        $this->merge($this->toSnakeCase($this->all()));
    }

    protected function toSnakeCase(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $snakeKey = Str::snake($key);
            if (is_array($value)) {
                $result[$snakeKey] = $this->toSnakeCase($value);
            } else {
                $result[$snakeKey] = $value;
            }
        }
        return $result;
    }
}
```

Update all FormRequests in `app/Http/Requests/` to extend `BaseFormRequest`.

### 2.4 Key Resources to Update

Priority order (booking flow):

1. `ListingResource.php`
2. `ListingSummaryResource.php` (if exists, or create from ListingResource)
3. `AvailabilitySlotResource.php`
4. `BookingHoldResource.php`
5. `BookingResource.php`
6. `UserResource.php`
7. `LocationResource.php`
8. `MediaResource.php`

**Verify Step 2:**

```bash
php artisan serve &
curl http://localhost:8000/api/v1/listings | jq '.data[0] | keys'
# Should show camelCase keys: basePrice, featuredImageUrl, etc.
```

✅ **CHECKPOINT**: If API returns camelCase, continue.
❌ **STOP**: If this is taking more than 2 hours, stop. Rebuild is faster.

---

## STEP 3: Fix BookingHold Model Method Name

**Problem**: Controller calls `hasExpired()` but model has `isExpired()`.

```bash
grep -rn "hasExpired\|isExpired" apps/laravel-api/app/
```

**Fix**: Rename method in model OR update controller call. Be consistent.

```php
// app/Models/BookingHold.php - add alias or rename
public function hasExpired(): bool
{
    return $this->isExpired();
}

// OR if isExpired doesn't exist, add:
public function isExpired(): bool
{
    return $this->expires_at->isPast();
}

public function hasExpired(): bool
{
    return $this->isExpired();
}
```

**Verify:**

```bash
php artisan tinker
>>> $hold = new \App\Models\BookingHold();
>>> method_exists($hold, 'hasExpired')  // Should return true
>>> method_exists($hold, 'isExpired')   // Should return true
```

---

## STEP 4: Fix Frontend React use() Hook Issue

**Problem**: Client components using `use(params)` which isn't supported.

**Find occurrences:**

```bash
cd apps/web
grep -rn "use(params)" src/
grep -rn "'use client'" src/app/
```

**Fix pattern** - for pages that need params:

```tsx
// BROKEN - Client component with use()
'use client';
import { use } from 'react';

export default function Page({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = use(params); // ❌ Doesn't work in client components
  // ...
}

// FIXED - Option A: Make it a server component (remove 'use client')
// Then use params directly with await
export default async function Page({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  // ...
}

// FIXED - Option B: Keep client, extract param differently
('use client');
import { useParams } from 'next/navigation';

export default function Page() {
  const params = useParams();
  const slug = params.slug as string;
  // ...
}
```

**Files to check:**

- `src/app/[locale]/listings/page.tsx`
- `src/app/[locale]/listings/[slug]/page.tsx`
- `src/app/[locale]/auth/login/page.tsx`
- `src/app/[locale]/auth/register/page.tsx`
- `src/app/[locale]/checkout/page.tsx`
- `src/app/[locale]/dashboard/page.tsx`

**Verify:**

```bash
pnpm dev
# Navigate to http://localhost:3000/en/listings - should not crash
```

---

## STEP 5: Wire Search Functionality

**Problem**: SearchBar has no onSearch handler.

**File**: `src/app/[locale]/listings/page.tsx`

```tsx
// Add search handler
'use client';

import { useRouter, useSearchParams } from 'next/navigation';

export default function ListingsPage() {
  const router = useRouter();
  const searchParams = useSearchParams();

  const handleSearch = (query: string) => {
    const params = new URLSearchParams(searchParams.toString());
    if (query) {
      params.set('q', query);
    } else {
      params.delete('q');
    }
    router.push(`/listings?${params.toString()}`);
  };

  return (
    <div>
      <SearchBar onSearch={handleSearch} defaultValue={searchParams.get('q') || ''} />
      {/* Rest of page */}
    </div>
  );
}
```

**Verify**: Type in search bar, press enter, URL should update with `?q=searchterm`

---

## STEP 6: Wire Availability → Hold → Checkout Flow

This is the most complex step. Focus on making the minimal path work.

### 6.1 Verify availability endpoint works

```bash
# Get a listing ID first
LISTING_ID=$(curl -s http://localhost:8000/api/v1/listings | jq -r '.data[0].id')

# Check availability
curl "http://localhost:8000/api/v1/listings/${LISTING_ID}/availability?startDate=2025-01-01&endDate=2025-01-31"
```

If this fails, check:

- `AvailabilitySlot` model exists and has data
- Route is defined in `routes/api.php`
- Controller method exists

### 6.2 Verify hold creation works

```bash
curl -X POST http://localhost:8000/api/v1/holds \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "listingId": "'$LISTING_ID'",
    "availabilitySlotId": "SLOT_ID",
    "quantity": 2
  }'
```

If this fails, check:

- Route exists for POST `/holds` or `/booking-holds`
- Controller accepts camelCase (from Step 2)
- BookingHold model has required fields

### 6.3 Connect frontend components

In listing detail page, ensure:

1. `AvailabilityCalendar` receives listing ID and fetches slots
2. `TimeSlotPicker` is shown when date is selected
3. "Book Now" button creates a hold via API
4. On hold success, redirect to `/checkout?holdId={id}`

In checkout page, ensure:

1. Page reads `holdId` from URL
2. Fetches hold details from API
3. Shows booking form with hold data
4. Submit creates booking via API

**Minimal wiring** - update `src/app/[locale]/listings/[slug]/page.tsx`:

```tsx
// Ensure these are imported and used:
import { AvailabilityCalendar } from '@/components/booking/AvailabilityCalendar';
import { useCreateHold } from '@/hooks/useBooking';

// In component:
const createHold = useCreateHold();

const handleSlotSelect = async (slotId: string) => {
  const hold = await createHold.mutateAsync({
    listingId: listing.id,
    availabilitySlotId: slotId,
    quantity: guestCount,
  });
  router.push(`/checkout?holdId=${hold.id}`);
};
```

---

## STEP 7: Fix Registration Form

**Problem**: API requires fields frontend doesn't send.

**Option A**: Update frontend to send required fields:

```tsx
// src/app/[locale]/auth/register/page.tsx
// Add fields for firstName, lastName, role

<input name="firstName" required />
<input name="lastName" required />
<input type="hidden" name="role" value="traveler" />
<input name="password" type="password" required />
<input name="passwordConfirmation" type="password" required />
```

**Option B**: Make backend fields optional for travelers:

```php
// app/Http/Requests/RegisterRequest.php
public function rules(): array
{
    return [
        'email' => ['required', 'email', 'unique:users'],
        'password' => ['required', 'min:8', 'confirmed'],
        'name' => ['required', 'string'],  // Accept single name field
        'first_name' => ['sometimes', 'string'],  // Optional
        'last_name' => ['sometimes', 'string'],   // Optional
        'role' => ['sometimes', 'in:traveler,vendor'],  // Default to traveler
    ];
}
```

---

## STEP 8: Add Missing Public Assets

```bash
cd apps/web/public

# Create placeholder OG image (or copy a real one)
convert -size 1200x630 xc:#0D642E -fill white -pointsize 72 -gravity center -annotate 0 "Go Adventure" og-image.png

# Or just create empty placeholders to prevent 404s
touch og-image.png icon-192.png icon-512.png favicon.ico

# Better: copy actual assets if you have them
cp /path/to/your/logo-192.png icon-192.png
cp /path/to/your/logo-512.png icon-512.png
```

---

## STEP 9: Smoke Test the Full Flow

Run through manually:

```
1. [ ] Start both servers
       - cd apps/laravel-api && php artisan serve
       - cd apps/web && pnpm dev

2. [ ] Homepage loads at http://localhost:3000

3. [ ] Can register new account
       - Go to /en/auth/register
       - Fill form, submit
       - Should redirect to dashboard or login

4. [ ] Can login
       - Go to /en/auth/login
       - Enter credentials
       - Should redirect to dashboard

5. [ ] Listings page works
       - Go to /en/listings
       - Listings display (even if empty, no crash)
       - Search bar accepts input

6. [ ] Listing detail works
       - Click a listing
       - Detail page shows info
       - Map displays (if location exists)

7. [ ] Availability shows
       - Calendar displays dates
       - Can select a date
       - Time slots appear (if seeded)

8. [ ] Can create hold
       - Select slot, click book
       - Redirects to checkout with holdId

9. [ ] Can complete booking
       - Checkout page loads hold info
       - Can fill traveler details
       - Submit creates booking
       - Confirmation page shows

10. [ ] Booking appears in dashboard
        - Go to /en/dashboard
        - Booking is listed
```

---

## Decision Point

**If you reach Step 9 and most checkboxes pass**: 🎉 Critical path works. Continue to vendor features.

**If stuck at Step 1-2**: ❌ Stop. Rebuild is faster.

**If stuck at Steps 3-8**: Evaluate how close you are. If >50% works, continue fixing. If <50%, consider rebuild.

---

## Quick Fixes Reference

### If API returns 500 errors

```bash
cd apps/laravel-api
tail -f storage/logs/laravel.log
```

### If frontend shows hydration errors

```bash
cd apps/web
# Check browser console for specific error
# Usually means server/client mismatch - check 'use client' directives
```

### If CORS errors

```php
// config/cors.php
'allowed_origins' => ['http://localhost:3000'],
'supports_credentials' => true,
```

### If auth tokens not working

```bash
# Check Sanctum is configured
php artisan config:cache
php artisan route:list | grep sanctum
```

---

## Time Tracking

| Step                 | Estimated   | Actual | Status |
| -------------------- | ----------- | ------ | ------ |
| Step 1: Migrations   | 30 min      |        |        |
| Step 2: API Contract | 2 hrs       |        |        |
| Step 3: Model Fix    | 15 min      |        |        |
| Step 4: React Fix    | 45 min      |        |        |
| Step 5: Search       | 30 min      |        |        |
| Step 6: Booking Flow | 2 hrs       |        |        |
| Step 7: Registration | 30 min      |        |        |
| Step 8: Assets       | 15 min      |        |        |
| Step 9: Smoke Test   | 30 min      |        |        |
| **Total**            | **7-8 hrs** |        |        |

If any step takes 2x the estimate, reassess whether to continue.
