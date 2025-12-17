# Design System Hardening & White‑Label Readiness Plan

**Status:** 🟡 In Progress (Updated: 2025-12-16)
**Overall Completion:** ~45% (Phases 1-3 partially complete, 4-5 not started)

⚠️ **IMPORTANT:** A separate Claude Code instance is actively working on the checkout/booking flow. See **Exclusion Zone** section below to avoid conflicts.

This document outlines the steps to clean up the Next.js frontend so we can safely customize colors, typography, and branding for new customers. Each phase is intentionally small, ends with a validation milestone, and should be merged independently to avoid destabilizing the site (last time a large "all‑in‑one" pass broke the app).

## Goals

1. **Single source of truth for theme tokens** – colors, typography, radii, spacing.
2. **All primitives routed through `@go-adventure/ui`** – no ad‑hoc inputs/buttons.
3. **Content decoupled from components** – copy/images pulled from data or i18n.
4. **Documented theming contract** – config object per customer driving the tokens.

Before touching anything, skim **docs/cms-implementation.md** and **docs/hybrid-homepage-setup.md** so you understand how the Statik/Filament CMS currently feeds the frontend (HOME page fetched via `getPageByCode`, blog posts served through `/api/v1/blog/posts`, etc.).

> **Note:** Initial implementation work has been completed. This document has been updated to reflect current state and remaining tasks. See **Appendix A** for detailed file-by-file tracking.

---

## 🚫 Exclusion Zone - DO NOT MODIFY

**Another Claude Code instance is working on checkout/booking.** The following files/directories are **OFF LIMITS** for this hardening work:

### Excluded Files

```
apps/web/src/components/booking/
├── BookingWizard.tsx           ← ACTIVE DEVELOPMENT
├── BookingPanel.tsx            ← ACTIVE DEVELOPMENT
├── BookingReview.tsx           ← ACTIVE DEVELOPMENT
├── BookingConfirmation.tsx     ← ACTIVE DEVELOPMENT
├── CheckoutAuth.tsx            ← ACTIVE DEVELOPMENT
├── TravelerInfoForm.tsx        ← ACTIVE DEVELOPMENT
├── MultiTravelerForm.tsx       ← ACTIVE DEVELOPMENT
├── ExtrasSelection.tsx         ← ACTIVE DEVELOPMENT
├── PaymentMethodSelector.tsx   ← ACTIVE DEVELOPMENT
├── CouponInput.tsx             ← ACTIVE DEVELOPMENT
└── PersonTypeSelector.tsx      ← ACTIVE DEVELOPMENT

apps/web/src/app/[locale]/checkout/
└── [holdId]/page.tsx           ← ACTIVE DEVELOPMENT
```

### What This Means

**For Phase 1 (Token Replacement):**

- ❌ **DO NOT** fix hard-coded colors in `apps/web/src/components/booking/*`
- ❌ **DO NOT** fix hard-coded colors in `apps/web/src/app/[locale]/checkout/*`
- ✅ **OK** to fix colors in other components

**For Phase 2 (Component Consolidation):**

- ❌ **DO NOT** refactor booking form inputs to use UI package
- ❌ **DO NOT** touch `CheckoutAuth.tsx` or any wizard steps
- ✅ **OK** to refactor newsletter forms, search forms, auth forms

**Coordination Points:**

- If you need to modify `@go-adventure/ui` components (Button, Input, Select), coordinate first
- Checkout instance may be using these same primitives
- Tag @checkout-team before making breaking changes to UI package

### When Exclusion Can Be Lifted

- After checkout work is merged to main
- Update this section with completion date: **[PENDING]**

---

## Phase 0 – Freeze & Baseline

**Status:** 🔴 Not Complete
**Completion:** 0%

