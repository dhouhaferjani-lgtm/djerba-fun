# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Evasion Djerba is a tourism marketplace for Djerba island (Tunisia) built as a pnpm monorepo. Service types: Tours, Nautical Activities, Accommodation, Events. Languages: French (default), English. Domain: evasiondjerba.com

- **`apps/laravel-api/`** - Laravel 12 API with Filament 3 admin panels (Admin + Vendor), Sanctum auth, Horizon queues, FrankenPHP/Octane runtime
- **`apps/web/`** - Next.js 16 App Router frontend with React 19, next-intl i18n, Tailwind CSS 4, Leaflet maps
- **`packages/schemas/`** - Zod schemas (source of truth for types shared between frontend and backend)
- **`packages/ui/`** - Shared design system components (Button, Input, Card, Badge) using class-variance-authority (cva)

## Common Commands

### Docker Development (primary workflow)

```bash
make up              # Start all services (API :8000, Web :3000, Mailpit :8025, MinIO :9001)
make down            # Stop services
make build           # Build Docker containers
make logs            # All logs; make logs-api / make logs-web / make logs-queue for specific
make fresh           # Reset DB: migrate:fresh --seed
make migrate         # Run migrations
make seed            # Seed database
make shell           # Shell into API container
make shell-web       # Shell into web container
make artisan <cmd>   # Run artisan commands in container
make composer <cmd>  # Run composer commands in container
make health          # Check service health (API + Frontend)
make lint            # Run Pint + PHPStan + ESLint (in containers)
make format          # Run Pint + Prettier (in containers)
make test            # Run all tests (API + Web)
make test-api        # API tests only
make test-web        # Web tests only
make test-e2e        # Playwright E2E tests
make openapi         # Regenerate OpenAPI docs + schemas
make clean           # Full cleanup (removes volumes)
```

### Backend (Laravel)

```bash
cd apps/laravel-api
php artisan test                    # Run all tests
php artisan test --filter=BookingTest  # Single test
./vendor/bin/pint                   # Fix PHP formatting
./vendor/bin/pint --test            # Check PHP formatting
./vendor/bin/phpstan analyse        # Static analysis
php artisan migrate                 # Run migrations
php artisan config:clear && php artisan config:cache  # After config changes
```

### Frontend (Next.js)

```bash
cd apps/web
pnpm dev          # Dev server
pnpm build        # Production build
pnpm lint         # ESLint
pnpm typecheck    # TypeScript check (tsc --noEmit)

# E2E tests (requires dev server + API running)
pnpm exec playwright test                    # All E2E tests
pnpm exec playwright test -g "test name"     # Single test by name
pnpm exec playwright test --headed           # With visible browser
pnpm exec playwright test --debug            # Debug mode
```

### Monorepo Root

```bash
pnpm build          # Build all packages
pnpm typecheck      # Type check all packages
pnpm format:check   # Prettier check
pnpm format         # Prettier fix
pnpm i18n:check     # Check translation completeness
```

### Schemas Package

```bash
cd packages/schemas
pnpm build    # Compile Zod schemas to dist/
pnpm dev      # Watch mode
```

## Architecture Details

### Frontend API Layer

- **Client-side API**: `apps/web/src/lib/api/client.ts` - `fetchApi<T>()` wrapper with auto auth token (localStorage), locale from `<html lang>`, guest session_id
- **Server-side API**: `apps/web/src/lib/api/server.ts` - for server components and `generateMetadata`
- **React Query hooks**: `apps/web/src/lib/api/hooks.ts` - all data fetching uses TanStack Query (useQuery/useMutation)
- API base URL: `NEXT_PUBLIC_API_URL` env var (default `http://localhost:8000/api/v1`)
- Types imported from `@go-adventure/schemas` - never define API types locally
- Utility: `cn()` from `apps/web/src/lib/utils/cn.ts` for Tailwind class merging

