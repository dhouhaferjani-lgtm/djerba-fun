# PPP Pricing - Required data-testid Attributes Checklist

This checklist shows all `data-testid` attributes that need to be added to frontend components for E2E tests to pass.

## Status Legend

- [ ] Not implemented
- [x] Implemented (update as you add them)

## Listing Page Components

### Price Display

- [ ] `listing-price` - Main price display with currency
- [ ] `price-location-hint` - Text showing detected country/currency
- [ ] `currency-info-tooltip` - Info icon to open tooltip
- [ ] `currency-tooltip-content` - Tooltip content explaining PPP

### Actions

- [ ] `book-now-button` - Primary CTA to start booking

---

## Booking Wizard - Date & Time Selection

### Date Picker

- [ ] `booking-date-selector` - Button to open date picker
- [ ] `date-{YYYY-MM-DD}` - Individual date buttons (dynamic)
  - Example: `date-2025-12-31`
  - Example: `date-2025-01-15`

### Time Picker

- [ ] `time-slot-{HH:MM}` - Time slot buttons (dynamic)
  - Example: `time-slot-09:00`
  - Example: `time-slot-14:30`

### Price Display

- [ ] `total-price` - Running total with currency

---

## Booking Wizard - Person Type Selection

### Adult Controls

- [ ] `person-type-adult-increment` - Add one adult
- [ ] `person-type-adult-decrement` - Remove one adult

### Child Controls

- [ ] `person-type-child-increment` - Add one child
- [ ] `person-type-child-decrement` - Remove one child

### Infant Controls

- [ ] `person-type-infant-increment` - Add one infant
- [ ] `person-type-infant-decrement` - Remove one infant

### Navigation

- [ ] `continue-to-traveler-info` - Proceed to next step

---

## Booking Wizard - Traveler Information

### Form Fields

- [ ] `traveler-email` - Email input
- [ ] `traveler-first-name` - First name input
- [ ] `traveler-last-name` - Last name input
- [ ] `traveler-phone` - Phone number input

### Navigation

- [ ] `continue-to-billing` - Proceed to billing address
- [ ] `back-to-date-selection` - Return to previous step

---

## Booking Wizard - Billing Address (NEW - needs implementation)

### Form Fields

- [ ] `billing-country` - Country select dropdown
- [ ] `billing-city` - City input
- [ ] `billing-postal-code` - Postal/ZIP code input
- [ ] `billing-address-line1` - Primary address line
- [ ] `billing-address-line2` - Secondary address line (optional)

### Navigation

- [ ] `continue-to-review` - Proceed to review
- [ ] `back-to-traveler-info` - Return to previous step

---

## Disclosure Modal (NEW - needs implementation)

### Modal Structure

- [ ] `price-change-disclosure` - Modal container (visibility check)
- [ ] `disclosure-modal-title` - Modal heading
- [ ] `disclosure-modal-explanation` - Explanation text

### Price Display

- [ ] `disclosure-old-price` - Previous price (based on IP)
- [ ] `disclosure-new-price` - New price (based on billing)

### Actions

- [ ] `disclosure-accept-button` - Accept price change
- [ ] `disclosure-cancel-button` - Cancel and return to billing

---

## Booking Review Page

### Price Display

- [ ] `review-total-price` - Total price on review page

### Actions

- [ ] `create-hold-button` - Create booking hold
- [ ] `back-to-billing` - Return to billing address

---

## Hold Page

### Timer Components

- [ ] `hold-timer` - Countdown timer (MM:SS format)
- [ ] `hold-timer-warning` - Warning indicator when <5 min remain

### Hold Information

- [ ] `hold-id` - Unique hold identifier (alphanumeric)
- [ ] `hold-total-price` - Locked total price
- [ ] `hold-currency` - Currency indicator
- [ ] `hold-expires-at` - Absolute expiration timestamp

### Navigation

- [ ] `proceed-to-payment` - Continue to checkout
- [ ] `back-to-hold` - Return to hold page (from payment)

---

## Checkout/Payment Page

### Price Display

- [ ] `checkout-total` - Total price on checkout page

### Payment Options

