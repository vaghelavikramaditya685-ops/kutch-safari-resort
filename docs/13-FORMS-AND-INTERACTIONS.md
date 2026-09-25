# Forms and Interactions

## 1. Booking — PHP engine
Direct booking is handled by `booking-engine/`, not the React site.

**Guest flow** (`/book/?property=…`, `assets/engine.js` → `api/*.php`):
1. Dates, guests and rooms → `api/availability.php`. Sold-out dates suggest the next free ones.
2. Choose room and rate plan (CP/MAP/AP or MAPAI) → `api/quote.php`.
3. Add extras (transfers, gala dinner, extra bed…).
4. Guest details → `api/book.php` (holds inventory).
5. Payment:
   * Razorpay → `api/payment-create.php` → Checkout → `api/payment-verify.php`, with `api/webhook-razorpay.php` as a backup confirmation.
   * UPI QR (drawn with qrious) → booking waits until staff click "Money received" in admin.
   * Pay at property → held 48 h, reservations calls to confirm.
6. Confirmation email (`lib/mail.php`, PHP `mail()` or SMTP), BCC to the office.

**Manage booking:** `/book/manage.php` → `api/booking-lookup.php` / `api/booking-cancel.php` (reference + phone). The refund follows the cancellation ladder.

**Enquiry endpoint:** `api/enquiry.php` stores enquiries in the engine's `enquiries` table, which staff see in Admin → Enquiries.

**Admin** (`/book/admin/`): bookings list (with a UPI "waiting to be checked" box), booking detail, availability calendar, rates, enquiries, CSV export. Logins are created with `php bin/setup.php --admin …`. Login attempts are rate-limited.

**Protections:** per-IP rate limits (stored in `audit_log`), CORS allow-list, row locking during booking (MySQL `FOR UPDATE`), Razorpay HMAC checks, an audit log of every money or inventory action, and `fail_closed` for Stayflexi.

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
