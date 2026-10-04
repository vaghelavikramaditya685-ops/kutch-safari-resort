# Admin Panel and Security

_Combined on 30 Sep 2026 from 2 earlier files. Start at [`README.md`](../README.md) for the overview and hard rules._

Every admin screen, and how sign-in (SHA-256), sessions, tokens and input checks work.

---

## Contents
* [Auth and Security](#context-auth-and-security) _(was `context/auth_and_security.md`)_
* [Admin Panel Guide](#docs-24) _(was `docs/24-ADMIN-PANEL-GUIDE.md`)_

---

<a id="context-auth-and-security"></a>

## Auth and Security
_(was `context/auth_and_security.md`)_
### Roles
* **Guest** — no account. Access to one booking by *ref + mobile/email* or *ref + private `manage_token`* (40 hex chars, compared with `hash_equals`) [CODE `find_booking`, `find_booking_by_token`].
* **Admin (`owner`)** — `admin_users` rows; one active user `manvir` [CODE]. No other roles [CODE].

### Admin sign-in [CODE `admin/login.php`, `admin/_auth.php`]
* The page sends **SHA-256 of the username (trimmed, lower-case) and SHA-256 of the password** — never the typed text (inputs have no `name`). Built-in `crypto.subtle`, or a checked plain-JS fallback on plain http.
* Server finds the user by `hash('sha256', lower(email))`, then `password_verify(sha256, password_hash)`; stored hash = **bcrypt(sha256(password))**. `bin/setup.php --admin` writes it this way.
* Plain-text posts are refused ("Please sign in using this page").
* Not encryption: HTTPS protects the connection on the live site [INFERRED; documented to the owner].
* 6 failed attempts per 15 min per IP → wait [CODE `admin.login_attempts`].
* Session cookie only (ends with the browser), `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS; per-tab `sessionStorage` mark; 10-minute idle sign-out [CODE].

### Request protection
* CSRF token on every admin POST (403 "Your session expired…" without it) [CODE `check_csrf`].
* Parameterised SQL everywhere (PDO prepared statements) [CODE].
* Output escaped (`h()` in PHP, `esc()` in admin JS); a `<script>` guest name shows as text on every admin page [CODE, tested 29 Sep].
* CSV export neutralises formula cells (= + − @) [CODE `admin/export.php`].
* Input limits and format checks (see [backend_spec.md](05-BOOKING-ENGINE.md#context-backend-spec)).
* Rate limits per IP on every public API [CODE `rate_limit`].
* Payment start needs the booking's private code; Razorpay HMAC for verify and webhook [CODE].
* `.htaccess` blocks `config*.php`, `*.sql`, `*.sqlite`, `data/`, `bin/` (Apache only) [DOC `docs/02`]; `bin/` scripts refuse to run from the web [CODE].

### Secrets
* Only in `backend/booking-engine/config.local.php` (git-ignored): Razorpay test + live keys [CODE]. Never commit; leave out of copies.
* **Live Razorpay secret was shared in chat → regenerate before launch** [DOC `docs/26`].
* Local admin password is the test value `1234` → set a long one on the live server [DOC `docs/30`].

### Known risks / hardening still needed
* Sample mode (`guest_details_optional`) and test payments are **on** — turn off before launch [CODE].
* `debug => true` locally shows error detail — off in production [CODE].
* Stayflexi off → overselling risk if the same rooms are on OTAs [DOC `docs/27`].
* No 2FA for admin [INFERRED]; single shared owner account [CODE].

---

<a id="docs-24"></a>

## Admin Panel Guide
_(was `docs/24-ADMIN-PANEL-GUIDE.md`)_
_Written 26 Sep 2026. Every screen in `backend/booking-engine/admin/`, what it does, and the rules behind it._

### 1. Getting in
* Address: `/book/admin/`. Typing **`/book/admin`** (no slash) or the website's **`/admin`** also works. The address fills itself in to `/book/admin/login.php`.
  > Why the redirect exists: without the trailing slash the panel's relative links pointed one folder too high (`/book/login.php`), so the page broke. `admin/_auth.php` now redirects `…/admin` → `…/admin/` first.
* Sign in with a **username** (not case-sensitive). The local test login is `manvir` / `1234`. **Change it before going live** (doc 30).
* **The username and password never leave the browser as typed text** (29 Sep 2026, owner's request): the sign-in page sends SHA-256 of the username (trimmed, lower-case) and of the password. The server matches the username hash and checks the password hash against `admin_users.password_hash`, which stores **bcrypt(SHA-256(password))**. `bin/setup.php --admin` writes passwords this way. A sign-in without the hashes is refused. SHA-256 is a one-way hash, not encryption; HTTPS protects the connection on the live site.
* Refusals on admin pages (wrong amount, missing choice, too-long note) show in a **red** box; successes in green.
* The panel **always asks for the password**:
  * the sign-in ends when the browser closes (session cookie, no lifetime);
  * it counts only in the tab it was made in, via a per-tab `sessionStorage` mark (`ksr_admin_tab`) set after sign-in (`login.php` → `index.php?signed_in=1`);
  * it ends after `admin.idle_minutes` (10) without use. The open tab then says "You were signed out…" with **Sign in again**, instead of failing on the next save.
* **One admin at a time** (owner's requirement, done 5 Oct 2026; `admin/_auth.php`):
  * **Who holds it:** while someone has the panel open, nobody else can sign in or use it. The holder is one sign-in (session), stored in the `admin_lock` table (one row).
  * **Staying alive:** the open tab checks in every 25 seconds (`admin/heartbeat.php`). A check-in is not "use", so it never stops the 10-minute idle sign-out.
  * **Someone else** gets "The admin panel is in use by Manvir since 3:10 pm. Only one person can use it at a time — try again when they close it." This shows on the sign-in page (only after a correct password) and, for anyone already signed in, on every admin page (HTTP 423).
  * **Letting go:** closing the tab frees the panel within about 5 seconds (it says so as the page goes away); signing out frees it at once; a crashed browser frees it 90 seconds after it was last heard from.
  * **A second tab in the same browser** goes to `admin/tab.php`: "The admin panel is already open… carry on in that one". The first tab is left alone (it used to be signed out, losing whatever was being changed, B20). **Sign in here instead** is offered for when the other tab is gone.
  * There is no "take over" button for a crashed browser; it frees itself after 90 seconds.
* **Security headers on every admin page:** `X-Frame-Options: DENY` and `Content-Security-Policy: frame-ancestors 'none'` (no clickjacking), `X-Content-Type-Options: nosniff`, `Referrer-Policy: same-origin`. No page sends `X-Powered-By` (the PHP version).
* Login attempts are limited to `admin.login_attempts` (6) per 15 minutes per IP (the guest's own address behind a known proxy, `trusted_proxies`).
* Create or reset a login: `php bin/setup.php --admin USERNAME "Name" "password"`.

### 2. Bookings (`index.php`)
* Groups, in the order the day at the hotel runs:
  1. **Staying now** (earliest check-out first)
  2. **Coming up** (soonest arrival first)
  3. **Cancelled / not paid / no-show**
  4. **Finished** (most recent first; includes "left early")
* Finished and cancelled bookings older than `admin.list_clear_days` (15) drop off the list but are **never deleted**. "Show older bookings" (`?older=1`), a search or a filter brings them back. They are the payment and GST records.
* Rooms are shown grouped by cottage type. Under the money: "due now ₹X" (or "collect ₹X" once the guest has arrived) and "₹Y before arrival", the same split as the booking page; "refund ₹X" when overpaid; "refund ₹X owed" on a cancelled booking until it is given back.
* **Cancel a booking:** type the booking code and it opens that booking at its cancel box.
* UPI payments waiting for a check sit in a box at the top ("Money received" confirms them), with how long each has waited. After 48 hours (`upi.confirm_hours`) a waiting payment no longer holds its cottage and is flagged. Cancelled bookings are not listed (their QR is withdrawn). One already paid another way is listed with a warning (confirming it records money owed back).
* On a phone, the tables scroll sideways inside their own box; the page itself never does.

### 3. One booking (`booking.php`)
* Header: status, facts strip, **Receipt (PDF)**, **Change this booking**.
* Table: Room · Cottage · Guests (Single/Double/Triple) · Extra bed · Plan · Nights · Amount (GST included for the resort), then extras and the money rows.
* Status: Awaiting payment / Not paid / Confirmed / **Staying** / **Checked out** / Cancelled / No-show / Left early.
* Money rows follow how the guest pays (doc 22): **Due now** (to make up the 50% or the full amount; **Collect at the desk** from the arrival day), **Due before arrival** (50% plan), or **Refund due to guest**.
* **A cancelled booking** shows the cancellation charge (percent and what was kept, never more than was paid: "nothing more is collected"), **Refund owed** (less anything given back so far), and when and why it was cancelled.
* Payments: money received, and (folded away) payment attempts that never completed (a withdrawn UPI QR says "withdrawn — the booking was cancelled").
* **Collect the balance** only appears while money is owed; the amount is capped at the balance (checked under the booking's lock). **Give back to the guest** appears when the guest has paid more than the total, and on a cancelled booking until its refund is settled. The server refuses payments on cancelled, no-show or fully paid bookings.
* **Cancel** (`#cancel`), shown until the arrival day: what today's cancellation would charge (whole calendar days, the same as the guest's table), what was paid, and what the guest gets back. Card payments are refunded through Razorpay automatically once it is connected. Guests can't cancel online; they call or WhatsApp.
* **The stay has started** (from the arrival day to check-out): **Guest did not arrive** (no-show) or **Guest left early**. The cottages go back on sale from today; the money stays as it is (a no-show is charged in full under the policy).
* After a change: "Old total + added − taken off = new total", then what to collect or give back.
* "Set status" and "Send to Stayflexi" were removed. Stayflexi is updated by itself (doc 27).

### 4. Change a booking (`edit.php`)
Dates, each room's cottage and guests (add a row to add a room; blank a row to remove it), extras (`#extras`).
**Check price** shows **What changes**: Added / Taken off / Changed with an amount each, then Old total, the sums and the **New total**, then **Money**: how the guest pays, paid so far, collect now (or at the desk), rest before arrival, or give back. **Save changes** asks in an on-page box and then writes it. If the booking changed after the price was checked (a payment, another change, a cancellation), the save is refused and the new price shown. Cancelled, no-show and left-early bookings cannot be changed. Full logic: doc 22.

### 5. Availability (`calendar.php`)
Tape chart **by cottage** (the only view), 7 / 14 / 30 days. Clicking a booking opens a side panel with check-in/out and ETA, rooms, transfers, extras and money. Full logic: doc 23. The "By guest" view and the "Change rooms on sale" grid were removed (26 Sep 2026).

### 6. Special prices (`rates.php`)
Replaces the old Rates page. Pick nights, tick cottages, enter the (double) price, **Check**, then **Save**. Guard rails: ≥ ₹500; 25%–400% of normal; tick to confirm below 60% or above 150%; no past dates. Saved prices are listed as date ranges with **Remove** (asks first). The normal price is never changed. Details: doc 21 §4.

### 7. Enquiries (`enquiries.php`) and Export (`export.php`)
Enquiries from the engine's `api/enquiry.php`, with WhatsApp links. Export gives a CSV of bookings.

### 8. No browser pop-ups
Every "are you sure?" (save a change, cancel a booking, remove a special price) is an **on-page box** with Go back and a clear action button (red for cancelling). Browser `confirm()` pop-ups are no longer used. To make any button or form ask first, add `data-confirm="Question?"` (optional `data-ok="Button text"`, `data-danger="1"`). The script is in `admin_foot()` in `admin/_auth.php`.

### 9. Owner notifications
There is no WhatsApp or Telegram bot ("right now no bot"). Confirmation emails BCC the office (`mail.bcc_office`) once mail is set up. Email alerts to the owner were offered and have not been decided yet.
