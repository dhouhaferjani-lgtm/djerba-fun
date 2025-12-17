'use client';

import React, { useEffect } from 'react';
import type { ThemeConfig } from '../../tokens';
import { themeConfigToCssVariables } from '../../tokens';

export interface ThemeProviderProps {
  /** The theme configuration to apply */
  theme: ThemeConfig;
  /** Child components */
  children: React.ReactNode;
  /**
   * Where to inject the theme CSS variables
   * - 'inline': Inject via <style> tag in head (client-side only)
   * - 'attribute': Set CSS variables on document root (SSR compatible)
   * @default 'inline'
   */
  mode?: 'inline' | 'attribute';
}

/**
 * ThemeProvider Component
 *
 * Injects theme CSS variables into the document, enabling runtime theme switching.
 *
 * @example
 * ```tsx
 * import { ThemeProvider, defaultTheme } from '@go-adventure/ui';
 *
 * export default function App() {
 *   return (
 *     <ThemeProvider theme={defaultTheme}>
 *       <YourApp />
 *     </ThemeProvider>
 *   );
 * }
 * ```
 *
 * @example Custom theme
 * ```tsx
 * import { ThemeProvider, mergeThemeConfig } from '@go-adventure/ui';
 * import customTheme from '@/configs/theme/custom';
 *
 * const theme = mergeThemeConfig(customTheme);
 *
 * export default function App() {
 *   return (
 *     <ThemeProvider theme={theme}>
 *       <YourApp />
 *     </ThemeProvider>
 *   );
 * }
 * ```
 */
export function ThemeProvider({ theme, children, mode = 'inline' }: ThemeProviderProps) {
  useEffect(() => {
    if (mode === 'inline') {
      // Inject CSS variables via style tag
      const styleId = 'go-adventure-theme';
      let styleElement = document.getElementById(styleId) as HTMLStyleElement;

      if (!styleElement) {
        styleElement = document.createElement('style');
        styleElement.id = styleId;
        document.head.appendChild(styleElement);
      }

      styleElement.textContent = themeConfigToCssVariables(theme);

      // Cleanup on unmount
      return () => {
        const el = document.getElementById(styleId);
        if (el) {
          el.remove();
        }
      };
    } else if (mode === 'attribute') {
      // Set CSS variables directly on :root
      const root = document.documentElement;

      // Primary colors
      root.style.setProperty('--primary', theme.colors.primary.DEFAULT);
      root.style.setProperty('--primary-light', theme.colors.primary.light);
      root.style.setProperty('--primary-dark', theme.colors.primary.dark);

      // Secondary colors
      root.style.setProperty('--secondary', theme.colors.secondary.DEFAULT);
      root.style.setProperty('--secondary-light', theme.colors.secondary.light);
      root.style.setProperty('--secondary-dark', theme.colors.secondary.dark);

      // Accent colors
      root.style.setProperty('--accent', theme.colors.accent.DEFAULT);
      root.style.setProperty('--accent-light', theme.colors.accent.light);
      root.style.setProperty('--accent-dark', theme.colors.accent.dark);

      // Cream
      if (theme.colors.cream) {
        root.style.setProperty('--cream', theme.colors.cream);
      }

      // Neutral colors
      if (theme.colors.neutral) {
        if (theme.colors.neutral.white)
          root.style.setProperty('--neutral-white', theme.colors.neutral.white);
        if (theme.colors.neutral.light)
          root.style.setProperty('--neutral-light', theme.colors.neutral.light);
        if (theme.colors.neutral.DEFAULT)
          root.style.setProperty('--neutral', theme.colors.neutral.DEFAULT);
        if (theme.colors.neutral.dark)
          root.style.setProperty('--neutral-dark', theme.colors.neutral.dark);
        if (theme.colors.neutral.darker)
          root.style.setProperty('--neutral-darker', theme.colors.neutral.darker);
        if (theme.colors.neutral.black)
          root.style.setProperty('--neutral-black', theme.colors.neutral.black);
      }

      // Semantic colors
      if (theme.colors.success) root.style.setProperty('--success', theme.colors.success);
      if (theme.colors.warning) root.style.setProperty('--warning', theme.colors.warning);
      if (theme.colors.error) root.style.setProperty('--error', theme.colors.error);
      if (theme.colors.info) root.style.setProperty('--info', theme.colors.info);

      // Cleanup on unmount (reset to default)
      return () => {
        // Note: We don't actually reset on unmount in production
        // because the theme should persist
      };
    }
  }, [theme, mode]);

  return <>{children}</>;
}
