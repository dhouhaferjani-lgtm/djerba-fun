# Production Readiness & SEO Audit

## Executive Summary

The codebase shows promising foundations (modular monorepo, shared schema package, typed APIs), but several critical gaps block a reliable launch. Database migrations currently fail, the API deviates dramatically from the TypeScript contract that the web app consumes, and key user flows (search → listing detail → checkout) stop at static or placeholder screens. SEO scaffolding exists (sitemap, robots, JsonLd helper), yet it is not wired to real data, and public assets referenced in metadata are missing. Until these issues are resolved, neither the marketplace experience nor organic acquisition will work in production.

## Critical Functional Blockers

- **Broken migrations** – multiple tables try to add UUID columns with `$table->uuid()` but no column name (e.g., `users`, `traveler_profiles`, `vendor_profiles`, `locations`, `media`, `listings`; see `apps/laravel-api/database/migrations/2025_12_13_195756_update_users_table_add_role_and_status.php:14-27`, `2025_12_13_195846_create_traveler_profiles_table.php:14-27`, etc.). Laravel will throw during `migrate`, so the API cannot boot with a fresh database.
- **API/Type contract mismatch** – the backend returns snake_case payloads with translation objects (`ListingResource` at `apps/laravel-api/app/Http/Resources/ListingResource.php:18-57`) while the web app relies on camelCase DTOs defined in `@go-adventure/schemas` (e.g., `ListingSummary` at `packages/schemas/src/index.ts:389-408`). As a result, the search grid, dashboard, checkout, and booking logic dereference fields such as `booking.startsAt`, `slot.currency`, or `listing.title` that the API never supplies (`apps/web/src/app/[locale]/dashboard/page.tsx:28-35`, `apps/web/src/components/booking/BookingReview.tsx:14-52`, `apps/web/src/components/molecules/ListingCard.tsx:17-43`).
- **Checkout flow is unfinished** – the checkout page never loads hold/listing data, the booking mutation sends camelCase fields (`holdId`, `travelers`) that the Laravel controller rejects (`apps/web/src/components/booking/BookingWizard.tsx:82-105` vs. `apps/laravel-api/app/Http/Requests/CreateBookingRequest.php:24-41`), payment methods (`mock|offline|click_to_pay`) don't exist on the API (`apps/web/src/components/booking/PaymentMethodSelector.tsx:6-59` vs. `apps/laravel-api/app/Enums/PaymentMethod.php:9-27`), and coupon UI is unreachable plus expects a `{ data: ... }` wrapper the API does not return (`apps/web/src/components/booking/CouponInput.tsx:101-125` vs. `apps/laravel-api/app/Http/Controllers/Api/V1/CouponController.php:19-32`). Booking creation also references non-existent hold fields (`apps/laravel-api/app/Services/BookingService.php:33-37` uses `$hold->availability_slot_id`, `price_per_unit`, `currency`).

## Backend & API Observations

- `BookingController@store` calls `$hold->hasExpired()` even though the model only exposes `isExpired()` (`apps/laravel-api/app/Http/Controllers/Api/V1/BookingController.php:62`), so booking creation will fatally error.
- Search/query parameters use snake_case on the server (`apps/laravel-api/app/Http/Controllers/Api/V1/ListingController.php:24-47`), whereas the client sends camelCase via `URLSearchParams` (`apps/web/src/lib/api/client.ts:123-150`), so filters never apply.
- Public/vendor endpoints that the UI consumes (`vendors/:id`, `/vendors/:id/listings`) are not defined anywhere in `routes/api.php`, yet `vendorsApi` calls them (`apps/web/src/lib/api/client.ts:247-259`, `apps/laravel-api/routes/api.php:20-74`).
- Availability/feed code references columns that do not exist (`available_quantity`, `total_quantity`, `price`) in `FeedGeneratorService` and `Agent` controllers (`apps/laravel-api/app/Services/FeedGeneratorService.php:110-140`, `apps/laravel-api/app/Http/Controllers/Api/Agent/AgentSearchController.php:84-100`, `AgentListingController.php:92-115`), so those endpoints will crash when executed.
- Registration/login expectations are misaligned: the API requires `role`, `first_name`, `last_name`, and `password_confirmation` (`apps/laravel-api/app/Http/Requests/RegisterRequest.php:24-48`), but the web form only collects display name, email, and password (`apps/web/src/app/[locale]/auth/register/page.tsx:18-112`).

## Frontend & UX Observations

