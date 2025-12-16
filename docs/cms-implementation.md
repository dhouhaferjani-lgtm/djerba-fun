# CMS Implementation - Statik Flexible Content Blocks

## Overview

A complete CMS integration has been implemented for Go Adventure using **Statik Flexible Content Blocks + Pages** package for Laravel + Filament. This provides a drag-and-drop, block-based content management system with full multilingual support (EN/FR).

## What Was Implemented

### Backend (Laravel)

1. **CMS Packages Installed**
   - `statikbe/laravel-filament-flexible-content-blocks` v2.6.4
   - `statikbe/laravel-filament-flexible-content-block-pages` v0.2.12
   - Plus dependencies: Spatie Media Library, Spatie Translatable, OpenAI, etc.

2. **Database Tables Created**
   - `pages` - Main CMS pages with translatable content
   - `redirects` - URL redirects management
   - `settings` - Site-wide settings
   - `tags` & `tag_types` - Tagging system
   - `menus` & `menu_items` - Menu builder
   - Plus supporting tables

3. **Configuration**
   - Locales: `['en', 'fr']`
   - Default canonical locale: `en`
   - CMS panel path: `/admin/website`
   - Sitemap generation: Manual mode

4. **Custom Content Block Created**
   - **ToursListingBlock** (`/app/ContentBlocks/ToursListingBlock.php`)
     - Filter by listing type (all, tour, event)
     - Configurable item count (1-12)
     - Multiple sort options (newest, title, price)
     - Display styles (grid, carousel, list)
   - Blade view: `/resources/views/content-blocks/tours-listing-block.blade.php`
   - Registered in config

5. **API Endpoints Created**
   - `GET /api/v1/pages` - List all published pages
   - `GET /api/v1/pages/{slug}` - Get page by slug
   - `GET /api/v1/pages/code/{code}` - Get page by code (e.g., HOME)
   - `GET /api/v1/menus/{menuCode}` - Get menu by code
   - All endpoints support `?locale=en|fr` parameter

6. **Resources Created**
   - `PageController` (`/app/Http/Controllers/Api/V1/PageController.php`)
   - `PageResource` (`/app/Http/Resources/PageResource.php`)

### Frontend (Next.js)

1. **TypeScript Types** (`/apps/web/src/types/cms.ts`)
   - `CMSPage` - Page data structure
   - `ContentBlock` - Generic block interface
   - `Menu` & `MenuItem` - Menu structures
   - Specific block data types for all block types

2. **API Client** (`/apps/web/src/lib/api/cms.ts`)
   - `getPages()` - Fetch all pages
   - `getPage({ slug, locale })` - Fetch single page
   - `getPageByCode({ code, locale })` - Fetch by code
   - `getMenu({ menuCode, locale })` - Fetch menu

3. **Block Components** (`/apps/web/src/components/cms/blocks/`)
   - `VideoBlock` - YouTube/Vimeo embeds
   - `ImageBlock` - Images with captions
   - `TextImageBlock` - Text + image side-by-side
   - `CallToActionBlock` - CTA sections
   - `QuoteBlock` - Blockquotes
   - `HtmlBlock` - Raw HTML content
   - `CardsBlock` - Card grids
   - `ToursListingBlock` - Dynamic tour listings

4. **BlockRenderer** (`/apps/web/src/components/cms/BlockRenderer.tsx`)
   - Main component for rendering CMS content
   - Maps block types to components
   - Handles unknown block types gracefully

## Available Content Blocks

The following blocks are available in the Filament CMS editor:

| Block Type        | Description                     | Use Case                |
| ----------------- | ------------------------------- | ----------------------- |
| Video             | Embed YouTube/Vimeo videos      | Media content           |
| Image             | Full-width images with captions | Photos, graphics        |
| Text + Image      | Text alongside image            | Feature descriptions    |
| Call to Action    | Prominent CTA sections          | Conversions             |
| Quote             | Blockquotes with attribution    | Testimonials            |
| HTML              | Raw HTML content                | Custom content          |
| Cards             | Grid of cards                   | Feature lists           |
| **Tours Listing** | Dynamic tour/event listings     | Homepage, landing pages |
| Template          | Custom templates                | Advanced layouts        |
| Overview          | Overview sections               | Summaries               |
| Collapsible Group | Accordion content               | FAQs                    |

