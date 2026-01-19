/**
 * About Page Tests
 *
 * Purpose: Ensure About page exists and has proper structure
 *
 * BDD Scenarios:
 * - About page file exists at correct location
 * - About page exports metadata for SEO
 * - About page has proper locale parameter handling
 * - Translations exist for about page content
 */

import { existsSync, readFileSync } from 'fs';
import { join } from 'path';

describe('About Page', () => {
  const webAppRoot = join(__dirname, '..', '..');
  const aboutPagePath = join(webAppRoot, 'src', 'app', '[locale]', 'about', 'page.tsx');
  const enMessagesPath = join(webAppRoot, 'messages', 'en.json');
  const frMessagesPath = join(webAppRoot, 'messages', 'fr.json');

  describe('Page File Structure', () => {
    it('should exist at correct location', () => {
      // About page must exist at apps/web/src/app/[locale]/about/page.tsx
      const exists = existsSync(aboutPagePath);
      expect(exists).toBe(true);
    });

    it('should be a valid TypeScript/React file', () => {
      const content = readFileSync(aboutPagePath, 'utf-8');
      // Should have default export
      expect(content).toMatch(/export\s+default/);
    });

    it('should handle locale parameter', () => {
      const content = readFileSync(aboutPagePath, 'utf-8');
      // Should receive params with locale
      expect(content).toMatch(/params.*locale|locale.*params/);
    });
  });

  describe('SEO Metadata', () => {
    it('should export generateMetadata function', () => {
      const content = readFileSync(aboutPagePath, 'utf-8');
      // About page should have SEO metadata
      const hasMetadata =
        content.includes('generateMetadata') || content.includes('export const metadata');
      expect(hasMetadata).toBe(true);
    });
  });

  describe('Translations', () => {
    it('should have about section in English translations', () => {
      const enMessages = JSON.parse(readFileSync(enMessagesPath, 'utf-8'));
      expect(enMessages.about).toBeDefined();
    });

    it('should have about section in French translations', () => {
      const frMessages = JSON.parse(readFileSync(frMessagesPath, 'utf-8'));
      expect(frMessages.about).toBeDefined();
    });

    it('should have page title in English', () => {
      const enMessages = JSON.parse(readFileSync(enMessagesPath, 'utf-8'));
      expect(enMessages.about?.title || enMessages.about?.page_title).toBeDefined();
    });

    it('should have page title in French', () => {
      const frMessages = JSON.parse(readFileSync(frMessagesPath, 'utf-8'));
      expect(frMessages.about?.title || frMessages.about?.page_title).toBeDefined();
    });

    it('should have hero section content in English', () => {
      const enMessages = JSON.parse(readFileSync(enMessagesPath, 'utf-8'));
      // Should have hero/intro content from old site: "Explorer autrement"
      const hasHeroContent =
        enMessages.about?.hero_title ||
        enMessages.about?.tagline ||
        enMessages.about?.hero_subtitle;
      expect(hasHeroContent).toBeDefined();
    });

    it('should have hero section content in French', () => {
      const frMessages = JSON.parse(readFileSync(frMessagesPath, 'utf-8'));
      // Should have hero/intro content: "Explorer autrement"
      const hasHeroContent =
        frMessages.about?.hero_title ||
        frMessages.about?.tagline ||
        frMessages.about?.hero_subtitle;
      expect(hasHeroContent).toBeDefined();
    });

    it('should have founder story section in translations', () => {
      const enMessages = JSON.parse(readFileSync(enMessagesPath, 'utf-8'));
      // Should have founder story content (Seif Ben Helel)
      const hasFounderContent =
        enMessages.about?.founder_name ||
        enMessages.about?.founder_story ||
        enMessages.about?.our_story;
      expect(hasFounderContent).toBeDefined();
    });

    it('should have core values/commitments in translations', () => {
      const enMessages = JSON.parse(readFileSync(enMessagesPath, 'utf-8'));
      // Should have sustainable tourism, active lifestyle, local immersion content
      const hasValues =
        enMessages.about?.values ||
        enMessages.about?.commitments ||
        enMessages.about?.sustainable ||
        enMessages.about?.active_lifestyle;
      expect(hasValues).toBeDefined();
    });
  });
});

describe('About Page Accessibility', () => {
  const webAppRoot = join(__dirname, '..', '..');
  const aboutPagePath = join(webAppRoot, 'src', 'app', '[locale]', 'about', 'page.tsx');

  it('should not throw errors when file is read', () => {
    expect(() => {
      readFileSync(aboutPagePath, 'utf-8');
    }).not.toThrow();
  });
});
