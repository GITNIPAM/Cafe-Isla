---
name: Café Isla
description: Slow sips from the archipelago — editorial Philippine coastal café and merch system
colors:
  parchment-ground: "#F6F1E6"
  limestone-surface: "#ECE3D0"
  paper-bright: "#FBF8F1"
  canopy-deep: "#1C3127"
  canopy-hover: "#254033"
  espresso-ink: "#261D18"
  taupe-muted: "#6E6256"
  hairline-sand: "#D4C8B4"
  clay-coral: "#C4553A"
  clay-coral-deep: "#A6422A"
  isla-launion: "#4A6B46"
  isla-siargao: "#1F4E52"
  isla-boracay: "#B87B28"
  isla-elnido: "#335C6E"
typography:
  display:
    fontFamily: "Libre Caslon Display, Georgia, serif"
    fontSize: "4.35rem"
    fontWeight: 400
    lineHeight: 1.03
    letterSpacing: "-0.025em"
  headline:
    fontFamily: "Libre Caslon Text, Georgia, serif"
    fontSize: "2.75rem"
    fontWeight: 400
    lineHeight: 1.12
    letterSpacing: "-0.02em"
  body:
    fontFamily: "Libre Caslon Text, Georgia, serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.65
    letterSpacing: "0em"
  label:
    fontFamily: "Archivo, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.2
    letterSpacing: "0.08em"
  mono:
    fontFamily: "Courier Prime, Courier New, monospace"
    fontSize: "0.84rem"
    fontWeight: 400
    lineHeight: 1.45
    letterSpacing: "0em"
rounded:
  none: "0px"
spacing:
  xs: "0.45rem"
  sm: "0.75rem"
  md: "1.25rem"
  lg: "2.25rem"
  xl: "3.75rem"
components:
  button-primary:
    backgroundColor: "{colors.clay-coral}"
    textColor: "{colors.paper-bright}"
    rounded: "{rounded.none}"
    padding: "0.62rem 1.2rem"
  button-primary-hover:
    backgroundColor: "{colors.clay-coral-deep}"
  button-canopy:
    backgroundColor: "{colors.canopy-deep}"
    textColor: "{colors.paper-bright}"
    rounded: "{rounded.none}"
    padding: "0.62rem 1.2rem"
  button-canopy-hover:
    backgroundColor: "{colors.canopy-hover}"
  button-outline:
    backgroundColor: "transparent"
    textColor: "{colors.espresso-ink}"
    rounded: "{rounded.none}"
    padding: "0.62rem 1.2rem"
  card-product:
    backgroundColor: "{colors.paper-bright}"
    textColor: "{colors.espresso-ink}"
    rounded: "{rounded.none}"
    padding: "1rem"
  input-field:
    backgroundColor: "{colors.paper-bright}"
    textColor: "{colors.espresso-ink}"
    rounded: "{rounded.none}"
    padding: "0.58rem 0.72rem"
---

# Design System: Café Isla

## Overview

**Creative North Star: "The Archipelago Broadsheet & Pour Bar"**

Café Isla pairs the unhurried editorial print-zine warmth of *Flora Wellness Cafe* with sunlit Philippine coastal materials: abaca parchment paper, honed travertine limestone, ceremonial matcha canopy greens, and roasted espresso espresso-ink linework. Persuade surfaces invite unhurried exploration through framed 35mm-film photography and split-ledger menus; Operate surfaces (`checkout`, `account`, `admin`, `diagrams`) shift cleanly into a crisp tabular ledger anchored by deep ceremonial canopy chrome.

**Key Characteristics:**
- Sharp `0px` architectural framing across every photograph, button, input, drawer, and card
- Three-voice typography pairing italic Caslon display/serif prose, tracked uppercase `Archivo` UI chrome, and `Courier Prime` tasting notes and tabular numbers
- Restrained warm paper palette anchored by deep canopy green (`#1C3127`) and terracotta clay (`#C4553A`), tinted per shore only on Island Series indicators

## Colors

Warm sun-bleached neutrals carry the reading ground while deep ceremonial canopy green grounds operational anchors and terracotta clay signals primary actions and batch scarcity.

### Primary
- **Terracotta Clay** (`#C4553A`, deep `#A6422A`): Top announcement bar, primary tag CTAs, active operator tabs, and `Low Batch` stamps.
- **Ceremonial Canopy Deep** (`#1C3127`, hover `#254033`): Footer anchor block, operator portal header, secondary dark tag buttons, and active bag/inbox count chips.

### Secondary
- **La Union Matcha** (`#4A6B46`): Island Series dot and origin badge for San Juan matcha pours.
- **Siargao Reef Teal** (`#1F4E52`): Island Series dot and origin badge for Cloud 9 espresso & coconut cold brews.
- **Boracay Mango Ochre** (`#B87B28`): Island Series dot and origin badge for golden hour crème drinks.
- **El Nido Lagoon Slate** (`#335C6E`): Island Series dot and origin badge for botanical wellness teas.

### Neutral
- **Parchment Ground** (`#F6F1E6`): Primary page canvas overlaid with subtle dot-matrix paper texture.
- **Limestone Surface** (`#ECE3D0`): Island switcher bands, filter toolbar, drawer headers, and table headers.
- **Paper Bright** (`#FBF8F1`): Card interiors, form fields, and operational ledger panels.
- **Espresso Ink** (`#261D18`): Body copy, `1px` structural borders, and horizontal rules.
- **Muted Taupe** (`#6E6256`): Coordinates, tasting notes, and uppercase field labels.
- **Hairline Sand** (`#D4C8B4`): Subtle row separators inside tables and menu ledgers.

### Named Rules
**The Sharp-Edge Material Rule.** No rounded SaaS pills or floating soft shadows appear on photographic frames, cards, or primary CTAs; structural hierarchy comes strictly from `1px solid #261D18` borders and surface value shifts.

