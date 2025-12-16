# Homepage Specification (`/`)

## 1. Page Overview

- **Role:** The main landing page designed to inspire, build trust, and drive search/exploration.
- **Layout Strategy:** Stacked full-width sections.
- **Background:** Global page background `#f5f0d1`.

---

## 2. Sections Breakdown

### A. Hero Section

- **Dimensions:** `85vh` height.
- **Visuals:**
  - Full-width background image (Sahara/Desert).
  - **Overlay:** `bg-gradient-to-t from-[#0D642E] via-transparent to-transparent opacity-90`.
  - **Texture:** `bg-[#0D642E]/30 mix-blend-multiply` (dampens bright images).
- **Content (Centered):**
  - **H1:** `text-4xl md:text-7xl` font-bold text-white shadow-lg.
  - **Subtext:** Light gray text, max-width `2xl`.
- **Component: Search Widget (Floating)**
  - **Style:** `bg-white/95 backdrop-blur-md`, `rounded-lg`, `shadow-2xl`.
  - **Fields:**
    1.  Destination (Input with MapPin icon).
    2.  Activity (Select Dropdown).
    3.  Date (Date Picker).
  - **Action:** Primary Button ("Search" + Arrow Icon).
- **Component: AI Tip Pill**
  - Small translucent pill (`bg-white/20`) appearing below search.
  - Text: "Travel Tip: [AI Generated Content]".

### B. Marketing Mosaic (Brand Pillars)

- **Padding:** `py-16`.
- **Layout:** 3-Column Grid (`grid-cols-1 md:grid-cols-3`).
- **Card Design (Square):**
  - **Image:** Full background, `object-cover`.
  - **Decor:** Border outline offset top-left (`border-white` or `border-white/50`).
  - **Content Box:** Solid colored box offset bottom-right (`bg-[#0D642E]/85` or `bg-[#8BC34A]/85`).
  - **Typography:** Uppercase Montserrat headings.
  - **Interaction:** Image scales `1.1` on hover. Content box shifts position.

### C. Featured Packages

- **Background:** `bg-white`.
- **Padding:** `py-20`.
- **Header:** Title Left ("Upcoming Adventures"), "View All" Link Right.
- **Grid:** 3 Cards.
- **Card Component:**
  - **Image:** Top (`h-64`). Badge (Category) on top-left.
  - **Body:** Title, Rating (Star icon), Location (MapPin), Duration (Calendar), Price (Large green text).
  - **Hover:** Lift (`-translate-y-1`) and Shadow (`shadow-xl`).

### D. Promo Banner (Ultra Mirage)

- **Background:** `#f5f0d1` (Section bg).
- **Container:** `container` width, `h-[500px]` rounded-lg overflow-hidden.
- **Visuals:**
  - Image: Runner in salt lake.
  - Overlay: Horizontal gradient `from-[#0D642E] to-transparent`.
- **Content (Left Aligned):**
  - Tag: "Event of the Year" (`bg-[#8BC34A]`).
  - Title: Large white text.
  - Buttons: 1 White Fill, 1 White Outline.

### E. Categories Grid

- **Background:** `bg-white`.
- **Grid:** 4 Columns (`md:grid-cols-4`).
- **Card Style:**
  - **Image Area:** `h-48`. Dark overlay on hover.
  - **Footer:** Light cream background (`#fcfaf2`), Center aligned text.
  - **Data:** Category Name + Count label ("12 Packages").

### F. Destinations (Bento Grid)

- **Padding:** `py-20`.
- **Layout:** CSS Grid with `auto-rows-[200px]`.
- **Cells:**
  1.  **Large Square:** `col-span-2 row-span-2` (e.g., Tozeur).
  2.  **Small Square:** 1x1 (e.g., Douz).
  3.  **Small Square:** 1x1 (e.g., Djerba).
  4.  **Wide Rectangle:** `col-span-2` (e.g., Tataouine).
- **Style:** Image fill, gradient overlay at bottom, text white bottom-left.

### G. CTA Section

- **Background:** Solid `#0D642E`.
- **Decor:** Large blurred blobs (`bg-[#8BC34A] filter blur-3xl`) absolute positioned.
- **Content:** Centered white text ("Tailor-Made Desert Experiences").
- **Button:** Large Secondary Button (`#8BC34A` bg).

### H. Latest Blog News

- **Grid:** 3 Columns.
- **Card:** Simple version. Image top (`h-48`), padding `p-6`, Category kicker, Title, Link.

---

## 3. Responsive Behavior

- **Mobile:** All grids collapse to 1 column. Hero text scales down. Search widget stacks vertically.
- **Tablet:** Grids become 2 columns.
- **Desktop:** Full 3 or 4 column layouts. Search widget is horizontal.
