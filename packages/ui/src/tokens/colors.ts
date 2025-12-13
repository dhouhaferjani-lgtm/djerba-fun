export const colors = {
  primary: {
    50: '#e8f5e9',
    100: '#c8e6c9',
    200: '#a5d6a7',
    300: '#81c784',
    400: '#66bb6a',
    500: '#8BC34A', // Light green - primary light
    600: '#7cb342',
    700: '#689f38',
    800: '#0D642E', // Dark forest green - primary DEFAULT
    900: '#0a5025',
    950: '#063d1b',
  },
  secondary: {
    cream: '#f5f0d1',
    'cream-dark': '#e8e2bc',
    'cream-light': '#faf8e8',
  },
  neutral: {
    white: '#ffffff',
    50: '#fafafa',
    100: '#f5f5f5',
    200: '#e5e5e5',
    300: '#d4d4d4',
    400: '#a3a3a3',
    500: '#737373',
    600: '#525252',
    700: '#404040',
    800: '#262626',
    900: '#171717',
    950: '#0a0a0a',
  },
  // Semantic
  success: '#22c55e',
  warning: '#f59e0b',
  error: '#ef4444',
  info: '#3b82f6',
} as const;

export type Colors = typeof colors;
