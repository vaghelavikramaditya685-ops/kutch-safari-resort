# Forms and Interactions

## 1. Booking — PHP engine
Direct booking is handled by `booking-engine/`, not the React site.

**Guest flow** (`/book/?property=…`, `assets/engine.js` → `api/*.php`; full detail in `25-GUEST-BOOKING-FLOW-INTERNALS.md`):
1. Dates (pre-filled), number of rooms, **Single / Double / Triple for each room** → `api/availability.php`. Sold-out dates suggest the next free ones.
2. Choose cottages. One room: pick a card. Several rooms: **Select** on a cottage opens its room numbers to tick; a room ticked in one cottage disappears from the others. One plan (room with breakfast) → `api/quote.php`.
3. Extras: airport transfer (Sedan / Ertiga / Innova, one card with a counter per car), gala dinner (minimum 10), candlelight dinner, birding jeep. The extra bed is chosen by picking **Triple**.
4. Guest details (optional in sample mode), **approximate arrival time on a scroll wheel** (nothing is sent unless the guest turns it), notes, **pay 50% or in full** → `api/book.php`.
5. Payment:
   * Razorpay → `api/payment-create.php` → Checkout → `api/payment-verify.php`, with `api/webhook-razorpay.php` as a backup confirmation.
   * UPI QR (drawn with qrious) → the booking waits until staff click "Money received" in admin.
   * Test mode: **I've paid (test)** → `api/payment-test.php` (no money; turn off before launch).
   * "Pay at the property" is switched off.
6. Confirmation with Receipt (PDF), Check status, Call. The page remembers the last booking on the device. Confirmation email (`lib/mail.php`), BCC to the office, once mail is set up.

**Check status:** `/book/manage.php` → `api/booking-lookup.php` (code + mobile or email, or a `?ref=&token=` link). Shows the stay, due now / due before arrival or refund due, "Updated by the resort", and Receipt, Terms, Call. **Guests cannot cancel online** (`api/booking-cancel.php` always refuses); they call or WhatsApp, and the admin cancels.

**Enquiry endpoint:** `api/enquiry.php` stores enquiries in the engine's `enquiries` table, which staff see in Admin → Enquiries.

**Admin** (`/book/admin/`, see `24-ADMIN-PANEL-GUIDE.md`): bookings list (staying now → coming up → cancelled → finished, with a UPI "waiting to be checked" box and a "Cancel a booking" box), booking detail, **Change this booking** (priced as a difference, doc 22), Availability (tape chart and guest side panel), Special prices, enquiries, CSV export. Logins are created with `php bin/setup.php --admin USERNAME NAME PASSWORD`. Login attempts are rate-limited. Every "are you sure?" is an on-page box, not a browser pop-up.

**Protections:** per-IP rate limits (stored in `audit_log`: availability 120/min, quote 90/min, book 12 per 5 min, lookup 15 per 5 min, payments 30 per 5 min, enquiry 8 per 10 min), CORS allow-list, row locking during booking (MySQL `FOR UPDATE`), Razorpay HMAC checks, an audit log of every money or inventory action, and `fail_closed` for Stayflexi.

## 2. Site links into the engine
Every Book Now / Check Availability button uses `bookingUrl()` with a plain `<a>`: Navbar (desktop + mobile), Home hero, Home room cards, and Stay's `RoomTemplate`.

## 3. Home contact form (still a mock)
`Home.tsx` → `ContactSection`:
```ts
const handleSubmit = (e: any) => {
  e.preventDefault();
  setSubmitting(true);
  setTimeout(() => { setSubmitting(false); setSubmitted(true); toast.success("Message sent successfully!"); … }, 1500);
};
```
Nothing is sent. The inputs are write-only (no `value`), so the "reset" doesn't clear them.

Two backends are available. Pick one:
* **Recommended:** POST to the engine's `api/enquiry.php` (at `BOOKING_URL + "api/enquiry.php"`). Enquiries then appear in the same admin panel as bookings. It takes JSON `{ name*, phone*, email, property, check_in, check_out, guests, interest, message, website }` (* required; `website` is a honeypot, leave it empty). It emails the office and returns `{ ok, id, message }`. Rate limit: 8 per 10 min per IP. The form already collects name, email, phone (required) and message, which map straight across.
* Express `POST /api/contact` → `data/enquiries.json`. This doesn't work on Vercel (no persistent disk), and `api/contact.ts` only logs.

## 4. Other interactions
* WhatsApp: `https://wa.me/919925238599` (Home contact, BookingRedirect fallback), with a pre-filled message on Destination and RannUtsavPackage.
* `tel:+919925238599`, `mailto:kutchsafaribhuj@yahoo.com` (Navbar top bar, Footer).
* Stay lightbox: click an image to open, click to close.
* Mobile menu toggle.
* Toasts: `sonner` `<Toaster />` in `App.tsx`.