- [ ] Create a tracking issue referencing this doc.
- [ ] Snapshot screenshots of key pages (home, listing detail, blog, auth) and attach to the issue.
- [ ] Run `pnpm --filter web lint && pnpm --filter web typecheck` and save logs.
- **Milestone:** Baseline recorded (screenshots + CI logs) so regressions are easy to spot.

> **Update:** Since initial work has progressed, take **new baseline screenshots** reflecting current state (Dec 2025) before proceeding with remaining phases. This will serve as the comparison point for Phase 5 verification.

## Phase 1 – Token Audit & Incremental Replacement

**Status:** 🟡 In Progress
**Completion:** ~40%

### ✅ Already Complete

- CSS variables defined in `apps/web/src/app/globals.css`:
  - `:root` with `--primary`, `--secondary`, `--accent`, neutrals, semantic colors
  - `@theme inline` mapping tokens to Tailwind classes
- Many components already use `bg-primary`, `text-secondary`, `bg-accent`
- UI package components consume tokens

### 🔴 Remaining Work

**~40 hard-coded hex values found** in the following files (see **Appendix A** for complete list):

1. **High Priority - Error Pages (inline styles):**
   - `apps/web/src/app/global-error.tsx` (11 instances)
   - `apps/web/src/app/not-found.tsx` (4 instances)
   - `apps/web/src/app/error.tsx` (1 instance)
   - `apps/web/src/app/loading.tsx` (2 instances)

2. **Medium Priority - Components:**
   - `apps/web/src/components/itinerary/ElevationProfile.tsx` (6 SVG colors)
   - `apps/web/src/components/home/PromoBannerSection.tsx` (`#f5f0d1`)
   - `apps/web/src/components/cms/blocks/CategoriesGridBlock.tsx` (`#fcfaf2`)
   - `apps/web/src/components/home/CategoriesGridSection.tsx` (`#fcfaf2`)

3. **Low Priority - Config Files:**
   - `apps/web/src/app/manifest.ts` (PWA theme colors - intentional?)

### Tasks

1. **Automated search:** ✅ Complete (see Appendix A)
2. **Triage:** ✅ Complete (categorized above)
3. **Replace in waves (max 5–8 files per PR):**
   - [ ] **Wave 1:** Error pages (global-error, not-found, error, loading) - convert inline styles to Tailwind utilities
   - [ ] **Wave 2:** Add missing cream token (`#fcfaf2`) and update CategoriesGrid components
   - [ ] **Wave 3:** ElevationProfile SVG colors - define chart-specific tokens
   - [ ] **Wave 4:** Verify manifest.ts colors (keep as intentional or tokenize)
4. **Special cases:** ✅ Identified - `global-error.tsx` uses inline styles (flagged in Wave 1)

- **Milestone:** `rg` returns only approved exceptions (manifest.ts if intentional). All component hex colors replaced with Tailwind tokens.

## Phase 2 – Consolidate Primitive Components

**Status:** 🟡 In Progress
**Completion:** ~50%

### ✅ Already Complete

**UI Package Components:** `packages/ui/src/index.ts` exports:

- ✅ `Button` (with variants)
- ✅ `Badge`
- ✅ `Input`
- ✅ `Select`
- ✅ `Heading`
- ✅ `Text`
- ✅ `Spinner`
- ✅ `Card` (with Header, Title, Description, Content, Footer)

All components consume tokens from Phase 1.

### 🔴 Remaining Work

**Duplicate Atoms Found:**

- `apps/web/src/components/atoms/InputWithIcon.tsx` ← needs refactor
- `apps/web/src/components/atoms/SelectWithIcon.tsx` ← needs refactor
- `apps/web/src/components/atoms/Logo.tsx` ← domain-specific (keep)
- `apps/web/src/components/atoms/NavLink.tsx` ← domain-specific (keep)

**Ad-hoc Form Inputs Found In:**

- Newsletter forms (footer, homepage)
- ~~Booking forms (checkout, wizard)~~ ← **EXCLUDED - See Exclusion Zone**
- Search forms (hero search)