## How to Use

### 1. Access the CMS

1. Navigate to `/admin` and login
2. The CMS resources are available in the Filament admin panel:
   - **Pages** - Create and manage pages
   - **Menus** - Build navigation menus
   - **Redirects** - Manage URL redirects
   - **Tags** - Organize content with tags
   - **Settings** - Site-wide settings

### 2. Create a Page

1. Go to **Pages** → **Create**
2. Fill in:
   - **Title** (translatable EN/FR)
   - **Slug** (translatable EN/FR)
   - **Intro** (optional)
   - **Hero Image** (optional)
   - **SEO fields** (title, description, keywords)
3. Add **Content Blocks**:
   - Click "Add Block"
   - Choose block type
   - Configure block settings
   - Drag to reorder
4. Set **Publishing Schedule** (optional)
5. Save

### 3. Use Special Pages

Create pages with special codes for specific purposes:

- Code: `HOME` - Will be used as homepage
- Code: Custom codes can be fetched via `/api/v1/pages/code/{code}`

### 4. Render Pages in Next.js

```tsx
import { getPage } from '@/lib/api/cms';
import { BlockRenderer } from '@/components/cms';

export default async function CMSPage({ params }: { params: { slug: string } }) {
  const page = await getPage({
    slug: params.slug,
    locale: 'en',
  });

  return (
    <div>
      {/* Hero */}
      {page.hero_image && (
        <div className="relative h-[60vh]">
          <img src={page.hero_image} alt={page.title} />
          <h1>{page.title}</h1>
          {page.intro && <p>{page.intro}</p>}
        </div>
      )}

      {/* Content Blocks */}
      <div className="container mx-auto py-12">
        <BlockRenderer blocks={page.content_blocks} />
      </div>
    </div>
  );
}
```

### 5. Use the Tours Listing Block

When editing a page in Filament:

1. Add block → **Tours Listing Block**
2. Configure:
   - **Listing Type**: All, Tours Only, or Events Only
   - **Number of Items**: 1-12
   - **Sort By**: Newest, Title, Price (Low/High)
   - **Display Style**: Grid, Carousel, or List
3. The block will automatically fetch and display tours from the API

## Multilingual Support

All content is translatable:

1. **In Filament**:
   - Use the language tabs (EN/FR) to add translations
   - Title, slug, intro, and all content blocks are translatable

2. **In API**:
   - Add `?locale=en` or `?locale=fr` to any endpoint
   - Default is `en`

3. **In Next.js**:
   ```tsx
   const page = await getPage({ slug: 'about', locale: 'fr' });
   ```

## Menu Builder

1. Go to **Menus** → **Create**
2. Set menu code (e.g., `main`, `footer`)
3. Add menu items:
   - Link to pages
   - External URLs
   - Nest items (max 2 levels)
   - Reorder with drag-and-drop
4. Fetch in Next.js:
   ```tsx
   const menu = await getMenu({ menuCode: 'main', locale: 'en' });
   ```

## Sitemap Generation

The CMS includes automatic sitemap generation:

```bash
# Generate sitemap
php artisan pages:generate-sitemap
```

Available at: `/sitemap.xml`

## SEO Features

Each page includes:

- Custom SEO title
- Meta description
- Keywords
- Social media image (og:image)
- Structured data support

## Advanced Features

### 1. Page Tree

Pages can have parent-child relationships (max 2 levels deep):

- Configured in page settings
- Useful for hierarchical content

### 2. Publishing Schedule

Set when pages should be visible:

- **Publishing Begins At**: Page goes live
- **Publishing Ends At**: Page is hidden

### 3. Tags

Organize pages with tags:

- Create tag types
- Tag pages
- Auto-generated tag pages at `/tag/{slug}`

