# Kutch Safari Resort & White Rann Camp — project handover

Everything in this folder: two websites and a custom booking engine, for a
family-run resort near Bhuj and its sister tented camp at Dhordo, in Kutch,
Gujarat, India.

Written in plain HTML, CSS, JavaScript and PHP with no build step, because the
destination is a cPanel shared-hosting account. Keep it that way — the owner
edits these files directly.

---

## What is here

```
kutch-safari-resort/     Main website, 8 pages, static HTML
white-rann-camp/         Second website, 6 pages, static HTML
booking-engine/          PHP + MySQL booking engine  (see its own README.md)
```

The two websites are separate sites, each landing on its own homepage, linked
only by a highlighted tab at the end of each navigation. They share a design
system but each has its own copy of `styles.css` so they can be restyled apart.

The booking engine is one PHP application serving both properties, themed per
property from the database. It lives at `/book` on the main domain and both
sites link to it.

---

## The two businesses

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

## Things that will bite you if you do not know them

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

## Current state

Working and tested: both websites, availability search, rate plans, the GST
slabs, add-ons, quoting, booking creation, guest lookup and cancellation with
refund rules, overbooking refusal, season limits, the admin panel, Razorpay
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

## House style

Plain files, no framework, no build step, no package manager. One stylesheet per
site with design tokens at the top. Comments explain *why*, not *what*. British
English in prose; the owner's guests are Indian and British-influenced spelling
reads correctly to them.

The owner is not a developer. Anything they are expected to edit — prices,
dates, copy, colours — should be findable without reading code.
