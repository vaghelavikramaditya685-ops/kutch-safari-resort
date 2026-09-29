# User Flows
> Purpose: journeys click by click, with the calls they make | Mode: A | Confidence: High | Related: [../docs/19-USER-FLOW-AND-UX.md](../docs/19-USER-FLOW-AND-UX.md), [api_spec.md](api_spec.md)

## 1. Guest books a stay
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

## 2. Guest checks a booking
Website **Already booked? Check status** → `manage.php` → code + mobile/email → `booking-lookup.php` → stay, money, **Receipt (PDF)**, **Terms**, **Call**; cancel = call/WhatsApp [CODE]. Nothing changes.

## 3. Desk: UPI payment arrived
Admin → Bookings → "waiting to be checked" → bank ref → **Money received** → payment paid, booking confirmed, email, Stayflexi [CODE `upi_mark_received`].

## 4. Desk: change a booking
Admin → booking → **Change this booking** → edit dates/rooms/guests/extras → **Check price** (no write; Added / Taken off / Changed + money) → **Save changes** (on-page confirm) → booking page: collect or give back [CODE `admin/edit.php`, `modify_booking`].

## 5. Desk: cancel
Bookings → **Cancel a booking** (code) → booking `#cancel` → reason → confirm → cancelled, charge by ladder, rooms back on sale, Razorpay refund when connected [CODE].

## 6. Desk: special price
Special prices → nights, cottages, price → **Check** → (tick if unusual) → **Save**; **Remove** later [CODE `admin/rates.php`].

## 7. Desk: sign in / out
`/admin` or `/book/admin` → sign-in page (hashes username + password with SHA-256) → Bookings. New tab, browser restart or 10 min idle → sign in again. **Sign out** ends the session [CODE].
