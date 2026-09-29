# Architecture
> Purpose: how the pieces fit and talk | Mode: A | Confidence: High | Related: [backend_spec.md](backend_spec.md), [frontend_spec.md](frontend_spec.md), [../docs/02-ARCHITECTURE.md](../docs/02-ARCHITECTURE.md)

## Components
```mermaid
graph LR
  B[Browser] -->|pages| SITE[frontend/ React SPA<br/>Vite dev :3000 or dist/public]
  SITE -->|plain a href, full page load| ENG[backend/booking-engine PHP<br/>served at /book/]
  B -->|fetch JSON| API[/book/api/*.php/]
  ENG --- API
  API --> DB[(SQLite locally / MySQL live)]
  ADMIN[/book/admin/*.php] --> DB
  API --> RZP[Razorpay]
  API -. off .-> SF[Stayflexi]
  SITE -. optional .-> EXP[backend/server Express<br/>POST /api/contact]
```
* The website and the engine share **no code, session or data**; the site only links to `/book/…` [CODE `frontend/src/lib/booking.ts`].
* In development Vite proxies `/book/` → `http://127.0.0.1:8080` (the PHP built-in server) [CODE `vite.config.ts`]. In production the engine is uploaded to a PHP host at `/book/` [DOC `docs/15`].
* The engine uses relative paths, so it runs under any folder [CODE].

## Data flow: a booking
`availability.php` (read) → `quote.php` (price, no write) → `book.php` (creates a **pending** booking + rooms + extras) → payment (`payment-create.php` → Razorpay/UPI, or `payment-test.php`) → `settle_payment()` (paid → **confirmed**, Stayflexi push, email) [CODE `lib/booking.php`, `lib/payment.php`].
A pending booking holds its rooms for 45 minutes, or while money or a UPI check is pending [CODE `rooms_booked`].

## Key patterns
* **One pricing function** `price_rooms()` for list, checkout, booking and desk changes [CODE `lib/inventory.php`].
* **Difference pricing** for changes (`modification_delta`) [CODE].
* **Money follows payment choice** (`booking_money`) [CODE].
* **Row locks** when creating/changing bookings (MySQL `FOR UPDATE`; SQLite serialises writes) [CODE `for_update()`].
* **Audit log** for every money/inventory action; also stores rate-limit counters [CODE `audit()`, `rate_limit()`].
* **PDFs generated on demand** (never stored) [CODE `lib/documents.php`].

## Scaling and failure
* Single small hotel: 20 cottages; SQLite is fine locally, MySQL on the host [INFERRED].
* Stayflexi down with `fail_closed` → bookings refused rather than oversold [CODE `channel_verify_still_available`].
* Razorpay webhook as backup if the browser closes during payment [CODE `api/webhook-razorpay.php`].
* Mail failure is logged (`mail_failed`), booking still saved [CODE `lib/mail.php`].
