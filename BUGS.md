# BUGS.md: bugs found, to fix later

_Tested 30 Sep 2026 with the demo data (6 "Demo …" bookings + the owner's KSR-GJKQYG). B1–B17 are not fixed yet. Two checks on 4 Oct 2026 followed:
- **Debug mode + syntax:** added B18–B19 and fixed F1–F2.
- **Runtime, every code path executed:** added B20–B26 and fixed R1–R4.
- **Console, every level on every page:** fixed C1–C3; no new open bugs.

All three are described in the next sections. B1–B26 are open._

## 4 Oct 2026: debug-mode and syntax check

**Syntax: no errors in any file.** Every tracked file was run through a real parser for its type:
- 47 PHP: `php -l`.
- 29 TypeScript/TSX: `tsc --noEmit`, plus the production build.
- 35 Python: compiled. Their byte-order marks are fine for Python.
- JS: `node --check`.
- JSON, YAML, XML/SVG, HTML: parsed.
- 3 CSS: `lightningcss`. The Tailwind `@theme`/`@apply` warnings are expected.
- `schema.sql` + `seed.sql`: loaded into a fresh database.
- 106 images and 3 videos: opened, none corrupt.
- `.htaccess`: read.

**Debug mode:** a throwaway engine copy with debug on.
- 143 requests across every guest page, API endpoint (good and bad input) and admin screen/action. Each was read through the debug bar's notice list, the `_debug` block on API replies and PHP's error log.
- Every website route (23) and the full guest booking flow, from choosing a cottage to the confirmation page, with the browser console watched.
- Admin Availability side panel.

**Fixed on 4 Oct (found by this check):**
* **F1. Fields sent as a list instead of text: crashes, and "Array" saved as data.** Only hand-made requests send these; the real pages never do. 21 places in all:
  * **Public booking API** (`quote.php`, `book.php`): `check_in`, `check_out`, a `rooms` entry, `payment_mode` or `coupon` as a list returned **HTTP 500**. A guest name sent as a list was **saved as the word "Array"**.
  * **Guest pages:** `index.php?check_in[]=` and `manage.php?ref[]=` returned **500**.
  * **Admin:** the bookings list and the CSV export crashed on `?q[]=`, and UPI **Money received** crashed when `bank_ref` was a list. The payment note, booking note, cancel reason and enquiry note were saved as "Array". Other fields raised warnings but were refused.
  * **Fix:**
    * Address parameters are made text once in `lib/db.php`, since no page reads a list from the URL.
    * Admin form fields are made text in `admin/_auth.php`, except the three real list fields: `room`, `addon`, `plans`.
    * Cart and guest fields are checked in `quote_cart()` and `create_booking()`.
  * **Result:** each case now gets a plain refusal, with no warnings, and nothing new is saved as "Array".
* **F2. Arrival-time wheel: uncaught JavaScript error.** `assets/engine.js` `show()` ran from the wheel's 90 ms settle timer after the details step had been replaced, and `$('#g-arrival')` was null: `Cannot set properties of null (setting 'value')`. Seen live in the browser, then reproduced on purpose. **Fix:** `show()` returns early when the wheel is gone. The wheel still records times ("5:00 PM").

**Checked and fine:**
- Pricing tests: 17/17.
- Every input refusal held in the database: no negative payment, no "bitcoin" method, no ₹−1 special price, no made-up enquiry status, bot enquiry not stored.
- UPI QR returns a correct QR.
- The website has no console errors and correct titles.

**New open bugs:** B18 and B19 below.

## 4 Oct 2026 (later): runtime check, executing every code path

**What ran.** Every path was executed with debug mode on, including the ones that are switched off on this PC:
- Razorpay, Stayflexi and SMTP email, pointed at local fake servers (nothing reached a real service).
- Pay-at-hotel, White Rann Camp and discount codes, switched on in a throwaway copy.
- All 8 command-line/cron scripts.
- 69 HTTP requests through those paths, checked against what each action did in the database.
- The guest booking page's JavaScript: Razorpay checkout (its popup stubbed), UPI QR, three rooms with mixed occupancy, sold-out dates with suggestions, the network-failure screen and retry, and the remembered-booking banner.
- Every state on the check-status page, the PDF viewer, and the admin's SHA-256 sign-in (including its fallback), confirm box and per-tab sign-in.
- The website's mobile menu, lightbox and contact form, plus all 53 media paths.
- The production Express server (`pnpm start`) and the Vercel contact function.
- The legacy Python scripts were checked without running them: they rewrite files.

**Result.** After the fixes below: no PHP warnings or errors, an empty error log, no JavaScript errors, and every expectation met. Pricing tests 17/17, TypeScript and the build pass, and the real database was only read.

**Fixed on 4 Oct (found by running the code):**
* **R1. `setup.php` treated any unknown option as "run the full setup".** A typo such as `--amdin`, or `--help`, reloaded `seed.sql` over the copy's data. That wiped its Stayflexi mapping and reset special prices. Three statements failed on foreign keys, yet it still printed **"Ready."** and exited as a success. **Fix:**
  * Unknown options are refused.
  * A full setup on a database with bookings is refused unless `--reset` is given.
  * Any failed statement ends with "FAILED", exit 1.
  * A first-time setup on an empty database is unchanged.
* **R2. Razorpay refunds did not update "amount paid".** `razorpay_refund()` recorded the −₹14,900 refund but left `amount_paid` at ₹14,900. `record_offline_refund()` already recalculated it. **Fix:** call `refresh_amount_paid()`. Verified: ₹0 after a full refund.
* **R3. Email (it had never run before).** **Fix:** subject and sender name encoded; body base64; `Date` and `Message-ID` added; the SMTP client stops if STARTTLS fails. Verified with a mail parser and a fake server that refuses TLS. The problems were:
  * The subject was sent as raw UTF-8 (`Booking confirmed — … · …`), which some mail apps show as `â€"`.
  * There were no `Date` or `Message-ID` headers, so mail was more likely filed as spam.
  * A line starting with "." could cut a message short, and very long lines could exceed SMTP's 998-character limit.
  * **If STARTTLS failed, the client carried on and sent the mailbox password in plain text.**
* **R4. `pnpm start` failed on Windows.** It used `NODE_ENV=production node dist/index.js`, Linux syntax that Windows' shell can't run: `'NODE_ENV' is not recognized`. **Fix:** the script is `node dist/index.js`, and the server sets production mode itself, so error pages still hide stack traces.

**New open bugs:** B20–B26 below.

## 4 Oct 2026 (latest): console check, every level on every page

**What was checked.** Every console level (errors, warnings, info, logs, debug) plus failed network loads:
- **Website in development:** all 23 routes at desktop size, key pages at phone size (375 px), with the menu, lightbox, contact form, FAQ jump and full-page scrolling.
- **Website production build:** all routes.
- **Booking engine:** a full booking with the debug bar open and the arrival wheel used; check-status lookups (right, wrong and private link); the receipt and terms viewers.
- **Admin:** sign-in, list filters, booking pages, change and special-price previews, Availability at 7/14/30 days with 5 side panels, enquiries, sign-out.
- **Server consoles:** Vite, PHP (built-in server and error log) and Node.
- **Source scan** for leftover `console.*` and `debugger`.

**Result.**
- The browser console was silent everywhere: in development the only lines are Vite's and React's own normal messages, and the production build logs nothing at all.
- No React warnings, no `debugger` statements, and no `console.*` calls in any browser code.
- Server consoles are clean after the fixes below.

**Fixed on 4 Oct (found by this check):**
* **C1. A mistyped check-status lookup logged a red console error.** `api/booking-lookup.php` answered "not found" with HTTP 404, so the browser logged "Failed to load resource: the server responded with a status of 404" every time a guest mistyped a code or phone number. **Fix:** it now answers 200 with `ok: false`, like the booking API's other guest mistakes. `manage.php` reads `ok`/`error`, so the page behaves the same. Verified: right, wrong and right-again lookups all display correctly with an empty console.
* **C2. `/favicon.ico` returned 404 on nearly every engine page** (26 times in the PHP server log): browsers that don't use SVG icons ask for it. **Fix:** `favicon.ico` (the website's) added to the engine and linked next to `icon.svg` on the booking, status, viewer and admin pages. Verified: 200, and no 404s since.
* **C3. The production server crashed with a raw stack trace when its port was taken** (`Unhandled 'error' event … EADDRINUSE`). **Fix:** `backend/server/index.ts` prints "Port 3100 is already in use…" and exits.

