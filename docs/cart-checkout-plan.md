# Shopping Cart & Multi-Booking Checkout Plan

> **Status**: Complete (Phase 5)
> **Created**: 2025-12-17
> **Last Updated**: 2025-12-17
> **Scope**: Enable users to book multiple experiences and pay for all at once

---

## Implementation Progress

### Phase 1: Backend Foundation ✅ COMPLETE

- [x] Migration: Create carts table
- [x] Migration: Create cart_items table
- [x] Migration: Create cart_payments table
- [x] Migration: Add cart_id to booking_holds
- [x] Migration: Add require_traveler_names to listings
- [x] Model: Cart with relationships
- [x] Model: CartItem with relationships
- [x] Model: CartPayment
- [x] Service: CartService (CRUD)
- [x] Controller: CartController
- [x] Routes: Cart API routes
- [x] Resources: CartResource, CartItemResource

### Phase 2: Hold Synchronization ✅ COMPLETE

- [x] Service: Implement hold extension on add (CartService.addItem)
- [x] Command: CleanupExpiredHoldsCommand (scheduled every minute)
- [x] Handle expired holds in cart context
- [x] Cart.extendExpiration() method

### Phase 3: Cart Checkout ✅ COMPLETE

- [x] Service: CartCheckoutService
- [x] Controller: CartCheckoutController
- [x] Routes: Cart checkout API routes
- [x] Atomic transaction + rollback
- [x] Multiple booking creation
- [x] Cart payment linking

### Phase 4: Frontend Cart UI ✅ COMPLETE

- [x] Context: CartProvider
- [x] Hooks: useCart, useAddToCart, useRemoveFromCart, etc.
- [x] Component: CartIcon (header)
- [x] Component: CartPage
- [x] Component: CartItemCard
- [x] Component: CartSummary
- [x] API client: cartApi methods
- [x] Translations: en.json, fr.json cart section
- [x] Modify: ListingPage + "Add to Cart" button in booking flow

### Phase 5: Cart Checkout Wizard ✅ COMPLETE

- [x] Component: CartCheckoutWizard (simplified - primary contact only)
- [x] Component: PrimaryContactForm (collects only primary contact info)
- [x] Component: CartPaymentStep (with order summary and payment selection)
- [x] Component: CartConfirmation (multi-booking confirmation)
- [x] Page: /cart/checkout route
- [x] Translations: en.json, fr.json cart.checkout section

### Phase 6: Polish (Optional)

