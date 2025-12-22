# Super Admin Settings Panel - Handover Document

**Version**: 1.0
**Date**: 2025-12-22
**Status**: Pending Implementation
**Priority**: High - Required for White-Label Deployment

---

## Executive Summary

This document identifies all hardcoded values in the Go Adventure marketplace that should be moved to a centralized Super Admin Settings panel. This will enable white-label deployment, allowing the platform to be customized for different brands without code changes.

### Goals

1. **White-Label Ready**: Enable platform rebranding through admin interface
2. **Centralized Configuration**: Single source of truth for all platform settings
3. **No Code Deployments**: Allow configuration changes without developer intervention
4. **Multi-Language Support**: All settings should support EN/FR/AR translations where applicable

### Implementation Approach

Create a `PlatformSettings` model with Filament resource in the Super Admin panel, organized into logical sections with tabbed interface.

---

## 1. Platform Identity Settings

**Current State**: Hardcoded in multiple files
**Required Fields**: All fields should be translatable (EN/FR/AR)

### 1.1 Basic Information

| Setting                  | Current Value  | Location       | Usage                           |
| ------------------------ | -------------- | -------------- | ------------------------------- |
| **Platform Name**        | "Go Adventure" | Multiple files | Site title, emails, metadata    |
| **Platform Tagline**     | N/A (missing)  | N/A            | Hero section, meta descriptions |
| **Platform Description** | N/A (missing)  | N/A            | About section, footer, SEO      |
| **Short Description**    | N/A (missing)  | N/A            | Meta descriptions, social cards |

**Files Affected**:

- `apps/web/src/components/seo/JsonLd.tsx` (line 15: name: 'Go Adventure')
- `apps/web/src/app/[locale]/layout.tsx` (metadata)
- `apps/laravel-api/.env` (APP_NAME=Go Adventure)
- Email templates (platform name in headers/footers)

### 1.2 URLs & Domains

| Setting            | Current Value         | Location              | Type           |
| ------------------ | --------------------- | --------------------- | -------------- |
| **Primary Domain** | goadventure.com       | Environment variables | URL            |
| **API Base URL**   | http://localhost:8000 | `apps/web/.env.local` | URL            |
| **Frontend URL**   | http://localhost:3000 | Various               | URL            |
| **CDN URL**        | N/A                   | N/A                   | URL (optional) |

**Files Affected**:

- `apps/web/src/components/seo/JsonLd.tsx` (lines 16, 39, 67, 95)
- `.env` files across projects
- Email templates (links back to platform)

### 1.3 Logo & Branding Assets

| Setting                | Current Value           | Location         | Type  |
| ---------------------- | ----------------------- | ---------------- | ----- |
| **Logo URL**           | `/logo.png`             | Hardcoded        | Media |
| **Favicon**            | `/favicon.ico`          | Hardcoded        | Media |
| **OG Image (Default)** | `/og-image.png`         | Hardcoded        | Media |
| **Apple Touch Icon**   | `/apple-touch-icon.png` | Hardcoded        | Media |
| **Logo (Dark Mode)**   | N/A                     | N/A              | Media |
| **Logo (Admin Panel)** | N/A                     | Filament default | Media |

**Files Affected**:

- `apps/web/src/components/seo/JsonLd.tsx` (line 17)
- `apps/web/src/app/[locale]/layout.tsx` (metadata icons)
- Filament panel providers

**Storage**: Use Spatie Media Library for file uploads

---

## 2. SEO & Metadata Settings

**Current State**: Partially hardcoded, partially missing
**Required Fields**: Translatable content

### 2.1 Default Meta Tags

| Setting                      | Current Value                       | Location           | Type             |
| ---------------------------- | ----------------------------------- | ------------------ | ---------------- |
| **Default Meta Title**       | "Go Adventure - Tours & Activities" | Various page files | Text (60 chars)  |
| **Default Meta Description** | N/A (missing)                       | N/A                | Text (160 chars) |
| **Keywords**                 | N/A                                 | N/A                | Text             |
| **Author**                   | N/A                                 | N/A                | Text             |

**Files Affected**:

- All `apps/web/src/app/[locale]/*/page.tsx` metadata exports
- Layout files

### 2.2 Organization Schema (JSON-LD)

| Setting                      | Current Value                  | Location                | Type   |
| ---------------------------- | ------------------------------ | ----------------------- | ------ |
| **Organization Name**        | "Go Adventure"                 | `JsonLd.tsx` line 15    | Text   |
| **Organization Description** | "Your gateway to authentic..." | `JsonLd.tsx` line 18-20 | Text   |
| **Organization Type**        | "TravelAgency"                 | `JsonLd.tsx` line 14    | Select |
| **Founded Year**             | N/A                            | N/A                     | Number |
| **Logo URL**                 | Hardcoded                      | `JsonLd.tsx` line 17    | Media  |

**Files Affected**:

