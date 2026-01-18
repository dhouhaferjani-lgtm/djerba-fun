# Urgent Improvements

## 1. Auto-fill Publish Date When Status is "Published"

### Problem

When creating or editing a blog post in the Filament admin panel, if the user sets the status to "Published" but doesn't manually set the "Publish Date" field, the blog post won't appear on the frontend.

**Root Cause**: The `scopePublished` query in `BlogPost.php` requires BOTH:

- `status = 'published'`
- `published_at IS NOT NULL AND published_at <= now()`

This creates a confusing UX where a post appears as "Published" in the admin but doesn't show on the frontend.

### Current Behavior

1. User creates blog post
2. User sets status to "Published"
3. User saves (without setting Publish Date)
4. Post shows as "Published" in admin list
5. Post does NOT appear on frontend (because `published_at` is NULL)

### Expected Behavior

1. User creates blog post
2. User sets status to "Published"
3. **Publish Date auto-fills with current date/time**
4. User saves
5. Post appears on frontend immediately

### Solution

**File**: `apps/laravel-api/app/Filament/Admin/Resources/BlogPostResource.php`

Add reactive logic to the form to auto-fill `published_at` when status changes to "published":

```php
Forms\Components\Select::make('status')
    ->options([
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ])
    ->required()
    ->default('draft')
    ->live()
    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
        // Auto-fill publish date when status changes to published
        if ($state === 'published' && empty($get('published_at'))) {
            $set('published_at', now());
        }
    }),

Forms\Components\DateTimePicker::make('published_at')
    ->label('Publish Date')
    ->nullable(),
```

### Priority

**HIGH** - This is a critical UX issue that causes confusion and makes blog posts "invisible" even when marked as published.

### Testing

1. Create new blog post
2. Set status to "Published"
3. Verify Publish Date auto-fills with current date
4. Save and verify post appears on frontend
5. Edit existing draft post
6. Change status to "Published"
7. Verify Publish Date auto-fills
8. Save and verify post appears on frontend

---

## 2. Homepage Blog Section Uses Hardcoded Data

### Problem

The `BlogSection.tsx` component on the homepage uses hardcoded blog post data with translation keys instead of fetching from the API.

### Current State

- Hardcoded slugs: `top-10-hidden-gems-tunisia`, `sustainable-travel-tunisia`, `tunisian-cuisine-guide`
- Uses `useTranslations('home')` for titles/excerpts
- If these posts don't exist in DB, links lead to 404

### Suggested Fix

Fetch latest 3 blog posts from API instead of using hardcoded data:

- Server component that calls `getFeaturedBlogPosts(3, locale)`
- Or client component with SWR/React Query for caching

### Priority

**MEDIUM** - Currently works if blog posts with matching slugs exist, but should be dynamic.

---

## 3. Blog Slug Not Updating in Real-Time

### Problem

When creating a blog post in Filament Admin, the slug only generates once when the title is first entered. It does NOT update in real-time as the user types or changes the title. The Listing slug in the Vendor panel DOES update in real-time.

### Root Cause Analysis

**Blog Post (BlogPostResource.php - lines 42-56):**

```php
Forms\Components\TextInput::make('title')
    ->label('Title')
    ->required()
    ->maxLength(255)
    ->live(onBlur: true)  // ❌ PROBLEM: Only fires when leaving field
    ->afterStateUpdated(
        fn (string $operation, $state, Forms\Set $set) =>
            $operation === 'create' ? $set('slug', Str::slug($state)) : null  // ❌ Only on create
    ),

Forms\Components\TextInput::make('slug')
    ->label('Slug')
    // No tracking of manual edits
```

**Issues:**

1. `->live(onBlur: true)` - Only triggers when user LEAVES the field, not while typing
2. `$operation === 'create'` - Only generates slug on CREATE, never updates on edit
3. No tracking of whether user manually edited the slug

**Listing (ListingResource.php - lines 101-144) - THE CORRECT WAY:**

```php
Forms\Components\TextInput::make('title')
    ->label('Title')
    ->maxLength(200)
    ->live(debounce: 500)  // ✅ Real-time with 500ms debounce
    ->afterStateUpdated(function (Get $get, Set $set, ?string $state, $record) {
        if ($record !== null && $record->exists && $record->slug) {
            return;  // Preserve existing slugs
        }

        $currentSlug = $get('slug');
        $newSlug = Str::slug($state ?? '');
        $autoSlug = $get('_auto_slug');

        if (empty($currentSlug) || $currentSlug === $autoSlug) {
            $set('slug', $newSlug);
            $set('_auto_slug', $newSlug);
        }
    }),

Forms\Components\Hidden::make('_auto_slug')
    ->dehydrated(false),  // ✅ Tracks auto-generated slug without saving
```

### Solution

Update `BlogPostResource.php` to match `ListingResource.php` approach:

1. Change `->live(onBlur: true)` to `->live(debounce: 500)`
2. Add hidden `_auto_slug` field for tracking
3. Update `afterStateUpdated` logic to detect manual edits
4. Preserve existing slugs on edit (SEO protection)

### File to Modify

- `apps/laravel-api/app/Filament/Admin/Resources/BlogPostResource.php`

### Priority

**HIGH** - Poor UX confuses content creators who expect real-time slug preview

---

## 4. Admin Panel French Translation Support

