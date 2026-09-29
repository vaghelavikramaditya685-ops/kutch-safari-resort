# Packages and Pricing

## 1. Where prices live now
| Source | What | Who edits it |
|---|---|---|
| **Booking engine database** (`backend/booking-engine/seed.sql` for a fresh install; Admin → **Special prices** for dates) | **What guests are actually charged**: room rate plans, special (date) prices, add-ons, packages | Staff (special prices); developer (normal prices, extras) |
| `backend/booking-engine/config.php` | GST slabs, payment modes, deposit %, cancellation ladder, booking limits | Developer |
| `RannUtsavPackage.tsx`, `Packages.tsx` | Prices **displayed** on the marketing site (hard-coded text) | Developer |

> Nothing links the displayed prices to the charged ones. When rates change, update the page text as well as the engine.

---

## 2. Booking Engine Pricing

### 2.1 Resort: published tariff, 1 Apr 2026 – 31 Mar 2027 (not Diwali or Christmas / New Year)
**GST included.** The guest pays exactly these figures per room per night. One plan: room with breakfast (CPAI).

| Cottage | Rooms | Single | Double | Triple (double + extra bed) |
|---|---|---|---|---|
| Deluxe AC Cottage | 8 | ₹5,500 | ₹6,500 | ₹8,000 |
| Kutchi AC Cottage | 12 | ₹6,500 | ₹7,450 | ₹8,950 |

The guest picks **Single / Double / Triple for each room** in the search bar, e.g. 3 rooms as Double, Single, Triple. Each room is priced for its own occupancy.

In the database (`seed.sql` → `rate_plans`): `base_price` = double, `single_price` = single, and the extra bed = `room_types.extra_adult_price` (₹1,500). A special price (Admin → Special prices) replaces the double price for those nights only, and the single keeps the same discount. Each night of a stay is priced on its own. The full calculation is in `21-PRICING-LOGIC-DEEP-DIVE.md`. The breakfast-and-dinner (MAP) and all-meals (AP) plans were removed to match the tariff.

`config.php` → `prices_include_tax` lists the properties whose prices include GST (the resort). For those, GST is taken **out** of the price instead of added on top.

### 2.2 White Rann Camp: GST on top (unchanged; **switched off in the engine since 26 Sep 2026**)
Deluxe Air-Cool tent ₹7,499 (peak ₹8,450), Non-AC ₹6,500 (peak ₹7,499), + GST. Peak dates: Christmas/New Year 19 Dec 2026 – 4 Jan 2027 · Uttarayan 13–15 Jan 2027 · Full moon 21–23 Jan 2027. There's no single rate (single = double). Triple adds ₹1,800.

### 2.3 Tax
GST slabs **per room per night**: 5% up to ₹7,500, 18% above. The slab is decided on the room's value including any extra bed. **The accountant must confirm this**, including the case in §5.

### 2.4 Payment and cancellation (`config.php`)
* The guest pays **50% now or in full** when booking. "Pay at the property" is switched off (`payment_modes.hotel.enabled = false`), so no booking is left fully unpaid.
* After booking, what is owed follows that choice (`booking_money()`): **Due now** (short of 100%, or of 50% on the 50% plan), **Due before arrival** (the other half on the 50% plan; guests are told "Balance due 30 days before arrival"), or **Refund due**. Shown in the admin, the check-status page and the receipt. See `22-BOOKING-CHANGES-AND-MONEY.md`.
* **When the desk changes a booking** it is priced as a difference: old total + what is added − what is taken off. Everything kept stays at its booked price (doc 22).
* An unpaid booking holds its rooms only while the guest can still pay (the longer of the card window, 20 min, and the UPI QR window, 45 min), or while a UPI payment waits for staff to confirm it. After that it stops blocking rooms and shows as "not paid" (`booking_lapsed()`).
* Payment by Razorpay (instant) or UPI QR (confirmed by staff in admin; the QR is valid for 45 min).
* Cancellation: free 30+ days before arrival, 75% charged at 21–29 days, 100% under 21 days.
* Limits: 21 nights, 5 rooms online, 2 h minimum notice, 20-min hold while paying by card.

### 2.5 Add-ons (resort; GST included like the rooms)
| Add-on | Price |
|---|---|
| Airport transfer, one way: Sedan / Ertiga / Innova | ₹1,200 / ₹1,500 / ₹2,100 per trip |
| Gala dinner with music programme (groups) | ₹1,500 per person, **minimum 10** (+ jumps 0 → 10) |
| Candlelight dinner overlooking the lake | ₹3,000 per person |
| Birding jeep, half day | ₹3,000 per booking |

The separate "Extra bed" add-on was removed at both properties. Choosing **Triple** for a room adds the extra bed, so it can't be charged twice. The camp's add-ons (Rann permit and jeep, camel cart, private folk evening, transfer from Bhuj) are unchanged.

---

## 3. White Rann Camp page (`RannUtsavPackage.tsx`, `/white-rann-camp`)
* Highlights: 20 Swiss tents, attached bath with hot and cold water, multi-cuisine restaurant, cultural music, campfire.
* Includes: tent, dinner, breakfast and hi-tea, 2 bottles of water.
* Displayed tariff (1 Dec 2026 – 31 Jan 2027, + GST): Deluxe Air Cool ₹8,450 peak / ₹7,499 non-peak. Non-AC ₹7,499 / ₹6,500. Extra child 6–12 ₹1,800. These match the engine.
* CTA: WhatsApp only. A "Book a tent" button → `bookingUrl({ property: "white-rann-camp" })` would connect it to the engine.

## 4. Colors of Kutch
### 4.1 Displayed (`RannUtsavPackage.tsx`)
Per person, twin sharing, + tax:

| | 2 pax | 4 pax | 10 pax | Extra person |
|---|---|---|---|---|
| 2N/3D | ₹15,000 | ₹13,500 | ₹11,500 | ₹6,000 |
| 3N/4D | ₹21,500 | ₹18,500 | ₹17,000 | ₹8,000 |

Itinerary: Day 1 Banni, White Rann, Rann Utsav · Day 2 Kala Dungar & Dholavira · Day 3 Bhuj & Bhujodi · Day 4 (3N/4D) Mandvi.

### 4.2 `Packages.tsx` (`/packages`)
Two cards (The Rann Short Break 2N/3D, The Complete Kutch 3N/4D) with a description only. No prices and no CTA.

### 4.3 In the engine
Both packages are in the `packages` / `package_prices` tables with itineraries, but they are **enquiry-only** by design (vehicle, driver and guide can't be confirmed by a payment form).

---

## 5. Pending owner sign-off
1. **GST on the Deluxe triple (₹8,000 incl. GST).** Any GST-inclusive price between ₹7,875 and ₹8,850 falls between the two slabs: at 5% the value before tax is over ₹7,500 (so it should be 18%), and at 18% it's under ₹7,500 (so it should be 5%). The engine uses 18% (₹6,779.66 + ₹1,220.34). The guest pays ₹8,000 either way; only the split on the invoice changes. Ask the accountant.
2. Diwali and Christmas / New Year resort rates (the tariff excludes them; the engine uses the normal rates for now).
3. Rates after 31 Mar 2027 (the engine keeps using these until changed).
4. Cancellation ladder and pay-at-property rules.
5. The "Additional activities" sheet (folk music programme, jeep trail, birding trail) was too blurry to read. The details are needed before they can be added.
6. The 50% plan's balance: 30 days before arrival (current wording) or at check-in?
