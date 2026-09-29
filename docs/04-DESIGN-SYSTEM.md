# Design System and Theming

## 1. Visual Identity — "Sundown Terracotta"
Earthy and warm, drawing on Kutch mud architecture (bhungas) and craft. The owner asked for the beige from the logo to run across the whole site.

### 1.1 Tokens (`frontend/src/index.css`, `:root`)

| Token | Value | Usage |
|---|---|---|
| `#f8f5e2` (hard-coded in pages) | Warm ivory/beige | Page, Navbar and Footer backgrounds |
| `--terracotta` / `--primary` | `oklch(0.55 0.16 35)` | Buttons, kickers, active links |
| `--ink` / `--foreground` | `oklch(0.28 0.03 50)` | Headings, text |
| `--saffron` / `--accent` | `oklch(0.75 0.13 75)` | Gold accent |
| `--sand` | `oklch(0.93 0.025 80)` | Alternate bands |
| `--sand-light` / `--card` | `oklch(0.955 0.02 85)` | Surfaces |
| `--background` | `oklch(0.97 0.015 85)` | shadcn base background |
| `--border` | `oklch(0.86 0.03 75)` | Borders |
| `--footer-bg` | `oklch(0.22 0.03 50)` | Dark footers (Destination) |
| `#e4d5c7` (hard-coded) | Sand border | Section dividers, card borders |
| `#b04838` (hard-coded) | Terracotta hover | Button hover |

Pages mostly use the hard-coded hex values and `var(--terracotta)` in arbitrary Tailwind classes, not the semantic tokens. A `.dark` palette exists (blue shadcn defaults) but is never switched on.

### 1.2 Typography
* **Display:** Cormorant Garamond (500–700, italics). Applied automatically to `h1`–`h3` and `.font-display`.
* **Body:** Jost (400–600).
* Common patterns: kicker text (`text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)]`), then a `font-display` heading, then `text-zinc-600` body.

---

## 2. Custom CSS Classes
Defined in `index.css`: `.section-tag`, `.section-title`, `.section-tagline`, `.btn-explore`, `.container` (custom widths), `.font-display`, `.watermark-sketch`, `.kicker`, `.mirror-frame` (double border with corner marks), `.stitch-line`, `.grain`, `.rise-in` / `-2` / `-3` (motion-safe entrance), `.img-lift`, `.link-underline`. Details are in `14-STYLING-DEEP-DIVE.md`.

---

## 3. The Heading Colour Trap
`@layer base` sets `h1, h2, h3, .font-display { color: var(--ink) }`. A text colour on a **parent** (e.g. `text-white` on a section) does not reach the headings. Put the colour class on the heading itself.

---

## 4. Booking Engine Styling (separate)
The PHP engine has its own design in `backend/booking-engine/assets/engine.css`, using **Marcellus + Montserrat** fonts. Each property's accent colour comes from the database (`properties.accent`: KSR `#B85C2E`, WRC `#C2703A`) and is written into `--clay`. To match the main site, either change those tokens and fonts in `engine.css` or update `properties.accent` in the database. The React site's CSS does not reach the engine.

**Finish (owner's request):** same colours everywhere, but a matte, smooth finish. Soft shadows, gentle transitions, and buttons that look like buttons (filled or outlined, uppercase, with a pressed state) instead of plain text links. This applies on both the site (`index.css`) and the engine (`engine.css`, `admin.css`).

**Engine components added since 25 Sep 2026** (all use the existing tokens: `--clay`, `--sand`, `--sand-2`, `--paper`, `--ink`, `--muted`, `--line`, `--ok`, `--warn`, `--err`):
* Room picker (`.assign`), occupancy row, "last booking" banner, grouped transfer card.
* Arrival-time **scroll wheel** (`.wheel*`): three snap-scrolling columns with a highlighted band and faded edges.
* Admin: tape-chart calendar and side panel (`.tc*`), change-booking breakdown (`.chg*`), and the on-page **confirm box** (`.ask*`, red action for destructive steps) that replaces browser pop-ups.