### Tasks

1. **Inventory duplicated primitives:** ✅ Complete (see above)
2. **Extend `@go-adventure/ui`:**
   - [ ] Check if `cream` variant needed (doc mentions it, but may not be required)
   - [ ] Add icon support to `Input` and `Select` (or create `InputWithIcon` wrapper that composes from base)
   - [ ] Verify `outlineInverse` variant is needed
3. **Refactor consumers:**
   - [ ] Update `InputWithIcon.tsx` to **compose** from `@go-adventure/ui` Input (don't duplicate)
   - [ ] Update `SelectWithIcon.tsx` to **compose** from `@go-adventure/ui` Select
   - [ ] Replace ad-hoc newsletter inputs with UI components
   - [ ] ~~Replace ad-hoc booking form inputs with UI components~~ ← **EXCLUDED - See Exclusion Zone**
   - [ ] Keep `Logo` and `NavLink` in atoms (domain-specific)
   - [ ] **IMPORTANT:** Coordinate with checkout team before modifying Button/Input/Select in UI package
4. **Regression checks:** [ ] Screenshots of all forms + `pnpm --filter web lint`

- **Milestone:** `apps/web/src/components/atoms/` contains only `Logo.tsx` and `NavLink.tsx` (domain-specific); all non-checkout forms use UI components. Booking/checkout forms excluded from this phase.

## Phase 3 – Content & CMS Wiring

**Status:** 🟢 Mostly Complete
**Completion:** ~80%

### ✅ Already Complete

**CMS Integration:**

- ✅ Statik Flexible Content Blocks installed and configured
- ✅ Homepage uses `getPageByCode('HOME')` with fallback to hardcoded sections
- ✅ Blog system fully functional:
  - `/api/v1/blog/posts` (index)
  - `/api/v1/blog/posts/{slug}` (detail)
  - `/api/v1/blog/posts/featured`
  - `/api/v1/blog/posts/{slug}/related`
- ✅ Filament resources: `BlogPostResource`, `BlogCategoryResource`, `PageResource`
- ✅ `BlockRenderer` implemented (`apps/web/src/components/cms/BlockRenderer.tsx`)

**CMS Blocks Implemented:**

- ✅ `ToursListingBlock`
- ✅ `PromoBannerBlock`
- ✅ `CategoriesGridBlock`
- ✅ `CTAWithBlobsBlock`
- ✅ Standard blocks: `TextImageBlock`, `VideoBlock`, `QuoteBlock`, `ImageBlock`, `HtmlBlock`, `CardsBlock`, `CallToActionBlock`

**Data Modules:**

- ✅ `apps/web/src/data/blog-posts.ts` (fallback data)

**Translation Files:**

- ✅ `apps/web/messages/en.json`
- ✅ `apps/web/messages/fr.json`

### 🔴 Remaining Work

1. **Audit CMS usage:**
   - [x] Homepage CMS integration verified
   - [ ] **Verify ALL homepage sections** have CMS block equivalents (MarketingMosaic, DestinationsBento, etc.)
   - [x] Blog pages verified
2. **Define fallback data modules:**
   - [x] Created `apps/web/src/data/blog-posts.ts`
   - [ ] **Document which sections use fallbacks** and why
3. **Move copy into i18n:**
   - [ ] **Audit components for baked-in English text** (check FeaturedPackagesSection, HeroSection, etc.)
   - [ ] **Ensure CMS entries use translation keys** not hardcoded strings
4. **Validate CMS coverage:**
   - [x] Filament admin can create/edit pages
   - [x] Blog posts can be created via BlogPostResource
   - [ ] **Test creating HOME page** via Filament and verify all sections render
5. **Add smoke tests:**
   - [ ] **Create Playwright test** or integration test for `/api/v1/pages/code/HOME`
   - [ ] **Create test** for `/api/v1/blog/posts`

### Tasks

- [ ] Audit homepage sections to confirm CMS block coverage (see docs/hybrid-homepage-setup.md)
- [ ] Run i18n audit: `rg -l "\".*\"" apps/web/src/components` and identify hardcoded strings
- [ ] Document fallback data strategy in README
- [ ] Write integration tests for CMS endpoints

- **Milestone:** Updating homepage/blog content happens in the CMS (or translation files for hero text), with data modules only used as explicit fallbacks. All sections verified to render from Filament.

## Phase 4 – Theme Configuration Contract

**Status:** 🔴 Not Started
**Completion:** 0%

### Tasks

1. **Define `themeConfig` interface** inside `packages/ui/src/tokens/index.ts` describing:
   - [ ] primary/secondary/accent palettes
   - [ ] font families (sans/display)
   - [ ] optional radii/spacing overrides
   - [ ] optional spacing scale overrides
2. **Implement runtime loader:**
   - [ ] Create `configs/theme/` directory
   - [ ] Create `configs/theme/default.ts` (Go Adventure theme)
   - [ ] Create example `configs/theme/customer-example.ts`
   - [ ] Update `globals.css` (or generate CSS file) to read from active config at build time
   - [ ] Import config in root layout (`apps/web/src/app/layout.tsx`)
3. **Expose provider hook:**
   - [ ] Create `ThemeProvider` component
   - [ ] Inject CSS variables from config
   - [ ] Pass config to UI components via context
4. **Add documentation:**
   - [ ] Create `docs/theming-guide.md` with:
     - How to add a new customer theme
     - How to switch between themes (env var or config import)
     - List of available tokens and their usage
     - Example theme file with all options

- **Milestone:** Switching customers is a matter of changing a config import or env var; no component edits needed.

## Phase 5 – Verification & Rollout

**Status:** 🔴 Not Started
**Completion:** 0%

### Tasks

- [ ] Re-run lint/typecheck: `pnpm --filter web lint && pnpm --filter web typecheck`
- [ ] Run visual regression tests (if available) or manual screenshot comparison
- [ ] Generate updated screenshots of:
  - [ ] Homepage (all sections)
  - [ ] Listing detail page
  - [ ] Blog homepage
  - [ ] Blog post detail
  - [ ] Auth pages (login/register)
  - [ ] Error pages (404, 500)
  - [ ] Booking flow
- [ ] Compare screenshots against Phase 0 baseline
- [ ] Update documentation:
  - [ ] README with theming overview
  - [ ] `docs/theming-guide.md` (created in Phase 4)
  - [ ] Document known limitations
  - [ ] Add troubleshooting section
- [ ] Create customer onboarding checklist (theme customization steps)
- **Milestone:** Close the tracking issue with evidence (screenshots, passing commands, list of remaining TODOs if any).

## Guardrails

- Keep PRs small and scoped to one phase/sub-phase.
- After each milestone, tag Jim for review before starting the next phase.
- Avoid mixing refactors with design tweaks; cosmetic adjustments should wait until tokens/ui-kit cleanup lands.
- If any step causes regressions, roll back immediately and capture the failure in the issue before retrying.

This staged approach ensures we never repeat the previous "big bang" attempt. Each milestone gives us a stable checkpoint, and by the end we'll have a maintainable, customer-ready theming foundation.

---

## Appendix A: Hard-Coded Color Inventory

**Last Updated:** 2025-12-16
**Total Instances:** ~40

### Error Pages (High Priority - Inline Styles)

#### `apps/web/src/app/global-error.tsx` (11 instances)

```typescript
backgroundColor: '#f9fafb'          → bg-gray-50
color: '#111827'                    → text-gray-900
color: '#6b7280'                    → text-gray-500
color: '#9ca3af'                    → text-gray-400
backgroundColor: '#f3f4f6'          → bg-gray-100
backgroundColor: '#0D642E'          → bg-primary
color: '#0D642E'                    → text-primary
border: '1px solid #0D642E'        → border-primary
borderTop: '1px solid #e5e7eb'     → border-t-gray-200
color: '#6b7280'                    → text-gray-500
color: '#0D642E'                    → text-primary
```

**Action:** Convert all inline styles to Tailwind utility classes.

#### `apps/web/src/app/not-found.tsx` (4 instances)

```typescript
from-[#f5f0d1]                     → from-accent
text-[#0D642E]                     → text-primary
hover:bg-[#8BC34A]                 → hover:bg-secondary
```

**Action:** Replace with Tailwind token classes.

#### `apps/web/src/app/error.tsx` (1 instance)

```typescript
text-[#0D642E]                     → text-primary
```

#### `apps/web/src/app/loading.tsx` (2 instances)

```typescript
from-[#f5f0d1]                     → from-accent
border-[#0D642E]                   → border-primary
```

### Components (Medium Priority)

#### `apps/web/src/components/itinerary/ElevationProfile.tsx` (6 instances - SVG)

```typescript
stroke="#e5e7eb"                   → Define --chart-grid-line
fill="#6b7280"                     → Define --chart-text
stroke="#d1d5db"                   → Define --chart-axis
stroke="#9ca3af"                   → Define --chart-tick
fill="#6b7280"                     → Define --chart-label
fill="#374151"                     → Define --chart-value
```

**Action:** Add chart-specific CSS variables or use Tailwind gray scale programmatically.

#### `apps/web/src/components/home/PromoBannerSection.tsx` (1 instance)

```typescript
bg-[#f5f0d1]                       → bg-accent
```

#### `apps/web/src/components/cms/blocks/CategoriesGridBlock.tsx` (1 instance)

```typescript
bg-[#fcfaf2]                       → bg-cream (need to add token)
```

**Action:** Add `--color-cream: #fcfaf2` to globals.css.

#### `apps/web/src/components/home/CategoriesGridSection.tsx` (1 instance)

```typescript
bg-[#fcfaf2]                       → bg-cream
```

### Config Files (Low Priority - Intentional?)

#### `apps/web/src/app/manifest.ts` (2 instances)

```typescript
background_color: '#ffffff'        → Intentional (PWA requirement)
theme_color: '#0D642E'             → Could use CSS variable reference?
```

**Action:** Document as intentional or research if manifest.ts can consume CSS variables.

### CSS Variables (Definitions - Keep As-Is)

#### `apps/web/src/app/globals.css` (~30 instances)

All hex values in `:root` are **intentional** - these are the token definitions.
**Action:** None - these are the source of truth.

---

## Appendix B: Quick Reference

### Phase Checklist

- [ ] **Phase 0:** Create tracking issue + baseline screenshots
- [ ] **Phase 1:** Replace ~40 hard-coded hex values with tokens
- [ ] **Phase 2:** Refactor InputWithIcon/SelectWithIcon to compose from UI package
- [ ] **Phase 3:** Complete i18n audit and CMS coverage verification
- [ ] **Phase 4:** Implement theme configuration system
- [ ] **Phase 5:** Final verification and documentation

### Commands

```bash
# Find hard-coded colors
rg "#[0-9a-fA-F]{3,6}" apps/web/src --type-add 'web:*.{tsx,ts,css}' --type web

# Lint and typecheck
pnpm --filter web lint && pnpm --filter web typecheck

# Find duplicate primitives
ls apps/web/src/components/atoms/

# List CMS blocks
ls apps/web/src/components/cms/blocks/
```

### Files to Review

- Theme tokens: `apps/web/src/app/globals.css`
- UI exports: `packages/ui/src/index.ts`
- CMS blocks: `apps/web/src/components/cms/blocks/`
- Translation files: `apps/web/messages/{en,fr}.json`
- Atoms directory: `apps/web/src/components/atoms/`