- `apps/web/src/components/seo/JsonLd.tsx` (OrganizationJsonLd function)

**Schema Fields Needed**:

```typescript
{
  name: string (translatable)
  description: string (translatable)
  url: string
  logo: string (media upload)
  foundingDate: string
  organizationType: 'TravelAgency' | 'LocalBusiness' | 'Organization'
  sameAs: string[] (social media URLs)
  address: {
    streetAddress: string
    addressLocality: string
    addressRegion: string
    postalCode: string
    addressCountry: string
  }
  contactPoint: {
    telephone: string
    contactType: string
    email: string
    availableLanguage: string[]
  }
}
```

### 2.3 Social Media Open Graph

| Setting                | Current Value         | Location  | Type   |
| ---------------------- | --------------------- | --------- | ------ |
| **OG Site Name**       | "Go Adventure"        | Hardcoded | Text   |
| **OG Type (Default)**  | "website"             | Hardcoded | Text   |
| **OG Image (Default)** | "/og-image.png"       | Hardcoded | Media  |
| **Twitter Card Type**  | "summary_large_image" | Hardcoded | Select |
| **Twitter Handle**     | N/A                   | N/A       | Text   |

**Files Affected**:

- Layout and page metadata exports

---

## 3. Contact Information

**Current State**: Hardcoded or missing
**Priority**: High - Used in footer, contact pages, schema markup

### 3.1 Primary Contact

| Setting                     | Current Value             | Location    | Type  |
| --------------------------- | ------------------------- | ----------- | ----- |
| **Support Email**           | "hello@goadventure.local" | `.env` file | Email |
| **General Inquiries Email** | N/A                       | N/A         | Email |
| **Phone Number**            | N/A                       | N/A         | Phone |
| **WhatsApp Number**         | N/A                       | N/A         | Phone |
| **Business Hours**          | N/A                       | N/A         | Text  |

**Files Affected**:

- `apps/laravel-api/.env` (MAIL_FROM_ADDRESS)
- Footer component (when created)
- Contact page
- Email templates

### 3.2 Physical Address

| Setting              | Current Value | Location | Type |
| -------------------- | ------------- | -------- | ---- |
| **Street Address**   | N/A           | N/A      | Text |
| **City**             | N/A           | N/A      | Text |
| **State/Region**     | N/A           | N/A      | Text |
| **Postal Code**      | N/A           | N/A      | Text |
| **Country**          | "Tunisia"     | Implied  | Text |
| **Google Maps Link** | N/A           | N/A      | URL  |

**Usage**: Footer, Contact page, Organization schema

---

## 4. Social Media Links

**Current State**: Missing
**Priority**: Medium - Used in footer and schema markup

### 4.1 Social Profiles

| Setting           | Default Value | Type | Purpose               |
| ----------------- | ------------- | ---- | --------------------- |
| **Facebook URL**  | N/A           | URL  | Footer, schema sameAs |
| **Instagram URL** | N/A           | URL  | Footer, schema sameAs |
| **Twitter/X URL** | N/A           | URL  | Footer, schema sameAs |
| **LinkedIn URL**  | N/A           | URL  | Footer, schema sameAs |
| **YouTube URL**   | N/A           | URL  | Footer, schema sameAs |
| **TikTok URL**    | N/A           | URL  | Footer, schema sameAs |

**Files Affected**:

- `apps/web/src/components/seo/JsonLd.tsx` (sameAs array - currently hardcoded examples)
- Footer component
- Social sharing buttons

**Current Hardcoded Values** (examples only):

```typescript
// apps/web/src/components/seo/JsonLd.tsx:21-24
sameAs: ['https://facebook.com/goadventure', 'https://instagram.com/goadventure'];
```

---

## 5. Email & Notification Settings

**Current State**: Environment variables
**Priority**: High - Core functionality

### 5.1 Email Configuration

| Setting            | Current Value             | Location | Type  |
| ------------------ | ------------------------- | -------- | ----- |
| **From Name**      | "Go Adventure"            | `.env`   | Text  |
| **From Email**     | "hello@goadventure.local" | `.env`   | Email |
| **Reply-To Email** | N/A                       | N/A      | Email |
| **Support Email**  | N/A                       | N/A      | Email |

**Files Affected**:

- `apps/laravel-api/.env`
- All Mail classes

### 5.2 Email Templates Content

| Setting                         | Current State           | Type                     |
| ------------------------------- | ----------------------- | ------------------------ |
| **Booking Confirmation Header** | Hardcoded in Mail class | Text (translatable)      |
| **Booking Confirmation Footer** | Hardcoded in Mail class | Text (translatable)      |
| **Email Signature**             | N/A                     | Rich Text (translatable) |
| **Terms & Conditions Link**     | N/A                     | URL                      |
| **Privacy Policy Link**         | N/A                     | URL                      |

**Files Affected**:

