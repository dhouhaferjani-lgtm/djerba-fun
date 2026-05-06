import { test, expect } from '@playwright/test';

/**
 * Browser-level regression for the "[object Object]" rendering bug on the
 * Filament admin EditListing page.
 *
 * Pre-fix: disabled RichEditor::make('description.en') and
 * Textarea::make('summary.en') leaked the parent translatable map to JS.
 * The disabled-input rendering then String'd the map → "[object Object]".
 *
 * Post-fix: Placeholder::content() emits static HTML on the server.
 *
 * Browser-level coverage is required because the bug never appeared in
 * Livewire's server-rendered HTML — it only surfaced after Alpine/TipTap
 * boot in a real browser. PHPUnit cannot catch this; Playwright can.
 */

const ADMIN_LOGIN = 'http://localhost:8100/admin/login';
const ADMIN_EMAIL = 'admin@djerba.fun';
const ADMIN_PASSWORD = 'password';

const JETSKI_SLUG = 'jetski-15-30-min';
const ADMIN_EDIT_URL = `http://localhost:8100/admin/listings/${JETSKI_SLUG}/edit`;

// A "freshly created" listing that has empty description / summary — this is
// the shape the product owner saw in the bug screenshot (vendor wizard saved
// without filling those fields).
const EMPTY_SLUG = 'fresh-empty-test';
const ADMIN_EDIT_EMPTY_URL = `http://localhost:8100/admin/listings/${EMPTY_SLUG}/edit`;

// A listing whose description/summary JSON has the malformed doubly-nested
// shape ({"en": {"en": "value"}}) that earlier Spatie + form bugs sometimes
// produced. This is the actual trigger for the [object Object] rendering
// in the pre-fix Filament admin form, where a disabled RichEditor receives
// {"en": "value"} (still an array) at the description.en path and the JS
// layer toString's it.
const MALFORMED_SLUG = 'malformed-desc-test';
const ADMIN_EDIT_MALFORMED_URL = `http://localhost:8100/admin/listings/${MALFORMED_SLUG}/edit`;

test.describe('Admin EditListing — translatable description display', () => {
  test('admin edit page renders description and summary without "[object Object]"', async ({
    page,
  }) => {
    test.setTimeout(60000);

    await page.goto(ADMIN_LOGIN);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/admin(?!\/login)/, { timeout: 15000 });

    await page.goto(ADMIN_EDIT_URL);
    await page.waitForLoadState('networkidle');

    // Critical regression assertion: the literal string "[object Object]"
    // must NOT appear anywhere on the page. This is what users see when
    // the JS layer toString's an array passed where a string is expected.
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).not.toContain('[object Object]');

    // Sanity: the EN description and EN summary are both visible.
    expect(bodyText).toContain('Experience a jetski adventure');
    expect(bodyText).toMatch(/state-certified instructor|Jetski session supervised/);
  });

  test('admin edit page on a fresh listing with empty description does NOT leak "[object Object]"', async ({
    page,
  }) => {
    test.setTimeout(60000);

    await page.goto(ADMIN_LOGIN);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/admin(?!\/login)/, { timeout: 15000 });

    await page.goto(ADMIN_EDIT_EMPTY_URL);
    await page.waitForLoadState('networkidle');

    const bodyText = await page.locator('body').innerText();
    expect(bodyText).not.toContain('[object Object]');
  });

  test('admin edit page on a listing with MALFORMED nested description does NOT leak "[object Object]"', async ({
    page,
  }) => {
    test.setTimeout(60000);

    await page.goto(ADMIN_LOGIN);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/admin(?!\/login)/, { timeout: 15000 });

    await page.goto(ADMIN_EDIT_MALFORMED_URL);
    await page.waitForLoadState('networkidle');

    // This is the strongest assertion: the listing has a doubly-nested
    // {"en": {"en": "..."}} description that pre-fix would render as
    // "[object Object]" in the disabled RichEditor.
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).not.toContain('[object Object]');
    // The fix uses Spatie getTranslation() which drills through nesting,
    // so the actual text should be visible.
    expect(bodyText).toContain('Doubly nested');
  });
});
