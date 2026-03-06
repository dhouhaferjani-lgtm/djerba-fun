# Go Adventure Theming Guide

**Date:** 2025-12-17
**Status:** ✅ Production Ready
**Scope:** Complete guide to white-label customization

---

## Overview

The Go Adventure platform is built with **white-label customization** as a core feature. This guide explains how to customize the visual appearance of your marketplace instance without modifying core code.

### What Can Be Customized?

✅ **Brand Colors** - Primary, secondary, accent, and semantic colors
✅ **Typography** - Font families and weights
✅ **Border Radius** - Component roundness
✅ **Spacing** - Layout spacing (advanced)

### What You'll Need

- Basic TypeScript knowledge
- Access to the codebase
- Your brand colors in hex format (e.g., `#0D642E`)
- (Optional) Custom font files or Google Fonts

---

## Quick Start

### 1. Create Your Theme Config

Create a new file in `configs/theme/`:

```typescript
// configs/theme/my-brand.ts
import type { ThemeConfig } from '@djerba-fun/ui';

export const myBrandTheme: Partial<ThemeConfig> = {
  name: 'My Brand Adventures',
  description: 'Custom theme for My Brand tourism marketplace',

  colors: {
    primary: {
      DEFAULT: '#YOUR_PRIMARY_COLOR',
      light: '#YOUR_PRIMARY_LIGHT',
      dark: '#YOUR_PRIMARY_DARK',
    },
    secondary: {
      DEFAULT: '#YOUR_SECONDARY_COLOR',
      light: '#YOUR_SECONDARY_LIGHT',
      dark: '#YOUR_SECONDARY_DARK',
    },
    accent: {
      DEFAULT: '#YOUR_ACCENT_COLOR',
      light: '#YOUR_ACCENT_LIGHT',
      dark: '#YOUR_ACCENT_DARK',
    },
  },
};

export default myBrandTheme;
```

### 2. Apply Your Theme

Update `apps/web/src/app/layout.tsx`:

```typescript
import { ThemeProvider, mergeThemeConfig } from '@djerba-fun/ui';
import myBrandTheme from '@/configs/theme/my-brand';

const theme = mergeThemeConfig(myBrandTheme);

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html>
      <body>
        <ThemeProvider theme={theme}>
          {children}
        </ThemeProvider>
      </body>
    </html>
  );
}
```

### 3. Rebuild and Test

```bash
pnpm build
pnpm dev
```

Your custom colors will now be applied throughout the entire platform!

---

## Color System

### Color Roles

| Role          | Usage                                | Examples                               |
| ------------- | ------------------------------------ | -------------------------------------- |
| **Primary**   | Main brand color, CTAs, navigation   | Buttons, links, active states          |
| **Secondary** | Supporting brand color, accents      | Badges, highlights, secondary buttons  |
| **Accent**    | Background tints, subtle highlights  | Section backgrounds, hover states      |
| **Neutral**   | Text, borders, backgrounds           | Body text, dividers, cards             |
| **Cream**     | Warm background alternative          | Footer backgrounds, card footers       |
| **Semantic**  | Success, warning, error, info states | Alerts, notifications, form validation |

### Color Shades

Each main color has three shades:

- **DEFAULT** - The main brand color (used most often)
- **light** - Lighter variant (hover states, lighter text)
- **dark** - Darker variant (active states, darker text)

**How to Generate Shades:**

1. **Manual Approach**: Use a color picker tool
   - Light: Increase luminosity by 10-15%
   - Dark: Decrease luminosity by 10-15%