**How it was tested.** All tests ran on **throwaway copies** of the booking engine and its database in a temp folder: one on port 8090 for pages and PDFs, one for the load tests. The real database was only read, never written. Checks covered:
- the numbers stored for every booking;
- the guest check-status API and page;
- every receipt and the terms PDF;
- every admin page with a test login made in the copy;
- the Availability screen and side panel;
- 7 heavy-traffic tests: up to 20 processes hitting the database at the same instant;
- the new website pages, for console errors.

Paths below are relative to `backend/booking-engine/` unless they start with `frontend/`.

---

## What works (checked, no bug)
* **Stored money adds up for all 7 bookings.**
  * rooms + extras + GST = total;
  * amount paid = payments − refunds;
  * nights match the dates.
* **Availability matches the bookings.**
  * Checked on the Availability chart, the booking page's room counts and the database, e.g. 30 Sep: 11 Kutchi / 6 Deluxe free.
  * Cancelled bookings free their cottages.
* **No double-booking under load.** 20 guests tried to book the last cottage at the same moment and exactly 1 booking was stored.
* **No guest's data mixed with another's.** 20 simultaneous book-and-pay runs gave:
  * 0 wrong names, emails, rooms or payments;
  * 0 duplicate booking codes;
  * 0 duplicate private codes;
  * 0 payments without a booking.
