export const colors = {
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
    dark: '#e8e2bc',
    light: '#faf8e8',
  },
  neutral: {
    white: '#ffffff',
    light: '#f5f5f5',
    DEFAULT: '#e5e5e5',
    dark: '#a3a3a3',
    darker: '#737373',
    black: '#000000',
  },
  // Semantic
  success: '#22c55e',
  warning: '#f59e0b',
  error: '#ef4444',
  info: '#3b82f6',
} as const;

export type Colors = typeof colors;
