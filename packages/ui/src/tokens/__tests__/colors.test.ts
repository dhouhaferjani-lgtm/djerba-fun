import { describe, it, expect } from 'vitest';
import { colors } from '../colors';

/**
 * BDD Tests for djerba.fun Brand Color System
 *
 * Logo Colors:
 * - Navy: #1B2A4E (Primary - text, headings)
 * - Emerald: #2E9E6B (Secondary - sea, nature, CTAs)
 * - Gold: #F5B041 (Accent - sun, warmth)
 * - Orange: #E05D26 (Tertiary - energy)
 */

describe('djerba.fun Color Token System', () => {
  describe('Given the new brand color palette', () => {
    describe('When checking primary colors', () => {
      it('Then primary DEFAULT should be Navy (#1B2A4E)', () => {
        expect(colors.primary.DEFAULT).toBe('#1B2A4E');
      });

      it('Then primary light should be Navy-500 (#3a5a8c)', () => {
        expect(colors.primary.light).toBe('#3a5a8c');
      });

      it('Then primary dark should be Navy-900 (#0d1426)', () => {
        expect(colors.primary.dark).toBe('#0d1426');
      });
    });

    describe('When checking secondary colors', () => {
      it('Then secondary DEFAULT should be Emerald (#2E9E6B)', () => {
        expect(colors.secondary.DEFAULT).toBe('#2E9E6B');
      });

      it('Then secondary light should be Emerald-400 (#4ade9a)', () => {
        expect(colors.secondary.light).toBe('#4ade9a');
      });

      it('Then secondary dark should be Emerald-600 (#25855a)', () => {
        expect(colors.secondary.dark).toBe('#25855a');
      });
    });

    describe('When checking accent colors', () => {
      it('Then accent DEFAULT should be Gold (#F5B041)', () => {
        expect(colors.accent.DEFAULT).toBe('#F5B041');
      });

      it('Then accent light should be Gold-200 (#fde68a)', () => {
        expect(colors.accent.light).toBe('#fde68a');
      });

      it('Then accent dark should be Gold-600 (#ca8a04)', () => {
        expect(colors.accent.dark).toBe('#ca8a04');
      });
    });
  });

  describe('Given full color scales are required', () => {
    describe('When checking navy scale', () => {
      it('Then navy should have 50-950 scale', () => {
        expect(colors.navy).toBeDefined();
        expect(colors.navy[50]).toBeDefined();
        expect(colors.navy[700]).toBe('#1B2A4E'); // Logo color
        expect(colors.navy[950]).toBeDefined();
      });
    });

    describe('When checking emerald scale', () => {
      it('Then emerald should have 50-950 scale', () => {
        expect(colors.emerald).toBeDefined();
        expect(colors.emerald[50]).toBeDefined();
        expect(colors.emerald[500]).toBe('#2E9E6B'); // Logo color
        expect(colors.emerald[950]).toBeDefined();
      });
    });

    describe('When checking gold scale', () => {
      it('Then gold should have 50-950 scale', () => {
        expect(colors.gold).toBeDefined();
        expect(colors.gold[50]).toBeDefined();
        expect(colors.gold[400]).toBe('#F5B041'); // Logo color
        expect(colors.gold[950]).toBeDefined();
      });
    });

    describe('When checking orange scale', () => {
      it('Then orange should have 50-950 scale', () => {
        expect(colors.orange).toBeDefined();
        expect(colors.orange[50]).toBeDefined();
        expect(colors.orange[500]).toBe('#E05D26'); // Logo color
        expect(colors.orange[950]).toBeDefined();
      });
    });
  });

  describe('Given semantic colors derived from brand', () => {
    describe('When checking semantic.success', () => {
      it('Then success should use Emerald (#2E9E6B)', () => {
        expect(colors.semantic.success.DEFAULT).toBe('#2E9E6B');
      });
    });

    describe('When checking semantic.warning', () => {
      it('Then warning should use Gold (#F5B041)', () => {
        expect(colors.semantic.warning.DEFAULT).toBe('#F5B041');
      });
    });

    describe('When checking semantic.error', () => {
      it('Then error should use Orange-600 (#c2410c)', () => {
        expect(colors.semantic.error.DEFAULT).toBe('#c2410c');
      });
    });

    describe('When checking semantic.info', () => {
      it('Then info should use Navy-500 (#3a5a8c)', () => {
        expect(colors.semantic.info.DEFAULT).toBe('#3a5a8c');
      });
    });
  });

  describe('Given legacy colors must be removed', () => {
    describe('When checking for old Forest Green palette', () => {
      it('Then NO token should reference #0D642E (old Forest Green)', () => {
        const colorValues = JSON.stringify(colors).toLowerCase();
        expect(colorValues).not.toContain('#0d642e');
      });

      it('Then NO token should reference #8BC34A (old Lime Green)', () => {
        const colorValues = JSON.stringify(colors).toLowerCase();
        expect(colorValues).not.toContain('#8bc34a');
      });
    });

    describe('When checking for old Ocean Blue palette', () => {
      it('Then NO token should reference #0077B6 (old Ocean Blue)', () => {
        const colorValues = JSON.stringify(colors).toLowerCase();
        expect(colorValues).not.toContain('#0077b6');
      });

      it('Then NO token should reference #F4A261 (old Sandy Orange)', () => {
        const colorValues = JSON.stringify(colors).toLowerCase();
        expect(colorValues).not.toContain('#f4a261');
      });
    });
  });

  describe('Given hex format validation', () => {
    describe('When validating all color values', () => {
      it('Then all primary colors should be valid hex codes', () => {
        const hexRegex = /^#[0-9A-Fa-f]{6}$/;
        expect(colors.primary.DEFAULT).toMatch(hexRegex);
        expect(colors.primary.light).toMatch(hexRegex);
        expect(colors.primary.dark).toMatch(hexRegex);
      });

      it('Then all secondary colors should be valid hex codes', () => {
        const hexRegex = /^#[0-9A-Fa-f]{6}$/;
        expect(colors.secondary.DEFAULT).toMatch(hexRegex);
        expect(colors.secondary.light).toMatch(hexRegex);
        expect(colors.secondary.dark).toMatch(hexRegex);
      });

      it('Then all accent colors should be valid hex codes', () => {
        const hexRegex = /^#[0-9A-Fa-f]{6}$/;
        expect(colors.accent.DEFAULT).toMatch(hexRegex);
        expect(colors.accent.light).toMatch(hexRegex);
        expect(colors.accent.dark).toMatch(hexRegex);
      });
    });
  });
});

