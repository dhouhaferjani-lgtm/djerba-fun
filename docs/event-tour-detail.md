# Event & Tour Detail Specification (`/package/[slug]`)

## 1. Page Overview

- **Role:** Conversion page. Provides specific details, itinerary, and booking capability.
- **Background:** `#fcfaf2`.

---

## 2. Sections Breakdown

### A. Hero Header

- **Dimensions:** `h-[60vh]`.
- **Visuals:** Full width image, bottom gradient (`to-black/70`).
- **Content (Bottom Left Container):**
  - **Badge:** Type (e.g., "Trail Run").
  - **Title:** Large White Heading (`text-6xl`).
  - **Meta Row:**
    - Location (MapPin).
    - Duration (Clock).
    - Group Size (Users).

### B. Main Layout Grid

- **Padding:** `py-12`.
- **Grid:** 2 Columns (`lg:grid-cols-3`). Left takes 2/3, Right takes 1/3.

#### Left Column (Content)

1.  **Description:**
    - Heading: "About This Adventure".
    - Text: Rich text description.
2.  **Interactive Map (Component):**
    - Container: Aspect video, rounded, border.
    - Visual: SVG/Canvas map.
    - Pins: Clickable tooltips showing stop details.
3.  **Elevation Profile (If Trail):**
    - Component: Recharts Area Chart.
    - Style: Green fill (`#8BC34A`), Dark line.
4.  **Highlights:**
    - List styling.
    - Icon: Green CheckCircle.
    - Text: Bold Title + Description.

#### Right Column (Booking Widget)

- **Behavior:** Sticky (`top-24`).
- **Card Style:** White bg, `shadow-xl`, `border-t-4` (hidden, usually top image or color block).
- **Header Section:**
  - Background: `#0D642E`.
  - Text: White ("Starting From $180").
- **Form Body:** `p-8`.
  - **Date Select:** Standard dropdown.
  - **Guest Counter:** Row with `-` / Value / `+` buttons.
  - **Total Price Row:** `#f5f0d1` background bar showing calculation.
  - **CTA Button:** Full width, `#8BC34A` bg, Shadow.
  - **Microcopy:** "Free cancellation..."
- **Footer Actions:**
  - Gray background row.
  - Buttons: Share / Save (Heart).
- **Eco-Promise:**
  - Small box below widget.
  - Style: Green border/text.
  - Content: "5% goes to reforestation."

---

## 3. Responsive Behavior

- **Mobile:**
  - Grid stacks (Right column moves below Left column).
  - Booking Widget loses sticky behavior or becomes a fixed bottom bar (optional).
  - Hero text scales down.
