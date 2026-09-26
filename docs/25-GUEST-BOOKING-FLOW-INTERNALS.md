# Guest Booking Flow — Internals

_Written 26 Sep 2026. What happens on the guest's side, step by step, and the less obvious behaviour. Code: `booking-engine/index.php` (page shell), `assets/engine.js` (all behaviour, ~1,000 lines), `assets/engine.css`, `api/*.php`._

## 1. Getting there
Every Book Now on the React site is a plain `<a href={bookingUrl()}>` to `/book/?property=kutch-safari-resort` (`client/src/lib/booking.ts`). It is never a wouter `<Link>`, because the engine is a different app and needs a full page load. `/booking`, `/book`, `/book/*`, `/admin` and `/admin/*` on the React site go through `BookingRedirect.tsx`.

## 2. Steps
| Step | What the guest does | Code |
|---|---|---|
| 1 Rooms | Dates (pre-filled: tomorrow → day after), number of rooms, **Single/Double/Triple for each room** (`#occList`). The search re-runs on any change. | `searchAvailability()` → `api/availability.php` |
| | Pick cottages. One room: pick a card. Several rooms: each card has a **Select** button that opens its room numbers ("Room 1 · Double") to tick. A room given to one cottage is **hidden** in the others until unticked. A "N of M rooms chosen · Continue" bar sits under the list. | `roomCard()`, `assignPanel()`, `wireRoomCards()` |
| 2 Extras | Airport transfer as one card with a counter per car; gala dinner jumps 0 → 10 (minimum); candlelight dinner; birding jeep. | `goToAddons()` |
| 3 Details | "Who is coming?" (optional in sample mode), email, city, **approximate arrival time (scroll wheel)**, notes, and pay 50% or in full. The cancellation dates and a terms link (PDF, new tab) are shown. | `goToDetails()` → `api/quote.php` |
| 4 Payment | Razorpay, UPI QR and, in test mode, **I've paid (test)**. | `createBooking()` → `api/book.php`, then `payWith…()` |
| Done | Confirmation with Receipt (PDF), Check status, Call. | `renderConfirmed()` |

Each step scrolls back up (`showStepTop()`) so the dates, rooms and guests stay in view. The price summary on the side comes from `quote.php` (grouped by cottage type, GST-inclusive for the resort).

## 3. Arrival time scroll wheel
Three wheels: hour (1–12), minutes (00/15/30/45) and AM/PM, with CSS scroll-snap (`.wheel*` in `engine.css`). They work with the mouse wheel, touch, clicking a number, or arrow keys.
* **Nothing is sent until the guest turns a wheel.** The wheels rest on 2:00 PM, but the field stays "Not set — scroll to choose" and sends an empty arrival time. That keeps the no-invented-data rule (doc 28).
* Once chosen it shows "Around 8:00 PM" with a **Clear** button. The value (e.g. `8:00 PM`) is saved in `bookings.arrival_time` and shown in the admin side panel.

## 4. Sample mode and test payments (turn off before launch)
* `rules.guest_details_optional = true`: name and phone may be blank. Blank is saved as blank; nothing is filled in.
* `test_payments.enabled = true`: the **I've paid (test)** button confirms the booking with no money (`api/payment-test.php`, payment provider `test`, "Test payment — no money taken").
* Razorpay and UPI show greyed "not connected yet" until their keys or UPI id are set.

## 5. After booking
* The page remembers the guest's last booking **on that device** (`localStorage` key `ksr_last_booking`). If they refresh or come back it shows "You booked KSR-… · Receipt (PDF) · Check status" until the stay is over.
* **Already booked? Check status** (`manage.php`): on the website top bar, mobile menu, home hero, footer, and the booking page header. Find by booking code + mobile or email, or open directly with `?ref=&token=`. It shows the stay, rooms, extras, total, paid, **due now / due before arrival** or **refund due**, "Updated by the resort" if the desk changed it, and Receipt / Terms / Call. There is **no online cancel** (`api/booking-cancel.php` always refuses); guests call or WhatsApp.

## 6. Receipts and terms (PDF, never downloaded)
* `lib/pdf.php` is a tiny built-in PDF writer (Helvetica, cp1252; "₹" is written as "Rs." because the base font has no rupee sign). No library to install.
* `lib/documents.php` builds the receipt (guest, stay, rooms with occupancy, extras, totals, payments and refunds, due/refund, this booking's cancellation dates, terms) and the terms on their own, from the settings plus `terms.house_rules`.
* `document.php?doc=receipt&ref=&token=` shows it **in the browser tab with PDF.js** (from cdnjs), with Print and Save as PDF, so it never downloads. Raw PDFs: `receipt.php` (guest token or signed-in staff) and `terms.php?property=`.
* **Receipts are never stored.** Each one is generated on the spot from the booking's current details, so a change by the desk shows at once.

## 7. Small things that matter
* Dates are handled as **local Indian dates** in the browser (`isoLocal()`/`todayISO()`). Using `toISOString()` once made check-out equal check-in after midnight UTC.
* Error messages are in-page boxes (`alertBox()`), never browser pop-ups.
* The header wraps below 960 px so the buttons don't overflow.
* The "Continue" bar under the room list is deliberately not sticky. It overlapped the list while scrolling.
