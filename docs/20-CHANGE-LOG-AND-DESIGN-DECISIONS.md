# Change Log and Design Decisions

## Change log

### 26 Sep 2026: change pricing, money by payment choice, admin polish (not yet committed)
* **Changing a booking is priced as a difference** (`modification_delta()`): new total = old total + added − taken off ± changed. Everything the guest keeps keeps exactly its booked amount (a share per night where special prices differ). New nights, rooms, occupancy and extras are priced at today's prices. A percent discount code moves only with rooms added or taken off. The preview lists each item under **Added / Taken off / Changed** with its amount and writes out the sum. The breakdown is saved with the change and shown on the booking page afterwards. Before, the whole cart was re-priced: adding a ₹7,450 room to a ₹22,000 booking gave ₹30,050.
* **Money follows how the guest pays** (`booking_money()`): Due now (to reach 100%, or 50% on the 50% plan), Due before arrival (the rest on the 50% plan, as guests are told), or Refund due. Used in the change preview, the booking page and its Collect form, the Availability panel, the check-status page (`due_now`, `due_later`) and the receipt.
* **`amount_paid` = payments − refunds everywhere:** online settlement and UPI confirmation used to ignore earlier refunds.
* **No browser pop-ups:** confirmations are an on-page box (`data-confirm`), red for cancelling.
* **Arrival time is a scroll wheel** (hour · minutes · AM/PM). Nothing is saved unless the guest turns it.
* **`/book/admin` works as typed:** redirects to `/book/admin/` and then `login.php`, so the address fills itself in.
* **Availability:** "Change rooms on sale" removed. Clicking a guest in either view opens the side panel, now with where they are in the stay (arrives in N days / staying now / leaves today), check-in and check-out, **which car** (Sedan / Ertiga / Innova), guests per room, and money by payment choice.
* **Test bookings removed** from the real database at the owner's request (KSR-2WQG3X, KSR-3E95RT, KSR-RKJL4F with their rooms, extras and payments). 0 bookings now.
* **`bin/test-changes.php`**: 17 checks on a temporary copy of the database.
* **Docs:** all 20 updated; 10 new (21–30) on pricing, changes and money, availability, admin, guest flow, payments, Stayflexi, database rules, lessons learned, and testing/go-live.

