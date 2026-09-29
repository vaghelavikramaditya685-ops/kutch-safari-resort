# Testing, Go-Live Checklist and Handover

_Written 26 Sep 2026. How to check the engine safely, what to switch before real guests, and where everything is._

## 1. Testing without touching real data
**Rule:** nothing is tested on `backend/booking-engine/data/booking.sqlite`. It holds only real data (doc 28 §3).

* **`php bin/test-changes.php`** (from `backend/booking-engine/`) runs 17 checks. It copies the database to the system temp folder, books test stays **in the copy**, and deletes the copy at the end. It checks:
  * adding or removing extras, rooms and nights;
  * moving dates;
  * Double ↔ Triple / Single;
  * special-price bookings;
  * used-up discount codes;
  * several changes at once, and that the saved booking matches the preview;
  * "no change → same total";
  * 50% vs full payment (collect now / before arrival / give back);
  * payments − refunds.

  It exits 1 if anything fails. It needs the SQLite setup, so it runs on a development PC, not the live server.
* **`php bin/check-system.php`**, **`check-razorpay.php`**, **`check-stayflexi.php`**: health checks (config, database, keys, connections). Since 29 Sep 2026 `check-system.php` tests the property that is on sale and runs its create/look-up/cancel test on a **temporary copy** of the SQLite database (on MySQL it skips that test unless `--write-test` is given).
* The flow and nonsense-input scripts used on 29 Sep 2026 are kept in `../chaos/EVIDENCE/`. They drive a throwaway engine copy on another port; see `../chaos/REPORT.md`.
* **Screens:** use the browser with test payments on. To look at an admin screen with made-up data, render it against a throwaway copy, never the real file, and delete the copy after.
* **Test bookings made by hand** in the real database are real rows. Delete them afterwards (Admin can cancel; full deletion is by request, as was done on 26 Sep 2026).

## 2. Local start (this PC)
```bash
pnpm dev:book     # PHP engine on 127.0.0.1:8080 (must be 127.0.0.1, not localhost)
pnpm dev          # website on :3000, /book/ proxied to the engine
```
* Site: http://localhost:3000
* Booking: http://localhost:3000/book/
* Admin: http://localhost:3000/book/admin (fills itself in)

## 3. Go-live checklist
**Must do**
- [ ] `rules.guest_details_optional => false` (sample mode off: name and phone required)
- [ ] `test_payments.enabled => false` (removes "I've paid (test)")
- [ ] `debug => false`
- [ ] Live Razorpay keys in the server's `config.local.php`, **after regenerating the live Key Secret** (it was shared in chat). Add the webhook `<base_url>/api/webhook-razorpay.php` with its secret
- [ ] UPI id (`upi.vpa`) if QR payments are wanted
- [ ] Strong admin password: `php bin/setup.php --admin manvir "Manvir" "<long password>"` (the local one is `1234`). The sign-in page sends SHA-256 of username and password; `setup.php --admin` stores bcrypt(SHA-256), so always set passwords with it
- [ ] Remove the six demo bookings (guest names starting "Demo", emails @example.com) — or start the live database fresh
- [ ] MySQL database from `schema.sql` + `seed.sql` (full setup **once**; never again on live, doc 28 §4). **`config.php` now defaults to SQLite**, so the live `config.local.php` must say `'db' => ['driver' => 'mysql', …]`
- [ ] `base_url` and `allowed_origins` for the real domain; `properties.website_url` → the live site ("Back to website"). `seed.sql` now sets it to `http://localhost:3000`, so change it in the live database after setup
- [ ] Mail: SMTP settings and `from_email` on a domain you own (currently `@kutchsafariresort.com`)
- [ ] Decide how the website reaches the engine: same host `/book/`, `VITE_BOOKING_URL`, or a Vercel rewrite (doc 15)

**Owner to confirm**
- [ ] GST on the Deluxe triple (₹8,000; doc 08 §5), with the accountant
- [ ] Cancellation ladder (free 30+ days, 75% at 21–29, 100% under 21)
- [ ] 50% plan: the balance is "due 30 days before arrival" (as guests are told), or at check-in? The wording must match (doc 22 §3)
- [ ] Diwali and Christmas / New Year resort prices (add as Special prices)
- [ ] House rules for the terms (`terms.house_rules`)
- [ ] Owner email alerts for new bookings (offered, not decided)

**Later**
- [ ] Stayflexi connection (doc 27). Until then, keep the website's cottages separate from the OTAs'
- [ ] White Rann Camp back on (`properties.active = 1`) after checking its prices
- [ ] A way to close cottages by hand, if wanted again (the grid was removed)

## 4. Handing the project over
* No pen-drive copy was made on 26 Sep 2026 (the owner said not to). The project lives in `C:\Users\ADMIN\Downloads\kutch-safari-resort`.
* **When copying it anywhere, leave out the Razorpay keys.** Blank the key values in `backend/booking-engine/config.local.php` in the copy (Razorpay then stays off until keys are added), or leave that file out and let the receiver create their own.
* `.git` holds history up to the one push on 25 Sep 2026; later work is uncommitted.

## 5. Where things are
| What | Where |
|---|---|
| All settings | `backend/booking-engine/config.php` (+ `config.local.php` for secrets and local overrides) |
| Prices, rooms, extras | Database (`seed.sql` for a fresh install); Admin → Special prices for dates |
| Price logic | `lib/inventory.php` (`price_rooms`), `lib/booking.php` (`quote_cart`) — doc 21 |
| Changing bookings, money | `lib/booking.php` (`quote_modification`, `modification_delta`, `booking_money`) — doc 22 |
| Availability | `lib/inventory.php`, `admin/calendar.php` — doc 23 |
| Admin screens | `backend/booking-engine/admin/` — doc 24 |
| Guest flow | `assets/engine.js`, `index.php`, `manage.php` — doc 25 |
| Payments | `lib/payment.php` — doc 26 |
| Stayflexi | `lib/channel.php`, `bin/` — doc 27 |
| Database rules | `schema.sql` — doc 28 |
| What went wrong before | doc 29 |
| Website ↔ engine links | `frontend/src/lib/booking.ts`, `pages/BookingRedirect.tsx` — docs 03, 09 |