* **Desk payments add up under load.** 20 desk payments recorded on one booking at the same moment summed correctly.
* **Guest look-up works.**
  * Finds bookings by code + phone, code + email (any case) and code + private link.
  * Wrong details are refused.
  * The rate limit (15 per 5 minutes) works.
* **Receipts render correctly.** All 7 open, show the correct amounts, GST and status, and show names with brackets and dashes properly.
* **Admin pages load cleanly.** Bookings list (groups, counters, UPI box), every booking page, Change booking, Availability with side panel, Special prices, Enquiries and CSV all return 200 with no PHP errors or warnings.
* **Website pages are clean.** Home, Experiences and Around the Resort have no console errors.

---

## High: money, refunds, lost bookings

### B1. Cancelling on the last day of a price band charges the next band
`lib/booking.php:911` (`cancel_booking`) counts days as `floor((check-in − now) / 24h)`. The table shown to guests (`cancellation_schedule`, `lib/booking.php:271`) counts whole calendar days, so they disagree on the boundary day.
**Proof (copy):**
| Check-in in | Guest is told cancelling today costs | Actually charged | Example on a ₹14,900 booking |
|---|---|---|---|
| 30 days | 0% ("Up to today") | **75%** | ₹11,175 kept instead of ₹0 |
| 21 days | 75% | **100%** | ₹14,900 kept instead of ₹11,175 |

**Fix:** count calendar days in both places: `(strtotime($check_in) - strtotime(date('Y-m-d'))) / 86400`. Also `available_payment_modes()` at line 251. Add a test in `bin/test-changes.php` for days 30, 29, 21 and 20.

