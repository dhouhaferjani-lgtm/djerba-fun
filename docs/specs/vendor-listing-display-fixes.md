# Vendor-Created Listing Display Fixes - BDD Specification

## Executive Summary

This document outlines the issues identified when comparing vendor-created listings with seeded listings, and provides a BDD-based implementation plan to fix them.

### Comparison Analysis

| Feature         | Seeded Listing (Trek au Sommet) | Vendor-Created (Entre Oliviers)         |
| --------------- | ------------------------------- | --------------------------------------- |
| Description     | Renders correctly (plain text)  | Shows RAW HTML tags (`<p>`, `<strong>`) |
| Images          | Display correctly               | No images (missing upload feature)      |
| Map             | Displays with location pin      | Not displaying                          |
| What's Included | Shows correct items             | May show wrong data                     |
| Not Included    | Shows correct items             | May show wrong data                     |
| Requirements    | Shows correct items             | May show wrong data                     |

---

## Issue 1: Description Shows Raw HTML Tags

### Root Cause Analysis

**Data Flow:**

```
Vendor Panel (RichEditor) → Database (HTML string) → API (returns HTML) → Frontend (renders as text)
```

**Problem:**

- Vendor Panel uses `Forms\Components\RichEditor` which outputs HTML
- Frontend renders description as plain text:
  ```tsx
  // listing-detail-client.tsx:789-791
  <p className="...">{description} // HTML is escaped, shows raw tags</p>
  ```

**Seeded listings work because:**

- Seeder stores plain text (no HTML tags) in description field
- RichEditor outputs: `<p>My description</p>`
- But seeder stores: `My description\n\nMore text`

### BDD Scenarios

```gherkin
Feature: Description HTML Rendering
  As a traveler
  I want to see properly formatted descriptions
  So that I can read about the experience clearly

  Background:
    Given the vendor has created a listing with HTML description
    And the description contains formatting like bold, lists, and paragraphs

  Scenario: HTML description renders correctly on listing detail page
    Given a listing with description containing "<p><strong>Welcome</strong> to our tour!</p>"
    When I view the listing detail page
    Then I should see "Welcome" in bold text
    And I should not see raw HTML tags like "<p>" or "<strong>"

  Scenario: Description with bullet points renders as a list
    Given a listing with description containing "<ul><li>Item 1</li><li>Item 2</li></ul>"
    When I view the listing detail page
    Then I should see a bulleted list with "Item 1" and "Item 2"

  Scenario: Plain text descriptions still work
    Given a listing with plain text description "Simple description without HTML"
    When I view the listing detail page
    Then I should see "Simple description without HTML"

  Scenario: XSS protection for malicious HTML
    Given a listing with description containing "<script>alert('xss')</script>"
    When I view the listing detail page
    Then the script should not execute
    And I should not see the script tag
```

### Implementation Plan

**File:** `apps/web/src/app/[locale]/listings/[slug]/listing-detail-client.tsx`

```tsx
// BEFORE (line 789-791)
<p className="font-sans text-lg text-neutral-700 leading-relaxed whitespace-pre-line">
  {description}
</p>;

// AFTER - Use dangerouslySetInnerHTML with DOMPurify sanitization
import DOMPurify from 'dompurify';

<div
  className="font-sans text-lg text-neutral-700 leading-relaxed prose prose-neutral max-w-none"
  dangerouslySetInnerHTML={{
    __html: DOMPurify.sanitize(description, {
      ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a', 'h2', 'h3', 'h4'],
      ALLOWED_ATTR: ['href', 'target', 'rel'],
    }),
  }}
/>;
```

**Dependencies to add:**

```bash
cd apps/web && pnpm add dompurify @types/dompurify
```

---

## Issue 2: Missing Media/Image Upload in Vendor Panel

### Root Cause Analysis

**Problem:**

- Vendor Panel `ListingResource.php` has NO FileUpload or SpatieMediaLibraryFileUpload for main images
- Only file upload exists inside itinerary checkpoints (for checkpoint photos)
- Seeded listings have images because seeder directly creates `Media` records

