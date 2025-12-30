# Payment Gateway Management System

This document describes the comprehensive payment gateway management system implemented for the Go Adventure marketplace.

## Overview

The payment gateway management system allows administrators to:

- Configure multiple payment gateways (Stripe, Click to Pay, Offline Payment, Bank Transfer)
- Enable/disable gateways dynamically
- Set a default gateway
- Configure gateway-specific settings (API keys, bank details, etc.)
- Control gateway priority/ordering
- Vendors can manually mark bookings as paid or partially paid

## Database Structure

### Payment Gateways Table

```sql
CREATE TABLE payment_gateways (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) UNIQUE,          -- Internal identifier
    slug VARCHAR(255) UNIQUE,          -- URL-friendly identifier
    display_name VARCHAR(255),         -- User-facing name
    description TEXT,                  -- Gateway description
    driver VARCHAR(255),               -- Driver: stripe, clicktopay, offline, bank_transfer
    is_enabled BOOLEAN DEFAULT FALSE,  -- Enable/disable gateway
    is_default BOOLEAN DEFAULT FALSE,  -- Mark as default
    priority INTEGER DEFAULT 0,        -- Sorting order
    configuration JSON,                -- Gateway-specific config
    test_mode BOOLEAN DEFAULT FALSE,   -- Test/sandbox mode
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

## Payment Gateway Model

Location: `/apps/laravel-api/app/Models/PaymentGateway.php`

### Key Methods

- `enabled()` - Scope to get only enabled gateways
- `default()` - Scope to get the default gateway
- `orderedByPriority()` - Scope to order by priority
- `getConfigValue($key, $default)` - Get a configuration value
- `setConfigValue($key, $value)` - Set a configuration value
- `enable()` / `disable()` - Enable/disable the gateway
- `setAsDefault()` - Set this gateway as default (unsets others)
- `getFullConfiguration()` - Get configuration with driver-specific defaults

## Filament Admin Resource

Location: `/apps/laravel-api/app/Filament/Admin/Resources/PaymentGatewayResource.php`

### Features

1. **List View:**
   - Toggle to enable/disable gateways
   - See gateway status, driver, priority
   - Filter by driver, status, test mode

2. **Form View:**
   - Basic information (name, slug, display name, description)
   - Gateway configuration (driver, priority, enabled, default, test mode)
   - Driver-specific configuration fields:
     - **Stripe:** publishable_key, secret_key, webhook_secret
     - **Click to Pay:** merchant_id, api_key, shared_secret
     - **Bank Transfer:** bank details, IBAN, SWIFT, instructions
     - **Offline Payment:** payment instructions

3. **Actions:**
   - Set as Default
   - Test Connection (for online gateways)
   - Enable/Disable (bulk action)
   - Delete (prevents deleting default gateway)

## Vendor Payment Management

Location: `/apps/laravel-api/app/Filament/Vendor/Resources/BookingResource.php`

### New Actions

1. **Mark as Paid** - Confirm full payment received
   - Creates a payment intent with SUCCEEDED status
   - Transitions booking to CONFIRMED status
   - Logs who confirmed the payment
   - Sends confirmation email to customer

2. **Mark Partially Paid** - Record partial payment
   - Input: amount paid, optional note
   - Creates a payment intent for the partial amount
   - Booking remains in PENDING_PAYMENT status
   - Logs partial payment details
   - Can record multiple partial payments

Both actions are only visible for bookings in `PENDING_PAYMENT` status.

## Payment Gateway Manager

Location: `/apps/laravel-api/app/Services/Payment/PaymentGatewayManager.php`

### Enhanced Methods

- `gateway($name)` - Get gateway by name (checks if enabled)
- `default()` - Get default gateway from database or config
- `getEnabledGateways()` - Get all enabled gateways ordered by priority
- `getAvailablePaymentMethods()` - Get available payment methods for frontend

### Database Integration

The manager now checks the `payment_gateways` table to:

- Verify a gateway is enabled before returning it
- Get the default gateway from database settings
- Throw an exception if a disabled gateway is requested

## Booking Service Updates

Location: `/apps/laravel-api/app/Services/BookingService.php`

### New Method

```php
public function markAsPaid(Booking $booking): Booking
```

This method:

- Validates the booking is in PENDING_PAYMENT status
- Creates a payment intent with offline payment method
- Confirms the payment and booking
- Reserves inventory for extras
- Sends confirmation email

## API Endpoints

### Get Available Payment Methods

```
GET /api/v1/payment/methods
```

Returns all enabled payment gateways:

```json
{
  "data": [
    {
      "driver": "offline",
      "display_name": "Offline Payment",
      "description": "Accept cash or other offline payment methods...",
      "is_default": true
    }
  ]
}
```

## Default Gateway Configuration

The system seeds with these default gateways:

1. **Stripe** (disabled, test mode)
2. **Click to Pay** (disabled, test mode)
3. **Offline Payment** (enabled, default, live mode)
4. **Bank Transfer** (disabled, live mode)

## Workflow Examples

### Admin Enabling Stripe

1. Navigate to Settings > Payment Gateways in Filament Admin
2. Click Edit on Stripe gateway
3. Enter publishable_key, secret_key, webhook_secret
4. Toggle "Enabled" to ON
5. Optionally set as "Default"
6. Save

### Vendor Confirming Offline Payment

1. Navigate to Bookings > My Bookings in Filament Vendor
2. Find booking with "Pending Payment" status
3. Click "Mark as Paid" action
4. Confirm in modal
5. System:
   - Creates payment intent
   - Confirms booking
   - Sends confirmation email
   - Logs audit trail

### Recording Partial Payment

1. Navigate to booking in "Pending Payment" status
2. Click "Partial Payment" action
3. Enter amount paid (e.g., 50.00)
4. Add optional note (e.g., "Deposit received")
5. Submit
6. System:
   - Records payment intent
   - Keeps booking in pending status
   - Logs partial payment with note

## Security Considerations

1. **API Keys Protected:** All sensitive config fields use `password()` with `revealable()` in Filament
2. **Default Gateway Protection:** Cannot disable or delete the default gateway without setting another as default
3. **Audit Trail:** All manual payment confirmations are logged with user ID and timestamp
4. **Gateway Validation:** PaymentGatewayManager checks if gateway is enabled before allowing use

## Testing

To test the system:

```bash
# Test API endpoint
curl http://localhost:8000/api/v1/payment/methods

