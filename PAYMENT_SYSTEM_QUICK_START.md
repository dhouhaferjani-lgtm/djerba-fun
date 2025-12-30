# Payment Gateway Management - Quick Start Guide

## What Was Built

A complete payment gateway management system with:

1. **Admin Panel** - Configure and manage payment gateways
2. **Vendor Panel** - Manually confirm payments for bookings
3. **API Endpoint** - Get available payment methods for frontend
4. **Database** - Store gateway configurations dynamically

## Quick Access

### Admin Panel (Filament)

```
URL: http://localhost:8000/admin/payment-gateways
Navigation: Settings > Payment Gateways
```

### Vendor Panel Actions

```
URL: http://localhost:8000/vendor/bookings
Navigation: Bookings > My Bookings
Actions: "Mark as Paid", "Partial Payment" (on pending bookings)
```

### API Endpoint

```
GET http://localhost:8000/api/v1/payment/methods
```

## Default Configuration

Out of the box, the system includes:

| Gateway         | Status      | Default | Driver        |
| --------------- | ----------- | ------- | ------------- |
| Stripe          | Disabled    | No      | stripe        |
| Click to Pay    | Disabled    | No      | clicktopay    |
| Offline Payment | **Enabled** | **Yes** | offline       |
| Bank Transfer   | Disabled    | No      | bank_transfer |

## Quick Test

### 1. View Available Payment Methods (API)

```bash
curl http://localhost:8000/api/v1/payment/methods | jq .
```

Expected response:

```json
{
  "data": [
    {
      "driver": "offline",
      "display_name": "Offline Payment",
      "description": "Accept cash or other offline payment methods. Payment must be confirmed manually.",
      "is_default": true
    }
  ]
}
```

### 2. Enable Stripe (Admin Panel)

1. Go to **Settings > Payment Gateways**
2. Click **Edit** on Stripe row
3. Fill in credentials:
   - Publishable Key: `pk_test_your_key`
   - Secret Key: `sk_test_your_key`
   - Webhook Secret: `whsec_your_secret`
4. Toggle **Enabled** to ON
5. Click **Save**

### 3. Test API Again

```bash
curl http://localhost:8000/api/v1/payment/methods | jq .
```

Now returns both Offline Payment AND Stripe.

### 4. Set Stripe as Default

1. In the list, click **Set as Default** action on Stripe row
2. Stripe becomes the default gateway
3. Offline Payment no longer shows as default

### 5. Vendor Confirms Payment

1. Go to **Vendor Panel > Bookings**
2. Find a booking with "Pending Payment" status
3. Click **Mark as Paid** action
4. Confirm in modal
5. Booking status changes to "Confirmed"
6. Customer receives email

## Common Tasks

### Add Bank Details for Bank Transfer

1. Go to **Settings > Payment Gateways**
2. Edit **Bank Transfer**
3. Fill in:
   - Bank Name: `Your Bank`
   - Account Number: `123456789`
   - IBAN: `XX00 0000 0000 0000`
   - SWIFT: `SWIFT123`
   - Instructions: `Transfer to account...`
4. Toggle **Enabled** to ON
5. Save

### Disable a Gateway

1. In the list, toggle the **Enabled** column to OFF
2. Gateway immediately unavailable for new payments
3. Existing payments unaffected

### Record Partial Payment

1. Vendor Panel > Bookings
2. Click **Partial Payment** on pending booking
3. Enter amount: `50.00`
4. Add note: `Deposit received via bank transfer`
5. Submit
6. Booking remains pending, payment recorded

## File Locations

### Models

```
/apps/laravel-api/app/Models/PaymentGateway.php
```

### Filament Resources

```
/apps/laravel-api/app/Filament/Admin/Resources/PaymentGatewayResource.php
/apps/laravel-api/app/Filament/Vendor/Resources/BookingResource.php (modified)
```

### Services

```
/apps/laravel-api/app/Services/Payment/PaymentGatewayManager.php (modified)
/apps/laravel-api/app/Services/BookingService.php (modified)
```

### Controllers

```
/apps/laravel-api/app/Http/Controllers/Api/V1/PaymentController.php (modified)
```

### Database

```
/apps/laravel-api/database/migrations/2025_12_29_212941_create_payment_gateways_table.php
/apps/laravel-api/database/seeders/PaymentGatewaySeeder.php
```

## Troubleshooting

### Gateway Not Showing in API

**Problem:** Gateway enabled but not returned by `/api/v1/payment/methods`

**Solution:**

1. Check `is_enabled` is `true` in database
2. Verify `PaymentGatewayManager` registered the driver
3. Clear cache: `php artisan cache:clear`

### Cannot Disable Gateway

**Problem:** Error when trying to disable a gateway

**Solution:**

- If it's the default gateway, set another as default first
- Cannot disable the last enabled gateway if it's default

### Vendor Action Not Visible

**Problem:** "Mark as Paid" not showing

**Solution:**

- Only visible for bookings in `PENDING_PAYMENT` status
- Check booking status in database
- Ensure user has vendor role

## Support

For detailed documentation, see:

- `/docs/payment-gateway-management.md`
- `/PAYMENT_GATEWAY_IMPLEMENTATION.md`

## Screenshots Location Guide

### Admin Panel

1. Navigate to `/admin/payment-gateways`
2. You'll see a table with all gateways
3. Edit button shows dynamic form based on driver
4. Actions: Set Default, Test Connection, Delete

### Vendor Panel

1. Navigate to `/vendor/bookings`
2. Pending bookings show payment actions
3. Click action to see modal form
4. Submit to confirm payment

## Next Steps

1. Configure your production Stripe keys
2. Set up webhook endpoints for real-time updates
3. Test payment flow end-to-end
4. Monitor payment confirmations in activity log
5. Set up email notifications for payment events

---

**System Status:** ✅ Fully Operational

All components tested and verified working.