- Several top-level pages are marked `'use client'` and call `use(params)` (e.g., listings, listing detail, auth pages; `apps/web/src/app/[locale]/listings/page.tsx:1-20`, `apps/web/src/app/[locale]/listings/[slug]/page.tsx:1-23`, `apps/web/src/app/[locale]/auth/login/page.tsx:1-24`). React’s experimental `use()` hook is not supported in client components, so these routes will throw during hydration.
- The search bar and filters are just visual: `SearchBar` no-ops unless `onSearch` is provided (`apps/web/src/components/molecules/SearchBar.tsx:14-37`), but `apps/web/src/app/[locale]/listings/page.tsx:13-44` never passes a handler, so submitting the form does nothing.
- Vendor profile, reviews, availability calendar, and hold creation components exist but are not wired to any data source. `useCreateHold`, `useAvailability`, `AvailabilityCalendar`, and `TimeSlotPicker` are unused, so there is no way to select dates or generate a hold before checkout.
- Blog/footer links route to pages that do not exist (`/blog`, `/about`, `/careers`, etc.) which will produce 404s (`apps/web/src/components/home/BlogSection.tsx:34-77`, `apps/web/src/components/organisms/Footer.tsx:37-110`). Likewise, the newsletter form only simulates a subscription via `setTimeout` and never calls an API (`apps/web/src/components/home/NewsletterSection.tsx:24-48`).
- The shared analytics helper is never imported, so no tracking or Core Web Vitals measurements are emitted (`apps/web/src/lib/analytics.ts` has zero references).

## SEO & Content Gaps

- Metadata references assets that are missing from `public/` (e.g., `/og-image.png`, `/icon-192.png`, `/icon-512.png` declared in `apps/web/src/app/[locale]/layout.tsx:44-68` and `apps/web/src/app/manifest.ts:14-49`). Browsers and crawlers will fetch 404s for favicons, PWA icons, and OG images.
- The canonical URL is hard-coded to `'/'` regardless of locale or path (`apps/web/src/app/[locale]/layout.tsx:80-87`), so every page reports the same canonical and risks duplicate content penalties.
- `sitemap.ts` only includes a handful of static routes and omits the dynamic inventory (`apps/web/src/app/sitemap.ts:8-74`). Robots/sitemap also list auth pages, which typically should be excluded.
- Error and 404 boundaries always assume English and link to `/en` (`apps/web/src/app/not-found.tsx:10-65`, `apps/web/src/app/error.tsx:10-63`, `apps/web/src/app/global-error.tsx:12-71`), so localized users and crawlers won’t get the correct language variants.
- The `JsonLd` helper lives in `apps/web/src/components/seo/JsonLd.tsx`, yet no page uses it, so search engines miss rich snippets for listings, events, or the organization.

## Operational & QA Concerns

- There are only stub tests on the API side (`apps/laravel-api/tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`) and no tests at all for the web app, leaving critical booking/payment logic unverified.
- PaymentGatewayManager registers only mock/offline gateways even though `config/payment.php` exposes Stripe (`apps/laravel-api/app/Providers/AppServiceProvider.php:44-58`), so real payments cannot be processed.
- Coupons, booking statuses, availability slots, and payments all have hard-coded assumptions about currencies/status enums that diverge between backend and shared schemas (`packages/schemas/src/index.ts:470-580`), making monitoring/troubleshooting extremely difficult once traffic arrives.

## Recommended Next Steps

1. Fix the schema/migration layer (explicit `uuid('uuid')` columns, align foreign keys) and re-run migrations; add integration tests to catch these regressions.
2. Decide on a single API contract (likely the `@go-adventure/schemas` shapes), then update Laravel resources/controllers to emit camelCase JSON with the expected fields, while updating request validators to accept the camelCase payloads the web app sends.
3. Finish the booking pipeline: implement hold retrieval on checkout, expose a real availability selector, map `PaymentMethod` enums, integrate at least one real gateway, and wire coupons end-to-end (request/response parity plus currency-aware messaging).
4. Add the missing vendor/public endpoints or remove those links until the API supports them; ensure all navigation targets exist.
5. Hook up the SEO surface area—generate per-page metadata (`generateMetadata`), integrate `JsonLd`, include real slugs in the sitemap, provide actual OG images/icons in `public/`, and localize error/canonical tags.
6. Instrument analytics/monitoring and add e2e tests (search, listing detail, booking) before go-live to prevent regressions and provide confidence in releases.\*\*\*
