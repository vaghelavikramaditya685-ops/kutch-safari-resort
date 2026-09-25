# Technical Architecture

## 1. High-Level Architecture

Two independent applications live in one repository:

```
                    ┌─────────────────────────────────────────┐
 Browser ──HTTPS──► │ Marketing site (React SPA, dist/public) │  Vercel / Express
                    │   POST /api/contact  → data/enquiries   │
                    └───────────────┬─────────────────────────┘
                                    │  <a href="/book/?property=…">  (full page load)
                                    ▼
                    ┌─────────────────────────────────────────┐
                    │ Booking engine (booking-engine/, PHP 8) │  PHP host, e.g. cPanel /book
                    │   index.php, manage.php, api/*.php      │
                    │   admin/*.php                           │──► MySQL (SQLite locally)
                    │   Razorpay · UPI QR · Stayflexi         │
                    └─────────────────────────────────────────┘
```

* The React site only **links** to the engine. It shares no code, data or session with it.
* The engine's pages use relative paths (`assets/…`, `api/…`), so it runs unchanged under any folder, e.g. `/book/`.

---

## 2. Directory Structure

```text
kutch-safari-resort/
├── client/
│   ├── index.html              # SPA shell, meta tags, fonts
│   ├── public/                 # static files served at / (assets/, robots.txt, sitemap.xml, vercel.json)
│   └── src/
│       ├── App.tsx             # routes
│       ├── main.tsx            # mount point
│       ├── index.css           # Tailwind 4 theme + custom classes
│       ├── components/         # Navbar, Footer, ErrorBoundary, ui/ (button, card, sonner, tooltip)
│       ├── contexts/           # ThemeContext
│       ├── lib/
│       │   ├── utils.ts        # cn()
│       │   └── booking.ts      # BOOKING_URL + bookingUrl() — links into the engine
│       └── pages/              # one file per route (see 03-ROUTING-AND-NAVIGATION.md)
├── server/index.ts             # Express: static files, POST /api/contact, SPA fallback
├── api/contact.ts              # Vercel function (logs only)
├── booking-engine/             # PHP + MySQL booking engine (own README.md)
│   ├── config.php              # every setting; overridden by config.local.php (git-ignored)
│   ├── index.php, manage.php   # guest booking flow, "my booking"
│   ├── api/                    # JSON endpoints used by assets/engine.js
│   ├── admin/                  # staff panel
│   ├── lib/                    # db, inventory/pricing, booking, payment, channel, mail
│   ├── bin/                    # setup + cron scripts (CLI only)
│   ├── assets/                 # engine.css, engine.js, WebP room photos
│   ├── schema.sql, seed.sql    # database + starting data
│   └── data/                   # SQLite file in local dev (git-ignored except .htaccess)
├── docs/                       # these documents
├── vite.config.ts, tsconfig.json, package.json
└── *.py, old_rooms.tsx         # leftover one-off edit scripts (safe to delete)
```

---

## 3. Build Pipeline

### 3.1 Vite 7
* Root is `client/`; output goes to `dist/public`.
* Alias: `@/` → `client/src/`.
* **Dev proxy:** `/book/` → `http://localhost:8080` with the `/book` prefix stripped. This lets `pnpm dev` show the engine on the same origin.

### 3.2 Server bundle
`pnpm build` runs `vite build` and then bundles `server/index.ts` with esbuild into `dist/index.js`.

### 3.3 Booking engine
It has no build step. Upload the folder as it is. Run `php bin/setup.php` once to create tables and load `seed.sql`.

### 3.4 TypeScript
`pnpm check` (`tsc --noEmit`) covers `client/src`, `server` and `api`. The PHP engine is not part of it.

---

## 4. Application Logic

### 4.1 Routing
wouter `<Switch>` in `App.tsx`. `/booking`, `/book` and `/book/*` render `BookingRedirect`, which does a full-page redirect to `bookingUrl()`. If the SPA itself is answering `/book/` (the engine isn't deployed on that host), it shows call/WhatsApp buttons instead of looping.

### 4.2 State
Local `useState` only. There is no global store. `ThemeContext` is fixed to light.

### 4.3 Error handling
`ErrorBoundary` wraps the app. It shows the error stack and a reload button.

### 4.4 Express server
* `express.static(dist/public)`
* `POST /api/contact` appends the request body to `data/enquiries.json` (root `/data/`, git-ignored). The front end does not call it yet.
* `GET *` returns `index.html`.

### 4.5 Booking engine internals (summary)
* `lib/inventory.php` handles availability and pricing: rate plans per room (CP/MAP/AP, MAPAI), date overrides in `rates`, and GST slabs worked out per night.
* `lib/booking.php` handles quote, create and cancel, with a refund ladder from `config.php`.
* `lib/payment.php` handles Razorpay orders, signature and webhook verification, and the UPI QR (confirmed manually in admin).
* `lib/channel.php` is the Stayflexi adapter. It is off by default. Its endpoint paths are unconfirmed guesses.
* Data lives in 18 tables (`schema.sql`): properties, room_types, rate_plans, rates, inventory, addons, packages, package_prices, bookings, booking_rooms, booking_addons, payments, holds, enquiries, coupons, admin_users, audit_log, settings.

---

## 5. Security & Performance
* The engine's `.htaccess` blocks `config*.php`, `*.sql`, `*.sqlite`, `*.md`, `data/` and `bin/`, and forces HTTPS. It only works on Apache. Other hosts need equivalent rules.
* Real keys go in `booking-engine/config.local.php`, which is git-ignored. Set `debug => false` in production.
* CORS: the engine only answers origins listed in `allowed_origins` in `config.php`. The kutchsafaribhuj.in domains and localhost:3000 are included.
* `/api/contact` does not validate or sanitise input.
* Performance: `client/public/assets` is about 269 MB of unoptimised media (see `11-IMAGE-ASSET-INVENTORY.md`). The engine's own photos are already WebP (3.1 MB in total).