- [ ] Error handling
- [ ] Mobile optimization
- [ ] Additional translations

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Current State Analysis](#current-state-analysis)
3. [Requirements](#requirements)
4. [Architecture Design](#architecture-design)
5. [Database Schema](#database-schema)
6. [API Design](#api-design)
7. [Frontend Design](#frontend-design)
8. [Implementation Phases](#implementation-phases)
9. [Technical Considerations](#technical-considerations)
10. [Checklist](#checklist)

---

## Executive Summary

### What We Have Now

The current booking system is **single-transaction**: one hold → one booking → one payment. Each booking is independent with a 15-minute hold reservation.

### What We Need

A **shopping cart** that allows users to:

1. Add multiple experiences to a cart
2. Each experience can have different dates, times, and traveler counts
3. Checkout once with a single payment for all items

### Key Insight

The current system **does NOT support this**. We need to build:

- New `Cart` and `CartItem` database tables
- New API endpoints for cart management
- New frontend components for cart UI
- Modified payment flow for multi-booking checkout

---

## Current State Analysis

### Current Single-Booking Flow

```
User selects listing → Creates Hold (15 min) → Enters traveler info →
Creates Booking → Processes Payment → Booking Confirmed
```

### Current Database Relationships

```
BookingHold (1) ───converts to──→ (1) Booking
Booking (1) ────────────────────→ (1) AvailabilitySlot
Booking (1) ←────────────────────→ (N) PaymentIntent (retries)
```

### What's Blocking Cart Support

| Component     | Current State                      | Issue                           |
| ------------- | ---------------------------------- | ------------------------------- |
| Booking model | `availability_slot_id` is singular | Can't span multiple slots       |
| PaymentIntent | `booking_id` FK to single booking  | Can't pay for multiple bookings |
| BookingHold   | Independent 15-min expiration      | No cart-level expiration sync   |
| Frontend      | BookingWizard handles one booking  | No cart state management        |

---

## Requirements

### Functional Requirements

1. **Add to Cart**
   - User can add a booking hold to cart from listing page
   - Cart persists across page navigation
   - Cart works for both authenticated users and guests

2. **Cart Management**
   - View all items in cart
   - Remove items from cart
   - Modify quantity per item
   - See total price across all items

3. **Simplified Checkout** (KEY CHANGE)
   - **Only require primary contact** (person paying): name, email, phone
   - **Guest names are OPTIONAL by default** - configurable per listing
   - What matters: person type distribution (adults, children, infants)
   - Single payment for all items
   - All-or-nothing: either all bookings succeed or none

4. **Hold Management**
   - Holds extend when new items added
   - All items in cart share same expiration deadline
   - Clear cart if any hold expires

5. **Vendor Configuration**
   - Vendors can enable "require guest names" per listing
   - Default: OFF (only primary contact required)
   - When enabled: collect names for each guest

### Non-Functional Requirements

- Backward compatible: existing single-booking flow must continue working
- Guest checkout must work (via session_id)
- Performance: cart operations should be fast (<200ms)

---

## Architecture Design

### High-Level Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                         USER JOURNEY                             │
└─────────────────────────────────────────────────────────────────┘

1. BROWSE & ADD
   ┌──────────┐     ┌──────────┐     ┌──────────┐
   │ Listing  │────→│ Create   │────→│ Add to   │
   │ Page     │     │ Hold     │     │ Cart     │
   └──────────┘     └──────────┘     └──────────┘
                          │
                          ▼
2. CART MANAGEMENT    ┌──────────┐
                      │ Cart     │◄──── Extend holds when items added
                      │ Page     │      Sync expiration across items
                      └──────────┘
                          │
                          ▼
3. CHECKOUT           ┌──────────┐
                      │ Checkout │      For each item:
                      │ Wizard   │      - Enter travelers
                      └──────────┘      - Select extras
                          │
                          ▼
4. PAYMENT            ┌──────────┐
                      │ Payment  │      Single payment for cart total
                      │ Process  │      Creates all bookings atomically
                      └──────────┘
                          │
                          ▼
5. CONFIRMATION       ┌──────────┐
                      │ Multiple │      Show all confirmed bookings
                      │ Bookings │      Send confirmation emails
                      └──────────┘
```

### Component Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         BACKEND                                  │
├─────────────────────────────────────────────────────────────────┤
│  NEW MODELS              EXISTING MODELS                        │
│  ┌────────────┐          ┌────────────┐                        │
│  │ Cart       │          │ Booking    │ (unchanged)            │
│  ├────────────┤          └────────────┘                        │
│  │ id         │                                                 │
│  │ user_id    │          ┌────────────┐                        │
│  │ session_id │          │ BookingHold│ (add cart_id FK)       │
│  │ status     │          └────────────┘                        │
│  │ expires_at │                                                 │
│  └────────────┘          ┌────────────┐                        │
│        │                 │ PaymentInt.│ (add cart_payment_id)  │
│        │                 └────────────┘                        │
│  ┌────────────┐                                                 │
│  │ CartItem   │          NEW MODEL                              │
│  ├────────────┤          ┌────────────┐                        │
│  │ id         │          │ CartPayment│                        │
│  │ cart_id    │          ├────────────┤                        │
│  │ hold_id    │          │ id         │                        │
│  │ listing_id │          │ cart_id    │                        │
│  │ travelers  │          │ amount     │                        │
│  │ extras     │          │ status     │                        │
│  └────────────┘          └────────────┘                        │
├─────────────────────────────────────────────────────────────────┤
│  NEW SERVICES                                                    │
│  ┌────────────────┐   ┌────────────────┐   ┌────────────────┐  │
│  │ CartService    │   │ CartCheckout   │   │ CartPayment    │  │
│  │                │   │ Service        │   │ Service        │  │
│  │ - addItem()    │   │ - checkout()   │   │ - process()    │  │
│  │ - removeItem() │   │ - validate()   │   │ - refund()     │  │
│  │ - getCart()    │   │ - createAll()  │   │                │  │
│  └────────────────┘   └────────────────┘   └────────────────┘  │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                         FRONTEND                                 │
├─────────────────────────────────────────────────────────────────┤
│  NEW COMPONENTS                                                  │
│  ┌────────────────┐   ┌────────────────┐   ┌────────────────┐  │
│  │ CartProvider   │   │ CartPage       │   │ CartCheckout   │  │
│  │ (Context)      │   │                │   │ Wizard         │  │
│  │                │   │ - CartItemList │   │                │  │
│  │ - items[]      │   │ - CartSummary  │   │ - Per-item     │  │
│  │ - addItem()    │   │ - CheckoutBtn  │   │   traveler form│  │
│  │ - removeItem() │   │                │   │ - Payment step │  │
│  │ - total        │   │                │   │                │  │
│  └────────────────┘   └────────────────┘   └────────────────┘  │
│                                                                  │
│  MODIFIED COMPONENTS                                             │
│  ┌────────────────┐   ┌────────────────┐                        │
│  │ BookingPanel   │   │ Header         │                        │
│  │ + "Add to Cart"│   │ + Cart icon    │                        │
│  │   button       │   │   with count   │                        │
│  └────────────────┘   └────────────────┘                        │
└─────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### New Tables

```sql
-- Cart: Groups multiple booking holds together
CREATE TABLE carts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    session_id VARCHAR(255),  -- For guest checkout
    status VARCHAR(20) DEFAULT 'active',  -- active, checking_out, completed, abandoned
    expires_at TIMESTAMP NOT NULL,  -- All items share this deadline
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),

    -- Either user_id or session_id must be set
    CONSTRAINT cart_owner CHECK (user_id IS NOT NULL OR session_id IS NOT NULL)
);

-- Cart Items: Individual items in a cart
CREATE TABLE cart_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    cart_id UUID NOT NULL REFERENCES carts(id) ON DELETE CASCADE,
    hold_id UUID NOT NULL REFERENCES booking_holds(id),
    listing_id UUID NOT NULL REFERENCES listings(id),

    -- Traveler info for this specific item (filled during checkout)
    travelers JSON,  -- Array of traveler info objects
    extras JSON,     -- Selected extras for this item

    -- Denormalized for display (avoids joins)
    listing_title JSON,  -- {en: "...", fr: "..."}
    slot_start TIMESTAMP,
    slot_end TIMESTAMP,
    quantity INTEGER NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) NOT NULL,

    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),

    UNIQUE(cart_id, hold_id)  -- Can't add same hold twice
);

-- Cart Payment: Single payment for entire cart
CREATE TABLE cart_payments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    cart_id UUID NOT NULL REFERENCES carts(id),
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    status VARCHAR(20) NOT NULL,  -- pending, processing, succeeded, failed
    payment_method VARCHAR(50),
    gateway VARCHAR(50),
    gateway_id VARCHAR(255),  -- External payment reference
    metadata JSON,
    paid_at TIMESTAMP,
    failed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Junction: Links cart payment to individual bookings created
CREATE TABLE cart_payment_bookings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    cart_payment_id UUID NOT NULL REFERENCES cart_payments(id),
    booking_id UUID NOT NULL REFERENCES bookings(id),
    amount DECIMAL(10,2) NOT NULL,  -- Portion of payment for this booking
    created_at TIMESTAMP DEFAULT NOW()
);

-- Indexes
CREATE INDEX idx_carts_user_id ON carts(user_id);
CREATE INDEX idx_carts_session_id ON carts(session_id);
CREATE INDEX idx_carts_status ON carts(status);
CREATE INDEX idx_cart_items_cart_id ON cart_items(cart_id);
CREATE INDEX idx_cart_payments_cart_id ON cart_payments(cart_id);
```

### Modified Tables

```sql
-- Add cart reference to booking_holds
ALTER TABLE booking_holds
ADD COLUMN cart_id UUID REFERENCES carts(id) ON DELETE SET NULL;

-- Add cart_payment reference to bookings (optional, for tracking)
ALTER TABLE bookings
ADD COLUMN cart_payment_id UUID REFERENCES cart_payments(id);
```

---

## API Design

### New Endpoints

```
Cart Management
───────────────
GET    /api/v1/cart                    Get current cart
POST   /api/v1/cart/items              Add item to cart
DELETE /api/v1/cart/items/{id}         Remove item from cart
PATCH  /api/v1/cart/items/{id}         Update item (travelers, extras)
DELETE /api/v1/cart                    Clear entire cart

Cart Checkout
─────────────
POST   /api/v1/cart/checkout           Create bookings from cart
POST   /api/v1/cart/pay                Process payment for cart
```

### Request/Response Examples

#### GET /api/v1/cart

```json
{
  "data": {
    "id": "cart_uuid",
    "status": "active",
    "expiresAt": "2025-12-17T12:30:00Z",
    "items": [
      {
        "id": "item_uuid",
        "holdId": "hold_uuid",
        "listing": {
          "id": "listing_uuid",
          "title": { "en": "Sahara Desert Tour", "fr": "Tour du Sahara" },
          "slug": "sahara-desert-tour"
        },
        "slot": {
          "start": "2025-12-20T09:00:00Z",
          "end": "2025-12-20T17:00:00Z"
        },
        "quantity": 3,
        "personTypeBreakdown": { "adult": 2, "child": 1 },
        "unitPrice": 150.0,
        "subtotal": 400.0,
        "travelers": null, // Filled during checkout
        "extras": []
      },
      {
        "id": "item_uuid_2",
        "holdId": "hold_uuid_2",
        "listing": {
          "id": "listing_uuid_2",
          "title": { "en": "Djerba Island Day Trip", "fr": "..." },
          "slug": "djerba-island"
        },
        "slot": {
          "start": "2025-12-21T08:00:00Z",
          "end": "2025-12-21T18:00:00Z"
        },
        "quantity": 2,
        "personTypeBreakdown": { "adult": 2 },
        "unitPrice": 85.0,
        "subtotal": 170.0,
        "travelers": null,
        "extras": []
      }
    ],
    "summary": {
      "itemCount": 2,
      "totalGuests": 5,
      "subtotal": 570.0,
      "discount": 0,
      "total": 570.0,
      "currency": "EUR"
    }
  }
}
```

#### POST /api/v1/cart/items

```json
// Request
{
  "holdId": "hold_uuid"
}

// Response
{
  "data": {
    "id": "item_uuid",
    "cartId": "cart_uuid",
    "holdId": "hold_uuid",
    // ... item details
  },
  "cart": {
    // Full cart object with updated expiration
  }
}
```

#### POST /api/v1/cart/checkout

```json
// Request
{
  "items": [
    {
      "itemId": "item_uuid",
      "travelers": [
        { "firstName": "John", "lastName": "Doe", "email": "john@example.com", "personType": "adult" },
        { "firstName": "Jane", "lastName": "Doe", "email": "jane@example.com", "personType": "adult" },
        { "firstName": "Jimmy", "lastName": "Doe", "personType": "child" }
      ],
      "extras": []
    },
    {
      "itemId": "item_uuid_2",
      "travelers": [
        { "firstName": "John", "lastName": "Doe", "email": "john@example.com", "personType": "adult" },
        { "firstName": "Jane", "lastName": "Doe", "email": "jane@example.com", "personType": "adult" }
      ],
      "extras": []
    }
  ]
}

// Response
{
  "data": {
    "cartId": "cart_uuid",
    "bookings": [
      { "id": "booking_uuid_1", "bookingNumber": "GA-202512-XXXXX", "status": "pending_payment" },
      { "id": "booking_uuid_2", "bookingNumber": "GA-202512-YYYYY", "status": "pending_payment" }
    ],
    "paymentRequired": {
      "amount": 570.00,
      "currency": "EUR"
    }
  }
}
```

#### POST /api/v1/cart/pay

```json
// Request
{
  "paymentMethod": "card",
  "paymentData": {}
}

// Response
{
  "data": {
    "paymentId": "payment_uuid",
    "status": "succeeded",
    "bookings": [
      { "id": "booking_uuid_1", "status": "confirmed", "bookingNumber": "GA-202512-XXXXX" },
      { "id": "booking_uuid_2", "status": "confirmed", "bookingNumber": "GA-202512-YYYYY" }
    ]
  }
}
```

---

## Frontend Design

### New Components

#### 1. CartProvider (Context)

```typescript
interface CartContextType {
  cart: Cart | null;
  isLoading: boolean;
  itemCount: number;
  total: number;

  addItem: (holdId: string) => Promise<void>;
  removeItem: (itemId: string) => Promise<void>;
  updateItem: (itemId: string, data: Partial<CartItem>) => Promise<void>;
  clearCart: () => Promise<void>;

  checkout: (itemsData: CheckoutItemData[]) => Promise<CheckoutResult>;
  processPayment: (method: PaymentMethod) => Promise<PaymentResult>;
}
```

#### 2. CartIcon (Header)

- Shows cart item count badge
- Clicking opens cart drawer or navigates to /cart

#### 3. CartPage

- Lists all cart items with listing image, title, date/time, quantity, price
- Shows cart summary with subtotal, discounts, total
- "Proceed to Checkout" button
- "Clear Cart" option

#### 4. CartCheckoutWizard

Steps:

1. **Review Items** - Verify all items before entering traveler info
2. **Travelers** - For each item, enter traveler details (with copy option)
3. **Extras** - Select add-ons for each item (optional)
4. **Payment** - Select payment method, see total
5. **Confirmation** - Show all confirmed bookings

### Modified Components

#### BookingPanel

Add "Add to Cart" button alongside or replacing "Book Now":

- If cart feature enabled: show "Add to Cart"
- Clicking creates hold and adds to cart
- User can continue browsing

#### ListingPage

After adding to cart:

- Show success toast
- Option to "View Cart" or "Continue Shopping"

### State Management

```typescript
// Cart stored in:
// 1. Server (source of truth)
// 2. React Query cache (client)
// 3. Optional: localStorage for quick hydration

// Cart identification:
// - Authenticated: user_id
// - Guest: session_id (stored in localStorage)

// Hooks
const { cart, isLoading } = useCart();
const addToCart = useAddToCart();
const removeFromCart = useRemoveFromCart();
const checkout = useCartCheckout();
const processPayment = useCartPayment();
```

---

## Implementation Phases

### Phase 1: Backend Foundation

**Effort**: 2-3 days

1. Create migrations for new tables (Cart, CartItem, CartPayment)
2. Create Cart and CartItem models with relationships
3. Create CartService with basic CRUD operations
4. Create CartController with endpoints
5. Add cart_id to BookingHold model

**Deliverables**:

- `GET /cart` - returns cart (creates if not exists)
- `POST /cart/items` - adds hold to cart
- `DELETE /cart/items/{id}` - removes item
- `DELETE /cart` - clears cart

### Phase 2: Hold Synchronization

**Effort**: 1-2 days

1. Implement hold extension logic when items added
2. Create scheduled job to expire carts
3. Handle hold expiration within cart context
4. Add cart expiration notifications

**Deliverables**:

- All cart items share single expiration time
- Expiration extends when new items added
- Clear cart when expired

### Phase 3: Cart Checkout

**Effort**: 2-3 days

1. Create CartCheckoutService
2. Implement atomic booking creation (all or nothing)
3. Create CartPayment model and service
4. Integrate with existing PaymentGateway interface
5. Handle partial failures gracefully

**Deliverables**:

- `POST /cart/checkout` - creates all bookings
- `POST /cart/pay` - processes single payment
- All bookings confirmed or all rolled back

### Phase 4: Frontend Cart UI

**Effort**: 3-4 days

1. Create CartProvider context
2. Create CartIcon component for header
3. Create CartPage with item list and summary
4. Add "Add to Cart" button to BookingPanel
5. Create cart-related API hooks

**Deliverables**:

- Cart icon in header with item count
- Cart page showing all items
- Add to cart flow from listing page

### Phase 5: Cart Checkout Wizard

**Effort**: 3-4 days

1. Create CartCheckoutWizard component
2. Implement per-item traveler forms with copy functionality
3. Implement per-item extras selection
4. Create payment step for cart
5. Create multi-booking confirmation page

**Deliverables**:

- Full checkout wizard for cart
- Confirmation showing all bookings
- Emails sent for each booking

### Phase 6: Polish & Edge Cases

**Effort**: 2-3 days

1. Handle edge cases (item becomes unavailable, price changes)
2. Add proper error handling and user feedback
3. Mobile-optimize cart UI
4. Add analytics tracking
5. Write tests

**Deliverables**:

- Production-ready cart feature
- Comprehensive error handling
- Test coverage

---

## Technical Considerations

### Hold Expiration Strategy

**Option A: Sliding Window (Recommended)**

- When item added, extend ALL holds to 15 min from now
- Maximum cart lifetime: 1 hour
- Clear warning when approaching limit

**Option B: Fixed Deadline**

- First item sets cart deadline
- New items must be added before deadline
- Simpler but less flexible

### Payment Atomicity

**Approach**: Database transaction wrapping all operations

```php
DB::transaction(function () use ($cart) {
    // 1. Validate all holds still valid
    foreach ($cart->items as $item) {
        if ($item->hold->hasExpired()) {
            throw new CartItemExpiredException($item);
        }
    }

    // 2. Create all bookings
    $bookings = [];
    foreach ($cart->items as $item) {
        $bookings[] = $this->bookingService->createFromHold(
            $item->hold,
            $item->travelers,
            $item->extras
        );
    }

    // 3. Create cart payment record
    $cartPayment = CartPayment::create([
        'cart_id' => $cart->id,
        'amount' => $cart->total,
        'status' => 'pending'
    ]);

    // 4. Process payment
    $result = $this->paymentService->processCartPayment($cartPayment);

    // 5. If successful, confirm all bookings
    if ($result->isSuccessful()) {
        foreach ($bookings as $booking) {
            $booking->update(['status' => 'confirmed']);
        }
        $cart->update(['status' => 'completed']);
    }

    return $result;
});
```

### Backward Compatibility

The existing single-booking flow MUST continue working:

- `POST /listings/{slug}/holds` - unchanged
- `POST /bookings` - unchanged (creates booking from single hold)
- `POST /bookings/{id}/pay` - unchanged

Cart is an ADDITIONAL flow, not a replacement.

### Guest Checkout

Session ID strategy remains the same:

- Generated on first interaction (stored in localStorage)
- Passed to all cart/booking APIs
- Cart tied to session_id for guests
- On login, merge guest cart with user cart (if exists)

---

## Checklist

### Phase 1: Backend Foundation

- [ ] Migration: Create carts table
- [ ] Migration: Create cart_items table
- [ ] Migration: Create cart_payments table
- [ ] Migration: Add cart_id to booking_holds
- [ ] Model: Cart with relationships
- [ ] Model: CartItem with relationships
- [ ] Model: CartPayment
- [ ] Service: CartService (CRUD)
- [ ] Controller: CartController
- [ ] Routes: Cart API routes
- [ ] Request: CreateCartItemRequest validation
- [ ] Resource: CartResource, CartItemResource

### Phase 2: Hold Synchronization

- [ ] Service: Implement hold extension on add
- [ ] Job: CartExpirationJob (scheduled)
- [ ] Service: Handle expired holds in cart context
- [ ] Event: CartExpiredEvent + notification

### Phase 3: Cart Checkout

- [ ] Service: CartCheckoutService
- [ ] Service: CartPaymentService
- [ ] Controller: CartCheckoutController
- [ ] Request: CartCheckoutRequest validation
- [ ] Integrate with PaymentGateway interface
- [ ] Handle atomic transaction + rollback

### Phase 4: Frontend Cart UI

- [ ] Context: CartProvider
- [ ] Hook: useCart, useAddToCart, useRemoveFromCart
- [ ] Component: CartIcon (header)
- [ ] Component: CartDrawer or CartPage
- [ ] Component: CartItemCard
- [ ] Component: CartSummary
- [ ] Modify: BookingPanel + "Add to Cart" button
- [ ] Page: /cart route

### Phase 5: Cart Checkout Wizard

- [ ] Component: CartCheckoutWizard
- [ ] Component: CartItemTravelerForm
- [ ] Component: CartItemExtrasSelector
- [ ] Component: CartPaymentStep
- [ ] Component: CartConfirmation
- [ ] Page: /cart/checkout route
- [ ] Hook: useCartCheckout, useCartPayment

### Phase 6: Polish

- [ ] Handle item unavailable during checkout
- [ ] Handle price changes during cart lifetime
- [ ] Mobile-responsive cart UI
- [ ] Loading states and skeletons
- [ ] Error handling and user feedback
- [ ] Translations (en/fr)
- [ ] Analytics events
- [ ] Unit tests
- [ ] Integration tests

---

## Timeline Estimate

| Phase                         | Effort   | Dependencies |
| ----------------------------- | -------- | ------------ |
| Phase 1: Backend Foundation   | 2-3 days | None         |
| Phase 2: Hold Synchronization | 1-2 days | Phase 1      |
| Phase 3: Cart Checkout        | 2-3 days | Phase 1, 2   |
| Phase 4: Frontend Cart UI     | 3-4 days | Phase 1      |
| Phase 5: Checkout Wizard      | 3-4 days | Phase 3, 4   |
| Phase 6: Polish               | 2-3 days | Phase 5      |

**Total**: ~14-19 days of development work

---

## Questions to Resolve

1. **Cart Lifetime**: Maximum time a cart can exist before forced expiration?
   - Suggestion: 1 hour max, with 15-min rolling extensions

2. **Price Locking**: Should prices be locked when added to cart, or reflect current prices?
   - Suggestion: Lock prices at hold creation (current behavior)

3. **Partial Checkout**: Allow checking out some items if others become unavailable?
   - Suggestion: No, keep it simple - all or nothing

4. **Guest Cart Merge**: When guest logs in, merge cart or replace?
   - Suggestion: Merge (add guest items to existing user cart)

5. **Cart Abandonment**: Track and follow up on abandoned carts?
   - Suggestion: Future enhancement, not MVP

---

## Notes

- This is a significant feature requiring substantial development effort
- Consider implementing as a feature flag for gradual rollout
- May want to A/B test cart vs direct booking conversion rates
- Mobile experience is critical - cart UI must be touch-friendly
