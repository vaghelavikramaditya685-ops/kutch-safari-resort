# User Stories
> Purpose: who wants what, and how we know it's done | Mode: A | Confidence: High (stories match existing behaviour) | Related: [user_flows.md](user_flows.md), [features.md](features.md)

## Personas
* **Asha, the guest** — books from a phone, may come with family (2–3 rooms), wants the price clear and a receipt.
* **Manvir, the owner / front desk** — runs the day from the admin panel; takes calls, cash and UPI; changes bookings.

## Guest
| Story | Acceptance criteria | Evidence |
|---|---|---|
| As a guest, I want to see which cottages are free for my dates so I can pick one. | Dates are pre-filled; changing dates, rooms or guests re-searches; sold-out suggests next dates; stays in the past, > 21 nights or > 2 years ahead are refused with a message. | [CODE `api/availability.php`, `validate_dates`] |
| As a guest with a family, I want different cottages and Single/Double/Triple per room. | Each room has its own occupancy; with several rooms a Select button assigns room numbers; a room ticked in one cottage disappears from others. | [CODE `assets/engine.js assignPanel`] |
| As a guest, I want to add a car or dinner. | Transfers shown as one card with a counter per car; gala dinner needs ≥ 10; quantities 1–50. | [CODE `quote_cart`] |
| As a guest, I want to pay 50% now or in full. | Both options priced; balance shown as "due before arrival". | [CODE `payment_modes`, `booking_money`] |
| As a guest, I want to know my booking later. | "Already booked? Check status" finds it by code + mobile/email or private link; shows due / refund; receipt PDF opens in the tab. | [CODE `manage.php`, `document.php`] |
| As a guest, I want my typing mistakes caught without losing what I typed. | Bad phone/email caught on the page; server refusal keeps me on the details step with my input. | [CODE `engine.js createBooking`] |

## Owner / desk
| Story | Acceptance criteria | Evidence |
|---|---|---|
| As the owner, I want today's picture at a glance. | Bookings list: Staying now → Coming up → Cancelled/not paid → Finished; counts for arriving / in house / awaiting payment. | [CODE `admin/index.php`] |
| As the owner, I want to change a booking and see exactly what the price change is. | Preview lists Added / Taken off / Changed with amounts; new total = old + added − taken off; kept items keep their booked price. | [CODE `modification_delta`, `admin/edit.php`] |
| As the owner, I want to record cash/UPI and refunds. | Amount capped at the balance; listed methods only; a pending booking is confirmed once enough is paid. | [CODE `admin/booking.php`, `record_offline_payment`] |
| As the owner, I want to cancel on the guest's behalf. | Cancel by booking code; today's charge shown; rooms go back on sale; card refund via Razorpay when connected. | [CODE `cancel_booking`] |
| As the owner, I want special prices for certain dates. | Pick nights and cottages, check, save; guard rails on price; normal price untouched. | [CODE `admin/rates.php`] |
| As the owner, I want to see who is where, by cottage. | Availability tape chart by cottage; click a booking for check-in/out, ETA, rooms, transfers, money. | [CODE `admin/calendar.php`] |
| As the owner, I want the admin panel to always ask for the password. | New tab/window, browser restart or 10 minutes idle → sign in again; credentials sent as SHA-256. | [CODE `admin/_auth.php`, `admin/login.php`] |
