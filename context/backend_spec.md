# Backend Spec
> Purpose: engine modules and business rules | Mode: A | Confidence: High | Related: [../docs/21-PRICING-LOGIC-DEEP-DIVE.md](../docs/21-PRICING-LOGIC-DEEP-DIVE.md), [../docs/22-BOOKING-CHANGES-AND-MONEY.md](../docs/22-BOOKING-CHANGES-AND-MONEY.md), [../docs/23-AVAILABILITY-AND-INVENTORY-LOGIC.md](../docs/23-AVAILABILITY-AND-INVENTORY-LOGIC.md)

## Modules (`backend/booking-engine/lib/`) [CODE]
| File | Responsibility | Key functions |
|---|---|---|
| db.php | PDO, helpers, settings, audit, **input checks** | `db`, `q`, `q1`, `insert`, `update`, `audit`, `LIMITS`, `too_long`, `valid_phone`, `valid_arrival_time`, `valid_date` |
| inventory.php | availability + pricing | `price_rooms` (single pricing function), `price_rate_plan`, `tax_split`, `rooms_booked`, `rooms_free_for_stay`, `search_availability`, `pricing_locks` |
| booking.php | quote, create, change, cancel, money | `quote_cart`, `create_booking`, `quote_modification`, `modification_delta`, `modify_booking`, `booking_money`, `cancel_booking`, `validate_dates(_staff)` |
| payment.php | Razorpay, UPI, test, desk payments/refunds | `settle_payment`, `upi_mark_received`, `record_offline_payment` (confirms a pending booking once enough is paid), `record_offline_refund`, `refresh_amount_paid` |
| channel.php | Stayflexi | `channel_push_booking`, `channel_cancel_booking`, `channel_verify_still_available` |
| mail.php / pdf.php / documents.php | emails, PDF writer, receipt & terms | `send_booking_confirmation`, `receipt_pdf`, `terms_pdf` |

## Business rules [CODE `config.php` unless noted]
* Price per room-night = double price (special price if set) − single discount (Single) + ₹1,500 extra bed (Triple); GST included for the resort, slab 5% ≤ ₹7,500 else 18% on the pre-tax value [CODE `price_rooms`, `tax_split`].
* Change of a booking = old total + added − taken off ± changed; kept items keep booked amounts [CODE `modification_delta`].
* Money: `due_now` (to reach 100% or 50%), `later` (50% plan, before arrival), `refund` [CODE `booking_money`].
* Holding rooms: confirmed always; pending while ≤ 45 min old, or money/UPI check pending [CODE `rooms_booked`].
* Limits: 21 nights, 5 rooms online, 2 hours' notice, 2 years ahead, 1–50 of an extra, gala ≥ 10.
* Cancellation ladder: 30+ days free, 21–29 days 75%, < 21 days 100%.

## Validation (29 Sep 2026)
Every guest/enquiry/admin input checked in the backend (never trusting the page): lengths per `LIMITS`, phone 7–15 digits, arrival "h:mm AM/PM", real dates, extras quantities, listed methods/statuses. Errors are plain sentences; guest-detail errors carry `field:"guest"` [CODE]. See [../chaos/REPORT.md](../chaos/REPORT.md).

## Background jobs [CODE `bin/`]
* `sync-inventory.php` every 10 min (Stayflexi → `inventory`), `retry-failed-sync.php` hourly — only when Stayflexi is on.
* `test-changes.php`, `check-system.php` — tests on a temporary copy of the database.

## Errors and logging
Exceptions in APIs become JSON errors (details only when `debug` is on) [CODE `api/_init.php`]; money/inventory actions in `audit_log`; mail failures logged [CODE].