**Evidence:**

```php
// Vendor Panel ListingResource.php - NO media upload field exists
// Only FileUpload is at line 853-860 inside itinerary repeater
Forms\Components\FileUpload::make('photos')
    ->label('Photos of this location')  // This is for checkpoint photos only!
```

### BDD Scenarios

```gherkin
Feature: Listing Media Upload
  As a vendor
  I want to upload images for my listing
  So that travelers can see what my experience looks like

  Background:
    Given I am logged in as a vendor
    And I am editing my listing

  Scenario: Vendor can upload hero image
    When I navigate to the "Media" step in the listing wizard
    Then I should see a "Hero Image" upload field
    When I upload an image file
    Then the image should appear as a preview
    And the image should be marked as the hero/cover image

  Scenario: Vendor can upload gallery images
    When I navigate to the "Media" step in the listing wizard
    Then I should see a "Gallery Images" upload field
    When I upload multiple images
    Then I should see all uploaded images as previews
    And I should be able to reorder the images

  Scenario: Uploaded images appear on listing detail page
    Given I have uploaded a hero image and 3 gallery images
    And my listing is published
    When a traveler views my listing
    Then they should see the hero image prominently
    And they should see the gallery images in a carousel

  Scenario: Image validation
    When I try to upload a file that is not an image
    Then I should see an error "Only image files are allowed"
    When I try to upload an image larger than 10MB
    Then I should see an error "Image must be smaller than 10MB"
```

### Implementation Plan

**Option A: Add MediaRelationManager (Recommended)**

Create a new relation manager for the Vendor Panel:

**File:** `apps/laravel-api/app/Filament/Vendor/Resources/ListingResource/RelationManagers/MediaRelationManager.php`

```php
<?php

namespace App\Filament\Vendor\Resources\ListingResource\RelationManagers;

use App\Enums\MediaCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';
    protected static ?string $title = 'Images';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('url')
                ->label('Image')
                ->image()
                ->required()
                ->directory('listings')
                ->maxSize(10240)
                ->imageResizeMode('cover')
                ->imageCropAspectRatio('16:9'),

            Forms\Components\TextInput::make('alt')
                ->label('Alt Text (for accessibility)')
                ->maxLength(255),

            Forms\Components\Select::make('category')
                ->label('Image Type')
                ->options([
                    MediaCategory::HERO->value => 'Hero/Cover Image',
                    MediaCategory::GALLERY->value => 'Gallery Image',
                    MediaCategory::FEATURED->value => 'Featured Image',
                ])
                ->default(MediaCategory::GALLERY->value)
                ->required(),

            Forms\Components\TextInput::make('order')
                ->label('Display Order')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('url')
                    ->label('Image')
                    ->height(60),
                Tables\Columns\TextColumn::make('alt')
                    ->label('Alt Text')
                    ->limit(30),
                Tables\Columns\TextColumn::make('category')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->reorderable('order')
            ->defaultSort('order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Image'),
            ]);
    }
}
```

**Update ListingResource.php:**

```php
public static function getRelations(): array
{
    return [
        RelationManagers\ExtrasRelationManager::class,
        RelationManagers\MediaRelationManager::class,  // ADD THIS
    ];
}
```

**Option B: Add inline FileUpload in wizard (Alternative)**

Add a new step or section in the existing wizard:

```php
// In ListingResource form(), add after Step 2 or as Step 7
Forms\Components\Wizard\Step::make('Media')
    ->icon('heroicon-o-photo')
    ->schema([
        Forms\Components\Section::make('Hero Image')
            ->description('This will be the main cover image for your listing')
            ->schema([
                Forms\Components\FileUpload::make('hero_image')
                    ->label('Cover Image')
                    ->image()
                    ->directory('listings/hero')
                    ->maxSize(10240)
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('16:9')
                    ->helperText('Recommended: 1920x1080px, max 10MB'),
            ]),

        Forms\Components\Section::make('Gallery Images')
            ->description('Add more images to showcase your experience')
            ->schema([
                Forms\Components\FileUpload::make('gallery_images')
                    ->label('Gallery')
                    ->image()
                    ->multiple()
                    ->maxFiles(10)
                    ->directory('listings/gallery')
                    ->maxSize(10240)
                    ->reorderable()
                    ->helperText('Upload up to 10 images'),
            ]),
    ]),
```

