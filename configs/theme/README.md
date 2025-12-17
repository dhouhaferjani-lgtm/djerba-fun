# Theme Configuration

This directory contains theme configuration files for white-label customization of the Go Adventure platform.

## Files

- **`default.ts`** - The default Go Adventure theme (forest green + lime)
- **`example-ocean.ts`** - Example ocean/coastal theme (blue + aqua)
- **`example-desert.ts`** - Example desert canyon theme (terracotta + amber)

## Usage

### Option 1: Use Default Theme

```typescript
// apps/web/src/app/layout.tsx
import { ThemeProvider } from '@go-adventure/ui';
import { goAdventureTheme } from '@/configs/theme/default';

export default function RootLayout({ children }) {
  return (
    <html>
      <body>
        <ThemeProvider theme={goAdventureTheme}>
          {children}
        </ThemeProvider>
      </body>
    </html>
  );
}
```

### Option 2: Use Custom Theme with Merge

```typescript
// apps/web/src/app/layout.tsx
import { ThemeProvider, mergeThemeConfig } from '@go-adventure/ui';
import oceanTheme from '@/configs/theme/example-ocean';

const theme = mergeThemeConfig(oceanTheme);

export default function RootLayout({ children }) {
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

### Option 3: Environment-Based Theme Switching

```typescript
// configs/theme/index.ts
import { goAdventureTheme } from './default';
import oceanTheme from './example-ocean';
import desertTheme from './example-desert';

const themes = {
  default: goAdventureTheme,
  ocean: oceanTheme,
  desert: desertTheme,
};

export function getTheme() {
  const themeName = process.env.NEXT_PUBLIC_THEME || 'default';
  return themes[themeName] || themes.default;
}
```

```typescript
// apps/web/src/app/layout.tsx
import { ThemeProvider, mergeThemeConfig } from '@go-adventure/ui';
import { getTheme } from '@/configs/theme';

const theme = mergeThemeConfig(getTheme());

export default function RootLayout({ children }) {
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

Then set in `.env.local`:

```bash
NEXT_PUBLIC_THEME=ocean
```

## Creating a New Theme

1. Copy `example-ocean.ts` as a starting point
2. Rename to your customer name (e.g., `customer-acme.ts`)
3. Update the theme object with custom colors
4. Import and use in your layout

### Minimal Theme Example

You only need to override what you want to change:

```typescript
import type { ThemeConfig } from '@go-adventure/ui';

export const myTheme: Partial<ThemeConfig> = {
  name: 'My Custom Theme',
  colors: {
    primary: {
      DEFAULT: '#YOUR_COLOR',
      light: '#YOUR_LIGHT_COLOR',
      dark: '#YOUR_DARK_COLOR',
    },
    secondary: {
      DEFAULT: '#YOUR_SECONDARY',
      light: '#YOUR_SECONDARY_LIGHT',
      dark: '#YOUR_SECONDARY_DARK',
    },
    accent: {
      DEFAULT: '#YOUR_ACCENT',
      light: '#YOUR_ACCENT_LIGHT',
      dark: '#YOUR_ACCENT_DARK',
    },
  },
};
```

Everything else will be inherited from the default theme.

## See Also

- [Full Theming Guide](../../docs/theming-guide.md) - Complete documentation
- [Design Tokens](../../packages/ui/src/tokens/) - Token definitions
- [ThemeProvider Component](../../packages/ui/src/components/ThemeProvider/) - Provider implementation
