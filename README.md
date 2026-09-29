# Kutch Safari Resort

Marketing site for Kutch Safari Resort, Bhuj. React 19 + Vite 7 + Tailwind 4,
client-side routing via wouter, with a small Express server for static hosting
and a contact endpoint.

## Setup

```bash
pnpm install
pnpm dev          # http://localhost:3000
```

## Scripts

| Script | What it does |
| --- | --- |
| `pnpm dev` | Vite dev server |
| `pnpm build` | Builds client to `dist/public` and server bundle to `dist/index.js` |
| `pnpm start` | Serves the production build |
| `pnpm check` | TypeScript typecheck (`tsc --noEmit`) |
| `pnpm format` | Prettier |

## Layout

```
frontend/
  index.html
  public/assets/      static images and video served at /assets/...
  src/
    pages/            Home, Stay, Experiences, Destination, RannUtsavPackage, …
    components/ui/    shadcn components in use (button, card, sonner, tooltip)
    contexts/         ThemeContext
    lib/booking.ts    links into the booking engine
backend/
  booking-engine/     PHP + MySQL booking engine, served at /book/
  server/index.ts     Express static server + POST /api/contact
api/contact.ts        Vercel serverless contact handler (stays at the root: Vercel needs /api there)
docs/                 30 project docs; docs/notes/ holds ideas.md and todo.md
scripts/legacy/       old one-off edit scripts, kept for history only (unused)
```

Routes: `/`, `/stay`, `/experiences`, `/our-journey`, `/dining`, `/gallery`,
`/plan-your-visit`, `/packages`, `/destination/:slug`, `/rann-utsav-package`
(also `/white-rann-camp`). `/booking` forwards to the booking engine.

## Booking engine

`backend/booking-engine/` is a standalone **PHP 8 + MySQL** app (its own README has the
full cPanel setup, Razorpay, UPI QR and Stayflexi notes). The React site does not
contain booking code — every Book Now button links to it via
`bookingUrl()` in `frontend/src/lib/booking.ts`:

```
/book/?property=kutch-safari-resort&check_in=YYYY-MM-DD&check_out=…&adults=2&rooms=1
```

`property` is `kutch-safari-resort` or `white-rann-camp`. The old `/booking` and
`/book` routes forward there.

**Run it locally** (needs PHP 8 with `pdo_sqlite` enabled; no MySQL needed —
`backend/booking-engine/config.local.php` switches it to SQLite):

```bash
pnpm setup:book   # once: creates backend/booking-engine/data/booking.sqlite
pnpm dev:book     # PHP on :8080
pnpm dev          # site on :3000, proxies /book/ to :8080
```

Admin panel: `/book/admin/`. Create a login with
`php backend/booking-engine/bin/setup.php --admin "email" "Name" "password"`.

**Production.** Vercel cannot run PHP. Upload `backend/booking-engine/` to a PHP host
(cPanel `public_html/book`) and either serve the site from the same domain, or
build the site with `VITE_BOOKING_URL=https://that-host/book/`. Real keys go in
`config.local.php` on the server, never in git. Add the site's domain to
`allowed_origins` in `backend/booking-engine/config.php`.

## Notes

- Images are referenced by absolute path (`/assets/...`) from `frontend/public`,
  not imported, so unused files are not tree-shaken — check references before
  adding or removing media.
- `frontend/public/assets` is ~215 MB of unoptimized originals; compressing and
  converting to WebP/AVIF is the single biggest available win for page weight.
