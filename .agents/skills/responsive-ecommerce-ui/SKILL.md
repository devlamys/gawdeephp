---
name: responsive-ecommerce-ui
description: >-
  Provides modern design patterns for e-commerce user interfaces including product grids, quick view drawers, dynamic cart drawers, checkout UI flows, image gallery lightboxes, mobile-first responsive navigation, and high-converting product detail views. Use when building or updating e-commerce frontend components.
---

# Responsive E-Commerce UI & UX Patterns

Use this skill whenever designing or modifying e-commerce features (product listings, detail pages, cart drawers, checkout, user accounts, search/filters) to maximize user engagement and conversion.

---

## Core E-Commerce UI Components & Standards

1. **Product Cards & Listing Grids**:
   - High-impact visual aspect ratios (1:1, 4:5 or 3:4) with clean image cropping (`object-fit: cover`).
   - Secondary hover image reveal (smooth opacity cross-fade).
   - Sticky or prominent "Add to Cart" / "Quick Add" trigger buttons with loading state indicator.
   - Distinct badges for promotional discounts, stock status ("Low Stock", "Featured"), or natural ingredients/features.

2. **Sliding Cart Drawer (Off-canvas Cart)**:
   - Smooth slide-in transition from screen edge with background backdrop blur.
   - Dynamic subtotal updates, free-shipping progress bar indicator, quantity increment/decrement controls, and instant item removal animations.
   - Clear sticky CTA button leading directly to checkout.

3. **High-Converting Product Detail Page (PDP)**:
   - Multi-angle gallery with thumbnail selector, image zoom, or swipeable mobile carousel.
   - Clear pricing display, discount savings percentage badge, dynamic variant selector (swatches for colors, pills for sizes/weights).
   - Tabbed or accordion details for Ingredients, Usage Instructions, Shipping & Returns, and Reviews.

4. **Mobile-First Responsive Layouts**:
   - Ensure touch targets are at least `44px x 44px` on mobile screens.
   - Fixed sticky bottom action bar on mobile PDPs for immediate "Add to Cart" access.
   - Off-canvas mobile navigation menu with seamless accordion sub-menus.

5. **Performance & Asset Optimization**:
   - Lazy load secondary product images using `loading="lazy"` and `decoding="async"`.
   - Use `<picture>` tags or modern image formats (`WebP`, `AVIF`) with dynamic fallback support.