# Check seeded gateways
php artisan tinker
>>> App\Models\PaymentGateway::all();

# Test enabling a gateway
>>> $stripe = App\Models\PaymentGateway::where('driver', 'stripe')->first();
>>> $stripe->enable();
>>> $stripe->setAsDefault();
```

## Future Enhancements

1. Add real gateway integrations (Stripe API, Click to Pay API)
2. Implement webhook handling for payment status updates
3. Add gateway transaction logs/history
4. Support multi-currency per gateway
5. Add gateway-specific validation rules
6. Implement automated payment reconciliation
7. Add payment analytics dashboard

## Files Created/Modified

### New Files

- `/apps/laravel-api/database/migrations/2025_12_29_212941_create_payment_gateways_table.php`
- `/apps/laravel-api/app/Models/PaymentGateway.php`
- `/apps/laravel-api/app/Filament/Admin/Resources/PaymentGatewayResource.php`
- `/apps/laravel-api/app/Filament/Admin/Resources/PaymentGatewayResource/Pages/ListPaymentGateways.php`
- `/apps/laravel-api/app/Filament/Admin/Resources/PaymentGatewayResource/Pages/CreatePaymentGateway.php`
- `/apps/laravel-api/app/Filament/Admin/Resources/PaymentGatewayResource/Pages/EditPaymentGateway.php`
- `/apps/laravel-api/database/seeders/PaymentGatewaySeeder.php`

### Modified Files

- `/apps/laravel-api/app/Services/Payment/PaymentGatewayManager.php` - Added database integration
- `/apps/laravel-api/app/Services/BookingService.php` - Added `markAsPaid()` method
- `/apps/laravel-api/app/Filament/Vendor/Resources/BookingResource.php` - Added payment actions
- `/apps/laravel-api/app/Http/Controllers/Api/V1/PaymentController.php` - Added `availableMethods()` endpoint
- `/apps/laravel-api/routes/api.php` - Added payment methods route
- `/apps/laravel-api/database/seeders/DatabaseSeeder.php` - Added PaymentGatewaySeeder