---

## Issue 3: Map Not Displaying

### Root Cause Analysis

**Potential Causes:**

1. **Missing location_id**: Vendor may not have selected a location
2. **Location has no coordinates**: Selected location may have NULL lat/lng
3. **Frontend dynamic import issue**: Map component uses dynamic import

**Data Flow:**

```
Vendor Panel (location_id select) → Database → API (returns location.latitude/longitude) → Frontend (MapView)
```

**API Response Structure:**

```json
{
  "location": {
    "id": "uuid",
    "name": "Location Name",
    "latitude": 36.8065,
    "longitude": 10.1815
  }
}
```

### BDD Scenarios

```gherkin
Feature: Listing Map Display
  As a traveler
  I want to see the listing location on a map
  So that I know where the experience takes place

  Background:
    Given a published listing exists

  Scenario: Map displays when listing has valid location
    Given the listing has a location with coordinates 36.8065, 10.1815
    When I view the listing detail page
    Then I should see a map centered on those coordinates
    And I should see a marker at the listing location

  Scenario: Map shows meeting point if no itinerary
    Given the listing has a meeting point at 36.8065, 10.1815
    And the listing has no itinerary stops
    When I view the listing detail page
    Then I should see a map showing the meeting point

  Scenario: Graceful handling when location has no coordinates
    Given the listing has a location without coordinates
    When I view the listing detail page
    Then I should not see a broken map
    And I should see "Location: [Location Name]" text instead

  Scenario: Map fallback for vendor-created listings
    Given a vendor creates a listing
    And selects a location that has coordinates
    When the listing is published and viewed
    Then the map should display correctly with the location marker
```

### Implementation Plan

**1. Ensure Vendor Panel requires location with coordinates:**

Update the Vendor Panel location select to show only locations with coordinates:

```php
// ListingResource.php - update location_id select
Forms\Components\Select::make('location_id')
    ->label('Location')
    ->options(fn () => Location::query()
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->get()
        ->mapWithKeys(fn ($loc) => [
            $loc->id => $loc->getTranslation('name', app()->getLocale()),
        ]))
    ->searchable()
    ->preload()
    ->helperText('Required for publishing. Only locations with map coordinates are shown.'),
```

**2. Add fallback in frontend:**

```tsx
// listing-detail-client.tsx - add location validation
const hasValidLocation =
  listing.location?.latitude &&
  listing.location?.longitude &&
  !isNaN(listing.location.latitude) &&
  !isNaN(listing.location.longitude);

// In JSX, conditionally render map
{
  hasValidLocation ? (
    <MapView
      center={[listing.location.latitude, listing.location.longitude]}
      // ... other props
    />
  ) : (
    <div className="bg-neutral-100 p-4 rounded-lg">
      <p className="text-neutral-600">
        <MapPin className="inline h-5 w-5 mr-2" />
        Location: {listing.location?.name || 'Not specified'}
      </p>
    </div>
  );
}
```

**3. Check database for locations without coordinates:**

```sql
-- Run this to identify locations missing coordinates
SELECT id, name, slug, latitude, longitude
FROM locations
WHERE latitude IS NULL OR longitude IS NULL;
```

---

## Issue 4: What's Included / Not Included / Requirements Display

### Root Cause Analysis

**Data Structure Comparison:**

**Seeded listing (RichDemoListingSeeder):**

```php
'included' => [
    ['en' => 'Transport', 'fr' => 'Transport'],
    ['en' => 'Guide', 'fr' => 'Guide'],
],
```

**Vendor Panel Repeater output:**

```php
// Each item is stored as: ['en' => '...', 'fr' => '...']
```

**Frontend translation helper:**

```tsx
// resolveTranslation handles {en: "...", fr: "..."} correctly
const tr = (field: any) => resolveTranslation(field, locale);
```

