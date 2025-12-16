# Single Blog Post Specification (`/blog/[slug]`)

## 1. Page Overview

- **Role:** Long-form reading experience. Focus on typography and readability.
- **Background:** White content area.

---

## 2. Sections Breakdown

### A. Navigation Bar

- **Background:** `#f5f0d1`.
- **Content:** Simple "Back to Blog" link with left arrow.

### B. Article Header (Hero)

- **Dimensions:** `h-[50vh]`.
- **Visuals:** Full width image, dark overlay (`bg-black/50`).
- **Content (Centered):**
  - **Category Pill:** `#8BC34A` bg, `#0D642E` text.
  - **Title:** `text-5xl` or `6xl`, White, Bold.
  - **Meta:** Author Name + Date (with icons).

### C. Main Layout Grid

- **Container:** `container py-12`.
- **Grid:** 12 Columns (`lg:grid-cols-12`).

#### Left Sidebar (Col 1) - Desktop Only

- **Content:** Sticky Social Share Buttons (Vertical stack).
- **Icons:** Facebook, Twitter, LinkedIn, Share (Lucide).

#### Center Content (Cols 8)

- **Typography:** Tailwind `prose` (Typography plugin).
  - Class: `prose-lg prose-green`.
  - Headings: `#0D642E`.
  - Text: Gray-700.
- **Elements:**
  - **Lead Paragraph:** Italic, larger font, border-left accent.
  - **Blockquote:** `#f5f0d1` background, serif font, italic.
  - **Tags:** Flex row at bottom, gray rounded pills.
  - **Author Bio Box:** Gray background (`#f9fafb`), rounded, Avatar + Bio text.

#### Right Sidebar (Cols 3)

- **Behavior:** Sticky (`top-32`).
- **Section 1: Related Stories**
  - Vertical list of small cards.
  - Image (`h-32`) + Title + Date.
- **Section 2: CTA Box**
  - Background: `#0D642E`.
  - Text: White ("Plan Your Trip").
  - Button: `#8BC34A` full width.

---

## 3. Responsive Behavior

- **Mobile:**
  - Hero height reduces.
  - Sidebars (Left and Right) move to bottom or disappear.
  - Content takes full width.