- `apps/laravel-api/app/Mail/BookingConfirmationMail.php`
- `apps/laravel-api/app/Mail/BookingCancellationMail.php`
- `apps/laravel-api/app/Mail/MagicLinkMail.php`
- Email view templates

---

## 6. Payment & Commerce Settings

**Current State**: Environment variables and hardcoded
**Priority**: High - Revenue critical

### 6.1 Currency Settings

| Setting                | Current Value         | Location  | Type              |
| ---------------------- | --------------------- | --------- | ----------------- |
| **Default Currency**   | "TND"                 | Hardcoded | Select (ISO 4217) |
| **Currency Symbol**    | N/A                   | N/A       | Text              |
| **Decimal Places**     | 2                     | Hardcoded | Number            |
| **Thousand Separator** | ","                   | Hardcoded | Text              |
| **Decimal Separator**  | "."                   | Hardcoded | Text              |
| **Enabled Currencies** | ["TND", "EUR", "USD"] | Hardcoded | Multi-select      |

**Files Affected**:

- `packages/schemas/src/index.ts` (pricingSchema)
- Price display components
- Checkout components

### 6.2 Payment Gateway Configuration

| Setting                     | Current Value       | Location | Type         |
| --------------------------- | ------------------- | -------- | ------------ |
| **Default Gateway**         | "mock"              | Code     | Select       |
| **Stripe Enabled**          | false               | N/A      | Boolean      |
| **Stripe Publishable Key**  | N/A                 | `.env`   | Encrypted    |
| **Stripe Secret Key**       | N/A                 | `.env`   | Encrypted    |
| **Payment Methods Enabled** | ["mock", "offline"] | Code     | Multi-select |

**Files Affected**:

- `apps/laravel-api/app/Services/BookingService.php`
- Payment gateway configuration

### 6.3 Commission & Fees

| Setting                        | Current Value     | Location | Type    |
| ------------------------------ | ----------------- | -------- | ------- |
| **Platform Commission (%)**    | Hardcoded in code | N/A      | Decimal |
| **Payment Processing Fee (%)** | N/A               | N/A      | Decimal |
| **Minimum Booking Amount**     | N/A               | N/A      | Decimal |
| **Maximum Booking Amount**     | N/A               | N/A      | Decimal |

---

## 7. Booking & Reservation Settings

**Current State**: Hardcoded
**Priority**: High - Core functionality

### 7.1 Hold & Timeout Settings

| Setting                         | Current Value | Location            | Type   |
| ------------------------------- | ------------- | ------------------- | ------ |
| **Hold Duration (minutes)**     | 15            | `BookingHold` model | Number |
| **Hold Warning Time (minutes)** | 5             | Frontend            | Number |
| **Auto-Cancel After (hours)**   | 24            | N/A                 | Number |

**Files Affected**:

- `apps/laravel-api/app/Models/BookingHold.php`
- `apps/web/src/components/booking/HoldTimer.tsx`

### 7.2 Cancellation Policies

| Setting                              | Current Value      | Location      | Type    |
| ------------------------------------ | ------------------ | ------------- | ------- |
| **Free Cancellation Period (hours)** | Varies per listing | Listing model | Number  |
| **Cancellation Fee (%)**             | Varies per listing | Listing model | Decimal |
| **Non-Refundable Period (hours)**    | Varies per listing | Listing model | Number  |

**Note**: These are currently set per-listing, but default values should be configurable.

---

## 8. Localization & Language Settings

**Current State**: Partially configured
**Priority**: High - Affects entire platform

### 8.1 Language Configuration

| Setting                 | Current Value      | Location       | Type         |
| ----------------------- | ------------------ | -------------- | ------------ |
| **Default Language**    | "fr"               | `routing.ts`   | Select       |
| **Available Languages** | ["fr", "en", "ar"] | `routing.ts`   | Multi-select |
| **Fallback Language**   | "en"               | Laravel config | Select       |
| **RTL Languages**       | ["ar"]             | Code           | Multi-select |

**Files Affected**:

- `apps/web/src/i18n/routing.ts`
- `apps/laravel-api/config/app.php`

### 8.2 Date & Time Formats

| Setting            | Current Value | Location  | Type             |
| ------------------ | ------------- | --------- | ---------------- |
| **Date Format**    | Various       | Hardcoded | Select           |
| **Time Format**    | "24h"         | Hardcoded | Select (12h/24h) |
| **Timezone**       | "UTC"         | `.env`    | Timezone         |
| **Week Starts On** | "Monday"      | Hardcoded | Select           |

**Files Affected**:

- Date formatting utilities
- Calendar components

---

## 9. Feature Flags & Toggles

**Current State**: Not implemented
**Priority**: Medium - Enables gradual rollout

### 9.1 Core Features

