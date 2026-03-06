# Evasion Djerba Migration Status

**Last Updated:** 2026-03-06
**Migration:** Go Adventure → Evasion Djerba

## Migration Summary

| Phase   | Status      | Description                                                               |
| ------- | ----------- | ------------------------------------------------------------------------- |
| Phase 1 | ✅ Complete | ServiceType enum migration (added NAUTICAL, renamed SEJOUR→ACCOMMODATION) |
| Phase 2 | ✅ Complete | Mediterranean color palette (Ocean Blue #0077B6, Sandy Orange #F4A261)    |
| Phase 3 | ✅ Complete | CSV translation system (`pnpm i18n:export/import`)                        |
| Phase 4 | ✅ Complete | Brand identity updates (seeders, metadata, logo component)                |
| Phase 5 | ✅ Complete | Backend config updates (app.php, cors.php, payment.php, filesystems.php)  |
| Phase 6 | ✅ Complete | Email translation files (lang/en/mail.php, lang/fr/mail.php)              |
| Phase 9 | ✅ Complete | CLAUDE.md updated for Evasion Djerba                                      |

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
- Primary: `#0077B6` (Ocean Blue)
- Secondary: `#F4A261` (Sandy Orange)
- Accent: `#E9F5F8` (Seafoam)
- Updated Filament AdminPanelProvider and VendorPanelProvider

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

- `PlatformSettingsSeeder.php` - Evasion Djerba identity, Tunisia address
- `ActivityTypeSeeder.php` - Djerba-specific activities (Island Tours, Nautical, Beach, Cultural, Gastronomy)
- `/apps/web/src/app/[locale]/layout.tsx` - Updated metadata, keywords, social links
- `/apps/web/src/app/manifest.ts` - PWA manifest
- `/apps/web/src/components/atoms/Logo.tsx` - Fallback brand name
- All other frontend source files with Go Adventure refs

### 5. Backend Configs (Phase 5)

- `config/app.php` - frontend_url → evasiondjerba.com
- `config/cors.php` - CORS pattern for evasiondjerba.com
- `config/payment.php` - booking prefix GA → ED
- `config/filesystems.php` - bucket name → evasion-djerba
- `.env.example` - All Go Adventure refs → Evasion Djerba

### 6. Email Templates (Phase 6)

- `lang/en/mail.php` - All Go Adventure → Evasion Djerba
- `lang/fr/mail.php` - All Go Adventure → Evasion Djerba

### 7. Additional Files Updated

- `VoucherPdfService.php` - Brand colors and name
- `VoucherMail.php` - Brand colors and name
- `OpenApiController.php` - API documentation title
- `SeedDestinationContentCommand.php` - SEO titles
- `PlatformSettingsPage.php` - All placeholder values

## What Remains

### Translation Files (Not Yet Migrated)

The JSON translation files still contain Go Adventure references:

- `/apps/web/messages/en.json`
- `/apps/web/messages/fr.json`
- `/apps/web/translations.csv`

**To update:**

```bash
cd apps/web
# Export current translations to CSV
pnpm i18n:export

# Edit translations.csv - search/replace "Go Adventure" → "Evasion Djerba"
# Then import back
pnpm i18n:import
```

### Demo Seeders (Low Priority)

These contain demo content with Go Adventure refs:

- `VendorSeeder.php`
- `RichDemoListingSeeder.php`
- `HikingTourWithMapSeeder.php`

### Test Files (Low Priority)

- Various test files and test result files
- Documentation files (PHASE\*.md)

## Quick Commands

```bash
# Start development
make up

# Run database fresh seed (will apply migrations + seeders)
make fresh

# Check frontend types
cd apps/web && pnpm typecheck

# Check for remaining "Go Adventure" references
grep -r "Go Adventure" apps/
```

## Brand Colors Reference

| Color          | Hex       | Usage                   |
| -------------- | --------- | ----------------------- |
| Primary        | `#0077B6` | Ocean Blue - Main brand |
| Primary Light  | `#0096C7` | Hover states            |
| Primary Dark   | `#023E8A` | Text on light bg        |
| Secondary      | `#F4A261` | Sandy Orange - CTAs     |
| Secondary Dark | `#E76F51` | Hover states            |
| Accent         | `#E9F5F8` | Seafoam - Backgrounds   |

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

## Next Steps When Resuming

1. Update translation files via CSV system
2. Run `make fresh` to apply all seeders
3. Test the frontend and admin panel
4. Deploy to staging.evasiondjerba.com
