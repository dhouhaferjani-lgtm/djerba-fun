/**
 * Error Boundary Structure Tests
 *
 * Purpose: Ensure error boundaries don't render nested HTML structure
 * Bug Reference: January 2026 - error.tsx rendered <html> and <body> tags,
 * causing React hydration errors due to nested HTML structure
 */

import { readFileSync } from 'fs';
import { join } from 'path';

describe('Error Boundary HTML Structure', () => {
  const webAppRoot = join(__dirname, '..');
  const errorPagePath = join(webAppRoot, 'src', 'app', 'error.tsx');
  const globalErrorPagePath = join(webAppRoot, 'src', 'app', 'global-error.tsx');
  const notFoundPagePath = join(webAppRoot, 'src', 'app', 'not-found.tsx');

  describe('error.tsx', () => {
    let errorPageContent: string;

    beforeAll(() => {
      errorPageContent = readFileSync(errorPagePath, 'utf-8');
    });

    it('should NOT render <html> tag', () => {
      // CRITICAL: Error boundaries must not render <html>
      const htmlTagPattern = /<html[^>]*>/gi;
      const matches = errorPageContent.match(htmlTagPattern);

      expect(matches).toBeNull();
    });

    it('should NOT render <body> tag', () => {
      // CRITICAL: Error boundaries must not render <body>
      const bodyTagPattern = /<body[^>]*>/gi;
      const matches = errorPageContent.match(bodyTagPattern);

      expect(matches).toBeNull();
    });

    it('should NOT render <head> tag', () => {
      // CRITICAL: Error boundaries must not render <head>
      const headTagPattern = /<head[^>]*>/gi;
      const matches = errorPageContent.match(headTagPattern);

      expect(matches).toBeNull();
    });

    it('should return JSX content directly', () => {
      // Error boundary should return content wrapped in div/fragment
      expect(errorPageContent).toMatch(/return\s*\(/);
      expect(errorPageContent).toMatch(/<div/);
    });
  });

  describe('global-error.tsx (special case)', () => {
    it('should exist for root-level errors', () => {
      const exists = require('fs').existsSync(globalErrorPagePath);
      expect(exists).toBe(true);
    });

    it('MAY include <html> and <body> as it replaces root layout', () => {
      // global-error.tsx is special - it's allowed to have html/body
      // because it replaces the entire root layout
      const globalErrorContent = readFileSync(globalErrorPagePath, 'utf-8');

      // This is OK for global-error.tsx specifically
      expect(globalErrorContent).toMatch(/<html/);
    });
  });

  describe('not-found.tsx', () => {
    let notFoundContent: string;

    beforeAll(() => {
      notFoundContent = readFileSync(notFoundPagePath, 'utf-8');
    });

    it('should NOT render <html> tag', () => {
      // not-found.tsx should also not render html/body
      const htmlTagPattern = /<html[^>]*>/gi;
      const matches = notFoundContent.match(htmlTagPattern);

      expect(matches).toBeNull();
    });

    it('should NOT render <body> tag', () => {
      const bodyTagPattern = /<body[^>]*>/gi;
      const matches = notFoundContent.match(bodyTagPattern);

      expect(matches).toBeNull();
    });
  });
});

/**
 * Documentation test - ensure patterns are documented
 */
describe('Error Boundary Documentation', () => {
  const claudeMdPath = join(__dirname, '..', '..', '..', 'CLAUDE.md');

  it('should document error boundary best practices in CLAUDE.md', () => {
    const claudeMd = readFileSync(claudeMdPath, 'utf-8');

    expect(claudeMd).toContain('Error Boundaries');
    expect(claudeMd).toContain('No Nested HTML');
  });
});