| Feature                    | Default | Type    | Description                    |
| -------------------------- | ------- | ------- | ------------------------------ |
| **Enable Reviews**         | true    | Boolean | Allow users to review bookings |
| **Enable Wishlists**       | false   | Boolean | Wishlist functionality         |
| **Enable Gift Cards**      | false   | Boolean | Gift card purchases            |
| **Enable Loyalty Program** | false   | Boolean | Points and rewards             |
| **Enable Agent API**       | true    | Boolean | API for travel agents          |
| **Enable Blog**            | true    | Boolean | Blog section                   |

### 9.2 Advanced Features

| Feature                       | Default | Type    | Description                  |
| ----------------------------- | ------- | ------- | ---------------------------- |
| **Enable Instant Booking**    | true    | Boolean | Book without vendor approval |
| **Enable Request to Book**    | true    | Boolean | Vendor approval required     |
| **Enable Group Bookings**     | false   | Boolean | Bookings for groups >10      |
| **Enable Custom Packages**    | false   | Boolean | Multi-listing packages       |
| **Enable Subscription Plans** | false   | Boolean | Recurring memberships        |

---

## 10. Analytics & Tracking

**Current State**: Partially implemented
**Priority**: Medium - Important for insights

### 10.1 Google Services

| Setting                   | Current Value | Location | Type      |
| ------------------------- | ------------- | -------- | --------- |
| **Google Analytics 4 ID** | N/A           | `.env`   | Text      |
| **Google Tag Manager ID** | N/A           | N/A      | Text      |
| **Google Search Console** | N/A           | N/A      | Text      |
| **Google Maps API Key**   | N/A           | N/A      | Encrypted |

**Files Affected**:

- `apps/web/src/lib/gtag.ts`
- `apps/web/src/app/web-vitals.tsx`
- Map components

### 10.2 Other Tracking

| Setting               | Current Value | Location | Type      |
| --------------------- | ------------- | -------- | --------- |
| **Facebook Pixel ID** | N/A           | N/A      | Text      |
| **Hotjar ID**         | N/A           | N/A      | Text      |
| **Plausible Domain**  | N/A           | N/A      | Text      |
| **Sentry DSN**        | N/A           | N/A      | Encrypted |

---

## 11. Legal & Compliance

**Current State**: Missing
**Priority**: High - Legal requirement

### 11.1 Legal Pages

| Setting                  | Current State | Type            | Required |
| ------------------------ | ------------- | --------------- | -------- |
| **Terms of Service**     | Not created   | URL or CMS Page | Yes      |
| **Privacy Policy**       | Not created   | URL or CMS Page | Yes      |
| **Cookie Policy**        | Not created   | URL or CMS Page | Yes      |
| **Refund Policy**        | Not created   | URL or CMS Page | Yes      |
| **Data Deletion Policy** | Not created   | URL or CMS Page | Yes      |

### 11.2 Compliance Settings

| Setting                          | Current Value | Type    | Description          |
| -------------------------------- | ------------- | ------- | -------------------- |
| **Cookie Consent Enabled**       | false         | Boolean | Show cookie banner   |
| **GDPR Compliance Mode**         | false         | Boolean | Enable GDPR features |
| **Data Retention Period (days)** | 365           | Number  | Auto-delete old data |
| **Min Age Requirement**          | 18            | Number  | Minimum user age     |

---

## 12. Homepage Customization

**Current State**: Hardcoded components
**Priority**: Medium - Better managed through CMS

### 12.1 Hero Section

| Setting             | Current Location  | Type                | Usage              |
| ------------------- | ----------------- | ------------------- | ------------------ |
| **Hero Title**      | `HeroSection.tsx` | Text (translatable) | Main heading       |
| **Hero Subtitle**   | `HeroSection.tsx` | Text (translatable) | Subheading         |
| **Hero CTA Text**   | `HeroSection.tsx` | Text (translatable) | Button text        |
| **Hero CTA Link**   | Hardcoded         | URL                 | Button destination |
| **Hero Background** | Hardcoded         | Media               | Background image   |

**Files Affected**:

- `apps/web/src/components/organisms/HeroSection.tsx`

### 12.2 Featured Content

| Setting                   | Current State          | Type                        |
| ------------------------- | ---------------------- | --------------------------- |
| **Featured Packages**     | Hardcoded in component | Listing IDs (multi-select)  |
| **Featured Destinations** | Hardcoded in component | Location IDs (multi-select) |
| **Featured Categories**   | Hardcoded in component | Category IDs (multi-select) |

**Note**: Should be selectable through admin instead of hardcoded.

---

## 13. Vendor Settings

**Current State**: Mixed
**Priority**: Medium - Vendor experience

### 13.1 Vendor Onboarding

| Setting                  | Current Value | Type         | Description                |
| ------------------------ | ------------- | ------------ | -------------------------- |
| **Auto-Approve Vendors** | false         | Boolean      | Skip approval process      |
| **Require KYC**          | true          | Boolean      | Mandatory KYC verification |
| **KYC Document Types**   | Hardcoded     | Multi-select | Accepted documents         |
| **Commission Rate (%)**  | Hardcoded     | Decimal      | Platform commission        |

