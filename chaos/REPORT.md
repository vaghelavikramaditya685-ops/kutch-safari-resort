# Chaos-monkey report (29 Sep 2026): nonsense input

Everything ran against a **throwaway copy** of the booking engine and its database (port 8090), never the real one. Snapshot branch: `heal/backup-2026-09-29`.

## 1. What was tried
| Area | Inputs | Nonsense values sent | Wrongly accepted before | After fixes |
|---|---|---|---|---|
| Room search (`api/availability.php`) | dates, rooms, occupancy, property | 13 | 1 (year 3000) | 0 |
| Price / booking cart (`api/quote.php`, `book.php`) | rooms, plans, occupancy, extras, payment choice, coupon, dates, body | 23 | 6 (extras quantities, unknown extras, broken JSON message) | 0 |
| Guest details (`book.php`) | name, phone, email, arrival time, notes, city | 13 | 9 | 0 |
| Status / payments (`booking-lookup`, `payment-test`, `payment-create`) | ref, contact, booking id, method | 5 | 0 | 0 |
| Enquiry form (`api/enquiry.php`) | name, phone, email, guests, dates, message, property, bot trap | 10 | 7 | 0 |
| Admin: payments, refunds, notes, cancel | amount, method, notes, reason | 11 | 4 (method, 3 over-long texts) | 0 |
| Admin: change a booking | guests, cottage, dates, extras quantity | 11 | 2 (year 3000, 1,000,000 cars) | 0 |
| Admin: special prices | price, dates, plans, range | 10 | 1 (confusing message) | 0 |
| Admin: enquiries | status, id, note | 3 | 2 | 0 |
| Page addresses (admin) | `id`, `property`, `start`, `days`, `q`, filters | 8 | 1 (PHP warning on `?property=abc`) | 0 |
| **Total** | | **107** | **33** | **0** |

"Correctly handled" includes values that are safely corrected rather than refused: impossible room/guest counts become valid ones (the page itself never sends them), and an unknown payment choice falls back to "pay in full", never to anything free. Before/after for every case is in `FINDINGS.md`.

## 2. Gaps found and fixed
| # | Field | Nonsense | What the app did | Severity | Fix |
|---|---|---|---|---|---|
| 1 | Extras quantity (booking + desk change) | 1,000,000 Innova | quoted **₹2.1 billion** | data corruption | refused above 50 (`rules.max_extra_quantity`), "please call us" |
| 2 | Extras quantity | −5, 0, "abc" | silently booked **1 car** | wrong data saved | refused: "Choose how many… (1 or more)" |
| 3 | Extra id | unknown / another property's | silently dropped | wrong data | refused: "no longer offered" |
| 4 | Guest name / city / note / arrival time | 5,000 / 1,000 / 200,000 / 3,000 chars | saved; **MySQL would crash** ("data too long") | crash on live | limits matching the columns, clear messages; page fields have `maxlength` |
| 5 | Phone (booking + enquiry) | "abc", "12", 60 digits | saved | wrong data | 7–15 digits (spaces, dashes, + allowed), clear example in the message |
| 6 | Arrival time | "25:99 PM" | saved | wrong data | must be "h:mm AM/PM" like the wheel, or blank |
| 7 | Dates (guest + desk) | year 3000, "2026-13-45" | year 3000 offered; bad day accepted by the pattern | wrong data | real calendar dates only; up to 2 years ahead (`rules.max_days_ahead`) |
| 8 | Enquiry form | bad email, −3/"lots" guests, "not-a-date", reversed dates, 100k message | all saved | wrong data | same checks as bookings |
| 9 | Payment method (desk) | "bitcoin" | saved | wrong data | only cash / card / bank transfer / upi |
| 10 | Staff notes, payment note, cancel reason, enquiry note | 50k–200k chars | saved; MySQL crash | crash on live | limits + messages; `maxlength` on the fields |
| 11 | Enquiry status | "hacked" | saved | wrong data | only new / contacted / converted / closed |
| 12 | Enquiry id | 99999 | "Enquiry updated." | confusing | "That enquiry was not found." |
| 13 | Admin messages | any refusal | shown in a green "success" box | confusing | refusals shown in red |
| 14 | Availability address | `?property=abc` | PHP warning on the page | crash-ish | falls back to the property on sale |
| 15 | Special prices first night | "abc" | "The last night is before the first night." | confusing | "Choose the first and the last night." |
| 16 | Request body | broken JSON | "Unknown property." | confusing | "We could not read that request…" (400) |
| 17 | `rooms` not a list | "lots" | refused but PHP warning | log noise | treated as nothing chosen |
| 18 | Guest details missing entirely | guest = text | PHP warnings | log noise | handled |
| 19 | CSV download | name "=HYPERLINK(…)" | would run as a formula in Excel | security | cells starting with = + − @ get a leading ' |

Also improved: a refused guest detail now keeps the guest on the details step with what they typed (before, they were sent back to step 1 and lost it), and the booking page checks phone and email itself before sending.

## 3. Root causes
* **No shared input checks.** Each form trusted its input. Now `lib/db.php` has `LIMITS`, `too_long()`, `valid_phone()`, `valid_arrival_time()` and `valid_date()`, used by booking, enquiry and admin.
* **"Be forgiving" code** (`max(1, (int) $qty)`) turned garbage into real orders. It now refuses instead.
* **SQLite hides length problems** that MySQL would turn into errors on the live server.

## 4. Already solid
Dates in the past, reversed dates, stays over 21 nights, more than 5 rooms online, overbooking, unknown or switched-off properties, rooms from another cottage or property, gala dinner minimum, email format on bookings, wrong or missing private codes, lookups with wrong details, SQL-looking input (all queries are parameterised), HTML in names (always shown as text; checked on every admin page), security tokens on admin forms, payment amounts (0, negative, "abc", 1e12, over the balance), refunds when nothing is owed, special-price guard rails.

## 5. Re-test and regression
* All 107 values re-sent after the fixes: **0 wrongly accepted**, and **0 PHP errors, warnings or notices** in the server log.
* Normal use still works: guest flow 24/24, admin flow 35/35, `bin/test-changes.php` 17/17, `bin/check-system.php` (only "Stayflexi not connected", a real go-live item), and a booking through the real page in the browser (bad phone caught on the page; `+91 98250 12345` goes through).
* Test data only ever went into the throwaway copy. The real database still has exactly the 7 bookings (the owner's + 6 demo).
