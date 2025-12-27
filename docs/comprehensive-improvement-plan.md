# Comprehensive Flow Improvement Plan

## Go Adventure Marketplace - Currency, Extras & UX Overhaul

**Date**: December 24, 2025
**Scope**: Multi-currency pricing, Extras/Upsells integration, End-to-end UX flow
**Target**: Production-ready, industry-standard implementation

---

## Executive Summary

### Current State Analysis

**✅ What's Working:**

- Solid technical foundation with dual-currency support (TND/EUR)
- Comprehensive extras/add-ons system with multiple pricing models
- Strong booking workflow with availability management
- Filament admin panels for both Admin and Vendor roles

**❌ Critical Issues:**

1. **Currency handling is inconsistent** across panels and unclear to users
2. **Extras system exists but is hidden** - no clear way for vendors to link extras to listings
3. **PPP (Purchasing Power Parity) conversion not documented** - just raw TND → EUR
4. **UX flow is confusing** for all actors (travelers, vendors, admins)
5. **Missing industry-standard features** (dynamic pricing, multi-currency display)

### Industry Standards Research

According to [research on multi-currency e-commerce](https://geotargetly.com/blog/multi-currency) and [PPP pricing strategies](https://www.getmonetizely.com/articles/how-can-purchasing-power-parity-transform-your-global-saas-pricing-strategy):

**Best Practices for 2025:**

- **84% of travelers consider sustainable travel important**
- **Experience economy will reach $1.5 trillion** by end of 2024 ([Airbnb vs GetYourGuide](https://skift.com/2025/03/11/airbnb-experiences-new-details-point-to-direct-competition-with-viator-and-getyourguide/))
- **Companies using PPP pricing see 4.7x higher conversion rates** in emerging markets
- **20-30% commission is standard** for platforms like Viator and GetYourGuide
- **Book Now, Pay Later** is increasingly expected by travelers

---

## Part 1: Multi-Currency Strategy & Implementation

### 1.1 Problem Analysis

**Current Implementation:**

```php
// Booking model
'base_price_tnd' => 'decimal:2',
'base_price_eur' => 'decimal:2',

// Admin panel shows USD ($)
->prefix('$')
->default('USD')

// Vendor panel shows EUR (€)
($record->currency ?? 'EUR')
```

**Issues:**

1. Three currencies mentioned (TND, EUR, USD) but no clear conversion logic
2. Admin and Vendor see different currencies for same booking
3. No PPP adjustment documented or implemented
4. Frontend shows EUR but backend stores both TND and EUR
5. No exchange rate service or update mechanism

### 1.2 Industry-Standard Solution

**Recommended Architecture:**

```
┌─────────────────────────────────────────────────────────┐
│  MASTER CURRENCY: Tunisian Dinar (TND)                 │
│  All prices ENTERED in TND by vendors                  │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
    ┌──────────────────────────────────────────┐
    │  CONVERSION SERVICE                      │
    │  • Real-time FX rates (daily update)     │
    │  • PPP adjustment factor                 │
    │  • Configurable markup (if applicable)   │
    └──────────────────────────────────────────┘
                          │
                          ▼
            ┌─────────────┴──────────────┐
            │                            │
            ▼                            ▼
    ┌──────────────┐           ┌──────────────┐
    │  EUR Display │           │  USD Display │
    │  (Travelers) │           │  (Optional)  │
    └──────────────┘           └──────────────┘
```

**Implementation Steps:**

1. **Create Currency Conversion Service**

   ```php
   // app/Services/CurrencyConversionService.php
   class CurrencyConversionService
   {
       // Daily updated exchange rates
       protected array $rates = [
           'TND' => 1.0,
           'EUR' => 0.31,  // Approximate as of Dec 2024
           'USD' => 0.32,
       ];

       // PPP adjustment factors (based on Tunisia vs Europe purchasing power)
       protected array $ppFactors = [
           'TND_EUR' => 0.85,  // 15% PPP adjustment
       ];

       public function convertWithPPP(
           float $amountTND,
           string $targetCurrency
       ): float {
           $rawConversion = $amountTND * $this->rates[$targetCurrency];

           if ($targetCurrency === 'EUR') {
               return $rawConversion * $this->ppPP_factors['TND_EUR'];
           }

           return $rawConversion;
       }
   }
   ```

2. **Update Database Schema**

   ```php
   // Migration: standardize currency storage
   Schema::table('listings', function (Blueprint $table) {
       // Master price
       $table->decimal('base_price_tnd', 10, 2);

       // Calculated prices (updated via cron)
       $table->decimal('display_price_eur', 10, 2)->virtualAs(
           'ROUND(base_price_tnd * (SELECT rate FROM exchange_rates WHERE currency = "EUR"), 2)'
       );

       // Pricing metadata
       $table->timestamp('exchange_rate_updated_at')->nullable();
       $table->string('primary_currency')->default('TND');
   });

   // New table: exchange_rates
   Schema::create('exchange_rates', function (Blueprint $table) {
       $table->string('currency')->primary();
       $table->decimal('rate', 10, 6);
       $table->decimal('ppp_adjustment', 5, 4)->default(1.0);
       $table->timestamps();
   });
   ```

3. **Automated Rate Updates**

   ```php
   // app/Console/Commands/UpdateExchangeRates.php
   // Schedule: Daily at 00:00 UTC

   use ExchangeRatesAPI;  // Use service like exchangeratesapi.io

   public function handle()
   {
       $rates = ExchangeRatesAPI::latest('TND', ['EUR', 'USD']);

       foreach ($rates as $currency => $rate) {
           ExchangeRate::updateOrCreate(
               ['currency' => $currency],
               [
                   'rate' => $rate,
                   'ppp_adjustment' => $this->getPPPFactor($currency),
               ]
           );
       }
   }
   ```

### 1.3 User-Facing Currency Display

**Best Practice: Show both currencies transparently**

```html
<!-- Listing card -->
<div class="pricing">
  <span class="primary-price">€45.00</span>
  <span class="secondary-price">~145 TND</span>
  <span class="tooltip">Price adjusted for international purchasing power</span>
</div>

<!-- Booking summary -->
<table>
  <tr>
    <td>Base Price (Tunisia)</td>
    <td>150.00 TND</td>
  </tr>
  <tr>
    <td>International Price (EUR)</td>
    <td>€47.25</td>
  </tr>
  <tr class="footnote">
    <td colspan="2">
      * EUR price includes purchasing power parity adjustment * Exchange rate updated: Dec 24, 2025
    </td>
  </tr>
</table>
```

**Frontend Implementation:**

```typescript
// apps/web/src/lib/currency.ts
export interface Price {
  tnd: number;
  eur: number;
  displayCurrency: 'TND' | 'EUR';
  exchangeRate: number;
  ppAdjustment: number;
  lastUpdated: string;
}

export function formatPrice(price: Price, locale: string): string {
  const display = locale === 'fr' || locale === 'en' ? price.eur : price.tnd;
  const currency = locale === 'fr' || locale === 'en' ? 'EUR' : 'TND';

  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
  }).format(display);
}
```

### 1.4 Admin Panel Currency Configuration

**Create Platform Settings Page:**

```php
// app/Filament/Admin/Pages/CurrencySettingsPage.php

Forms\Components\Section::make('Currency Configuration')
    ->schema([
        Forms\Components\Select::make('primary_currency')
            ->label('Primary Currency (Vendor Input)')
            ->options(['TND' => 'Tunisian Dinar', 'EUR' => 'Euro', 'USD' => 'US Dollar'])
            ->default('TND')
            ->required(),

        Forms\Components\Select::make('display_currency')
            ->label('Display Currency (Traveler)')
            ->options(['EUR' => 'Euro', 'USD' => 'US Dollar', 'TND' => 'Tunisian Dinar'])
            ->default('EUR')
            ->multiple(),

        Forms\Components\TextInput::make('ppp_adjustment_eur')
            ->label('PPP Adjustment Factor (TND → EUR)')
            ->numeric()
            ->step(0.01)
            ->helperText('Recommended: 0.85 (15% adjustment based on purchasing power parity)')
            ->default(0.85),

        Forms\Components\Toggle::make('auto_update_rates')
            ->label('Automatically Update Exchange Rates')
            ->helperText('Fetches daily rates from exchangeratesapi.io')
            ->default(true),

        Forms\Components\DateTimePicker::make('last_rate_update')
            ->label('Last Exchange Rate Update')
            ->disabled(),
    ]);
```

---

## Part 2: Extras/Upsells Integration & UX

### 2.1 Current Implementation Analysis

**What Exists:**

- ✅ `Extra` model with comprehensive features
- ✅ Multiple pricing types (per_person, per_booking, per_unit, per_person_type)
- ✅ Inventory tracking
- ✅ Vendor ExtraResource (Filament)
- ✅ Relationship to listings via `listing_extras` pivot

**What's Missing:**

- ❌ **No clear UI to attach extras to specific listings**
- ❌ **Extras don't appear in booking flow**
- ❌ **No frontend display of extras on listing detail pages**
- ❌ **Vendors don't know how to link their extras to tours/events**

### 2.2 Industry Standard: How GetYourGuide/Viator Handle Add-ons

**Typical Flow:**

1. Vendor creates activity/tour
2. Vendor creates add-ons (equipment, meals, insurance, upgrades)
3. **Vendor LINKS add-ons to specific activities**
4. Traveler sees add-ons on listing page
5. Traveler selects add-ons during booking
6. Add-ons appear in booking summary with pricing

**Example from GetYourGuide:**

```
┌─────────────────────────────────────────┐
│  Hiking Tour in Atlas Mountains         │
│  €45 per person                         │
│                                         │
│  Add-ons Available:                    │
│  □ Lunch Pack            +€12          │
│  □ Hiking Poles (pair)   +€5           │
│  □ Travel Insurance      +€8           │
│  □ Photo Package         +€15          │
└─────────────────────────────────────────┘
```

### 2.3 Solution: Listing-Extra Management

**Create Filament Relation Manager:**

```php
// app/Filament/Vendor/Resources/ListingResource/RelationManagers/ExtrasRelationManager.php

use Filament\Resources\RelationManagers\RelationManager;

class ExtrasRelationManager extends RelationManager
{
    protected static string $relationship = 'extras';

    protected static ?string $title = 'Add-ons & Extras';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('extra_id')
                    ->label('Select Add-on')
                    ->options(
                        Extra::where('vendor_id', auth()->id())
                            ->where('is_active', true)
                            ->pluck('name->en', 'id')
                    )
                    ->searchable()
                    ->required()
                    ->reactive(),

                Forms\Components\Section::make('Pricing Overrides')
                    ->description('Leave blank to use default pricing from the add-on')
                    ->schema([
                        Forms\Components\TextInput::make('override_price_tnd')
                            ->label('Override Price (TND)')
                            ->numeric()
                            ->placeholder('Use default'),

                        Forms\Components\TextInput::make('override_price_eur')
                            ->label('Override Price (EUR)')
                            ->numeric()
                            ->placeholder('Auto-calculated'),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Display Settings')
                    ->schema([
                        Forms\Components\TextInput::make('display_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Feature This Add-on')
                            ->helperText('Featured add-ons appear at the top'),

                        Forms\Components\Toggle::make('override_is_required')
                            ->label('Make Required for This Listing')
                            ->default(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('name', app()->getLocale()))
                    ->description(fn ($record) => $record->getTranslation('short_description', app()->getLocale())),

                Tables\Columns\TextColumn::make('pricing_display')
                    ->label('Price')
                    ->formatStateUsing(function ($record) {
                        $pivot = $record->pivot;
                        $tnd = $pivot->override_price_tnd ?? $record->base_price_tnd;
                        $eur = $pivot->override_price_eur ?? $record->base_price_eur;
                        return "{$tnd} TND / €{$eur}";
                    }),

                Tables\Columns\IconColumn::make('pivot.is_required')
                    ->label('Required')
                    ->boolean(),

                Tables\Columns\IconColumn::make('pivot.is_featured')
                    ->label('Featured')
                    ->boolean(),

                Tables\Columns\TextColumn::make('pivot.display_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('pivot.is_active')
                    ->label('Active'),
            ])
            ->defaultSort('pivot.display_order')
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        // Include the full form from above
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ]);
    }
}
```

**Add to ListingResource:**

```php
public static function getRelations(): array
{
    return [
        ExtrasRelationManager::class,
        // ... other relation managers
    ];
}
```

### 2.4 Frontend: Display Extras in Booking Flow

**Update Listing Detail Page:**

```typescript
// apps/web/src/app/[locale]/listings/[slug]/page.tsx

// Fetch listing with extras
const listing = await api.get(`/listings/${params.slug}?include=extras`);

// Add Extras Section
<section className="extras-section">
  <h2>Enhance Your Experience</h2>
  <p>Add these optional extras to your booking</p>

  <div className="extras-grid">
    {listing.extras.map(extra => (
      <ExtraCard
        key={extra.id}
        extra={extra}
        pricingType={extra.pricing_type}
        price={{
          tnd: extra.pivot.override_price_tnd || extra.base_price_tnd,
          eur: extra.pivot.override_price_eur || extra.base_price_eur,
        }}
        isRequired={extra.pivot.is_required}
        isFeatured={extra.pivot.is_featured}
      />
    ))}
  </div>
</section>
```

**Booking Wizard - Add Extras Step:**

```typescript
// apps/web/src/components/booking/BookingWizard.tsx

const steps = [
  { id: 'date', label: 'Date' },
  { id: 'time', label: 'Time' },
  { id: 'guests', label: 'Guests' },
  { id: 'extras', label: 'Add-ons' },  // NEW STEP
  { id: 'details', label: 'Details' },
  { id: 'payment', label: 'Payment' },
];

// Extras step component
<ExtrasSelectionStep
  listingExtras={listing.extras}
  guestCount={bookingData.guests}
  onExtrasSelected={(selectedExtras) => {
    updateBooking({ extras: selectedExtras });
  }}
/>
```

**Extras Selection Component:**

```tsx
// apps/web/src/components/booking/ExtrasSelection.tsx

interface ExtrasSelectionProps {
  listingExtras: ListingExtra[];
  guestCount: number;
  onExtrasSelected: (extras: SelectedExtra[]) => void;
}

export function ExtrasSelection({
  listingExtras,
  guestCount,
  onExtrasSelected,
}: ExtrasSelectionProps) {
  const [selectedExtras, setSelectedExtras] = useState<SelectedExtra[]>([]);

  const calculateExtraPrice = (extra: ListingExtra, quantity: number) => {
    switch (extra.pricing_type) {
      case 'per_person':
        return extra.price_eur * guestCount;
      case 'per_booking':
        return extra.price_eur;
      case 'per_unit':
        return extra.price_eur * quantity;
      default:
        return 0;
    }
  };

  return (
    <div className="extras-selection">
      <h3>Customize Your Experience</h3>

      {/* Featured Extras */}
      {listingExtras.filter((e) => e.pivot.is_featured).length > 0 && (
        <div className="featured-extras">
          <h4>Recommended Add-ons</h4>
          {listingExtras
            .filter((e) => e.pivot.is_featured)
            .map((extra) => (
              <ExtraItem
                key={extra.id}
                extra={extra}
                guestCount={guestCount}
                isRequired={extra.pivot.is_required}
                onChange={(selected) => handleExtraChange(extra.id, selected)}
              />
            ))}
        </div>
      )}

      {/* Regular Extras by Category */}
      {Object.entries(groupByCategory(listingExtras)).map(([category, extras]) => (
        <div key={category} className="extra-category">
          <h4>{category}</h4>
          {extras.map((extra) => (
            <ExtraItem
              key={extra.id}
              extra={extra}
              guestCount={guestCount}
              isRequired={extra.pivot.is_required}
              onChange={(selected) => handleExtraChange(extra.id, selected)}
            />
          ))}
        </div>
      ))}

      {/* Summary */}
      <div className="extras-summary">
        <h4>Selected Add-ons</h4>
        {selectedExtras.map((extra) => (
          <div key={extra.id} className="summary-item">
            <span>{extra.name}</span>
            <span>+€{calculateExtraPrice(extra, extra.quantity).toFixed(2)}</span>
          </div>
        ))}
      </div>
    </div>
  );
}
```

### 2.5 Backend: Extras in Booking API

**Update BookingController:**

```php
// app/Http/Controllers/Api/V1/BookingController.php

public function store(Request $request)
{
    $validated = $request->validate([
        'listing_id' => 'required|exists:listings,id',
        'availability_slot_id' => 'required|exists:availability_slots,id',
        'quantity' => 'required|integer|min:1',
        'extras' => 'array',
        'extras.*.extra_id' => 'required|exists:extras,id',
        'extras.*.quantity' => 'required|integer|min:1',
    ]);

    DB::transaction(function () use ($validated) {
        // Create booking
        $booking = Booking::create([...]);

        // Attach extras
        foreach ($validated['extras'] ?? [] as $extraData) {
            $extra = Extra::find($extraData['extra_id']);

            // Reserve inventory if tracked
            if ($extra->track_inventory) {
                $extra->reserveInventory($extraData['quantity'], $booking);
            }

            // Calculate price based on pricing type
            $priceCalc = $extra->calculateTotal(
                $extraData['quantity'],
                $validated['person_type_breakdown'],
                $booking->currency
            );

            // Attach to booking
            $booking->extras()->attach($extra->id, [
                'quantity' => $extraData['quantity'],
                'unit_price' => $priceCalc['unit_price'],
                'subtotal' => $priceCalc['subtotal'],
                'pricing_type' => $extra->pricing_type,
                'calculation_details' => json_encode($priceCalc),
            ]);
        }

        // Recalculate booking total
        $booking->recalculateTotal();
    });
}
```

---

## Part 3: Complete User Flow Diagrams

### 3.1 Vendor Flow: Creating Listing with Extras

```
┌──────────────────────────────────────────────────────────────┐
│  VENDOR DASHBOARD                                            │
└──────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  STEP 1: Create/Manage Add-ons                              │
│  Navigation: My Listings > Add-ons & Extras > Create        │
│                                                              │
│  Fields:                                                     │
│  • Name (EN/FR)                                             │
│  • Description                                              │
│  • Category (Equipment, Food, Transport, etc.)              │
│  • Pricing Type (per person/booking/unit)                  │
│  • Price (TND) → Auto-calculates EUR                       │
│  • Inventory tracking (optional)                            │
└──────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  STEP 2: Create/Edit Tour/Event                             │
│  Navigation: My Listings > Listings > Create/Edit           │
│                                                              │
│  Fields:                                                     │
│  • Basic Info (Title, Description, Location)                │
│  • Pricing (Enter in TND, EUR auto-calculated)             │
│  • Availability Rules                                       │
│  • Photos & Media                                           │
└──────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  STEP 3: Link Add-ons to Listing                            │
│  Location: Edit Listing > "Add-ons & Extras" Tab            │
│                                                              │
│  Actions:                                                    │
│  • Click "Attach Add-on" button                            │
│  • Select from YOUR add-ons                                │
│  • Configure:                                               │
│    - Override price (optional)                             │
│    - Make required (yes/no)                                │
│    - Featured (yes/no)                                     │
│    - Display order                                         │
│  • Save                                                     │
└──────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  RESULT: Listing now shows add-ons on frontend              │
│  Travelers can select during booking                        │
└──────────────────────────────────────────────────────────────┘
```

### 3.2 Traveler Flow: Booking with Extras

```
┌──────────────────────────────────────────────────────────────┐
│  TRAVELER FRONTEND                                           │
└──────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  Browse Listings                                             │
│  • All prices shown in EUR (with TND reference)             │
│  • Badge: "Add-ons Available" if extras exist              │
└──────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  View Listing Detail                                         │
│  • Main price: €45.00 (~145 TND)                           │
│  • "Enhance Your Experience" section shows extras:         │
│    □ Lunch Pack +€12                                       │
│    □ Equipment Rental +€8                                  │
│    □ Photo Package +€15                                    │
└──────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  Click "Check Availability"                                  │
└──────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  BOOKING WIZARD                                              │
│                                                              │
│  Step 1: Select Date          [✓]                          │
│  Step 2: Select Time          [✓]                          │
│  Step 3: Select Guests        [✓]                          │
│  Step 4: Add-ons & Extras     [▶] ← NEW!                   │
│  Step 5: Traveler Details     [ ]                          │
│  Step 6: Payment              [ ]                          │
└──────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  STEP 4: Add-ons Selection                                   │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ Recommended Add-ons (Featured)                       │  │
│  │ ☑ Lunch Pack          +€12   [Qty: 2  ] [Per Person]│  │
│  │ □ Equipment Rental    +€8    [Qty: 1  ] [Per Person]│  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ Transportation                                       │  │
│  │ □ Hotel Pickup        +€5    [Qty: 1  ] [Per Booking]│  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ Add-ons Summary:                                     │  │
│  │ Lunch Pack (2x)                          +€24.00    │  │
│  │                                                      │  │
│  │ TOTAL ADD-ONS:                           +€24.00    │  │
│  └──────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  FINAL SUMMARY (Step 6: Payment)                            │
│                                                              │
│  Base Price (2 guests × €45)        €90.00                 │
│  Add-ons:                                                   │
│    • Lunch Pack (2×)                +€24.00                 │
│  ─────────────────────────────────────────                  │
│  TOTAL                              €114.00                 │
│  (Approx. 365 TND)                                          │
└──────────────────────────────────────────────────────────────┘
```

### 3.3 Admin Flow: Platform Management

```
┌──────────────────────────────────────────────────────────────┐
│  ADMIN DASHBOARD                                             │
└──────────────────────────────────────────────────────────────┘
    │
    ├─► Currency Settings
    │   • Set PPP adjustment factor
    │   • Configure auto-update exchange rates
    │   • View current rates (TND → EUR, USD)
    │   • Historical rate changes
    │
    ├─► Bookings Management
    │   • View all bookings (standardized currency display)
    │   • See extras purchased with each booking
    │   • Revenue reports by currency
    │
    ├─► Listings Approval
    │   • Review vendor listings
    │   • Check pricing (TND with EUR preview)
    │   • Approve/reject/request changes
    │
    └─► Vendor Management
        • View vendor earnings (both TND and EUR)
        • Payout management
        • Performance metrics
```

---

## Part 4: Implementation Roadmap

### Phase 1: Currency Standardization (Week 1) - CRITICAL

**Priority**: P0

**Tasks:**

1. Create `CurrencyConversionService` with PPP support
2. Add `exchange_rates` table and migration
3. Create `UpdateExchangeRates` console command
4. Update all Filament resources to show consistent currency
5. Add Platform Settings page for currency configuration
6. Update booking calculations to use conversion service

**Deliverables:**

- [ ] Service class with tests
- [ ] Database migration
- [ ] Console command (scheduled daily)
- [ ] Admin settings page
- [ ] Updated all Resources (Admin + Vendor)
- [ ] Documentation: "Currency Management Guide"

**Estimated Time**: 16-20 hours

---

### Phase 2: Extras UX Enhancement (Week 2) - HIGH

**Priority**: P1

**Tasks:**

1. Create `ExtrasRelationManager` for Listing Resource
2. Update `ListingResource` to include extras tab
3. Add frontend extras display on listing detail page
4. Create `ExtrasSelectionStep` component for booking wizard
5. Update booking API to handle extras
6. Test inventory reservation/release for extras
7. Update booking confirmation email to show extras

**Deliverables:**

- [ ] Relation Manager in Vendor panel
- [ ] Frontend components
- [ ] API updates
- [ ] Email template updates
- [ ] Vendor documentation: "How to Add Extras to Your Listings"
- [ ] Traveler-facing help text

**Estimated Time**: 20-24 hours

---

### Phase 3: Frontend Currency Display (Week 3) - HIGH

**Priority**: P1

**Tasks:**

1. Create `currency.ts` utility with formatting functions
2. Update all listing cards to show dual currency
3. Add currency selector to user preferences (future)
4. Update booking summary to show clear currency breakdown
5. Add tooltips explaining PPP adjustment
6. Update all price displays across frontend

**Deliverables:**

- [ ] Currency utility module
- [ ] Updated all React components
- [ ] Internationalization updates
- [ ] User documentation

**Estimated Time**: 12-16 hours

---

### Phase 4: Testing & Documentation (Week 4) - MEDIUM

**Priority**: P2

**Tasks:**

1. End-to-end testing of booking flow with extras
2. Currency conversion accuracy testing
3. Create vendor onboarding guide
4. Create admin operational manual
5. Update API documentation
6. Performance testing (ensure extras don't slow down queries)

**Deliverables:**

- [ ] Test suite (Pest/PHPUnit)
- [ ] Vendor guide PDF
- [ ] Admin manual
- [ ] API docs (OpenAPI/Swagger)
- [ ] Performance report

**Estimated Time**: 16-20 hours

---

### Phase 5: Advanced Features (Future) - LOW

**Priority**: P3

**Tasks:**

1. Dynamic pricing based on demand
2. Multi-currency support (add USD, GBP, etc.)
3. Automatic PPP recalculation based on macroeconomic data
4. Bundle/package pricing (multiple listings + extras)
5. Seasonal pricing for extras
6. Vendor analytics dashboard (revenue by extra)

**Estimated Time**: 40+ hours

---

## Part 5: Key Design Decisions

### 5.1 Why TND as Master Currency?

**Rationale:**

1. ✅ Vendors are Tunisian - they think in TND
2. ✅ Reduces conversion errors
3. ✅ Simplifies accounting and tax compliance
4. ✅ Clear source of truth

**Industry Example:**

- Airbnb: Host sets price in local currency, converts for guests
- Booking.com: Properties price in local currency
- GetYourGuide: Operators set prices in their currency

### 5.2 Why PPP Adjustment?

**Rationale:**

1. ✅ Makes prices fair for international travelers
2. ✅ Accounts for purchasing power differences
3. ✅ Increases conversion rates (proven 4.7x improvement)
4. ✅ Competitive advantage vs. platforms that don't adjust

**Example:**

- Netflix: Different prices per country based on PPP
- Spotify: $3.50 in India vs. $9.99 in US
- Steam: Regional pricing for games

**Tunisia-specific:**

- Average salary: ~1,500 TND/month
- European average: ~2,500 EUR/month
- PPP factor: ~0.85 (15% adjustment reasonable)

### 5.3 Why Show Both Currencies?

**Rationale:**

1. ✅ Transparency builds trust
2. ✅ Helps travelers understand value
3. ✅ Reduces support inquiries
4. ✅ Educational (travelers learn about PPP)

**Best Practice:**
Show primary currency large, secondary small with "~" prefix

---

## Part 6: Success Metrics

### KPIs to Track

**Currency Conversion:**

- [ ] % of bookings completed (before vs. after PPP)
- [ ] Average booking value in EUR
- [ ] Currency confusion support tickets (target: -80%)

**Extras Adoption:**

- [ ] % of listings with extras attached
- [ ] % of bookings that include extras
- [ ] Average extras revenue per booking
- [ ] Most popular extra categories

**Vendor Satisfaction:**

- [ ] Time to create listing + extras (target: <10 min)
- [ ] % of vendors using extras feature (target: >60%)
- [ ] Support tickets about extras (target: <5%)

**Traveler Satisfaction:**

- [ ] Cart abandonment rate (target: <30%)
- [ ] Booking completion time (target: <5 min)
- [ ] Reviews mentioning pricing clarity

---

## Part 7: Risk Mitigation

### Risk 1: Exchange Rate Volatility

**Mitigation:**

- Daily rate updates (not real-time)
- Price lock at booking time
- Vendor can set rate update frequency preference

### Risk 2: Vendor Confusion About Dual Currency

**Mitigation:**

- Clear onboarding tutorial
- Help text: "Enter price in TND, we'll handle conversion"
- Preview of EUR price in real-time
- Vendor documentation/videos

### Risk 3: Performance Impact (Extras Queries)

**Mitigation:**

- Eager loading: `Listing::with('extras')`
- Caching: Cache extras for 1 hour
- Database indexing on pivot table

### Risk 4: Inventory Management Complexity

**Mitigation:**

- Optional feature (can disable tracking)
- Clear low-stock warnings
- Automatic release on booking cancellation
- Inventory logs for auditing

---

## Part 8: Technical Specifications

### 8.1 Database Schema Updates

```sql
-- Add to listings table
ALTER TABLE listings
ADD COLUMN exchange_rate_version INT DEFAULT 1,
ADD COLUMN price_locked_at TIMESTAMP NULL,
ADD INDEX idx_exchange_rate_version (exchange_rate_version);

-- New table: exchange_rates
CREATE TABLE exchange_rates (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    version INT NOT NULL,
    currency VARCHAR(3) NOT NULL,
    rate DECIMAL(10, 6) NOT NULL,
    ppp_adjustment DECIMAL(5, 4) DEFAULT 1.0000,
    source VARCHAR(50) DEFAULT 'manual',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY uk_version_currency (version, currency),
    INDEX idx_currency (currency),
    INDEX idx_version (version)
);

-- Booking table adjustments
ALTER TABLE bookings
ADD COLUMN exchange_rate_version INT,
ADD COLUMN exchange_rate_snapshot JSON,
ADD COLUMN extras_total_tnd DECIMAL(10, 2) DEFAULT 0,
ADD COLUMN extras_total_eur DECIMAL(10, 2) DEFAULT 0;

-- Booking extras pivot enhancements
ALTER TABLE booking_extras
ADD COLUMN pricing_type VARCHAR(20),
ADD COLUMN calculation_details JSON,
ADD COLUMN inventory_reserved_at TIMESTAMP NULL;
```

### 8.2 API Endpoint Updates

**New Endpoints:**

```
GET  /api/v1/exchange-rates/current
GET  /api/v1/listings/{slug}/extras
POST /api/v1/bookings (updated to accept extras array)
GET  /api/v1/vendors/extras
POST /api/v1/vendors/extras
PUT  /api/v1/vendors/extras/{id}
POST /api/v1/vendors/listings/{id}/attach-extra
```

**Updated Responses:**

```json
{
  "listing": {
    "id": "uuid",
    "title": "Atlas Mountains Trek",
    "pricing": {
      "base_price_tnd": 150.0,
      "base_price_eur": 47.25,
      "currency_display": "EUR",
      "exchange_rate": 0.315,
      "ppp_adjustment": 0.85,
      "rate_updated_at": "2025-12-24T00:00:00Z"
    },
    "extras": [
      {
        "id": "uuid",
        "name": "Lunch Pack",
        "price_tnd": 40.0,
        "price_eur": 12.6,
        "pricing_type": "per_person",
        "category": "food_beverage",
        "is_required": false,
        "is_featured": true,
        "inventory_available": 50
      }
    ]
  }
}
```

---

## Conclusion

This comprehensive plan provides:

1. **Clear currency strategy** aligned with industry standards
2. **Fully functional extras/upsells** with vendor-friendly UX
3. **End-to-end flow documentation** for all actors
4. **Phased implementation** with realistic time estimates
5. **Risk mitigation** and success metrics

**Total Estimated Time**: 64-80 hours (2-3 developer weeks)

**Priority Order**:

1. Phase 1: Currency (Week 1) - Fixes critical inconsistencies
2. Phase 2: Extras UX (Week 2) - Unlocks revenue potential
3. Phase 3: Frontend (Week 3) - Improves traveler experience
4. Phase 4: Testing (Week 4) - Ensures quality

**Expected Outcomes**:

- ✅ Clear, consistent pricing across all panels
- ✅ Vendor can easily create and link extras
- ✅ Travelers see and select extras during booking
- ✅ Revenue increase from extras adoption (est. 15-25%)
- ✅ Reduced support tickets about pricing
- ✅ Industry-standard user experience

---

**Sources:**

- [Multi-Currency E-Commerce Best Practices](https://geotargetly.com/blog/multi-currency)
- [PPP Pricing Strategy](https://www.getmonetizely.com/articles/how-can-purchasing-power-parity-transform-your-global-saas-pricing-strategy)
- [Tourism Marketplace Trends](https://skift.com/2025/03/11/airbnb-experiences-new-details-point-to-direct-competition-with-viator-and-getyourguide/)
- [Building Travel Marketplace](https://www.shipturtle.com/blog/build-travel-experiences-marketplace)
- [Viator vs GetYourGuide Analysis](https://pro.regiondo.com/blog/viator-vs-getyourguide-which-ota-can-get-you-more-bookings/)

**Document Created**: December 24, 2025
**Last Updated**: December 24, 2025
**Version**: 1.0
