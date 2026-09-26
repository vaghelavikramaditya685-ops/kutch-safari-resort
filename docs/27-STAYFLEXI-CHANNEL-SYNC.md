# Stayflexi (Channel Manager) Sync

_Written 26 Sep 2026. Code: `lib/channel.php`, `bin/sync-inventory.php`, `bin/retry-failed-sync.php`, `bin/map-stayflexi.php`, `bin/check-stayflexi.php`. **Status: not connected** (`stayflexi.enabled = false`, no API key)._

## 1. Why it matters
Stayflexi already sells the cottages on MakeMyTrip, Booking.com and others. If this website sells a room without telling Stayflexi, the same room can be sold twice. There are only two safe ways to run:
* **A. Connected:** availability is pulled from Stayflexi, and every paid booking is pushed back to it. Stayflexi is the single source of truth.
* **B. Not connected (now):** the engine uses its own numbers. That is safe only if the website's rooms and the OTAs' rooms are kept **separate** in Stayflexi (e.g. keep 4 cottages back for direct bookings). Selling the same rooms in both places and reconciling by hand is not safe.

## 2. When the engine talks to Stayflexi (once connected)
| Event | Call | Where |
|---|---|---|
| Just before a booking is saved | `channel_verify_still_available()`. With `fail_closed = true` the booking is **refused** if Stayflexi can't confirm the room is free | `create_booking()` |
| Booking paid (Razorpay, UPI confirmed, test) | `channel_push_booking()` | `settle_payment()`, `upi_mark_received()` |
| Desk changes a booking | cancel the old reservation, push the new one | `modify_booking()` |
| Admin cancels | `channel_cancel_booking()` | `cancel_booking()` |
| Every 10 min (cron) | pull availability into `inventory` | `bin/sync-inventory.php` |
| Hourly (cron) | re-send confirmed bookings not yet synced, and cancellations that failed | `bin/retry-failed-sync.php` |

* Unpaid bookings are **never** pushed. Only confirmed ones are.
* A failed push or cancel is written to `bookings.sf_sync_error` and shown in red on the admin booking page. The hourly job retries it by itself. **Fixed on this project:** a failed cancel used to be only logged, so the room stayed closed on the OTAs.
* The admin buttons "Set status" and "Send to Stayflexi" were removed at the owner's request. Everything happens by itself.

## 3. Connecting it
1. Ask Stayflexi support for API access for a custom booking engine (API key/secret, hotel id).
2. **Confirm the endpoint paths.** The ones in `lib/channel.php` (e.g. `/core/api/v1/reservations/{id}/cancel`) are educated guesses and have **not** been checked against Stayflexi's documentation.
3. Put the key in `config.local.php` → `stayflexi`, then map the ids: `php bin/map-stayflexi.php property kutch-safari-resort HOTEL_ID`, `… room kutchi-ac-cottage ROOM_TYPE_ID`, `… plan 1 RATE_PLAN_ID`, `… list`.
4. `php bin/check-stayflexi.php`, then set `enabled = true` and add the two cron jobs (doc 15).

## 4. Rooms on sale without Stayflexi
Stayflexi fills `inventory.rooms_open`. The admin grid to edit those numbers by hand ("Change rooms on sale") was removed on 26 Sep 2026. Until Stayflexi is connected, every cottage type is sold up to `room_types.total_rooms`. To keep cottages back, lower `total_rooms` or add `inventory` rows, or ask for the grid back.
