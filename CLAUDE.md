# Go Adventure Marketplace - Master Orchestrator Instructions

> **Model**: Claude Opus 4.5 (orchestrator) delegating to Claude Sonnet 4.5 (sub-agents)
> **Mode**: Autonomous overnight build with checkpoint validations
> **Created**: 2025-12-13

---

## 🎯 Mission

Build a complete, production-ready tourism marketplace from scratch. The system consists of:

- **Laravel 12 API + Filament 3 Admin** (backend)
- **Next.js 16 + React 19** (frontend)
- **Shared Zod schemas** (single source of truth)
- **Docker Compose** development environment

**MVP Scope**: Events and Tours/Activities (accommodations deferred to v1.1)

---

## 🏗️ Architecture Overview

```
go-adventure/
├── apps/
│   ├── laravel-api/        # Laravel 12 + Octane + Horizon + Filament
│   └── web/                # Next.js 16 App Router
├── packages/
│   ├── schemas/            # Zod schemas (SOURCE OF TRUTH)
│   ├── sdk/                # Generated TypeScript API client
│   └── ui/                 # Design system + shared components
├── docker/
│   ├── compose.dev.yml
│   └── services/
├── scripts/
│   └── bootstrap.sh
└── docs/
    └── specs/
```

---

## 🎨 Design System

### Brand Colors

```typescript
export const colors = {
  primary: {
    DEFAULT: '#0D642E', // Dark forest green
    light: '#8BC34A', // Light green / lime
  },
  secondary: {
    cream: '#f5f0d1', // Warm cream/beige
  },
  neutral: {
    white: '#ffffff',
    // Generate full scale: 50-950
  },
};
```

### Design Principles

- Modern, snappy, memorable UI
- Mobile-first responsive design
- Atomic design methodology (atoms → molecules → organisms → templates → pages)
- Leaflet/OpenStreetMap for all map components with custom styling to match brand
- Elevation profiles for trail-based activities
- Multilingual: French (fr) and English (en) from day one

---

## 📋 Sub-Agent Delegation

### Agent Boundaries

| Agent        | Scope                                             | Model      | Instruction File     |
| ------------ | ------------------------------------------------- | ---------- | -------------------- |
| **backend**  | Laravel API, Filament, migrations, policies, jobs | Sonnet 4.5 | `agents/BACKEND.md`  |
| **frontend** | Next.js, React components, pages, i18n            | Sonnet 4.5 | `agents/FRONTEND.md` |
| **devops**   | Docker, scripts, CI, environment                  | Sonnet 4.5 | `agents/DEVOPS.md`   |
| **schemas**  | Zod definitions, type generation, OpenAPI sync    | Sonnet 4.5 | `agents/SCHEMAS.md`  |

### Delegation Rules

1. **Never have two agents edit the same file simultaneously**
2. **Schema changes require orchestrator approval** before propagation
3. **Backend and frontend work in parallel** but sync at checkpoints
4. **All agents read from `packages/schemas`** - never define types locally

### Communication Protocol

```
ORCHESTRATOR → AGENT: Task assignment with context
AGENT → ORCHESTRATOR: Completion report or blocker
ORCHESTRATOR: Validates, resolves conflicts, advances phase
```

---

## 🔄 Execution Phases

### Phase 0: Foundation (Est. 30-45 min)

**Owner**: DevOps Agent → then all agents

- [ ] Initialize monorepo with pnpm workspaces
- [ ] Create Docker Compose with all services
- [ ] Bootstrap Laravel app with Octane + Horizon
- [ ] Bootstrap Next.js app with App Router
- [ ] Set up `packages/schemas` with base Zod definitions
- [ ] Configure shared TypeScript/ESLint/Prettier
- [ ] Verify all services start: `make up && make health`

**Checkpoint**: `curl localhost:8000/api/health` returns 200, Next.js renders at localhost:3000

---

### Phase 1: Identity & Catalog (Est. 2-3 hours)