### B2. Refund owed after a cancellation is invisible to staff and guest
Demo Vikram (KSR-Z8P7X6) was cancelled 45 days ahead, so ₹17,900 is owed back (`bookings.refund_amount`). Where it shows:
* **Admin booking page:** shows "Paid ₹17,900" and nothing else. The money section is empty for cancelled bookings (`admin/booking.php:213`), so there is no "Refund due" line and no **Record refund** form. The desk sees the refund once in the message right after cancelling, then never again.
* **Guest status page:** says only "This booking has been cancelled" (`manage.php:142`). The API forces `refund_due` to 0 for cancelled bookings (`api/booking-lookup.php:37`) and never sends `refund_amount`.
* **Receipt PDF:** *does* say "Refund ₹17,900", so the guest's two views disagree.
* **Bookings list:** the Cancelled group shows only "Paid", with no refund flag.
* **No cancellation charge or reason shown.** Demo Priya (KSR-6JQ9JK, cancelled 10 days ahead, 100% kept) shows "Paid ₹80,850" with no charge line and no cancellation reason anywhere (`cancel_reason` is stored but not displayed).

**Fix:**
* For cancelled bookings, show "Cancellation charge X% ₹…", "Refund due ₹… (given back ₹…)" and a Record refund form until it's settled.
* Return `refund_amount` from the lookup API and show it on the status page.
* Show `cancel_reason` and `cancelled_at`.

### B3. Desk changes an unpaid booking, but the guest still pays the old amount and it's marked confirmed
`modify_booking()` (`lib/booking.php:654`) updates `total` but not `amount_due_now`. All three payment paths charge `amount_due_now`:
* Razorpay: `lib/payment.php:57`
* test payment: `lib/payment.php:241`
* UPI QR: `lib/payment.php:272`

**Proof (copy):**
1. 50% booking of ₹14,900 (due now ₹7,450).
2. The desk changes it to 4 nights (total ₹29,800).
3. The guest pays and is charged **₹7,450**.
4. The booking becomes **confirmed** with ₹7,450 still due now.

**Fix:** recompute `amount_due_now` in `modify_booking()` (e.g. from `booking_money()`), or charge `booking_money($b)['due_now']` in the payment paths.

### B4. Heavy traffic: most simultaneous bookings fail with "We could not save that booking" (SQLite)
`lib/db.php:18` opens SQLite with no `busy_timeout`, so a second writer fails at once with *database is locked* instead of waiting.
**Proof (copy):**
* 20 guests booking **different dates** at the same moment: **17 refused, 3 saved**.
* 20 guests booking 12 free Kutchi cottages: **14 refused**, only 6 saved, 6 cottages left unsold.
* 34 `booking_failed` rows logged, all *database is locked*.

This matters on this PC and anywhere SQLite is used. The live site is meant to use MySQL, which waits on the `FOR UPDATE` lock instead. **That path has not been tested:** MySQL isn't installed here.
**Fix:**
* `PRAGMA busy_timeout = 5000` and `PRAGMA journal_mode = WAL` after opening SQLite.
* Start write transactions with `BEGIN IMMEDIATE` on SQLite.
* Run the same load test (`conc_run.php` pattern) on MySQL before launch.

### B5. Test payment has no "already paid" check: repeated presses multiply the amount paid
`test_payment_settle()` (`lib/payment.php:232`) only refuses cancelled bookings.
**Proof (copy):**
* "I've paid (test)" pressed 20 times at once on a ₹14,900 booking recorded **₹1,49,000 paid** (20 payment rows).
* One ordinary second press later added another ₹7,450.

Test mode must be off at launch anyway, but until then any double-click corrupts the booking.
**Fix:** refuse unless status is `pending` and `booking_money()['due_now'] > 0`, inside a transaction.

---

## Medium: under load, or wrong information