2. **Tool-Based Approach**: Use color palette generators
   - [Coolors.co](https://coolors.co/) - Generate palettes
   - [TailwindShades](https://www.tailwindshades.com/) - Generate Tailwind color scales

### Example Color Definitions

```typescript
// Forest Green Theme (Default)
primary: {
  DEFAULT: '#0D642E', // Forest green
  light: '#1a7e45',   // 15% lighter
  dark: '#0a5025',    // 15% darker
}

// Ocean Blue Theme
primary: {
  DEFAULT: '#0A4B78', // Ocean blue
  light: '#1565A3',   // Lighter blue
  dark: '#073657',    // Darker blue
}

// Terracotta Theme
primary: {
  DEFAULT: '#C1440E', // Terracotta
  light: '#D45D2E',   // Lighter terracotta
  dark: '#9A360B',    // Darker terracotta
}
```

### Testing Your Colors

1. **Contrast Ratio**: Ensure text is readable
   - Use [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)
   - WCAG AA requires 4.5:1 for normal text, 3:1 for large text

2. **Accessibility**: Test with colorblindness simulators
   - [Color Oracle](https://colororacle.org/) - Free desktop app
   - Chrome DevTools > Rendering > Emulate vision deficiencies

3. **Brand Consistency**: Verify colors match your brand guidelines

---

## Typography System

### Font Families

The platform uses two font families:

- **Sans** - Body text, UI elements (default: Inter)
- **Display** - Headings, hero text (default: Poppins)

### Custom Fonts

#### Option 1: Google Fonts (Recommended)

Update `apps/web/src/app/layout.tsx`:

```typescript
import { Inter, Montserrat } from 'next/font/google';

const inter = Inter({
  subsets: ['latin'],
  variable: '--font-inter',
});

const montserrat = Montserrat({
  subsets: ['latin'],
  variable: '--font-montserrat',
});

export default function RootLayout({ children }) {
  return (
    <html className={`${inter.variable} ${montserrat.variable}`}>
      <body>
        <ThemeProvider theme={theme}>
          {children}
        </ThemeProvider>
      </body>
    </html>
  );
}
```

Then in your theme config:

```typescript
typography: {
  fontFamily: {
    sans: ['var(--font-inter)', 'system-ui', 'sans-serif'],
    display: ['var(--font-montserrat)', 'system-ui', 'sans-serif'],
  },
}
```

#### Option 2: Custom Font Files

1. Add font files to `apps/web/public/fonts/`
2. Create CSS in `apps/web/src/app/fonts.css`:

```css
@font-face {
  font-family: 'MyCustomFont';
  src: url('/fonts/MyCustomFont.woff2') format('woff2');
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}
```

3. Import in layout and use in theme config

### Font Weights

You can customize font weights:

```typescript
typography: {
  fontWeight: {
    normal: '400',
    medium: '500',
    semibold: '600',
    bold: '700',
  },
}
```

---

## Border Radius

Control the roundness of components:

```typescript
borderRadius: {
  sm: '0.125rem',    // 2px - Buttons, inputs, badges
  DEFAULT: '0.25rem', // 4px - General use
  lg: '0.5rem',      // 8px - Cards, modals
  full: '9999px',    // Circular - Avatars, pills
}
```

**Recommendations:**

- **Modern/Tech**: `sm: 0.25rem, lg: 0.75rem` (more rounded)
- **Classic/Professional**: `sm: 0.125rem, lg: 0.5rem` (default)
- **Sharp/Editorial**: `sm: 0, lg: 0.25rem` (less rounded)

---

## Advanced: Spacing Overrides

⚠️ **Use with caution** - Spacing changes affect layout consistency.

```typescript
spacing: {
  // Override specific spacing values
  '4': '1.5rem',  // Change 1rem to 1.5rem
  '8': '2.5rem',  // Change 2rem to 2.5rem
}
```

Only override spacing if you have a specific design system requirement.

---

## Theme Switching Strategies

### Strategy 1: Build-Time Theme (Recommended)

**Pros**: Simple, no runtime overhead, fast
**Cons**: Requires rebuild to change theme

```typescript
// configs/theme/index.ts
import myBrandTheme from './my-brand';
export const activeTheme = myBrandTheme;

// apps/web/src/app/layout.tsx
import { activeTheme } from '@/configs/theme';
```

### Strategy 2: Environment Variable

**Pros**: Different themes per environment without code changes
**Cons**: Still requires rebuild

```typescript
// configs/theme/index.ts
import defaultTheme from './default';
import customerATheme from './customer-a';
import customerBTheme from './customer-b';

const themes = {
  default: defaultTheme,
  'customer-a': customerATheme,
  'customer-b': customerBTheme,
};

export function getActiveTheme() {
  const themeName = process.env.NEXT_PUBLIC_THEME || 'default';
  return themes[themeName] || themes.default;
}

// apps/web/src/app/layout.tsx
import { getActiveTheme } from '@/configs/theme';
const theme = mergeThemeConfig(getActiveTheme());
```

`.env.local`:

```bash
NEXT_PUBLIC_THEME=customer-a
```

### Strategy 3: Runtime Theme Switching

**Pros**: Theme can change without rebuild
**Cons**: More complex, requires state management

```typescript
'use client';

import { useState } from 'react';
import { ThemeProvider, mergeThemeConfig } from '@djerba-fun/ui';
import defaultTheme from '@/configs/theme/default';
import oceanTheme from '@/configs/theme/example-ocean';

export function AppThemeProvider({ children }) {
  const [activeTheme, setActiveTheme] = useState(defaultTheme);

  const theme = mergeThemeConfig(activeTheme);

  return (
    <ThemeProvider theme={theme}>
      {children}
      {/* Optional: Add theme switcher UI */}
    </ThemeProvider>
  );
}
```

---

## CSS Variables Reference

All theme colors are exposed as CSS variables:

```css
/* Primary colors */
var(--primary)
var(--primary-light)
var(--primary-dark)

/* Secondary colors */
var(--secondary)
var(--secondary-light)
var(--secondary-dark)

/* Accent colors */
var(--accent)
var(--accent-light)
var(--accent-dark)

/* Neutral colors */
var(--neutral-white)
var(--neutral-light)
var(--neutral)
var(--neutral-dark)
var(--neutral-darker)
var(--neutral-black)

/* Semantic colors */
var(--success)
var(--warning)
var(--error)
var(--info)

/* Special */
var(--cream)
```

Use these in custom CSS:

```css
.my-custom-component {
  background-color: var(--primary);
  color: var(--neutral-white);
  border: 2px solid var(--secondary);
}
```

---

## Tailwind Classes Reference

All colors are available as Tailwind utility classes:

```jsx
{/* Primary */}
<div className="bg-primary text-white">Primary background</div>
<div className="bg-primary-light">Light primary</div>
<div className="bg-primary-dark">Dark primary</div>

{/* Secondary */}
<div className="bg-secondary text-primary">Secondary background</div>
<div className="text-secondary">Secondary text</div>

{/* Accent */}
<div className="bg-accent">Accent background</div>

{/* Semantic */}
<div className="text-success">Success message</div>
<div className="border-error">Error border</div>
```

---

## Testing Your Theme

### 1. Visual Regression Testing

Take screenshots of key pages before and after theme changes:

```bash
# Before
pnpm dev
# Take screenshots of:
# - Homepage
# - Listing detail page
# - Booking checkout
# - Dashboard

# After applying theme
pnpm dev
# Take new screenshots and compare
```

### 2. Cross-Browser Testing

Test in:

- Chrome (desktop + mobile)
- Safari (desktop + iOS)
- Firefox
- Edge

### 3. Accessibility Testing

1. **Color Contrast**: Use browser DevTools or online tools
2. **Keyboard Navigation**: Tab through the entire site
3. **Screen Reader**: Test with VoiceOver (Mac) or NVDA (Windows)

### 4. Checklist

- [ ] All pages load without errors
- [ ] Colors match brand guidelines
- [ ] Text is readable on all backgrounds (contrast ratio ≥ 4.5:1)
- [ ] Buttons have clear hover/active states
- [ ] Forms show validation states correctly
- [ ] Maps and charts use theme colors
- [ ] Email templates use theme colors (if customized)

---

## Troubleshooting

### Colors Not Updating

**Problem**: Theme changes aren't visible after rebuild

**Solutions**:

1. Clear Next.js cache: `rm -rf .next && pnpm build`
2. Hard refresh browser: Cmd+Shift+R (Mac) or Ctrl+Shift+R (Windows)
3. Check browser DevTools > Elements > Computed styles to see CSS variables

### Build Errors

**Problem**: TypeScript errors when importing theme

**Solutions**:

1. Ensure `@djerba-fun/ui` is built: `cd packages/ui && pnpm build`
2. Check import path: Should be `from '@djerba-fun/ui'` not `from '@djerba-fun/ui/tokens'`
3. Restart TypeScript server in your IDE

### Theme Provider Not Working

**Problem**: ThemeProvider component not injecting CSS variables

**Solutions**:

1. Verify it's wrapping all content in layout.tsx
2. Check browser console for errors
3. Ensure you're using `mergeThemeConfig()` to merge with defaults
4. Verify `mode` prop is set correctly (`'inline'` or `'attribute'`)

### Fonts Not Loading

**Problem**: Custom fonts don't appear

**Solutions**:

1. Check font files are in `public/fonts/` directory
2. Verify `@font-face` declarations in CSS
3. Check browser Network tab for 404s on font files
4. Ensure `font-display: swap` is set for better loading

---

## Example Themes

### Minimal Override (Colors Only)

```typescript
export const minimalTheme: Partial<ThemeConfig> = {
  name: 'Minimal Brand',
  colors: {
    primary: {
      DEFAULT: '#2563EB', // Blue
      light: '#3B82F6',
      dark: '#1D4ED8',
    },
    secondary: {
      DEFAULT: '#10B981', // Green
      light: '#34D399',
      dark: '#059669',
    },
    accent: {
      DEFAULT: '#F3F4F6', // Gray
      light: '#F9FAFB',
      dark: '#E5E7EB',
    },
  },
};
```

### Full Override (Colors + Typography)

```typescript
export const fullTheme: Partial<ThemeConfig> = {
  name: 'Full Custom Theme',

  colors: {
    primary: {
      DEFAULT: '#7C3AED',
      light: '#8B5CF6',
      dark: '#6D28D9',
    },
    secondary: {
      DEFAULT: '#EC4899',
      light: '#F472B6',
      dark: '#DB2777',
    },
    accent: {
      DEFAULT: '#FEF3C7',
      light: '#FEF9E7',
      dark: '#FDE68A',
    },
  },

  typography: {
    fontFamily: {
      sans: ['Nunito', 'system-ui', 'sans-serif'],
      display: ['Playfair Display', 'serif'],
    },
    fontWeight: {
      normal: '300',
      medium: '500',
      semibold: '700',
      bold: '800',
    },
  },

  borderRadius: {
    sm: '0.25rem',
    DEFAULT: '0.5rem',
    lg: '1rem',
    full: '9999px',
  },
};
```

---

## Best Practices

### ✅ Do

- **Test contrast ratios** for all text/background combinations
- **Use the merge function** to inherit defaults: `mergeThemeConfig(yourTheme)`
- **Keep brand consistency** across all color shades
- **Document your theme** with name and description
- **Version your themes** in git with meaningful commit messages
- **Test on real devices** not just desktop browsers

### ❌ Don't

- **Don't hardcode colors** in components after setting up theming
- **Don't skip accessibility testing** - contrast matters
- **Don't override spacing** unless absolutely necessary
- **Don't mix hex and RGB** - use hex consistently (#RRGGBB)
- **Don't forget semantic colors** - they're important for UX
- **Don't use extremely light/dark shades** that fail contrast checks

---

## Customer Onboarding Checklist

When setting up a new white-label customer:

- [ ] Get brand colors (primary, secondary, accent)
- [ ] Get logo files (SVG preferred)
- [ ] Get custom fonts (if any)
- [ ] Create theme config file in `configs/theme/`
- [ ] Apply theme in layout.tsx
- [ ] Build and test locally
- [ ] Run accessibility audit
- [ ] Take screenshots for approval
- [ ] Deploy to staging environment
- [ ] Get customer sign-off
- [ ] Deploy to production
- [ ] Update documentation with customer name

---

## API Reference

### `ThemeConfig` Interface

Full TypeScript interface definition:

```typescript
interface ThemeConfig {
  name: string;
  description?: string;
  colors: {
    primary: { DEFAULT: string; light: string; dark: string };
    secondary: { DEFAULT: string; light: string; dark: string };
    accent: { DEFAULT: string; light: string; dark: string };
    neutral?: {
      /* ... */
    };
    cream?: string;
    success?: string;
    warning?: string;
    error?: string;
    info?: string;
  };
  typography?: {
    /* ... */
  };
  borderRadius?: {
    /* ... */
  };
  spacing?: Record<string, string>;
}
```

### `mergeThemeConfig(customTheme)`

Merges a partial theme with the default theme.

**Parameters:**

- `customTheme: Partial<ThemeConfig>` - Your custom theme overrides

**Returns:**

- `ThemeConfig` - Complete theme config with defaults merged

**Example:**

```typescript
const theme = mergeThemeConfig({
  colors: {
    primary: { DEFAULT: '#123456', light: '#234567', dark: '#012345' },
  },
});
```

### `themeConfigToCssVariables(theme)`

Converts a theme config to CSS variable declarations.

**Parameters:**

- `theme: ThemeConfig` - Complete theme configuration

**Returns:**

- `string` - CSS string with `:root { ... }` variable declarations

**Example:**

```typescript
const css = themeConfigToCssVariables(theme);
// Returns: ":root {\n  --primary: #123456;\n  ...\n}"
```

### `<ThemeProvider>` Component

React component that injects theme CSS variables.

**Props:**

- `theme: ThemeConfig` - Theme to apply (required)
- `children: ReactNode` - Child components (required)
- `mode?: 'inline' | 'attribute'` - Injection mode (default: 'inline')

**Example:**

```tsx
<ThemeProvider theme={myTheme} mode="inline">
  <App />
</ThemeProvider>
```

---

## Summary

### ✅ What We Built

- **ThemeConfig Interface** - Type-safe theme definition
- **Default Theme** - Base Go Adventure theme
- **Example Themes** - Ocean and Desert variations
- **ThemeProvider Component** - Runtime theme injection
- **Merge Function** - Smart theme inheritance
- **CSS Variable Generator** - Build-time CSS generation
- **Full Documentation** - This guide

### Key Benefits

- 🎨 **No Code Changes** - Customize without touching components
- 🚀 **Fast Setup** - New theme in under 30 minutes
- ♿ **Accessible** - Built-in contrast and WCAG compliance
- 📱 **Responsive** - Works across all devices
- 🔄 **Flexible** - Environment-based or runtime switching
- 🎯 **Type-Safe** - Full TypeScript support

### Next Steps

1. Create your theme config in `configs/theme/`
2. Apply it in `apps/web/src/app/layout.tsx`
3. Build and test: `pnpm build && pnpm dev`
4. Run accessibility audit
5. Deploy to staging for customer review

---

**Questions?** Check the [README in configs/theme/](../configs/theme/README.md) or review the [Design System Hardening Plan](./design-system-hardening.md).