**Owners**: Backend + Frontend in parallel

#### Backend Tasks

- [ ] User model + migrations (roles: traveler, vendor, admin, agent)
- [ ] Sanctum API token authentication
- [ ] Laravel policies for role-based access
- [ ] Listing model with polymorphic service types (Tour, Event)
- [ ] Location model with coordinates
- [ ] Media model with S3/MinIO storage
- [ ] Filament VendorPanel + AdminPanel scaffolding
- [ ] ListingResource, LocationResource for Filament

#### Frontend Tasks

- [ ] Design system setup in `packages/ui`
- [ ] Auth context + protected routes
- [ ] Home page with hero + search
- [ ] Listing search page with filters
- [ ] Listing detail page (placeholder for map/calendar)
- [ ] i18n setup with next-intl (en, fr)

#### Schema Tasks

- [ ] User, TravelerProfile, VendorProfile schemas
- [ ] Listing, Tour, Event schemas
- [ ] Location schema with GeoJSON support
- [ ] Media schema
- [ ] Generate TypeScript types + JSON Schema

**Checkpoint**: Can create vendor via Filament, list shows on frontend, auth flow works

---

### Phase 2: Availability & Maps (Est. 2-3 hours)

**Owners**: Backend + Frontend in parallel

#### Backend Tasks

- [ ] AvailabilityRule model (recurring patterns)
- [ ] AvailabilitySlot model (computed instances)
- [ ] BookingHold model with Redis TTL
- [ ] Availability calculation job (Horizon)
- [ ] API endpoints: GET /listings/{id}/availability, POST /listings/{id}/holds
- [ ] Filament AvailabilityResource with calendar widget

#### Frontend Tasks

- [ ] Leaflet map component with custom tiles/markers
- [ ] Map marker popups with location info
- [ ] Itinerary/stops display component
- [ ] Elevation profile component (for trails)
- [ ] Availability calendar component
- [ ] Hold creation flow

#### Schema Tasks

- [ ] AvailabilityRule, AvailabilitySlot schemas
- [ ] BookingHold schema
- [ ] MapMarker, Itinerary, ElevationPoint schemas

**Checkpoint**: Vendor can set availability in Filament, traveler sees calendar + map on frontend, holds persist in Redis

---

### Phase 3: Booking & Payments (Est. 3-4 hours)

**Owners**: Backend + Frontend in parallel

#### Backend Tasks

- [ ] Booking model with status enum
- [ ] PaymentIntent model
- [ ] PaymentGateway interface (abstraction layer)
- [ ] MockPaymentGateway driver (for testing)
- [ ] OfflinePaymentGateway driver
- [ ] Payment webhook handling
- [ ] Booking confirmation emails (queued)
- [ ] Filament BookingResource

#### Frontend Tasks

- [ ] Booking flow wizard (travelers info → review → payment)
- [ ] Payment method selection
- [ ] Booking confirmation page
- [ ] Traveler dashboard (my bookings)
- [ ] Booking detail page with status

#### Schema Tasks

- [ ] Booking schema with all statuses
- [ ] PaymentIntent schema
- [ ] TravelerInfo schema
- [ ] BookingExtra schema

**Checkpoint**: Complete booking flow works with mock payment, emails sent, booking visible in Filament

---

### Phase 4: Vendor & Admin Features (Est. 2-3 hours)

**Owners**: Backend + Frontend (backend-heavy)

#### Backend Tasks

- [ ] Vendor onboarding flow in Filament
- [ ] KYC status tracking
- [ ] Payout model + PayoutResource
- [ ] Review model + ReviewReply
- [ ] ReviewResource in Filament
- [ ] Coupon model + validation logic
- [ ] Vendor analytics widgets (bookings, revenue)
- [ ] Admin fraud console widget

#### Frontend Tasks

- [ ] Vendor public profile page
- [ ] Review display on listings
- [ ] Review submission (post-booking)
- [ ] Coupon input in checkout

