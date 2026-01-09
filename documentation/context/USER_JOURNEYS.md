# User Journey Analysis Report

> **Last Updated**: 2026-01-07
> **Branch**: `latest`
> **Analyst Role**: Senior UX Analyst

---

## Executive Summary

| Role         | Screens      | Journey Status | Key Findings                                |
| ------------ | ------------ | -------------- | ------------------------------------------- |
| **Traveler** | 24 pages     | Complete       | Full booking flow, guest checkout, vouchers |
| **Vendor**   | 3 resources  | Basic          | Listings, Bookings, Extras only             |
| **Admin**    | 14 resources | Comprehensive  | Full platform management                    |

---

## 1. Traveler Journey

### 1.1 Discovery Flow

```
+---------------+    +----------------+    +-------------------+
|  Home Page    | -> |  Listings      | -> | Listing Detail    |
|  (Hero,       |    |  (Search,      |    | (Gallery, Map,    |
|  Featured)    |    |  Filters)      |    |  Reviews, FAQ)    |
+---------------+    +----------------+    +-------------------+
                            |
                 +--------------------+
                 |  Destination Page  |
                 |  (Location-based)  |
                 +--------------------+
```

**Screens Available:**

| Page            | URL Pattern                     | Purpose                                        |
| --------------- | ------------------------------- | ---------------------------------------------- |
| Home            | `/[locale]`                     | Hero, Featured, Categories, Destinations, Blog |
| Listings        | `/[locale]/listings`            | Search results with filters                    |
| Listing Detail  | `/[locale]/listings/[slug]`     | Full listing info + booking panel              |
| Location Detail | `/[locale]/[location]/[slug]`   | SEO-friendly URL variant                       |
| Destination     | `/[locale]/destinations/[slug]` | Location-based discovery                       |
| Vendor Profile  | `/[locale]/vendors/[id]`        | View vendor + their listings                   |

**Key Features:**

- **CMS-powered sections** - Home page can pull from CMS or use hardcoded fallback
- **Location-first URLs** - `/[location]/[slug]` pattern for SEO
- **Multi-locale** - French (default) + English

### 1.2 Booking Flow

```
+-------------------+    +----------------+    +---------------+
| Listing Detail    | -> |  Select        | -> |  Create       |
| (Select Date)     |    |  Time Slot     |    |  Hold         |
+-------------------+    +----------------+    +---------------+
                                                      |
+-------------------+    +----------------+    +---------------+
| Checkout Page     | <- | Auth Modal     | <- | Hold Timer    |
| /checkout/[id]    |    | (Optional)     |    | (15 min)      |
+-------------------+    +----------------+    +---------------+
        |
+-------------------+    +----------------+    +---------------+
| Contact Form      | -> |  Extras        | -> |  Payment      |
| + Billing         |    |  Selection     |    |  Method       |
+-------------------+    +----------------+    +---------------+
        |
+-------------------+    +----------------+
| Confirmation      | -> |  Vouchers      |
| Page              |    |  + Email       |
+-------------------+    +----------------+
```

**Booking Components:**

| Component               | Purpose                                   |
| ----------------------- | ----------------------------------------- |
| `PersonTypeSelector`    | Adult/Child/Senior selection with pricing |
| `AvailabilityCalendar`  | Month view with slot status               |
| `BookingWizard`         | Multi-step checkout flow                  |
| `BookingSummary`        | Price breakdown + coupon                  |
| `ExtrasSelection`       | Add-ons with quantity                     |
| `PaymentMethodSelector` | Mock/Offline payment                      |
| `CheckoutAuthModal`     | Login/Register/Continue as Guest          |

**Key Features:**

- **Guest Checkout** - No account required (session-based)
- **Hold Timer** - 15-minute reservation lock
- **Person Type Pricing** - Different prices per person type (BUG-010 fix)
- **Auth Modal** - Login/Register/Guest choice mid-checkout
- **Cart System** - Multi-item cart support (not just single booking)

### 1.3 Cart Flow (Alternative)

```
+-------------------+    +----------------+    +---------------+
| Listing Detail    | -> |  Add to        | -> |  Cart Page    |
| "Add to Cart"     |    |  Cart          |    |  /cart        |
+-------------------+    +----------------+    +---------------+
        |                                            |
+-------------------+    +----------------+    +---------------+
| Continue          | -> | Cart           | -> | Unified       |
| Shopping          |    | Summary        |    | Checkout      |
+-------------------+    +----------------+    +---------------+
```

**Cart Components:**

| Component            | Purpose                 |
| -------------------- | ----------------------- |
| `CartIcon`           | Header cart indicator   |
| `CartPage`           | Full cart view          |
| `CartItemCard`       | Individual item display |
| `CartCheckoutWizard` | Multi-item checkout     |
| `CartSummary`        | Total + discounts       |

**Key Features:**

- **Multi-item Cart** - Book multiple activities at once
- **Hold Extension** - Auto-extend holds for cart items
- **Unified Checkout** - Single payment for multiple items

### 1.4 Post-Booking Experience