**Potential Issue:**
If the Vendor Panel is storing data incorrectly (nested arrays, wrong structure), the frontend will fail.

### BDD Scenarios

```gherkin
Feature: Included/Not Included Display
  As a traveler
  I want to see what is included and not included
  So that I know what to expect

  Scenario: What's Included displays correctly
    Given a listing with included items:
      | en                | fr               |
      | Professional guide | Guide professionnel |
      | Transport         | Transport        |
    When I view the listing in English
    Then I should see "Professional guide" with a checkmark
    And I should see "Transport" with a checkmark

  Scenario: What's Not Included displays correctly
    Given a listing with not included items:
      | en         | fr        |
      | Meals      | Repas     |
      | Equipment  | Equipement|
    When I view the listing in English
    Then I should see "Meals" with an X icon
    And I should see "Equipment" with an X icon

  Scenario: Empty included/not included handled gracefully
    Given a listing with no included items
    When I view the listing detail page
    Then I should not see the "What's Included" section

  Scenario: Multilingual support
    Given a listing with included item "Guide" in English and "Guide" in French
    When I view the listing in French
    Then I should see "Guide" in the included section
```

### Implementation Plan

**1. Verify data structure in API Resource:**

The current implementation should work. Add debugging if issues persist:

```php
// ListingResource.php - add logging for debugging
'included' => tap(
    is_array($this->included) ? $this->toCamelCase($this->included) : $this->included,
    fn($v) => \Log::debug('Included data', ['value' => $v])
),
```

**2. Frontend defensive coding:**

```tsx
// Ensure tr() handles edge cases
const tr = (field: any) => {
  if (!field) return '';
  if (typeof field === 'string') return field;
  if (Array.isArray(field)) {
    // Handle malformed array data
    const first = field[0];
    if (first && typeof first === 'object') {
      return first[locale] || first['en'] || '';
    }
    return '';
  }
  return resolveTranslation(field, locale);
};
```

---

## Implementation Priority

### Phase 1 - Critical (Block user experience)

1. **Description HTML Rendering** - Users see raw HTML tags
2. **Media Upload** - Vendors cannot add images

### Phase 2 - Important (Affects functionality)

3. **Map Display** - Users cannot see location

### Phase 3 - Enhancement

4. **Included/NotIncluded** - May need data migration if structure is wrong

---

## Testing Checklist

### Manual Testing

- [ ] Create new listing via Vendor Panel
- [ ] Upload hero image and gallery images
- [ ] Add HTML-formatted description with bold, lists
- [ ] Add highlights, included, not included items
- [ ] Select a location with coordinates
- [ ] Submit for review and publish via Admin
- [ ] View listing on frontend in EN and FR
- [ ] Verify all sections display correctly
- [ ] Compare with seeded listing side-by-side

### Regression Testing

- [ ] Existing seeded listings still display correctly
- [ ] Existing vendor-created listings display better (or same)
- [ ] Search and filter still work
- [ ] Booking flow still works
- [ ] Admin panel still functions

---

## Files to Modify

| File                                                                                                       | Changes                                       |
| ---------------------------------------------------------------------------------------------------------- | --------------------------------------------- |
| `apps/web/src/app/[locale]/listings/[slug]/listing-detail-client.tsx`                                      | Add DOMPurify for description, map validation |
| `apps/web/package.json`                                                                                    | Add dompurify dependency                      |
| `apps/laravel-api/app/Filament/Vendor/Resources/ListingResource.php`                                       | Add Media wizard step or relation manager     |
| `apps/laravel-api/app/Filament/Vendor/Resources/ListingResource/RelationManagers/MediaRelationManager.php` | NEW FILE                                      |

---

## Estimated Effort

| Task                           | Complexity | Estimate      |
| ------------------------------ | ---------- | ------------- |
| Description HTML rendering     | Low        | 1 hour        |
| Media upload (RelationManager) | Medium     | 2-3 hours     |
| Map display fixes              | Low        | 1 hour        |
| Testing & verification         | Medium     | 2 hours       |
| **Total**                      |            | **6-7 hours** |
