---
name: ui-ux-micro-interactions
description: >-
  Provides patterns for smooth 60fps micro-animations, interaction feedback, scroll-driven visual triggers, state transitions, skeleton loaders, dynamic toasts, ripple effects, and transition easing functions. Use when making interfaces responsive, dynamic, and engaging.
---

# UI/UX Micro-Interactions & Motion Design

Use this skill whenever adding interactive features, animations, motion effects, state transitions, or feedback loops to ensure a dynamic, fluid user experience.

---

## Key Principles of Motion & Feedback

1. **Performant 60fps Animations**:
   - Only animate composite-safe properties: `transform` (scale, translate, rotate) and `opacity`. Avoid animating `height`, `width`, `margin`, or `top`/`left` to prevent layout reflows.
   - Use custom cubic-bezier easing curves for natural physics:
     ```css
     --ease-out-back: cubic-bezier(0.34, 1.56, 0.64, 1);
     --ease-in-out-smooth: cubic-bezier(0.16, 1, 0.3, 1);
     --ease-spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
     ```

2. **Micro-Interactions Checklist**:
   - **Hover States**: Subtle lift (`transform: translateY(-2px)`), glow intensification, or soft background shift over `150ms-250ms`.
   - **Active/Click Feedback**: Immediate tactile press down (`transform: scale(0.97)`) on click or touch start.
   - **Skeleton Screen Loaders**: Shimmer animations during asynchronous data fetches:
     ```css
     @keyframes shimmer {
       0% { background-position: -200% 0; }
       100% { background-position: 200% 0; }
     }
     .skeleton-loader {
       background: linear-gradient(90deg, var(--surface-bg) 25%, var(--surface-highlight) 50%, var(--surface-bg) 75%);
       background-size: 200% 100%;
       animation: shimmer 1.6s infinite ease-in-out;
     }
     ```
   - **Toast Notifications**: Slide-and-fade entry from screen edge (`translateY(20px)` to `translateY(0)` with `opacity: 0` to `1`).

3. **Scroll-Driven Animations & Reveal Triggers**:
   - Use `IntersectionObserver` or modern CSS `@scroll-timeline` / `animation-timeline: scroll()` for progressive scroll reveals.
   - Stagger grid items with dynamic delays: `--delay: calc(var(--index) * 50ms)`.

4. **Reduced Motion Compliance**:
   - Respect user accessibility settings:
     ```css
     @media (prefers-reduced-motion: reduce) {
       *, ::before, ::after {
         animation-duration: 0.01ms !important;
         animation-iteration-count: 1 !important;
         transition-duration: 0.01ms !important;
         scroll-behavior: auto !important;
       }
     }
     ```
