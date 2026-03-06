/**
 * BDD Tests for @djerba-fun Package Imports
 *
 * These tests verify that the package rename from @go-adventure to @djerba-fun
 * is complete and all required exports are available.
 *
 * Expected package names:
 * - @djerba-fun/schemas (was @go-adventure/schemas)
 * - @djerba-fun/ui (was @go-adventure/ui)
 */

import { existsSync, readFileSync } from 'fs';
import { join } from 'path';

describe('@djerba-fun/schemas package', () => {
  const packagesRoot = join(__dirname, '../../../packages');
  const schemasPackageJson = join(packagesRoot, 'schemas/package.json');

  it('should have package name @djerba-fun/schemas', () => {
    expect(existsSync(schemasPackageJson)).toBe(true);

    const packageJson = JSON.parse(readFileSync(schemasPackageJson, 'utf-8'));

    expect(packageJson.name).toBe('@djerba-fun/schemas');
    expect(packageJson.name).not.toBe('@go-adventure/schemas');
  });

  it('should export all required schema types', async () => {
    // Dynamic import to test actual exports
    // Note: This test will fail until package rename is complete
    try {
      const schemas = await import('@djerba-fun/schemas');

      // Core schemas should exist
      expect(schemas.listingSchema).toBeDefined();
      expect(schemas.bookingSchema).toBeDefined();
      expect(schemas.userSchema).toBeDefined();

      // Types should be exported
      expect(schemas).toHaveProperty('ListingSummary');
    } catch (e) {
      // If import fails, it means package hasn't been renamed yet
      // This is expected behavior in BDD - test should fail first
      throw new Error(
        '@djerba-fun/schemas import failed - package may still be named @go-adventure/schemas'
      );
    }
  });
});

describe('@djerba-fun/ui package', () => {
  const packagesRoot = join(__dirname, '../../../packages');
  const uiPackageJson = join(packagesRoot, 'ui/package.json');

  it('should have package name @djerba-fun/ui', () => {
    expect(existsSync(uiPackageJson)).toBe(true);

    const packageJson = JSON.parse(readFileSync(uiPackageJson, 'utf-8'));

    expect(packageJson.name).toBe('@djerba-fun/ui');
    expect(packageJson.name).not.toBe('@go-adventure/ui');
  });

  it('should export Button component', async () => {
    try {
      const ui = await import('@djerba-fun/ui');

      expect(ui.Button).toBeDefined();
      expect(ui.Card).toBeDefined();
      expect(ui.Input).toBeDefined();
    } catch (e) {
      throw new Error('@djerba-fun/ui import failed - package may still be named @go-adventure/ui');
    }
  });
});

describe('Root package.json', () => {
  const rootPackageJson = join(__dirname, '../../../../package.json');

  it('should have root package name djerba-fun', () => {
    expect(existsSync(rootPackageJson)).toBe(true);

    const packageJson = JSON.parse(readFileSync(rootPackageJson, 'utf-8'));

    expect(packageJson.name).toBe('djerba-fun');
    expect(packageJson.name).not.toBe('go-adventure');
  });
});

describe('Web app dependencies', () => {
  const webPackageJson = join(__dirname, '../../package.json');

  it('should depend on @djerba-fun packages, not @go-adventure', () => {
    expect(existsSync(webPackageJson)).toBe(true);

    const packageJson = JSON.parse(readFileSync(webPackageJson, 'utf-8'));
    const allDeps = {
      ...packageJson.dependencies,
      ...packageJson.devDependencies,
    };

    // Should have @djerba-fun packages
    expect(allDeps['@djerba-fun/schemas']).toBeDefined();
    expect(allDeps['@djerba-fun/ui']).toBeDefined();

    // Should NOT have @go-adventure packages
    expect(allDeps['@go-adventure/schemas']).toBeUndefined();
    expect(allDeps['@go-adventure/ui']).toBeUndefined();
  });
});

describe('No @go-adventure imports in source code', () => {
  const srcDir = join(__dirname, '../../src');

  it('should not contain @go-adventure import statements', () => {
    // This test checks that all imports have been updated
    const glob = require('glob');
    const files = glob.sync('**/*.{ts,tsx}', { cwd: srcDir, absolute: true });

    const filesWithOldImports: string[] = [];

    for (const file of files) {
      const content = readFileSync(file, 'utf-8');
      if (content.includes('@go-adventure/')) {
        filesWithOldImports.push(file.replace(srcDir, 'src'));
      }
    }

    expect(filesWithOldImports).toEqual([]);
  });
});
