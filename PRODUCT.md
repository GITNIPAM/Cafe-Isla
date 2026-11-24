# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

Laravel 12 (upgraded from planned Laravel 11 for PHP 8.2.12 zero-advisory security compatibility) + Blade Templates + Modular Vanilla CSS & JS + MySQL (`cafe_isla_db`), runnable directly on XAMPP PHP 8.2 / Apache / FourSys Cloud with zero Node or Vite build dependencies.

## Users

- **Customer**: Guests and registered shoppers browsing specialty island beverages and coastal lifestyle merch, customizing drink temperature/size variants, applying promo coupons, checking out with billing/shipping/payment validation, tracking live delivery progress, viewing transactional emails, and submitting cancellation or return/spill-refund requests.
- **Admin**: Café owner / head roaster managing the full catalog (CRUD + product image uploads + variant stock/pricing + archiving), order fulfillment, courier assignment, return/refund approvals with conditional merch restocking, customer/employee RBAC accounts, and revenue/low-stock/conversion/abandonment analytics.
- **Staff**: Barista / store operator handling daily inventory adjustments, order confirmation/preparation, and return review without team RBAC mutation privileges.
- **Fulfillment Courier**: Delivery rider restricted to viewing assigned/in-transit orders and updating delivery checkpoints (`in_transit` to `delivered`).

## Product Purpose

Café Isla (*"Slow sips from the archipelago"*) evolves the team's original coastal apparel brand (*Suot-Isla*) into a specialty Philippine island-themed wellness café and lifestyle storefront built for the PCU College of Informatics Final Project (100-point rubric). Success means seamless end-to-end customer ordering, role-protected operational fulfillment, transparent dual-channel email proof, and clean architectural/ERD/Use Case clarity during live panel defense.

## Positioning

Unlike generic café e-commerce templates, Café Isla organizes craft drinks around four authentic Philippine coastal terroir chapters (**La Union**, **Siargao**, **Boracay**, and **El Nido**), anchors La Union on five signature ceremonial matcha and hojicha blends, pairs perishable freshly brewed drinks with non-perishable **Isla Merch** carried over from *Suot-Isla*, and enforces smart hybrid business logic (local rider express for drinks vs. nationwide parcel for merch; automatic restock on approved merch returns vs. refund-without-restock for perishable beverages).

## Operating Context

- Evaluated live by academic panelists against a 100-point rubric (Brand Name, Logo, 16-table ERD, 5-actor/7-stage Use Case Diagram, Core Transactional Flow, UI/UX, and Delivery).
- Dual-Channel Mail logs every formatted HTML transactional email (`order_confirmation`, `shipping_update`, `order_cancellation`, `return_update`) to `email_logs` while optionally dispatching via SMTP so live demonstrations work reliably even on campus networks without external mail relay access.
- Includes a dedicated interactive `/diagrams` route presenting the Brand/Logo narrative, normalized Crow's Foot ERD, UML Use Case Diagram (`<<include>>` / `<<extend>>`), and pre-seeded role demo reference.

## Capabilities and Constraints

- 17 freshly crafted drinks (`is_perishable = true`) across 4 Island Series plus 8 curated *Suot-Isla* coastal merch pieces (`is_perishable = false`).
- Persistent session + user-synced shopping bag across page navigations with dynamic subtotal, coupon discount (`ISLA10`, `FREESIP`), and delivery fee computation (`₱65` Local Express / `₱120` Provincial Parcel / Free over `₱1,200`).
- Passwords hashed with bcrypt (`Hash::make` / `Hash::check`); strict role-based access control (`EnsureRole` middleware).
- Customer return/refund workflow distinguishes order cancellation (`pending` orders), perishable drink spill refunds (refund without restock), and unworn merch returns (refund with automatic variant stock restock).

## Brand Commitments

- **Brand Name**: Café Isla — *"Slow sips from the archipelago."*
- **Pinned La Union Series Drinks**: Muni Matcha, Dulce Matcha, Isla Matcha, Berry Matcha, Spanish Hojicha (with exact specified recipes and Hot/Iced availability).
- **Aesthetic Inspiration**: *Flora Wellness Cafe* editorial print-zine warmth translated into a sun-bleached Philippine island café world.
- **No external runtime JS/CSS build pipeline**: plain CSS custom properties and vanilla ES6 JS under `public/assets/`.

## Evidence on Hand

- Original *Suot-Isla* merch photography (`public/assets/images/merch/`) and island coordinates/stories carried forward.
- Bespoke 35mm-film editorial hero, island terroir, and drink photographs (`public/assets/images/editorial/`, `public/assets/images/drinks/`) with embedded provenance metadata.
- Pre-seeded realistic orders, visits, coupons, return requests, and email logs in `DatabaseSeeder.php`.

## Product Principles

1. **Terroir Before Generic Menu**: Every beverage carries a specific island shore, coordinates, and preparation story.
2. **Perishable Honesty**: Drinks and merch share one bag smoothly while respecting real café inventory, delivery zone, and restocking rules.
3. **Proof Built Into the Surface**: Emails, order timelines, conversion/abandonment metrics, ERD, and Use Cases are inspectable directly inside the live app.
4. **Operational Clarity Under Role Boundaries**: Persuasion lives on the storefront; operational workspaces stay fast, scannable, and scoped strictly to each role's permissions.
