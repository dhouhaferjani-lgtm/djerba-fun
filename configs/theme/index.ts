/**
 * Theme Configuration Exports
 *
 * Import themes from this central location for easier management.
 */

export { default as goAdventureTheme } from './default';
export { default as oceanTheme } from './example-ocean';
export { default as desertCanyonTheme } from './example-desert';

// Re-export types and utilities from UI package for convenience
export type { ThemeConfig } from '@djerba-fun/ui';
export { mergeThemeConfig, themeConfigToCssVariables, defaultTheme } from '@djerba-fun/ui';

/**
 * Get active theme based on environment variable
 *
 * Usage:
 * ```bash
 * # .env.local
 * NEXT_PUBLIC_THEME=ocean
 * ```
 *
 * Then in your layout:
 * ```typescript
 * import { getActiveTheme } from '@/configs/theme';
 * const theme = mergeThemeConfig(getActiveTheme());
 * ```
 */
import goAdventureTheme from './default';
import oceanTheme from './example-ocean';
import desertCanyonTheme from './example-desert';

const themes = {
  default: goAdventureTheme,
  ocean: oceanTheme,
  desert: desertCanyonTheme,
} as const;

export type ThemeName = keyof typeof themes;

export function getActiveTheme(): typeof goAdventureTheme {
  if (typeof process === 'undefined' || !process.env) {
    return goAdventureTheme;
  }

  const themeName = (process.env.NEXT_PUBLIC_THEME || 'default') as ThemeName;
  return themes[themeName] || themes.default;
}
