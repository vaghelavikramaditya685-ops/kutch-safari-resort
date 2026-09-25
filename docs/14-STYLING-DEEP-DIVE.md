# Styling Deep Dive

## 1. Tailwind CSS v4
Loaded with `@tailwindcss/vite`. There is no `tailwind.config.js`. `client/src/index.css` starts with:

```css
@import "tailwindcss";
@import "tw-animate-css";
@custom-variant dark (&:is(.dark *));
@theme inline { --color-background: var(--background); --color-primary: var(--primary); … }
```

`@theme inline` maps the shadcn semantic tokens (`background`, `foreground`, `primary`, `accent`, `muted`, `border`, `card`, `chart-*`, `sidebar-*`, radius) to utilities such as `bg-background` and `text-primary`. Brand tokens (`--ink`, `--sand`, `--terracotta`, `--saffron`, `--footer-bg`, `--ease-out`) live in `:root`. Pages use them as `text-[var(--terracotta)]`. See `04-DESIGN-SYSTEM.md` for values.

In practice most pages hard-code `bg-[#f8f5e2]`, `border-[#e4d5c7]`, `text-zinc-*`, and `hover:bg-[#b04838]`.

## 2. Base layer
```css
body { @apply bg-background text-foreground; font-family: "Jost", …; }
h1, h2, h3, .font-display { font-family: "Cormorant Garamond", …; color: var(--ink); }
```
The heading rule means a parent's text colour never reaches headings. Put the colour class on the `h1`–`h3` itself.

## 3. Custom classes (`index.css`)
| Class | Purpose |
|---|---|
| `.section-tag`, `.section-title`, `.section-tagline` | Standard section headings |
| `.btn-explore` | Terracotta uppercase button with pressed state |
| `.container` | Custom max-widths and padding at 640 px and 1024 px |
| `.font-display` | Cormorant Garamond |
| `.watermark-sketch` | Faint decorative sketch |
| `.kicker` | Small uppercase tracked label |
| `.mirror-frame` (+ `::before`/`::after`) | Double border with corner marks (mirror-work motif) |
| `.stitch-line` | Dashed textile-seam rule |
| `.grain::after` | Noise texture overlay |
| `.rise-in`, `.rise-in-2`, `.rise-in-3` | 14 px fade-up, staggered, only under `prefers-reduced-motion: no-preference` |
| `.img-lift` | Scale 1.03 on hover |
| `.link-underline` | Animated underline |

`index.css` also adds `min-width: 0; min-height: 0` to `.flex` in `@layer components`. That stops flex children overflowing, but it applies to every `flex` element.

## 4. Logo blending
The Navbar logo (`logo-main.jpg`, white background) uses the Tailwind class `mix-blend-darken`. On the beige background the white drops out. This only works on backgrounds darker than white.

## 5. Booking engine CSS
`booking-engine/assets/engine.css` (about 250 lines) is completely separate: plain CSS with its own tokens at the top, Marcellus + Montserrat fonts, and `--clay` set per property from the database. Edit it to bring the engine in line with this design system.

## 6. shadcn/ui
`components.json` style "new-york". `class-variance-authority`, `clsx` and `tailwind-merge` power `button.tsx`. Only button, card, sonner and tooltip are installed.
