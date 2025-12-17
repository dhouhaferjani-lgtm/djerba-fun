/**
 * Theme Configuration Interface
 *
 * Defines the structure for white-label theme customization.
 * All color values should be hex codes (e.g., '#0D642E').
 */

export interface ThemeConfig {
  /** Theme metadata */
  name: string;
  description?: string;

  /** Brand colors */
  colors: {
    primary: {
      DEFAULT: string;
      light: string;
      dark: string;
    };
    secondary: {
      DEFAULT: string;
      light: string;
      dark: string;
    };
    accent: {
      DEFAULT: string;
      light: string;
      dark: string;
    };
    neutral?: {
      white?: string;
      light?: string;
      DEFAULT?: string;
      dark?: string;
      darker?: string;
      black?: string;
    };
    cream?: string;
    /** Semantic colors */
    success?: string;
    warning?: string;
    error?: string;
    info?: string;
  };

  /** Typography configuration */
  typography?: {
    fontFamily?: {
      sans?: string[];
      display?: string[];
    };
    /** Font weights */
    fontWeight?: {
      normal?: string;
      medium?: string;
      semibold?: string;
      bold?: string;
    };
  };

  /** Border radius values (optional overrides) */
  borderRadius?: {
    sm?: string;
    DEFAULT?: string;
    lg?: string;
    full?: string;
  };

  /** Spacing overrides (rare - use with caution) */
  spacing?: Record<string, string>;
}

/**
 * Default Go Adventure Theme
 *
 * This is the base theme that all other themes can extend from.
 */
export const defaultTheme: ThemeConfig = {
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

/**
 * Merge a custom theme with the default theme
 *
 * This allows customers to override only the parts they need,
 * while keeping defaults for everything else.
 */
export function mergeThemeConfig(customTheme: Partial<ThemeConfig>): ThemeConfig {
  return {
    name: customTheme.name || defaultTheme.name,
    description: customTheme.description || defaultTheme.description,
    colors: {
      primary: {
        ...defaultTheme.colors.primary,
        ...customTheme.colors?.primary,
      },
      secondary: {
        ...defaultTheme.colors.secondary,
        ...customTheme.colors?.secondary,
      },
      accent: {
        ...defaultTheme.colors.accent,
        ...customTheme.colors?.accent,
      },
      neutral: {
        ...defaultTheme.colors.neutral,
        ...customTheme.colors?.neutral,
      },
      cream: customTheme.colors?.cream || defaultTheme.colors.cream,
      success: customTheme.colors?.success || defaultTheme.colors.success,
      warning: customTheme.colors?.warning || defaultTheme.colors.warning,
      error: customTheme.colors?.error || defaultTheme.colors.error,
      info: customTheme.colors?.info || defaultTheme.colors.info,
    },
    typography: {
      fontFamily: {
        sans: customTheme.typography?.fontFamily?.sans || defaultTheme.typography?.fontFamily?.sans,
        display:
          customTheme.typography?.fontFamily?.display ||
          defaultTheme.typography?.fontFamily?.display,
      },
      fontWeight: {
        ...defaultTheme.typography?.fontWeight,
        ...customTheme.typography?.fontWeight,
      },
    },
    borderRadius: {
      ...defaultTheme.borderRadius,
      ...customTheme.borderRadius,
    },
    spacing: {
      ...customTheme.spacing,
    },
  };
}

/**
 * Convert a ThemeConfig to CSS variables
 *
 * Returns a CSS string that can be injected into a <style> tag
 * or written to :root in globals.css.
 */
export function themeConfigToCssVariables(theme: ThemeConfig): string {
  const lines: string[] = [':root {'];

  // Brand colors
  lines.push('  /* Brand Colors */');
  lines.push(`  --primary: ${theme.colors.primary.DEFAULT};`);
  lines.push(`  --primary-light: ${theme.colors.primary.light};`);
  lines.push(`  --primary-dark: ${theme.colors.primary.dark};`);
  lines.push('');
  lines.push(`  --secondary: ${theme.colors.secondary.DEFAULT};`);
  lines.push(`  --secondary-light: ${theme.colors.secondary.light};`);
  lines.push(`  --secondary-dark: ${theme.colors.secondary.dark};`);
  lines.push('');
  lines.push(`  --accent: ${theme.colors.accent.DEFAULT};`);
  lines.push(`  --accent-light: ${theme.colors.accent.light};`);
  lines.push(`  --accent-dark: ${theme.colors.accent.dark};`);
  lines.push('');
  if (theme.colors.cream) {
    lines.push(`  --cream: ${theme.colors.cream};`);
    lines.push('');
  }

  // Neutral colors
  if (theme.colors.neutral) {
    lines.push('  /* Neutral Colors */');
    if (theme.colors.neutral.white) lines.push(`  --neutral-white: ${theme.colors.neutral.white};`);
    if (theme.colors.neutral.light) lines.push(`  --neutral-light: ${theme.colors.neutral.light};`);
    if (theme.colors.neutral.DEFAULT) lines.push(`  --neutral: ${theme.colors.neutral.DEFAULT};`);
    if (theme.colors.neutral.dark) lines.push(`  --neutral-dark: ${theme.colors.neutral.dark};`);
    if (theme.colors.neutral.darker)
      lines.push(`  --neutral-darker: ${theme.colors.neutral.darker};`);
    if (theme.colors.neutral.black) lines.push(`  --neutral-black: ${theme.colors.neutral.black};`);
    lines.push('');
  }

  // Semantic colors
  lines.push('  /* Semantic Colors */');
  if (theme.colors.success) lines.push(`  --success: ${theme.colors.success};`);
  if (theme.colors.warning) lines.push(`  --warning: ${theme.colors.warning};`);
  if (theme.colors.error) lines.push(`  --error: ${theme.colors.error};`);
  if (theme.colors.info) lines.push(`  --info: ${theme.colors.info};`);

  lines.push('}');

  return lines.join('\n');
}