#### Schema Tasks

- [ ] Review, ReviewReply schemas
- [ ] Payout schema
- [ ] Coupon schema
- [ ] VendorAnalytics schema

**Checkpoint**: Vendor dashboard functional, reviews display, coupons work in checkout

---

### Phase 5: Agentic APIs & Polish (Est. 2-3 hours)

**Owners**: Backend + Frontend + DevOps

#### Backend Tasks

- [ ] Agent OAuth client credentials flow
- [ ] Agent-specific controllers under Api/Agent namespace
- [ ] Rate limiting for agent endpoints
- [ ] Audit logging for agent actions
- [ ] OpenAPI spec generation (`artisan openapi:generate`)
- [ ] Product feeds (JSON, CSV)

#### Frontend Tasks

- [ ] SEO metadata + JSON-LD schema markup
- [ ] OG images generation
- [ ] Performance optimization (images, fonts, code splitting)
- [ ] Error boundaries + fallback UI
- [ ] 404/500 pages

#### DevOps Tasks

- [ ] Production Docker configs
- [ ] CI pipeline (lint → test → build)
- [ ] Health check endpoints
- [ ] Log aggregation setup
- [ ] Backup scripts

**Checkpoint**: Lighthouse score > 90, agent endpoints authenticated, feeds validate, CI green

---

## 🛡️ Quality Gates

### Code Quality Stack

| Tool             | Scope                    | Config File                     |
| ---------------- | ------------------------ | ------------------------------- |
| **Husky**        | Git hooks                | `.husky/`                       |
| **lint-staged**  | Pre-commit               | `package.json`                  |
| **commitlint**   | Commit messages          | `commitlint.config.js`          |
| **Prettier**     | JS/TS/JSON/MD formatting | `.prettierrc`                   |
| **ESLint**       | JS/TS linting            | `apps/web/.eslintrc.cjs`        |
| **Laravel Pint** | PHP formatting           | `apps/laravel-api/pint.json`    |
| **PHPStan**      | PHP static analysis      | `apps/laravel-api/phpstan.neon` |
| **TypeScript**   | Type checking            | `tsconfig.json` files           |

### Git Hooks (Husky)

```bash
# Pre-commit: lint-staged runs on staged files only
pnpm exec lint-staged

# Pre-push: full type check
pnpm typecheck

# Commit-msg: enforce conventional commits
pnpm exec commitlint --edit $1
```

### Commit Message Format

```
<type>(<scope>): <subject>

Types: feat, fix, docs, style, refactor, perf, test, chore, ci, build
Scopes: api, web, ui, schemas, sdk, docker, deps, release

Examples:
feat(api): add booking cancellation endpoint
fix(web): resolve map marker z-index issue
chore(deps): update Laravel to 12.1
```

### Before Each Phase Completion

```bash
# Backend checks
cd apps/laravel-api
./vendor/bin/pint --test          # Check formatting
./vendor/bin/phpstan analyse      # Static analysis (level 7)
php artisan test                  # Run tests

# Frontend checks
cd apps/web
pnpm lint                         # ESLint
pnpm typecheck                    # TypeScript
pnpm test                         # Vitest

# Full quality check
cd ../..
pnpm format:check                 # Prettier
make test-e2e                     # End-to-end
```

### Schema Sync Validation

```bash
# Ensure no drift between Zod schemas and Laravel
pnpm --filter schemas run validate
pnpm --filter schemas run generate
# Check that generated types match Laravel API responses
```

---

## 🚨 Error Recovery

### If a sub-agent gets stuck:

1. Log the blocker with full context
2. Attempt alternative approach
3. If still blocked, mark task as BLOCKED and continue with independent tasks
4. Orchestrator reviews blockers at next checkpoint

### If tests fail:

1. Identify failing test(s)
2. Check if schema drift caused the failure
3. Fix at source (usually schemas package)
4. Re-run affected agent tasks

