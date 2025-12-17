import type { ThemeConfig } from '@go-adventure/ui';

/**
 * Default Go Adventure Theme
 *
 * This is the default theme used by the Go Adventure platform.
 * Export this from your theme config to use the default branding.
 */
export const goAdventureTheme: ThemeConfig = {
  name: 'Go Adventure Default',
  description: 'Default Go Adventure tourism marketplace theme with forest green and lime accents',

  colors: {
    primary: {
      DEFAULT: '#0D642E',
      light: '#1a7e45',
      dark: '#0a5025',
    },
    secondary: {
      DEFAULT: '#8BC34A',
      light: '#a2d16a',
      dark: '#7cb342',
    },
    accent: {
      DEFAULT: '#f5f0d1',
      light: '#faf8e8',
      dark: '#e8e2bc',
    },
    neutral: {
      white: '#ffffff',
      light: '#f5f5f5',
      DEFAULT: '#e5e5e5',
      dark: '#a3a3a3',
      darker: '#737373',
      black: '#000000',
    },
    cream: '#fcfaf2',
    success: '#22c55e',
    warning: '#f59e0b',
    error: '#ef4444',
    info: '#3b82f6',
  },

  typography: {
    fontFamily: {
      sans: ['Inter', 'system-ui', 'sans-serif'],
      display: ['Poppins', 'system-ui', 'sans-serif'],
    },
    fontWeight: {
      normal: '400',
      medium: '500',
      semibold: '600',
      bold: '700',
    },
  },

  borderRadius: {
    sm: '0.125rem', // 2px
    DEFAULT: '0.25rem', // 4px
    lg: '0.5rem', // 8px
    full: '9999px',
  },
};

export default goAdventureTheme;