### Frontend Routing (next-intl)

- Locales: `fr` (default, no URL prefix), `en` (at `/en/...`)
- Locale prefix mode: `as-needed` - French routes have no prefix
- Locale detection disabled - users switch via language switcher only
- All pages under `apps/web/src/app/[locale]/`
- Translation files: `apps/web/messages/{fr,en}.json` - must stay in sync (`pnpm i18n:check`)
- i18n config: `apps/web/src/i18n/routing.ts`, `request.ts`, `navigation.ts`
- Navigation helpers: Import `Link`, `redirect`, `usePathname`, `useRouter` from `@/i18n/navigation` (not from `next/link` or `next/navigation`)
- Middleware redirects legacy `/ar/*` URLs to French equivalent (301)

### Backend Structure

- **Filament Admin**: `app/Filament/Admin/Resources/` - full admin panel
- **Filament Vendor**: `app/Filament/Vendor/Resources/` - vendor self-service
- **API Controllers**: `app/Http/Controllers/Api/V1/` - thin controllers delegating to Actions/Services
- **Partner API**: `app/Http/Controllers/Api/Partner/` - B2B partner endpoints (X-Partner-Key auth)
- **Actions**: `app/Domain/*/Actions/` - single-purpose business logic classes (prefer over fat services)
- **Services**: `app/Services/` - cross-cutting business logic (BookingService, CartService, CouponService, PriceCalculationService, etc.)
- **FormRequests**: `app/Http/Requests/` - input validation (always use, never validate in controllers)
- **Resources**: `app/Http/Resources/` - JSON serialization (never return Eloquent models directly)
- **Enums**: `app/Enums/` - PHP enums (BookingStatus, PaymentStatus, ListingStatus, UserRole, etc.)
- **Policies**: `app/Policies/` - authorization logic (always use, register in AuthServiceProvider)
- Routes: `routes/api.php` - all API routes with auth/rate-limiting middleware
- Translatable models use Spatie `HasTranslations` trait for multilingual fields

### Auth Flow

- Sanctum token-based auth for API
- Supports: email/password, magic links, OAuth (social), guest checkout with session_id
- Rate limiting on auth endpoints (e.g., 5 login attempts per 15 min)

### Key Domain Models

Listing (polymorphic via ServiceType: tour, event, nautical, accommodation) -> AvailabilityRule -> AvailabilitySlot -> BookingHold -> Booking -> PaymentIntent -> BookingParticipant (voucher codes)

Additional: Cart -> CartItem -> CartPayment, Coupon, Review, Partner, BlogPost, CustomTripRequest

### Pricing System

Dual-currency pricing (TND + EUR) with geo-based display:

- Listings store both `tnd_price` and `eur_price`
- API detects user location and returns `displayPrice` + `displayCurrency`
- Frontend stores detected currency in cookie for SSR consistency

### Guest Session Pattern

Guest users (not logged in) are tracked via `session_id` (UUID stored in localStorage). This ID is sent as `X-Session-ID` header or in request body. After login, guest carts/bookings are merged to the authenticated user via `cartApi.mergeCart()` and `bookingsApi.link()`.

### Docker Services

PostgreSQL (:15432), Redis (:16379), MinIO (:9002/:9003), MeiliSearch (:7701), Mailpit (:1025/:8025)

### Deployment

Multiple Docker Compose files exist for different environments:

- `docker/compose.dev.yml` - local development (used by Makefile)
- `docker-compose.staging.yml`, `docker-compose.prod.yml`, `docker-compose.dokploy.yml` - deployment configs

## Critical Rules

### Never mix .js and .ts config files

This project is 100% TypeScript. Next.js prefers `.js` over `.ts` - if both exist, `.js` wins and breaks next-intl. Only use `apps/web/next.config.ts`, never create `next.config.js`.

### next.config.ts must wrap with next-intl plugin

