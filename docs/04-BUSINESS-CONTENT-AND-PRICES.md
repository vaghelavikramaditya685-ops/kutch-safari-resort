# Business, Content and Prices

_Combined on 30 Sep 2026 from 4 earlier files. Start at [`README.md`](../README.md) for the overview and hard rules._

Facts about the resort, the owner's brochure, published prices and packages, goals and user stories.

---

## Contents
* [Product Requirements](#context-prd) _(was `context/prd.md`)_
* [User Stories](#context-user-stories) _(was `context/user_stories.md`)_
* [Business Content Reference](#docs-17) _(was `docs/17-BUSINESS-CONTENT-REFERENCE.md`)_
* [Packages and Pricing](#docs-08) _(was `docs/08-PACKAGES-AND-PRICING.md`)_

---

<a id="context-prd"></a>

## Product Requirements
_(was `context/prd.md`)_
### Problem
The resort took bookings by phone, WhatsApp and online travel agencies (OTAs, via Stayflexi) [DOC `docs/27`]. The previous website had a React booking engine that stored reservations in a JSON file and could not run on Vercel [DOC `docs/20`]. Direct bookings mean no OTA commission [INFERRED: usual reason for a direct engine].

### Vision
A beige, "Sundown Terracotta" website that shows the resort and Kutch [DOC `docs/04`], and a booking engine where a guest books and pays in a few minutes, while the owner manages everything (bookings, changes, money, availability, prices) from one admin panel [DOC].

### Users
* **Guest**: books online, pays 50% or in full, checks status later, receives a receipt [CODE `index.php`, `manage.php`].
* **Owner / front desk** (single admin role `owner`): sees the day's arrivals, changes bookings, records cash/UPI, cancels, sets special prices [CODE `admin/`].

### Goals
1. Book any mix of Kutchi / Deluxe cottages, Single/Double/Triple per room, with extras (cars, dinners) [CODE `lib/booking.php quote_cart`].
2. Take money: Razorpay online, UPI QR confirmed by the desk, or at the desk [CODE `lib/payment.php`].
3. Price changes transparently: old total + added − taken off [CODE `modification_delta`] (owner requirement [DOC]).
4. Never sell a room twice (availability locks, Stayflexi bridge) [CODE `rooms_booked`, `lib/channel.php`].
5. Owner-controlled data only: no invented data in the real database [DOC owner rule; exception: 6 marked demo bookings, 29 Sep].

### Non-goals (for now)
* White Rann Camp online booking (switched off: `properties.active = 0`) [CODE].
* Guest self-cancellation (owner decision: only the admin cancels) [CODE `api/booking-cancel.php` returns 403].
* Bots / WhatsApp notifications ("right now no bot") [DOC].
* Colors of Kutch packages are enquiry-only [DOC `docs/08`].

### Core requirements
* GST-inclusive prices for the resort, per-night slabs (5% ≤ ₹7,500, else 18%) [CODE `tax_split`].
* Payment modes: full or 50% advance; pay-at-property off [CODE `config.php payment_modes`].
* Cancellation ladder: free 30+ days, 75% at 21–29, 100% under 21 [CODE `config.php cancellation`].
* Limits: 21 nights, 5 rooms online, up to 2 years ahead, ≤ 50 of one extra [CODE `config.php rules`].
* Admin always asks for the password (per tab, 10-minute idle); username/password sent as SHA-256 [CODE `admin/_auth.php`, `admin/login.php`].

### Success measures
[UNKNOWN] — none defined. Suggested [PLANNED]: share of bookings made direct, abandoned-checkout rate (pending bookings that lapse), time from search to payment.

### Scope of the first live version
Website + engine for Kutch Safari Resort only, Razorpay live, UPI QR, Stayflexi connected or inventory split by hand, sample mode and test payments off [DOC `docs/30` go-live checklist].

---

<a id="context-user-stories"></a>

## User Stories
_(was `context/user_stories.md`)_
### Personas
* **Asha, the guest** — books from a phone, may come with family (2–3 rooms), wants the price clear and a receipt.
* **Manvir, the owner / front desk** — runs the day from the admin panel; takes calls, cash and UPI; changes bookings.

### Guest
| Story | Acceptance criteria | Evidence |
|---|---|---|
| As a guest, I want to see which cottages are free for my dates so I can pick one. | Dates are pre-filled; changing dates, rooms or guests re-searches; sold-out suggests next dates; stays in the past, > 21 nights or > 2 years ahead are refused with a message. | [CODE `api/availability.php`, `validate_dates`] |
| As a guest with a family, I want different cottages and Single/Double/Triple per room. | Each room has its own occupancy; with several rooms a Select button assigns room numbers; a room ticked in one cottage disappears from others. | [CODE `assets/engine.js assignPanel`] |
| As a guest, I want to add a car or dinner. | Transfers shown as one card with a counter per car; gala dinner needs ≥ 10; quantities 1–50. | [CODE `quote_cart`] |
| As a guest, I want to pay 50% now or in full. | Both options priced; balance shown as "due before arrival". | [CODE `payment_modes`, `booking_money`] |
| As a guest, I want to know my booking later. | "Already booked? Check status" finds it by code + mobile/email or private link; shows due / refund; receipt PDF opens in the tab. | [CODE `manage.php`, `document.php`] |
| As a guest, I want my typing mistakes caught without losing what I typed. | Bad phone/email caught on the page; server refusal keeps me on the details step with my input. | [CODE `engine.js createBooking`] |

### Owner / desk
| Story | Acceptance criteria | Evidence |
|---|---|---|
| As the owner, I want today's picture at a glance. | Bookings list: Staying now → Coming up → Cancelled/not paid → Finished; counts for arriving / in house / awaiting payment. | [CODE `admin/index.php`] |
| As the owner, I want to change a booking and see exactly what the price change is. | Preview lists Added / Taken off / Changed with amounts; new total = old + added − taken off; kept items keep their booked price. | [CODE `modification_delta`, `admin/edit.php`] |
| As the owner, I want to record cash/UPI and refunds. | Amount capped at the balance; listed methods only; a pending booking is confirmed once enough is paid. | [CODE `admin/booking.php`, `record_offline_payment`] |
| As the owner, I want to cancel on the guest's behalf. | Cancel by booking code; today's charge shown; rooms go back on sale; card refund via Razorpay when connected. | [CODE `cancel_booking`] |
| As the owner, I want special prices for certain dates. | Pick nights and cottages, check, save; guard rails on price; normal price untouched. | [CODE `admin/rates.php`] |
| As the owner, I want to see who is where, by cottage. | Availability tape chart by cottage; click a booking for check-in/out, ETA, rooms, transfers, money. | [CODE `admin/calendar.php`] |
| As the owner, I want the admin panel to always ask for the password. | New tab/window, browser restart or 10 minutes idle → sign in again; credentials sent as SHA-256. | [CODE `admin/_auth.php`, `admin/login.php`] |

---

<a id="docs-17"></a>

## Business Content Reference
_(was `docs/17-BUSINESS-CONTENT-REFERENCE.md`)_
_Updated 30 Sep 2026 (brochure added)._

The facts used across the site and the booking engine. When one changes, update both. Places to check: `frontend/src/pages/*`, `Navbar.tsx`, `Footer.tsx`, `backend/booking-engine/seed.sql` (or the admin panel), and `backend/booking-engine/config.php`.

### Identity
* **Kutch Safari Resort**, Near Rudramata Dam, Bhuj–Khavda Road, Bhuj, Kutch, Gujarat 370001. About 15 km from Bhuj.
* Founder: Mike Vaghela. Hosting for 35+ years (since 1992).
* Directions: take the Khavda road out of Bhuj, go 14 km, pass the bus stop to the LORIYA 7 KM milestone, turn right and go up the hill.
* Tagline (Home hero): "Where the Lake Meets the Desert". Brochure tagline: "Where Tradition Meets Comfort" (used in the Home welcome heading).
* Home welcome text: the owner's own wording (30 Sep 2026), see [`05-HOME-PAGE.md`](02-WEBSITE.md#docs-05) §2.3. Do not reword it.

### Contact
* Phone / WhatsApp: +91 99252 38599 (WRC also lists +91 70165 84647 in the engine seed)
* Email: kutchsafaribhuj@yahoo.com (WRC in the engine seed: whiteranncamp@gmail.com)
* Instagram: @kutchsafariresort

### Resort
* 20 cottages: **12 Kutchi AC** (traditional bhunga, mirror-work, lake-facing balcony) and **8 Deluxe AC** (larger/uncluttered, garden and lake view).
* In-room: AC, Wi-Fi, hot kettle, TV, in-room safe, hair dryer.
* Facilities: pool, The Banni restaurant (Kutchi, Gujarati, Punjabi, Chinese, Continental), travel desk, garden lawn (events up to 300), room service.
* Check-in 12:00. Check-out 10:00 in the engine seed (the old engine used 11:00, so confirm). The Availability chart draws stays from these times.
* Payment: 50% now or in full. Guests are told the 50% balance is "due 30 days before arrival". Cancellation: free 30+ days before, 75% at 21–29 days, 100% under 21. Only the resort cancels (guests call or WhatsApp).

### White Rann Camp (Dhordo) — switched off in the booking engine for now
* 3 minutes from the White Rann entry and Rann Utsav. **Open 1 Dec 2026 – 31 Jan 2027.**
* 20 Swiss tents: 6 Deluxe Air-Cool, 14 Non-AC. Attached bath, hot and cold water.
* Includes dinner, breakfast, hi-tea, 2 bottles of water, campfire and folk music.
* Peak dates: Christmas/New Year 19 Dec – 4 Jan, Uttarayan 13–15 Jan, full moon 21–23 Jan.

### Prices
Resort 2026–27 (GST included, breakfast): Deluxe ₹5,500 single / ₹6,500 double; Kutchi ₹6,500 / ₹7,450; extra bed ₹1,500. Candlelight dinner ₹3,000 per person; gala dinner ₹1,500 per person (min 10); airport transfer one way: Sedan ₹1,200, Ertiga ₹1,500, Innova ₹2,100. Full details are in [`08-PACKAGES-AND-PRICING.md`](#docs-08). The engine database is the source of truth for what is charged.

### Social proof (Home TrustStrip, confirm with the owner)
Google 4.6/5 · TripAdvisor 4.5/5 · MakeMyTrip Assured.

### Destinations (`/destination/:slug`)
Dholavira · Road to Heaven · The Great White Rann · Mandvi Beach & Palace · Artisan villages (Banni, Bhujodi) · Kala Dungar.

### Colors of Kutch
"Kutch ke Rang, Apno ke Sang". 2N/3D and 3N/4D, private vehicle, permits, twin sharing, priced per person by group size.

### Owner directives (Mike Vaghela)
1. "Changes that are told to you in the PDF. Don't change anything else."
2. "Integrate the beige colour into the entire website." (`#f8f5e2`)
3. "Where the lake meets the desert, it should be white."
4. Keep the old masonry style for the cottages.
5. The offline enquiry route stays available next to online booking. The engine keeps "Call / Enquire on WhatsApp" beside every step.

### Brochure: "KUTCH SAFARI RESORT 2026 2027.pdf" (given 30 Sep 2026)
The owner's printed brochure. Used for the **Experiences** page (doc 10) and the Home welcome heading. What it contains:
* Why Visit Kutch? (White Rann, beaches, textiles, communities, Dholavira UNESCO site, palaces; visitors from the UK, France, Italy, Japan, USA, Australia, all India).
* Guest experiences: morning yoga for groups, lake-view gala dinner, sunrise breakfast, candlelight dinner. On request: gala dinner, folk music, camel cart welcome.
* Other assistance: jeep safari, walking trails, travel assistance, laundry, doctor on call, tourist guides, Wi-Fi, pet friendly.
* The Banni: multi-cuisine overlooking the dam, "one of the few restaurants in Kutch that serve non-vegetarian meals".
* How to reach: Bhuj airport 15 km, railway 14 km; direct flights and daily trains from Mumbai, Ahmedabad, Delhi.

**Where it disagrees with the website (not changed; owner to confirm which is right):**
| Fact | Brochure | Website |
|---|---|---|
| Ahmedabad | 375 km | 350 km (Plan Your Visit) |
| Rajkot | 250 km | 240 km |
| Mandvi | 75 km | 60 km (Plan Your Visit), 65 km (destination guide) |
| Dholavira | 115 km | 220 km (destination guide) |
| Years hosting | 34 years | 35+ (StatsBand) |
| Room names | "Kutchi AC Rooms – 8" | Deluxe AC Cottage – 8, Kutchi AC – 12 |
| Airport / railway | 15 km / 14 km | not on the site |

### Content still owed by the owner
Food photos. FAQ. WRC logo (transparent PNG). Confirmed resort rates, GST and cancellation terms. Real guest reviews. Resort Diwali and Christmas supplement dates. Original (high-resolution) brochure photos. Answers to the brochure/website differences above.

---

<a id="docs-08"></a>

## Packages and Pricing
_(was `docs/08-PACKAGES-AND-PRICING.md`)_
### 1. Where prices live now
| Source | What | Who edits it |
|---|---|---|
| **Booking engine database** (`backend/booking-engine/seed.sql` for a fresh install; Admin → **Special prices** for dates) | **What guests are actually charged**: room rate plans, special (date) prices, add-ons, packages | Staff (special prices); developer (normal prices, extras) |
| `backend/booking-engine/config.php` | GST slabs, payment modes, deposit %, cancellation ladder, booking limits | Developer |
| `RannUtsavPackage.tsx`, `Packages.tsx` | Prices **displayed** on the marketing site (hard-coded text) | Developer |

> Nothing links the displayed prices to the charged ones. When rates change, update the page text as well as the engine.

---

### 2. Booking Engine Pricing

#### 2.1 Resort: published tariff, 1 Apr 2026 – 31 Mar 2027 (not Diwali or Christmas / New Year)
**GST included.** The guest pays exactly these figures per room per night. One plan: room with breakfast (CPAI).

| Cottage | Rooms | Single | Double | Triple (double + extra bed) |
|---|---|---|---|---|
| Deluxe AC Cottage | 8 | ₹5,500 | ₹6,500 | ₹8,000 |
| Kutchi AC Cottage | 12 | ₹6,500 | ₹7,450 | ₹8,950 |

The guest picks **Single / Double / Triple for each room** in the search bar, e.g. 3 rooms as Double, Single, Triple. Each room is priced for its own occupancy.

In the database (`seed.sql` → `rate_plans`): `base_price` = double, `single_price` = single, and the extra bed = `room_types.extra_adult_price` (₹1,500). A special price (Admin → Special prices) replaces the double price for those nights only, and the single keeps the same discount. Each night of a stay is priced on its own. The full calculation is in [`21-PRICING-LOGIC-DEEP-DIVE.md`](05-BOOKING-ENGINE.md#docs-21). The breakfast-and-dinner (MAP) and all-meals (AP) plans were removed to match the tariff.

`config.php` → `prices_include_tax` lists the properties whose prices include GST (the resort). For those, GST is taken **out** of the price instead of added on top.

#### 2.2 White Rann Camp: GST on top (unchanged; **switched off in the engine since 26 Sep 2026**)
Deluxe Air-Cool tent ₹7,499 (peak ₹8,450), Non-AC ₹6,500 (peak ₹7,499), + GST. Peak dates: Christmas/New Year 19 Dec 2026 – 4 Jan 2027 · Uttarayan 13–15 Jan 2027 · Full moon 21–23 Jan 2027. There's no single rate (single = double). Triple adds ₹1,800.

#### 2.3 Tax
GST slabs **per room per night**: 5% up to ₹7,500, 18% above. The slab is decided on the room's value including any extra bed. **The accountant must confirm this**, including the case in §5.

#### 2.4 Payment and cancellation (`config.php`)
* The guest pays **50% now or in full** when booking. "Pay at the property" is switched off (`payment_modes.hotel.enabled = false`), so no booking is left fully unpaid.
* After booking, what is owed follows that choice (`booking_money()`): **Due now** (short of 100%, or of 50% on the 50% plan), **Due before arrival** (the other half on the 50% plan; guests are told "Balance due 30 days before arrival"), or **Refund due**. Shown in the admin, the check-status page and the receipt. See [`22-BOOKING-CHANGES-AND-MONEY.md`](05-BOOKING-ENGINE.md#docs-22).
* **When the desk changes a booking** it is priced as a difference: old total + what is added − what is taken off. Everything kept stays at its booked price (doc 22).
* An unpaid booking holds its rooms only while the guest can still pay (the longer of the card window, 20 min, and the UPI QR window, 45 min), or while a UPI payment waits for staff to confirm it. After that it stops blocking rooms and shows as "not paid" (`booking_lapsed()`).
* Payment by Razorpay (instant) or UPI QR (confirmed by staff in admin; the QR is valid for 45 min).
* Cancellation: free 30+ days before arrival, 75% charged at 21–29 days, 100% under 21 days.
* Limits: 21 nights, 5 rooms online, 2 h minimum notice, 20-min hold while paying by card.

#### 2.5 Add-ons (resort; GST included like the rooms)
| Add-on | Price |
|---|---|
| Airport transfer, one way: Sedan / Ertiga / Innova | ₹1,200 / ₹1,500 / ₹2,100 per trip |
| Gala dinner with music programme (groups) | ₹1,500 per person, **minimum 10** (+ jumps 0 → 10) |
| Candlelight dinner overlooking the lake | ₹3,000 per person |
| Birding jeep, half day | ₹3,000 per booking |

The separate "Extra bed" add-on was removed at both properties. Choosing **Triple** for a room adds the extra bed, so it can't be charged twice. The camp's add-ons (Rann permit and jeep, camel cart, private folk evening, transfer from Bhuj) are unchanged.

---

### 3. White Rann Camp page (`RannUtsavPackage.tsx`, `/white-rann-camp`)
* Highlights: 20 Swiss tents, attached bath with hot and cold water, multi-cuisine restaurant, cultural music, campfire.
* Includes: tent, dinner, breakfast and hi-tea, 2 bottles of water.
* Displayed tariff (1 Dec 2026 – 31 Jan 2027, + GST): Deluxe Air Cool ₹8,450 peak / ₹7,499 non-peak. Non-AC ₹7,499 / ₹6,500. Extra child 6–12 ₹1,800. These match the engine.
* CTA: WhatsApp only. A "Book a tent" button → `bookingUrl({ property: "white-rann-camp" })` would connect it to the engine.

### 4. Colors of Kutch
#### 4.1 Displayed (`RannUtsavPackage.tsx`)
Per person, twin sharing, + tax:

| | 2 pax | 4 pax | 10 pax | Extra person |
|---|---|---|---|---|
| 2N/3D | ₹15,000 | ₹13,500 | ₹11,500 | ₹6,000 |
| 3N/4D | ₹21,500 | ₹18,500 | ₹17,000 | ₹8,000 |

Itinerary: Day 1 Banni, White Rann, Rann Utsav · Day 2 Kala Dungar & Dholavira · Day 3 Bhuj & Bhujodi · Day 4 (3N/4D) Mandvi.

#### 4.2 `Packages.tsx` (`/packages`)
Two cards (The Rann Short Break 2N/3D, The Complete Kutch 3N/4D) with a description only. No prices and no CTA.

#### 4.3 In the engine
Both packages are in the `packages` / `package_prices` tables with itineraries, but they are **enquiry-only** by design (vehicle, driver and guide can't be confirmed by a payment form).

---

### 5. Pending owner sign-off
1. **GST on the Deluxe triple (₹8,000 incl. GST).** Any GST-inclusive price between ₹7,875 and ₹8,850 falls between the two slabs: at 5% the value before tax is over ₹7,500 (so it should be 18%), and at 18% it's under ₹7,500 (so it should be 5%). The engine uses 18% (₹6,779.66 + ₹1,220.34). The guest pays ₹8,000 either way; only the split on the invoice changes. Ask the accountant.
2. Diwali and Christmas / New Year resort rates (the tariff excludes them; the engine uses the normal rates for now).
3. Rates after 31 Mar 2027 (the engine keeps using these until changed).
4. Cancellation ladder and pay-at-property rules.
5. The "Additional activities" sheet (folk music programme, jeep trail, birding trail) was too blurry to read. The details are needed before they can be added.
6. The 50% plan's balance: 30 days before arrival (current wording) or at check-in?