### 4. Redirects

Manage URL redirects:

- Old URL → New URL
- 301 or 302 redirects
- Integrated with Spatie Missing Page Redirector

## Example Use Cases

### Use Case 1: Create Homepage with CMS

1. Create page with code `HOME`
2. Add blocks:
   - Call to Action (hero)
   - Tours Listing Block (featured tours)
   - Text + Image (about section)
   - Call to Action (bottom CTA)
3. In Next.js:
   ```tsx
   const homepage = await getPageByCode({ code: 'HOME' });
   ```

### Use Case 2: Landing Page for Sahara Tours

1. Create page: "Sahara Desert Adventures"
2. Add blocks:
   - Image Block (hero image)
   - Text + Image (description)
   - Tours Listing Block (filter: tours only, sort: price)
   - Call to Action (book now)

### Use Case 3: Event Calendar Page

1. Create page: "Upcoming Events"
2. Add block:
   - Tours Listing Block (filter: events only, sort: newest, count: 12)

## File Structure

```
apps/laravel-api/
├── app/
│   ├── ContentBlocks/
│   │   └── ToursListingBlock.php
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   └── PageController.php
│   │   └── Resources/
│   │       └── PageResource.php
│   └── Providers/Filament/
│       └── AdminPanelProvider.php (updated)
├── config/
│   ├── filament-flexible-content-blocks.php
│   └── filament-flexible-content-block-pages.php
├── database/migrations/
│   └── 2025_12_15_* (CMS migrations)
├── resources/views/
│   └── content-blocks/
│       └── tours-listing-block.blade.php
└── routes/
    └── api.php (updated)

apps/web/
├── src/
│   ├── components/cms/
│   │   ├── BlockRenderer.tsx
│   │   ├── blocks/
│   │   │   ├── VideoBlock.tsx
│   │   │   ├── ImageBlock.tsx
│   │   │   ├── TextImageBlock.tsx
│   │   │   ├── CallToActionBlock.tsx
│   │   │   ├── QuoteBlock.tsx
│   │   │   ├── HtmlBlock.tsx
│   │   │   ├── CardsBlock.tsx
│   │   │   └── ToursListingBlock.tsx
│   │   └── index.ts
│   ├── lib/api/
│   │   └── cms.ts
│   └── types/
│       └── cms.ts
```

## Next Steps

To fully integrate the CMS into your application:

1. **Create CMS Page Route** in Next.js:

   ```tsx
   // /apps/web/src/app/[locale]/[slug]/page.tsx
   import { getPage } from '@/lib/api/cms';
   import { BlockRenderer } from '@/components/cms';

   export default async function DynamicPage({ params }) {
     const page = await getPage({ slug: params.slug, locale: params.locale });
     return <BlockRenderer blocks={page.content_blocks} />;
   }
   ```

2. **Add Menu to Header**:

   ```tsx
   const menu = await getMenu({ menuCode: 'main' });
   // Render menu items in navigation
   ```

3. **Use HOME Page**:
   Replace current homepage with CMS-managed version

4. **Create Content**:
   - About page
   - FAQ page
   - Contact page
   - Landing pages for specific tours/regions

5. **Extend Blocks** (optional):
   Create more custom blocks as needed:
   - BookingFormBlock
   - TestimonialCarouselBlock
   - MapBlock
   - etc.

## Resources

- [Statik Flexible Content Blocks Documentation](https://github.com/statikbe/laravel-filament-flexible-content-blocks)
- [Statik Pages Plugin Documentation](https://github.com/statikbe/laravel-filament-flexible-content-block-pages)
- [Filament Plugin Page](https://filamentphp.com/plugins/statik-flexible-content-blocks)

## Support

For CMS-related issues:

1. Check package documentation
2. Verify configuration in `/config/filament-flexible-content-block*.php`
3. Check Filament admin logs
4. Verify API responses in browser DevTools

---

**Implementation Complete** ✅

All CMS features are now ready to use. Access the CMS at `/admin` and start creating pages!
