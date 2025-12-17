import type { ThemeConfig } from '@go-adventure/ui';

/**
 * Example Ocean Theme
 *
 * This is an example of a white-label theme that overrides only the colors,
 * keeping typography and other settings from the default theme.
 *
 * Use this as a template for creating customer-specific themes.
 */
export const oceanTheme: Partial<ThemeConfig> = {
  name: 'Ocean Adventures',
  description: 'Coastal and water-sport themed marketplace with deep blue and aqua accents',

  colors: {
    // Deep ocean blue as primary
    primary: {
      DEFAULT: '#0A4B78', // Deep ocean blue
      light: '#1565A3', // Lighter blue
      dark: '#073657', // Darker blue
    },
    // Bright aqua as secondary
    secondary: {
      DEFAULT: '#00BCD4', // Bright aqua
      light: '#33C9DC', // Light aqua
      dark: '#00A0B2', // Dark aqua
    },
    // Sandy beige as accent
    accent: {
      DEFAULT: '#F5E6D3', // Sandy beige
      light: '#FFF8ED', // Light sand
      dark: '#E8D4BA', // Darker sand
    },
    // Keep defaults for neutral, cream, and semantic colors
    // (these will be merged from defaultTheme)
  },

  // Typography not specified - will use defaults
  // Border radius not specified - will use defaults
};

export default oceanTheme;
