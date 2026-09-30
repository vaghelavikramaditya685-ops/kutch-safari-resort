# The Booking Engine (backend/booking-engine/)

_Combined on 30 Sep 2026 from 11 earlier files. Start at [`README.md`](../README.md) for the overview and hard rules._

The PHP engine: modules, API, pricing, changes and money, availability, the guest flow, payments and Stayflexi.

---

## Contents
* [Backend Spec](#context-backend-spec) _(was `context/backend_spec.md`)_
* [API Spec](#context-api-spec) _(was `context/api_spec.md`)_
* [User Flows](#context-user-flows) _(was `context/user_flows.md`)_
* [Booking engine — Kutch Safari Resort & White Rann Camp](#engine-readme) _(was `backend/booking-engine/README.md`)_
* [Kutch Safari Resort & White Rann Camp — project handover](#engine-project-context) _(was `backend/booking-engine/PROJECT-CONTEXT.md`)_
* [Pricing Logic Deep Dive (booking engine)](#docs-21) _(was `docs/21-PRICING-LOGIC-DEEP-DIVE.md`)_
* [Booking Changes and Money Logic](#docs-22) _(was `docs/22-BOOKING-CHANGES-AND-MONEY.md`)_
* [Availability and Inventory Logic](#docs-23) _(was `docs/23-AVAILABILITY-AND-INVENTORY-LOGIC.md`)_
* [Guest Booking Flow — Internals](#docs-25) _(was `docs/25-GUEST-BOOKING-FLOW-INTERNALS.md`)_
* [Payments: Razorpay, UPI QR, Test Payments, Refunds](#docs-26) _(was `docs/26-PAYMENTS-RAZORPAY-UPI-TEST.md`)_
* [Stayflexi (Channel Manager) Sync](#docs-27) _(was `docs/27-STAYFLEXI-CHANNEL-SYNC.md`)_

---

<a id="context-backend-spec"></a>

## Backend Spec
_(was `context/backend_spec.md`)_
### Modules (`backend/booking-engine/lib/`) [CODE]
| File | Responsibility | Key functions |
|---|---|---|
| db.php | PDO, helpers, settings, audit, **input checks** | `db`, `q`, `q1`, `insert`, `update`, `audit`, `LIMITS`, `too_long`, `valid_phone`, `valid_arrival_time`, `valid_date` |
| inventory.php | availability + pricing | `price_rooms` (single pricing function), `price_rate_plan`, `tax_split`, `rooms_booked`, `rooms_free_for_stay`, `search_availability`, `pricing_locks` |
| booking.php | quote, create, change, cancel, money | `quote_cart`, `create_booking`, `quote_modification`, `modification_delta`, `modify_booking`, `booking_money`, `cancel_booking`, `validate_dates(_staff)` |
| payment.php | Razorpay, UPI, test, desk payments/refunds | `settle_payment`, `upi_mark_received`, `record_offline_payment` (confirms a pending booking once enough is paid), `record_offline_refund`, `refresh_amount_paid` |
| channel.php | Stayflexi | `channel_push_booking`, `channel_cancel_booking`, `channel_verify_still_available` |
| mail.php / pdf.php / documents.php | emails, PDF writer, receipt & terms | `send_booking_confirmation`, `receipt_pdf`, `terms_pdf` |

### Business rules [CODE `config.php` unless noted]
* Price per room-night = double price (special price if set) − single discount (Single) + ₹1,500 extra bed (Triple); GST included for the resort, slab 5% ≤ ₹7,500 else 18% on the pre-tax value [CODE `price_rooms`, `tax_split`].
* Change of a booking = old total + added − taken off ± changed; kept items keep booked amounts [CODE `modification_delta`].
* Money: `due_now` (to reach 100% or 50%), `later` (50% plan, before arrival), `refund` [CODE `booking_money`].
* Holding rooms: confirmed always; pending while ≤ 45 min old, or money/UPI check pending [CODE `rooms_booked`].
* Limits: 21 nights, 5 rooms online, 2 hours' notice, 2 years ahead, 1–50 of an extra, gala ≥ 10.
* Cancellation ladder: 30+ days free, 21–29 days 75%, < 21 days 100%.

### Validation (29 Sep 2026)
Every guest/enquiry/admin input checked in the backend (never trusting the page): lengths per `LIMITS`, phone 7–15 digits, arrival "h:mm AM/PM", real dates, extras quantities, listed methods/statuses. Errors are plain sentences; guest-detail errors carry `field:"guest"` [CODE]. See [../chaos/REPORT.md](09-HISTORY-TESTING-AND-GO-LIVE.md#chaos-report).

### Background jobs [CODE `bin/`]
* `sync-inventory.php` every 10 min (Stayflexi → `inventory`), `retry-failed-sync.php` hourly — only when Stayflexi is on.
* `test-changes.php`, `check-system.php` — tests on a temporary copy of the database.

### Errors and logging
Exceptions in APIs become JSON errors (details only when `debug` is on) [CODE `api/_init.php`]; money/inventory actions in `audit_log`; mail failures logged [CODE].

---

<a id="context-api-spec"></a>

## API Spec
_(was `context/api_spec.md`)_
Base: `/book/api/` (dev: proxied to `127.0.0.1:8080/api/`). JSON in/out; CORS allow-list in `config.php allowed_origins`; per-IP rate limits (HTTP 429 "Too many attempts") [CODE `api/_init.php`]. A body that is not valid JSON → 400 "We could not read that request…" [CODE].

| Method & path | Auth | Request | Response | Errors | Rate limit |
|---|---|---|---|---|---|
| GET `availability.php` | none | `property, check_in, check_out, rooms, occupancy` (e.g. `2,3`) | `{ok, results:[{id,name,rate_plans:[{id,total,units…}],rooms_left,fits…}], addons:[…], next_available}` | bad/past/too-far dates, > 21 nights, unknown property | 120/min |
| POST `quote.php` | none | cart: `{property, check_in, check_out, occupancy:[], payment_mode, rooms:[{room_type_id,rate_plan_id,rooms}], addons:[{addon_id,quantity,rooms?}], coupon?}` | `{ok,total,tax_amount,discount,amount_due_now,balance_later,rooms,addons,cancellation,payment_modes}` | room not offered, extra not offered / quantity 1–50, gala < 10, > 5 rooms, not enough free | 90/min |
| POST `book.php` | none | cart + `guest:{name,phone,email,city,arrival_time,special_requests}` | `{ok,booking_id,ref,manage_token,payment_mode,amount_due_now,total,status:"pending",next:"payment",methods}` | as quote, plus guest checks (`field:"guest"`) | 12 / 5 min |
| POST `payment-create.php` | **booking's manage_token** | `{booking_id, manage_token, method:"razorpay"\|"upi_qr"}` | Razorpay order or UPI `{ok,vpa,amount,upi_uri…}` | not found / already paid / cancelled | 30 / 5 min |
| POST `payment-verify.php` | Razorpay signature | `{razorpay_order_id, razorpay_payment_id, razorpay_signature}` | `{ok, booking…}` | signature mismatch | 30 / 5 min |
| POST `payment-test.php` | manage_token; only if `test_payments.enabled` | `{booking_id, manage_token}` | `{ok}` → booking confirmed | off / wrong token | 30 / 5 min |
| POST `webhook-razorpay.php` | HMAC `X-Razorpay-Signature` | Razorpay event (`payment.captured`, `payment.failed`) | 200 | 400 bad signature | — |
| GET `booking-lookup.php` | ref + contact, or ref + token | `ref, contact` or `ref, token` | booking with rooms, extras, total, paid, `due_now`, `due_later`, `refund_due`, `modified_at`, cancellation | 404 not found | 15 / 5 min |
| POST `booking-cancel.php` | — | — | always **403** + call/WhatsApp message | — | — |
| POST `enquiry.php` | none | `{name*, phone*, email, property, check_in, check_out, guests, interest, message, website(honeypot)}` | `{ok,id,message}` | phone/email/dates/guests/length checks | 8 / 10 min |

All from [CODE `backend/booking-engine/api/*.php`].

### Non-JSON endpoints
* `receipt.php?ref=&token=` → PDF (guest token or signed-in staff; 404 otherwise) [CODE].
* `terms.php?property=` → PDF [CODE].
* `document.php?doc=receipt|terms&…` → page that shows the PDF with PDF.js [CODE].
* Admin pages are HTML forms with CSRF tokens (see [auth_and_security.md](06-ADMIN-AND-SECURITY.md#context-auth-and-security)) [CODE `admin/`].

### Express (optional)
`POST /api/contact` → appends to `/data/enquiries.json` [CODE `backend/server/index.ts`]; the website does not call it yet [CODE `Home.tsx` mock].

---

<a id="context-user-flows"></a>

## User Flows
_(was `context/user_flows.md`)_
### 1. Guest books a stay
```mermaid
flowchart LR
  A[Website: Book Now] --> B[/book/ rooms step/]
  B -->|GET availability.php| B
  B --> C[Extras] -->|POST quote.php| D[Details + pay 50%/full]
  D -->|POST book.php → pending| E[Payment]
  E -->|payment-create.php → Razorpay/UPI| F{paid?}
  E -->|payment-test.php (test mode)| G
  F -->|verify / webhook → settle_payment| G[Confirmed: receipt, status, call]
```
1. Website **Book Now** → `/book/?property=kutch-safari-resort` (full page load) [CODE `lib/booking.ts`].
2. Dates pre-filled; choose rooms and Single/Double/Triple per room → `availability.php` [CODE].
3. **Select** a cottage (several rooms: tick room numbers) → **Continue** → extras → **Continue** → `quote.php` [CODE].
4. Details (optional in sample mode), arrival wheel, 50%/full → **Continue to payment** → `book.php` creates a **pending** booking [CODE].
5. Pay → `settle_payment()` → **confirmed**, Stayflexi push (when on), email [CODE].
**What changes:** `bookings`, `booking_rooms`, `booking_addons`, `payments`, `audit_log`; rooms held.

### 1a. Visitor explores
Menu **Experiences** → brochure content → **Arrange an Experience** (WhatsApp). Menu **Around the Resort** (or Home **Explore Kutch**) → 6 places → `/destination/:slug` → **Inquire on WhatsApp**; **Back to Beyond Bhuj** returns to `/around-the-resort` [CODE].

### 2. Guest checks a booking
Website **Already booked? Check status** → `manage.php` → code + mobile/email → `booking-lookup.php` → stay, money, **Receipt (PDF)**, **Terms**, **Call**; cancel = call/WhatsApp [CODE]. Nothing changes.

### 3. Desk: UPI payment arrived
Admin → Bookings → "waiting to be checked" → bank ref → **Money received** → payment paid, booking confirmed, email, Stayflexi [CODE `upi_mark_received`].

### 4. Desk: change a booking
Admin → booking → **Change this booking** → edit dates/rooms/guests/extras → **Check price** (no write; Added / Taken off / Changed + money) → **Save changes** (on-page confirm) → booking page: collect or give back [CODE `admin/edit.php`, `modify_booking`].

### 5. Desk: cancel
Bookings → **Cancel a booking** (code) → booking `#cancel` → reason → confirm → cancelled, charge by ladder, rooms back on sale, Razorpay refund when connected [CODE].

### 6. Desk: special price
Special prices → nights, cottages, price → **Check** → (tick if unusual) → **Save**; **Remove** later [CODE `admin/rates.php`].

### 7. Desk: sign in / out
`/admin` or `/book/admin` → sign-in page (hashes username + password with SHA-256) → Bookings. New tab, browser restart or 10 min idle → sign in again. **Sign out** ends the session [CODE].

---

<a id="engine-readme"></a>

## Booking engine — Kutch Safari Resort & White Rann Camp
_(was `backend/booking-engine/README.md`)_
Direct bookings, taken on your own website, with Razorpay and a UPI QR code,
sitting alongside the Enquire Now route you already have.

Plain **PHP 8 + MySQL**. No build step, no npm, no framework — because the end
destination is a cPanel account, and PHP runs there with nothing to configure.

> **Current state, 30 Sep 2026: read this first.** This README began as the
> original handover and some parts described plans that later changed. What is
> true today:
> - Folder is `backend/booking-engine/` inside the website repo; locally it runs on
>   **SQLite** (`data/booking.sqlite`, the default driver in `config.php`); the live
>   server's `config.local.php` must set `mysql`.
> - **One plan per room** for the resort (room with breakfast, prices GST-included).
>   White Rann Camp is **switched off** (`properties.active = 0`).
> - Guests pay **50% now or in full**. "Pay at the property" is off. **Guests cannot
>   cancel online**: they call or WhatsApp and staff cancel in admin.
> - Admin screens: Bookings, booking page, **Change this booking** (priced as a
>   difference), **Availability** (tape chart by cottage, click a guest for a side
>   panel), **Special prices** (was "Rates"), Enquiries, Export. The old "rooms on
>   sale" grid was removed at the owner's request.
> - Admin sign-in is **SHA-256 hashed in the browser**; stored hash = bcrypt(SHA-256).
>   Create or reset a login with `php bin/setup.php --admin USERNAME "Name" PASSWORD`.
>   **Never run `setup.php` without `--admin` on real data** (it reloads `seed.sql`).
> - Every input is length- and format-checked (`LIMITS` and helpers in `lib/db.php`).
> - Extra files since the handover: `lib/documents.php` + `lib/pdf.php` (receipt and
>   terms PDFs), `document.php`, `receipt.php`, `terms.php`, `api/payment-test.php`
>   (test mode, turn off before launch), `bin/check-system.php`, `bin/check-razorpay.php`,
>   `bin/check-stayflexi.php`, `bin/map-stayflexi.php`, `bin/test-changes.php`.
> - Full, current detail: `../../docs/21`–`30` (pricing, changes and money,
>   availability, admin, guest flow, payments, Stayflexi, database, lessons, go-live)
>   and [`../../context/`](../README.md#context-overview).

---

### What it does

- **Search → choose a room → add extras → pay**, the four-step flow used by the
  big Indian engines.
- **Several meal plans per room** (CP / MAP / AP for the resort, MAPAI for the
  camp), each with its own price, exactly as your tariffs are written.
- **A rate calendar** — peak dates are already loaded: Christmas/New Year
  19 Dec 2026 – 4 Jan 2027, Uttarayan 13–15 Jan, full moon 21–23 Jan.
- **GST in slabs.** 5% up to ₹7,500 a night and 18% above it, worked out per
  night, so a ₹7,499 tent and an ₹8,450 tent are taxed differently and correctly.
- **The guest chooses how to pay** — in full or 50% now ("at the property" exists but is switched off).
- **Sold-out dates suggest the next free ones** instead of dead-ending.
- **Add-ons** — transfers, gala dinner, camel cart, extra beds.
- **A guest can look up** their own booking with the reference and their phone
  number or email (receipt, terms, what is due). Cancelling is done by staff;
  refunds follow your published ladder.
- **An admin panel** for bookings, changing a booking, availability, special prices, enquiries and CSV export.
- **Stayflexi bridge** so the same room is never sold twice.

---

### Files

```
booking-engine/
├── config.php            ← every setting and key lives here
├── schema.sql            ← the database
├── seed.sql              ← your rooms, rates, add-ons and packages
├── index.php             ← the booking engine a guest sees
├── manage.php            ← "check status": look up (no online cancel)
├── lib/
│   ├── db.php            small PDO helpers
│   ├── inventory.php     availability + pricing  ← the heart of it
│   ├── booking.php       quote, create, change (difference pricing), money split, cancel
│   ├── payment.php       Razorpay + UPI QR
│   ├── channel.php       Stayflexi
│   └── mail.php          confirmation emails
├── api/                  the JSON endpoints the page calls
├── admin/                staff panel
├── bin/
│   ├── setup.php         creates the tables and loads your data
│   ├── sync-inventory.php        cron: pull availability from Stayflexi
│   └── retry-failed-sync.php     cron: resend bookings that did not land
└── assets/               engine CSS, JS and room photographs
```

---

### Putting it on cPanel

**1. Upload.** Put this whole folder in `public_html/book` on
kutchsafariresort.com. Both websites link to it, so it only needs to exist once.

**2. Make the database.** cPanel → MySQL Databases → create a database and a
user, give the user All Privileges. Note the names — cPanel prefixes them with
your account, e.g. `kutchsaf_booking`.

**3. Load the tables.** cPanel → phpMyAdmin → pick the database → Import →
`schema.sql`, then `seed.sql`. Or over SSH:

```bash
mysql -u USER -p DBNAME < schema.sql && mysql -u USER -p DBNAME < seed.sql
```

**4. Put your settings in `config.local.php`** next to `config.php`. That file
overrides anything in `config.php` and is never committed, so your keys stay out
of the repository:

```php
<?php
return [
  'db' => ['driver' => 'mysql', 'host' => 'localhost',
           'name' => 'kutchsaf_booking', 'user' => 'kutchsaf_book', 'pass' => '••••'],
  'base_url' => 'https://www.kutchsafariresort.com/book',
  'razorpay' => ['enabled' => true, 'key_id' => 'rzp_live_…',
                 'key_secret' => '…', 'webhook_secret' => '…'],
  'upi'      => ['enabled' => true, 'vpa' => 'yourname@okhdfcbank'],
  'debug'    => false,
];
```

**5. Create your login.**

```bash
php bin/setup.php --admin "username" "Your Name" "a-long-password"
```

**6. Two cron jobs** (cPanel → Cron Jobs), once Stayflexi is connected:

```
*/10 * * * *  /usr/local/bin/php /home/USER/public_html/book/bin/sync-inventory.php
0    * * * *  /usr/local/bin/php /home/USER/public_html/book/bin/retry-failed-sync.php
```

**7. Check `.htaccess` uploaded.** It forces HTTPS and blocks the web from
reading `config.php` and the `data` and `bin` folders. Hidden files are easy to
miss — turn on "Show Hidden Files" in the cPanel File Manager.

---

### Stayflexi — read this part

Stayflexi is what stops the same tent being sold here and on MakeMyTrip at once.
There are two safe ways to run, and one unsafe one.

**Safe — connected.** Ask Stayflexi support for **API access for a custom
booking engine**. They issue an API key and your hotel id. Put them in
`config.local.php`, set `stayflexi.enabled = true`, fill in `sf_hotel_id` on each
property and `sf_room_type_id` on each room type, and the cron jobs keep the two
in step. Availability is read from Stayflexi and every booking is pushed back to
it within seconds.

**Safe — separated.** Leave Stayflexi off here, and in Stayflexi hold some rooms
back from the OTAs — say four cottages and four tents kept for direct bookings.
Set the same numbers in Availability in the admin panel. The two systems never
touch the same room, so neither can oversell.

**Not safe.** Selling all twenty rooms here *and* all twenty on the OTAs, and
reconciling by hand. On a full-moon night in January you will double-sell.

The engine is built for the first option. Until the key arrives, use the second.
`config.php` has `fail_closed` set to true, which means that if Stayflexi is
connected but unreachable, a booking is refused rather than risked. Losing one
booking costs less than turning a family away at the gate.

If Stayflexi will not give you API access, tell me — the fallback is to embed
their own booking engine behind your design, which is what Rann Riders does with
DJUBO.

---

### Razorpay

1. Sign up at razorpay.com and finish KYC — they will want PAN, GST and the
   hotel's bank account. Allow a few days.
2. Settings → API Keys → generate **test** keys first (`rzp_test_…`).
3. Settings → Webhooks → add
   `https://www.kutchsafariresort.com/book/api/webhook-razorpay.php`
   with the events `payment.captured`, `payment.failed`, `refund.processed`.
   Copy the webhook secret into your config.
4. Make one real test booking with a test card, then switch to the live keys.

The webhook matters: if a guest closes the tab the instant after paying, the
browser never reports back, and the webhook is what confirms the booking anyway.

**Fees.** Razorpay takes roughly 2% + GST on cards and UPI. On a ₹19,942 booking
that is about ₹470.

---

### The UPI QR code

A second way to pay that sends money straight to your bank account with **no
gateway fee**. Put the bank account's UPI id in `config.local.php`:

```php
'upi' => ['enabled' => true, 'vpa' => 'kutchsafari@okhdfcbank'],
```

The guest scans, pays, and sees a reference number. **The website is not told
that the money arrived** — nothing can tell it, because the payment never touches
Razorpay. So the booking waits, and the admin panel shows a "UPI payments waiting
to be checked" box at the top of the bookings list. Someone checks the bank,
presses "Money received", and only then is the booking confirmed and the room
closed on Stayflexi.

That is the real trade-off: no fee, but a person has to look. Razorpay is the
default for exactly that reason. The QR is best for large bookings where the fee
is worth a phone call, and for guests who ask for it.

---

### Changing things

**Prices.** Admin → Special prices. Choose the cottage, the dates, the new nightly
price. Leave a date alone and it uses the base price from `rate_plans`.

**How many rooms are on sale.** Every cottage type is sold up to its total. The
by-hand "rooms on sale" grid was removed from Availability (owner's request,
26 Sep 2026); `inventory` rows (e.g. from Stayflexi later) can still reduce it.

**Add-ons, rooms, meal plans.** In the database tables `addons`, `room_types`
and `rate_plans`. Edit them in phpMyAdmin, or re-run `seed.sql` after editing it.

**Tax, cancellation terms, deposit percentage, how many rooms may be booked
online.** All at the top of `config.php`, with a comment on each.

**Look and feel.** `assets/engine.css`. The tokens at the top match the two
websites, and each property's accent colour comes from its row in `properties`.

---

### Before you go live

1. **Confirm the GST slabs with your accountant.** `config.php` has 5% up to
   ₹7,500 and 18% above. These have changed more than once; the website has said
   12% in the past. Getting it wrong is a tax problem, not a website problem.
2. **Confirm the resort's room rates.** The figures in `seed.sql` came from the
   tariff page, which was labelled for an earlier season.
3. **Test one real booking end to end** with live Razorpay keys and a real card,
   then refund it from the Razorpay dashboard.
4. **Check the confirmation email arrives** and is not treated as spam. If it is,
   turn on SMTP in `config.php` and send through the mailbox cPanel gives you.
5. **Set `debug` to false.** With it on, error details are shown to guests.
5a. **Turn off sample mode and test payments** (`rules.guest_details_optional` and
   `test_payments.enabled` → false), set `properties.website_url` to the live
   site, remove the six demo bookings, set a long admin password, and use a
   **regenerated** live Razorpay secret. Full list: `../../docs/30-…`.
6. **Decide the Stayflexi arrangement** from the three options above, and write
   down which rooms are sold where.

---

### What is deliberately not built yet

- **Package booking online.** The Colors of Kutch packages are in the database
  with their pricing tiers and itineraries, but they are still enquiry-only at
  checkout. They need a vehicle, a driver and a guide, and those cannot be
  confirmed by a payment form. Say the word and I will add them as a bookable
  product with a deposit.
- **A guest login.** Bookings are looked up by reference plus phone number, which
  is what most Indian hotel engines do and is one less password for a guest.
- **Automatic GST invoices.** The data is all captured; the PDF is not written.
- **Multi-currency.** Prices are in rupees only. The plumbing is there if you
  start taking foreign bookings.

---

<a id="engine-project-context"></a>

## Kutch Safari Resort & White Rann Camp — project handover
_(was `backend/booking-engine/PROJECT-CONTEXT.md`)_
Everything in this folder: two websites and a custom booking engine, for a
family-run resort near Bhuj and its sister tented camp at Dhordo, in Kutch,
Gujarat, India.

Written in plain HTML, CSS, JavaScript and PHP with no build step, because the
destination is a cPanel shared-hosting account. Keep it that way — the owner
edits these files directly.

> **Current state, 30 Sep 2026: read this first.** This file is the original
> handover. Since then: the static HTML sites were replaced by the React site in
> `frontend/` (this engine now lives in `backend/booking-engine/` of the same repo);
> White Rann Camp is **switched off** in the engine; the resort sells **one plan**
> (room with breakfast, GST included); guests pay 50% or in full and **cannot cancel
> online**; admin sign-in is SHA-256 hashed; changes are priced as a difference; the
> local database is SQLite. For the up-to-date picture read [`../../context/overview.md`](../README.md#context-overview)
> and [`../../docs/01-PROJECT-OVERVIEW.md`](../README.md#docs-01) (and docs 21–30 for the engine).

---

### What is here

```
frontend/                    Main website (React 19 + Vite; replaced the static HTML sites)
backend/booking-engine/      PHP booking engine (MySQL live, SQLite locally; see its README.md)
backend/server/              Small Express server (static hosting, contact endpoint)
```

The two websites are separate sites, each landing on its own homepage, linked
only by a highlighted tab at the end of each navigation. They share a design
system but each has its own copy of `styles.css` so they can be restyled apart.

The booking engine is one PHP application serving both properties, themed per
property from the database. It lives at `/book` on the main domain and both
sites link to it.

---

### The two businesses

**Kutch Safari Resort**, Bhuj — 20 lake-view cottages by the Rudramata Dam,
15 km from Bhuj on the road to the White Rann. Open all year. Two cottage
types (Kutchi AC ×12, Deluxe AC ×8), sold on three meal plans each.

**White Rann Camp**, Dhordo — 20 Swiss tents three minutes from the White Rann
and the Rann Utsav tent city. **Seasonal: 1 December to 31 January only**, which
the engine enforces. Six Deluxe Air-Cool tents, fourteen Non-AC, sold on one
MAPAI plan (dinner, breakfast, hi-tea).

Both also sell "Colors of Kutch" tour packages (2N/3D and 3N/4D) priced per
person on a sliding scale by group size. These are in the database with full
itineraries but are **enquiry-only at checkout** — they need a vehicle, driver
and guide, which a payment form cannot confirm.

---

### Things that will bite you if you do not know them

**GST is charged in slabs, per night.** 5% up to ₹7,500 a night, 18% above it.
This is not cosmetic: the camp's two tents straddle the line (₹7,499 and
₹8,450), and so do the resort's meal plans. `tax_percent_for()` in
`lib/inventory.php` handles it; the slabs are configurable in `config.php`.
**The owner must confirm the current rates with their accountant** — the old
website said 12%, and Indian hotel GST has changed more than once.

**Stayflexi is the channel manager.** It sells the same rooms on MakeMyTrip,
Booking.com and others. If this engine sells a room without telling Stayflexi,
the room can be sold twice. `lib/channel.php` has the adapter, but Stayflexi
does not publish its API — the endpoint paths in there are **informed guesses**
pending credentials. Read the Stayflexi section of `booking-engine/README.md`
before changing anything here. Until it is connected, inventory must be split
so the two systems never sell the same room.

**Peak dates are pre-loaded** for the camp from the owner's 2026–27 leaflet:
Christmas/New Year 19 Dec – 4 Jan, Uttarayan 13–15 Jan, full moon 21–23 Jan.
Only nights that differ from the base price are stored in `rates`.

**Payments: Razorpay plus a direct UPI QR.** Razorpay confirms instantly. The
UPI QR pays the hotel's bank account with no gateway fee, but nothing tells the
website the money arrived — staff confirm it in the admin panel. That asymmetry
is deliberate, not an oversight.

**No credentials are in this bundle.** `config.local.php` is excluded. Copy
`config.php`, create `config.local.php` next to it, and put the real database,
Razorpay and UPI values there.

---

### Current state

Working and tested: the website, availability search, rate plans, the GST
slabs, add-ons, quoting, booking creation, guest lookup (no online cancel; staff
cancel with the refund rules), changes priced as a difference, overbooking refusal, season limits, the admin panel, Razorpay
order creation and signature verification, the UPI QR, and the enquiry path.

Run `php bin/check-system.php` inside `booking-engine/` for a live report.

Not done: Stayflexi is unconnected and four room types are unmapped; the
Razorpay webhook secret is unset; there is no children selector on the booking
form (the pricing underneath supports children, the UI does not collect them);
packages are not bookable online; GST invoices are not generated.

Content still owed by the owner: confirmed resort room rates (the ones in
`seed.sql` came from a tariff page labelled for an earlier season), confirmed
GST rates, Diwali and Christmas supplement dates, real guest reviews in place of
the written placeholders on the homepage, higher-resolution photographs, and a
clean transparent PNG of the camp logo.

---

### House style

Plain files, no framework, no build step, no package manager. One stylesheet per
site with design tokens at the top. Comments explain *why*, not *what*. British
English in prose; the owner's guests are Indian and British-influenced spelling
reads correctly to them.

The owner is not a developer. Anything they are expected to edit — prices,
dates, copy, colours — should be findable without reading code.

---

<a id="docs-21"></a>

## Pricing Logic Deep Dive (booking engine)
_(was `docs/21-PRICING-LOGIC-DEEP-DIVE.md`)_
_Written 26 Sep 2026. This is how a price is actually worked out in code, and why it is done that way. For the tariff itself see [`08-PACKAGES-AND-PRICING.md`](04-BUSINESS-CONTENT-AND-PRICES.md#docs-08); for changing an existing booking see [`22-BOOKING-CHANGES-AND-MONEY.md`](#docs-22)._

### 1. One pricing function for everything
Every room price the guest ever sees comes from **`price_rooms()`** in `backend/booking-engine/lib/inventory.php`. The room list (`search_availability()`), the checkout summary (`quote_cart()` in `lib/booking.php`), the booking that is saved (`create_booking()`), and a desk change (`quote_modification()`) all go through it.

> Why: earlier the room list and checkout priced rooms separately and disagreed (one divided adults by the number of lines, so 4 adults in 2 rooms were charged as 4 per room). One function means one answer.

```
price_rooms(property, room_type, plan, check_in, check_out, occupancy[])
  └─ price_rate_plan(plan, check_in, check_out)   → the double price for each night
       ├─ special price for that night?  (rates table)  → use it
       ├─ locked price for that night?   (pricing_locks, only while changing a booking) → use it
       └─ otherwise                       → rate_plans.base_price
  └─ for each room, for each night:
       night = double
             − (base_price − single_price)           if the room is Single
             + extra_adult_price × (guests − 2)      if the room is Triple (extra bed)
       [before tax, GST] = tax_split(night, inclusive?)
```

### 2. Occupancy: Single / Double / Triple per room
* The guest picks the occupancy **for each room** (`occupancy` = e.g. `[2, 1, 3]`).
* `rate_plans.base_price` is the **double** price. `rate_plans.single_price` is the single price. The single keeps the same **discount** (base − single) on every night, including special-price nights. Example: Kutchi base ₹7,450, single ₹6,500, so the discount is ₹950. On a special night of ₹8,000 the single is ₹7,050.
* Triple = double + `room_types.extra_adult_price` (₹1,500). That is the extra bed. The old "Extra bed" add-on was removed so it cannot be charged twice.
* `normalise_occupancy()` makes sure there is one number per room and clamps each to 1…`max_adults`.

### 3. GST: inclusive for the resort, on top for the camp
`config.php → prices_include_tax = ['kutch-safari-resort']`.

* **Inclusive (resort):** the tariff is what the guest pays. `tax_split()` takes GST **out**. The slab is decided on the value **before** tax: ₹7,450 incl. 5% = ₹7,095.24 + ₹354.76, which is under ₹7,500, so 5% is right.
* **Exclusive (White Rann Camp, currently switched off):** the tariff is the taxable value and GST is added on top.
* Slabs (`config.php → tax_slabs`): up to ₹7,500 → 5%, above → 18%. They apply **per room per night**, on the room's price including any extra bed.
* **Edge case (needs the accountant):** inclusive prices between ₹7,875 and ₹8,850 fit neither slab cleanly. The Deluxe triple at ₹8,000 is split at 18% (₹6,779.66 + ₹1,220.34). The guest pays ₹8,000 either way; only the invoice split changes.

### 4. Special prices (per night, never the normal price)
Admin → **Special prices** (`admin/rates.php`) writes rows into `rates (rate_plan_id, stay_date, price, min_stay, closed)`. Each row replaces the **double** price for one night of one plan.

* A stay is priced **night by night**. A stay of 4 normal nights + 5 special nights is 4 × normal + 5 × special. This was tested: ₹39,800 for that case.
* Guard rails in `rates.php`: never under ₹500 (`PRICE_FLOOR`); refused below 25% or above 400% of normal (`HARD_LOW`/`HARD_HIGH`); a confirm tick is needed below 60% or above 150% (`CONFIRM_LOW`/`CONFIRM_HIGH`); no past dates; a "check" step before saving.
* `closed = 1` on a night stops that plan being sold that night. `min_stay` refuses shorter stays that touch that night.
* Removing a special price deletes those rows, so the nights go back to `base_price`.

### 5. Extras (add-ons)
`addon_amount()` in `lib/inventory.php`:

| `price_type` | Amount |
|---|---|
| `per_booking` | price × quantity |
| `per_person` | price × quantity (quantity = people). `addons.min_quantity` is enforced (gala dinner: 10) |
| `per_night` | price × quantity × nights |
| `per_room_night` | price × quantity × nights |

* The resort's extras are GST-inclusive too. The tax is taken out at `addon_tax_rate` (5%).
* Extras whose names follow **"Group — Choice"** (e.g. "Airport transfer, one way — Innova") are shown as one card with a counter per choice. This is purely a naming convention, read by `assets/engine.js`. Rename carefully.
* `addon_is_per_room()` extras are assigned to numbered rooms, and the room numbers are written into the line name (" — Room 2"). None of the current extras use this.

### 6. Discount codes
`coupons` table. `quote_cart()` checks `active`, `min_nights`, `valid_from`/`valid_to` (on check-in) and `max_uses`. `percent` codes apply to the **rooms** subtotal only (not extras); `fixed` codes take off a flat amount, never more than the rooms. When the desk changes a booking the code is kept even if it has since expired (`keep_coupon`). See doc 22.

### 7. Rounding
* `money()` rounds to 2 decimals.
* Each room-night is split into [before tax, GST] and then summed. Totals therefore always add up on the receipt: rooms + extras − discount + GST = total.
* In a change, a partial stay is rounded as **amount first, GST inside it second**, so ₹7,450 a night stays exactly ₹7,450 (it once came out as ₹7,450.01).

### 8. What is paid now
`quote_cart()` returns `amount_due_now`: the full total, or `payment_modes.advance.percent` (50%) of it. "Pay at the property" (`hotel`) is switched off, so a booking is never left with nothing paid. After booking, `booking_money()` (doc 22) works out due now / due before arrival / refund from the payments.

### 9. Where to change what
| To change | Edit |
|---|---|
| Normal room price | `rate_plans.base_price` / `single_price` (database; `seed.sql` for a fresh install) |
| Extra bed | `room_types.extra_adult_price` |
| Price on certain dates | Admin → Special prices |
| GST slabs, inclusive or not | `config.php` → `tax_slabs`, `prices_include_tax` |
| Deposit percentage | `config.php` → `payment_modes.advance.percent` |
| Extras | `addons` table (name, price, `price_type`, `min_quantity`, `active`) |

---

<a id="docs-22"></a>

## Booking Changes and Money Logic
_(was `docs/22-BOOKING-CHANGES-AND-MONEY.md`)_
_Written 26 Sep 2026. How the desk changes a booking (Admin → booking → **Change this booking**), how the new price is worked out, and how "collect / give back" follows the guest's payment choice. Code: `lib/booking.php` → `quote_modification()`, `modification_delta()`, `booking_money()`, `modify_booking()`; screen: `admin/edit.php`._

### 1. The rule the owner asked for
> "What is added, total it and add it to their price. What is removed, total it and take it off the full amount."

So a change is **never re-priced from scratch**:

```
new total = old total + added − taken off ± changed
```

Everything the guest keeps stays at **exactly the amount it was booked at**, even if the tariff or a special price has moved since. Only new things are priced, at today's prices.

#### Why it had to be rebuilt
The first version re-ran the whole cart through `quote_cart()` (with the old nightly prices "locked"). The total still drifted, because GST, the single/extra-bed adjustment and the discount were worked out again from today's settings. On a real test booking, adding one ₹7,450 Kutchi room moved the total from ₹22,000 to ₹30,050 instead of ₹29,450. The preview also mixed the change with money already owed. On a 50%-paid booking it said "collect ₹33,300" when the change was only +₹2,100.

### 2. How the difference is worked out (`modification_delta()`)
1. `quote_cart()` still runs first on the new cart. That checks availability (with this booking's own rooms left out, `availability_ignore_booking()`), dates, occupancy and extras, and prices anything **new**. Nights the guest already had are priced at their booked nightly price (`pricing_locks()`), and the code stays on (`keep_coupon`).
2. The old booking and the new cart are each broken into **one entry per room**: cottage type, rate plan, occupancy (read from the line name "Room 2 Double", see doc 28), its `[before tax, GST]` amount and its nightly prices.
3. Rooms are matched old ↔ new:
   * **Same cottage, plan and occupancy:** nights kept keep their stored share. Extra nights are **added** at the new price, and nights dropped are **taken off** at their stored share.
   * **Same cottage and plan, different occupancy** (e.g. Double → Triple): nights kept are **changed** by (new − old). Extra and dropped nights are as above.
   * **Left over new:** **added** in full. **Left over old:** **taken off** in full.
4. A room's share of a night is proportional to that night's price (special prices make nights differ). The whole stay kept means the exact stored amount, with no rounding.
5. **Extras**, by extra: the units kept keep their stored amount, more units are **added** at the new price, and fewer units are **taken off** at their stored per-unit amount. Extras priced by the night (`per_night`, `per_room_night`) are re-priced when the number of nights changes and shown as **changed**.
6. **Discount code:** a percent code moves by its percentage on the rooms added or taken off (kept rooms keep their discount). A fixed code stays the same.
7. The new booking lines are rewritten to those amounts, and the totals are summed from them. Any paise left from how an *older* booking was rounded go into the GST figure so the parts still add up.

The result, `$q['changes']`:
```php
['added'   => [['what' => 'Deluxe AC Cottage (Single), 16 Oct – 20 Oct (4 nights)', 'amount' => 22000], …],
 'removed' => [['what' => 'Airport transfer, one way — Innova × 1', 'amount' => -2100], …],
 'changed' => [['what' => 'Kutchi AC Cottage: Double → Single, 16 Oct – 19 Oct (3 nights)', 'amount' => -2850], …],
 'added_total', 'removed_total', 'changed_total', 'change']
```
It is also saved in the audit log with the change (`booking_modified`), so the booking page can say "Old total ₹X + added ₹A − taken off ₹R = new total ₹N" after saving.

### 3. Money: follows how the guest chose to pay (`booking_money()`)
| Field | Paid in full | 50% advance |
|---|---|---|
| `needed_now` | 100% of total | 50% of total (`payment_modes.advance.percent`) |
| `due_now` | total − paid | max(0, 50% − paid) |
| `later` | 0 | total − max(paid, 50%), **due before arrival** |
| `refund` | paid − total, if over | paid − total, if over |

* The 50% plan's balance is labelled **"before arrival"** because that is what guests are told (`payment_modes.advance.note`: "Balance due 30 days before arrival"). If the owner collects it at check-in instead, change that note and the labels together.
* Used by: the change preview (`admin/edit.php`), the booking page ("Due now", "Due before arrival", the Collect form), the Availability side panel, the guest's check-status page (`api/booking-lookup.php` → `due_now`, `due_later`) and the receipt PDF.
* A cancelled booking owes nothing and is owed nothing here. Its refund is `bookings.refund_amount`.

### 4. After saving
* `modify_booking()` re-checks availability under a lock, deletes and re-inserts the booking's rooms and extras, updates the totals, audits old → new with the breakdown, and (once Stayflexi is connected) cancels and re-pushes the reservation there.
* It does **not** move money. The desk records it on the booking page: **Collect the balance** (`record_offline_payment()`) or **Give back** (`record_offline_refund()`). `bookings.amount_paid` is always payments − refunds (`refresh_amount_paid()`).
* The guest sees the new details, "Updated by the resort", and the new due / refund on the check-status page and receipt.

### 5. Worked examples (all pass in the test script, see doc 30)
Booking: Kutchi Double + Deluxe Double, 3 nights, 1 Innova = ₹43,950.

| Change | Result |
|---|---|
| + 2 candlelight dinners | + ₹6,000 → ₹49,950 |
| − Innova | − ₹2,100 → ₹41,850 |
| − Deluxe room | − ₹19,500 → ₹24,450 |
| + 1 night | + ₹7,450 + ₹6,500 → ₹57,900 |
| Kutchi Double → Triple | changed + ₹4,500 → ₹48,450 |
| Same length, one day later | + (new night) − (old night) → ₹43,950 |
| Booked at a special ₹5,000 × 3, special later removed, + 1 night | ₹15,000 + ₹7,450 = ₹22,450 (booked nights stay ₹5,000) |
| Several at once (+room, occupancy, extras, +night) | adds up exactly, and the saved booking equals the preview |
| 50% paid (₹9,750 of ₹19,500), + Innova | total ₹21,600; collect now ₹1,050; ₹10,800 before arrival |
| 50% paid, cut to 1 night (₹6,500) | give back ₹3,250 |
| Paid in full, + Innova | collect now ₹2,100 |

### 6. Things to know
* The desk can change a stay that has already started, and isn't bound by the online 5-room limit (`staff_edit`).
* Room numbers in the line names ("Room 1", "Room 2") are positions in the booking, **not** physical cottage numbers. They may renumber after a change.
* Changing nothing and checking the price shows "Nothing that changes the price" and the same total.

---

<a id="docs-23"></a>

## Availability and Inventory Logic
_(was `docs/23-AVAILABILITY-AND-INVENTORY-LOGIC.md`)_
_Written 26 Sep 2026. How the engine decides how many cottages are free, what "holds" a room, and how the admin Availability calendar is built. Code: `lib/inventory.php`, `admin/calendar.php`._

### 1. The engine sells room *types*, not named cottages
The database knows "Kutchi AC Cottage: 12" and "Deluxe AC Cottage: 8" (`room_types.total_rooms`). A booking takes **N rooms of a type**. It never takes "Cottage 7". The desk decides which physical cottage a guest gets on arrival.

"Room 1 / Room 2" in a booking are **positions inside that booking** (used for occupancy and per-room extras), not cottage numbers.

### 2. Free rooms on a night
```
rooms_free(type, night) = rooms_open − rooms_booked − rooms_held      (never below 0)
rooms_free_for_stay     = the smallest rooms_free over every night of the stay
```
* **`rooms_open`**: how many the resort sells that night. It is `room_types.total_rooms` unless there is a row in `inventory (room_type_id, stay_date, rooms_open)`. Those rows come from Stayflexi (`bin/sync-inventory.php`, once connected). The admin grid for editing them ("Change rooms on sale") **was removed on 26 Sep 2026** at the owner's request. To close cottages by hand now, put rows in `inventory`, or ask for the grid back.
* **`rooms_booked`**: rooms on bookings that cover that night (`check_in <= night < check_out`) and still count (below).
* **`rooms_held`**: rows in `holds` that haven't expired. The current checkout **does not create holds**. An unpaid (`pending`) booking plays that role instead (below), so this is normally 0.

### 3. Which bookings hold rooms (`rooms_booked()`)
| Booking | Holds its rooms? |
|---|---|
| `confirmed` (paid, or recorded by the desk) | Always |
| `pending`, some money paid (`amount_paid > 0`) | Yes |
| `pending`, a UPI payment waiting for the desk to confirm | Yes |
| `pending`, created within the payment window | Yes. The window is the longer of `rules.hold_minutes` (20) and `upi.hold_minutes` (45) |
| `pending`, window closed, nothing paid | **No.** It has "lapsed" (`booking_lapsed()`); shown as "not paid" |
| `cancelled` | No |

> Why: before this, an abandoned checkout blocked its rooms forever. Now an unpaid booking frees its rooms 45 minutes after it was made.

### 4. Several rooms, mixed types
With several rooms the guest can pick a different cottage for each room. `search_availability()` lists a type if it can take **at least one** of the rooms (at least 1 free and big enough for that room's guests; `fits` says which). `quote_cart()` and `create_booking()` then check availability **cumulatively per type**: two Deluxe rooms need two free Deluxe. `create_booking()` re-checks inside a transaction with row locks (`FOR UPDATE` on MySQL), so two guests can't both take the last cottage.

### 5. Changing a booking doesn't collide with itself
While the desk changes a booking, `availability_ignore_booking($id)` leaves that booking's own rooms out of `rooms_booked()`. Moving a stay by a night therefore isn't refused because of its own rooms. It is set and cleared around `quote_modification()` and `modify_booking()`.

### 6. Other checks
* Seasons: `properties.season_start` / `season_end` (`property_open_between()`). The camp is seasonal and is also switched off (`properties.active = 0`).
* Online rules (`config.php → rules`): up to 21 nights, up to 5 rooms online (more goes to enquiry), at least 2 hours' notice. The desk (`staff_edit`) may exceed the room limit and change a stay already under way (`validate_dates_staff()`).
* Special prices can close a plan on a night (`rates.closed`) or set a minimum stay (`rates.min_stay`).
* Sold out: `next_available_dates()` suggests the next dates that work.

### 7. The Availability calendar (`admin/calendar.php`)
A tape chart, full page width. Each day is a full 24 hours. A stay is a bar from **check-in time (12:00) on arrival day to check-out time (10:00) on departure day** (`properties.check_in_time` / `check_out_time`), so a 10:00 departure and a 12:00 arrival sit in one row without overlapping.

One view only, **by cottage** (the "By guest" view was removed on request, 26 Sep 2026):
* Rows per cottage type. The server lays each type's rooms into non-overlapping lanes, one per cottage the type owns (`total_rooms`). It reuses the lane that became free most recently before the stay starts, so turnovers line up. Any booking that doesn't fit goes into a red **Overbooked** row. Free counts per night, arrivals ↓ and departures ↑, and hatched "not on sale" nights are shown.

Hover a bar for the booking card; hover a day for all its check-outs, check-ins and stays. **Click a booking bar** to open the side panel:
* Where they are today: Arrives in N days / Arrives today / Staying now (night X of Y) / Leaves today / Checked out
* Phone and email; check-in and check-out date and time; **ETA** (the arrival time the guest chose on the scroll wheel)
* Rooms as bullet points, e.g. "Kutchi AC Cottage Room 1 · Double · 2 guests"
* **Transfers:** the car transfers by full name, e.g. "Airport transfer, one way — Sedan × 2" (extras whose code starts with `transfer`). Listed only here, not again under Extras. No emoji
* Other extras; notes
* Money: total, paid, and either Due now / Before arrival (50% plan), Fully paid, or Refund due; plus "Paying in full" or "Paying 50% now…"
* Buttons: Change booking, Add or change transfer, Collect, Open booking, Call, WhatsApp

Ctrl/Cmd-click on a bar still opens the booking page directly. Only active properties are listed.

---

<a id="docs-25"></a>

## Guest Booking Flow — Internals
_(was `docs/25-GUEST-BOOKING-FLOW-INTERNALS.md`)_
_Written 26 Sep 2026. What happens on the guest's side, step by step, and the less obvious behaviour. Code: `backend/booking-engine/index.php` (page shell), `assets/engine.js` (all behaviour, ~1,000 lines), `assets/engine.css`, `api/*.php`._

### 1. Getting there
Every Book Now on the React site is a plain `<a href={bookingUrl()}>` to `/book/?property=kutch-safari-resort` (`frontend/src/lib/booking.ts`). It is never a wouter `<Link>`, because the engine is a different app and needs a full page load. `/booking`, `/book`, `/book/*`, `/admin` and `/admin/*` on the React site go through `BookingRedirect.tsx`.

### 2. Steps
| Step | What the guest does | Code |
|---|---|---|
| 1 Rooms | Dates (pre-filled: tomorrow → day after), number of rooms, **Single/Double/Triple for each room** (`#occList`). The search re-runs on any change. | `searchAvailability()` → `api/availability.php` |
| | Pick cottages. One room: pick a card. Several rooms: each card has a **Select** button that opens its room numbers ("Room 1 · Double") to tick. A room given to one cottage is **hidden** in the others until unticked. A "N of M rooms chosen · Continue" bar sits under the list. | `roomCard()`, `assignPanel()`, `wireRoomCards()` |
| 2 Extras | Airport transfer as one card with a counter per car; gala dinner jumps 0 → 10 (minimum); candlelight dinner; birding jeep. | `goToAddons()` |
| 3 Details | "Who is coming?" (optional in sample mode), email, city, **approximate arrival time (scroll wheel)**, notes, and pay 50% or in full. The cancellation dates and a terms link (PDF, new tab) are shown. | `goToDetails()` → `api/quote.php` |
| 4 Payment | Razorpay, UPI QR and, in test mode, **I've paid (test)**. | `createBooking()` → `api/book.php`, then `payWith…()` |
| Done | Confirmation with Receipt (PDF), Check status, Call. | `renderConfirmed()` |

Each step scrolls back up (`showStepTop()`) so the dates, rooms and guests stay in view. The price summary on the side comes from `quote.php` (grouped by cottage type, GST-inclusive for the resort).

### 3. Arrival time scroll wheel
Three wheels: hour (1–12), minutes (00/15/30/45) and AM/PM, with CSS scroll-snap (`.wheel*` in `engine.css`). They work with the mouse wheel, touch, clicking a number, or arrow keys.
* **Nothing is sent until the guest turns a wheel.** The wheels rest on 2:00 PM, but the field stays "Not set — scroll to choose" and sends an empty arrival time. That keeps the no-invented-data rule (doc 28).
* Once chosen it shows "Around 8:00 PM" with a **Clear** button. The value (e.g. `8:00 PM`) is saved in `bookings.arrival_time` and shown in the admin side panel.

### 4. Sample mode and test payments (turn off before launch)
* `rules.guest_details_optional = true`: name and phone may be blank. Blank is saved as blank; nothing is filled in.
* `test_payments.enabled = true`: the **I've paid (test)** button confirms the booking with no money (`api/payment-test.php`, payment provider `test`, "Test payment — no money taken").
* Razorpay and UPI show greyed "not connected yet" until their keys or UPI id are set.

### 5. After booking
* The page remembers the guest's last booking **on that device** (`localStorage` key `ksr_last_booking`). If they refresh or come back it shows "You booked KSR-… · Receipt (PDF) · Check status" until the stay is over.
* **Already booked? Check status** (`manage.php`): on the website top bar, mobile menu, home hero, footer, and the booking page header. Find by booking code + mobile or email, or open directly with `?ref=&token=`. It shows the stay, rooms, extras, total, paid, **due now / due before arrival** or **refund due**, "Updated by the resort" if the desk changed it, and Receipt / Terms / Call. There is **no online cancel** (`api/booking-cancel.php` always refuses); guests call or WhatsApp.

### 6. Receipts and terms (PDF, never downloaded)
* `lib/pdf.php` is a tiny built-in PDF writer (Helvetica, cp1252; "₹" is written as "Rs." because the base font has no rupee sign). No library to install.
* `lib/documents.php` builds the receipt (guest, stay, rooms with occupancy, extras, totals, payments and refunds, due/refund, this booking's cancellation dates, terms) and the terms on their own, from the settings plus `terms.house_rules`.
* `document.php?doc=receipt&ref=&token=` shows it **in the browser tab with PDF.js** (from cdnjs), with Print and Save as PDF, so it never downloads. Raw PDFs: `receipt.php` (guest token or signed-in staff) and `terms.php?property=`.
* **Receipts are never stored.** Each one is generated on the spot from the booking's current details, so a change by the desk shows at once.

### 7. Small things that matter
* Dates are handled as **local Indian dates** in the browser (`isoLocal()`/`todayISO()`). Using `toISOString()` once made check-out equal check-in after midnight UTC.
* Error messages are in-page boxes (`alertBox()`), never browser pop-ups.
* The header wraps below 960 px so the buttons don't overflow.
* The "Continue" bar under the room list is deliberately not sticky. It overlapped the list while scrolling.

---

<a id="docs-26"></a>

## Payments: Razorpay, UPI QR, Test Payments, Refunds
_(was `docs/26-PAYMENTS-RAZORPAY-UPI-TEST.md`)_
_Written 26 Sep 2026. Code: `lib/payment.php`, `api/payment-*.php`, `api/webhook-razorpay.php`, admin booking page._

### 1. What the guest can pay
* **50% now** or **in full** (`config.php → payment_modes`). "Pay at the property" is off (`hotel.enabled = false`), so every booking has money behind it.
* The amount asked for at checkout is `bookings.amount_due_now`, fixed when the booking is made. A later change by the desk does **not** change it. The desk collects any difference itself (doc 22).

### 2. Razorpay (cards, UPI apps, netbanking)
1. `api/payment-create.php` → `razorpay_create_order()` → Razorpay order for `amount_due_now` (in paise). The booking ref goes in `receipt`/`notes`. **Since 29 Sep 2026 the request must include the booking's private `manage_token`**, and only unpaid (pending) bookings can start a payment — before, anyone could start one with just a booking number and leave a stranger's booking "waiting for UPI", holding its rooms.
2. The guest pays in Razorpay Checkout (script from Razorpay's CDN, loaded only when enabled).
3. `api/payment-verify.php` → `razorpay_confirm()` checks the HMAC signature → `settle_payment()`.
4. `api/webhook-razorpay.php` (`payment.captured`, `payment.failed`, `refund.processed`) is the backup if the browser closes. `settle_payment()` is **idempotent**: the browser and the webhook often both arrive, and the second is ignored.
5. `settle_payment()`: marks the payment paid, recounts `amount_paid` = payments − refunds (`refresh_amount_paid()`), sets the booking `confirmed`, pushes it to Stayflexi (doc 27), and sends the confirmation email.

**Keys** live only in `backend/booking-engine/config.local.php` (git-ignored). This PC uses the **test** keys. Test and live key pairs were checked against Razorpay on 26 Sep 2026. The live Key Secret was shared over WhatsApp and chat, so **regenerate it before launch**. Any copy handed to someone else must leave the keys out (doc 30 §4).

**Windows HTTPS fix:** PHP on Windows had no certificate bundle, so every call to Razorpay failed with "HTTP 0". `curl_trust_system_certs()` (`lib/db.php`) tells cURL to use the Windows certificate store (`CURLSSLOPT_NATIVE_CA`). It is used by Razorpay and Stayflexi calls. Linux hosts don't need it.

### 3. UPI QR (straight to the bank account)
* Needs `upi.vpa` (the account's UPI id; not set yet). The QR is drawn in the browser (qrious) with the exact amount and the booking ref.
* The payment is `awaiting_confirmation` until staff see the money in the bank and click **Money received** in the admin (`upi_mark_received()`). That is the **only** way a UPI QR payment becomes paid.
* While waiting, the booking keeps its rooms. With nothing paid and nothing waiting, it lapses after 45 minutes (doc 23).

### 4. Test payments (turn off before launch)
`test_payments.enabled = true` shows **I've paid (test)**. `api/payment-test.php` → `test_payment_settle()` needs the booking's own `manage_token`, records a `test` payment of `amount_due_now`, and goes through the same `settle_payment()` path as a real payment, so everything downstream behaves as it will for real. Receipts say "Test payment — no money taken".

### 5. Money taken or given back by the desk
* **Collect the balance** → `record_offline_payment()` (cash, card, bank transfer, UPI — only these). A **pending** booking is confirmed once what it needs now is paid (all of it, or the 50%), with Stayflexi push and confirmation email, the same as an online payment (fixed 29 Sep 2026). The amount is capped at the balance. It is refused on cancelled or fully paid bookings.
* **Give back** (after a change made the stay cheaper) → `record_offline_refund()`. It is stored as a negative `refund` row.
* **Cancel** → the ladder decides the charge. Card payments are refunded through Razorpay automatically (`razorpay_refund()`) once it is connected; otherwise the refund is recorded.
* `bookings.amount_paid` is always recounted by `refresh_amount_paid()` = paid payments − refunds. Online settlement and UPI confirmation now use it too. **Fixed 26 Sep 2026:** before, they counted payments only and ignored earlier refunds.

### 6. What "due" means
See doc 22 §3 (`booking_money()`): due now, due before arrival (50% plan), or refund due.

---

<a id="docs-27"></a>

## Stayflexi (Channel Manager) Sync
_(was `docs/27-STAYFLEXI-CHANNEL-SYNC.md`)_
_Written 26 Sep 2026. Code: `lib/channel.php`, `bin/sync-inventory.php`, `bin/retry-failed-sync.php`, `bin/map-stayflexi.php`, `bin/check-stayflexi.php`. **Status: not connected** (`stayflexi.enabled = false`, no API key)._

### 1. Why it matters
Stayflexi already sells the cottages on MakeMyTrip, Booking.com and others. If this website sells a room without telling Stayflexi, the same room can be sold twice. There are only two safe ways to run:
* **A. Connected:** availability is pulled from Stayflexi, and every paid booking is pushed back to it. Stayflexi is the single source of truth.
* **B. Not connected (now):** the engine uses its own numbers. That is safe only if the website's rooms and the OTAs' rooms are kept **separate** in Stayflexi (e.g. keep 4 cottages back for direct bookings). Selling the same rooms in both places and reconciling by hand is not safe.

### 2. When the engine talks to Stayflexi (once connected)
| Event | Call | Where |
|---|---|---|
| Just before a booking is saved | `channel_verify_still_available()`. With `fail_closed = true` the booking is **refused** if Stayflexi can't confirm the room is free | `create_booking()` |
| Booking paid (Razorpay, UPI confirmed, test) | `channel_push_booking()` | `settle_payment()`, `upi_mark_received()` |
| Desk changes a booking | cancel the old reservation, push the new one | `modify_booking()` |
| Admin cancels | `channel_cancel_booking()` | `cancel_booking()` |
| Every 10 min (cron) | pull availability into `inventory` | `bin/sync-inventory.php` |
| Hourly (cron) | re-send confirmed bookings not yet synced, and cancellations that failed | `bin/retry-failed-sync.php` |

* Unpaid bookings are **never** pushed. Only confirmed ones are.
* A failed push or cancel is written to `bookings.sf_sync_error` and shown in red on the admin booking page. The hourly job retries it by itself. **Fixed on this project:** a failed cancel used to be only logged, so the room stayed closed on the OTAs.
* The admin buttons "Set status" and "Send to Stayflexi" were removed at the owner's request. Everything happens by itself.

### 3. Connecting it
1. Ask Stayflexi support for API access for a custom booking engine (API key/secret, hotel id).
2. **Confirm the endpoint paths.** The ones in `lib/channel.php` (e.g. `/core/api/v1/reservations/{id}/cancel`) are educated guesses and have **not** been checked against Stayflexi's documentation.
3. Put the key in `config.local.php` → `stayflexi`, then map the ids: `php bin/map-stayflexi.php property kutch-safari-resort HOTEL_ID`, `… room kutchi-ac-cottage ROOM_TYPE_ID`, `… plan 1 RATE_PLAN_ID`, `… list`.
4. `php bin/check-stayflexi.php`, then set `enabled = true` and add the two cron jobs (doc 15).

### 4. Rooms on sale without Stayflexi
Stayflexi fills `inventory.rooms_open`. The admin grid to edit those numbers by hand ("Change rooms on sale") was removed on 26 Sep 2026. Until Stayflexi is connected, every cottage type is sold up to `room_types.total_rooms`. To keep cottages back, lower `total_rooms` or add `inventory` rows, or ask for the grid back.