### B6. One payment confirmed at the same moment runs its side effects many times
`settle_payment()` (`lib/payment.php:150`) and `upi_mark_received()` (`lib/payment.php:319`) read the status first, then update it: check, then act. The amount stays right (same payment row), but:
**Proof (copy):**
* The same online payment confirmed by 20 processes at once (browser return + webhook + retries) sent the confirmation email and pushed to Stayflexi **15 times**.
* Two staff pressing **Money received** at once ran it 5 of 5 times.

Once Stayflexi is connected, this could create duplicate reservations on the OTAs.
**Fix:** make it atomic, e.g. `UPDATE payments SET status='paid' … WHERE id = ? AND status <> 'paid'`, and only continue if 1 row changed.

### B7. Rate limiting will slow down and may block real guests under heavy traffic
`rate_limit()` (`api/_init.php:78`):
* It adds an `audit_log` row for **every** guest request.
* It counts rows with no index (`schema.sql:342`).
* **Measured (copy):** 0.1 ms per request now, **105 ms** after 500,000 rows (roughly a busy season). Every page makes several calls.
* It keys on `REMOTE_ADDR` only. Behind a proxy or CDN (Vercel rewrite, Cloudflare), every guest shares one address, so 12 bookings in 5 minutes from *everyone* would block everyone.

**Fix:**
* Add an index on `(action, ip, created_at)`.
* Delete `rl_%` rows older than a day (cron).
* Use the real client IP header from the known proxy.

### B8. 50% option and free-cancellation promise are offered when they can't apply
`config.php:127`: the `advance` mode has no `min_days_before_arrival`. For a stay 10 days away the guest is still offered "Pay 50% now — Balance due 30 days before arrival. Free cancellation up to 30 days before arrival." Both dates are already past. The same note is printed on the receipt (see Demo Karan, 17 days out).
**Fix:** hide 50% (or change its note) inside 30 days, and only show "free cancellation" when it's still possible. Owner to confirm the rule.

### B9. Admin offers "Cancel booking" for guests who are already staying, then fails with a guest-facing message
`admin/booking.php:281` shows the cancel form whenever the booking isn't cancelled. `cancel_booking()` refuses any stay that has started (`lib/booking.php:908`).
**Proof (copy):** pressing Cancel on in-house Demo Rohan shows *"Past stays cannot be cancelled online. Please call us."* to the staff member. There is no way to record a no-show or an early departure.
**Fix:** hide the form once the stay has started. Add "No-show" / "Left early" actions (the schema already allows `no_show` and `completed`).

### B10. Required change (owner, 30 Sep 2026): only one admin session at a time
**Today:** two people signed in with the same login from two browsers both get full access (tested on the copy). The only guards are:
* the per-tab mark (a new tab must sign in again);
* the 10-minute idle timeout.

**Wanted:** while someone is using the admin (e.g. Manvir), nobody else can get in. The next person can sign in only after that tab is closed or signed out.
**Suggested design:**
* Store the active session id and a `last_seen` time in the database (e.g. an `admin_lock` row).
* The open admin tab sends a small heartbeat every 20–30 s. Browsers don't reliably report a closed tab, so the lock expires about 60 s after the last heartbeat.
* A second sign-in is refused with "The admin panel is in use by Manvir since 3:10 pm. Try again when they close it."
* Sign out releases the lock immediately.
* Decide with the owner whether an "I'm Manvir, take over" option is needed for a crashed browser.

This also prevents two people changing the same booking at once, which nothing prevents today.

### B11. A UPI payment waiting for the desk holds the cottage indefinitely
`rooms_booked()` (`lib/inventory.php:174`) counts any pending booking with an `awaiting_confirmation` UPI payment, with no time limit. Demo Karan (KSR-LL6CZZ) has held a Kutchi cottage since 29 Sep. If the guest never paid, the cottage stays blocked until someone notices.
**Fix:** expire the UPI wait after N hours, or flag old ones on the Bookings page.

