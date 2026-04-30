/**
 * Admin Panel — AvailabilityRule price-override "Person type" dropdown.
 *
 * Regression guard for the bug shipped in 6addc39: inside the doubly-nested
 * Repeater (time_slots → price_overrides.person_types), the prior closure
 * climbed `Get('../../listing_id')` — only 2 segments — which could not
 * reach the form-root listing_id. Dropdown rendered empty in production.
 *
 * The Filament-side PHPUnit test
 *   tests/Feature/Filament/AvailabilityRuleResourceTest::test_edit_form_options_resolve_listing_person_types
 * already locks the closure resolution. This spec is the browser-level
 * canary that catches anything Filament's component test cannot — actual
 * DOM rendering of the <select>, real Livewire add-row roundtrip, and the
 * post-save hydration that re-populates options.
 *
 * Filament admin lives on the Laravel API host. The dev compose shifts the
 * API host port +100 to coexist with another local project, so the URL is
 * configurable via ADMIN_BASE_URL with a sensible default.
 *
 * The spec assumes a seeded AvailabilityRule with id=1 whose listing has
 * pricing.person_types defined (adult/child/infant). Run
 *   php artisan db:seed
 * (or the project's standard dev seed) before invoking.
 */

import { test, expect, Page } from '@playwright/test';

const ADMIN_BASE_URL = process.env.ADMIN_BASE_URL ?? 'http://localhost:8100/admin';
const RULE_ID = process.env.E2E_AVAILABILITY_RULE_ID ?? '1';

/**
 * Log into the Filament Admin panel using the data.email / data.password
 * field IDs that Filament 3 emits.
 */
async function loginToAdminPanel(
  page: Page,
  email: string = 'admin@djerba.fun',
  password: string = 'password'
): Promise<void> {
  await page.context().clearCookies();
  await page.goto(`${ADMIN_BASE_URL}/login`);
  await page.waitForSelector('#data\\.email', { state: 'visible', timeout: 15000 });
  await page.fill('#data\\.email', email);
  await page.fill('#data\\.password', password);
  await page.click('button[type="submit"]');
  await page.waitForURL((url) => /\/admin(?!\/login)/.test(url.pathname), { timeout: 15000 });
}

/**
 * Read the option list of the Filament Select by its wire:model attribute.
 * The Select uses `wire:model` (no modifier) — confirmed against Filament 3
 * via DOM inspection. Returns [{value, text}, ...].
 */
async function getPersonTypeSelectOptions(page: Page): Promise<{ value: string; text: string }[]> {
  return await page.evaluate(() => {
    const select = document.querySelector<HTMLSelectElement>(
      'select[wire\\:model^="data.time_slots"][wire\\:model$=".key"]'
    );
    if (!select) return [];
    return Array.from(select.options).map((o) => ({
      value: o.value,
      text: (o.textContent ?? '').trim(),
    }));
  });
}

