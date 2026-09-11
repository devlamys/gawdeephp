---
name: a11y-modern-web
description: >-
  Provides guidelines and implementation patterns for web accessibility (WCAG 2.1 AA compliance), semantic HTML5 elements, ARIA attributes, focus trap management in modals/drawers, keyboard navigation, screen reader live regions, and color contrast standards. Use when developing accessible web components.
---

# Web Accessibility (A11Y) & Inclusive Design Standards

Use this skill whenever creating or modifying HTML markup, interactive widgets, modal dialogues, form elements, or client-side logic to guarantee WCAG 2.1 AA accessibility compliance.

---

## Key Rules & Implementation Patterns

1. **Semantic HTML First**:
   - Always use native HTML elements (`<button>`, `<a>`, `<nav>`, `<main>`, `<header>`, `<footer>`, `<aside>`, `<article>`) before resorting to custom `<div>` or `<span>` click handlers.
   - Use correct heading hierarchy (`<h1>` -> `<h2>` -> `<h3>`) without skipping levels.

2. **Keyboard Navigation & Focus Traps**:
   - All interactive elements MUST be keyboard reachable via standard `Tab` / `Shift+Tab` and operable via `Enter` / `Space`.
   - Never suppress focus outlines completely (`outline: none` without replacement). Implement a distinct visible focus ring:
     ```css
     :focus-visible {
       outline: 2px solid var(--color-primary, #10b981);
       outline-offset: 3px;
     }
     ```
   - In modals and drawer overlays, trap keyboard focus within the container while open and restore focus to the triggering element upon closure.

3. **ARIA Attributes & Live Regions**:
   - Dynamic content updates (such as cart updates, search autocomplete, or toast notifications) must announce changes to screen readers using `aria-live="polite"` or `role="status"`.
   - Use `aria-expanded="true|false"` for dropdown toggles and accordions.
   - Use `aria-hidden="true"` on decorative icons and SVG graphics.

4. **Color Contrast & Tap Dimensions**:
   - Text elements must achieve at least a 4.5:1 contrast ratio against their background color (3:1 for large text).
   - Interactive mobile elements must maintain minimum target dimensions of `44px x 44px`.