```typescript
export default withBundleAnalyzer(withNextIntl(nextConfig));
```

Removing the `withNextIntl` wrapper breaks all i18n routing.

### CORS after config changes

CORS config at `apps/laravel-api/config/cors.php` must allow ports 3000 and 3001. After editing, always run:

```bash
php artisan config:clear && php artisan config:cache
```

### Error boundaries: different rules for error.tsx vs global-error.tsx

- `error.tsx` files return plain JSX without `<html>` or `<body>` - the root layout provides the HTML shell
- `global-error.tsx` MUST include `<html>` and `<body>` tags since it replaces the entire document when the root layout fails

### Schema-first development

New entities must be defined in `packages/schemas/` first, then implemented in Laravel and consumed by Next.js.

### Translation completeness

Both `fr.json` and `en.json` must have matching keys. Run `pnpm i18n:check` to verify. Never hardcode user-facing strings - always use `useTranslations()` (client) or `getTranslations()` (server).

### Always use i18n-aware navigation

Import `Link`, `redirect`, `usePathname`, `useRouter` from `@/i18n/navigation` instead of `next/link` or `next/navigation`. This ensures locale is preserved in all route changes.

### API parameter naming

Frontend uses camelCase, backend uses snake_case. The API client (`client.ts`) handles conversion for common params. When adding new endpoints, map params explicitly:

```typescript
const paramMapping = { serviceType: 'service_type', activityType: 'activity_type' };
```

### Images and media

- Hero/gallery images stored in MinIO (S3-compatible)
- URLs returned from API are fully qualified (include domain)
- Use `galleryImages` array for Filament-uploaded images, `media` array for structured media with categories

## Code Quality Enforcement

Pre-commit (via Husky + lint-staged):

- `*.{ts,tsx,js,jsx,json,md,yml,yaml}` -> Prettier format

PHP formatting: Run `./vendor/bin/pint` manually before committing PHP changes (or use `make format` in Docker).

Commit messages: commitlint enforces conventional commits:

```
<type>(<scope>): <subject>
Types: feat, fix, docs, style, refactor, perf, test, chore, ci, build, revert
Scopes: api, web, ui, schemas, sdk, docker, deps, release, ci, docs
```

## Brand Colors (Mediterranean Palette)

- Primary: `#0077B6` (Ocean Blue), light: `#0096C7`, dark: `#023E8A`
- Secondary: `#F4A261` (Sandy Orange), dark: `#E76F51`
- Accent: `#E9F5F8` (Seafoam)
- Fonts: Inter (body), Poppins (display/headings)

## Translation Management

CSV-based translations for easy client editing:

```bash
cd apps/web
pnpm i18n:export   # Export JSON to translations.csv
pnpm i18n:import   # Import CSV back to JSON files
```

Edit `apps/web/translations.csv` in Excel or Google Sheets, then import.

## Testing Patterns

### Running Single Tests

```bash
# Laravel - specific test class
php artisan test --filter=BookingTest

# Laravel - specific test method
php artisan test --filter=BookingTest::test_can_create_booking

# Playwright E2E - specific test by name
pnpm exec playwright test -g "booking flow"

# Playwright - specific file
pnpm exec playwright test tests/booking.spec.ts
```

## API Patterns

### Standard response formats

```typescript
// Single resource: { data: Resource }
// Collection: { data: Resource[], meta: { total, page, limit } }
// Mutation success: { data: Resource, message: string }
// Error: { message: string, errors?: { field: string[] } }
```

### Auth headers

- Authenticated: `Authorization: Bearer <token>`
- Guest session: `X-Session-ID: <uuid>` (stored in localStorage as `guest_session_id`)
- Locale: `Accept-Language: fr|en`

## What's Not Yet Done

- Comprehensive unit/integration tests (some exist, more needed)
- PHPStan configuration
- CI/CD pipeline
- Real payment gateway (Stripe) - currently using MockPaymentGateway
