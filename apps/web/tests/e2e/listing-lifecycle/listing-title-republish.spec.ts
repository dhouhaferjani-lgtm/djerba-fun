/**
 * Listing French-title — public render E2E
 *
 * Client ticket (live djerbafun.com): a published listing is set to DRAFT, its
 * FRENCH title is edited in the vendor panel, then re-published. The public
 * French site kept showing the OLD title.
 *
 * Root cause (fixed in apps/laravel-api, see ListingTitleRepublishTest):
 *   1. MOUNT — the vendor edit form mixes explicit per-locale fields
 *      (Title (English) / Title (French)) with the Spatie Translatable plugin,
 *      which hydrates each translatable attribute with only the ACTIVE locale's
 *      string. The per-locale fields rendered EMPTY, so the vendor could not see
 *      or edit existing translations (EditListing::mutateFormDataBeforeFill now
 *      repopulates the full {en, fr} arrays).
 *   2. SAVE — the concern persisted only the active locale; a French edit was
 *      dropped (EditListing::handleRecordUpdate now fills every locale).
 *   3. CACHE — ListingController::show cached per listing with no invalidation
 *      (Listing::saved now busts it).
 *
 * The vendor edit + save persistence is covered deterministically by the PHPUnit
 * Livewire test (tests/Feature/Filament/Admin/ListingTitleRepublishTest.php),
 * which is the reliable layer for Filament wizard forms. This Playwright spec
 * covers the USER-VISIBLE half: the public French listing page must render the
 * listing's current French title (served by the API, which the PHPUnit test
 * also asserts is busted from cache on update).
 *
 * Requires a stack where the Next.js server can reach the API (i.e. a server-side
 * API URL that resolves from inside the web server — the local docker dev compose
 * currently only sets the browser-facing localhost:8100, so SSR cannot fetch
 * there; run this against CI/staging or a stack with an internal API URL).
 *
 *   pnpm exec playwright test -g "French title renders" --project=chromium
 */

import { test, expect } from '@playwright/test';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';
const FRONTEND_URL = process.env.NEXT_PUBLIC_URL || 'http://localhost:3000';

test.describe('Public French listing page renders the current French title', () => {
  test('TC-TITLE-FR-001: published listing French title renders on the public fr page', async ({
    page,
    request,
  }) => {
    // Pick any published listing from the API and read its French title +
    // location slug (the canonical public URL is location-first: /{location}/{slug}).
    const listResp = await request.get(`${API_URL}/listings`, {
      headers: { 'Accept-Language': 'fr' },
    });
    expect(listResp.ok(), 'listings API should respond').toBeTruthy();
    const listings = (await listResp.json()).data as Array<{ slug: string }>;
    expect(listings.length, 'need at least one published listing').toBeGreaterThan(0);

    const slug = listings[0].slug;
    const detailResp = await request.get(`${API_URL}/listings/${slug}`, {
      headers: { 'Accept-Language': 'fr' },
    });
    expect(detailResp.ok()).toBeTruthy();
    const listing = (await detailResp.json()).data as {
      slug: string;
      title: string;
      location?: { name?: string };
    };

    const frenchTitle = listing.title;
    expect(frenchTitle, 'API must return a French title').toBeTruthy();

    // French is the default locale (no /en prefix). Try location-first canonical
    // URL, fall back to the legacy /listings/{slug} route.
    const locationSlug = (listing.location?.name || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[̀-ͯ]/g, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '');

    await page.goto(`${FRONTEND_URL}/${locationSlug}/${slug}`);
    if (
      await page
        .getByText('Listing Not Found')
        .isVisible({ timeout: 3000 })
        .catch(() => false)
    ) {
      await page.goto(`${FRONTEND_URL}/listings/${slug}`);
    }
    await page.waitForLoadState('networkidle');

    // The page heading must show the API's current French title.
    await expect(page.getByRole('heading', { name: frenchTitle }).first()).toBeVisible({
      timeout: 15000,
    });
  });
});