```
+-------------------+    +----------------+    +---------------+
| Confirmation      | -> |  Dashboard     | -> |  Booking      |
| (Email link)      |    |  /dashboard    |    |  Detail       |
+-------------------+    +----------------+    +---------------+
        |
+-------------------+    +----------------+    +---------------+
| Vouchers Page     |    | Participants   |    |  Review       |
| /vouchers         |    |  Form          |    |  Form         |
+-------------------+    +----------------+    +---------------+
```

**Dashboard Pages:**

| Page                                    | Purpose                              |
| --------------------------------------- | ------------------------------------ |
| `/dashboard`                            | Overview with upcoming/past bookings |
| `/dashboard/bookings`                   | All bookings list                    |
| `/dashboard/bookings/[id]`              | Single booking detail                |
| `/dashboard/bookings/[id]/participants` | Enter participant names              |
| `/dashboard/bookings/[id]/vouchers`     | Download/email vouchers              |
| `/dashboard/bookings/[id]/review`       | Submit review (post-completion)      |
| `/dashboard/profile`                    | User profile management              |

**Key Features:**

- **Magic Links** - Email-based access without login
- **Booking Recovery** - `/booking/recover` to find lost bookings
- **Claim Booking** - Link guest bookings to account
- **Individual Vouchers** - Separate voucher per participant
- **PDF Download** - Single or all vouchers as PDF

### 1.5 Authentication Options

| Method             | Description                    |
| ------------------ | ------------------------------ |
| **Traditional**    | Email + Password registration  |
| **Quick Register** | Minimal info for fast checkout |
| **Magic Link**     | Passwordless email login       |
| **Guest**          | Session-based, no account      |

---

## 2. Vendor Journey

### 2.1 Vendor Portal (Filament)

**URL**: `http://localhost:8000/vendor`

```
+-------------------+    +----------------+    +---------------+
| Vendor Login      | -> |  Dashboard     | -> |  Listings     |
|                   |    |  (Stats?)      |    |  Index        |
+-------------------+    +----------------+    +---------------+
                                                      |
+-------------------+    +----------------+    +---------------+
| Edit Listing      | <- |  View          | <- |  Create       |
| (Wizard form)     |    |  Listing       |    |  Listing      |
+-------------------+    +----------------+    +---------------+
```

**Available Resources:**

| Resource            | Actions                  | Purpose                         |
| ------------------- | ------------------------ | ------------------------------- |
| **ListingResource** | Create, Edit, View, List | Manage tours/events             |
| **BookingResource** | View, List               | See bookings for their listings |
| **ExtraResource**   | CRUD                     | Manage add-on products          |

**Listing Creation Wizard (7 Steps):**

1. **Basic Information** - Type, Location, Title, Summary, Description
2. **Details & Highlights** - Highlights, Included, Not Included, Requirements
3. **Service Details** - Duration, Difficulty, Group Size
4. **Pricing** - Person types, Currency, Base price
5. **Meeting Point** - Address, Coordinates, Instructions
6. **Cancellation Policy** - Rules, Timeframes
7. **Media** - Images, GPX files

**Key Features:**

- **Draft Saving** - Save incomplete listings as drafts
- **Quick Availability** - Add availability rules during listing creation
- **GPX Upload** - Parse elevation data from trail files
- **Multi-locale** - Enter content in English + French

### 2.2 Missing Vendor Features

| Feature               | Status               | Impact                          |
| --------------------- | -------------------- | ------------------------------- |
| **Revenue Dashboard** | Missing              | No earnings overview            |
| **Payout Management** | Not in Vendor panel  | Can't request payouts           |
| **Review Responses**  | Partially documented | Limited review management       |
| **Calendar View**     | Missing              | No visual availability calendar |
| **Analytics**         | Missing              | No performance metrics          |
| **Messaging**         | Missing              | No traveler communication       |

---

## 3. Admin Journey

### 3.1 Admin Portal (Filament)

**URL**: `http://localhost:8000/admin`

```
+-------------------+    +--------------------+
| Admin Login       | -> |  Dashboard         |
|                   |    |  (Platform Stats,  |
|                   |    |   Fraud Alerts)    |
+-------------------+    +--------------------+
                                |
        +-----------------------+-----------------------+
        |                       |                       |
+---------------+       +---------------+       +---------------+
| Users         |       | Bookings      |       | Listings      |
| Management    |       | Management    |       | Management    |
+---------------+       +---------------+       +---------------+
```

**Available Resources (14):**

| Resource                        | Purpose                                       |
| ------------------------------- | --------------------------------------------- |
| **UserResource**                | Manage all users (travelers, vendors, admins) |
| **VendorProfileResource**       | KYC status, verification                      |
| **ListingResource**             | Approve/reject vendor listings                |
| **BookingResource**             | View all bookings, refunds                    |
| **AvailabilityRuleResource**    | Manage availability patterns                  |
| **CouponResource**              | Create/manage discount codes                  |
| **PayoutResource**              | Process vendor payouts                        |
| **PartnerResource**             | API partner management                        |
| **PaymentGatewayResource**      | Configure payment providers                   |
| **LocationResource**            | Manage destinations                           |
| **BlogPostResource**            | Blog content management                       |
| **BlogCategoryResource**        | Blog categories                               |
| **PageResource**                | CMS pages                                     |
| **DataDeletionRequestResource** | GDPR compliance                               |

