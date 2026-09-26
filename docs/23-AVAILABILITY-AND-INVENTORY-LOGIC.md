# Availability and Inventory Logic

_Written 26 Sep 2026. How the engine decides how many cottages are free, what "holds" a room, and how the admin Availability calendar is built. Code: `lib/inventory.php`, `admin/calendar.php`._

## 1. The engine sells room *types*, not named cottages
The database knows "Kutchi AC Cottage: 12" and "Deluxe AC Cottage: 8" (`room_types.total_rooms`). A booking takes **N rooms of a type**. It never takes "Cottage 7". The desk decides which physical cottage a guest gets on arrival.

"Room 1 / Room 2" in a booking are **positions inside that booking** (used for occupancy and per-room extras), not cottage numbers.

## 2. Free rooms on a night
```
rooms_free(type, night) = rooms_open − rooms_booked − rooms_held      (never below 0)
rooms_free_for_stay     = the smallest rooms_free over every night of the stay
```
* **`rooms_open`**: how many the resort sells that night. It is `room_types.total_rooms` unless there is a row in `inventory (room_type_id, stay_date, rooms_open)`. Those rows come from Stayflexi (`bin/sync-inventory.php`, once connected). The admin grid for editing them ("Change rooms on sale") **was removed on 26 Sep 2026** at the owner's request. To close cottages by hand now, put rows in `inventory`, or ask for the grid back.
* **`rooms_booked`**: rooms on bookings that cover that night (`check_in <= night < check_out`) and still count (below).
* **`rooms_held`**: rows in `holds` that haven't expired. The current checkout **does not create holds**. An unpaid (`pending`) booking plays that role instead (below), so this is normally 0.

## 3. Which bookings hold rooms (`rooms_booked()`)
| Booking | Holds its rooms? |
|---|---|
| `confirmed` (paid, or recorded by the desk) | Always |
| `pending`, some money paid (`amount_paid > 0`) | Yes |
| `pending`, a UPI payment waiting for the desk to confirm | Yes |
| `pending`, created within the payment window | Yes. The window is the longer of `rules.hold_minutes` (20) and `upi.hold_minutes` (45) |
| `pending`, window closed, nothing paid | **No.** It has "lapsed" (`booking_lapsed()`); shown as "not paid" |
| `cancelled` | No |

> Why: before this, an abandoned checkout blocked its rooms forever. Now an unpaid booking frees its rooms 45 minutes after it was made.

## 4. Several rooms, mixed types
With several rooms the guest can pick a different cottage for each room. `search_availability()` lists a type if it can take **at least one** of the rooms (at least 1 free and big enough for that room's guests; `fits` says which). `quote_cart()` and `create_booking()` then check availability **cumulatively per type**: two Deluxe rooms need two free Deluxe. `create_booking()` re-checks inside a transaction with row locks (`FOR UPDATE` on MySQL), so two guests can't both take the last cottage.

## 5. Changing a booking doesn't collide with itself
While the desk changes a booking, `availability_ignore_booking($id)` leaves that booking's own rooms out of `rooms_booked()`. Moving a stay by a night therefore isn't refused because of its own rooms. It is set and cleared around `quote_modification()` and `modify_booking()`.

## 6. Other checks
* Seasons: `properties.season_start` / `season_end` (`property_open_between()`). The camp is seasonal and is also switched off (`properties.active = 0`).
* Online rules (`config.php → rules`): up to 21 nights, up to 5 rooms online (more goes to enquiry), at least 2 hours' notice. The desk (`staff_edit`) may exceed the room limit and change a stay already under way (`validate_dates_staff()`).
* Special prices can close a plan on a night (`rates.closed`) or set a minimum stay (`rates.min_stay`).
* Sold out: `next_available_dates()` suggests the next dates that work.

## 7. The Availability calendar (`admin/calendar.php`)
A tape chart, full page width. Each day is a full 24 hours. A stay is a bar from **check-in time (12:00) on arrival day to check-out time (10:00) on departure day** (`properties.check_in_time` / `check_out_time`), so a 10:00 departure and a 12:00 arrival sit in one row without overlapping.

Two views:
* **By guest (default):** one row per booking, in arrival order.
* **By cottage:** rows per cottage type. The server lays each type's rooms into non-overlapping lanes, one per cottage the type owns (`total_rooms`). It reuses the lane that became free most recently before the stay starts, so turnovers line up. Any booking that doesn't fit goes into a red **Overbooked** row. Free counts per night, arrivals ↓ and departures ↑, and hatched "not on sale" nights are shown.

Hover a bar for the guest card; hover a day for all its check-outs, check-ins and stays. **Click a guest or bar (either view)** to open the side panel:
* Where they are today: Arrives in N days / Arrives today / Staying now (night X of Y) / Leaves today / Checked out
* Phone and email; check-in and check-out date and time; the approximate arrival time the guest chose (the scroll wheel)
* Rooms with occupancy and guests per room
* **Car:** which airport-transfer car is booked (Sedan / Ertiga / Innova, and how many), read from the extras named "Airport transfer, one way — …"
* All extras; notes
* Money: total, paid, and either Due now / Before arrival (50% plan), Fully paid, or Refund due; plus "Paying in full" or "Paying 50% now…"
* Buttons: Change booking, Add or change car, Collect, Open booking, Call, WhatsApp

Ctrl/Cmd-click on a bar still opens the booking page directly. Only active properties are listed.
