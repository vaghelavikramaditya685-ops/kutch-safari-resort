# Kutch Safari Resort — website and booking engine

_Docs combined into 10 files on 30 Sep 2026 (from 70). Last content update: 30 Sep 2026._

The website and direct-booking system for **Kutch Safari Resort**, 20 lake-view cottages near Bhuj, Kutch, Gujarat:
a **React 19 + Vite** marketing site (`frontend/`) and a **PHP 8 booking engine + admin panel** (`backend/booking-engine/`, served at `/book/`).

> **New here (person or AI)?** Read this file top to bottom (overview, hard rules, AI instructions),
> then open the doc for your area below. Every old doc is still here as a section, titled with its
> old name ("was `docs/16-…`"), so references like "doc 22 §3" can be found by searching for `docs/22`.

## The 10 documents
| File | What's in it | Was |
|---|---|---|
| [`BUGS.md`](BUGS.md) | Bugs found in the 30 Sep 2026 test (demo data, heavy traffic), to fix next |
| [`CLAUDE.md`](CLAUDE.md) | Map of every .md file and every section inside it, plus the rules to follow first |
| `README.md` (this file) | Overview, hard rules for AI/devs, project overview, quick start | context/overview, context/ai_instructions, docs/01, old README |
| [`docs/01-ARCHITECTURE-AND-SETUP.md`](docs/01-ARCHITECTURE-AND-SETUP.md) | How the two apps fit together, where every file lives, and how to install and run them. | context/architecture, context/tech_stack, context/folder_structure, context/env_and_setup, docs/02-ARCHITECTURE, docs/18-COMPONENT-DEPENDENCY-MAP |
| [`docs/02-WEBSITE.md`](docs/02-WEBSITE.md) | Routes, menu, every page, shared components, SEO, forms and user journeys of the React site. | context/frontend_spec, docs/03-ROUTING-AND-NAVIGATION, docs/05-HOME-PAGE, docs/06-ACCOMMODATION-PAGES, docs/07-DESTINATION-SYSTEM, docs/10-CONTENT-PAGES, docs/09-SHARED-COMPONENTS, docs/12-SEO-AND-META, docs/13-FORMS-AND-INTERACTIONS, docs/19-USER-FLOW-AND-UX |
| [`docs/03-DESIGN-AND-ASSETS.md`](docs/03-DESIGN-AND-ASSETS.md) | Colours, fonts, CSS rules for the site and the engine, and the image/video inventory. | context/design_system, docs/04-DESIGN-SYSTEM, docs/14-STYLING-DEEP-DIVE, docs/11-IMAGE-ASSET-INVENTORY |
| [`docs/04-BUSINESS-CONTENT-AND-PRICES.md`](docs/04-BUSINESS-CONTENT-AND-PRICES.md) | Facts about the resort, the owner's brochure, published prices and packages, goals and user stories. | context/prd, context/user_stories, docs/17-BUSINESS-CONTENT-REFERENCE, docs/08-PACKAGES-AND-PRICING |
| [`docs/05-BOOKING-ENGINE.md`](docs/05-BOOKING-ENGINE.md) | The PHP engine: modules, API, pricing, changes and money, availability, the guest flow, payments and Stayflexi. | context/backend_spec, context/api_spec, context/user_flows, backend/booking-engine/README, backend/booking-engine/PROJECT-CONTEXT, docs/21-PRICING-LOGIC-DEEP-DIVE, docs/22-BOOKING-CHANGES-AND-MONEY, docs/23-AVAILABILITY-AND-INVENTORY-LOGIC, docs/25-GUEST-BOOKING-FLOW-INTERNALS, docs/26-PAYMENTS-RAZORPAY-UPI-TEST, docs/27-STAYFLEXI-CHANNEL-SYNC |
| [`docs/06-ADMIN-AND-SECURITY.md`](docs/06-ADMIN-AND-SECURITY.md) | Every admin screen, and how sign-in (SHA-256), sessions, tokens and input checks work. | context/auth_and_security, docs/24-ADMIN-PANEL-GUIDE |
| [`docs/07-DATABASE.md`](docs/07-DATABASE.md) | The tables, hidden couplings between them, and the rules for real vs test/demo data. | context/data_models, docs/28-DATABASE-AND-DATA-RULES |
| [`docs/08-STATUS-ISSUES-AND-ROADMAP.md`](docs/08-STATUS-ISSUES-AND-ROADMAP.md) | What works, what doesn't, every known issue, decisions, open questions for the owner, and what's next. | context/current_status, context/features, docs/16-KNOWN-ISSUES-AND-BUGS, context/decisions_and_assumptions, context/roadmap, docs/notes/todo, docs/notes/ideas |
| [`docs/10-GO-LIVE-ON-HOSTINGER.md`](docs/10-GO-LIVE-ON-HOSTINGER.md) | The planned live setup on Hostinger (`kutchsafaribhuj.in`, `book.`, `admin.`): local vs live addresses, every URL/setting that changes at upload time, the tested upload recipe, Hostinger and DNS steps. | new, 5 Oct 2026 |
| [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md) | What changed and why, what broke and what we learned, how to test, how to deploy, and the 29 Sep 2026 check reports. | docs/20-CHANGE-LOG-AND-DESIGN-DECISIONS, docs/29-LESSONS-LEARNED-AND-GOTCHAS, context/testing_and_deployment, docs/30-TESTING-GO-LIVE-AND-HANDOVER, docs/15-DEPLOYMENT-AND-INFRASTRUCTURE, restructure/REPORT, restructure/CHANGELOG, restructure/MOVE_MAP, restructure/CURRENT_TREE, restructure/TARGET_TREE, heal/REPORT, heal/FINDINGS, heal/CHANGELOG, heal/RUN_LOG, heal/STATE, button_audit/REPORT, chaos/REPORT, chaos/FINDINGS, chaos/CHANGELOG, chaos/TARGET_MAP |

