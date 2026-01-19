/**
 * Footer Component Tests
 *
 * Purpose: Ensure Footer displays correct links aligned with old site (go-adventure.net)
 * and does NOT display removed/broken links
 *
 * BDD Scenarios:
 * - Footer displays correct company links (about, blog, tours, events)
 * - Footer does NOT display removed links (careers, press, partners)
 * - Footer displays correct support links (my account, terms, contact)
 * - Footer does NOT display removed support links (help, faq, cancellation, safety, accessibility)
 * - Footer displays correct contact information (Djerba address)
 * - Footer displays Facebook and Instagram but NOT Twitter
 */

import { readFileSync } from 'fs';
import { join } from 'path';

describe('Footer Component', () => {
  const webAppRoot = join(__dirname, '..', '..');
  const footerPath = join(webAppRoot, 'src', 'components', 'organisms', 'Footer.tsx');
  const enMessagesPath = join(webAppRoot, 'messages', 'en.json');
  const frMessagesPath = join(webAppRoot, 'messages', 'fr.json');

  let footerContent: string;
  let enMessages: Record<string, unknown>;
  let frMessages: Record<string, unknown>;

  beforeAll(() => {
    footerContent = readFileSync(footerPath, 'utf-8');
    enMessages = JSON.parse(readFileSync(enMessagesPath, 'utf-8'));
    frMessages = JSON.parse(readFileSync(frMessagesPath, 'utf-8'));
  });

  describe('Company Section Links', () => {
    it('should have link to About page', () => {
      // About Us link should point to /about (next-intl handles locale automatically)
      expect(footerContent).toContain('href="/about"');
    });

    it('should have link to Blog page', () => {
      // Blog link should exist
      expect(footerContent).toContain('href="/blog"');
    });

    it('should have link to Tours listing', () => {
      // Tours link should point to /listings with type=tour filter (using object syntax for query params)
      expect(footerContent).toContain("pathname: '/listings'");
      expect(footerContent).toContain("type: 'tour'");
    });

    it('should have link to Events listing', () => {
      // Events link should point to /listings with type=event filter
      expect(footerContent).toContain("type: 'event'");
    });

    it('should NOT have link to Careers page (removed)', () => {
      // REGRESSION: Careers link should be removed
      expect(footerContent).not.toContain('/careers');
    });

    it('should NOT have link to Press page (removed)', () => {
      // REGRESSION: Press link should be removed
      expect(footerContent).not.toContain('/press');
    });

    it('should NOT have link to Partners page (removed)', () => {
      // REGRESSION: Partners link should be removed
      expect(footerContent).not.toContain('/partners');
    });
  });

  describe('Support Section Links', () => {
    it('should have link to Dashboard/My Account', () => {
      // My Account link should point to /dashboard
      expect(footerContent).toContain('href="/dashboard"');
    });

    it('should have link to Terms page', () => {
      // Terms link should exist
      expect(footerContent).toContain('href="/terms"');
    });

    it('should have link to Contact page', () => {
      // Contact link should point to /contact
      expect(footerContent).toContain('href="/contact"');
    });

    it('should NOT have link to Help Center (removed)', () => {
      // REGRESSION: Help Center link should be removed
      expect(footerContent).not.toContain('/help"');
    });

    it('should NOT have link to FAQ page (removed)', () => {
      // REGRESSION: FAQ link should be removed
      expect(footerContent).not.toContain('/faq');
    });

    it('should NOT have link to Cancellation page (removed)', () => {
      // REGRESSION: Cancellation link should be removed
      expect(footerContent).not.toContain('/cancellation');
    });

    it('should NOT have link to Safety page (removed)', () => {
      // REGRESSION: Safety link should be removed
      expect(footerContent).not.toContain('/safety');
    });

    it('should NOT have link to Accessibility page (removed)', () => {
      // REGRESSION: Accessibility link should be removed
      expect(footerContent).not.toContain('/accessibility');
    });
  });

  describe('Contact Information', () => {
    it('should display correct phone number in translations', () => {
      // Phone should be +216 52 665 202 (Djerba number from old site)
      const footer = enMessages.footer as Record<string, string>;
      expect(footer.phone).toBe('+216 52 665 202');
    });

    it('should display correct email in translations', () => {
      // Email should be contact@go-adventure.net
      const footer = enMessages.footer as Record<string, string>;
      expect(footer.email).toBe('contact@go-adventure.net');
    });

    it('should display Djerba address in English translations', () => {
      // Address should mention Djerba (not Tunis)
      const footer = enMessages.footer as Record<string, string>;
      expect(footer.address).toContain('Djerba');
    });

    it('should display Djerba address in French translations', () => {
      // Address should mention Djerba (not Tunis)
      const footer = frMessages.footer as Record<string, string>;
      expect(footer.address).toContain('Djerba');
    });

    it('should NOT have hours field in English translations', () => {
      // Hours field should be removed (not in old site footer)
      const footer = enMessages.footer as Record<string, string>;
      expect(footer.hours).toBeUndefined();
    });

    it('should NOT have hours field in French translations', () => {
      // Hours field should be removed (not in old site footer)
      const footer = frMessages.footer as Record<string, string>;
      expect(footer.hours).toBeUndefined();
    });
  });

  describe('Social Media Links', () => {
    it('should have Facebook social icon', () => {
      // Facebook icon should exist
      expect(footerContent).toContain('aria-label="Facebook"');
    });

    it('should have Instagram social icon', () => {
      // Instagram icon should exist
      expect(footerContent).toContain('aria-label="Instagram"');
    });

    it('should NOT have Twitter social icon', () => {
      // REGRESSION: Twitter should be removed (not in old site)
      // Check that Twitter icon/link doesn't exist in the rendered output
      // The fallback section should not include Twitter
      const twitterFallbackPattern = /aria-label="Twitter"/g;
      const matches = footerContent.match(twitterFallbackPattern);

      // Twitter should not appear at all (0 occurrences in fallback section)
      expect(matches).toBeNull();
    });
  });

  describe('Translation Keys', () => {
    it('should have tours translation key in English', () => {
      const footer = enMessages.footer as Record<string, string>;
      expect(footer.tours).toBeDefined();
    });

    it('should have events translation key in English', () => {
      const footer = enMessages.footer as Record<string, string>;
      expect(footer.events).toBeDefined();
    });

    it('should have my_account translation key in English', () => {
      const footer = enMessages.footer as Record<string, string>;
      expect(footer.my_account).toBeDefined();
    });

    it('should have contact_us translation key in English', () => {
      const footer = enMessages.footer as Record<string, string>;
      expect(footer.contact_us).toBeDefined();
    });

    it('should have tours translation key in French', () => {
      const footer = frMessages.footer as Record<string, string>;
      expect(footer.tours).toBeDefined();
    });

    it('should have events translation key in French', () => {
      const footer = frMessages.footer as Record<string, string>;
      expect(footer.events).toBeDefined();
    });

    it('should have my_account translation key in French', () => {
      const footer = frMessages.footer as Record<string, string>;
      expect(footer.my_account).toBeDefined();
    });

    it('should have contact_us translation key in French', () => {
      const footer = frMessages.footer as Record<string, string>;
      expect(footer.contact_us).toBeDefined();
    });

    it('should NOT have careers translation key in English (removed)', () => {
      const footer = enMessages.footer as Record<string, string>;
      expect(footer.careers).toBeUndefined();
    });

    it('should NOT have press translation key in English (removed)', () => {
      const footer = enMessages.footer as Record<string, string>;
      expect(footer.press).toBeUndefined();
    });

    it('should NOT have partners translation key in English (removed)', () => {
      const footer = enMessages.footer as Record<string, string>;
      expect(footer.partners).toBeUndefined();
    });

    it('should NOT have help_center translation key in English (removed)', () => {
      const footer = enMessages.footer as Record<string, string>;
      expect(footer.help_center).toBeUndefined();
    });

    it('should NOT have faq translation key in English (removed)', () => {
      const footer = enMessages.footer as Record<string, string>;
      expect(footer.faq).toBeUndefined();
    });

    it('should NOT have cancellation translation key in English (removed)', () => {
      const footer = enMessages.footer as Record<string, string>;
      expect(footer.cancellation).toBeUndefined();
    });

    it('should NOT have safety translation key in English (removed)', () => {
      const footer = enMessages.footer as Record<string, string>;
      expect(footer.safety).toBeUndefined();
    });

    it('should NOT have accessibility translation key in English (removed)', () => {
      const footer = enMessages.footer as Record<string, string>;
      expect(footer.accessibility).toBeUndefined();
    });
  });
});

describe('Footer Fallback Social Links', () => {
  const webAppRoot = join(__dirname, '..', '..');
  const footerPath = join(webAppRoot, 'src', 'components', 'organisms', 'Footer.tsx');

  let footerContent: string;

  beforeAll(() => {
    footerContent = readFileSync(footerPath, 'utf-8');
  });

  it('should have correct Facebook fallback URL', () => {
    // Fallback should use the actual Go Adventure Facebook page
    expect(footerContent).toContain('facebook.com/people/LAventurier/61560766694725');
  });

  it('should have correct Instagram fallback URL', () => {
    // Fallback should use the actual Go Adventure Instagram page
    expect(footerContent).toContain('instagram.com/laventurier.tn');
  });
});
