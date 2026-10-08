# Design System, Styling and Assets

_Combined on 30 Sep 2026 from 4 earlier files. Start at [`README.md`](../README.md) for the overview and hard rules._

Colours, fonts, CSS rules for the site and the engine, and the image/video inventory.

---

## Contents
* [Design System](#context-design-system) _(was `context/design_system.md`)_
* [Design System and Theming](#docs-04) _(was `docs/04-DESIGN-SYSTEM.md`)_
* [Styling Deep Dive](#docs-14) _(was `docs/14-STYLING-DEEP-DIVE.md`)_
* [Image and Asset Inventory](#docs-11) _(was `docs/11-IMAGE-ASSET-INVENTORY.md`)_

---

<a id="context-design-system"></a>

## Design System
_(was `context/design_system.md`)_
### Website ("Sundown Terracotta") [CODE `frontend/src/index.css`]
* **Colours:** page beige `#f8f5e2` (owner directive), terracotta `oklch(0.55 0.16 35)` (`--terracotta`, buttons, kickers), hover `#b04838`, ink `oklch(0.28 0.03 50)` (headings), sand borders `#e4d5c7`. Keep colours; only the finish changed to matte/smooth [DOC owner].
* **Type:** Cormorant Garamond for `h1`–`h3` and `.font-display`; Jost for body.
* **Heading trap:** `h1–h3` get `color: var(--ink)` from the base layer; put colour classes on the heading itself [CODE].
* **Buttons:** filled terracotta or terracotta outline, uppercase, tracked, `rounded-sm` [CODE].
* **Smooth scrolling** is on for the page; section jumps use `behavior: "instant"` [CODE].

### Booking engine [CODE `backend/booking-engine/assets/engine.css`, `admin/admin.css`]
* Tokens: `--clay` (property accent from the database, KSR `#B85C2E`), `--sand`, `--sand-2`, `--paper`, `--ink`, `--muted`, `--line`, `--ok`, `--warn`, `--err`.
* Fonts: Marcellus (display), Montserrat (body).
* Components: room cards, occupancy row, assign picker, extras counters, arrival scroll wheel, payment panels, admin tables, tape chart + side panel, change breakdown (`.chg`), on-page confirm box (`.ask`, red for destructive).
* Icon: `assets/icon.svg` (terracotta square, white "K").

### Responsive
* All website pages and engine pages checked at 375 / 768 / desktop — no sideways scrolling [CODE [`button_audit/REPORT.md`](09-HISTORY-TESTING-AND-GO-LIVE.md#button-audit-report)].
* Engine header wraps below 960 px; header buttons may wrap text on phones [CODE engine.css].

### Accessibility
Pinch-zoom allowed; one `h1` per page; icon links/buttons have names (menu toggle `aria-label`/`aria-expanded`, Instagram); gallery alt text (7 descriptive, 19 numbered — real descriptions [UNKNOWN]); errors in text, not colour only [CODE].

### Tone of voice
Plain, warm, short sentences; money always with ₹; messages say what to do next ("please call us…") [CODE messages].

---

<a id="docs-04"></a>

## Design System and Theming
_(was `docs/04-DESIGN-SYSTEM.md`)_
### 1. Visual Identity — "Sundown Terracotta"
Earthy and warm, drawing on Kutch mud architecture (bhungas) and craft. The owner asked for the beige from the logo to run across the whole site.

#### 1.1 Tokens (`frontend/src/index.css`, `:root`)

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

#### 1.2 Typography
* **Display:** Cormorant Garamond (500–700, italics). Applied automatically to `h1`–`h3` and `.font-display`.
* **Body:** Jost (400–600).
* Common patterns: kicker text (`text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)]`), then a `font-display` heading, then `text-zinc-600` body.

---

### 2. Custom CSS Classes
Defined in `index.css`: `.section-tag`, `.section-title`, `.section-tagline`, `.btn-explore`, `.container` (custom widths), `.font-display`, `.watermark-sketch`, `.kicker`, `.mirror-frame` (double border with corner marks), `.stitch-line`, `.grain`, `.rise-in` / `-2` / `-3` (motion-safe entrance), `.img-lift`, `.link-underline`. Details are in [`14-STYLING-DEEP-DIVE.md`](#docs-14).

---

### 3. The Heading Colour Trap
`@layer base` sets `h1, h2, h3, .font-display { color: var(--ink) }`. A text colour on a **parent** (e.g. `text-white` on a section) does not reach the headings. Put the colour class on the heading itself.

---

### 4. Booking Engine Styling (separate)
The PHP engine has its own design in `backend/booking-engine/assets/engine.css`, using **Marcellus + Montserrat** fonts. Each property's accent colour comes from the database (`properties.accent`: KSR `#B85C2E`, WRC `#C2703A`) and is written into `--clay`. To match the main site, either change those tokens and fonts in `engine.css` or update `properties.accent` in the database. The React site's CSS does not reach the engine.

**Finish (owner's request):** same colours everywhere, but a matte, smooth finish. Soft shadows, gentle transitions, and buttons that look like buttons (filled or outlined, uppercase, with a pressed state) instead of plain text links. This applies on both the site (`index.css`) and the engine (`engine.css`, `admin.css`).

**Engine components added since 25 Sep 2026** (all use the existing tokens: `--clay`, `--sand`, `--sand-2`, `--paper`, `--ink`, `--muted`, `--line`, `--ok`, `--warn`, `--err`):
* Room picker (`.assign`), occupancy row, "last booking" banner, grouped transfer card.
* Arrival-time **scroll wheel** (`.wheel*`): three snap-scrolling columns with a highlighted band and faded edges.
* Admin: tape-chart calendar and side panel (`.tc*`), change-booking breakdown (`.chg*`), and the on-page **confirm box** (`.ask*`, red action for destructive steps) that replaces browser pop-ups.

---

<a id="docs-14"></a>

## Styling Deep Dive
_(was `docs/14-STYLING-DEEP-DIVE.md`)_
_Updated 30 Sep 2026._

### 1. Tailwind CSS v4
Loaded with `@tailwindcss/vite`. There is no `tailwind.config.js`. `frontend/src/index.css` starts with:

```css
@import "tailwindcss";
@import "tw-animate-css";
@custom-variant dark (&:is(.dark *));
@theme inline { --color-background: var(--background); --color-primary: var(--primary); … }
```

`@theme inline` maps the shadcn semantic tokens (`background`, `foreground`, `primary`, `accent`, `muted`, `border`, `card`, `chart-*`, `sidebar-*`, radius) to utilities such as `bg-background` and `text-primary`. Brand tokens (`--ink`, `--sand`, `--terracotta`, `--saffron`, `--footer-bg`, `--ease-out`) live in `:root`. Pages use them as `text-[var(--terracotta)]`. See [`04-DESIGN-SYSTEM.md`](#docs-04) for values.

In practice most pages hard-code `bg-[#f8f5e2]`, `border-[#e4d5c7]`, `text-zinc-*`, and `hover:bg-[#b04838]`.

### 2. Base layer
```css
body { @apply bg-background text-foreground; font-family: "Jost", …; }
h1, h2, h3, .font-display { font-family: "Cormorant Garamond", …; color: var(--ink); }
```
The heading rule means a parent's text colour never reaches headings. Put the colour class on the `h1`–`h3` itself.

### 3. Custom classes (`index.css`)
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

### 4. Logo blending
The Navbar logo (`logo-main.jpg`, white background) uses the Tailwind class `mix-blend-darken`. On the beige background the white drops out. This only works on backgrounds darker than white.

### 4a. Navbar width rules (30 Sep 2026)
The menu has 10 items (Home … Plan Your Visit + White Rann Camp). To keep them on one line:
* The header box is `w-full max-w-[1440px]` (not `.container`, which capped it too narrow).
* Links are `whitespace-nowrap tracking-wider`, gap `gap-3 min-[1500px]:gap-6`.
* The desktop menu only appears at `min-[1320px]:flex`; below that the ☰ menu (`min-[1320px]:hidden`) is used. At 1,024–1,280 px the items wrapped onto two lines, so don't lower the breakpoint without re-measuring.

### 4b. Section jumps
`/plan-your-visit#faq` and `/white-rann-camp/tariff` use `scrollIntoView({ behavior: "instant" })` and the targets have `scroll-mt-28` so the sticky header doesn't cover them. Smooth scrolling was dropped because it didn't run reliably right after a route change.

### 5. Booking engine CSS
`backend/booking-engine/assets/engine.css` (about 450 lines) is completely separate: plain CSS with its own tokens at the top, Marcellus + Montserrat fonts, and `--clay` set per property from the database. Edit it to bring the engine in line with this design system. `backend/booking-engine/admin/admin.css` adds the admin screens on top of it.

Engine CSS details worth knowing:
* **Scroll wheel** (`.wheel`, `.wheel__col`, `.wheel__item`, `.wheel__band`): rows are 36 px, columns 180 px tall (5 rows) with 72 px padding top and bottom so the first and last values can reach the middle. `scroll-snap-type: y mandatory` does the snapping; the JS reads `scrollTop / 36`. `overscroll-behavior: contain` stops the page scrolling with it.
* **`hidden` vs `.btn`:** `.btn` sets `display`, which beats the browser's `[hidden]` rule. Buttons that toggle `hidden` need an explicit `[hidden] { display: none }`.
* Header buttons wrap below 960 px (`white-space: normal`), so the booking page fits a 375 px phone (it was 389 px wide).
* `.wheel__side .btn[hidden]{display:none}` for the arrival wheel's clear button.
* CSS and JS are loaded as `engine.css?v=<filemtime>`, so browsers pick up changes.
* Nothing in the room list is sticky except the whole side summary. Sticky pieces overlapped while scrolling.
* Admin: `.wrap:has(> .tc-page)` makes the Availability page full width; `.ask` is the on-page confirm box; `.chg` is the change-booking breakdown.

### 6. shadcn/ui
`components.json` style "new-york". `class-variance-authority`, `clsx` and `tailwind-merge` power `button.tsx`. Only button, card, sonner and tooltip are installed.

---

<a id="docs-11"></a>

## Image and Asset Inventory
_(was `docs/11-IMAGE-ASSET-INVENTORY.md`)_
_Measured 25 Sep 2026; updated 30 Sep 2026 (brochure images, favicons, preload removed)._

### Summary
| Location | Size | Notes |
|---|---|---|
| `frontend/public/assets/` | **~140 MB** (was ~269 MB) | Photos compressed 6 Oct 2026 (same names and paths, so no code changes), served as-is at `/assets/...`. About 122 MB of the rest is **unused** files and videos that no page loads (list below) |
| `backend/booking-engine/assets/img/` | 3.1 MB | Already WebP, used only by the booking engine |

Images are referenced by absolute path strings, not imports, so Vite never removes unused files. Before deleting anything, grep for the path in `frontend/src` and `frontend/index.html`.

### Photos compressed (6 Oct 2026)
The photos came straight from the cameras (up to 6016×4016 px, 17 MB each) but show at 40–1440 px. 32 used files were resized and re-encoded **in place** (same file names and paths, JPEG stays JPEG, PNG stays PNG): longest side 1920 px (1280 px for `new/gallery/*`, which the Gallery grid shows 256 px tall), JPEG quality 80 (lowered to as low as 62 only where a photo stayed above 450 KB; two went to 76), camera metadata (EXIF, including any GPS) stripped, rotation baked in. Files under 100 KB, or that would shrink by under 15%, were left alone. Checked at 1:1 against the originals with no visible difference; `/`, `/stay` and `/gallery` load every image. **136.7 MB → 6.4 MB** for those 32 files.

| Before → now | File | Used by |
|---|---|---|
| 17.1 MB → 426 KB | `images/new/restaurant-kutch-safari-ab-vision-11.jpg` | Dining, Gallery |
| 14.3 MB → 429 KB | `images/new/kutchi-ac-room-_-deluxe-ac-cottage-kutch-safari-ab-vision-17.jpg` | Stay |
| 11.7 MB → 319 KB | `images/new/kutch-ac-cottage-_dsc9435.jpg` | Stay, Gallery |
| 11.5 / 10.8 MB → 155 / 136 KB | `images/new/gallery/_DSC9412.JPG`, `_DSC9408.JPG` | Gallery |
| 9.6 / 9.0 MB → 280 / 235 KB | `…deluxe-ac-cottage-interior.jpg`, `…-interior-02.jpg` | Stay, Home |
| 6.7 MB → 258 KB | `images/new/kutch-destination-road_2.jpg` | Home, Around the Resort, Packages, Destination |
| 6.8 MB → 180 KB | `LAKEVIEW KUTCH SAFARI1.jpeg` (now 1600×686) | `og:image` (the filename still has spaces) |
| 4.6 MB → 34 KB | `images/logo-mark.png` (now 128×128, shown at 40×40, transparency kept) | Destination, PackageDetail, RannUtsavPackage headers |
| 5–6 MB each → ~200–270 KB | `images/new/gallery/2018…`, `IMG_20190310…` | Gallery |

Page weight of the images (measured in the browser): Home **16.9 MB → 1.65 MB**, Stay 44.3 MB → 1.5 MB, Gallery 82.2 MB → 3.96 MB (all 27 photos). The **biggest remaining download is the Home hero video, 10.2 MB** (`images/new/kutch-safari-resort-website-hero.mp4`, autoplays, no poster image). It is a video, so it was not touched.

Do this again whenever new photos are added: phone and camera originals are 5–17 MB. The throwaway script used (Pillow, resize + re-encode in place) was not kept in the repo.

### Small files added 29–30 Sep 2026
| File | Used by |
|---|---|
| `favicon.ico` (9 KB), `apple-touch-icon.png` | Site icon (`index.html`) |
| `assets/images/brochure/*.jpg`: 11 photos, 4–23 KB, 200–590 px, extracted from "KUTCH SAFARI RESORT 2026 2027.pdf" | Experiences page |

Brochure files: `kutch-colourful-communities`, `white-rann-of-kutch`, `kutch-textiles`, `dholavira-unesco-site` (not shown anywhere since 5 Oct 2026; were the Why Visit Kutch? photos); `morning-yoga`, `lake-view-gala-dinner`, `sunrise-breakfast`, `candlelight-dinner` (Guest Experiences); `gala-dinner-campfire`, `folk-music-evening`, `camel-cart-welcome` (Arrangements on Request). They are low resolution because that is what the PDF contains: fine at card size; ask the owner for originals before showing them larger.

The booking engine has its own icon: `backend/booking-engine/assets/icon.svg`.

### Unused files (not referenced anywhere)
`campfire-night.png` (4.9 MB), `hero-kutch-safari.png` (4.5 MB), `lake-sunrise-reference.png` (5.6 MB), `sketch-camel.png` (4.1 MB), `sketch-wild-ass.png` (5.2 MB), `guest-feedback…mov` (71.1 MB), `new/guests-*.jpg` (4 files, ~13 MB, duplicates of gallery photos), `new/kutch-ac-cottage-kutchi-cottage-interior.jpg`. Together that is about **109 MB** that can be deleted or moved out of `public/`.

### Booking engine photos (`backend/booking-engine/assets/img`)
* `ksr/`: 22 WebP files (cottage exterior and interior, bathroom, lake dusk, gazebo, restaurant, cuisine, gala dinner, road to heaven, Mandvi, etc.)
* `wrc/`: 20 WebP files (tents inside and out, washrooms, camp, full moon Rann, etc.)

These are the right format and size. They could also be reused on the main site (e.g. Dining). The `wrc/` photos are kept although White Rann Camp is switched off in the engine.

_Re-checked 26 Sep 2026: no images were added or removed by the booking-engine work. Receipts and terms are PDFs built on the fly (`lib/pdf.php`) with no images, so nothing is stored for them._

### Optimisation plan
1. Delete or move the unused files (about 109 MB, plus the unused 12.8 MB `KSR_VIDEO.mp4`). They are not loaded by any page, but they are still uploaded with every deploy.
2. ~~Resize and compress the photos~~ done 6 Oct 2026 (JPEG/PNG kept, see above). Optional next step: WebP/AVIF would save roughly another 25–30%, but needs the paths in the code changed.
3. Re-encode the hero video to 720p MP4 + WebM (target under 3 MB). (The unused `KSR_VIDEO.mp4` preload is already gone.)
4. ~~Favicon~~ done 29 Sep 2026. ~~Small logo~~ done 6 Oct 2026 (`logo-mark.png` is 34 KB).
5. ~~Small `og:image`~~ done 6 Oct 2026 (180 KB). Still to do: rename `LAKEVIEW KUTCH SAFARI1.jpeg` to use hyphens, then update `og:image`.