---

## Contents
* [The 10 documents](#the-10-documents)
* [Overview](#context-overview) _(was `context/overview.md`)_
* [Instructions for AI Agents and Developers](#context-ai-instructions) _(was `context/ai_instructions.md`)_
* [Project Overview: Kutch Safari Resort Website](#docs-01) _(was `docs/01-PROJECT-OVERVIEW.md`)_
* [Quick start and repository layout](#quick-start) _(was `README.md`)_
* [Where each old file went](#where-each-old-file-went)

---

<a id="context-overview"></a>

## Overview
_(was `context/overview.md`)_
**What it is.** The website and direct-booking system for **Kutch Safari Resort**, a family-run resort of 20 lake-view cottages near Bhuj, Gujarat [DOC]. It has two apps in one repository [CODE [`README.md`](#quick-start)]:
1. a **marketing website** (React 19 + Vite) with the resort's pages, destination guides and "Book Now" links [CODE `frontend/`], and
2. a **PHP booking engine** where guests search, pick cottages and extras, and pay 50% or in full, plus an **admin panel** for the owner to manage bookings, availability, special prices and enquiries [CODE `backend/booking-engine/`].

**Who it's for.** Guests booking a stay (domestic and international travellers heading to the White Rann) [DOC], and the owner/front desk (admin user `manvir`) [CODE `data/booking.sqlite` admin_users].

**Status (5 Oct 2026).** The **website is live on Vercel** (`https://kutch-safari-resort.vercel.app`) **without online booking**: every Book Now is an enquiry (WhatsApp/email). The booking engine and admin run only on the owner's PC, end to end with test payments; not yet deployed; Razorpay on test keys, Stayflexi not connected, sample mode and test payments still on [CODE `config.php`, `config.local.php`]. See [current_status.md](docs/08-STATUS-ISSUES-AND-ROADMAP.md#context-current-status).

**Latest (30 Sep 2026).** Home welcome text replaced with the owner's wording; **Experiences** rebuilt from the owner's 2026–27 brochure (PDF); new menu tab **Around the Resort** (`/around-the-resort`) for places like Mandvi Beach → destination guides; full desktop menu from 1,320 px. Brochure vs website fact differences await the owner [DOC `docs/17`, `docs/20`].

**Tech in one line.** React 19 / Vite 7 / Tailwind 4 / wouter (site) + PHP 8.3 / SQLite locally, MySQL on the host (engine), Razorpay + UPI QR, Stayflexi bridge (off) [CODE `package.json`, `backend/booking-engine/lib/`].

### Reading order
* **AI agent about to change code:** the hard rules and AI instructions below (first, always) → [`docs/08-STATUS-ISSUES-AND-ROADMAP.md`](docs/08-STATUS-ISSUES-AND-ROADMAP.md) (status, known issues, open questions) → the doc for the area → [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md) (how to test; lessons learned).
* **New developer:** this file → [`docs/01-ARCHITECTURE-AND-SETUP.md`](docs/01-ARCHITECTURE-AND-SETUP.md) → [`docs/02-WEBSITE.md`](docs/02-WEBSITE.md) and [`docs/05-BOOKING-ENGINE.md`](docs/05-BOOKING-ENGINE.md) → [`docs/07-DATABASE.md`](docs/07-DATABASE.md) → [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md).
* **Owner / non-technical:** this file → [`docs/04-BUSINESS-CONTENT-AND-PRICES.md`](docs/04-BUSINESS-CONTENT-AND-PRICES.md) → [`docs/08-STATUS-ISSUES-AND-ROADMAP.md`](docs/08-STATUS-ISSUES-AND-ROADMAP.md) (what's left and the questions waiting for you).

Each doc starts with a summary section (the old `context/` file) followed by the detailed sections (the old `docs/NN` files).

---

<a id="context-ai-instructions"></a>

## Instructions for AI Agents and Developers
_(was `context/ai_instructions.md`)_
### Hard rules (owner's)
1. **Do not push to GitHub** unless the owner asks in that conversation. Committing locally is fine when asked. [DOC]
2. **No invented data in the real database** (`backend/booking-engine/data/booking.sqlite`). Test on a throwaway copy; delete it after. Demo data only when the owner asks, marked "Demo …" / @example.com. [DOC]
3. **Never run full `bin/setup.php` on a database with real data** — it empties rooms, prices, special prices and extras. `--admin` alone is safe. Since 4 Oct 2026 the script enforces this: it refuses unknown options (e.g. a typo such as `--amdin`) and refuses a full setup when bookings exist unless `--reset` is given, and a reload that half-fails ends with FAILED instead of "Ready". [CODE seed.sql, bin/setup.php]
4. **Secrets** only in `config.local.php`; never commit, print or copy them. [CODE .gitignore]
5. **Keep the colours**; the owner approves visual changes. [DOC]
6. Don't type the owner's real passwords anywhere; the local test login is for local testing only. [DOC]

### Conventions
* PHP: plain functions in `lib/`, `snake_case`, PDO helpers `q/q1/qval/insert/update`, `money()` for rounding, `cfg('a.b')` for settings, `audit()` for money/inventory actions. [CODE]
* Validate every input on the server with the helpers in `lib/db.php` (`LIMITS`, `too_long`, `valid_phone`, `valid_date`, `valid_arrival_time`); messages are plain sentences telling the user what to do. [CODE]
* Admin pages: `require_login()`, `check_csrf()`, `admin_head()` / `admin_foot()`; escape output with `h()`; destructive actions use `data-confirm` (never `confirm()`). [CODE]
* Website: engine links only via `lib/booking.ts` and plain `<a>`; new routes also get a title in `App.tsx` and a sitemap entry. [CODE]
* Comments explain *why*, match the surrounding density. [CODE style]

### Unsafe areas (read the doc first)
| Area | Why | Read |
|---|---|---|
| `price_rooms`, `tax_split` | every price flows through them | [05 → docs/21](docs/05-BOOKING-ENGINE.md#docs-21) |
| `modification_delta`, `booking_money` | owner-defined money rules | [05 → docs/22](docs/05-BOOKING-ENGINE.md#docs-22) |
| `rooms_booked` | overselling | [05 → docs/23](docs/05-BOOKING-ENGINE.md#docs-23) |
| `booking_rooms.rate_plan_name` format | occupancy is parsed from it everywhere | [07 → docs/28 §2](docs/07-DATABASE.md#docs-28) |
| `admin/login.php`, `_auth.php` | SHA-256 sign-in contract with `setup.php` | [06](docs/06-ADMIN-AND-SECURITY.md#context-auth-and-security) |
| `lib/channel.php` | Stayflexi, unverified endpoints | [05 → docs/27](docs/05-BOOKING-ENGINE.md#docs-27) |

### How to verify a change
1. `php -l` on changed PHP; `pnpm check`; `pnpm build`.
2. `php backend/booking-engine/bin/test-changes.php` (17/17), `bin/test-logic.php` (29/29), `bin/test-concurrency.php` (12/12) and `bin/check-system.php`. All work on a temporary copy of the database.
3. For flows: run a copy of the engine on another port with a copy of the DB (see `chaos/EVIDENCE/`), never the real one.
4. Open the pages in a browser; check the console and that nothing scrolls sideways at 375 px.

### Which file for which task
Pricing, changes and money, availability, guest page, payments, Stayflexi → [`docs/05-BOOKING-ENGINE.md`](docs/05-BOOKING-ENGINE.md) · Admin and sign-in → [`docs/06-ADMIN-AND-SECURITY.md`](docs/06-ADMIN-AND-SECURITY.md) · Website pages → [`docs/02-WEBSITE.md`](docs/02-WEBSITE.md) · Look and images → [`docs/03-DESIGN-AND-ASSETS.md`](docs/03-DESIGN-AND-ASSETS.md) · Database → [`docs/07-DATABASE.md`](docs/07-DATABASE.md) · Setup → [`docs/01-ARCHITECTURE-AND-SETUP.md`](docs/01-ARCHITECTURE-AND-SETUP.md) · Testing and deploy → [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md) · Business facts and prices → [`docs/04-BUSINESS-CONTENT-AND-PRICES.md`](docs/04-BUSINESS-CONTENT-AND-PRICES.md).

### Glossary
* **Pending / confirmed / lapsed** — unpaid, paid, unpaid past the 45-minute window.
* **Due now / before arrival** — what must be paid now (to 100% or 50%) / the rest on the 50% plan.
* **Special price** — a nightly price override for dates (`rates`).
* **Sample mode** — guest details optional (`rules.guest_details_optional`).
* **manage_token** — a booking's private code in status/receipt links.
* **Demo booking** — owner-requested fake booking, named "Demo …".

---

<a id="docs-01"></a>

## Project Overview: Kutch Safari Resort Website
_(was `docs/01-PROJECT-OVERVIEW.md`)_
_Last updated: 26 Sep 2026. The PHP booking engine in `backend/booking-engine/` now handles booking, desk changes priced as a difference, 50%/full payment tracking and the admin panel. Docs 21–30 explain the logic and the lessons learned._

### 1. Business Context
Kutch Safari Resort is a family-run resort near Bhuj, Gujarat, operating for over 35 years. Founder: Mike Vaghela.

#### 1.1 Properties
* **Kutch Safari Resort** — Near Rudramata Dam, Bhuj–Khavda Road, Bhuj, Kutch, Gujarat 370001, about 15 km from Bhuj. Open all year.
  * 20 lake-view cottages: 12 Kutchi AC Cottages and 8 Deluxe AC Cottages.
  * Restaurant: The Banni (multi-cuisine, veg and non-veg). Pool and garden lawn (events up to 300).
* **White Rann Camp** (sister property; **switched off in the booking engine for now**, enquiries by WhatsApp) — Dhordo, three minutes from the White Rann entry and Rann Utsav. **Seasonal: 1 Dec 2026 – 31 Jan 2027.**
  * 20 Swiss tents: 6 Deluxe Air-Cool and 14 Non-AC, each with an attached bathroom.
* **Colors of Kutch** tour packages (2N/3D and 3N/4D), priced per person by group size.

#### 1.2 Audience
Domestic and international travellers heading to the White Rann, Rann Utsav guests, people interested in the culture and craft villages, and groups and families.

#### 1.3 Contact
* Phone / WhatsApp: +91 99252 38599
* Email: kutchsafaribhuj@yahoo.com
* Instagram: @kutchsafariresort

---

### 2. What the repository contains

| Part | Stack | Purpose |
|---|---|---|
| Marketing site (`frontend/`) | React 19, Vite 7, Tailwind 4, TypeScript, wouter | All public pages |
| Server (`backend/server/index.ts`) | Express 4 | Serves the built site and `POST /api/contact` |
| Serverless contact (`api/contact.ts`) | Vercel function | Logs enquiries only (does not store them) |
| **Booking engine (`backend/booking-engine/`)** | **PHP 8 + MySQL (SQLite locally)** | **Search → room → extras → pay; admin panel; Razorpay + UPI QR; Stayflexi bridge** |

The booking engine is a **separate application**. The React site holds no booking code. Every "Book Now" button links to the engine at `/book/` (see `frontend/src/lib/booking.ts`). Details are in [`02-ARCHITECTURE.md`](docs/01-ARCHITECTURE-AND-SETUP.md#docs-02) and [`backend/booking-engine/README.md`](docs/05-BOOKING-ENGINE.md#engine-readme).

#### 2.1 Front-end libraries
* Icons: lucide-react
* UI primitives: shadcn/ui (only `button`, `card`, `sonner`, `tooltip` are present)
* Toasts: sonner
* Fonts: Cormorant Garamond (display), Jost (body) from Google Fonts

#### 2.2 Theme: "Sundown Terracotta"
* Page background: beige `#f8f5e2` (owner's directive, taken from the logo)
* Terracotta accent: `oklch(0.55 0.16 35)` (`--terracotta`)
* Ink: `oklch(0.28 0.03 50)` (`--ink`)

---

### 3. Environments
* **Production domain:** `https://kutchsafaribhuj.in` (Vercel)
* **Preview:** `https://kutch-safari-resort.vercel.app`
* **Booking engine:** must run on a PHP host (e.g. cPanel `public_html/book`). Vercel cannot run it. See [`15-DEPLOYMENT-AND-INFRASTRUCTURE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#docs-15). The planned live setup on Hostinger is in [`docs/10-GO-LIVE-ON-HOSTINGER.md`](docs/10-GO-LIVE-ON-HOSTINGER.md).
* **Old WRC portal:** `https://whiteranncamp.travstack.com` (still linked from the Navbar placeholder button)

---

### 4. Quick start

```bash
pnpm install
pnpm setup:book   # once — creates the local SQLite booking database (needs PHP 8)
pnpm dev:book     # booking engine on :8080
pnpm dev          # site on :3000; /book/ is proxied to :8080
```

Without PHP installed, `pnpm dev` still runs the site. Book Now buttons then have nothing to open.

---

### 5. Current state (summary)
Working: every route (including `/around-the-resort` and `/white-rann-camp/tariff`, 30 Sep 2026), destination guides, the brochure-based Experiences page, the booking engine locally (search → rooms → extras → details → pay, check status, receipts, admin), and the typecheck and production build.

The booking database on this PC has **7 bookings**: the owner's own KSR-GJKQYG and six **demo** bookings added at the owner's request on 29 Sep 2026 (guest names start "Demo", emails end @example.com) to explore the site. Sample mode and test payments are still **on**. See the go-live checklist in [`30-TESTING-GO-LIVE-AND-HANDOVER.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#docs-30).

**Where to read what:** see [The 10 documents](#the-10-documents) at the top of this file, and [Where each old file went](#where-each-old-file-went) at the bottom.

Outstanding, detailed in [`16-KNOWN-ISSUES-AND-BUGS.md`](docs/08-STATUS-ISSUES-AND-ROADMAP.md#docs-16):
1. ~~The Home contact form is a mock~~ fixed 5 Oct 2026: enquiries go to WhatsApp/email (not stored by the site).
2. ~~Broken links~~ fixed 29 Sep 2026. Brochure vs website facts (distances, years, room names) await the owner's answer (doc 17).
3. Placeholder content: Dining placeholder box, FAQ, `[WRC LOGO]`. (The Our Journey page was removed on 5 Oct 2026.)
4. Assets are ~140 MB, mostly unused files and videos. The photos were compressed on 6 Oct 2026 (136.7 MB → 6.4 MB); the 10.2 MB Home hero video is not. (The 4.5 MB favicon was replaced by a 9 KB `favicon.ico` on 29 Sep 2026.)
5. The booking engine is not yet deployed. Its rates, GST, the 50% balance date and cancellation terms need the owner's sign-off.
6. Stayflexi is not connected, and Razorpay is on test keys only.
7. Six demo bookings to remove before launch; brochure photos are low resolution.

**Latest changes (30 Sep 2026):** new Home welcome text, Experiences rebuilt from the brochure, new "Around the Resort" menu tab, full menu from 1,320 px. See doc 20.

---

<a id="quick-start"></a>

## Quick start and repository layout
_(was `README.md`)_
Marketing site for Kutch Safari Resort, Bhuj. React 19 + Vite 7 + Tailwind 4,
client-side routing via wouter, with a small Express server for static hosting
and a contact endpoint, plus a separate PHP booking engine.


### Setup

```bash
pnpm install
pnpm dev          # http://localhost:3000
```

### Scripts

| Script | What it does |
| --- | --- |
| `pnpm dev` | Vite dev server |
| `pnpm build` | Builds client to `dist/public` and server bundle to `dist/index.js` |
| `pnpm start` | Serves the production build |
| `pnpm check` | TypeScript typecheck (`tsc --noEmit`) |
| `pnpm format` | Prettier |

### Layout

```
frontend/
  index.html
  public/assets/      static images and video served at /assets/...
  src/
    pages/            Home, Stay, Experiences, AroundTheResort, Destination, RannUtsavPackage, …
    components/ui/    shadcn components in use (button, card, sonner, tooltip)
    contexts/         ThemeContext
    lib/booking.ts    links into the booking engine
backend/
  booking-engine/     PHP + MySQL booking engine, served at /book/
  server/index.ts     Express static server + POST /api/contact
api/contact.ts        Vercel serverless contact handler (stays at the root: Vercel needs /api there)
docs/                 10 project docs (01–10); all Markdown lives in README.md + docs/
chaos/EVIDENCE/       scripts used by the 29 Sep 2026 chaos test (the reports are in docs/09)
```

Routes: `/`, `/stay`, `/experiences` (brochure content), `/around-the-resort`
(places → guides), `/dining`, `/gallery`, `/plan-your-visit`,
`/packages`, `/destination/:slug`, `/rann-utsav-package` (also `/white-rann-camp`
and `/white-rann-camp/tariff`). `/booking`, `/book` and `/admin` forward to the
booking engine.

### Booking engine

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
`php backend/booking-engine/bin/setup.php --admin "username" "Name" "password"`
(stored as bcrypt of the SHA-256; the sign-in page hashes with SHA-256 in the
browser). **Never run `setup.php` without `--admin` on real data**: it reloads
`seed.sql` and wipes prices.

**Production.** Vercel cannot run PHP. Upload `backend/booking-engine/` to a PHP host
(cPanel `public_html/book`) and either serve the site from the same domain, or
build the site with `VITE_BOOKING_URL=https://that-host/book/`. Real keys go in
`config.local.php` on the server, never in git. Add the site's domain to
`allowed_origins` in `backend/booking-engine/config.php`.

### Notes

- Images are referenced by absolute path (`/assets/...`) from `frontend/public`,
  not imported, so unused files are not tree-shaken — check references before
  adding or removing media.
- The photos in `frontend/public/assets` were compressed on 6 Oct 2026 (resized to
  at most 1920 px, JPEG quality ~80). New photos from a phone or camera are 5–17 MB
  each: shrink them the same way before adding them. The 10.2 MB hero video and
  ~122 MB of unused files are the remaining weight.

---

## Where each old file went

| Old file | Now in |
|---|---|
| `context/overview.md` | [`README.md`](#context-overview) |
| `context/ai_instructions.md` | [`README.md`](#context-ai-instructions) |
| `docs/01-PROJECT-OVERVIEW.md` | [`README.md`](#docs-01) |
| `README.md` | [`README.md`](#quick-start) |
| `context/architecture.md` | [`docs/01-ARCHITECTURE-AND-SETUP.md`](docs/01-ARCHITECTURE-AND-SETUP.md#context-architecture) |
| `context/tech_stack.md` | [`docs/01-ARCHITECTURE-AND-SETUP.md`](docs/01-ARCHITECTURE-AND-SETUP.md#context-tech-stack) |
| `context/folder_structure.md` | [`docs/01-ARCHITECTURE-AND-SETUP.md`](docs/01-ARCHITECTURE-AND-SETUP.md#context-folder-structure) |
| `context/env_and_setup.md` | [`docs/01-ARCHITECTURE-AND-SETUP.md`](docs/01-ARCHITECTURE-AND-SETUP.md#context-env-and-setup) |
| `docs/02-ARCHITECTURE.md` | [`docs/01-ARCHITECTURE-AND-SETUP.md`](docs/01-ARCHITECTURE-AND-SETUP.md#docs-02) |
| `docs/18-COMPONENT-DEPENDENCY-MAP.md` | [`docs/01-ARCHITECTURE-AND-SETUP.md`](docs/01-ARCHITECTURE-AND-SETUP.md#docs-18) |
| `context/frontend_spec.md` | [`docs/02-WEBSITE.md`](docs/02-WEBSITE.md#context-frontend-spec) |
| `docs/03-ROUTING-AND-NAVIGATION.md` | [`docs/02-WEBSITE.md`](docs/02-WEBSITE.md#docs-03) |
| `docs/05-HOME-PAGE.md` | [`docs/02-WEBSITE.md`](docs/02-WEBSITE.md#docs-05) |
| `docs/06-ACCOMMODATION-PAGES.md` | [`docs/02-WEBSITE.md`](docs/02-WEBSITE.md#docs-06) |
| `docs/07-DESTINATION-SYSTEM.md` | [`docs/02-WEBSITE.md`](docs/02-WEBSITE.md#docs-07) |
| `docs/10-CONTENT-PAGES.md` | [`docs/02-WEBSITE.md`](docs/02-WEBSITE.md#docs-10) |
| `docs/09-SHARED-COMPONENTS.md` | [`docs/02-WEBSITE.md`](docs/02-WEBSITE.md#docs-09) |
| `docs/12-SEO-AND-META.md` | [`docs/02-WEBSITE.md`](docs/02-WEBSITE.md#docs-12) |
| `docs/13-FORMS-AND-INTERACTIONS.md` | [`docs/02-WEBSITE.md`](docs/02-WEBSITE.md#docs-13) |
| `docs/19-USER-FLOW-AND-UX.md` | [`docs/02-WEBSITE.md`](docs/02-WEBSITE.md#docs-19) |
| `context/design_system.md` | [`docs/03-DESIGN-AND-ASSETS.md`](docs/03-DESIGN-AND-ASSETS.md#context-design-system) |
| `docs/04-DESIGN-SYSTEM.md` | [`docs/03-DESIGN-AND-ASSETS.md`](docs/03-DESIGN-AND-ASSETS.md#docs-04) |
| `docs/14-STYLING-DEEP-DIVE.md` | [`docs/03-DESIGN-AND-ASSETS.md`](docs/03-DESIGN-AND-ASSETS.md#docs-14) |
| `docs/11-IMAGE-ASSET-INVENTORY.md` | [`docs/03-DESIGN-AND-ASSETS.md`](docs/03-DESIGN-AND-ASSETS.md#docs-11) |
| `context/prd.md` | [`docs/04-BUSINESS-CONTENT-AND-PRICES.md`](docs/04-BUSINESS-CONTENT-AND-PRICES.md#context-prd) |
| `context/user_stories.md` | [`docs/04-BUSINESS-CONTENT-AND-PRICES.md`](docs/04-BUSINESS-CONTENT-AND-PRICES.md#context-user-stories) |
| `docs/17-BUSINESS-CONTENT-REFERENCE.md` | [`docs/04-BUSINESS-CONTENT-AND-PRICES.md`](docs/04-BUSINESS-CONTENT-AND-PRICES.md#docs-17) |
| `docs/08-PACKAGES-AND-PRICING.md` | [`docs/04-BUSINESS-CONTENT-AND-PRICES.md`](docs/04-BUSINESS-CONTENT-AND-PRICES.md#docs-08) |
| `context/backend_spec.md` | [`docs/05-BOOKING-ENGINE.md`](docs/05-BOOKING-ENGINE.md#context-backend-spec) |
| `context/api_spec.md` | [`docs/05-BOOKING-ENGINE.md`](docs/05-BOOKING-ENGINE.md#context-api-spec) |
| `context/user_flows.md` | [`docs/05-BOOKING-ENGINE.md`](docs/05-BOOKING-ENGINE.md#context-user-flows) |
| `backend/booking-engine/README.md` | [`docs/05-BOOKING-ENGINE.md`](docs/05-BOOKING-ENGINE.md#engine-readme) |
| `backend/booking-engine/PROJECT-CONTEXT.md` | [`docs/05-BOOKING-ENGINE.md`](docs/05-BOOKING-ENGINE.md#engine-project-context) |
| `docs/21-PRICING-LOGIC-DEEP-DIVE.md` | [`docs/05-BOOKING-ENGINE.md`](docs/05-BOOKING-ENGINE.md#docs-21) |
| `docs/22-BOOKING-CHANGES-AND-MONEY.md` | [`docs/05-BOOKING-ENGINE.md`](docs/05-BOOKING-ENGINE.md#docs-22) |
| `docs/23-AVAILABILITY-AND-INVENTORY-LOGIC.md` | [`docs/05-BOOKING-ENGINE.md`](docs/05-BOOKING-ENGINE.md#docs-23) |
| `docs/25-GUEST-BOOKING-FLOW-INTERNALS.md` | [`docs/05-BOOKING-ENGINE.md`](docs/05-BOOKING-ENGINE.md#docs-25) |
| `docs/26-PAYMENTS-RAZORPAY-UPI-TEST.md` | [`docs/05-BOOKING-ENGINE.md`](docs/05-BOOKING-ENGINE.md#docs-26) |
| `docs/27-STAYFLEXI-CHANNEL-SYNC.md` | [`docs/05-BOOKING-ENGINE.md`](docs/05-BOOKING-ENGINE.md#docs-27) |
| `context/auth_and_security.md` | [`docs/06-ADMIN-AND-SECURITY.md`](docs/06-ADMIN-AND-SECURITY.md#context-auth-and-security) |
| `docs/24-ADMIN-PANEL-GUIDE.md` | [`docs/06-ADMIN-AND-SECURITY.md`](docs/06-ADMIN-AND-SECURITY.md#docs-24) |
| `context/data_models.md` | [`docs/07-DATABASE.md`](docs/07-DATABASE.md#context-data-models) |
| `docs/28-DATABASE-AND-DATA-RULES.md` | [`docs/07-DATABASE.md`](docs/07-DATABASE.md#docs-28) |
| `context/current_status.md` | [`docs/08-STATUS-ISSUES-AND-ROADMAP.md`](docs/08-STATUS-ISSUES-AND-ROADMAP.md#context-current-status) |
| `context/features.md` | [`docs/08-STATUS-ISSUES-AND-ROADMAP.md`](docs/08-STATUS-ISSUES-AND-ROADMAP.md#context-features) |
| `docs/16-KNOWN-ISSUES-AND-BUGS.md` | [`docs/08-STATUS-ISSUES-AND-ROADMAP.md`](docs/08-STATUS-ISSUES-AND-ROADMAP.md#docs-16) |
| `context/decisions_and_assumptions.md` | [`docs/08-STATUS-ISSUES-AND-ROADMAP.md`](docs/08-STATUS-ISSUES-AND-ROADMAP.md#context-decisions-and-assumptions) |
| `context/roadmap.md` | [`docs/08-STATUS-ISSUES-AND-ROADMAP.md`](docs/08-STATUS-ISSUES-AND-ROADMAP.md#context-roadmap) |
| `docs/notes/todo.md` | [`docs/08-STATUS-ISSUES-AND-ROADMAP.md`](docs/08-STATUS-ISSUES-AND-ROADMAP.md#docs-notes-todo) |
| `docs/notes/ideas.md` | [`docs/08-STATUS-ISSUES-AND-ROADMAP.md`](docs/08-STATUS-ISSUES-AND-ROADMAP.md#docs-notes-ideas) |
| `docs/20-CHANGE-LOG-AND-DESIGN-DECISIONS.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#docs-20) |
| `docs/29-LESSONS-LEARNED-AND-GOTCHAS.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#docs-29) |
| `context/testing_and_deployment.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#context-testing-and-deployment) |
| `docs/30-TESTING-GO-LIVE-AND-HANDOVER.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#docs-30) |
| `docs/15-DEPLOYMENT-AND-INFRASTRUCTURE.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#docs-15) |
| `restructure/REPORT.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#restructure-report) |
| `restructure/CHANGELOG.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#restructure-changelog) |
| `restructure/MOVE_MAP.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#restructure-move-map) |
| `restructure/CURRENT_TREE.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#restructure-current-tree) |
| `restructure/TARGET_TREE.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#restructure-target-tree) |
| `heal/REPORT.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#heal-report) |
| `heal/FINDINGS.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#heal-findings) |
| `heal/CHANGELOG.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#heal-changelog) |
| `heal/RUN_LOG.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#heal-run-log) |
| `heal/STATE.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#heal-state) |
| `button_audit/REPORT.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#button-audit-report) |
| `chaos/REPORT.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#chaos-report) |
| `chaos/FINDINGS.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#chaos-findings) |
| `chaos/CHANGELOG.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#chaos-changelog) |
| `chaos/TARGET_MAP.md` | [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#chaos-target-map) |
