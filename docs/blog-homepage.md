# Blog Homepage Specification (`/blog`)

## 1. Page Overview

- **Role:** Content hub for articles, guides, and sustainability news.
- **Background:** `#fcfaf2` (Slightly lighter than global cream).

---

## 2. Sections Breakdown

### A. Blog Header

- **Height:** Compact (`py-16`).
- **Background:** `#0D642E` solid.
- **Texture:** SVG Pattern overlay (dotted circles or subtle lines) at 10% opacity.
- **Content:**
  - Eyebrow: "Our Journal" (`#8BC34A`).
  - H1: "Tales from the Dunes".
  - Subtext: "Guides, stories, and sustainability tips...".

### B. Featured Post (Hero Card)

- **Container:** `container` width, `mb-16`.
- **Dimensions:** `h-[500px]`.
- **Style:**
  - Full background image.
  - Gradient overlay (`from-[#0D642E] via-transparent`).
- **Content (Bottom Left):**
  - **Meta Row:** Category Badge (Glassmorphism) + Date.
  - **Title:** Large (`text-5xl`), White.
  - **Excerpt:** White text, line-clamped.
  - **Link:** "Read Full Story" with underline and arrow.

### C. Filter & Grid Section

- **Filter Bar:**
  - Left: Heading "Latest Articles" (`text-[#0D642E]`).
  - Right: Filter links (All / Culture / Guides) separated by slashes.
- **Main Grid:**
  - Layout: 3 Columns (`md:grid-cols-3`).
  - Gap: `gap-8`.
- **Article Card Component:**
  - **Container:** White bg, `rounded-lg`, `shadow-sm`, `border-gray-100`.
  - **Image:** `h-64`, `object-cover`.
  - **Badge:** Floating on image (Top Left), cream bg (`#f5f0d1`).
  - **Content Body:** `p-6`, Flex column layout.
    - Meta: Date + Author (Gray text, small icons).
    - Title: Bold, hover color change to Green.
    - Excerpt: Gray text, `line-clamp-3`.
    - Footer: "Read Article" link aligned to bottom.

### D. Newsletter Section (Inline)

- **Style:** Inset Card (`mt-20`).
- **Background:** `#8BC34A` (Secondary Green).
- **Layout:** Flex row (Left: Text, Right: Form).
- **Form:**
  - Input: White background, no border.
  - Button: Primary Green (`#0D642E`), "Sign Up".

---

## 3. Responsive Behavior

- **Mobile:** Featured post text scales down. Grid becomes 1 column. Newsletter stacks vertically.
