# Accessibility (A11y) Guide

## Current Accessibility Status

### ✅ Implemented

1. **Semantic HTML** - Using proper heading hierarchy (h1, h2, h3)
2. **Alt Text on Images** - All Next.js Image components have alt attributes
3. **Form Labels** - React Hook Form components have proper labels
4. **Keyboard Navigation** - Native HTML elements support keyboard nav
5. **Color Contrast** - Brand colors meet WCAG AA standards
6. **Responsive Design** - Works on all screen sizes
7. **Focus Indicators** - Browser default focus rings present

### ⚠️ Needs Improvement

## Priority Accessibility Fixes

### 1. Icon-Only Buttons (HIGH PRIORITY)

**Issue:** Buttons with only icons lack accessible labels for screen readers.

**Files Affected:**

- `ExtrasSelection.tsx` - Quantity increment/decrement buttons
- `AvailabilityCalendar.tsx` - Month navigation buttons
- `BookingWizard.tsx` - Navigation buttons
- Various modal close buttons

**Fix:**

```tsx
// Bad - No label for screen readers
<button onClick={handleDecrement}>
  <MinusIcon />
</button>

// Good - Has aria-label
<button onClick={handleDecrement} aria-label="Decrease quantity">
  <MinusIcon />
</button>

// Also good - Has visually hidden text
<button onClick={handleDecrement}>
  <MinusIcon />
  <span className="sr-only">Decrease quantity</span>
</button>
```

**Utility Class:**
Add to `globals.css`:

```css
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
```

### 2. Form Validation Announcements (HIGH PRIORITY)

**Issue:** Error messages not announced to screen readers.

**Fix:**

```tsx
// Add role="alert" to error messages
{
  errors.email && (
    <p role="alert" className="text-red-600 text-sm mt-1">
      {errors.email.message}
    </p>
  );
}
```

### 3. Skip to Main Content Link (MEDIUM PRIORITY)

**Issue:** Keyboard users must tab through entire navigation.

**Fix in `MainLayout.tsx`:**

```tsx
<a
  href="#main-content"
  className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary focus:text-white focus:rounded"
>
  Skip to main content
</a>
<main id="main-content">
  {children}
</main>
```

### 4. Loading States (MEDIUM PRIORITY)

**Issue:** Loading spinners not announced to screen readers.

**Fix:**

```tsx
<div role="status" aria-live="polite">
  <Spinner />
  <span className="sr-only">Loading...</span>
</div>
```

### 5. Modal Focus Management (HIGH PRIORITY)

**Issue:** Focus not trapped in modals, doesn't return to trigger.

**Solution:** Use `@headlessui/react` Dialog component or implement focus trap:

```tsx
import { Dialog } from '@headlessui/react';

<Dialog open={isOpen} onClose={onClose}>
  <Dialog.Panel>
    <Dialog.Title>Modal Title</Dialog.Title>
    {/* Content */}
  </Dialog.Panel>
</Dialog>;
```

### 6. ARIA Landmarks (MEDIUM PRIORITY)

**Current:** Basic HTML5 landmarks (`<nav>`, `<main>`, `<footer>`)

**Improve:**

```tsx
<header role="banner">
  <nav role="navigation" aria-label="Main navigation">
    {/* Nav items */}
  </nav>
</header>

<main role="main">
  {/* Content */}
</main>

<aside role="complementary" aria-label="Booking panel">
  {/* Sidebar */}
</aside>

<footer role="contentinfo">
  {/* Footer */}
</footer>
```

### 7. Keyboard Navigation Enhancements

**Calendar Component:**

```tsx
// Add keyboard support for date selection
<div role="grid" aria-label={`${format(currentMonth, 'MMMM yyyy')} calendar`}>
  {/* Days */}
  <button
    role="gridcell"
    aria-selected={isSelected}
    aria-disabled={isDisabled}
    tabIndex={isSelected ? 0 : -1}
    onKeyDown={handleKeyDown} // Arrow keys for navigation
  >
    {day}
  </button>
</div>
```

### 8. Live Regions for Dynamic Content

**Shopping Cart Updates:**

```tsx
<div aria-live="polite" aria-atomic="true" className="sr-only">
  {itemCount} items in cart. Total: {formatPrice(total)}
</div>
```

**Availability Updates:**

```tsx
<div role="status" aria-live="polite">
  {availabilityStatus === 'checking' && 'Checking availability...'}
  {availabilityStatus === 'available' && `${spotsLeft} spots available`}
  {availabilityStatus === 'sold_out' && 'Sold out'}
</div>
```

### 9. Color Contrast Verification

