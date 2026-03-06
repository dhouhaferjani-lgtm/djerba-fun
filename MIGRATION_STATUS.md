# Djerba Fun Migration Status

**Last Updated:** 2026-03-06
**Migration:** Go Adventure → Djerba Fun (previously Evasion Djerba)

## Migration Summary

| Phase   | Status      | Description                                                               |
| ------- | ----------- | ------------------------------------------------------------------------- |
| Phase 1 | ✅ Complete | ServiceType enum migration (added NAUTICAL, renamed SEJOUR→ACCOMMODATION) |
| Phase 2 | ✅ Complete | Djerba Fun color palette (Navy, Emerald, Gold, Orange)                    |
| Phase 3 | ✅ Complete | CSV translation system (`pnpm i18n:export/import`)                        |
| Phase 4 | ✅ Complete | Brand identity updates (seeders, metadata, logo component)                |
| Phase 5 | ✅ Complete | Backend config updates (app.php, cors.php, payment.php, filesystems.php)  |
| Phase 6 | ✅ Complete | Email translation files (lang/en/mail.php, lang/fr/mail.php)              |
| Phase 7 | ✅ Complete | Package scope rename (@go-adventure → @djerba-fun)                        |
| Phase 8 | ✅ Complete | Brand name migration (Go Adventure/Evasion Djerba → Djerba Fun)           |
| Phase 9 | ✅ Complete | CLAUDE.md updated for Djerba Fun                                          |

## Brand Information

| Property      | Value         |
| ------------- | ------------- |
| Brand Name    | Djerba Fun    |
| Domain        | djerbafun.com |
| Email Domain  | @djerba.fun   |
| Package Scope | @djerba-fun   |

## Brand Colors (Djerba Fun Palette)

| Color   | Base    | Light   | Dark    | Usage              |
| ------- | ------- | ------- | ------- | ------------------ |
| Navy    | #1B2A4E | #3a5a8c | #0d1426 | Primary, structure |
| Emerald | #2E9E6B | #4ade9a | #25855a | Secondary, nature  |
| Gold    | #F5B041 | #fde68a | #ca8a04 | Accent, warmth     |
| Orange  | #E05D26 | #f97316 | #c2410c | Highlight, energy  |

## What Was Done

### 1. ServiceType Enum (Phase 1)

- **File:** `/apps/laravel-api/app/Enums/ServiceType.php`
- Added `NAUTICAL` case
- Renamed `SEJOUR` → `ACCOMMODATION`
- Updated Listing model with new scopes and helpers
- Updated Admin/Vendor Filament resources
- Updated frontend components (ListingCard, Header, etc.)
- Created database migration for existing records

### 2. Color Palette (Phase 2)

- **File:** `/packages/ui/src/tokens/colors.ts`
- Navy: `#1B2A4E` (Primary)
- Emerald: `#2E9E6B` (Secondary)
- Gold: `#F5B041` (Accent)
- Orange: `#E05D26` (Highlight)
- Updated Filament AdminPanelProvider and VendorPanelProvider
- BDD tests: `/packages/ui/src/tokens/__tests__/colors.test.ts`

### 3. CSV Translation System (Phase 3)

- **Files created:**
  - `/apps/web/scripts/translation-utils.ts`
  - `/apps/web/scripts/export-translations-csv.ts`
  - `/apps/web/scripts/import-translations-csv.ts`
  - `/apps/web/scripts/translation-utils.test.ts` (30 passing tests)
- **Commands:**
  - `pnpm i18n:export` - Export JSON to CSV
  - `pnpm i18n:import` - Import CSV back to JSON

### 4. Brand Identity Updates (Phase 4)

- `PlatformSettingsSeeder.php` - Djerba Fun identity
- `ActivityTypeSeeder.php` - Djerba-specific activities
- `/apps/web/src/app/[locale]/layout.tsx` - Updated metadata
- `/apps/web/src/app/manifest.ts` - PWA manifest
- `/apps/web/src/components/atoms/Logo.tsx` - Fallback brand name

### 5. Backend Configs (Phase 5)

- `config/app.php` - frontend_url → djerbafun.com
- `config/cors.php` - CORS pattern for djerbafun.com
- `config/payment.php` - booking prefix
- `config/filesystems.php` - bucket name
- `.env.example` - All Djerba Fun branding

### 6. Email Templates (Phase 6)

- `lang/en/mail.php` - All Go Adventure/Evasion Djerba → Djerba Fun
- `lang/fr/mail.php` - All Go Adventure/Evasion Djerba → Djerba Fun

### 7. Package Scope Rename (Phase 7)

- Root `package.json`: `go-adventure` → `djerba-fun`
- `packages/schemas/package.json`: `@go-adventure/schemas` → `@djerba-fun/schemas`
- `packages/ui/package.json`: `@go-adventure/ui` → `@djerba-fun/ui`
- `apps/web/package.json`: Updated dependencies
- Updated 147+ import statements across codebase
- BDD tests: `/apps/web/__tests__/packages/schema-imports.test.ts`

### 8. Brand Name Migration (Phase 8)

- Updated all "Go Adventure" references → "Djerba Fun"
- Updated all "Evasion Djerba" references → "Djerba Fun"
- Updated email addresses → @djerba.fun
- Updated social media URLs → djerbafun
- BDD tests:
  - `/apps/laravel-api/tests/Feature/BrandMigrationTest.php`
  - `/apps/web/tests/e2e/brand-migration.spec.ts`

## Service Types

| Enum          | Value           | Description                  |
| ------------- | --------------- | ---------------------------- |
| TOUR          | `tour`          | Island tours, excursions     |
| NAUTICAL      | `nautical`      | Jet ski, parasailing, diving |
| ACCOMMODATION | `accommodation` | Hotels, guesthouses          |
| EVENT         | `event`         | Special events, festivals    |

## Important Files

| File                                                            | Purpose                  |
| --------------------------------------------------------------- | ------------------------ |
| `/apps/laravel-api/app/Enums/ServiceType.php`                   | Service type definitions |
| `/packages/ui/src/tokens/colors.ts`                             | Design system colors     |
| `/apps/laravel-api/database/seeders/PlatformSettingsSeeder.php` | Platform identity        |
| `/apps/web/messages/{en,fr}.json`                               | UI translations          |
| `/apps/laravel-api/lang/{en,fr}/mail.php`                       | Email translations       |
| `/apps/web/scripts/*.ts`                                        | CSV translation scripts  |

## Quick Commands

```bash
# Start development
make up

# Run database fresh seed (will apply migrations + seeders)
make fresh

# Check frontend types
cd apps/web && pnpm typecheck

# Check for remaining old brand references
grep -r "Go Adventure" apps/
grep -r "Evasion Djerba" apps/
grep -r "@go-adventure" apps/
```
