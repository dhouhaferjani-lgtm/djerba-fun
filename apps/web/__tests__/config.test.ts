/**
 * Configuration File Regression Tests
 *
 * Purpose: Prevent duplicate config files (.js and .ts) from existing simultaneously
 * Bug Reference: January 2026 - next.config.js was created alongside next.config.ts,
 * causing Next.js to use .js file which lacked next-intl plugin configuration
 */

import { existsSync } from 'fs';
import { join } from 'path';

describe('Next.js Configuration Files', () => {
  const webAppRoot = join(__dirname, '..');
  const configJs = join(webAppRoot, 'next.config.js');
  const configTs = join(webAppRoot, 'next.config.ts');
  const configMjs = join(webAppRoot, 'next.config.mjs');

  it('should only have ONE Next.js config file', () => {
    const configFiles = [
      { path: configJs, name: 'next.config.js', exists: existsSync(configJs) },
      { path: configTs, name: 'next.config.ts', exists: existsSync(configTs) },
      { path: configMjs, name: 'next.config.mjs', exists: existsSync(configMjs) },
    ];

    const existingConfigs = configFiles.filter((f) => f.exists);

    // Exactly one config file should exist
    expect(existingConfigs).toHaveLength(1);

    // It should be the TypeScript file (project standard)
    expect(existingConfigs[0].name).toBe('next.config.ts');
  });

  it('should NOT have next.config.js (JavaScript)', () => {
    // CRITICAL: JavaScript config files should never exist in this TypeScript project
    expect(existsSync(configJs)).toBe(false);
  });

  it('should have next.config.ts (TypeScript)', () => {
    // REQUIRED: TypeScript config is the project standard
    expect(existsSync(configTs)).toBe(true);
  });
});