### Problem

The Filament admin panel has no language switcher visible and all UI text (buttons, labels, navigation) is in English only. French-speaking users cannot use the admin panel in their native language.

### Root Cause Analysis

**What EXISTS (partially working):**

1. **SpatieLaravelTranslatablePlugin** is configured in `AdminPanelProvider.php`
2. **Models have translatable fields** (Listing, BlogPost, Location, etc.)
3. **LocaleSwitcher EXISTS** but only in **Vendor panel pages**, NOT in Admin panel

**What's MISSING:**

1. **LocaleSwitcher not in Admin panel pages** - Vendor panel has `Actions\LocaleSwitcher::make()` but Admin panel does not
2. **No French translation files for Filament UI** - No custom `lang/fr/` folder with translated strings

### Solution

**Step 1: Publish Filament French language files**

```bash
php artisan vendor:publish --tag=filament-panels-translations
php artisan vendor:publish --tag=filament-translations
```

**Step 2: Add LocaleSwitcher to Admin panel pages**
Each Admin resource page needs:

```php
use Filament\Actions;

protected function getHeaderActions(): array
{
    return [
        Actions\LocaleSwitcher::make(),
        // ... other actions
    ];
}
```

### Files to Modify

- `apps/laravel-api/app/Providers/Filament/AdminPanelProvider.php`
- All Admin Resource page files (List, Create, Edit pages)
- Create `apps/laravel-api/lang/fr/filament-panels.php`
- Create `apps/laravel-api/lang/fr/filament.php`

### Priority

**HIGH** - French-speaking customers cannot effectively use the admin panel

---

## 5. Blog Author Field Shows Travelers

### Problem

When creating a blog post in the Admin panel, the Author dropdown shows ALL users including travelers. Logically, only admins and vendors should be able to author blog posts.

### Root Cause Analysis

**BlogPostResource.php (lines 91-97):**

```php
Forms\Components\Select::make('author_id')
    ->label('Author')
    ->relationship('author', 'display_name')  // ❌ NO ROLE FILTERING
    ->searchable()
    ->preload()
    ->required()
    ->default(fn () => auth()->id()),
```

The problem: `->relationship('author', 'display_name')` queries ALL users with no constraints.

### Solution

```php
Forms\Components\Select::make('author_id')
    ->label('Author')
    ->relationship(
        'author',
        'display_name',
        modifyQueryUsing: fn (Builder $query) => $query->whereIn('role', ['admin', 'vendor'])
    )
    ->searchable()
    ->preload()
    ->required()
    ->default(fn () => auth()->id()),
```

### File to Modify

- `apps/laravel-api/app/Filament/Admin/Resources/BlogPostResource.php` (line 93)

### Priority

**HIGH** - Data integrity issue. Travelers should NOT be allowed as blog authors.

---

## 6. Blog Editor Missing Features

### Problem

The current blog editor is limited compared to expectations. Users can only add a featured image but cannot insert inline images throughout the article. Missing key features like blockquotes.

### Root Cause

**BlogPostResource.php (lines 65-84):**

```php
Forms\Components\RichEditor::make('content')
    ->label('Content')
    ->required()
    ->fileAttachmentsDisk('public')           // ✅ Configured
    ->fileAttachmentsDirectory('blog-attachments')  // ✅ Configured
    ->fileAttachmentsVisibility('public')     // ✅ Configured
    ->toolbarButtons([
        'bold',
        'bulletList',
        'codeBlock',
        'h2',
        'h3',
        'italic',
        'link',
        'orderedList',
        'redo',
        'strike',
        'underline',
        'undo',
        // ❌ MISSING: 'attachFiles' - Cannot insert inline images!
        // ❌ MISSING: 'blockquote' - Cannot add quotes
    ])
```

File attachment storage IS configured, but **`attachFiles` button is NOT in the toolbar array**.

### Solution

```php
->toolbarButtons([
    'attachFiles',  // ⬅️ ADD THIS - enables inline image uploads
    'blockquote',   // ⬅️ ADD THIS - enables quote blocks
    'bold',
    'bulletList',
    'codeBlock',
    'h2',
    'h3',
    'italic',
    'link',
    'orderedList',
    'redo',
    'strike',
    'underline',
    'undo',
])
```

### File to Modify

- `apps/laravel-api/app/Filament/Admin/Resources/BlogPostResource.php` (lines 71-84)

### Priority

**HIGH** - Users cannot add inline images to blog posts, severely limiting content creation.

---

## 7. 500 Error on Create Listing - Missing GpxParserService (FIXED)

### Problem

When clicking "Create Listing" in the Vendor panel, a 500 server error occurred.

### Root Cause

**File**: `apps/laravel-api/app/Filament/Vendor/Resources/ListingResource.php`

Line 14 imported `use App\Services\GpxParserService;` and line 498 used `app(GpxParserService::class)`, but the class didn't exist in `app/Services/`.

### Status

**FIXED** - Created `apps/laravel-api/app/Services/GpxParserService.php` with all required methods:

- `parse()` - Parse GPX files
- `generateElevationProfile()` - Generate elevation data
- `waypointsToItinerary()` - Convert waypoints to itinerary
- `createStopsFromTrack()` - Create stops from track points

### Deploy

```bash
git pull
docker compose -f docker-compose.prod.yml up -d --build api
```
