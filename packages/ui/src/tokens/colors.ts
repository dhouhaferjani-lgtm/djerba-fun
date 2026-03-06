/**
 * djerba.fun Brand Color System
 *
 * Logo Colors (Source of Truth):
 * - Navy: #1B2A4E (sophistication, primary text)
 * - Emerald: #2E9E6B (sea, nature, secondary)
 * - Gold: #F5B041 (sun, warmth, accent)
 * - Orange: #E05D26 (energy, tertiary)
 */

export const colors = {
  // ============================================
  // PRIMARY - Dark Navy Blue (sophistication)
  // ============================================
  navy: {
    50: '#f0f3f8',
    100: '#d8e0ed',
    200: '#b1c1db',
    300: '#8aa1c9',
    400: '#5f7fb2',
    500: '#3a5a8c',
    600: '#2d4670',
    700: '#1B2A4E', // BASE - Logo color
    800: '#141f3a',
    900: '#0d1426',
    950: '#080b14',
  },

  // ============================================
  // SECONDARY - Emerald Green (sea, nature)
  // ============================================
  emerald: {
    50: '#f0fdf6',
    100: '#dcfced',
    200: '#bbf7d9',
    300: '#86efbf',
    400: '#4ade9a',
    500: '#2E9E6B', // BASE - Logo color
    600: '#25855a',
    700: '#1d6b48',
    800: '#19553b',
    900: '#154530',
    950: '#0a2618',
  },

  // ============================================
  // ACCENT - Golden Yellow (sun, warmth)
  // ============================================
  gold: {
    50: '#fffbeb',
    100: '#fef3c7',
    200: '#fde68a',
    300: '#fcd34d',
    400: '#F5B041', // BASE - Logo color
    500: '#eab308',
    600: '#ca8a04',
    700: '#a16207',
    800: '#854d0e',
    900: '#713f12',
    950: '#422006',
  },

  // ============================================
  // TERTIARY - Burnt Orange (energy)
  // ============================================
  orange: {
    50: '#fff7ed',
    100: '#ffedd5',
    200: '#fed7aa',
    300: '#fdba74',
    400: '#fb923c',
    500: '#E05D26', // BASE - Logo color
    600: '#c2410c',
    700: '#9a3412',
    800: '#7c2d12',
    900: '#6b2710',
    950: '#431407',
  },

  // ============================================
  // NEUTRALS - Warm grays
  // ============================================
  neutral: {
    50: '#fafaf9',
    100: '#f5f5f4',
    200: '#e7e5e4',
    300: '#d6d3d1',
    400: '#a8a29e',
    500: '#78716c',
    600: '#57534e',
    700: '#44403c',
    800: '#292524',
    900: '#1c1917',
    950: '#0c0a09',
    white: '#ffffff',
    black: '#000000',
  },

  // ============================================
  // LEGACY ALIASES (backward compatibility)
  // Maps to new brand colors
  // ============================================
  primary: {
    DEFAULT: '#1B2A4E', // navy-700
    light: '#3a5a8c', // navy-500
    dark: '#0d1426', // navy-900
  },

  secondary: {
    DEFAULT: '#2E9E6B', // emerald-500
    light: '#4ade9a', // emerald-400
    dark: '#25855a', // emerald-600
  },

  accent: {
    DEFAULT: '#F5B041', // gold-400
    light: '#fde68a', // gold-200
    dark: '#ca8a04', // gold-600
  },

  // ============================================
  // SEMANTIC COLORS (derived from brand)
  // ============================================
  semantic: {
    success: {
      DEFAULT: '#2E9E6B', // emerald-500
      light: '#f0fdf6', // emerald-50
      dark: '#1d6b48', // emerald-700
    },
    warning: {
      DEFAULT: '#F5B041', // gold-400
      light: '#fffbeb', // gold-50
      dark: '#a16207', // gold-700
    },
    error: {
      DEFAULT: '#c2410c', // orange-600
      light: '#fef2f2', // warm red-tinted
      dark: '#6b2710', // orange-900
    },
    info: {
      DEFAULT: '#3a5a8c', // navy-500
      light: '#f0f3f8', // navy-50
      dark: '#1B2A4E', // navy-700
    },
  },

  // Legacy flat semantic (for components using old API)
  success: '#2E9E6B',
  warning: '#F5B041',
  error: '#c2410c',
  info: '#3a5a8c',
} as const;

export type Colors = typeof colors;

// Type helpers for color scales
export type ColorScale = {
  50: string;
  100: string;
  200: string;
  300: string;
  400: string;
  500: string;
  600: string;
  700: string;
  800: string;
  900: string;
  950: string;
};