**Tool:** Use axe DevTools or WAVE browser extension

**Check:**

- Text on backgrounds (minimum 4.5:1 for normal text, 3:1 for large)
- Link colors
- Button states (hover, focus, active)
- Form inputs and borders

**Current Brand Colors:**

- Primary: `#0D642E` on white = 8.32:1 ✅
- Secondary light: `#8BC34A` on white = 1.98:1 ❌ (use for decorative only)

### 10. Form Improvements

**Autocomplete Attributes:**

```tsx
<input
  type="email"
  name="email"
  autoComplete="email"
  aria-required="true"
  aria-describedby="email-hint email-error"
/>
<p id="email-hint" className="text-sm text-neutral-600">
  We'll send your booking confirmation here
</p>
{error && <p id="email-error" role="alert">{error}</p>}
```

**Required Field Indicators:**

```tsx
<label htmlFor="email">
  Email <span aria-label="required">*</span>
</label>
```

## Testing Checklist

### Manual Testing

- [ ] **Keyboard Only Navigation**
  - Tab through entire site without mouse
  - Ensure all interactive elements are reachable
  - Verify focus order is logical
  - Test form submission with Enter key

- [ ] **Screen Reader Testing**
  - Test with NVDA (Windows) or VoiceOver (Mac)
  - Verify all buttons/links are announced correctly
  - Check form labels and error messages
  - Test modal dialogs

- [ ] **Zoom Testing**
  - Test at 200% browser zoom
  - Verify no content is cut off
  - Check text doesn't overflow containers

- [ ] **Color Blindness**
  - Use Color Oracle or browser DevTools
  - Don't rely solely on color for information
  - Use icons/text in addition to color

### Automated Testing

**Install axe-core:**

```bash
pnpm add -D @axe-core/react
```

**Add to development:**

```tsx
// apps/web/src/app/layout.tsx (dev only)
if (process.env.NODE_ENV !== 'production') {
  import('@axe-core/react').then((axe) => {
    axe.default(React, ReactDOM, 1000);
  });
}
```

**Run Lighthouse Accessibility Audit:**

```bash
npx lighthouse http://localhost:3000 --only-categories=accessibility --view
```

**Target Score:** 95+ on Lighthouse Accessibility

## WCAG 2.1 Level AA Compliance

### Must-Have (Level A + AA)

- [x] Text alternatives for non-text content
- [x] Captions for audio/video (N/A - no media)
- [x] Meaningful sequence (reading order)
- [x] Sensory characteristics (don't rely on shape/color alone)
- [x] Color contrast (minimum 4.5:1)
- [ ] Keyboard accessible (mostly ✅, needs icon button labels)
- [ ] No keyboard trap (verify modals)
- [ ] Timing adjustable (booking timer - needs pause option)
- [x] Seizures prevention (no flashing content)
- [ ] Skip blocks (need skip link)
- [x] Page titled
- [ ] Focus order (verify)
- [ ] Link purpose (verify all links are descriptive)
- [ ] Multiple ways to navigate (search + nav menu ✅)
- [x] Headings and labels
- [ ] Focus visible (enhance focus indicators)
- [x] Language of page
- [ ] On focus/input (verify no auto-submit)
- [ ] Error identification (enhance)
- [ ] Labels or instructions (enhance)
- [ ] Error suggestion (add helpful messages)
- [ ] Error prevention (add confirmation for destructive actions)

## Implementation Priority

### Week 1: High Priority

1. Add ARIA labels to all icon-only buttons
2. Implement skip to main content link
3. Add role="alert" to form errors
4. Fix modal focus management

### Week 2: Medium Priority

1. Enhance keyboard navigation in calendar
2. Add live regions for dynamic updates
3. Improve ARIA landmarks
4. Add loading state announcements

### Week 3: Testing & Polish

1. Run axe-core automated tests
2. Conduct screen reader testing
3. Keyboard-only navigation testing
4. Fix any issues found

## Resources

- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [WebAIM Checklist](https://webaim.org/standards/wcag/checklist)
- [A11y Project Checklist](https://www.a11yproject.com/checklist/)
- [MDN Accessibility](https://developer.mozilla.org/en-US/docs/Web/Accessibility)
- [React Accessibility](https://react.dev/learn/accessibility)

## Quick Wins

1. ✅ All images have alt text
2. ⏳ Add aria-labels to icon buttons (30 min)
3. ⏳ Add skip link (15 min)
4. ⏳ Add sr-only class (5 min)
5. ⏳ Add role="alert" to errors (30 min)
6. ⏳ Run Lighthouse audit (10 min)

**Estimated Total Time:** 4-6 hours for high-priority fixes