### 13.2 Payout Settings

| Setting                   | Current Value | Type    | Description        |
| ------------------------- | ------------- | ------- | ------------------ |
| **Payout Frequency**      | "weekly"      | Select  | weekly/monthly     |
| **Minimum Payout Amount** | 100           | Decimal | Minimum for payout |
| **Payout Currency**       | "TND"         | Select  | Payout currency    |
| **Payout Delay (days)**   | 7             | Number  | Days after booking |

---

## 14. Search & Discovery

**Current State**: Basic implementation
**Priority**: Medium - User experience

### 14.1 Search Configuration

| Setting                        | Current Value | Type    | Description            |
| ------------------------------ | ------------- | ------- | ---------------------- |
| **Default Search Radius (km)** | 50            | Number  | Location search radius |
| **Max Search Results**         | 50            | Number  | Results per page       |
| **Enable Fuzzy Search**        | false         | Boolean | Typo-tolerant search   |
| **Search Boost Factors**       | N/A           | JSON    | Field weights          |

### 14.2 Filtering Options

| Setting                    | Current Value | Type         | Description          |
| -------------------------- | ------------- | ------------ | -------------------- |
| **Price Range Min**        | 0             | Decimal      | Minimum price filter |
| **Price Range Max**        | 10000         | Decimal      | Maximum price filter |
| **Default Sort Order**     | "relevance"   | Select       | Default sorting      |
| **Available Sort Options** | Hardcoded     | Multi-select | Sorting choices      |

---

## Implementation Plan

### Phase 1: Database Schema (Week 1)

```php
// apps/laravel-api/database/migrations/xxxx_create_platform_settings_table.php

Schema::create('platform_settings', function (Blueprint $table) {
    $table->id();

    // Identity
    $table->json('platform_name');           // Translatable
    $table->json('platform_tagline');        // Translatable
    $table->json('platform_description');    // Translatable
    $table->string('primary_domain');
    $table->string('api_url');
    $table->string('frontend_url');

    // SEO
    $table->json('default_meta_title');      // Translatable
    $table->json('default_meta_description'); // Translatable
    $table->json('keywords');

    // Contact
    $table->string('support_email');
    $table->string('phone_number')->nullable();
    $table->string('whatsapp_number')->nullable();
    $table->json('business_hours')->nullable();

    // Address
    $table->json('street_address')->nullable();
    $table->string('city')->nullable();
    $table->string('postal_code')->nullable();
    $table->string('country')->default('TN');

    // Social Media
    $table->string('facebook_url')->nullable();
    $table->string('instagram_url')->nullable();
    $table->string('twitter_url')->nullable();
    $table->string('linkedin_url')->nullable();

    // Currency
    $table->string('default_currency', 3)->default('TND');
    $table->json('enabled_currencies');

    // Analytics
    $table->string('ga4_id')->nullable();
    $table->string('gtm_id')->nullable();
    $table->string('facebook_pixel')->nullable();

    // Feature Flags
    $table->json('feature_flags');

    // Legal
    $table->string('terms_url')->nullable();
    $table->string('privacy_url')->nullable();
    $table->boolean('cookie_consent_enabled')->default(true);

    // Booking Settings
    $table->integer('hold_duration_minutes')->default(15);
    $table->decimal('platform_commission_percent', 5, 2)->default(10.00);

    $table->timestamps();
});
```

### Phase 2: Spatie Media Collections (Week 1)

```php
// Add to PlatformSettings model

public function registerMediaCollections(): void
{
    $this
        ->addMediaCollection('logo')
        ->singleFile()
        ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml']);

    $this
        ->addMediaCollection('logo_dark')
        ->singleFile()
        ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml']);

    $this
        ->addMediaCollection('favicon')
        ->singleFile()
        ->acceptsMimeTypes(['image/x-icon', 'image/png']);

    $this
        ->addMediaCollection('og_image')
        ->singleFile()
        ->acceptsMimeTypes(['image/jpeg', 'image/png'])
        ->registerMediaConversions(function (Media $media) {
            $this
                ->addMediaConversion('og')
                ->width(1200)
                ->height(630)
                ->format('jpg')
                ->quality(85);
        });
}
```

### Phase 3: Filament Resource (Week 2)

**Create**: `apps/laravel-api/app/Filament/Admin/Resources/PlatformSettingsResource.php`

**Features**:

- Singleton pattern (only one settings record)
- Tabbed interface with sections:
  1. **Identity** - Platform name, logos, URLs
  2. **SEO & Metadata** - Meta tags, schema.org
  3. **Contact & Address** - Contact info, physical address
  4. **Social Media** - All social profile URLs
  5. **Email Settings** - SMTP, templates, signatures
  6. **Payment & Commerce** - Currency, gateways, commissions
  7. **Booking Settings** - Holds, cancellation, policies
  8. **Localization** - Languages, formats, timezone
  9. **Feature Flags** - Enable/disable features
  10. **Analytics** - GA4, GTM, tracking pixels
  11. **Legal & Compliance** - Terms, privacy, GDPR

