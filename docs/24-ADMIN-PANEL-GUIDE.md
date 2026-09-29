# Admin Panel Guide

_Written 26 Sep 2026. Every screen in `booking-engine/admin/`, what it does, and the rules behind it._

## 1. Getting in
* Address: `/book/admin/`. Typing **`/book/admin`** (no slash) or the website's **`/admin`** also works. The address fills itself in to `/book/admin/login.php`.
  > Why the redirect exists: without the trailing slash the panel's relative links pointed one folder too high (`/book/login.php`), so the page broke. `admin/_auth.php` now redirects `…/admin` → `…/admin/` first.
* Sign in with a **username** (not case-sensitive). The local test login is `manvir` / `1234`. **Change it before going live** (doc 30).
* The panel **always asks for the password**:
  * the sign-in ends when the browser closes (session cookie, no lifetime);
  * it counts only in the tab it was made in. A new tab or window asks again, via a per-tab `sessionStorage` mark (`ksr_admin_tab`) set after sign-in (`login.php` → `index.php?signed_in=1`);
  * it ends after `admin.idle_minutes` (10) without use.
* Login attempts are limited to `admin.login_attempts` (6) per 15 minutes per IP.
* Create or reset a login: `php bin/setup.php --admin USERNAME "Name" "password"`.

## 2. Bookings (`index.php`)
* Groups, in the order the day at the hotel runs:
  1. **Staying now** (earliest check-out first)
  2. **Coming up** (soonest arrival first)
  3. **Cancelled / not paid**
  4. **Finished** (most recent first)
* Finished and cancelled bookings older than `admin.list_clear_days` (15) drop off the list but are **never deleted**. "Show older bookings" (`?older=1`), a search or a filter brings them back. They are the payment and GST records.
* Rooms are shown grouped by cottage type. "due ₹X" and "refund ₹X" show under the money.
* **Cancel a booking:** type the booking code and it opens that booking at its cancel box.
* UPI payments waiting for a check sit in a box at the top ("Money received" confirms them).

## 3. One booking (`booking.php`)
* Header: status, facts strip, **Receipt (PDF)**, **Change this booking**.
* Table: Room · Cottage · Guests (Single/Double/Triple) · Extra bed · Plan · Nights · Amount (GST included for the resort), then extras and the money rows.
* Money rows follow how the guest pays (doc 22): **Due now** (to make up the 50% or the full amount), **Due before arrival** (50% plan), or **Refund due to guest**.
* Payments: money received, and (folded away) online payment attempts that never completed.
* **Collect the balance** only appears while money is owed; the amount is capped at the balance. **Give back** appears when the guest has paid more than the total. The server refuses payments on cancelled or fully paid bookings.
* **Cancel** (`#cancel`): shows what today's cancellation would charge (from the ladder). Card payments are refunded through Razorpay automatically once it is connected. Guests can't cancel online; they call or WhatsApp.
* After a change: "Old total + added − taken off = new total", then what to collect or give back.
* "Set status" and "Send to Stayflexi" were removed. Stayflexi is updated by itself (doc 27).

## 4. Change a booking (`edit.php`)
Dates, each room's cottage and guests (add a row to add a room; blank a row to remove it), extras (`#extras`).
**Check price** shows **What changes**: Added / Taken off / Changed with an amount each, then Old total, the sums and the **New total**, then **Money**: how the guest pays, paid so far, collect now, rest before arrival, or give back. **Save changes** asks in an on-page box and then writes it. Full logic: doc 22.

## 5. Availability (`calendar.php`)
Tape chart **by cottage** (the only view), 7 / 14 / 30 days. Clicking a booking opens a side panel with check-in/out and ETA, rooms, transfers, extras and money. Full logic: doc 23. The "By guest" view and the "Change rooms on sale" grid were removed (26 Sep 2026).

## 6. Special prices (`rates.php`)
Replaces the old Rates page. Pick nights, tick cottages, enter the (double) price, **Check**, then **Save**. Guard rails: ≥ ₹500; 25%–400% of normal; tick to confirm below 60% or above 150%; no past dates. Saved prices are listed as date ranges with **Remove** (asks first). The normal price is never changed. Details: doc 21 §4.

## 7. Enquiries (`enquiries.php`) and Export (`export.php`)
Enquiries from the engine's `api/enquiry.php`, with WhatsApp links. Export gives a CSV of bookings.

## 8. No browser pop-ups
Every "are you sure?" (save a change, cancel a booking, remove a special price) is an **on-page box** with Go back and a clear action button (red for cancelling). Browser `confirm()` pop-ups are no longer used. To make any button or form ask first, add `data-confirm="Question?"` (optional `data-ok="Button text"`, `data-danger="1"`). The script is in `admin_foot()` in `admin/_auth.php`.

## 9. Owner notifications
There is no WhatsApp or Telegram bot ("right now no bot"). Confirmation emails BCC the office (`mail.bcc_office`) once mail is set up. Email alerts to the owner were offered and have not been decided yet.
