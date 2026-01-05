/**
 * Next-Intl Plugin Configuration Tests
 *
 * Purpose: Ensure next-intl plugin is properly configured in Next.js config
 * Bug Reference: January 2026 - next.config.js lacked next-intl plugin wrapper,
 * causing "Couldn't find next-intl config file" runtime errors
 */

import { readFileSync } from 'fs';
import { join } from 'path';

describe('Next-Intl Plugin Configuration', () => {
  const webAppRoot = join(__dirname, '..');
  const configPath = join(webAppRoot, 'next.config.ts');

  let configContent: string;

  beforeAll(() => {
    configContent = readFileSync(configPath, 'utf-8');
  });

  it('should import createNextIntlPlugin', () => {
    // CRITICAL: Must import the plugin creator
    expect(configContent).toMatch(/import.*createNextIntlPlugin.*from.*['"]next-intl\/plugin['"]/);
  });

  it('should create withNextIntl plugin instance', () => {
    // CRITICAL: Must create the plugin wrapper
    expect(configContent).toMatch(/const\s+withNextIntl\s*=\s*createNextIntlPlugin\(/);
  });

  it('should specify correct i18n request path', () => {
    // Must point to the correct i18n configuration file
    expect(configContent).toMatch(/createNextIntlPlugin\(['"]\.\/src\/i18n\/request\.ts['"]\)/);
  });

  it('should wrap nextConfig with withNextIntl plugin', () => {
    // CRITICAL: Export must wrap config with next-intl plugin
    expect(configContent).toMatch(/export\s+default.*withNextIntl\s*\(/);
  });

  it('should NOT have standalone export without plugin wrapper', () => {
    // Anti-pattern: Exporting config without next-intl wrapper
    const standaloneExport = /export\s+default\s+nextConfig\s*;/;
    expect(configContent).not.toMatch(standaloneExport);
  });
});