**Code Structure**:

```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            Tabs::make('Settings')
                ->tabs([
                    Tab::make('Identity')
                        ->schema([
                            Section::make('Basic Information')
                                ->schema([
                                    TextInput::make('platform_name')
                                        ->required()
                                        ->translatable(['en', 'fr', 'ar']),

                                    TextInput::make('platform_tagline')
                                        ->translatable(['en', 'fr', 'ar']),

                                    Textarea::make('platform_description')
                                        ->rows(3)
                                        ->translatable(['en', 'fr', 'ar']),
                                ]),

                            Section::make('Logos & Branding')
                                ->schema([
                                    SpatieMediaLibraryFileUpload::make('logo')
                                        ->collection('logo')
                                        ->image()
                                        ->maxSize(2048),

                                    SpatieMediaLibraryFileUpload::make('logo_dark')
                                        ->collection('logo_dark')
                                        ->image()
                                        ->maxSize(2048),

                                    SpatieMediaLibraryFileUpload::make('favicon')
                                        ->collection('favicon')
                                        ->maxSize(512),
                                ]),

                            Section::make('URLs')
                                ->schema([
                                    TextInput::make('primary_domain')
                                        ->required()
                                        ->url(),

                                    TextInput::make('api_url')
                                        ->required()
                                        ->url(),

                                    TextInput::make('frontend_url')
                                        ->required()
                                        ->url(),
                                ]),
                        ]),

                    Tab::make('SEO & Metadata')
                        ->schema([/* SEO fields */]),

                    Tab::make('Contact & Address')
                        ->schema([/* Contact fields */]),

                    Tab::make('Social Media')
                        ->schema([/* Social fields */]),

                    Tab::make('Email Settings')
                        ->schema([/* Email fields */]),

                    Tab::make('Payment & Commerce')
                        ->schema([/* Payment fields */]),

                    Tab::make('Booking Settings')
                        ->schema([/* Booking fields */]),

                    Tab::make('Localization')
                        ->schema([/* Language fields */]),

                    Tab::make('Feature Flags')
                        ->schema([
                            Section::make('Core Features')
                                ->schema([
                                    Toggle::make('feature_flags.enable_reviews')
                                        ->label('Enable Reviews'),
                                    Toggle::make('feature_flags.enable_wishlists')
                                        ->label('Enable Wishlists'),
                                    Toggle::make('feature_flags.enable_blog')
                                        ->label('Enable Blog'),
                                ]),
                        ]),

                    Tab::make('Analytics')
                        ->schema([/* Analytics fields */]),

                    Tab::make('Legal & Compliance')
                        ->schema([/* Legal fields */]),
                ])
                ->columnSpanFull(),
        ]);
}
```

### Phase 4: Service Provider & Helpers (Week 2)

**Create**: `apps/laravel-api/app/Services/PlatformSettingsService.php`

```php
<?php

namespace App\Services;

use App\Models\PlatformSettings;
use Illuminate\Support\Facades\Cache;

class PlatformSettingsService
{
    private const CACHE_KEY = 'platform_settings';
    private const CACHE_TTL = 3600; // 1 hour

    public function get(string $key = null, mixed $default = null): mixed
    {
        $settings = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return PlatformSettings::first();
        });

        if ($key === null) {
            return $settings;
        }

        return data_get($settings, $key, $default);
    }

    public function refresh(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // Convenience methods
    public function platformName(?string $locale = null): string
    {
        $name = $this->get('platform_name');
        return $locale ? ($name[$locale] ?? $name['en']) : $name['en'];
    }

    public function logoUrl(): ?string
    {
        $settings = $this->get();
        return $settings?->getFirstMediaUrl('logo');
    }

    public function organizationSchema(): array
    {
        $settings = $this->get();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'TravelAgency',
            'name' => $settings->platform_name['en'] ?? 'Platform',
            'url' => $settings->frontend_url,
            'logo' => $settings->getFirstMediaUrl('logo'),
            'sameAs' => array_filter([
                $settings->facebook_url,
                $settings->instagram_url,
                $settings->twitter_url,
                $settings->linkedin_url,
            ]),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $settings->street_address,
                'addressLocality' => $settings->city,
                'postalCode' => $settings->postal_code,
                'addressCountry' => $settings->country,
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => $settings->phone_number,
                'email' => $settings->support_email,
                'contactType' => 'customer service',
            ],
        ];
    }
}
```

**Register in AppServiceProvider**:

```php
// app/Providers/AppServiceProvider.php

public function register(): void
{
    $this->app->singleton(PlatformSettingsService::class);
}

public function boot(): void
{
    // Make available in views
    View::share('platformSettings', app(PlatformSettingsService::class));
}
```