test.describe('Admin AvailabilityRule — price-override Person type dropdown', () => {
  test.setTimeout(60000);

  test.beforeEach(async ({ page }) => {
    await loginToAdminPanel(page);
  });

  test('dropdown populates with the listing person_types when a price-override row is added', async ({
    page,
  }) => {
    await page.goto(`${ADMIN_BASE_URL}/availability-rules/${RULE_ID}/edit`);
    await page.waitForLoadState('networkidle');

    // Wait for the form to render its Schedule section (proxy for full mount).
    await expect(page.getByRole('heading', { name: 'Schedule' }).first()).toBeVisible({
      timeout: 15000,
    });

    // If a price override row already exists from a prior run / seed, no need
    // to click Add — the existing row's Select already exercises the bug.
    const existingSelectCount = await page
      .locator('select[wire\\:model^="data.time_slots"][wire\\:model$=".key"]')
      .count();

    if (existingSelectCount === 0) {
      await page.getByRole('button', { name: 'Add a person-type override' }).first().click();
      await page.waitForSelector('select[wire\\:model^="data.time_slots"][wire\\:model$=".key"]', {
        state: 'visible',
        timeout: 5000,
      });
    }

    const options = await getPersonTypeSelectOptions(page);
    const optionValues = options.map((o) => o.value).filter(Boolean);
    const optionTexts = options.map((o) => o.text);

    // Bug regression guard: dropdown must NOT be empty.
    expect(optionValues.length, 'Person type dropdown must not be empty').toBeGreaterThan(0);

    // Assertion is structural rather than enumerated — different seed
    // configurations may have more or fewer person_types, but the bug shape
    // we're guarding against is "zero options". Each present option must have
    // a non-empty visible label (no `ucfirst('')` blanks).
    for (const opt of options.filter((o) => o.value !== '')) {
      expect(opt.text.length, `Option for "${opt.value}" must have a label`).toBeGreaterThan(0);
    }
  });

  test('save round-trip — picking a person type, saving, and reloading rehydrates the dropdown with options still populated', async ({
    page,
  }) => {
    await page.goto(`${ADMIN_BASE_URL}/availability-rules/${RULE_ID}/edit`);
    await page.waitForLoadState('networkidle');
    await expect(page.getByRole('heading', { name: 'Schedule' }).first()).toBeVisible({
      timeout: 15000,
    });

    const existingSelectCount = await page
      .locator('select[wire\\:model^="data.time_slots"][wire\\:model$=".key"]')
      .count();

    if (existingSelectCount === 0) {
      await page.getByRole('button', { name: 'Add a person-type override' }).first().click();
      await page.waitForSelector('select[wire\\:model^="data.time_slots"][wire\\:model$=".key"]', {
        state: 'visible',
        timeout: 5000,
      });
    }

    // Pick the first non-placeholder option so the test doesn't depend on a
    // specific seed value being present.
    const optionsBeforeSave = await getPersonTypeSelectOptions(page);
    const firstReal = optionsBeforeSave.find((o) => o.value !== '');
    expect(firstReal, 'There must be at least one selectable option').toBeTruthy();
    const pickedValue = firstReal!.value;

    await page.selectOption(
      'select[wire\\:model^="data.time_slots"][wire\\:model$=".key"]',
      pickedValue
    );

    // TND / EUR inputs use Filament's accessible spinbutton role with the
    // visible labels "TND price"/"EUR price" — this is stable across
    // wire:model modifier changes (Filament uses .blur on these fields).
    const tnd = page.getByRole('spinbutton', { name: /TND price/i }).first();
    const eur = page.getByRole('spinbutton', { name: /EUR price/i }).first();
    await tnd.fill('150');
    await eur.fill('48');

    await page.getByRole('button', { name: 'Save changes' }).click();
    await page.waitForLoadState('networkidle');

    // Reload to confirm round-trip from DB.
    await page.goto(`${ADMIN_BASE_URL}/availability-rules/${RULE_ID}/edit`);
    await page.waitForLoadState('networkidle');
    await expect(page.getByRole('heading', { name: 'Schedule' }).first()).toBeVisible({
      timeout: 15000,
    });

    await page.waitForSelector('select[wire\\:model^="data.time_slots"][wire\\:model$=".key"]', {
      state: 'visible',
      timeout: 5000,
    });

    // After reload: the saved key hydrates AND the options must STILL populate.
    const optionsAfterReload = await getPersonTypeSelectOptions(page);
    const reloadedValues = optionsAfterReload.map((o) => o.value);
    expect(
      reloadedValues.filter((v) => v !== '').length,
      'Options must still populate on reload'
    ).toBeGreaterThan(0);
    expect(reloadedValues).toContain(pickedValue);

    const selectedValue = await page
      .locator('select[wire\\:model^="data.time_slots"][wire\\:model$=".key"]')
      .first()
      .inputValue();
    expect(selectedValue).toBe(pickedValue);
  });
});
