# Data-TestID Additions for PPP E2E Tests

This document lists all data-testid attributes that need to be added to existing components to support the PPP pricing E2E tests.

## Listing Page Components

### ListingDetailClient (listing-detail-client.tsx)

- `data-testid="book-now-button"` - Main "Book Now" CTA button
- `data-testid="listing-price"` - Price display element
- `data-testid="price-location-hint"` - Location hint below price
- `data-testid="currency-info-tooltip"` - Info icon for currency explanation
- `data-testid="currency-tooltip-content"` - Tooltip content popup

### Booking Panel (within BookingPanel.tsx and FixedBookingPanel.tsx)

- `data-testid="booking-date-selector"` - Date picker/selector
- `data-testid="date-YYYY-MM-DD"` - Individual date buttons (dynamic, e.g., date-2025-01-15)
- `data-testid="time-slot-HH:MM"` - Time slot buttons (dynamic, e.g., time-slot-09:00)
- `data-testid="person-type-adult-increment"` - Add adult button
- `data-testid="person-type-adult-decrement"` - Remove adult button
- `data-testid="person-type-child-increment"` - Add child button
- `data-testid="person-type-child-decrement"` - Remove child button
- `data-testid="person-type-infant-increment"` - Add infant button (if applicable)
- `data-testid="total-price"` - Total price display in booking panel

## Booking Wizard Steps

### Step 1: Email (CheckoutAuth.tsx)

✅ Already Created - Add these:

- `data-testid="traveler-email"` - Email input field
- `data-testid="continue-to-extras"` or similar - Continue button

### Step 2: Traveler Info (if full flow used)

- `data-testid="traveler-first-name"` - First name input
- `data-testid="traveler-last-name"` - Last name input
- `data-testid="traveler-phone"` - Phone number input
- `data-testid="traveler-email"` - Email input
- `data-testid="continue-to-billing"` - Continue button
- `data-testid="back-to-date-selection"` or similar - Back button

### Step 3: Extras Selection (ExtrasSelection.tsx)

- `data-testid="continue-to-traveler-info"` or `continue-to-billing` - Continue button
- `data-testid="skip-extras"` - Skip extras button (if exists)

### Step 4: Billing Address (BillingAddressStep.tsx)

✅ Already Created with all data-testids:

- `data-testid="billing-country"`
- `data-testid="billing-city"`
- `data-testid="billing-postal-code"`
- `data-testid="billing-address-line1"`
- `data-testid="billing-address-line2"`
- `data-testid="continue-to-review"`
- `data-testid="back-to-traveler-info"`

### Step 5: Review (BookingReview.tsx)

- `data-testid="review-total-price"` - Total price on review page
- `data-testid="create-hold-button"` - Create hold/reserve button
- `data-testid="back-to-billing"` - Back button

## Hold/Payment Page

### Hold Display

- `data-testid="hold-timer"` - Countdown timer display
- `data-testid="hold-timer-warning"` - Warning indicator when time low (optional)
- `data-testid="hold-total-price"` - Total price in hold state
- `data-testid="hold-id"` - Hold ID display
- `data-testid="hold-currency"` - Currency indicator
- `data-testid="hold-expires-at"` - Expiration timestamp
- `data-testid="proceed-to-payment"` - Proceed to payment button
- `data-testid="back-to-hold"` - Back from payment button

### Payment Page

- `data-testid="checkout-total"` - Total on checkout/payment page
- `data-testid="payment-method-mock"` - Mock payment method selector
- `data-testid="payment-method-offline"` - Offline payment method
- `data-testid="payment-method-click_to_pay"` - Click to Pay method
- `data-testid="complete-payment-button"` - Final payment button

### Confirmation Page (BookingConfirmation.tsx)

- `data-testid="booking-confirmation"` - Confirmation container
- `data-testid="confirmation-total"` - Total price on confirmation

## Pricing Disclosure Modal

✅ Already Created (PricingDisclosureModal.tsx):

- `data-testid="price-change-disclosure"` - Modal container
- `data-testid="disclosure-modal-title"` - Modal title
- `data-testid="disclosure-modal-explanation"` - Explanation text
- `data-testid="disclosure-old-price"` - Old price display
- `data-testid="disclosure-new-price"` - New price display
- `data-testid="disclosure-accept-button"` - Accept button
- `data-testid="disclosure-cancel-button"` - Cancel/Go back button

## Navigation/Session

- `data-testid="active-holds-indicator"` - Badge showing number of active holds (optional)
- `data-testid="back-to-listings"` - Navigation back to listings

## Footer

✅ Already Created (FooterPricingDisclosure.tsx):

- `data-testid="footer-pricing-disclosure"` - PPP disclosure in footer

## Implementation Priority

### HIGH PRIORITY (Required for tests to run):

1. ✅ BillingAddressStep.tsx - DONE
2. ✅ PricingDisclosureModal.tsx - DONE
3. ✅ BookingWizardUpdated.tsx - DONE
4. Listing page price displays
5. Booking panel person type selectors
6. Hold page elements
7. Payment page elements
8. Confirmation page elements

### MEDIUM PRIORITY:

1. Navigation between wizard steps
2. Active holds indicator
3. Back buttons

### LOW PRIORITY:

1. Timer warning states
2. Optional fields
3. Tooltips (already added to PriceDisplay)

## Files to Update

1. `/apps/web/src/app/[locale]/listings/[slug]/listing-detail-client.tsx`
2. `/apps/web/src/components/booking/CheckoutAuth.tsx`
3. `/apps/web/src/components/booking/ExtrasSelection.tsx`
4. `/apps/web/src/components/booking/BookingReview.tsx`
5. `/apps/web/src/components/booking/BookingPanel.tsx`
6. `/apps/web/src/components/booking/FixedBookingPanel.tsx`
7. `/apps/web/src/components/booking/PersonTypeSelector.tsx`
8. `/apps/web/src/components/booking/PaymentMethodSelector.tsx`
9. `/apps/web/src/components/booking/BookingConfirmation.tsx`
10. `/apps/web/src/components/availability/HoldTimer.tsx`
11. `/apps/web/src/components/availability/AvailabilityCalendar.tsx`

## Next Steps

The main BookingWizard.tsx file should be replaced with BookingWizardUpdated.tsx which includes:

- Billing address step integration
- Price disclosure modal integration
- Currency locking logic
- All necessary data-testid attributes