**Helper Functions**:

```php
// app/helpers.php (autoload in composer.json)

if (!function_exists('platform_setting')) {
    function platform_setting(string $key = null, mixed $default = null): mixed
    {
        return app(\App\Services\PlatformSettingsService::class)->get($key, $default);
    }
}

if (!function_exists('platform_name')) {
    function platform_name(?string $locale = null): string
    {
        return app(\App\Services\PlatformSettingsService::class)->platformName($locale);
    }
}
```

### Phase 5: Frontend Integration (Week 3)

**Create API Endpoint**: `apps/laravel-api/app/Http/Controllers/Api/V1/PlatformSettingsController.php`

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PlatformSettingsService;

class PlatformSettingsController extends Controller
{
    public function __construct(
        private PlatformSettingsService $settingsService
    ) {}

    public function index()
    {
        return response()->json([
            'data' => [
                'platform_name' => $this->settingsService->get('platform_name'),
                'platform_tagline' => $this->settingsService->get('platform_tagline'),
                'logo_url' => $this->settingsService->logoUrl(),
                'social_media' => [
                    'facebook' => $this->settingsService->get('facebook_url'),
                    'instagram' => $this->settingsService->get('instagram_url'),
                    'twitter' => $this->settingsService->get('twitter_url'),
                ],
                'contact' => [
                    'email' => $this->settingsService->get('support_email'),
                    'phone' => $this->settingsService->get('phone_number'),
                ],
                'organization_schema' => $this->settingsService->organizationSchema(),
            ],
        ]);
    }

    public function publicSettings()
    {
        // Only return non-sensitive settings for frontend
        return response()->json([
            'data' => [
                'platform_name' => $this->settingsService->get('platform_name'),
                'logo_url' => $this->settingsService->logoUrl(),
                'default_currency' => $this->settingsService->get('default_currency'),
                'available_languages' => $this->settingsService->get('available_languages'),
                'ga4_id' => $this->settingsService->get('ga4_id'),
            ],
        ]);
    }
}
```

**Frontend Hook**: `apps/web/src/lib/api/settings.ts`

```typescript
import { useQuery } from '@tanstack/react-query';
import { fetchApi } from './client';

interface PlatformSettings {
  platformName: Record<string, string>;
  platformTagline: Record<string, string>;
  logoUrl: string | null;
  socialMedia: {
    facebook: string | null;
    instagram: string | null;
    twitter: string | null;
  };
  contact: {
    email: string;
    phone: string | null;
  };
  organizationSchema: any;
}

export async function getPlatformSettings(): Promise<PlatformSettings> {
  const response = await fetchApi<{ data: PlatformSettings }>('/platform-settings/public');
  return response.data;
}

export function usePlatformSettings() {
  return useQuery({
    queryKey: ['platform-settings'],
    queryFn: getPlatformSettings,
    staleTime: 1000 * 60 * 60, // 1 hour
  });
}
```

**Update JsonLd Component**: `apps/web/src/components/seo/JsonLd.tsx`

```typescript
import { usePlatformSettings } from '@/lib/api/settings';

export function OrganizationJsonLd() {
  const { data: settings } = usePlatformSettings();

  if (!settings) return null;

  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{
        __html: JSON.stringify(settings.organizationSchema),
      }}
    />
  );
}
```

---

## Migration Strategy

### Step 1: Create Settings Table & Model

1. Run migration to create `platform_settings` table
2. Create `PlatformSettings` model with Spatie Translatable
3. Add Spatie MediaLibrary support

### Step 2: Seed Default Values

```php
// database/seeders/PlatformSettingsSeeder.php

public function run(): void
{
    PlatformSettings::create([
        'platform_name' => [
            'en' => 'Go Adventure',
            'fr' => 'Go Adventure',
            'ar' => 'جو أدفنتشر',
        ],
        'platform_tagline' => [
            'en' => 'Your gateway to authentic Tunisian experiences',
            'fr' => 'Votre porte d\'entrée vers des expériences tunisiennes authentiques',
            'ar' => 'بوابتك إلى تجارب تونسية أصيلة',
        ],
        'primary_domain' => env('APP_URL', 'https://goadventure.com'),
        'api_url' => env('API_URL', 'http://localhost:8000'),
        'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),
        'support_email' => env('MAIL_FROM_ADDRESS', 'hello@goadventure.com'),
        'default_currency' => 'TND',
        'enabled_currencies' => ['TND', 'EUR', 'USD'],
        'hold_duration_minutes' => 15,
        'platform_commission_percent' => 10.00,
        'feature_flags' => [
            'enable_reviews' => true,
            'enable_wishlists' => false,
            'enable_blog' => true,
            'enable_agent_api' => true,
        ],
        'cookie_consent_enabled' => true,
    ]);
}
```

### Step 3: Replace Hardcoded Values Gradually

**Priority Order**:

1. **Week 1**: Identity (name, logos, URLs) - Most visible
2. **Week 2**: SEO & Social Media - SEO impact
3. **Week 3**: Contact & Email - Customer experience
4. **Week 4**: Payment & Commerce - Revenue critical
5. **Week 5**: Feature Flags & Analytics - Flexibility

**Replacement Pattern**:

```typescript
// Before (hardcoded)
const platformName = 'Go Adventure';