### B12. The unpaid half says "before arrival" even after the guest has arrived
Demo Rohan is staying now (night 2 of 2) with ₹10,100 unpaid. Every screen still says "Due before arrival" / "the rest before arrival":
* admin booking page (`admin/booking.php:179`, `:231`)
* Availability side panel (`admin/calendar.php:331`)
* receipt

**Fix:** once check-in day has come, show it as overdue ("Collect at the desk ₹…") and highlight it on the Bookings list.

---

## Low: display and tidy-ups

* **B13. Empty "If you need to cancel" heading.** `lib/documents.php:288` prints the heading even when every period is over (stays that have started or finished), so the receipt shows an empty section.
* **B14. Stayflexi would receive base prices.** `lib/channel.php:146` sends the stored `nightly` list, which holds the base (double) price per night, not what was charged. For singles and triples, Stayflexi would get the wrong nightly amount, e.g. Demo Asha's triple: ₹6,500 sent vs ₹8,000 charged. Not live yet (Stayflexi off).
* **B15. Bookings list "due" means different things.** The list shows the whole unpaid balance as "due" (Demo Karan: "due ₹14,900"). The booking page splits it into ₹7,450 now + ₹7,450 before arrival.
* **B16. A finished stay stays "confirmed".** There is no `completed` step (the list calls it "checked out" only by date).
* **B17. Guests told they owe more than they paid, with nothing collecting it.** Demo Neha's page says cancelling now costs 75% (₹16,875) but only ₹13,000 was paid, and nothing explains or collects the difference.
* **B19. Emails make three actions take about 2 seconds on this PC (local only).** This affects "I've paid (test)", sending an enquiry and UPI **Money received**. With SMTP off, `lib/mail.php` calls PHP `mail()`. Windows has no local mail server, so each call waits and then fails; it's logged as `mail_failed` in `audit_log`. The debug bar shows the delay, e.g. `payment-test.php 2191ms (php 2169ms)`. cPanel hosts deliver locally, so it is fast live. There is no on/off switch for email. Either point SMTP at a real mailbox in the local `config.local.php`, or add a `mail.enabled` setting that skips sending.

## B18 (4 Oct 2026, medium): "Money received" brings a cancelled booking back to life
`upi_mark_received()` (`lib/payment.php:316`) sets the booking to **confirmed** without checking whether the desk has cancelled it meanwhile.
* Nothing stops it from happening:
  * `cancel_booking()` leaves any UPI payment that's still waiting as it is.
  * The **UPI payments waiting to be checked** box (`admin/index.php:105`) lists waiting payments for cancelled bookings too.

**Proof (throwaway copy, booking KSR-PKEBBX):**
1. Booking created and the UPI QR shown.
2. The desk cancels it.
3. Someone checks the bank and presses **Money received**.
4. Status: **confirmed** again, still carrying its cancel reason and a ₹100 refund owed.

In real life, the cottage would be held again (perhaps after being resold), Stayflexi would be told it's booked, and the guest would get a confirmation email for a cancelled booking.

**Fix:**
* `upi_mark_received()` should refuse (or only record the money, leaving the booking cancelled) when the booking is cancelled.
* `cancel_booking()` should mark waiting UPI payments as void.
* The UPI box should leave out cancelled bookings, or flag them.

