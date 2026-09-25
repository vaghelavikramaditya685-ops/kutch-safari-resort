# Packages and Pricing

## 1. Where prices live now
| Source | What | Who edits it |
|---|---|---|
| **Booking engine database** (`booking-engine/seed.sql` → Admin → Rates) | **What guests are actually charged**: room rate plans, peak-date rates, add-ons, packages, tax | Staff, in the admin panel |
| `booking-engine/config.php` | GST slabs, payment modes, deposit %, cancellation ladder, booking limits | Developer |
| `RannUtsavPackage.tsx`, `Packages.tsx` | Prices **displayed** on the marketing site (hard-coded text) | Developer |

> Nothing links the displayed prices to the charged ones. When rates change, update the page text as well as the engine.

---

## 2. Booking Engine Pricing

### 2.1 Rate plans (base, per night, double occupancy)
| Room | CP (breakfast) | MAP (+ dinner) | AP (all meals) | MAPAI |
|---|---|---|---|---|
| Kutchi AC Cottage | ₹6,250 | ₹7,550 | ₹8,550 | — |
| Deluxe AC Cottage | ₹5,536 | ₹6,836 | ₹7,836 | — |
| Deluxe Air-Cool Tent | — | — | — | ₹7,499 |
| Non-AC Tent | — | — | — | ₹6,500 |

### 2.2 Peak dates (White Rann Camp, preloaded)
Christmas/New Year 19 Dec 2026 – 4 Jan 2027 · Uttarayan 13–15 Jan 2027 · Full moon 21–23 Jan 2027. Peak rates are ₹8,450 (Deluxe Air-Cool) and ₹7,499 (Non-AC).

### 2.3 Tax
GST in slabs **per night**: 5% up to ₹7,500, 18% above. Add-ons are taxed at 5%. **The owner's accountant must confirm this.**

### 2.4 Payment and cancellation (`config.php`)
* Guest chooses: pay in full, pay 50% now, or pay at the property (only when arriving 14+ days out; room held 48 h).
* Razorpay (instant) or UPI QR (confirmed by staff in admin; QR valid for 45 min).
* Cancellation: free 30+ days before arrival, 75% charged at 21–29 days, 100% under 21 days.
* Limits: 21 nights, 5 rooms online, 2 h minimum notice, 20-minute hold while paying by card. Children under 6 stay free.

### 2.5 Add-ons (resort)
Airport or station transfer ₹1,200/booking · Gala dinner with folk music ₹1,500/person · Candlelight dinner ₹2,500 · Birding jeep ₹3,000 · Extra bed ₹1,500/night. The camp has its own (e.g. White Rann permit and jeep). See `seed.sql`.

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
1. Resort cottage rates (the seed came from an earlier season's tariff; Deluxe is currently cheaper than Kutchi).
2. GST slabs.
3. Cancellation ladder and pay-at-property rules.
4. Diwali and Christmas supplements for the resort.
