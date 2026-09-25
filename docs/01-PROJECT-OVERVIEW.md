# Project Overview: Kutch Safari Resort Website

_Last updated: 25 Sep 2026 — booking engine replaced with the PHP engine in `booking-engine/`._

## 1. Business Context
Kutch Safari Resort is a family-run resort near Bhuj, Gujarat, operating for over 35 years. Founder: Mike Vaghela.

### 1.1 Properties
* **Kutch Safari Resort** — Near Rudramata Dam, Bhuj–Khavda Road, Bhuj, Kutch, Gujarat 370001, about 15 km from Bhuj. Open all year.
  * 20 lake-view cottages: 12 Kutchi AC Cottages and 8 Deluxe AC Cottages.
  * Restaurant: The Banni (multi-cuisine, veg and non-veg). Pool and garden lawn (events up to 300).
* **White Rann Camp** (sister property) — Dhordo, three minutes from the White Rann entry and Rann Utsav. **Seasonal: 1 Dec 2026 – 31 Jan 2027.**
  * 20 Swiss tents: 6 Deluxe Air-Cool and 14 Non-AC, each with an attached bathroom.
* **Colors of Kutch** tour packages (2N/3D and 3N/4D), priced per person by group size.

### 1.2 Audience
Domestic and international travellers heading to the White Rann, Rann Utsav guests, people interested in the culture and craft villages, and groups and families.

### 1.3 Contact
* Phone / WhatsApp: +91 99252 38599
* Email: kutchsafaribhuj@yahoo.com
* Instagram: @kutchsafariresort

---

## 2. What the repository contains

| Part | Stack | Purpose |
|---|---|---|
| Marketing site (`client/`) | React 19, Vite 7, Tailwind 4, TypeScript, wouter | All public pages |
| Server (`server/index.ts`) | Express 4 | Serves the built site and `POST /api/contact` |
| Serverless contact (`api/contact.ts`) | Vercel function | Logs enquiries only (does not store them) |
| **Booking engine (`booking-engine/`)** | **PHP 8 + MySQL (SQLite locally)** | **Search → room → extras → pay; admin panel; Razorpay + UPI QR; Stayflexi bridge** |

The booking engine is a **separate application**. The React site holds no booking code. Every "Book Now" button links to the engine at `/book/` (see `client/src/lib/booking.ts`). Details are in `02-ARCHITECTURE.md` and `booking-engine/README.md`.

### 2.1 Front-end libraries
* Icons: lucide-react
* UI primitives: shadcn/ui (only `button`, `card`, `sonner`, `tooltip` are present)
* Toasts: sonner
* Fonts: Cormorant Garamond (display), Jost (body) from Google Fonts

### 2.2 Theme: "Sundown Terracotta"
* Page background: beige `#f8f5e2` (owner's directive, taken from the logo)
* Terracotta accent: `oklch(0.55 0.16 35)` (`--terracotta`)
* Ink: `oklch(0.28 0.03 50)` (`--ink`)

---

## 3. Environments
* **Production domain:** `https://kutchsafaribhuj.in` (Vercel)
* **Preview:** `https://kutch-safari-resort.vercel.app`
* **Booking engine:** must run on a PHP host (e.g. cPanel `public_html/book`). Vercel cannot run it. See `15-DEPLOYMENT-AND-INFRASTRUCTURE.md`.
* **Old WRC portal:** `https://whiteranncamp.travstack.com` (still linked from the Navbar placeholder button)

---

## 4. Quick start

```bash
pnpm install
pnpm setup:book   # once — creates the local SQLite booking database (needs PHP 8)
pnpm dev:book     # booking engine on :8080
pnpm dev          # site on :3000; /book/ is proxied to :8080
```

Without PHP installed, `pnpm dev` still runs the site. Book Now buttons then have nothing to open.

---

## 5. Current state (summary)
Working: all 12 routes, destination guides, the new booking engine (once hosted), and the typecheck and production build.

Outstanding, detailed in `16-KNOWN-ISSUES-AND-BUGS.md`:
1. The Home contact form is still a mock (`setTimeout`).
2. `/white-rann-camp/tariff` is linked but has no route. The `/#explore`, `/#rann-utsav` and `/#contact` anchors point to sections that don't exist.
3. Placeholder content: Our Journey (lorem ipsum), Dining and Our Journey placeholder boxes, FAQ, `[WRC LOGO]`.
4. About 269 MB of unoptimised assets, including a 4.5 MB favicon.
5. The booking engine is not yet deployed. Its rates, GST and cancellation terms need the owner's sign-off.
