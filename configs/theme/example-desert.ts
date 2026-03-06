import type { ThemeConfig } from '@djerba-fun/ui';

/**
 * Example Desert Canyon Theme
 *
 * This example shows a theme optimized for desert and canyon adventures,
 * with warm earth tones and terracotta accents.
 *
 * It overrides colors and typography to demonstrate full customization.
 */
export const desertCanyonTheme: Partial<ThemeConfig> = {
  name: 'Desert Canyon Adventures',
  description: 'Warm earth-toned theme for desert and canyon tourism experiences',

  colors: {
    // Rich terracotta as primary
    primary: {
      DEFAULT: '#C1440E', // Terracotta
      light: '#D45D2E', // Lighter terracotta
      dark: '#9A360B', // Darker terracotta
    },
    // Warm amber as secondary
    secondary: {
      DEFAULT: '#F59E0B', // Amber
      light: '#FBB042', // Light amber
      dark: '#D97706', // Dark amber
    },
    // Soft sand as accent
    accent: {
      DEFAULT: '#F5DEB3', // Wheat/sand
      light: '#FFF8DC', // Cornsilk
      dark: '#D4C5A0', // Darker wheat
    },
    cream: '#FFF8E7', // Lighter cream for desert theme
    // Neutral colors will use defaults
    // Semantic colors will use defaults
  },

  typography: {
    fontFamily: {
      // Use more rugged, western-style fonts
      sans: ['Open Sans', 'system-ui', 'sans-serif'],
      display: ['Bebas Neue', 'Impact', 'sans-serif'],
    },
    // Font weights will use defaults
  },

  // Border radius will use defaults
};

export default desertCanyonTheme;