## B20–B26 (4 Oct 2026, from the runtime check)
* **B20 (medium). Opening the admin in a second tab signs out the first one.** A new tab has no per-tab mark, so it goes to `logout.php?again=1`, which ends the whole session. Someone halfway through changing a booking who opens another tab to look something up loses that work on their next save ("Your session expired"). Verified: after a second tab opened, the first tab's next request went to the sign-in page. Belongs with the one-admin-at-a-time work (B10): the lock planned there should replace this.
* **B21 (medium, UX). Three rooms, two chosen: "Continue" does nothing.** On the booking page, pressing **Continue** before every room has a cottage leaves the page unchanged, with no message (`assets/engine.js`, `#roomsDone` handler: `if (cart.picks.every(Boolean)) …`). Guests will think the button is broken. **Fix:** say which room still needs a cottage, or disable the button until all are chosen.
* **B22 (low, privacy). The check-status page sends the guest's phone or email in the address.** The lookup is `GET api/booking-lookup.php?ref=…&contact=<phone or email>`, so the details land in server logs and browser history. The API already accepts POST (`input()` reads JSON); `manage.php` should send it that way.
* **B23 (low). A junk reply from Stayflexi is reported to the guest as "Those dates have just been taken".** A 200 response that isn't JSON (e.g. an HTML error page) leaves the availability count at 0 (`lib/channel.php` `channel_verify_still_available()`), so the guest is told the room sold out. **Fix:** treat a reply without the expected fields like "unreachable": "We could not confirm availability just now. Please call us."
* **B24 (low). Both contact endpoints accept empty posts and report success.**
  * `backend/server/index.ts` `POST /api/contact` stores *anything*, even a form post with no JSON, as an "enquiry" in `data/enquiries.json` inside the project folder, and replies "Enquiry saved successfully".
  * `api/contact.ts` (Vercel) only logs, and also says success with no body.
  * The site doesn't use either today (the Home form is still the mock), but don't wire them up as they are. The engine's `api/enquiry.php` already validates and stores enquiries properly.
  * Both also write the whole enquiry (name, email, phone, message) to the server console with `console.log`, so personal details end up in server and Vercel logs.
* **B25 (tidy-up). The 35 legacy Python scripts can't run.** Every one opens files under `client/…`, the folder renamed to `frontend/` on 29 Sep, so each would fail at its first file. 29 of them rewrite project files. They compile and have no undefined names or missing imports. **Fix:** delete `scripts/legacy/` (already marked "delete once confirmed").
* **B26 (low). `setup.php --reset` on a database with bookings still half-applies.** `seed.sql` deletes properties, which bookings point at, so 3 statements fail; this is now reported as FAILED (R1). Only ever use `--reset` on a test copy.

---

## Not tested (need the live setup)
* **MySQL under load** (B4 and B6 on MySQL): row locks, deadlocks when two carts lock cottage types in a different order.
* **Real Razorpay** payments, webhooks and refunds; real emails. The code paths themselves were run against local fakes on 4 Oct 2026; what's untested is the real services' responses and delivery.
* **Real multi-worker web server.** Load was tested at the database level: PHP's built-in server on Windows handles one request at a time.

## Still open from earlier checks (details in `docs/08-STATUS-ISSUES-AND-ROADMAP.md`)
* **Must be off before launch:**
  * sample mode and test payments;
  * debug mode;
  * `website_url` still points to localhost;
  * a long admin password is needed;
  * the live Razorpay secret must be regenerated;
  * the six demo bookings must be removed.
* Home contact form is a mock (sends nothing).
* Stayflexi not connected; SMTP not set; no owner email on new bookings.
* ~269 MB of unoptimised images; placeholder content (Our Journey, Dining, FAQ, `[WRC LOGO]`).
* Brochure vs website facts await the owner (distances, years, room names).

## How to re-run these tests
The scripts are not in the repo; they were one-off, in the session's temp folder. The pattern:
1. Copy `backend/booking-engine/` without `config.local.php` to a temp folder.
2. Copy `data/booking.sqlite` into it.
3. Add a `config.local.php` with `db.driver = sqlite`, no keys, and mail off.
4. Create a login there with `php bin/setup.php --admin qa-tester "QA" <password>`.
5. Run it with `php -S 127.0.0.1:8090`.

For load, start 20 `php worker.php` processes that wait for the same start time, then call `create_booking()`, `settle_payment()`, `test_payment_settle()`, `record_offline_payment()` or `upi_mark_received()`, and afterwards compare every booking's rows. **Never point these at the real `data/booking.sqlite`.**
