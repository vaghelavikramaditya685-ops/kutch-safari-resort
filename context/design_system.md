# Design System
> Purpose: how things look and why | Mode: A | Confidence: High | Related: [../docs/04-DESIGN-SYSTEM.md](../docs/04-DESIGN-SYSTEM.md), [../docs/14-STYLING-DEEP-DIVE.md](../docs/14-STYLING-DEEP-DIVE.md)

## Website ("Sundown Terracotta") [CODE `frontend/src/index.css`]
* **Colours:** page beige `#f8f5e2` (owner directive), terracotta `oklch(0.55 0.16 35)` (`--terracotta`, buttons, kickers), hover `#b04838`, ink `oklch(0.28 0.03 50)` (headings), sand borders `#e4d5c7`. Keep colours; only the finish changed to matte/smooth [DOC owner].
* **Type:** Cormorant Garamond for `h1`–`h3` and `.font-display`; Jost for body.
* **Heading trap:** `h1–h3` get `color: var(--ink)` from the base layer; put colour classes on the heading itself [CODE].
* **Buttons:** filled terracotta or terracotta outline, uppercase, tracked, `rounded-sm` [CODE].
* **Smooth scrolling** is on for the page; section jumps use `behavior: "instant"` [CODE].

## Booking engine [CODE `backend/booking-engine/assets/engine.css`, `admin/admin.css`]
* Tokens: `--clay` (property accent from the database, KSR `#B85C2E`), `--sand`, `--sand-2`, `--paper`, `--ink`, `--muted`, `--line`, `--ok`, `--warn`, `--err`.
* Fonts: Marcellus (display), Montserrat (body).
* Components: room cards, occupancy row, assign picker, extras counters, arrival scroll wheel, payment panels, admin tables, tape chart + side panel, change breakdown (`.chg`), on-page confirm box (`.ask`, red for destructive).
* Icon: `assets/icon.svg` (terracotta square, white "K").

## Responsive
* All website pages and engine pages checked at 375 / 768 / desktop — no sideways scrolling [CODE `button_audit/REPORT.md`].
* Engine header wraps below 960 px; header buttons may wrap text on phones [CODE engine.css].

## Accessibility
Pinch-zoom allowed; one `h1` per page; icon links/buttons have names (menu toggle `aria-label`/`aria-expanded`, Instagram); gallery alt text (7 descriptive, 19 numbered — real descriptions [UNKNOWN]); errors in text, not colour only [CODE].

## Tone of voice
Plain, warm, short sentences; money always with ₹; messages say what to do next ("please call us…") [CODE messages].
