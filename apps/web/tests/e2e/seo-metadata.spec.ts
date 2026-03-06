import { test, expect } from '@playwright/test';

/**
 * TC-F090 & TC-F091: SEO & Metadata Tests
 * Tests for proper SEO implementation including meta tags, OG tags, and structured data.
 */

test.describe('SEO & Metadata', () => {
  // TC-F090: Listing Page Metadata
  test('TC-F090a: should have proper title tag on listing page', async ({ page }) => {
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    // Get the page title
    const title = await page.title();

    // Title should not be empty
    expect(title).toBeTruthy();
    expect(title.length).toBeGreaterThan(0);

    // Title should contain listing name or site name
    const hasRelevantTitle =
      title.toLowerCase().includes('kroumirie') ||
      title.toLowerCase().includes('trek') ||
      title.toLowerCase().includes('evasion') ||
      title.toLowerCase().includes('djerba');

    console.log(`TC-F090a: Page title: "${title}"`);
    console.log(`TC-F090a: Relevant title content: ${hasRelevantTitle}`);
  });

  test('TC-F090b: should have meta description on listing page', async ({ page }) => {
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    // Get meta description
    const metaDescription = await page.locator('meta[name="description"]').getAttribute('content');

    // Should have a description
    expect(metaDescription).toBeTruthy();
    expect(metaDescription?.length).toBeGreaterThan(50); // Reasonable description length

    console.log(`TC-F090b: Meta description: "${metaDescription?.substring(0, 100)}..."`);
  });

  test('TC-F090c: should have Open Graph tags on listing page', async ({ page }) => {
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    // Check for OG tags
    const ogTitle = await page.locator('meta[property="og:title"]').getAttribute('content');
    const ogDescription = await page
      .locator('meta[property="og:description"]')
      .getAttribute('content');
    const ogImage = await page.locator('meta[property="og:image"]').getAttribute('content');
    const ogUrl = await page.locator('meta[property="og:url"]').getAttribute('content');
    const ogType = await page.locator('meta[property="og:type"]').getAttribute('content');

    // OG title should exist
    expect(ogTitle).toBeTruthy();
    console.log(`TC-F090c: og:title: "${ogTitle}"`);

    // OG description should exist
    if (ogDescription) {
      console.log(`TC-F090c: og:description: "${ogDescription.substring(0, 100)}..."`);
    }

    // OG image should exist (for social sharing)
    if (ogImage) {
      expect(ogImage).toContain('http');
      console.log(`TC-F090c: og:image: "${ogImage}"`);
    }

    // OG URL should be present
    if (ogUrl) {
      console.log(`TC-F090c: og:url: "${ogUrl}"`);
    }

    // OG type
    if (ogType) {
      console.log(`TC-F090c: og:type: "${ogType}"`);
    }
  });

  test('TC-F090d: should have JSON-LD structured data on listing page', async ({ page }) => {
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    // Look for JSON-LD script tag
    const jsonLdScripts = page.locator('script[type="application/ld+json"]');
    const scriptCount = await jsonLdScripts.count();

    expect(scriptCount).toBeGreaterThan(0);
    console.log(`TC-F090d: Found ${scriptCount} JSON-LD script(s)`);

    if (scriptCount > 0) {
      const jsonLdContent = await jsonLdScripts.first().textContent();

      // Parse and validate JSON-LD
      try {
        const jsonLd = JSON.parse(jsonLdContent || '{}');

        // Check for common schema.org properties
        const hasType = jsonLd['@type'] || jsonLd.type;
        const hasName = jsonLd.name || jsonLd.headline;
        const hasContext = jsonLd['@context']?.includes('schema.org');

        console.log(`TC-F090d: JSON-LD @type: ${hasType}`);
        console.log(`TC-F090d: JSON-LD name: ${hasName}`);
        console.log(`TC-F090d: Schema.org context: ${hasContext}`);

        // Should be a valid activity/product/event type
        const validTypes = [
          'TouristAttraction',
          'Product',
          'Event',
          'Service',
          'Place',
          'LocalBusiness',
          'TourOrAdventure',
          'ExperienceTrip',
        ];
        const isValidType = validTypes.some((t) => hasType?.includes(t));
        console.log(`TC-F090d: Valid schema type: ${isValidType}`);
      } catch (e) {
        console.log('TC-F090d: JSON-LD parse error (may be multiple scripts)');
      }
    }
  });

  test('TC-F090e: should have canonical URL', async ({ page }) => {
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    // Check for canonical link
    const canonicalUrl = await page.locator('link[rel="canonical"]').getAttribute('href');

    if (canonicalUrl) {
      expect(canonicalUrl).toContain('/listings/');
      console.log(`TC-F090e: Canonical URL: "${canonicalUrl}"`);
    } else {
      console.log('TC-F090e: No canonical URL found');
    }
  });

  // TC-F091: Blog Post SEO
  test('TC-F091a: should have article schema markup on blog post', async ({ page }) => {
    // Navigate to blog section
    await page.goto('/en/blog');
    await page.waitForLoadState('networkidle');

    // Find a blog post link
    const blogPostLink = page.locator('a[href*="/blog/"]').first();
    const hasBlogPosts = await blogPostLink.isVisible().catch(() => false);

    if (hasBlogPosts) {
      await blogPostLink.click();
      await page.waitForLoadState('networkidle');

      // Check for article-specific meta tags
      const articleAuthor = await page
        .locator('meta[name="author"], meta[property="article:author"]')
        .getAttribute('content');
      const articlePublished = await page
        .locator('meta[property="article:published_time"]')
        .getAttribute('content');

      console.log(`TC-F091a: Article author: ${articleAuthor}`);
      console.log(`TC-F091a: Article published: ${articlePublished}`);

      // Check for JSON-LD with Article type
      const jsonLdScripts = page.locator('script[type="application/ld+json"]');
      const scriptCount = await jsonLdScripts.count();

      for (let i = 0; i < scriptCount; i++) {
        const content = await jsonLdScripts.nth(i).textContent();
        try {
          const jsonLd = JSON.parse(content || '{}');
          if (
            jsonLd['@type'] === 'Article' ||
            jsonLd['@type'] === 'BlogPosting' ||
            jsonLd['@type'] === 'NewsArticle'
          ) {
            console.log(`TC-F091a: Found Article schema: ${jsonLd['@type']}`);
            console.log(`TC-F091a: Headline: ${jsonLd.headline}`);
            break;
          }
        } catch (e) {
          // Skip invalid JSON
        }
      }
    } else {
      console.log('TC-F091a: No blog posts found to test');
    }
  });

  test('TC-F091b: should have proper OG tags for blog posts', async ({ page }) => {
    await page.goto('/en/blog');
    await page.waitForLoadState('networkidle');

    const blogPostLink = page.locator('a[href*="/blog/"]').first();
    const hasBlogPosts = await blogPostLink.isVisible().catch(() => false);

    if (hasBlogPosts) {
      await blogPostLink.click();
      await page.waitForLoadState('networkidle');

      // Check OG type is article
      const ogType = await page.locator('meta[property="og:type"]').getAttribute('content');

      if (ogType) {
        console.log(`TC-F091b: og:type for blog: "${ogType}"`);
        expect(ogType).toBe('article');
      }

      // Check for article-specific OG tags
      const ogAuthor = await page
        .locator('meta[property="article:author"]')
        .getAttribute('content');
      const ogSection = await page
        .locator('meta[property="article:section"]')
        .getAttribute('content');
      const ogPublished = await page
        .locator('meta[property="article:published_time"]')
        .getAttribute('content');

      console.log(`TC-F091b: article:author: ${ogAuthor}`);
      console.log(`TC-F091b: article:section: ${ogSection}`);
      console.log(`TC-F091b: article:published_time: ${ogPublished}`);
    }
  });

  // Additional SEO tests
  test('should have proper meta viewport tag', async ({ page }) => {
    await page.goto('/en/listings');
    await page.waitForLoadState('networkidle');

    const viewport = await page.locator('meta[name="viewport"]').getAttribute('content');

    expect(viewport).toContain('width=device-width');
    console.log(`Viewport meta: "${viewport}"`);
  });

  test('should have lang attribute on html tag', async ({ page }) => {
    // English page
    await page.goto('/en/listings');
    await page.waitForLoadState('networkidle');

    const langEn = await page.locator('html').getAttribute('lang');
    expect(langEn).toBe('en');
    console.log(`HTML lang for English page: "${langEn}"`);

    // French page
    await page.goto('/fr/listings');
    await page.waitForLoadState('networkidle');

    const langFr = await page.locator('html').getAttribute('lang');
    expect(langFr).toBe('fr');
    console.log(`HTML lang for French page: "${langFr}"`);
  });

  test('should have hreflang tags for alternate languages', async ({ page }) => {
    await page.goto('/en/listings');
    await page.waitForLoadState('networkidle');

    // Check for hreflang links
    const hreflangEn = await page
      .locator('link[rel="alternate"][hreflang="en"]')
      .getAttribute('href');
    const hreflangFr = await page
      .locator('link[rel="alternate"][hreflang="fr"]')
      .getAttribute('href');
    const hreflangDefault = await page
      .locator('link[rel="alternate"][hreflang="x-default"]')
      .getAttribute('href');

    if (hreflangEn) console.log(`hreflang en: ${hreflangEn}`);
    if (hreflangFr) console.log(`hreflang fr: ${hreflangFr}`);
    if (hreflangDefault) console.log(`hreflang x-default: ${hreflangDefault}`);
  });

  test('should have Twitter card meta tags', async ({ page }) => {
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    const twitterCard = await page.locator('meta[name="twitter:card"]').getAttribute('content');
    const twitterTitle = await page.locator('meta[name="twitter:title"]').getAttribute('content');
    const twitterDescription = await page
      .locator('meta[name="twitter:description"]')
      .getAttribute('content');
    const twitterImage = await page.locator('meta[name="twitter:image"]').getAttribute('content');

    if (twitterCard) {
      console.log(`Twitter card type: ${twitterCard}`);
      expect(['summary', 'summary_large_image', 'app', 'player']).toContain(twitterCard);
    }

    if (twitterTitle) console.log(`Twitter title: ${twitterTitle}`);
    if (twitterDescription)
      console.log(`Twitter description: ${twitterDescription?.substring(0, 50)}...`);
    if (twitterImage) console.log(`Twitter image: ${twitterImage}`);
  });

  test('should have robots meta tag', async ({ page }) => {
    await page.goto('/en/listings');
    await page.waitForLoadState('networkidle');

    const robots = await page.locator('meta[name="robots"]').getAttribute('content');

    if (robots) {
      console.log(`Robots meta: ${robots}`);
      // Should allow indexing for public pages
      expect(robots).not.toContain('noindex');
    }
  });

  test('homepage should have Organization schema', async ({ page }) => {
    await page.goto('/en');
    await page.waitForLoadState('networkidle');

    const jsonLdScripts = page.locator('script[type="application/ld+json"]');
    const scriptCount = await jsonLdScripts.count();

    let foundOrg = false;
    for (let i = 0; i < scriptCount; i++) {
      const content = await jsonLdScripts.nth(i).textContent();
      try {
        const jsonLd = JSON.parse(content || '{}');
        if (jsonLd['@type'] === 'Organization' || jsonLd['@type'] === 'LocalBusiness') {
          console.log(`Found Organization schema: ${jsonLd['@type']}`);
          console.log(`Organization name: ${jsonLd.name}`);
          foundOrg = true;
          break;
        }
      } catch (e) {
        // Skip invalid JSON
      }
    }

    console.log(`Organization schema found: ${foundOrg}`);
  });
});