## Typography

**Display Font:** `Libre Caslon Display` (fallback `Georgia, serif`)
**Body Font:** `Libre Caslon Text` (Regular `400`, Italic `400`, Bold `700`; fallback `Georgia, serif`)
**Label Font:** `Archivo` (`500`, `600`, `700`; uppercase with `0.04em–0.11em` tracking)
**Mono Font:** `Courier Prime` (`400`, `700` for coordinates, prices, timestamps, and tasting notes)

**Character:** Expressive Caslon italics convey café ritual warmth while `Archivo` uppercase labels and `Courier Prime` tabular metrics keep operational screens brisk and legible.

### Hierarchy
- **Display** (`400`, italic, `clamp(2.45rem, 4.9vw, 4.35rem)`, line-height `1.03`, tracking `-0.025em`): Landing hero headline (`Slow sips brewed from four Philippine shores.`).
- **Headline** (`400`, italic, `clamp(1.85rem, 3.2vw, 2.75rem)`, line-height `1.12`): Section headers (`Pour by Island Series`, `Explore Our Island Ledger`, `Isla Merch Capsule`).
- **Body** (`400`, `1rem`, line-height `1.65`, measure `65ch` max): Terroir stories and product descriptions.
- **Label** (`600–700`, `0.68rem–0.86rem`, uppercase, tracking `0.04em–0.11em`): Header links, form labels, rectangular tag CTAs, and table column headers.
- **Tabular Mono** (`400–700`, `0.74rem–1.55rem`, `font-variant-numeric: tabular-nums`): Coordinates, peso currency totals, stock counts, and KPI values.

### Named Rules
**The Heading-Leads Rule.** Never place tiny uppercase kicker/eyebrow labels above section or product headings; place island coordinates, SKUs, and timestamps inline beside or below the heading.

## Layout

Container max width `1320px` with responsive side gutter `clamp(1.25rem, 3.2vw, 3.25rem)`. Storefront sections alternate between full-width framed photography, asymmetric two-column Island Bar layouts (`0.92fr / 1.08fr`), a `1/3` sticky lifestyle photo paired with a `2/3` two-column menu ledger, and a snap-scrolling horizontal merch carousel. Operational routes (`/checkout`, `/account`, `/admin`, `/diagrams`) adopt clean `1.15fr / 0.85fr` or `1fr / 1fr` splits and full-width tabular grids. Breakpoints collapse cleanly at `1080px`, `960px` (mobile navigation toggle), and `820px`.

## Elevation & Depth

Surface elevation is flat (`box-shadow: none`) across cards, panels, and buttons. Depth is conveyed purely through tonal contrast (`#F6F1E6` vs `#ECE3D0` vs `#FBF8F1` vs `#1C3127`), `1px solid #261D18` architectural borders, and the slide-over drawer backdrop (`rgba(28, 49, 39, 0.54)`).

## Shapes

Every photo frame, card, tag button, input control, table container, and brand emblem uses `0px` corner radius (`border-radius: 0`). Organic rhythm enters exclusively through a crisp geometric sinusoidal SVG wave divider (`#C4553A`) and warm editorial photography.

## Components

### Buttons
- **Shape:** Sharp rectangular architectural tag (`0px` radius).
- **Primary:** Terracotta Clay (`#C4553A`) fill with Paper Bright (`#FBF8F1`) uppercase `Archivo` text, padding `0.62rem 1.2rem` (`.tag-btn--sm` at `0.42rem 0.82rem`).
- **Secondary / Canopy:** Canopy Deep (`#1C3127`) fill with Paper Bright text; Outline variant uses transparent background with `1px solid #261D18` border.
- **Hover / Focus:** Smooth `180ms` color transition to deep clay/canopy or ink inversion; focus-visible ring `2px solid #C4553A` with `3px` offset.

### Cards / Containers
- **Corner Style:** `0px` sharp rectangle.
- **Background:** `#FBF8F1` inside `1px solid #261D18` frames.
- **Product Card:** Top `3 : 3.5` aspect image with bottom border, upper-left Island Series tag, upper-right `Low Batch` or `Sold Out` stamp, uppercase `Archivo` title, `Courier Prime` excerpt, and tabular peso price + quick-pour button.

### Inputs / Fields
- **Style:** `0px` sharp border `1px solid #261D18` on `#FBF8F1` fill (or minimal bottom-underline input inside the `#1C3127` canopy footer).

### Navigation
- **Style:** Open single-row sticky header (`rgba(246, 241, 230, 0.96)`) over `1px` bottom rule, custom square sun-wave-leaf SVG emblem + italic wordmark left, uppercase single-line links center, and Search / Live Inbox / Portal / Bag actions right; collapses into `Menu` toggle at `<= 960px`.

### Slide-Over Drawers
- **Signature Behavior:** Right-docked `460px` sharp-framed drawer (`#F6F1E6` body, `#ECE3D0` header/footer) for both **Your Isla Bag** and **Live Email Dispatch** proof.

## Do's and Don'ts

### Do:
- **Do** keep `0px` sharp corners across all imagery, buttons, cards, and form controls.
- **Do** preserve `white-space: nowrap` on single-row navigation links and `font-variant-numeric: tabular-nums` on every price, quantity, and metric.
- **Do** pair `Libre Caslon` italic headings with tracked uppercase `Archivo` actions and `Courier Prime` metadata.

### Don't:
- **Don't** add eyebrow/kicker labels above headings or `01 / 02` decorative numbering.
- **Don't** replace crisp inline geometric SVG icons with emoji or Unicode glyph shortcuts.
- **Don't** introduce drop shadows, rounded pill cards, or gradient text anywhere on the canvas.
