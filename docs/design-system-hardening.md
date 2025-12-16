# Design System Hardening & White‑Label Readiness Plan

This document outlines the steps to clean up the Next.js frontend so we can safely customize colors, typography, and branding for new customers. Each phase is intentionally small, ends with a validation milestone, and should be merged independently to avoid destabilizing the site (last time a large “all‑in‑one” pass broke the app).

## Goals

1. **Single source of truth for theme tokens** – colors, typography, radii, spacing.
2. **All primitives routed through `@go-adventure/ui`** – no ad‑hoc inputs/buttons.
3. **Content decoupled from components** – copy/images pulled from data or i18n.
4. **Documented theming contract** – config object per customer driving the tokens.

Before touching anything, skim **docs/cms-implementation.md** and **docs/hybrid-homepage-setup.md** so you understand how the Statik/Filament CMS currently feeds the frontend (HOME page fetched via `getPageByCode`, blog posts served through `/api/v1/blog/posts`, etc.).

## Phase 0 – Freeze & Baseline

- [ ] Create a tracking issue referencing this doc.
- [ ] Snapshot screenshots of key pages (home, listing detail, blog, auth) and attach to the issue.
- [ ] Run `pnpm --filter web lint && pnpm --filter web typecheck` and save logs.
- **Milestone:** Baseline recorded (screenshots + CI logs) so regressions are easy to spot.

## Phase 1 – Token Audit & Incremental Replacement

1. **Automated search:** use `rg -n "#[0-9a-fA-F]{3,6}" apps/web/src packages/ui/src` to list all literal hex values.
2. **Triage:** categorize matches into (a) should map to `@theme` color, (b) belongs in data/config, (c) intentional (e.g., map SDK requiring hex).
3. **Replace in waves (max 5–8 files per PR):**
   - Update Tailwind classes to `bg-primary`, `text-accent-light`, etc. so they resolve through `globals.css`.
   - When Tailwind lacks the token (e.g., `#fcfaf2`), add the variable inside `:root` and map it in `@theme inline` before replacing the usages.
4. **Special cases:** components like `apps/web/src/app/global-error.tsx` use inline styles; extract those into CSS modules or convert to Tailwind utilities first, then swap to tokens.

- **Milestone:** `rg` returns only approved exceptions (Leaflet map styles). Document remaining intentional hex codes in the issue.

## Phase 2 – Consolidate Primitive Components

1. **Inventory duplicated primitives:** `InputWithIcon`, `SelectWithIcon`, newsletter `<input>`, bespoke CTA buttons, etc.
2. **Extend `@go-adventure/ui`:**
   - Add missing variants (`cream`, `outlineInverse`, etc.) and prop hooks (e.g., `leftIcon`, `rightIcon`) directly inside the UI package.
   - Ensure these components consume the same tokens introduced in Phase 1.
3. **Refactor consumers:**
   - Replace ad‑hoc inputs/selects/buttons with the shared components.
   - Delete the duplicate atoms from `apps/web/src/components/atoms`.
4. **Regression checks:** Storybook (if available) or ad‑hoc screenshots plus `pnpm --filter web lint`.

- **Milestone:** `apps/web/src/components/atoms/` contains only domain‑specific atoms; all forms use UI components.

## Phase 3 – Content & CMS Wiring

1. **Audit CMS usage:**
   - Homepage already tries `getPageByCode('HOME')` → ensure every middle section has a CMS block equivalent (see `apps/web/src/components/cms/blocks`).
   - Blog pages already call `/api/v1/blog/posts`; confirm the block renderer handles cards/promo/tour listings coming from the Filament dashboard.
2. **Define fallback data modules** only for sections that _must_ stay hardcoded (e.g., Hero stats). Store them under `apps/web/src/data/` so they’re easy to swap or delete later.
3. **Move copy into i18n** (`apps/web/messages/*.json`) and keep CMS data purely structural — CMS entries should provide the string IDs or rich content, not baked-in English text.
4. **Validate CMS coverage:** create or update the HOME page via Filament (docs/hybrid-homepage-setup.md) and ensure `BlockRenderer` renders all required content; blog index/detail should display posts created from the Filament BlogPostResource.
5. **Add smoke tests:** lightweight integration test (or Playwright script) that hits `/api/v1/pages/code/HOME` and `/api/v1/blog/posts` to prevent silent regressions.

- **Milestone:** Updating homepage/blog content happens in the CMS (or translation files for hero text), with data modules only used as explicit fallbacks.

## Phase 4 – Theme Configuration Contract

1. **Define `themeConfig` interface** inside `packages/ui/src/tokens/index.ts` describing:
   - primary/secondary/accent palettes
   - font families (sans/display)
   - optional radii/spacing overrides
2. **Implement runtime loader:**
   - Create `configs/theme/<customer>.ts` that exports a `ThemeConfig`.
   - Update `globals.css` (or a generated CSS file) to read from the active config (can be done at build time by importing the config in the root layout).
3. **Expose provider hook:** e.g., `ThemeProvider` that injects CSS variables + passes config to UI components.
4. **Add doc describing how to add a new customer** (copy config file, update env var to select theme).

- **Milestone:** Switching customers is a matter of changing a config import or env var; no component edits needed.

## Phase 5 – Verification & Rollout

- [ ] Re-run lint/typecheck + targeted E2E or visual regression suite (if available).
- [ ] Generate updated screenshots and compare against Phase 0 baseline.
- [ ] Update README/docs with theming instructions and known limitations.
- **Milestone:** Close the tracking issue with evidence (screenshots, passing commands, list of remaining TODOs if any).

## Guardrails

- Keep PRs small and scoped to one phase/sub-phase.
- After each milestone, tag Jim for review before starting the next phase.
- Avoid mixing refactors with design tweaks; cosmetic adjustments should wait until tokens/ui-kit cleanup lands.
- If any step causes regressions, roll back immediately and capture the failure in the issue before retrying.

This staged approach ensures we never repeat the previous “big bang” attempt. Each milestone gives us a stable checkpoint, and by the end we’ll have a maintainable, customer-ready theming foundation.\*\*\*