/**
 * WCAG Accessibility Tests
 * Using simplified luminance calculation for contrast ratios
 */
describe('Color Accessibility (WCAG AA)', () => {
  // Helper function to calculate relative luminance
  function getLuminance(hex: string): number {
    const rgb = parseInt(hex.slice(1), 16);
    const r = (rgb >> 16) & 0xff;
    const g = (rgb >> 8) & 0xff;
    const b = rgb & 0xff;

    const [rs, gs, bs] = [r, g, b].map((c) => {
      c = c / 255;
      return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    });

    return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
  }

  function getContrastRatio(color1: string, color2: string): number {
    const l1 = getLuminance(color1);
    const l2 = getLuminance(color2);
    const lighter = Math.max(l1, l2);
    const darker = Math.min(l1, l2);
    return (lighter + 0.05) / (darker + 0.05);
  }

  describe('Given WCAG AA requires 4.5:1 contrast for normal text', () => {
    describe('When pairing primary on white', () => {
      it('Then contrast ratio should be >= 4.5:1', () => {
        const ratio = getContrastRatio(colors.primary.DEFAULT, '#ffffff');
        expect(ratio).toBeGreaterThanOrEqual(4.5);
      });
    });

    describe('When pairing dark text on secondary (correct usage)', () => {
      it('Then contrast ratio should be >= 3:1 for large text (WCAG AA)', () => {
        // Green backgrounds with navy text - suitable for buttons/large text
        // WCAG AA: 3:1 for large text (18px+ or 14px+ bold)
        const ratio = getContrastRatio(colors.primary.DEFAULT, colors.secondary.DEFAULT);
        expect(ratio).toBeGreaterThanOrEqual(3);
      });
    });

    describe('When pairing navy-900 on secondary for small text', () => {
      it('Then contrast ratio should be >= 4.5:1', () => {
        // Darker navy for small text on green backgrounds
        const ratio = getContrastRatio(colors.primary.dark, colors.secondary.DEFAULT);
        expect(ratio).toBeGreaterThanOrEqual(4.5);
      });
    });
  });
});
