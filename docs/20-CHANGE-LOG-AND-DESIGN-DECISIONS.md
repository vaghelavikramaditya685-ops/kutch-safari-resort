# Change Log and Design Decisions

## Change log

### 25 Sep 2026: booking engine replaced
* **Removed** the React/Express booking engine: `client/src/booking/`, `client/src/pages/BookingPage.tsx`, `shared/booking/`, `server/booking/`, the Vite `bookingApiDev` plugin, and the `@shared` alias.
* **Added** `booking-engine/`, a standalone PHP 8 + MySQL engine (search → room → extras → pay, Razorpay + UPI QR, admin panel, guest self-service cancellation, Stayflexi bridge, per-night GST slabs).
* **Wired it in:**
  * `client/src/lib/booking.ts`: `BOOKING_URL` (`VITE_BOOKING_URL` or `/book/`) and `bookingUrl()`.
  * Every Book Now link (Navbar ×2, Home ×3, Stay) → engine.
  * `/booking`, `/book`, `/book/*` → `BookingRedirect` (forwards, or shows call/WhatsApp if the engine isn't hosted there).
  * Vite dev proxy `/book/` → `localhost:8080`. New scripts `pnpm dev:book` and `pnpm setup:book`.
  * Engine `allowed_origins` += kutchsafaribhuj.in, www, the Vercel preview, localhost:3000.
  * Local `booking-engine/config.local.php` (SQLite, git-ignored).
  * `.gitignore`: root `data/` → `/data/`. Engine keeps `data/.htaccess` tracked.
  * `.vercelignore` and `.prettierignore` exclude `booking-engine/`.
* All 20 docs rewritten to match the code.

### Earlier (before 25 Sep 2026)
* Redesign to "Sundown Terracotta" with the owner's beige `#f8f5e2`. Multi-page structure (Stay, Experiences, Our Journey, Dining, Gallery, Plan Your Visit, Packages).
* The Weddings page was removed. `/experiences` was added.
* Rooms.tsx was replaced by Stay.tsx (masonry kept at the owner's request).
* A React booking engine was built (now removed, see above).

---

## Owner directives (Mike Vaghela)
1. **"Changes that are told to you in the PDF. Don't change anything else."** Stick to the provided copy and layout.
2. **"Integrate the beige colour into the entire website."** `#f8f5e2`, taken from the logo.
3. **"Where the lake meets the desert, it should be white."** Drives the hero copy and imagery.
4. **"Keep the old masonry style for cottages."** Stay uses a two-column exterior/interior masonry.
5. Keep the offline enquiry route next to online booking.

## Technical decisions

### Why the booking engine is PHP and separate
The engine was built for a cPanel host, where PHP + MySQL run with nothing to configure and the owner can edit files directly. The previous React engine stored reservations in a JSON file, which can't persist on Vercel. Keeping the engine as its own app at `/book/`:
* lets it serve both properties (and a future separate White Rann Camp site) from one install;
* keeps payment keys and guest data off the static front-end host;
* means the React site only needs a link (`bookingUrl()`), with no shared code to keep in sync.

**Trade-off:** two stacks and two styles, and prices shown on marketing pages are separate text from the prices charged.

### Why a redirect route instead of removing `/booking`
Old links, bookmarks and ads may point at `/booking`. `BookingRedirect` forwards them. If the engine isn't deployed on that host, it shows the reservations phone and WhatsApp instead of a redirect loop.

### Why `VITE_BOOKING_URL`
The site is on Vercel and the engine needs a PHP host. One build-time variable lets the engine live on the same domain (`/book/`, the default) or anywhere else, without code changes.

### React + Vite + wouter
Fast builds. wouter (~1.5 KB) is enough for flat routes.

### Tailwind v4 (config-less)
Theme lives in CSS (`@theme inline` + `:root` tokens).

### Logo `mix-blend-darken`
The logo is a JPG with a white background. Blending on the beige hides the white without a transparent PNG.

### Express + JSON enquiries
`POST /api/contact` writes `data/enquiries.json`. This only works on a host with a disk. The engine's `api/enquiry.php` (database-backed, visible in admin) is the better target for the Home form.