**Dashboard Widgets:**

- **PlatformStatsWidget** - Key metrics
- **FraudAlertWidget** - Suspicious activity

**Key Features:**

- **Partner API Management** - Full B2B API with key management
- **Payment Gateway Config** - Multiple payment provider support
- **GDPR Compliance** - Data deletion request handling
- **CMS System** - Dynamic page management

---

## 4. Internationalization (i18n)

### Coverage Analysis

| Language         | Keys | Status           |
| ---------------- | ---- | ---------------- |
| **French (fr)**  | 910  | Default language |
| **English (en)** | 893  | Complete         |

**Multi-locale Content:**

- Listing titles, descriptions, highlights
- Blog posts
- CMS pages
- Location names
- Reviews

**URL Structure:**

- Default: `/fr/listings/...` (French)
- English: `/en/listings/...`
- No prefix needed for French (default)

---

## 5. API Endpoints Reference

### Public Endpoints (No Auth)

| Endpoint                               | Method | Purpose                     |
| -------------------------------------- | ------ | --------------------------- |
| `/api/v1/listings`                     | GET    | List all published listings |
| `/api/v1/listings/{slug}`              | GET    | Get single listing          |
| `/api/v1/listings/{slug}/availability` | GET    | Get availability slots      |
| `/api/v1/listings/{slug}/holds`        | POST   | Create booking hold         |
| `/api/v1/listings/{slug}/reviews`      | GET    | Get listing reviews         |
| `/api/v1/locations`                    | GET    | List all locations          |
| `/api/v1/cart`                         | GET    | View cart (session-based)   |
| `/api/v1/cart/items`                   | POST   | Add item to cart            |
| `/api/v1/cart/checkout`                | POST   | Initiate checkout           |
| `/api/v1/bookings`                     | POST   | Create booking              |
| `/api/v1/payment/methods`              | GET    | Available payment methods   |

### Protected Endpoints (Auth Required)

| Endpoint                         | Method | Purpose               |
| -------------------------------- | ------ | --------------------- |
| `/api/v1/auth/me`                | GET    | Current user info     |
| `/api/v1/bookings`               | GET    | User's bookings       |
| `/api/v1/bookings/{id}`          | GET    | Single booking detail |
| `/api/v1/bookings/{id}/cancel`   | POST   | Cancel booking        |
| `/api/v1/bookings/{id}/review`   | POST   | Submit review         |
| `/api/v1/bookings/{id}/vouchers` | GET    | Get vouchers          |
| `/api/v1/me`                     | PUT    | Update profile        |

---

## 6. Known Issues

### Critical

| Issue         | Impact        | Location                                                          |
| ------------- | ------------- | ----------------------------------------------------------------- |
| Build Failure | Cannot deploy | `destinations/[slug]/page.tsx` - `ssr: false` in Server Component |

### Medium

| Issue                          | Impact                    | Notes                 |
| ------------------------------ | ------------------------- | --------------------- |
| No Vendor Dashboard            | Poor vendor experience    | No widgets configured |
| Locations API may return empty | Destination filter issues | Needs investigation   |

### Minor

| Issue                  | Impact              | Notes                            |
| ---------------------- | ------------------- | -------------------------------- |
| Platform Settings Null | May use defaults    | Currency settings not configured |
| No Vendor Analytics    | Basic vendor portal | Feature gap vs competitors       |

---

## 7. Feature Comparison

### vs Typical Tourism Marketplace

| Feature               | Expected | Actual  | Notes                        |
| --------------------- | -------- | ------- | ---------------------------- |
| Search & Filter       | Yes      | Yes     | Working                      |
| Calendar Availability | Yes      | Yes     | Working                      |
| Guest Checkout        | Yes      | Yes     | **Better** - with hold timer |
| Multi-currency        | Yes      | Partial | TND + EUR                    |
| Reviews               | Yes      | Yes     | Working                      |
| Wishlists             | Yes      | No      | Missing                      |
| Messaging             | Yes      | No      | Missing                      |
| Vendor Analytics      | Yes      | No      | Missing                      |
| Mobile App            | Yes      | No      | Web only                     |
| Social Login          | Yes      | No      | Email only                   |
| Real Payments         | Yes      | Partial | Mock + Offline only          |

---

## 8. Unique Platform Features

1. **Person Type Pricing** - Granular pricing per traveler type (adult, child, senior)
2. **GPX Integration** - Trail elevation profiles for hiking tours
3. **Partner API** - Full B2B integration with key management
4. **CMS-powered pages** - Dynamic content management
5. **Booking claim system** - Link guest bookings to accounts post-registration
6. **Location-first URLs** - SEO-optimized URL structure
7. **Hold Timer** - 15-minute reservation lock prevents double-booking
8. **Multi-item Cart** - Book multiple activities in single transaction
9. **Magic Links** - Passwordless email access to bookings

---

## Document History

| Date       | Changes                                        |
| ---------- | ---------------------------------------------- |
| 2026-01-07 | Initial creation from branch `latest` analysis |