### If Docker services fail:

1. `make down && make clean && make up`
2. Check logs: `docker compose logs [service]`
3. Verify port availability
4. Check .env configuration

---

## 🔀 Git Workflow

### Branching

```
main ← protected, deployable
  └── develop ← integration
        └── phase/0-foundation
        └── phase/1-identity-catalog
        └── phase/2-availability-maps
        └── phase/3-booking-payments
        └── phase/4-vendor-admin
        └── phase/5-agent-apis-polish
```

### Commit Frequency

**Commit after each atomic unit of work:**

- Migration + model created → commit
- API endpoint working → commit
- React component complete → commit
- Filament resource done → commit
- Test file added → commit

**Never commit:** broken tests, syntax errors, half-written code

### Commit Format

```bash
feat(api): add User model with role enum
feat(web): add ListingCard molecule
fix(api): resolve booking race condition
test(web): add MapView unit tests
chore(docker): update PHP to 8.5
```

### Phase Completion Protocol

```bash
# 1. All tests pass
make test

# 2. Create tag
git tag -a "phase-X-complete" -m "Phase X complete"

# 3. Merge to develop
git checkout develop
git merge phase/X-name --no-ff
git push origin develop --tags
```

### Rollback Safety

- **Phase tags** = safe rollback points
- **E2E tests** = lock in behavior (must keep passing)
- **Pre-push hook** = validates before push
- **Checkpoints** = documented working states

See `configs/git-workflow.md` for full details.

---

## 📁 Key Files Reference

| File                                   | Purpose                        |
| -------------------------------------- | ------------------------------ |
| `packages/schemas/src/index.ts`        | All Zod schema exports         |
| `packages/schemas/src/listings.ts`     | Listing, Tour, Event schemas   |
| `packages/schemas/src/bookings.ts`     | Booking, Hold, Payment schemas |
| `packages/schemas/src/users.ts`        | User, Profile schemas          |
| `packages/schemas/src/maps.ts`         | Map, Marker, Elevation schemas |
| `apps/laravel-api/routes/api.php`      | API route definitions          |
| `apps/laravel-api/app/Http/Resources/` | JSON resource classes          |
| `apps/web/src/lib/api/`                | SDK usage and API calls        |
| `apps/web/src/components/`             | React components               |
| `apps/web/messages/`                   | i18n translation files         |

---

## 🔐 Environment Variables

### Laravel (.env)

```
APP_ENV=local
APP_KEY=base64:...
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_DATABASE=go_adventure
REDIS_HOST=redis
QUEUE_CONNECTION=redis
FILESYSTEM_DISK=minio
```

### Next.js (.env.local)

```
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
NEXT_PUBLIC_DEFAULT_LOCALE=en
```

---

## 🎬 Startup Command

```bash
# Initial bootstrap (run once)
./scripts/bootstrap.sh

# Development
make up
make logs

# Testing
make test

# Full rebuild
make clean && make build && make up
```

---

## 📝 Notes for Orchestrator

1. **Parallelization**: Backend and Frontend agents can work simultaneously after Phase 0
2. **Schema-first**: Any new entity must be defined in `packages/schemas` FIRST
3. **No shortcuts**: Always use FormRequests, Resources, Policies on backend
4. **Design skill**: Frontend agent should leverage the installed design skill for UI
5. **Atomic commits**: Each completed task should be a logical commit
6. **French translations**: Can be machine-translated initially, marked for human review

---

## ✅ Success Criteria

The build is successful when:

- [ ] All Docker services healthy
- [ ] User can browse listings on frontend
- [ ] User can complete a booking with mock payment
- [ ] Vendor can manage listings in Filament
- [ ] Admin can view all data in Filament
- [ ] Site works in both English and French
- [ ] Maps display with custom markers
- [ ] All tests passing
- [ ] No TypeScript errors
- [ ] No PHPStan errors at level 7