// After (from settings)
const { data: settings } = usePlatformSettings();
const platformName = settings?.platformName[locale] || 'Go Adventure';
```

### Step 4: Update Documentation

- Create admin user guide for settings panel
- Document each setting's purpose and impact
- Add tooltips in Filament for complex settings

---

## Security Considerations

### 1. Sensitive Data

- **Encrypt**: API keys, payment credentials, tracking IDs
- **Use Environment Variables**: For server-side secrets
- **Never Expose**: Secret keys to frontend API

### 2. Permission Control

- Only Super Admins can access PlatformSettings
- Add policy to restrict access
- Log all changes to settings (audit trail)

### 3. Cache Invalidation

- Clear cache when settings updated
- Add listener to PlatformSettings model:

```php
protected static function booted()
{
    static::saved(function () {
        app(PlatformSettingsService::class)->refresh();
    });
}
```

---

## Testing Checklist

### Unit Tests

- [ ] PlatformSettingsService returns correct values
- [ ] Cache invalidation works on update
- [ ] Translatable fields return correct locale
- [ ] Media uploads attach correctly

### Integration Tests

- [ ] API endpoint returns public settings only
- [ ] Frontend hook fetches and caches settings
- [ ] JsonLd renders with settings data
- [ ] Email templates use settings values

### Manual QA

- [ ] Filament resource saves all tabs correctly
- [ ] Logo upload and display works
- [ ] Changing platform name updates everywhere
- [ ] Social links appear in footer
- [ ] Organization schema validates at schema.org
- [ ] Multi-language content displays correctly

---

## Benefits Summary

### For Platform Owner

1. **White-Label Ready**: Rebrand without code changes
2. **No Developer Dependency**: Change settings anytime
3. **Consistent Branding**: Single source of truth
4. **Easy Compliance**: Update legal links instantly
5. **Quick Iterations**: Test different messaging/pricing

### For Developers

1. **Clean Code**: No hardcoded values scattered
2. **Easy Maintenance**: Change once, apply everywhere
3. **Environment Agnostic**: Same code, different brands
4. **Type Safety**: Settings typed and validated
5. **Performance**: Cached for fast access

### For End Users

1. **Consistent Experience**: Branding everywhere
2. **Accurate Information**: Contact info always current
3. **Personalized**: Language-specific content
4. **Trust**: Professional, cohesive platform

---

## Estimated Timeline

| Phase                        | Duration | Deliverables                         |
| ---------------------------- | -------- | ------------------------------------ |
| **Database & Models**        | 3 days   | Migration, model, media collections  |
| **Filament Resource**        | 5 days   | Full admin interface with all tabs   |
| **Backend Service**          | 2 days   | Service class, helpers, API endpoint |
| **Frontend Integration**     | 3 days   | Hook, update components              |
| **Replace Hardcoded Values** | 5 days   | Update all occurrences               |
| **Testing & QA**             | 3 days   | Unit tests, integration tests, QA    |
| **Documentation**            | 2 days   | Admin guide, developer docs          |

**Total**: ~23 working days (~1 month)

---

## Next Steps

1. **Review this Document**: Approve scope and priorities
2. **Create Database Schema**: Finalize field list
3. **Design Filament UI**: Sketch tab organization
4. **Begin Implementation**: Start with Phase 1
5. **Gradual Migration**: Replace hardcoded values incrementally

---

## Appendix: Current Hardcoded Values Inventory

### Files with Platform Name

```
✓ apps/web/src/components/seo/JsonLd.tsx (line 15)
✓ apps/laravel-api/.env (APP_NAME)
✓ Email templates (all)
✓ apps/web/src/app/[locale]/layout.tsx
```

### Files with URLs

```
✓ apps/web/src/components/seo/JsonLd.tsx (multiple lines)
✓ .env files (all projects)
✓ apps/web/next.config.ts
```

### Files with Social Media

```
✓ apps/web/src/components/seo/JsonLd.tsx (lines 21-24)
✓ Footer component (when created)
```

### Files with Email Settings

```
✓ apps/laravel-api/.env (MAIL_FROM_*)
✓ All Mail classes in app/Mail/
```

### Files with Currency

```
✓ packages/schemas/src/index.ts (pricingSchema)
✓ Price display components
✓ Checkout flow
```

### Files with Feature Flags

```
✗ None currently - all features always enabled
```

---

**Document Version**: 1.0
**Last Updated**: 2025-12-22
**Next Review**: After implementation Phase 1
**Owner**: Development Team
**Approver**: Product Owner