### Booking engine: mixed cottages, receipts, check status (not yet committed)
* **White Rann Camp is switched off in the engine** for now (`properties.active = 0`, also in `seed.sql`). Its tents, prices and peak dates are kept; the booking page, availability, pricing and terms for it are refused, and it is hidden from the admin. To sell it again, set `active` back to 1.
* **Changing a booking keeps what was agreed:** nights the guest already has keep their booked price (even if the tariff or a special price has changed since), extras already on the booking keep their booked unit price, and the discount code stays on even if it has since expired or been used up (`pricing_locks()`, `keep_coupon`). Only new nights, cottages, extra beds and extras are priced at today's rates. Seven "only what changed changes" tests pass. _(Superseded on 26 Sep 2026 by the difference method above; the locks are still used to price new nights.)_
* **Availability by guest (default):** one row per guest in arrival order, bar from check-in to check-out, full page width. Clicking a guest opens a side panel with contact, times, rooms and occupancy, extras, airport pickup, and total/paid/due, plus Change booking, Add airport pickup, Collect payment, Open booking, Call and WhatsApp. "By cottage" is the other tab.
* **Availability is one calendar** (`admin/calendar.php`): rows are cottages (Kutchi 1–12, Deluxe 1–8…), columns are days, and each day is a full 24 hours. Every booked room is a bar from check-in (12:00) to check-out (10:00), so turnovers share a row. Each room type's bookings are laid into non-overlapping rows on the server; extra bookings go into a red "Overbooked" row. It shows free counts per night, arrivals (↓) and departures (↑) per day, hatched "not on sale" nights and a "now" line. Hover a bar for guest, code, room and occupancy, times and money; hover a day for all its check-outs, check-ins and stays; click a bar to open the booking. The rooms-on-sale grid was under "Change rooms on sale" (removed 26 Sep 2026).
* **Special prices** (`admin/rates.php`, menu "Special prices"): pick nights, tick cottages, enter a price. The normal price is never changed, and each night of a stay is priced on its own. Guard rails: at least ₹500, 25%–400% of normal, a confirm tick below 60% or above 150%, no past dates, a check step before saving. Saved prices are listed as date ranges with Remove.
* **Similar rooms together** everywhere rooms are listed: grouped by cottage type in the site's order, then room number (`get_booking()`, admin pages, receipt, email, check-status page, booking summary).
* **Receipts are never stored:** every Receipt (PDF) is generated on the spot from the booking's current details.
* **Bookings list reads like the day at the hotel:** Staying now (earliest check-out first) → Coming up (soonest arrival first) → Cancelled / not paid → Finished (most recent first). It is sorted every time it opens. Finished and cancelled bookings older than `admin.list_clear_days` (15) drop off the list but are **never deleted**: "Show older bookings", a search or a filter brings them back. They are the hotel's payment and GST records.
* **Owner can change a booking.** Admin → open booking → **Change this booking** (`admin/edit.php`): dates, each room's cottage and occupancy, add or remove rooms, extras. **Check price** (`quote_modification()`) shows what changes and what to collect or give back, with the booking's own rooms left out of availability (`availability_ignore_booking()`). _(Since 26 Sep 2026 it is priced as a difference, see above.)_ **Save changes** (`modify_booking()`) rewrites the rooms and extras, logs old → new, and re-sends to Stayflexi. The booking page then offers **Collect the balance** or **Record a refund** (`record_offline_refund()`); `amount_paid` is payments minus refunds (`refresh_amount_paid()`). Guests see the new details, "Updated by the resort", and balance or refund due on the check-status page and receipt. The desk may change a stay that has already started, and isn't bound by the online room limit.
* **Admin booking page** keeps the table, now with Guests (Single/Double/Triple) and Extra bed columns per room, amounts as quoted (GST included), refund due, and abandoned online payment attempts listed apart from money received. The bookings list shows "refund ₹X" when money is owed back.
* **Admin always asks for the password.** The sign-in ends when the browser closes (was 8 hours), counts only in the tab it was made in (a new tab or window asks again, via a sessionStorage mark set after sign-in), and ends after `admin.idle_minutes` (10) without use.
* **Fully paid means nothing to collect.** The admin "Collect the balance" form only appears when money is still due, and the server refuses payments on cancelled or fully paid bookings, or above the balance. Test payments read "Test payment — no money taken".
* **Stayflexi updates by itself.** The admin booking page no longer has "Set status" or "Send to Stayflexi". A booking is sent when it is paid, and cancelled in Stayflexi when the admin cancels it. A failed Stayflexi cancel is now saved on the booking and retried hourly by `bin/retry-failed-sync.php` (before, it was only logged). That job now re-sends only confirmed (paid) bookings, never unpaid ones.
* **Pay 50% or in full.** "Pay at the property" is off. Unpaid bookings stop holding rooms once the payment window closes (`rooms_booked()`), and show as "not paid" on the check-status page and receipt. Before this, an abandoned checkout blocked its rooms forever.
* **Razorpay:** the live Key ID is in `booking-engine/config.local.php` (git-ignored). Razorpay stays off until the Key Secret is added there.
* **Only the admin can cancel.** Guests cannot cancel online (`api/booking-cancel.php` now refuses). The check-status page shows the charges and "call or WhatsApp us with your booking code". In the admin panel, **Bookings → Cancel a booking** takes the booking code and opens that booking at its cancel form, which shows the charge if cancelled today; card payments are refunded through Razorpay automatically when it is connected. The terms say cancellations are made by the reservations team.
* **Different cottage per room.** With several rooms, each cottage card has one **Select** button that opens its room numbers (Room 1 · Double, Room 2 · Single…) to tick. A room already given to another cottage is hidden until it is unticked there. A "N of M rooms chosen · Continue" bar sits under the list. The server sends one booking line per room and checks availability **across** lines (two Deluxe rooms need two free Deluxe).
* **Receipt and terms as PDF, in a new tab.** `lib/pdf.php` is a small built-in PDF writer (no library to install). `lib/documents.php` builds the receipt (guest, stay, rooms with occupancy, extras, totals, payments, this booking's cancellation dates, terms) and the terms on their own. `document.php` shows either one in the browser tab with PDF.js, with Print and Save as PDF, so it never downloads. Raw PDFs: `receipt.php?ref=&token=` (guest token or signed-in staff) and `terms.php?property=`.
* **Terms** are written only from the settings (check-in/out, cancellation ladder, payment options, GST, online limits) plus `config.php` → `terms.house_rules`, where the property adds its own rules.
* **"Already booked? Check status"** on the website (top bar, mobile menu, home hero, footer) and the booking page header, going to `manage.php`: find by code + mobile/email, then Receipt (PDF), Terms (PDF), Call. A `?ref=&token=` link opens it with no typing.
* **Refresh-proof confirmation:** the booking page remembers the guest's last booking on their device and shows "You booked KSR-… · Receipt (PDF) · Check status" until the stay is over.
* **Payment step in test mode** shows the Razorpay and UPI QR options (greyed out as "not connected yet" until their keys / UPI id are set) with the I've paid (test) button.
* **Fixed:** cancellation dates were labelled with the day a charge period ends instead of when it starts, and past periods still showed. `cancellation_schedule()` now returns `from`/`to`/`past`, used by every screen and the receipt. The check-status page now shows GST-inclusive amounts like the tariff.

### Booking engine: tariff, occupancy, extras, finish (after 25 Sep 2026, not yet committed)
* **2026–27 resort tariff:** breakfast plan only. Deluxe ₹5,500 / ₹6,500, Kutchi ₹6,500 / ₹7,450 (single / double), extra bed ₹1,500, **GST included** (`prices_include_tax`). MAP/AP plans removed.
* **Per-room occupancy:** the guest picks Single / Double / Triple for each room. New `price_rooms()` in `lib/inventory.php` is the single pricing function for both the room list and checkout. New columns: `rate_plans.single_price`, `addons.min_quantity`; `booking_rooms.rate_plan_name` widened to 255.
* **Extras:** candlelight dinner ₹3,000 per person; gala dinner ₹1,500 per person, minimum 10; airport transfers by vehicle (Sedan / Ertiga / Innova), shown as one Airport transfer card with a counter per car (any add-ons named "Group — Choice" group this way). The extra-bed add-on was removed; Triple replaces it. The per-room add-on picker code stays for any future "one per room" extra.
* **Fixed:** guests were spread over room types instead of rooms (4 adults in 2 rooms were charged as 4 per room); check-out was set to check-in on Indian time; the summary didn't add up; the sidebar overlapped while scrolling.
* **UX:** the engine opens with dates filled in and rooms loaded; the search re-runs on any change; each step scrolls back up so the dates, rooms and guests stay visible; clearer buttons; matte/smooth finish on the site and engine.

### 25 Sep 2026: booking engine replaced
* **Removed** the React/Express booking engine: `client/src/booking/`, `client/src/pages/BookingPage.tsx`, `shared/booking/`, `server/booking/`, the Vite `bookingApiDev` plugin, and the `@shared` alias.
* **Added** `booking-engine/`, a standalone PHP 8 + MySQL engine (search → room → extras → pay, Razorpay + UPI QR, admin panel, guest self-service cancellation, Stayflexi bridge, per-night GST slabs).
* **Wired it in:**
  * `client/src/lib/booking.ts`: `BOOKING_URL` (`VITE_BOOKING_URL` or `/book/`) and `bookingUrl()`.
  * Every Book Now link (Navbar ×2, Home ×3, Stay) → engine.
  * `/booking`, `/book`, `/book/*` → `BookingRedirect` (forwards, or shows call/WhatsApp if the engine isn't hosted there).
  * Vite dev proxy `/book/` → `localhost:8080`. New scripts `pnpm dev:book` and `pnpm setup:book`.
  * Engine `allowed_origins` += kutchsafaribhuj.in, www, the Vercel preview, localhost:3000.
  * Local `booking-engine/config.local.php` (SQLite, git-ignored).
  * `.gitignore`: root `data/` → `/data/`. Engine keeps `data/.htaccess` tracked.
  * `.vercelignore` and `.prettierignore` exclude `booking-engine/`.
* All 20 docs rewritten to match the code.

### Earlier (before 25 Sep 2026)
* Redesign to "Sundown Terracotta" with the owner's beige `#f8f5e2`. Multi-page structure (Stay, Experiences, Our Journey, Dining, Gallery, Plan Your Visit, Packages).
* The Weddings page was removed. `/experiences` was added.
* Rooms.tsx was replaced by Stay.tsx (masonry kept at the owner's request).
* A React booking engine was built (now removed, see above).

---

## Owner directives (Mike Vaghela)
1. **"Changes that are told to you in the PDF. Don't change anything else."** Stick to the provided copy and layout.
2. **"Integrate the beige colour into the entire website."** `#f8f5e2`, taken from the logo.
3. **"Where the lake meets the desert, it should be white."** Drives the hero copy and imagery.
4. **"Keep the old masonry style for cottages."** Stay uses a two-column exterior/interior masonry.
5. Keep the offline enquiry route next to online booking.

## Technical decisions

### Why the booking engine is PHP and separate
The engine was built for a cPanel host, where PHP + MySQL run with nothing to configure and the owner can edit files directly. The previous React engine stored reservations in a JSON file, which can't persist on Vercel. Keeping the engine as its own app at `/book/`:
* lets it serve both properties (and a future separate White Rann Camp site) from one install;
* keeps payment keys and guest data off the static front-end host;
* means the React site only needs a link (`bookingUrl()`), with no shared code to keep in sync.

**Trade-off:** two stacks and two styles, and prices shown on marketing pages are separate text from the prices charged.

### Why a redirect route instead of removing `/booking`
Old links, bookmarks and ads may point at `/booking`. `BookingRedirect` forwards them. If the engine isn't deployed on that host, it shows the reservations phone and WhatsApp instead of a redirect loop.

### Why `VITE_BOOKING_URL`
The site is on Vercel and the engine needs a PHP host. One build-time variable lets the engine live on the same domain (`/book/`, the default) or anywhere else, without code changes.

### React + Vite + wouter
Fast builds. wouter (~1.5 KB) is enough for flat routes.

### Tailwind v4 (config-less)
Theme lives in CSS (`@theme inline` + `:root` tokens).

### Logo `mix-blend-darken`
The logo is a JPG with a white background. Blending on the beige hides the white without a transparent PNG.

### Why a change is priced as a difference
The owner reads a change the way the desk does: "this was added, that was taken off". Re-pricing the whole booking moved amounts the guest had already agreed to (GST split, single discount, special prices that have ended), so the new total couldn't be explained line by line. Pricing only the difference makes every rupee traceable to an item.

### Why the 50% balance says "before arrival"
The payment option shown to guests says "Balance due 30 days before arrival". Screens and receipts use the same words so the guest and the desk see the same thing. Changing when it is due means changing that note and those labels together.

### Why no browser pop-ups
The owner found them jarring ("shit like this… the browser pop up"). An on-page box matches the design, can use a red button for destructive actions, and says exactly what will happen.

### Express + JSON enquiries
`POST /api/contact` writes `data/enquiries.json`. This only works on a host with a disk. The engine's `api/enquiry.php` (database-backed, visible in admin) is the better target for the Home form.
