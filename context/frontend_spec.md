# Frontend Spec
> Purpose: the website and the booking page's front end | Mode: A | Confidence: High | Related: [../docs/03-ROUTING-AND-NAVIGATION.md](../docs/03-ROUTING-AND-NAVIGATION.md), [../docs/25-GUEST-BOOKING-FLOW-INTERNALS.md](../docs/25-GUEST-BOOKING-FLOW-INTERNALS.md), [design_system.md](design_system.md)

## Website routes (`frontend/src/App.tsx`) [CODE]
| Route | Page | Purpose |
|---|---|---|
| `/` | Home | hero, stay, sister property, experiences (link to guides), contact (mock form) |
| `/stay` | Stay | the two cottage types (masonry photos, lightbox) |
| `/experiences` | Experiences | 6 cards → destination guides |
| `/our-journey`, `/dining`, `/gallery`, `/plan-your-visit` (`#faq`), `/packages` | content pages |
| `/destination/:slug` | Destination | 6 guides (own old header/footer) |
| `/rann-utsav-package`, `/white-rann-camp`, `/white-rann-camp/tariff` | RannUtsavPackage | camp + tariffs (tariff route scrolls to the table) |
| `/booking`, `/book`, `/book/*`, `/admin`, `/admin/*` | BookingRedirect | full-page redirect to the engine |
| anything else | NotFound | 404 |

* **Titles:** `usePageTitle()` sets a title per route [CODE `App.tsx`].
* **Shared components:** `Navbar` (top bar with "Already booked? Check status", mobile menu with aria-label), `Footer`, `ErrorBoundary` (stack only in dev) [CODE].
* **State:** local `useState` only; no global store [CODE].
* **Engine links:** always plain `<a href>` from `lib/booking.ts` (`bookingUrl`, `statusUrl`, `adminUrl`), never wouter `<Link>` [CODE].
* **Scroll:** pages scroll to top on mount; `/plan-your-visit#faq` and `/white-rann-camp/tariff` jump (instant) to their section [CODE].

## Booking page front end (`backend/booking-engine/assets/engine.js`, ~1,000 lines) [CODE]
Steps: 1 rooms (occupancy per room, Select + room ticks for several rooms) → 2 extras → 3 details (arrival wheel, pay 50%/full, terms link) → 4 payment (Razorpay / UPI QR / I've paid (test)) → confirmation (receipt, status, remembered in `localStorage ksr_last_booking`).
* Talks to `api/*.php` via `api()` (fetch JSON) — see [api_spec.md](api_spec.md).
* Validates phone/email before sending; a server refusal about guest details keeps the step and the typed values (`res.field === 'guest'`) [CODE].
* Scripts/styles are versioned `?v=<filemtime>` so updates are picked up [CODE `index.php`].

## Admin front end
Server-rendered PHP pages with small inline scripts: on-page confirm box for any `data-confirm` form/button (no browser pop-ups), per-tab sign-in mark, Availability tape chart + side panel, SHA-256 hashing on the sign-in page [CODE `admin/_auth.php`, `calendar.php`, `login.php`].
