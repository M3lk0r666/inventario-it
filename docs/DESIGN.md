---
name: Core Assets System
colors:
  surface: '#faf8ff'
  surface-dim: '#d9d9e4'
  surface-bright: '#faf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f3fd'
  surface-container: '#ededf8'
  surface-container-high: '#e7e7f2'
  surface-container-highest: '#e1e2ec'
  on-surface: '#191b23'
  on-surface-variant: '#434654'
  inverse-surface: '#2e3038'
  inverse-on-surface: '#f0f0fb'
  outline: '#737685'
  outline-variant: '#c3c6d6'
  surface-tint: '#0c56d0'
  primary: '#003d9b'
  on-primary: '#ffffff'
  primary-container: '#0052cc'
  on-primary-container: '#c4d2ff'
  inverse-primary: '#b2c5ff'
  secondary: '#285ab9'
  on-secondary: '#ffffff'
  secondary-container: '#709bfe'
  on-secondary-container: '#003179'
  tertiary: '#7b2600'
  on-tertiary: '#ffffff'
  tertiary-container: '#a33500'
  on-tertiary-container: '#ffc6b2'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dae2ff'
  primary-fixed-dim: '#b2c5ff'
  on-primary-fixed: '#001848'
  on-primary-fixed-variant: '#0040a2'
  secondary-fixed: '#d9e2ff'
  secondary-fixed-dim: '#b1c6ff'
  on-secondary-fixed: '#001946'
  on-secondary-fixed-variant: '#00419d'
  tertiary-fixed: '#ffdbcf'
  tertiary-fixed-dim: '#ffb59b'
  on-tertiary-fixed: '#380d00'
  on-tertiary-fixed-variant: '#812800'
  background: '#faf8ff'
  on-background: '#191b23'
  surface-variant: '#e1e2ec'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 30px
    fontWeight: '700'
    lineHeight: 38px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  title-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
  mono-sm:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
    letterSpacing: 0.02em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  unit: 4px
  container-padding: 24px
  gutter: 16px
  stack-sm: 8px
  stack-md: 16px
  table-cell-padding: 12px
---

## Brand & Style

The design system is engineered for high-utility corporate environments where data density and clarity are paramount. The brand personality is professional, systematic, and dependable, catering to operations managers and financial auditors who require a "source of truth" for physical and digital assets.

The aesthetic follows a **Corporate / Modern** approach. It prioritizes functional minimalism to reduce cognitive load during complex tasks. Key characteristics include high legibility, a disciplined color application to signify status, and a structured layout that balances information density with visual breathing room. The emotional response should be one of control, efficiency, and institutional trust.

## Colors

The palette is anchored by a trustworthy corporate blue used for primary actions and navigation states. A neutral gray scale provides the structural foundation: `#F4F5F7` for the main application canvas to reduce screen glare, and absolute `#FFFFFF` for cards and content containers to create clear separation.

Functional colors are applied with high intentionality:
- **Primary (#0052CC):** Brand presence, primary buttons, and active navigation.
- **Success (#10B981):** "In Stock," "Active," or "Completed" status indicators.
- **Alert (#E11D48):** "Overdue," "Missing," or "Critical Error" states.
- **Neutral Grays:** Used for borders (`#DFE1E6`) and secondary text to maintain a calm, professional interface.

## Typography

This design system utilizes **Inter** for its exceptional legibility in data-heavy environments. The hierarchy is tight, using subtle weight shifts rather than drastic size changes to maintain density.

- **Data Tables:** Use `body-sm` for standard cell content. For serial numbers or asset IDs, use `mono-sm` to ensure character distinction (e.g., 0 vs O).
- **Headers:** Section titles use `title-md` or `headline-sm`. Reserved `display-lg` is for high-level dashboard metrics only.
- **Labels:** Small, all-caps labels (`label-md`) are used for table headers and metadata descriptions to provide visual contrast against dynamic data.

## Layout & Spacing

The system employs a **Fixed Grid** philosophy for the main content area, typically constrained to a 1440px maximum width for desktop, while the sidebar remains fixed.

A strict 4px base unit ensures precision. Information density is achieved through "Compact Composing":
- **Desktop:** 24px outer margins with 16px gutters between cards.
- **Tables:** Vertical cell padding is reduced to 12px to maximize the number of rows visible above the fold.
- **Mobile/Tablet:** The layout reflows to a single column. The sidebar collapses into a hamburger menu or a bottom navigation bar for tablet-specific views.

## Elevation & Depth

To maintain a clean and flat "corporate" feel, the design system avoids heavy shadows. Depth is communicated via **Tonal Layers** and **Low-contrast Outlines**:

- **Level 0 (Background):** `#F4F5F7` — The lowest layer, used for the main application canvas.
- **Level 1 (Cards/Containers):** `#FFFFFF` with a 1px solid border of `#DFE1E6`. No shadow is used for static containers.
- **Level 2 (Overlays/Dropdowns):** `#FFFFFF` with a 1px border and a subtle ambient shadow (0px 4px 12px, 8% black) to separate floating elements from the page content.
- **Active State:** Elements being dragged or high-priority modals may use a slightly deeper shadow to indicate focus.

## Shapes

The design system uses a consistent **8px (0.5rem)** corner radius for almost all components, including buttons, input fields, cards, and dropdowns. This "Rounded" setting balances modern approachability with corporate structure. 

- **Exceptions:** Status "pills" or tags may use `rounded-xl` (1.5rem) to distinguish them from interactive buttons.
- **Small Elements:** Checkboxes use a reduced 4px radius to maintain sharp alignment with text.

## Components

### Buttons
- **Primary:** Solid `#0052CC` with white text. 8px radius.
- **Secondary:** Ghost style with `#0052CC` border and text.
- **Tertiary:** Text-only for low-priority actions in tables.

### Input Fields
- **Default:** White background, 1px `#DFE1E6` border. Focus state uses a 2px `#0052CC` border with no "glow."
- **Labels:** Positioned above the field in `label-md` weight.

### Data Tables
- **Header:** Light gray background (`#F9FAFB`), `label-md` text color, 1px bottom border.
- **Rows:** Zebra striping is optional; use a subtle hover state (`#F4F5F7`) to help users track rows.

### Status Chips
- **Success:** Soft green background (10% opacity of `#10B981`) with dark green text.
- **Alert:** Soft red background (10% opacity of `#E11D48`) with dark red text.

### Navigation Sidebar
- **Background:** Dark corporate blue (`#0747A6`) or clean white (`#FFFFFF`) depending on the desired contrast. Active links use a 4px left-accent border in `#0052CC`.