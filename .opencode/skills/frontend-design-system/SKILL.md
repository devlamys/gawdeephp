---
name: frontend-design-system
description: >-
  Provides comprehensive design tokens, curated color systems, typography hierarchy, modern CSS layout patterns (CSS Grid, Flexbox, Container Queries, :has()), glassmorphism, depth layering, and component styling standards. Use when designing, refactoring, or building user interfaces to achieve a high-end, premium aesthetic.
---

# Frontend Design System & Aesthetic Architecture

Use this skill whenever designing or modifying front-end code (HTML, CSS, JS, PHP views) to ensure state-of-the-art UI/UX, visual consistency, and a premium feel.

---

## Core Aesthetics & Visual Principles

1. **Rich & Modern Color Palettes**:
   - Avoid standard default colors (pure `#000`, pure `#FFF`, flat `#FF0000`, flat `#0000FF`).
   - Use curated HSL or OKLCH color spaces with rich neutrals, warm dark modes (`#0c0f12`, `#12161a`), subtle surface borders (`rgba(255,255,255,0.08)` or `rgba(0,0,0,0.06)`), and vibrant accent highlights (emerald `#10b981`, electric violet `#8b5cf6`, warm amber `#f59e0b`).
   - Maintain high contrast ratios for text readability (4.5:1 minimum for body text).

2. **Modern Typography**:
   - Use Google Fonts with strong readability and personality (e.g., *Plus Jakarta Sans*, *Outfit*, *Inter*, *Playfair Display* for headers).
   - Implement fluid typography using CSS `clamp()`:
     ```css
     --fs-heading-1: clamp(2rem, 4vw + 1rem, 3.5rem);
     --fs-heading-2: clamp(1.5rem, 2.5vw + 0.8rem, 2.25rem);
     --fs-body: clamp(0.95rem, 0.5vw + 0.8rem, 1.1rem);
     ```
   - Enforce distinct font-weight contrast (e.g., 400 for body, 600 for subheadings, 700-800 for primary headings).

3. **Depth, Glassmorphism & Elevation**:
   - Layer elements using multi-stage box-shadows rather than heavy single shadows:
     ```css
     --shadow-sm: 0 2px 4px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06);
     --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.08), 0 8px 10px -6px rgba(0,0,0,0.04);
     --shadow-lg: 0 20px 40px -15px rgba(0,0,0,0.15), 0 12px 20px -10px rgba(0,0,0,0.08);
     ```
   - Use glassmorphism backdrop filters for modals, sticky headers, and floating cards:
     ```css
     background: rgba(255, 255, 255, 0.75);
     backdrop-filter: blur(16px) saturate(180%);
     -webkit-backdrop-filter: blur(16px) saturate(180%);
     border: 1px solid rgba(255, 255, 255, 0.3);
     ```

4. **Modern Layout Architecture**:
   - Prefer **CSS Grid** for two-dimensional component cards and dynamic page sections:
     ```css
     grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr));
     ```
   - Use **Container Queries** (`container-type: inline-size`) for self-contained components that react to their parent container size.
   - Utilize CSS `:has()` for parent styling based on child states (e.g., card highlight when checkbox inside is checked).

5. **Design Tokens Checklist**:
   - CSS Variables for color palette (`--color-primary`, `--color-surface`, `--color-text-main`, `--color-text-muted`).
   - Spacing tokens (`--space-xs`, `--space-sm`, `--space-md`, `--space-lg`, `--space-xl`).
   - Border radius scale (`--radius-sm: 8px`, `--radius-md: 14px`, `--radius-lg: 24px`, `--radius-pill: 9999px`).

---

## Component Guidelines

- **Buttons**: Include active feedback, smooth hover states, explicit focus rings, and proper loading indicators (spinners or pulsing dots).
- **Cards**: Soft borders, hover lift effects (`transform: translateY(-4px)`), smooth transition curves (`cubic-bezier(0.16, 1, 0.3, 1)`).
- **Form Controls**: Custom styled inputs, floating labels or clean helper text, explicit `:focus-visible` states, and contextual inline validation messages.