- [ ] `payment-method-mock` - Mock payment option (for testing)

### Actions

- [ ] `complete-payment-button` - Submit payment

---

## Confirmation Page

### Confirmation Display

- [ ] `booking-confirmation` - Confirmation page container
- [ ] `confirmation-total` - Final paid amount

---

## Global/Navigation Components

### Multiple Bookings

- [ ] `active-holds-indicator` - Badge showing count of active holds (optional)

---

## Implementation Priority

### Phase 1: Basic Flow (Tunisia User)

1. Listing page: `listing-price`, `price-location-hint`, `book-now-button`
2. Date/Time: `booking-date-selector`, `date-*`, `time-slot-*`, `total-price`
3. People: `person-type-*-increment`, `continue-to-traveler-info`
4. Traveler: All `traveler-*` fields, `continue-to-billing`
5. Review: `review-total-price`, `create-hold-button`
6. Hold: `hold-timer`, `hold-id`, `hold-total-price`, `proceed-to-payment`
7. Payment: `checkout-total`, `payment-method-mock`, `complete-payment-button`
8. Confirmation: `booking-confirmation`, `confirmation-total`

### Phase 2: Billing & Disclosure (VPN/Expat Users)

1. Billing form: All `billing-*` fields, `continue-to-review`
2. Disclosure modal: All `disclosure-*` elements

### Phase 3: Advanced Features

1. Currency tooltip: `currency-info-tooltip`, `currency-tooltip-content`
2. Hold timer warning: `hold-timer-warning`
3. Hold details: `hold-currency`, `hold-expires-at`
4. Multiple holds: `active-holds-indicator`

---

## Testing Each Attribute

After adding each `data-testid`, verify:

```bash
# Search for the attribute in tests
grep -r "data-testid-name" tests/e2e/ppp-pricing/

# Run specific test that uses it
pnpm playwright test tests/e2e/ppp-pricing/tunisia-user-flow.spec.ts --grep "should show TND prices"
```

---

## Notes for Developers

1. **Use semantic data-testid names** - The attribute name should describe the element's purpose, not its styling
2. **Add to root element** - Place `data-testid` on the interactive element itself, not a wrapper
3. **Dynamic values** - For dynamic testids like `date-2025-12-31`, construct them in the component:
   ```tsx
   <button data-testid={`date-${formattedDate}`}>
   ```
4. **Consistency** - Use kebab-case for all data-testid values
5. **Don't remove** - These attributes are needed for E2E tests, don't remove them

---

## Component Mapping

| Component             | File Path (approximate)                        | Required testids                       |
| --------------------- | ---------------------------------------------- | -------------------------------------- |
| ListingCard           | `components/listings/ListingCard.tsx`          | `listing-price`, `price-location-hint` |
| BookingWizard         | `components/booking/BookingWizard.tsx`         | `book-now-button`                      |
| DatePicker            | `components/booking/DatePicker.tsx`            | `booking-date-selector`, `date-*`      |
| TimePicker            | `components/booking/TimePicker.tsx`            | `time-slot-*`                          |
| PersonTypeSelector    | `components/booking/PersonTypeSelector.tsx`    | `person-type-*-increment`              |
| TravelerInfoForm      | `components/booking/TravelerInfoForm.tsx`      | `traveler-*`                           |
| BillingAddressForm    | `components/booking/BillingAddressForm.tsx`    | `billing-*` (NEW)                      |
| PriceChangeDisclosure | `components/booking/PriceChangeDisclosure.tsx` | `disclosure-*` (NEW)                   |
| BookingReview         | `components/booking/BookingReview.tsx`         | `review-total-price`                   |
| HoldTimer             | `components/booking/HoldTimer.tsx`             | `hold-timer`, `hold-*`                 |
| CheckoutPage          | `app/[locale]/checkout/[holdId]/page.tsx`      | `checkout-total`                       |
| ConfirmationPage      | `components/booking/BookingConfirmation.tsx`   | `booking-confirmation`                 |

---

## Total Count: 50+ data-testid attributes

**Implemented:** 0 / 50+
**In Progress:** 0
**Not Started:** 50+

Update this checklist as you implement each attribute!
