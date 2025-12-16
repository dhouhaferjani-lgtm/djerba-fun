# Hybrid Homepage Setup Guide

## Overview

The Go Adventure homepage now uses a **hybrid approach** combining hardcoded React components with CMS-managed content blocks. This gives you the best of both worlds:

- ✅ **Fixed sections** (Hero, Marketing Mosaic) remain hardcoded for complex interactions
- ✅ **Flexible sections** can be managed entirely from the CMS
- ✅ **Graceful fallback** to hardcoded sections if CMS page doesn't exist yet

## How It Works

### Current Homepage Structure

```tsx
<MainLayout>
  {/* HARDCODED - Always shows */}
  <HeroSection />
  <MarketingMosaicSection />

  {/* CMS-MANAGED - Or fallback to hardcoded */}
  {cmsPage ? (
    <BlockRenderer blocks={cmsPage.content_blocks} />
  ) : (
    <>
      <FeaturedPackagesSection />
      <PromoBannerSection />
      <CategoriesGridSection />
      <DestinationsBentoGrid />
      <CTASectionWithBlobs />
    </>
  )}

  {/* HARDCODED - Always shows */}
  <BlogSection />
</MainLayout>
```

### New CMS Blocks Available

You can now use these blocks to recreate the middle sections:

| Block Name              | Replaces                | Description                      |
| ----------------------- | ----------------------- | -------------------------------- |
| **ToursListingBlock**   | FeaturedPackagesSection | Dynamic tour/event listings      |
| **PromoBannerBlock**    | PromoBannerSection      | Promotional banner with gradient |
| **CategoriesGridBlock** | CategoriesGridSection   | 4-column category grid           |
| **CardsBlock**          | DestinationsBentoGrid   | Flexible card layouts            |
| **CTAWithBlobsBlock**   | CTASectionWithBlobs     | CTA with decorative blobs        |

## Step-by-Step Setup

### Step 1: Access the CMS

1. Navigate to `/admin`
2. Login with admin credentials
3. Go to **Pages** in the sidebar

### Step 2: Create HOME Page

1. Click **Create** button
2. Fill in basic details:
   - **Code**: `HOME` (REQUIRED - must be exactly this)
   - **Title (EN)**: "Home"
   - **Title (FR)**: "Accueil"
   - **Slug (EN)**: "home"
   - **Slug (FR)**: "accueil"
3. Leave SEO fields empty (not used for homepage)
4. Skip hero image (we have hardcoded hero)

### Step 3: Add Content Blocks

Click "Add Block" and configure each section:

#### Block 1: Tours Listing (Featured Tours)

- **Block Type**: Tours Listing Block
- **Configuration**:
  - Listing Type: `All Listings`
  - Number of Items: `6`
  - Sort By: `Newest First`
  - Display Style: `Grid`

#### Block 2: Promo Banner

- **Block Type**: Promo Banner Block
- **Configuration**:
  - Tag: "Limited Time Offer" (optional)
  - Title: "Discover Tunisia's Hidden Gems"
  - Subtitle: "Book your next adventure and save 20%"
  - Primary Button Label: "Explore Tours"
  - Primary Button URL: "/listings"
  - Secondary Button Label: "Learn More"
  - Secondary Button URL: "/about"
  - Background Color: `Primary`

#### Block 3: Categories Grid

- **Block Type**: Categories Grid Block
- **Configuration**:
  - Click "Add Category" for each:
    1. **Desert Adventures**
       - Image: Upload desert image
       - Count: 12
       - URL: /listings?category=desert
    2. **Cultural Tours**
       - Image: Upload culture image
       - Count: 8
       - URL: /listings?category=culture
    3. **Beach & Coast**
       - Image: Upload beach image
       - Count: 15
       - URL: /listings?category=beach
    4. **Mountain Treks**
       - Image: Upload mountain image
       - Count: 6
       - URL: /listings?category=mountain

#### Block 4: Destinations (use CardsBlock)

- **Block Type**: Cards Block
- **Configuration**:
  - Columns: `3` or `4`
  - Add cards for each destination:
    - **Sahara Desert**
      - Image: Desert landscape
      - Description: "Experience endless dunes..."
      - Link: /destinations/sahara
    - **Tunis Medina**
      - Image: Medina photo
      - Description: "Explore ancient streets..."
      - Link: /destinations/tunis
    - **Djerba Island**
      - Image: Beach photo
      - Description: "Relax on pristine beaches..."
      - Link: /destinations/djerba

#### Block 5: CTA with Blobs

- **Block Type**: CTA With Blobs Block
- **Configuration**:
  - Title: "Ready for Your Next Adventure?"
  - Text: "Join thousands of travelers discovering Tunisia's wonders"
  - Button Label: "Browse All Tours"
  - Button URL: "/listings"
  - Button Style: `Secondary`

### Step 4: Publish the Page

1. Leave publishing dates empty (always visible)
2. Click **Save**
3. The homepage will now use your CMS content!

## Testing

### With CMS Page

1. Create HOME page as described above
2. Visit homepage: `/` or `/en` or `/fr`
3. Should see:
   - ✅ HeroSection (hardcoded)
   - ✅ MarketingMosaic (hardcoded)
   - ✅ Your CMS blocks
   - ✅ BlogSection (hardcoded)

### Without CMS Page

1. Delete or unpublish HOME page
2. Visit homepage
3. Should see:
   - ✅ HeroSection (hardcoded)
   - ✅ MarketingMosaic (hardcoded)
   - ✅ Hardcoded fallback sections
   - ✅ BlogSection (hardcoded)

## Multilingual Content

All blocks support EN/FR translations:

1. When editing a block, use the language tabs
2. Add content for both EN and FR
3. Frontend will automatically show correct language

## Advanced Customization

### Option 1: Replace More Sections with CMS

To make Hero or MarketingMosaic CMS-managed:

1. Create custom blocks for them (more complex)
2. Update `/apps/web/src/app/[locale]/page.tsx`
3. Remove hardcoded sections, add to CMS

### Option 2: Mix and Match

You can have some blocks from CMS and some hardcoded:

```tsx
<HeroSection />
<MarketingMosaicSection />
<BlockRenderer blocks={cmsPage.content_blocks} />
<CTASectionWithBlobs /> {/* Still hardcoded */}
<BlogSection />
```

### Option 3: Different Pages

Create other CMS pages (About, FAQ, etc.) using `/pages/[slug]`:

```tsx
// /apps/web/src/app/[locale]/[slug]/page.tsx
const page = await getPage({ slug: params.slug, locale });
return <BlockRenderer blocks={page.content_blocks} />;
```

## Block Configuration Reference

### ToursListingBlock

```
Listing Type: all | tour | event
Count: 1-12
Sort By: created_at | title | price | -price
Style: grid | carousel | list
```

### PromoBannerBlock

```
Tag: Optional badge text
Title: Required, large heading
Subtitle: Optional description
Primary Button: Label + URL
Secondary Button: Label + URL (optional)
Background Color: primary | secondary | accent | dark
```

### CategoriesGridBlock

```
Categories: Array of:
  - Name: Required
  - Image: Required
  - Count: Optional number
  - URL: Optional link
Displays in 4-column grid (responsive)
```

### CTAWithBlobsBlock

```
Title: Required large heading
Text: Optional description
Button Label: Required
Button URL: Required
Button Variant: primary | secondary | white
```

### CardsBlock (Built-in)

```
Cards: Array of:
  - Title: Required
  - Description: Optional
  - Image: Optional
  - Link: Optional
Columns: 1 | 2 | 3 | 4
```

## Troubleshooting

### Homepage shows fallback sections

**Cause**: HOME page doesn't exist or isn't published

**Fix**:

1. Check page exists with code `HOME`
2. Check publishing dates are valid
3. Check page status is published

### CMS blocks not rendering

**Cause**: Block type mismatch

**Fix**:

1. Check block names match exactly (case-sensitive)
2. Verify blocks are registered in config
3. Check browser console for errors

### Images not loading

**Cause**: Media library not configured

**Fix**:

1. Configure Spatie Media Library disk
2. Ensure images are uploaded correctly
3. Check file permissions

### Translations not working

**Cause**: Content not translated

**Fix**:

1. Edit page in Filament
2. Use language tabs to add FR content
3. Ensure all translatable fields are filled

## Benefits of Hybrid Approach

✅ **Flexibility**: Change middle content without code changes
✅ **Safety**: Complex sections stay in code
✅ **Performance**: No overhead when CMS not used
✅ **Gradual Migration**: Move sections to CMS over time
✅ **Fallback**: Always works even without CMS

## Next Steps

1. ✅ Create HOME page in CMS
2. ✅ Configure your blocks
3. ✅ Test in both EN and FR
4. ✅ Create additional pages (About, FAQ)
5. ✅ Set up menus in CMS
6. ✅ Configure redirects if needed

## Files Modified

### Backend

- `/app/ContentBlocks/PromoBannerBlock.php` (NEW)
- `/app/ContentBlocks/CategoriesGridBlock.php` (NEW)
- `/app/ContentBlocks/CTAWithBlobsBlock.php` (NEW)
- `/resources/views/content-blocks/*.blade.php` (NEW)
- `/config/filament-flexible-content-blocks.php` (UPDATED)

### Frontend

- `/src/components/cms/blocks/PromoBannerBlock.tsx` (NEW)
- `/src/components/cms/blocks/CategoriesGridBlock.tsx` (NEW)
- `/src/components/cms/blocks/CTAWithBlobsBlock.tsx` (NEW)
- `/src/components/cms/BlockRenderer.tsx` (UPDATED)
- `/src/types/cms.ts` (UPDATED)
- `/src/app/[locale]/page.tsx` (UPDATED - hybrid approach)

---

**You're all set!** 🎉

The homepage is now using a hybrid approach. Create your HOME page in the CMS to start managing content, or keep using the hardcoded sections as a fallback.
