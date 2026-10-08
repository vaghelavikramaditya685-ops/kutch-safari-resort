# Change Log, Lessons, Testing and Go-Live

_Combined on 30 Sep 2026 from 20 earlier files. Start at [`README.md`](../README.md) for the overview and hard rules._

What changed and why, what broke and what we learned, how to test, how to deploy, and the 29 Sep 2026 check reports.

---

## Contents
* [Change Log and Design Decisions](#docs-20) _(was `docs/20-CHANGE-LOG-AND-DESIGN-DECISIONS.md`)_
* [Lessons Learned and Gotchas](#docs-29) _(was `docs/29-LESSONS-LEARNED-AND-GOTCHAS.md`)_
* [Testing and Deployment](#context-testing-and-deployment) _(was `context/testing_and_deployment.md`)_
* [Testing, Go-Live Checklist and Handover](#docs-30) _(was `docs/30-TESTING-GO-LIVE-AND-HANDOVER.md`)_
* [Deployment and Infrastructure](#docs-15) _(was `docs/15-DEPLOYMENT-AND-INFRASTRUCTURE.md`)_
* [Folder cleanup report (29 Sep 2026)](#restructure-report) _(was `restructure/REPORT.md`)_
* [Changelog](#restructure-changelog) _(was `restructure/CHANGELOG.md`)_
* [Move map](#restructure-move-map) _(was `restructure/MOVE_MAP.md`)_
* [Current tree (26 Sep 2026, before the cleanup)](#restructure-current-tree) _(was `restructure/CURRENT_TREE.md`)_
* [Target tree](#restructure-target-tree) _(was `restructure/TARGET_TREE.md`)_
* [Self-healing report (29 Sep 2026)](#heal-report) _(was `heal/REPORT.md`)_
* [Findings](#heal-findings) _(was `heal/FINDINGS.md`)_
* [Changelog](#heal-changelog) _(was `heal/CHANGELOG.md`)_
* [Run log](#heal-run-log) _(was `heal/RUN_LOG.md`)_
* [State](#heal-state) _(was `heal/STATE.md`)_
* [Button, link and page audit (29 Sep 2026)](#button-audit-report) _(was `button_audit/REPORT.md`)_
* [Chaos-monkey report (29 Sep 2026): nonsense input](#chaos-report) _(was `chaos/REPORT.md`)_
* [Findings (before -> after)](#chaos-findings) _(was `chaos/FINDINGS.md`)_
* [Changelog](#chaos-changelog) _(was `chaos/CHANGELOG.md`)_
* [Input map](#chaos-target-map) _(was `chaos/TARGET_MAP.md`)_

---

<a id="docs-20"></a>

## Change Log and Design Decisions
_(was `docs/20-CHANGE-LOG-AND-DESIGN-DECISIONS.md`)_
_Updated 30 Sep 2026. Newest first._

### Change log

#### 30 Sep 2026 (later): docs combined from 70 files into 10
At the owner's request every Markdown file (docs 01–30, docs/notes, the 20 `context/` files, both engine READMEs and the 29 Sep report folders) was merged into `README.md` + `docs/01`–`09`. Each old file is a section headed "(was `old/path.md`)" with an anchor; the table at the bottom of `README.md` maps every old file to its new place. Older text that says "doc 22" or "docs/30" means those sections. The old files are in git history.

### 6 Oct 2026: photos compressed, site redeployed
* **32 photos resized and re-encoded in place** (136.7 MB → 6.4 MB; same names and paths, no code changes). Home images 16.9 MB → 1.65 MB, Stay 44.3 MB → 1.5 MB, Gallery 82.2 MB → 3.96 MB. Details and settings: [docs/03, Image and Asset Inventory](03-DESIGN-AND-ASSETS.md#docs-11). `logo-mark.png` is now 128 px (34 KB) and the `og:image` file is 180 KB.
* Deployed to Vercel with the §2.2 prebuilt method; the live domains `kutchsafariresort.in` and `www.kutchsafariresort.in` serve the new files (both were added to the Vercel project on 5 Oct 2026).
* Not touched: the 10.2 MB Home hero video (now the heaviest download) and the unused files (~122 MB, still in `public/` and uploaded with every deploy). Originals are in git history.

### 5 Oct 2026: website live on Vercel, enquiry instead of Book Now
* **Deployed the website only** to Vercel (project `kutch-safari-resort`, `https://kutch-safari-resort.vercel.app`) with the tested prebuilt method in §2.2. Booking engine and admin not deployed.
* **One switch for online booking:** `BOOKING_ENABLED` in `frontend/src/lib/booking.ts` (`VITE_BOOKING_ENABLED=true` at build time). Off by default: every Book Now / Check Availability is a `ReserveButton` showing **Enquire Now** → `/enquire?room=…`; "Already booked? Check status" links are hidden; `/booking`, `/book`, `/book/*` → `/enquire`; `/admin` → not found. On: everything as before.
* **New `/enquire` page** and **new enquiry form** (`components/EnquiryForm.tsx`, also on the Home contact section): opens WhatsApp (or email) with the enquiry written out. Replaces the Home mock form, which showed "Message sent" and sent nothing (O3).
* Tested locally and on the live URL: buttons, room pre-fill, validation (name, phone, dates), the WhatsApp text, `/book/` → `/enquire`, `/admin` not found, direct loads of `/stay` and `/enquire`.

### 5 Oct 2026: live setup planned, site stays local for now
* Live setup chosen: Hostinger, one plan and one domain: website `kutchsafaribhuj.in`, booking `book.kutchsafaribhuj.in`, admin `admin.kutchsafaribhuj.in` (both subdomains point at the engine folder `public_html/book`). No Vercel.
* **Not applied yet:** the site runs only on the owner's PC, so the code keeps the local `/book/` links. Everything that changes at upload time (build setting `VITE_BOOKING_URL`, the website `.htaccess`, the engine's subdomain rules, the live `config.local.php`, the database link, DNS) is written up in the new [`docs/10-GO-LIVE-ON-HOSTINGER.md`](10-GO-LIVE-ON-HOSTINGER.md). The upload recipe was tested in a scratch folder.
* (Same day, tried and reverted: live URLs in the code, a website `.htaccess`, subdomain rules in the engine `.htaccess`, a root `vercel.json` for a Vercel + Hostinger split.)

### 5 Oct 2026: "Why Visit Kutch?" moved to Around the Resort
* The brochure intro ("Experience Kutch, Where Tradition Meets Wonder", "Why Visit Kutch?" and its four photos) moved from `Experiences.tsx` to `AroundTheResort.tsx`, under "Kutch, from Our Doorstep". Experiences now has the plain title "Experiences". The four photos were then removed at the owner's request (image files kept in `brochure/`).
* The navbar items are spread evenly between the logo and the buttons (`justify-evenly`).

### 5 Oct 2026: Our Journey page removed
* Removed `frontend/src/pages/OurJourney.tsx` (placeholder content only), its route and title, the menu item, the footer "About the Resort" link, the Home "Our Story" link and the sitemap entry. `/our-journey` redirects to `/`.

### 5 Oct 2026: every open bug fixed (B1–B26), errors outside the console fixed (E1–E12), logic and race tests added
* **All of `BUGS.md` B1–B26 fixed.** The table at the top of `BUGS.md` lists each fix and the test that proves it. The main changes:
  * **Cancellations count whole calendar days** (`days_until()`, `cancellation_percent()`), the same as the guest's table.
  * **Payments charge what is due now** (`payable_now()`), never the amount stored at checkout.
  * **Money:**
    * a refund owed after a cancellation is shown and can be recorded (`cancellation_money()`);
    * a late cancellation keeps at most what was paid;
    * after arrival the unpaid part is "Collect at the desk".
  * **Statuses:** **No-show** and **Left early** for stays that have started (statuses `no_show`, `completed`).
  * **UPI:**
    * a waiting UPI payment holds its cottage for 48 hours (`upi.confirm_hours`);
    * cancelling withdraws a waiting UPI QR;
    * money that arrives after the cottage was taken is owed back, and the booking is never sold twice.
  * **Many requests at once:**
    * SQLite waits instead of failing (`busy_timeout`, WAL, `BEGIN IMMEDIATE`);
    * every "check, then write" (payments, cancellations, desk payments and refunds, changes, the rate limit) runs under a lock;
    * a payment is confirmed (and emailed) once, however many requests confirm it.
  * **One admin at a time** (owner's requirement):
    * `admin_lock` and `admin/heartbeat.php`: the panel belongs to the open tab;
    * anyone else is told "in use by … since …";
    * closing the tab frees it in seconds, a crash in 90 s;
    * a second tab no longer signs the first out (`admin/tab.php`).
  * **Other fixes:**
    * 50% and "free cancellation" are only offered while they apply;
    * Stayflexi gets the nightly amounts actually charged, and a junk reply from it is "could not confirm";
    * the rate limit counts the guest's own address behind a proxy (`trusted_proxies`);
    * the lookup is a POST;
    * the contact endpoints check their fields and log no personal details;
    * `setup.php --reset` is all or nothing;
    * the email switch `mail.enabled`;
    * `scripts/legacy/` deleted.
* **Errors outside the console (E1–E12):**
  * unlabelled form fields (website, booking page, admin);
  * heading order and a hidden h1 on every engine page;
  * announced error messages;
  * admin pages no longer scroll sideways on phones;
  * the Node server answers 404/400/405 properly and logs no stack traces;
  * noindex on unknown website addresses, plus canonical addresses;
  * the admin cannot be framed, and the PHP version is no longer sent;
  * unused website code removed;
  * `pnpm-lock.yaml` regenerated (it pinned versions with 39 known advisories; now none).
* **Database additions** (made by `db_upgrade()` on first use, so an existing database needs no script): the table `admin_lock`, the index `idx_audit_rl`, a `db_version` setting, and WAL mode on SQLite. Nothing existing is changed or removed.
* **Tests:**
  * `bin/test-logic.php` (new, 29 checks with made-up bookings and 250 random carts);
  * `bin/test-concurrency.php` (new, 12 race checks with up to 20 processes at the same instant);
  * `bin/test-changes.php` (17/17).
  * All three work on a temporary copy of the database. Live browser checks were made on a throwaway copy; the real database was only read.
* **For the owner (`BUGS.md` "Open"):**
  * the GST slab for a ₹8,000 Deluxe triple (O1);
  * text contrast (O2);
  * the Home contact form is still a mock (O3);
  * placeholder content (O4);
  * per-page descriptions (O5);
  * when the 50% balance is due (O6).

### 4 Oct 2026 (latest): console check, every level on every page
* **Checked:** every console level (errors, warnings, info, logs, debug) and failed network loads.
  * Website: all routes in development (desktop and 375 px, with interactions) and in the production build.
  * Engine: a full booking with the debug bar open, check-status lookups, and the receipt and terms viewers.
  * Admin: every screen, including the previews and Availability side panels.
  * Server consoles: Vite, PHP and Node.
  * A source scan for leftover `console.*` and `debugger`.
* **Result:** the browser console is silent everywhere (development shows only Vite's and React's normal messages; production logs nothing). There are no React warnings and no stray `console.*` or `debugger` in browser code.
* **Fixed:**
  * **C1:** a mistyped check-status lookup returned HTTP 404, which logged "Failed to load resource" in the console. `api/booking-lookup.php` now answers 200 with `ok: false`; the page works the same.
  * **C2:** `/favicon.ico` 404s. The engine now has the website's `favicon.ico`, linked next to `icon.svg`.
  * **C3:** the production server's raw `EADDRINUSE` stack trace became a plain "Port … is already in use" message.
* Noted in `BUGS.md` B24: both contact endpoints `console.log` the full enquiry, including personal details.
* Pricing tests 17/17; `tsc` and the build pass. The real database was only read.

### 4 Oct 2026 (later still): runtime check, every code path executed
* **What ran:** the paths that never run on this PC, switched on in a throwaway copy with Razorpay, Stayflexi and email pointed at local fakes, plus all 8 CLI and cron scripts.
  * Server side: 69 HTTP requests (Razorpay order, verify, replays, webhook and refunds; Stayflexi down, sold-out and junk replies; pay-at-hotel; White Rann Camp; discount codes; admin refunds and the sign-in lockout).
  * Browser: the guest booking page's JavaScript (Razorpay checkout, UPI QR, three rooms, sold-out suggestions, network-failure retry, remembered booking), check-status states, the PDF viewer and admin scripts.
  * Website: interactions and media paths, `pnpm start` and the Vercel function.
* **Fixed:**
  * **R1 `setup.php`:** unknown options are refused; there is no full reload over bookings without `--reset`; a half-failed reload says FAILED, not "Ready".
  * **R2 Razorpay refunds:** they now update `amount_paid`.
  * **R3 email:** encoded subject and sender name, base64 body, `Date` and `Message-ID` headers, and the client stops if STARTTLS fails instead of sending the password in plain text.
  * **R4 `pnpm start`:** works on Windows.
* **Logged in `BUGS.md`:** B20 (a second admin tab signs out the first), B21 (multi-room Continue silently does nothing), B22 (status lookup puts phone/email in the URL), B23 (junk Stayflexi reply shown as "just taken"), B24 (contact endpoints accept empty posts), B25 (legacy Python scripts can't run; delete), B26 (`--reset` half-applies with bookings).
* After the fixes: 0 PHP warnings or errors, 0 JavaScript errors, all expectations met; pricing tests 17/17; `tsc` and the build pass. The real database was only read.

### 4 Oct 2026 (later): debug-mode and syntax check of everything
* **Syntax: no errors in any file.** 260 tracked files were run through a real parser for their type: PHP lint, `tsc` plus the production build, Python compile, `node --check`, JSON/YAML/XML/HTML parsers, lightningcss, SQL loaded into a fresh database, and every image and video opened. Full breakdown in `BUGS.md` → "4 Oct 2026".
* **Debug mode:**
  * 143 requests against a throwaway copy, read through the debug bar's notice list, the API `_debug` block and PHP's error log.
  * All 23 website routes and the full guest booking flow, with the browser console watched.
* **Fixed:**
  * **F1, list-instead-of-text input** (21 places): 8 caused HTTP 500 crashes (public booking API, booking page, status page, admin list, CSV export, UPI confirm), and 5 saved the word "Array" as data. Fields are now made text at the boundary: `$_GET` in `lib/db.php`, admin `$_POST` in `admin/_auth.php` (except `room`, `addon`, `plans`), and the cart and guest fields in `quote_cart()` and `create_booking()`.
  * **F2, the arrival wheel's uncaught JS error** after leaving the details step (`assets/engine.js` `show()` guard).
  * My own debug bar now shows `lib/db.php:42` rather than full Windows paths.
* **Found, not fixed:**
  * **B18:** UPI "Money received" re-confirms a cancelled booking.
  * **B19:** emails add about 2 s per action on this PC only.
  * Both are in `BUGS.md`.
* Pricing tests still 17/17. The real database was only read: 7 bookings, 8 payments, no "Array" values.

### 4 Oct 2026: debug bar
* **`lib/debug.php`**: a dev-only bar on the booking page, check-status page and admin pages showing config flags, the live cart, every API call with timings, and PHP notices (plus JS errors). See doc 01 for what it shows.
* **Local only by construction:** `debug_visible()` = `cfg('debug')` **and** REMOTE_ADDR in 127.0.0.1/::1. The `_debug` key added to API responses follows the same rule, so a server with debug left on still leaks nothing to guests.
* Hooked in at `index.php`, `manage.php`, `admin/_auth.php` (end of `admin_head`) and `api/_init.php` (`json_out`); `assets/engine.js` publishes the cart through `window.KSR_DEBUG.cart()`. Receipt/terms pages (`document.php`) are deliberately left alone.
* The bar reserves page space while open, so it never sits on top of the Continue button.
* `@`-silenced errors are ignored, because the engine silences things it has already handled (`@mkdir`).
* Debug mode itself was already on locally (`config.local.php` → `debug => true`); nothing about that changed.
* Checked: booking page through to the payment step, check-status page, admin sign-in page, API `_debug` payload, notice capture, and that a non-localhost address sees nothing. `bin/test-changes.php` still 17/17.

### 30 Sep 2026: new welcome text, Experiences from the brochure, "Around the Resort" tab, docs refresh
* **Home welcome** replaced with the owner's text (kicker "Welcome to Kutch Safari Resort", heading "Where Tradition Meets Comfort on the Road to the White Rann", three paragraphs). Doc 05 §2.3.
* **Experiences rebuilt from the owner's brochure** ("KUTCH SAFARI RESORT 2026 2027.pdf"): Why Visit Kutch?, Guest Experiences, Arrangements on Request, Other Assistance, "Arrange an Experience" on WhatsApp. 11 photos extracted from the PDF into `frontend/public/assets/images/brochure/`. Doc 10.
* **New page and menu item "Around the Resort"** (`/around-the-resort`, `AroundTheResort.tsx`): the six places (White Rann, Road to Heaven & Dholavira, Banni, Kala Dungar, Mandvi Beach, Bhuj & Bhujodi) → destination guides. The owner asked for places and experiences to be separate. Home "Explore Kutch", the destination back links, the Footer, the sitemap (16 URLs) and page titles were updated.
* **Menu breakpoint:** with 10 items the menu wrapped at 1,024–1,440 px; the full menu now shows from 1,320 px (wider header box, tighter spacing, no wrapping), ☰ below. Doc 14 §4a.
* **Brochure vs website differences** (distances, 34 vs 35+ years, room names) were reported to the owner, not changed. Doc 17.
* **Docs:** every Markdown file refreshed so a new developer or AI reading them gets the current state.

#### 29 Sep 2026: folder cleanup, self-healing, button pipeline, chaos monkey, SHA-256 sign-in, demo data, context files
* **Folders:** `client/` → `frontend/`, `booking-engine/` → `backend/booking-engine/`, `server/` → `backend/server/`, old `*.py` scripts → `scripts/legacy/`, notes → `docs/notes/` ([`../restructure/REPORT.md`](#restructure-report)).
* **Self-healing** ([`../heal/REPORT.md`](#heal-report)): 3 problems, 0 errors/warnings left — unused 12.8 MB video preload (console warning), nested links on destination/camp pages (console error), `/favicon.ico` 404 (real 9 KB favicon + engine icon).
* **Earlier check's 5 findings fixed:** cash at the desk confirms a pending booking; payment start needs the private code and an unpaid booking; `check-system.php` uses a temporary copy and the property on sale; CSV follows the search box; unused status filters removed. Engine script/styles are versioned so browsers pick up updates.
* **Button pipeline** ([`../button_audit/REPORT.md`](#button-audit-report)): 5 broken links + 6 dead Home cards fixed, invisible tariff button, booking page too wide on phones, accessibility (names, alt text, one `h1`, zoom), per-page titles, full sitemap, robots, stack trace hidden from visitors.
* **Chaos monkey** ([`../chaos/REPORT.md`](#chaos-report)): 107 nonsense values, 33 wrongly accepted → 0. Shared checks in `lib/db.php` (lengths, phone, arrival time, real dates), extras 1–50, bookings up to 2 years ahead, enquiry and admin form checks, red error messages, CSV formula protection, clearer messages; the booking page checks phone/email itself and keeps the guest's typing on a refusal.
* **Admin sign-in with SHA-256** (owner's request): the page sends SHA-256 of username and password; stored hash is bcrypt(SHA-256). The `manvir` login was re-saved in this format (same password).
* **Demo data:** six marked demo bookings added at the owner's request (doc 28 §3).
* **[`context/`](../README.md#context-overview):** 20 short context files for new developers and AI agents, pointing back to these docs.

#### 26 Sep 2026 (later): Availability by cottage only, tidier side panel, local defaults
* **Availability is by cottage only.** The "By guest" / "By cottage" buttons and the by-guest layout were removed. Clicking a booking bar opens the side panel (it no longer leaves the page; Ctrl/Cmd-click still opens the booking page).
* **Side panel tidied:** no car emoji anywhere; "ETA: 4:30 PM" instead of "arriving about…"; rooms as bullet points ("Kutchi AC Cottage Room 1 · Double · 2 guests"); car transfers listed once under **Transfers** by full name ("Airport transfer, one way — Sedan × 2"), not repeated under Extras.
* **Local defaults:** `config.php` now defaults to SQLite (the live server's `config.local.php` must set `mysql`), and the resort's "Back to website" (`properties.website_url`) is `http://localhost:3000` in the local database and in `seed.sql`. **Change both for the live site** (doc 30 checklist).

#### 26 Sep 2026: change pricing, money by payment choice, admin polish (pushed to GitHub)
* **Changing a booking is priced as a difference** (`modification_delta()`): new total = old total + added − taken off ± changed. Everything the guest keeps keeps exactly its booked amount (a share per night where special prices differ). New nights, rooms, occupancy and extras are priced at today's prices. A percent discount code moves only with rooms added or taken off. The preview lists each item under **Added / Taken off / Changed** with its amount and writes out the sum. The breakdown is saved with the change and shown on the booking page afterwards. Before, the whole cart was re-priced: adding a ₹7,450 room to a ₹22,000 booking gave ₹30,050.
* **Money follows how the guest pays** (`booking_money()`): Due now (to reach 100%, or 50% on the 50% plan), Due before arrival (the rest on the 50% plan, as guests are told), or Refund due. Used in the change preview, the booking page and its Collect form, the Availability panel, the check-status page (`due_now`, `due_later`) and the receipt.
* **`amount_paid` = payments − refunds everywhere:** online settlement and UPI confirmation used to ignore earlier refunds.
* **No browser pop-ups:** confirmations are an on-page box (`data-confirm`), red for cancelling.
* **Arrival time is a scroll wheel** (hour · minutes · AM/PM). Nothing is saved unless the guest turns it.
* **`/book/admin` works as typed:** redirects to `/book/admin/` and then `login.php`, so the address fills itself in.
* **Availability:** "Change rooms on sale" removed. Clicking a guest in either view (later: cottage view only) opens the side panel, now with where they are in the stay (arrives in N days / staying now / leaves today), check-in and check-out, **which car** (Sedan / Ertiga / Innova), guests per room, and money by payment choice.
* **Test bookings removed** from the real database at the owner's request (KSR-2WQG3X, KSR-3E95RT, KSR-RKJL4F with their rooms, extras and payments). 0 bookings now.
* **`bin/test-changes.php`**: 17 checks on a temporary copy of the database.
* **Docs:** all 20 updated; 10 new (21–30) on pricing, changes and money, availability, admin, guest flow, payments, Stayflexi, database rules, lessons learned, and testing/go-live.

#### Booking engine: mixed cottages, receipts, check status (not yet committed)
* **White Rann Camp is switched off in the engine** for now (`properties.active = 0`, also in `seed.sql`). Its tents, prices and peak dates are kept; the booking page, availability, pricing and terms for it are refused, and it is hidden from the admin. To sell it again, set `active` back to 1.
* **Changing a booking keeps what was agreed:** nights the guest already has keep their booked price (even if the tariff or a special price has changed since), extras already on the booking keep their booked unit price, and the discount code stays on even if it has since expired or been used up (`pricing_locks()`, `keep_coupon`). Only new nights, cottages, extra beds and extras are priced at today's rates. Seven "only what changed changes" tests pass. _(Superseded on 26 Sep 2026 by the difference method above; the locks are still used to price new nights.)_
* **Availability by guest (default):** one row per guest in arrival order, bar from check-in to check-out, full page width. Clicking a guest opens a side panel with contact, times, rooms and occupancy, extras, airport pickup, and total/paid/due, plus Change booking, Add airport pickup, Collect payment, Open booking, Call and WhatsApp. "By cottage" is the other tab.
* **Availability is one calendar** (`admin/calendar.php`): rows are cottages (Kutchi 1–12, Deluxe 1–8…), columns are days, and each day is a full 24 hours. Every booked room is a bar from check-in (12:00) to check-out (10:00), so turnovers share a row. Each room type's bookings are laid into non-overlapping rows on the server; extra bookings go into a red "Overbooked" row. It shows free counts per night, arrivals (↓) and departures (↑) per day, hatched "not on sale" nights and a "now" line. Hover a bar for guest, code, room and occupancy, times and money; hover a day for all its check-outs, check-ins and stays; click a bar to open the booking. The rooms-on-sale grid was under "Change rooms on sale" (removed 26 Sep 2026).
* **Special prices** (`admin/rates.php`, menu "Special prices"): pick nights, tick cottages, enter a price. The normal price is never changed, and each night of a stay is priced on its own. Guard rails: at least ₹500, 25%–400% of normal, a confirm tick below 60% or above 150%, no past dates, a check step before saving. Saved prices are listed as date ranges with Remove.
* **Similar rooms together** everywhere rooms are listed: grouped by cottage type in the site's order, then room number (`get_booking()`, admin pages, receipt, email, check-status page, booking summary).
* **Receipts are never stored:** every Receipt (PDF) is generated on the spot from the booking's current details.
* **Bookings list reads like the day at the hotel:** Staying now (earliest check-out first) → Coming up (soonest arrival first) → Cancelled / not paid → Finished (most recent first). It is sorted every time it opens. Finished and cancelled bookings older than `admin.list_clear_days` (15) drop off the list but are **never deleted**: "Show older bookings", a search or a filter brings them back. They are the hotel's payment and GST records.
* **Owner can change a booking.** Admin → open booking → **Change this booking** (`admin/edit.php`): dates, each room's cottage and occupancy, add or remove rooms, extras. **Check price** (`quote_modification()`) shows what changes and what to collect or give back, with the booking's own rooms left out of availability (`availability_ignore_booking()`). _(Since 26 Sep 2026 it is priced as a difference, see above.)_ **Save changes** (`modify_booking()`) rewrites the rooms and extras, logs old → new, and re-sends to Stayflexi. The booking page then offers **Collect the balance** or **Record a refund** (`record_offline_refund()`); `amount_paid` is payments minus refunds (`refresh_amount_paid()`). Guests see the new details, "Updated by the resort", and balance or refund due on the check-status page and receipt. The desk may change a stay that has already started, and isn't bound by the online room limit.
* **Admin booking page** keeps the table, now with Guests (Single/Double/Triple) and Extra bed columns per room, amounts as quoted (GST included), refund due, and abandoned online payment attempts listed apart from money received. The bookings list shows "refund ₹X" when money is owed back.
* **Admin always asks for the password.** The sign-in ends when the browser closes (was 8 hours), counts only in the tab it was made in (a new tab or window asks again, via a sessionStorage mark set after sign-in), and ends after `admin.idle_minutes` (10) without use.
* **Fully paid means nothing to collect.** The admin "Collect the balance" form only appears when money is still due, and the server refuses payments on cancelled or fully paid bookings, or above the balance. Test payments read "Test payment — no money taken".
* **Stayflexi updates by itself.** The admin booking page no longer has "Set status" or "Send to Stayflexi". A booking is sent when it is paid, and cancelled in Stayflexi when the admin cancels it. A failed Stayflexi cancel is now saved on the booking and retried hourly by `bin/retry-failed-sync.php` (before, it was only logged). That job now re-sends only confirmed (paid) bookings, never unpaid ones.
* **Pay 50% or in full.** "Pay at the property" is off. Unpaid bookings stop holding rooms once the payment window closes (`rooms_booked()`), and show as "not paid" on the check-status page and receipt. Before this, an abandoned checkout blocked its rooms forever.
* **Razorpay:** the live Key ID is in `booking-engine/config.local.php` (git-ignored). Razorpay stays off until the Key Secret is added there.
* **Only the admin can cancel.** Guests cannot cancel online (`api/booking-cancel.php` now refuses). The check-status page shows the charges and "call or WhatsApp us with your booking code". In the admin panel, **Bookings → Cancel a booking** takes the booking code and opens that booking at its cancel form, which shows the charge if cancelled today; card payments are refunded through Razorpay automatically when it is connected. The terms say cancellations are made by the reservations team.
* **Different cottage per room.** With several rooms, each cottage card has one **Select** button that opens its room numbers (Room 1 · Double, Room 2 · Single…) to tick. A room already given to another cottage is hidden until it is unticked there. A "N of M rooms chosen · Continue" bar sits under the list. The server sends one booking line per room and checks availability **across** lines (two Deluxe rooms need two free Deluxe).
* **Receipt and terms as PDF, in a new tab.** `lib/pdf.php` is a small built-in PDF writer (no library to install). `lib/documents.php` builds the receipt (guest, stay, rooms with occupancy, extras, totals, payments, this booking's cancellation dates, terms) and the terms on their own. `document.php` shows either one in the browser tab with PDF.js, with Print and Save as PDF, so it never downloads. Raw PDFs: `receipt.php?ref=&token=` (guest token or signed-in staff) and `terms.php?property=`.
* **Terms** are written only from the settings (check-in/out, cancellation ladder, payment options, GST, online limits) plus `config.php` → `terms.house_rules`, where the property adds its own rules.
* **"Already booked? Check status"** on the website (top bar, mobile menu, home hero, footer) and the booking page header, going to `manage.php`: find by code + mobile/email, then Receipt (PDF), Terms (PDF), Call. A `?ref=&token=` link opens it with no typing.
* **Refresh-proof confirmation:** the booking page remembers the guest's last booking on their device and shows "You booked KSR-… · Receipt (PDF) · Check status" until the stay is over.
* **Payment step in test mode** shows the Razorpay and UPI QR options (greyed out as "not connected yet" until their keys / UPI id are set) with the I've paid (test) button.
* **Fixed:** cancellation dates were labelled with the day a charge period ends instead of when it starts, and past periods still showed. `cancellation_schedule()` now returns `from`/`to`/`past`, used by every screen and the receipt. The check-status page now shows GST-inclusive amounts like the tariff.

#### Booking engine: tariff, occupancy, extras, finish (after 25 Sep 2026, not yet committed)
* **2026–27 resort tariff:** breakfast plan only. Deluxe ₹5,500 / ₹6,500, Kutchi ₹6,500 / ₹7,450 (single / double), extra bed ₹1,500, **GST included** (`prices_include_tax`). MAP/AP plans removed.
* **Per-room occupancy:** the guest picks Single / Double / Triple for each room. New `price_rooms()` in `lib/inventory.php` is the single pricing function for both the room list and checkout. New columns: `rate_plans.single_price`, `addons.min_quantity`; `booking_rooms.rate_plan_name` widened to 255.
* **Extras:** candlelight dinner ₹3,000 per person; gala dinner ₹1,500 per person, minimum 10; airport transfers by vehicle (Sedan / Ertiga / Innova), shown as one Airport transfer card with a counter per car (any add-ons named "Group — Choice" group this way). The extra-bed add-on was removed; Triple replaces it. The per-room add-on picker code stays for any future "one per room" extra.
* **Fixed:** guests were spread over room types instead of rooms (4 adults in 2 rooms were charged as 4 per room); check-out was set to check-in on Indian time; the summary didn't add up; the sidebar overlapped while scrolling.
* **UX:** the engine opens with dates filled in and rooms loaded; the search re-runs on any change; each step scrolls back up so the dates, rooms and guests stay visible; clearer buttons; matte/smooth finish on the site and engine.

#### 25 Sep 2026: booking engine replaced
* **Removed** the React/Express booking engine: `client/src/booking/`, `client/src/pages/BookingPage.tsx`, `shared/booking/`, `server/booking/`, the Vite `bookingApiDev` plugin, and the `@shared` alias.
* **Added** `booking-engine/`, a standalone PHP 8 + MySQL engine (search → room → extras → pay, Razorpay + UPI QR, admin panel, guest self-service cancellation, Stayflexi bridge, per-night GST slabs).
* **Wired it in:**
  * `client/src/lib/booking.ts`: `BOOKING_URL` (`VITE_BOOKING_URL` or `/book/`) and `bookingUrl()`.
  * Every Book Now link (Navbar ×2, Home ×3, Stay) → engine.
  * `/booking`, `/book`, `/book/*` → `BookingRedirect` (forwards, or shows call/WhatsApp if the engine isn't hosted there).
  * Vite dev proxy `/book/` → `localhost:8080`. New scripts `pnpm dev:book` and `pnpm setup:book`.
  * Engine `allowed_origins` += kutchsafaribhuj.in, www, the Vercel preview, localhost:3000.
  * Local `booking-engine/config.local.php` (SQLite, git-ignored).
  * `.gitignore`: root `data/` → `/data/`. Engine keeps `data/.htaccess` tracked.
  * `.vercelignore` and `.prettierignore` exclude `booking-engine/`.
* All 20 docs rewritten to match the code.

#### Earlier (before 25 Sep 2026)
* Redesign to "Sundown Terracotta" with the owner's beige `#f8f5e2`. Multi-page structure (Stay, Experiences, Our Journey, Dining, Gallery, Plan Your Visit, Packages).
* The Weddings page was removed. `/experiences` was added.
* Rooms.tsx was replaced by Stay.tsx (masonry kept at the owner's request).
* A React booking engine was built (now removed, see above).

---

### Owner directives (Mike Vaghela)
1. **"Changes that are told to you in the PDF. Don't change anything else."** Stick to the provided copy and layout.
2. **"Integrate the beige colour into the entire website."** `#f8f5e2`, taken from the logo.
3. **"Where the lake meets the desert, it should be white."** Drives the hero copy and imagery.
4. **"Keep the old masonry style for cottages."** Stay uses a two-column exterior/interior masonry.
5. Keep the offline enquiry route next to online booking.

### Technical decisions

#### Why the booking engine is PHP and separate
The engine was built for a cPanel host, where PHP + MySQL run with nothing to configure and the owner can edit files directly. The previous React engine stored reservations in a JSON file, which can't persist on Vercel. Keeping the engine as its own app at `/book/`:
* lets it serve both properties (and a future separate White Rann Camp site) from one install;
* keeps payment keys and guest data off the static front-end host;
* means the React site only needs a link (`bookingUrl()`), with no shared code to keep in sync.

**Trade-off:** two stacks and two styles, and prices shown on marketing pages are separate text from the prices charged.

#### Why a redirect route instead of removing `/booking`
Old links, bookmarks and ads may point at `/booking`. `BookingRedirect` forwards them. If the engine isn't deployed on that host, it shows the reservations phone and WhatsApp instead of a redirect loop.

#### Why `VITE_BOOKING_URL`
The site is on Vercel and the engine needs a PHP host. One build-time variable lets the engine live on the same domain (`/book/`, the default) or anywhere else, without code changes.

#### React + Vite + wouter
Fast builds. wouter (~1.5 KB) is enough for flat routes.

#### Tailwind v4 (config-less)
Theme lives in CSS (`@theme inline` + `:root` tokens).

#### Logo `mix-blend-darken`
The logo is a JPG with a white background. Blending on the beige hides the white without a transparent PNG.

#### Why a change is priced as a difference
The owner reads a change the way the desk does: "this was added, that was taken off". Re-pricing the whole booking moved amounts the guest had already agreed to (GST split, single discount, special prices that have ended), so the new total couldn't be explained line by line. Pricing only the difference makes every rupee traceable to an item.

#### Why the 50% balance says "before arrival"
The payment option shown to guests says "Balance due 30 days before arrival". Screens and receipts use the same words so the guest and the desk see the same thing. Changing when it is due means changing that note and those labels together.

#### Why no browser pop-ups
The owner found them jarring ("shit like this… the browser pop up"). An on-page box matches the design, can use a red button for destructive actions, and says exactly what will happen.

#### Why Experiences and Around the Resort are separate pages
The owner asked for it (30 Sep 2026): **Experiences** is what the resort itself offers, taken word for word from the brochure; **Around the Resort** is the places to visit in Kutch (Mandvi Beach etc.), each with its own guide. Mixing them made the brochure content hard to find. Both are in the top menu, which is why the full menu now needs 1,320 px.

#### Why brochure facts weren't "corrected" on the site
Directive 1 ("don't change anything else"). Where the brochure and the site disagree (doc 17), both are reported to the owner and nothing changes until they choose.

#### Express + JSON enquiries
`POST /api/contact` writes `data/enquiries.json`. This only works on a host with a disk. The engine's `api/enquiry.php` (database-backed, visible in admin) is the better target for the Home form.

---

<a id="docs-29"></a>

## Lessons Learned and Gotchas
_(was `docs/29-LESSONS-LEARNED-AND-GOTCHAS.md`)_
_Written 26 Sep 2026. Problems hit while building and fixing this project, why they happened, and what was done. Read this before changing the engine. Most of these are not obvious from the code._

### Pricing and money
| Problem | Cause | Fix |
|---|---|---|
| 4 adults in 2 rooms were charged as 4 per room | Adults were divided by the number of booking **lines**, not rooms | Per-room occupancy; one pricing function `price_rooms()` (doc 21) |
| Room list and checkout disagreed | Two separate price calculations | Everything goes through `price_rooms()` |
| Summary on the booking page didn't add up | Screen re-added figures itself | It shows the quote's own figures, GST-inclusive |
| Changing a booking re-priced **all** of it at today's rates and dropped expired codes | `quote_cart()` re-ran on the whole cart | First, price locks (`pricing_locks()`, `keep_coupon`). Then, on 26 Sep 2026, the **difference method**: old total + added − taken off (doc 22) |
| Adding one ₹7,450 room raised a ₹22,000 booking to ₹30,050 | Re-pricing moved kept rooms (GST split, single discount) | Kept rooms keep their stored amount |
| "Collect ₹33,300" on a change worth +₹2,100 | The preview showed total − paid, mixing in the old unpaid balance | Separate "What changes" and "Money" blocks, with collect now / before arrival from the payment choice |
| A night came out at ₹7,450.01 | Tax and pre-tax each rounded separately | Round the amount first, then the GST inside it |
| Refunds were forgotten when a later online payment settled | `settle_payment()` and `upi_mark_received()` summed payments only | Both use `refresh_amount_paid()` (payments − refunds) |
| Deluxe triple ₹8,000 fits neither GST slab | Inclusive prices between ₹7,875 and ₹8,850 | Uses 18%. **Accountant to confirm** (doc 08 §5) |

### Availability
| Problem | Cause | Fix |
|---|---|---|
| Abandoned checkouts blocked rooms forever | Every `pending` booking counted | Unpaid bookings stop counting 45 min after creation (doc 23) |
| Two rooms of one type could both take the last free cottage | Availability was checked per line | Checked cumulatively per type, and re-checked under a lock |
| Moving a stay by one night was refused | The booking collided with itself | `availability_ignore_booking()` while changing |

### Dates and time
* **Check-out equal to check-in after midnight:** `toISOString()` gives UTC. India is +5:30, so between 00:00 and 05:30 the date was a day behind. The browser code uses local dates (`isoLocal()`, `todayISO()`).
* **Cancellation dates were labelled with the day a period ends**, and past periods still showed. `cancellation_schedule()` returns `from` / `to` / `past`.
* PHP runs in `Asia/Kolkata` (`config.php → timezone`). Keep the server's `php.ini` timezone the same.

### Local setup on Windows
* **Rooms didn't load (spinner forever):** the Vite proxy used `localhost`, which resolved to IPv6 `::1`, but PHP listened on IPv4 only. The proxy target is `http://127.0.0.1:8080`.
* **Razorpay "HTTP 0":** PHP on Windows has no CA bundle. `curl_trust_system_certs()` uses the Windows certificate store.
* PHP 8.3 is installed with winget. `php.ini` needs `pdo_sqlite`, `sqlite3`, `curl`, `mbstring`, `openssl`, `fileinfo`, and `date.timezone = Asia/Kolkata`.
* Long scratch paths and shell quoting broke inline scripts. Edits were written as small Python/PHP files instead.

### Admin panel
* **`/book/admin` (no slash) broke the page:** relative links resolved one level too high. `_auth.php` now redirects to `/book/admin/`, and the address fills itself in to `…/admin/login.php`.
* **"Why did it sign me in automatically?":** the session lasted 8 hours across tabs. Now the password is asked for in every new tab or window, after the browser closes, and after 10 minutes idle.
* **Browser pop-ups** (`confirm()`) looked out of place and the owner didn't want them. They are replaced by an on-page box (`data-confirm`, doc 24 §8).
* **A cash form showed on a fully paid booking:** it is now shown only while money is owed, and the server refuses overpayment.
* Owner feedback that shaped the screens: room-by-room tabs → a Select button with room ticks; a card layout → "the old table style was good"; a calendar by cottage → by guest/date, full width; "Change rooms on sale" → removed.

### Front end
* **wouter route props** gave a TypeScript error. The redirect routes use children render functions.
* **Sticky elements overlapped on scroll:** the whole side panel is sticky, not parts of it, and the room "Continue" bar is not sticky.
* **Header buttons overflowed** on mid-size screens. The engine header wraps below 960 px, and admin nav items use `flex: auto`.
* **A button with the `hidden` attribute still showed:** `.btn { display: inline-flex }` beats the browser's `[hidden]` rule. Add an explicit `[hidden] { display: none }` for such buttons (see the arrival wheel's Clear).
* Headings ignore a parent's text colour (`index.css` base rule). Put the colour on the heading (doc 04 §3).

### Found on 29 Sep 2026 (self-healing, button pipeline, chaos monkey)
| Problem | Cause | Fix |
|---|---|---|
| Favicon 404 on every engine page, even after adding an icon | this browser ignores data-URI SVG icons and asks for `/favicon.ico` anyway | a real `/favicon.ico` at the website root (where the engine's `/book/` pages get it in production) + `assets/icon.svg` |
| Old `engine.js` kept by browsers after an update | no version on script/style URLs | `?v=<filemtime>` on engine.js/engine.css/admin.css |
| Section jumps "didn't work" in a test browser | smooth scrolling never animates in a pane that isn't painting | jumps use `behavior: "instant"` (better for visitors too) |
| Booking page 389 px wide on a 375 px phone | a `white-space: nowrap` header button | header buttons wrap below 960 px |
| 1,000,000 cars → ₹2.1 billion quote; 0 or −5 cars → 1 car | "forgiving" code: `max(1, (int) $qty)`, no upper limit | refuse 0/negative/words; cap at `rules.max_extra_quantity` (50) |
| 200,000-character notes saved | SQLite has no length limits; MySQL would crash | `LIMITS` + `too_long()` everywhere, `maxlength` on fields |
| `check-system.php` put a booking in the real DB and tested a switched-off property | written before the rules | runs on a temporary copy; uses the property on sale |
| A guest-detail error sent the guest back to step 1, losing their typing | error path always went to the rooms step | `field: "guest"` keeps them on the details step with their input |
| Admin errors looked like successes | one green message box for everything | red for refusals |
| Test sessions "failed" mid-run | the 10-minute idle sign-out (working as designed) | sign in again in long scripts |

### Process rules the owner set
* **Don't push to GitHub** unless asked. The one push was on 25 Sep 2026; everything since is uncommitted.
* **No synthetic data** in the real database. Test on throwaway copies (doc 28 §3, doc 30).
* Keep the colours. Only the finish changed (matte and smooth).
* No bot notifications for now.
* Secrets only in `config.local.php`. Copies given to others leave the Razorpay keys out.

---

<a id="context-testing-and-deployment"></a>

## Testing and Deployment
_(was `context/testing_and_deployment.md`)_
### Rule
Never test on the real database (`backend/booking-engine/data/booking.sqlite`); use a temporary copy [DOC owner rule].

### Existing tests
| What | Command | Covers |
|---|---|---|
| Change pricing & money | `php backend/booking-engine/bin/test-changes.php` | 17 checks on a temp copy (add/remove, dates, occupancy, special prices, coupons, 50%/full, payments − refunds) [CODE] |
| Logic & money rules | `php backend/booking-engine/bin/test-logic.php` | 29 checks with made-up bookings on a temp copy: cancellation days and charges, what is payable, refunds, payments after a cancellation, no-shows, UPI waits, the receipt, Stayflexi replies, `setup --reset`, 250 random carts that must add up [CODE] |
| Races (many at once) | `php backend/booking-engine/bin/test-concurrency.php` | 12 checks, up to 20 processes at the same instant on a temp copy: last cottages, many dates, payments, confirmations, desk payments, cancellations, cancel vs pay, desk changes, rate limits, the one-admin lock [CODE] |
| System readiness | `php backend/booking-engine/bin/check-system.php` | search, quote, create/lookup/cancel (temp copy), overbooking, payments/Stayflexi config [CODE] |
| TypeScript | `pnpm check` | website types [CODE] |
| Build | `pnpm build` | Vite + esbuild bundle [CODE] |
| PHP lint | `php -l` on each file | syntax [CODE practice] |
Flow scripts used on 29 Sep (59 guest/admin actions over HTTP) and the chaos scripts live in `chaos/EVIDENCE/` [CODE]; they target a throwaway engine copy on port 8090.

### Coverage gaps
No automated browser tests in the repo (browser checks were done by hand/agent on 29 Sep and 4–5 Oct); no CI [CODE: no CI config]; no unit tests for the website [CODE]. The race tests run on SQLite only; MySQL under load is untested.

### Environments
* **Local:** this PC, SQLite, test keys [CODE].
* **Website live:** Vercel `kutchsafaribhuj.in` [DOC].
* **Engine live:** [PLANNED] a PHP + MySQL host at `/book/`.

### Deployment (short; full list in docs/30)
1. Host: upload `backend/booking-engine/` → `public_html/book` (with `.htaccess`).
2. MySQL: create DB, `php bin/setup.php` **once**, then `--admin` with a long password.
3. `config.local.php` on the server: `db.driver = mysql`, live Razorpay (regenerated secret) + webhook, `debug=false`, `guest_details_optional=false`, `test_payments.enabled=false`, `base_url`, `properties.website_url` → live site.
4. Website: build with the right `VITE_BOOKING_URL` or proxy `/book/` to the PHP host.
5. Cron when Stayflexi is on: `sync-inventory.php` (10 min), `retry-failed-sync.php` (hourly).
Monitoring: `audit_log` (`mail_failed`, `stayflexi_*`, `login_failed`) [CODE]. Rollback: git snapshot branches (`restructure/…`, `heal/…`) and a DB backup before changes [CODE].

---

<a id="docs-30"></a>

## Testing, Go-Live Checklist and Handover
_(was `docs/30-TESTING-GO-LIVE-AND-HANDOVER.md`)_
_Written 26 Sep 2026. How to check the engine safely, what to switch before real guests, and where everything is._

### 1. Testing without touching real data
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
* **`php bin/test-logic.php`** (since 5 Oct 2026): 29 checks of every money and state rule with made-up bookings, plus 250 random carts whose sums must add up (and 25 of them saved and compared with their quote). Same temp-copy rule; exits 1 on a failure. It also prints an INFO line about the GST slab of GST-inclusive prices between ₹7,876 and ₹8,850 (a question for the accountant, not a failure).
* **`php bin/test-concurrency.php`** (since 5 Oct 2026): 12 race checks. Each starts up to 20 PHP processes, each from its own address, that act at the same instant on the same cottage, booking or payment. It then checks the database: nothing sold twice, nothing paid twice, one confirmation and email, one cancellation, nobody's details mixed up, rate limits held, one admin. Same temp-copy rule; takes about 40 seconds.
* **`php bin/check-system.php`**, **`check-razorpay.php`**, **`check-stayflexi.php`**: health checks (config, database, keys, connections). Since 29 Sep 2026 `check-system.php` tests the property that is on sale and runs its create/look-up/cancel test on a **temporary copy** of the SQLite database (on MySQL it skips that test unless `--write-test` is given).
* The flow and nonsense-input scripts used on 29 Sep 2026 are kept in `../chaos/EVIDENCE/`. They drive a throwaway engine copy on another port; see [`../chaos/REPORT.md`](#chaos-report).
* **Screens:** use the browser with test payments on. To look at an admin screen with made-up data, render it against a throwaway copy, never the real file, and delete the copy after.
* **Test bookings made by hand** in the real database are real rows. Delete them afterwards (Admin can cancel; full deletion is by request, as was done on 26 Sep 2026).

### 2. Local start (this PC)
```bash
pnpm dev:book     # PHP engine on 127.0.0.1:8080 (must be 127.0.0.1, not localhost)
pnpm dev          # website on :3000, /book/ proxied to the engine
```
* Site: http://localhost:3000
* Booking: http://localhost:3000/book/
* Admin: http://localhost:3000/book/admin (fills itself in)

### 3. Go-live checklist
**Must do**
- [ ] `rules.guest_details_optional => false` (sample mode off: name and phone required)
- [ ] `test_payments.enabled => false` (removes "I've paid (test)")
- [ ] `debug => false` (this also hides the debug bar — though the bar is already invisible to anyone who is not on the server itself, doc 01)
- [ ] Live Razorpay keys in the server's `config.local.php`, **after regenerating the live Key Secret** (it was shared in chat). Add the webhook `<base_url>/api/webhook-razorpay.php` with its secret
- [ ] UPI id (`upi.vpa`) if QR payments are wanted
- [ ] Strong admin password: `php bin/setup.php --admin manvir "Manvir" "<long password>"` (the local one is `1234`). The sign-in page sends SHA-256 of username and password; `setup.php --admin` stores bcrypt(SHA-256), so always set passwords with it
- [ ] Remove the six demo bookings (guest names starting "Demo", emails @example.com) — or start the live database fresh
- [ ] MySQL database from `schema.sql` + `seed.sql` (full setup **once**; never again on live, doc 28 §4). **`config.php` now defaults to SQLite**, so the live `config.local.php` must say `'db' => ['driver' => 'mysql', …]`
- [ ] `base_url` and `allowed_origins` for the real domain; `properties.website_url` → the live site ("Back to website"). `seed.sql` now sets it to `http://localhost:3000`, so change it in the live database after setup
- [ ] Mail: SMTP settings and `from_email` on a domain you own (currently `@kutchsafariresort.com`). Keep `mail.enabled => true` (a development PC may set it to false)
- [ ] Behind a proxy or CDN (a Vercel rewrite to the engine, Cloudflare)? List its addresses in `trusted_proxies`, so the rate limits see each guest's own address. Leave it empty when guests reach the engine directly
- [ ] Run `bin/test-logic.php` and `bin/test-concurrency.php` on a development PC before each release (both use a temp copy); on MySQL, run a load test against a copy before launch
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

### 4. Handing the project over
* No pen-drive copy was made on 26 Sep 2026 (the owner said not to). The project lives in `C:\Users\ADMIN\Downloads\kutch-safari-resort`.
* **When copying it anywhere, leave out the Razorpay keys.** Blank the key values in `backend/booking-engine/config.local.php` in the copy (Razorpay then stays off until keys are added), or leave that file out and let the receiver create their own.
* `.git` holds history up to the one push on 25 Sep 2026; later work is uncommitted.

### 5. Where things are
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

---

<a id="docs-15"></a>

## Deployment and Infrastructure
_(was `docs/15-DEPLOYMENT-AND-INFRASTRUCTURE.md`)_
### 1. Overview
| Piece | Runtime | Where it can be hosted |
|---|---|---|
| Marketing site (`dist/public`) | Static files | Vercel (current), Netlify, cPanel, anything |
| Express server (`dist/index.js`) | Node 18+ | Any Node host. Optional; only needed for `/api/contact` |
| **Booking engine (`backend/booking-engine/`)** | **PHP 8 + MySQL** | **PHP host with MySQL (cPanel). Not Vercel.** |

Production domain: `kutchsafaribhuj.in`. Vercel project: `kutch-safari-resort` (`.vercel/project.json`).

---

### 2. Marketing site

#### 2.1 Build
```bash
pnpm build     # vite build → dist/public ; esbuild backend/server/index.ts → dist/index.js
```
Set `VITE_BOOKING_URL` at build time when the engine is **not** served from `/book/` on the same domain; for the planned live setup that is `https://book.kutchsafaribhuj.in/` (docs/10 §3.1). The value must end with `/`.

#### 2.2 Vercel (the live website since 5 Oct 2026, enquiry only)
Project **kutch-safari-resort** (owner `vsmsmsv`), linked in the root `.vercel/project.json`; live at `https://kutch-safari-resort.vercel.app`. The site goes up **without** the booking engine (`VITE_BOOKING_ENABLED` unset): Book Now is Enquire Now, `/book` → `/enquire`, `/admin` not found.

Deploy the way it was done on 5 Oct 2026: build here, then upload the finished files ("prebuilt"), because the project's Vercel settings (Vite preset, output `dist`) would not build this repo (output is `dist/public`, and `pnpm build` also needs `backend/`, which `.vercelignore` leaves out). From the project root, in Git Bash:
```bash
pnpm exec vite build
rm -rf .vercel/output && mkdir -p .vercel/output/static
cp -r dist/public/. .vercel/output/static/ && rm -f .vercel/output/static/vercel.json .vercel/output/static/.gitkeep
printf '{"version":3,"routes":[{"handle":"filesystem"},{"src":"/(.*)","dest":"/index.html"}]}' > .vercel/output/config.json
cmd.exe //c "vercel deploy --prebuilt --prod --yes"
rm -rf .vercel/output
```
The route sends every address that isn't a file to `index.html` (so `/stay` opened directly works). Check afterwards: `/`, `/stay`, `/enquire` answer 200; Book Now says Enquire Now. **Don't** run `vercel --prod` from `dist/public`: that folder isn't linked, and the CLI links it to a new project (that is how a stray project "public" was made earlier). `frontend/public/vercel.json` (rewrites to `/index.html`) only matters for that older method.

On Vercel, `/book/` is answered by the SPA, so `BookingRedirect` shows "call/WhatsApp us". **To make booking work while the site stays on Vercel**, pick one:
* Build with `VITE_BOOKING_URL` pointing at the engine on the PHP host, **or**
* Add a Vercel rewrite that proxies `/book/(.*)` to the PHP host, placed before the SPA catch-all:
  ```json
  { "rewrites": [
      { "source": "/book/:path*", "destination": "https://PHP-HOST/book/:path*" },
      { "source": "/(.*)", "destination": "/index.html" } ] }
  ```

#### 2.3 Everything on cPanel (simplest)
Upload `dist/public/*` to `public_html/` and `backend/booking-engine/*` to `public_html/book/`. The default `/book/` link then works with no environment variable. SPA routing needs an `.htaccess` fallback to `index.html` with `book/` excluded; its full text is in [docs/10 §3.2](10-GO-LIVE-ON-HOSTINGER.md#32-website-htaccess-new-file).

#### 2.4 Hostinger (planned live setup, 5 Oct 2026)
The owner's chosen live setup (one Hostinger plan: `kutchsafaribhuj.in`, `book.kutchsafaribhuj.in`, `admin.kutchsafaribhuj.in`) and every step and URL change for it are in [`docs/10-GO-LIVE-ON-HOSTINGER.md`](10-GO-LIVE-ON-HOSTINGER.md). Until then the site runs only on the owner's PC with the local `/book/` links.

---

### 3. Booking engine
Full steps are in [`backend/booking-engine/README.md`](05-BOOKING-ENGINE.md#engine-readme). In short:
1. Upload `backend/booking-engine/` → `public_html/book` (include the hidden `.htaccess` files).
2. cPanel → MySQL: create the database and user, then import `schema.sql` and `seed.sql` (or run `php bin/setup.php`).
3. Create `config.local.php` next to `config.php` with DB, `base_url`, Razorpay, UPI, `debug => false` and `'rules' => ['guest_details_optional' => false]` (sample mode off) and `'test_payments' => ['enabled' => false]` (no free "I've paid (test)" button). **Replace the local SQLite `config.local.php` from the repo. Don't upload it.**
4. `php bin/setup.php --admin USERNAME "Name" "password"` (a long password; the local test login `manvir` / `1234` must not go live).
   **Never run full `php bin/setup.php` again on the live database.** It reloads `seed.sql`, which empties the rooms, prices, special prices and extras first (doc 28 §4).
5. Razorpay: test keys first, then a webhook to `<base_url>/api/webhook-razorpay.php` (payment.captured, payment.failed, refund.processed).
6. Cron (once Stayflexi is connected): `bin/sync-inventory.php` every 10 min, `bin/retry-failed-sync.php` hourly.
7. Make sure the site's domain is in `allowed_origins` (`config.php`). The kutchsafaribhuj.in domains and the Vercel preview are already listed.
8. Health check: `php bin/check-system.php`, `bin/check-razorpay.php`, `bin/check-stayflexi.php`.

**Seed data to adjust for this domain:** `properties.website_url` is `http://localhost:3000` (KSR, set for local testing) and `https://www.whiterann.com` (WRC). **`config.php` defaults to SQLite**; the live `config.local.php` must set `'driver' => 'mysql'`. This is the engine's "Back to website" link. Change it to `https://kutchsafaribhuj.in` if that's the live site. Mail settings in `config.php` also assume `@kutchsafariresort.com`.

---

### 4. Local development
```bash
pnpm install
pnpm setup:book   # php backend/booking-engine/bin/setup.php → backend/booking-engine/data/booking.sqlite
pnpm dev:book     # php -S 127.0.0.1:8080 -t backend/booking-engine
pnpm dev          # vite on :3000, proxies /book/ → :8080
```
* PHP 8.3 is installed on this PC with winget. `php.ini` enables `pdo_sqlite`, `sqlite3`, `curl`, `mbstring`, `openssl` and `fileinfo`, with `date.timezone = Asia/Kolkata`.
* The engine must listen on **127.0.0.1:8080** and the Vite proxy must point at `http://127.0.0.1:8080`. With `localhost`, Node picked IPv6 and the rooms never loaded.
* On Windows, PHP uses the Windows certificate store for HTTPS (`curl_trust_system_certs()`); without it Razorpay calls fail with HTTP 0.
* `backend/booking-engine/config.local.php` (git-ignored) switches to SQLite and sets `base_url` to `http://localhost:3000/book`.
* Admin locally: `http://localhost:3000/book/admin` (the address fills itself in to `…/admin/login.php`).
* Tests: `php backend/booking-engine/bin/test-changes.php`, `bin/test-logic.php` and `bin/test-concurrency.php` (each works on a temporary copy of the database, doc 30).

### 5. Express server (`backend/server/index.ts`)
Serves `dist/public`, `POST /api/contact` (checks name, phone or email, and message, then appends only those fields to `/data/enquiries.json`; no personal details in the log; other methods 405; bad JSON 400), and a `*` fallback to `index.html` for page addresses (a missing file such as `/assets/x.jpg` is a 404). `pnpm start` runs it in production mode. It does not proxy `/book/`. If you host the site with this server, add a proxy or serve the engine from a PHP host.

### 6. Secrets and git
* Ignored: `.env*`, `/data/`, `backend/booking-engine/config.local.php`, `backend/booking-engine/data/*` (except `.htaccess`), `*.sqlite`, `*.log`.
* Never commit Razorpay keys, the webhook secret, the UPI VPA or DB passwords. They go only in the server's `config.local.php`.
* On this PC, `config.local.php` holds the Razorpay test and live key pairs (test pair in use). Regenerate the live secret before launch; it was shared in chat.
* **Don't push to GitHub** unless the owner asks. The one push was on 25 Sep 2026; everything since is uncommitted.

---

<a id="restructure-report"></a>

## Folder cleanup report (29 Sep 2026)
_(was `restructure/REPORT.md`)_
Snapshot before any move: branch `restructure/backup-2026-09-29`. To undo everything: `git checkout restructure/backup-2026-09-29`.

### 1. The new tree
| Folder | Purpose |
|---|---|
| `frontend/` | React 19 + Vite marketing site (was `client/`) |
| `backend/booking-engine/` | PHP booking engine, served at `/book/` (was `booking-engine/`) |
| `backend/server/` | Express server for the built site (was `server/`) |
| `api/` | Vercel serverless function. **Not moved:** Vercel only picks up functions from `/api` at the project root |
| `docs/` | 30 project docs; `docs/notes/` holds `ideas.md` and `todo.md` |
| `scripts/legacy/` | old one-off `*.py` edit scripts and `old_rooms.tsx` |
| [`restructure/`](#restructure-report) | this report, the move map and the change log |
| root | `package.json`, lockfiles, `vite.config.ts`, `tsconfig*.json`, `components.json`, Prettier/Vercel/git ignore files, [`README.md`](../README.md#quick-start) (where the tools expect them) |

Full annotated tree: [`TARGET_TREE.md`](#restructure-target-tree).

### 2. Move log
Every old → new path is in [`CHANGELOG.md`](#restructure-changelog) (all moves done with `git mv`, so history is kept). The engine's git-ignored files (`config.local.php` with the Razorpay keys, `data/booking.sqlite`) moved with the folder and were checked afterwards.

### 3. References updated
* **Tooling:** `vite.config.ts` (root, `@` alias), `tsconfig.json` (include, paths), `components.json`, `package.json` (`dev:book`, `setup:book`, `build`), `.gitignore`, `.prettierignore`, `.vercelignore`, `.claude/launch.json`.
* **Code:** `backend/server/index.ts` now finds the project root whether it runs from the bundle (`dist/index.js`) or from source; a comment in `frontend/src/lib/booking.ts`.
* **Docs:** [`README.md`](../README.md#quick-start) and 21 docs. Change-log history (doc 20) and the note about the removed old React engine keep the paths they had at the time.
* **Not changed on purpose:** the scripts in `scripts/legacy/` still mention `client/…`. They are finished one-off edits, kept only as history.

### 4. Possibly unused files (NOT deleted)
`scripts/legacy/` — 36 files: the one-off `*.py` edit scripts and `old_rooms.tsx`. Nothing imports or runs them. They can be deleted once the owner confirms.

### 5. Verification (same checks as the baseline)
| Check | Before | After |
|---|---|---|
| TypeScript (`tsc --noEmit`) | pass | pass |
| Build (`npm run build`: Vite + esbuild) | pass | pass (`dist/public`, `dist/index.js`) |
| PHP lint (43 engine files) | 0 errors | 0 errors |
| Engine tests (`bin/test-changes.php`) | 17/17 | 17/17 |
| Express server from `dist/index.js` and from `backend/server/index.ts` | — | both serve `/` and `/stay` (200) |
| Dev setup (`dev:book` + Vite, via the preview config) | — | site, `/book/`, check status, admin, terms PDF, room search all 200; database still has the owner's booking |

Not tested: posting the Express contact form, because it would write a fake enquiry into the real `data/enquiries.json`. The path logic for it was checked in code.

### 6. Where to start
* Website code: `frontend/src` (pages in `frontend/src/pages`).
* Booking engine: `backend/booking-engine` (settings in `config.php`, logic in `lib/`).
* Run it: `pnpm dev:book` then `pnpm dev`, and open http://localhost:3000.

---

<a id="restructure-changelog"></a>

## Changelog
_(was `restructure/CHANGELOG.md`)_
### Moves (git mv, history kept)

| Old path | New path |
|---|---|
| client/ | frontend/ |
| booking-engine/ (with ignored config.local.php and data/booking.sqlite) | backend/booking-engine/ |
| server/ | backend/server/ |
| ideas.md, todo.md | docs/notes/ |
| analyze_edges.py | scripts/legacy/analyze_edges.py |
| create_pages.py | scripts/legacy/create_pages.py |
| fix_camp_img.py | scripts/legacy/fix_camp_img.py |
| fix_favicon.py | scripts/legacy/fix_favicon.py |
| fix_hero_text.py | scripts/legacy/fix_hero_text.py |
| fix_home_pdf.py | scripts/legacy/fix_home_pdf.py |
| fix_home_wrc.py | scripts/legacy/fix_home_wrc.py |
| fix_imports.py | scripts/legacy/fix_imports.py |
| fix_logo_bg.py | scripts/legacy/fix_logo_bg.py |
| fix_logo_bg2.py | scripts/legacy/fix_logo_bg2.py |
| fix_logo_blend.py | scripts/legacy/fix_logo_blend.py |
| fix_logo_darken.py | scripts/legacy/fix_logo_darken.py |
| fix_nav_pdf.py | scripts/legacy/fix_nav_pdf.py |
| fix_navbar.py | scripts/legacy/fix_navbar.py |
| fix_route.py | scripts/legacy/fix_route.py |
| fix_seo.py | scripts/legacy/fix_seo.py |
| fix_stay.py | scripts/legacy/fix_stay.py |
| fix_white.py | scripts/legacy/fix_white.py |
| fix_wrc_link.py | scripts/legacy/fix_wrc_link.py |
| gen_navbar_footer.py | scripts/legacy/gen_navbar_footer.py |
| get_colors.py | scripts/legacy/get_colors.py |
| get_logo_palette.py | scripts/legacy/get_logo_palette.py |
| get_logo_palette2.py | scripts/legacy/get_logo_palette2.py |
| old_rooms.tsx | scripts/legacy/old_rooms.tsx |
| remove_weddings.py | scripts/legacy/remove_weddings.py |
| restore_rooms.py | scripts/legacy/restore_rooms.py |
| revert_all.py | scripts/legacy/revert_all.py |
| revert_nav.py | scripts/legacy/revert_nav.py |
| rewrite_home.py | scripts/legacy/rewrite_home.py |
| rewrite_stay.py | scripts/legacy/rewrite_stay.py |
| update_all_pages.py | scripts/legacy/update_all_pages.py |
| update_app.py | scripts/legacy/update_app.py |
| update_gallery.py | scripts/legacy/update_gallery.py |
| update_navbar_logo.py | scripts/legacy/update_navbar_logo.py |
| update_server.py | scripts/legacy/update_server.py |
| update_theme.py | scripts/legacy/update_theme.py |

### Reference updates

| File | Change | Count |
|---|---|---|
| vite.config.ts | `/** Where `pnpm dev:book` serves the PHP booking engine (booking-engin` → `/** Where `pnpm dev:book` serves the PHP booking engine (backend/booki` | 1× |
| vite.config.ts | `path.resolve(import.meta.dirname, "client", "src")` → `path.resolve(import.meta.dirname, "frontend", "src")` | 1× |
| vite.config.ts | `root: path.resolve(import.meta.dirname, "client"),` → `root: path.resolve(import.meta.dirname, "frontend"),` | 1× |
| tsconfig.json | `"include": ["client/src/**/*", "server/**/*", "api/**/*"]` → `"include": ["frontend/src/**/*", "backend/server/**/*", "api/**/*"]` | 1× |
| tsconfig.json | `"@/*": ["./client/src/*"]` → `"@/*": ["./frontend/src/*"]` | 1× |
| components.json | `"css": "client/src/index.css"` → `"css": "frontend/src/index.css"` | 1× |
| package.json | `"dev:book": "php -S 127.0.0.1:8080 -t booking-engine"` → `"dev:book": "php -S 127.0.0.1:8080 -t backend/booking-engine"` | 1× |
| package.json | `"setup:book": "php booking-engine/bin/setup.php"` → `"setup:book": "php backend/booking-engine/bin/setup.php"` | 1× |
| package.json | `esbuild server/index.ts` → `esbuild backend/server/index.ts` | 1× |
| .gitignore | `client/public/__manus__/version.json` → `frontend/public/__manus__/version.json` | 1× |
| .prettierignore | `booking-engine/` → `backend/booking-engine/` | 1× |
| .vercelignore | `booking-engine/` → `backend/` | 1× |
| .claude/launch.json | `"-t", "booking-engine"]` → `"-t", "backend/booking-engine"]` | 1× |
| frontend/src/lib/booking.ts | ` * Links into the PHP booking engine (booking-engine/).` → ` * Links into the PHP booking engine (backend/booking-engine/).` | 1× |
| backend/server/index.ts | paths to dist/public and data/ now go through projectRoot (works from dist/index.js and from backend/server/index.ts) | 3 edits |
| README.md | 12 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/01-PROJECT-OVERVIEW.md | 5 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/02-ARCHITECTURE.md | 10 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/03-ROUTING-AND-NAVIGATION.md | 1 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/04-DESIGN-SYSTEM.md | 2 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/06-ACCOMMODATION-PAGES.md | 2 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/08-PACKAGES-AND-PRICING.md | 2 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/10-CONTENT-PAGES.md | 1 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/11-IMAGE-ASSET-INVENTORY.md | 5 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/12-SEO-AND-META.md | 3 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/13-FORMS-AND-INTERACTIONS.md | 1 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/14-STYLING-DEEP-DIVE.md | 2 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/15-DEPLOYMENT-AND-INFRASTRUCTURE.md | 12 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/16-KNOWN-ISSUES-AND-BUGS.md | 2 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/17-BUSINESS-CONTENT-REFERENCE.md | 1 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/21-PRICING-LOGIC-DEEP-DIVE.md | 1 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/24-ADMIN-PANEL-GUIDE.md | 1 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/25-GUEST-BOOKING-FLOW-INTERNALS.md | 2 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/26-PAYMENTS-RAZORPAY-UPI-TEST.md | 1 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/28-DATABASE-AND-DATA-RULES.md | 1 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/30-TESTING-GO-LIVE-AND-HANDOVER.md | 6 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| README.md, docs/02-ARCHITECTURE.md | directory trees redrawn by hand | 2 |
| docs/16-KNOWN-ISSUES-AND-BUGS.md | root clutter item marked resolved | 1 |

---

<a id="restructure-move-map"></a>

## Move map
_(was `restructure/MOVE_MAP.md`)_
| Current path | New path | Category | Depended on by |
|---|---|---|---|
| client/ (85 files) | frontend/ | frontend | vite.config.ts, tsconfig.json, components.json, .gitignore, README, docs |
| booking-engine/ (95 files + ignored config.local.php, data/) | backend/booking-engine/ | backend | package.json scripts, .claude/launch.json, .prettierignore, .vercelignore, README, docs, frontend/src/lib/booking.ts (comment) |
| server/index.ts | backend/server/index.ts | backend | package.json build script, README, docs; its own paths to data/ and dist/ |
| ideas.md, todo.md | docs/notes/ | docs | — |
| analyze_edges.py | scripts/legacy/analyze_edges.py | scripts (unused) | nothing |
| create_pages.py | scripts/legacy/create_pages.py | scripts (unused) | nothing |
| fix_camp_img.py | scripts/legacy/fix_camp_img.py | scripts (unused) | nothing |
| fix_favicon.py | scripts/legacy/fix_favicon.py | scripts (unused) | nothing |
| fix_hero_text.py | scripts/legacy/fix_hero_text.py | scripts (unused) | nothing |
| fix_home_pdf.py | scripts/legacy/fix_home_pdf.py | scripts (unused) | nothing |
| fix_home_wrc.py | scripts/legacy/fix_home_wrc.py | scripts (unused) | nothing |
| fix_imports.py | scripts/legacy/fix_imports.py | scripts (unused) | nothing |
| fix_logo_bg.py | scripts/legacy/fix_logo_bg.py | scripts (unused) | nothing |
| fix_logo_bg2.py | scripts/legacy/fix_logo_bg2.py | scripts (unused) | nothing |
| fix_logo_blend.py | scripts/legacy/fix_logo_blend.py | scripts (unused) | nothing |
| fix_logo_darken.py | scripts/legacy/fix_logo_darken.py | scripts (unused) | nothing |
| fix_nav_pdf.py | scripts/legacy/fix_nav_pdf.py | scripts (unused) | nothing |
| fix_navbar.py | scripts/legacy/fix_navbar.py | scripts (unused) | nothing |
| fix_route.py | scripts/legacy/fix_route.py | scripts (unused) | nothing |
| fix_seo.py | scripts/legacy/fix_seo.py | scripts (unused) | nothing |
| fix_stay.py | scripts/legacy/fix_stay.py | scripts (unused) | nothing |
| fix_white.py | scripts/legacy/fix_white.py | scripts (unused) | nothing |
| fix_wrc_link.py | scripts/legacy/fix_wrc_link.py | scripts (unused) | nothing |
| gen_navbar_footer.py | scripts/legacy/gen_navbar_footer.py | scripts (unused) | nothing |
| get_colors.py | scripts/legacy/get_colors.py | scripts (unused) | nothing |
| get_logo_palette.py | scripts/legacy/get_logo_palette.py | scripts (unused) | nothing |
| get_logo_palette2.py | scripts/legacy/get_logo_palette2.py | scripts (unused) | nothing |
| remove_weddings.py | scripts/legacy/remove_weddings.py | scripts (unused) | nothing |
| restore_rooms.py | scripts/legacy/restore_rooms.py | scripts (unused) | nothing |
| revert_all.py | scripts/legacy/revert_all.py | scripts (unused) | nothing |
| revert_nav.py | scripts/legacy/revert_nav.py | scripts (unused) | nothing |
| rewrite_home.py | scripts/legacy/rewrite_home.py | scripts (unused) | nothing |
| rewrite_stay.py | scripts/legacy/rewrite_stay.py | scripts (unused) | nothing |
| update_all_pages.py | scripts/legacy/update_all_pages.py | scripts (unused) | nothing |
| update_app.py | scripts/legacy/update_app.py | scripts (unused) | nothing |
| update_gallery.py | scripts/legacy/update_gallery.py | scripts (unused) | nothing |
| update_navbar_logo.py | scripts/legacy/update_navbar_logo.py | scripts (unused) | nothing |
| update_server.py | scripts/legacy/update_server.py | scripts (unused) | nothing |
| update_theme.py | scripts/legacy/update_theme.py | scripts (unused) | nothing |
| old_rooms.tsx | scripts/legacy/old_rooms.tsx | scripts (unused) | nothing |
| api/contact.ts | (stays) | backend (Vercel) | tsconfig.json |

---

<a id="restructure-current-tree"></a>

## Current tree (26 Sep 2026, before the cleanup)
_(was `restructure/CURRENT_TREE.md`)_
```
.claude/
.gitignore
.prettierignore
.prettierrc
.vercelignore
README.md
analyze_edges.py
api/
booking-engine/
client/
components.json
create_pages.py
docs/
fix_camp_img.py
fix_favicon.py
fix_hero_text.py
fix_home_pdf.py
fix_home_wrc.py
fix_imports.py
fix_logo_bg.py
fix_logo_bg2.py
fix_logo_blend.py
fix_logo_darken.py
fix_nav_pdf.py
fix_navbar.py
fix_route.py
fix_seo.py
fix_stay.py
fix_white.py
fix_wrc_link.py
gen_navbar_footer.py
get_colors.py
get_logo_palette.py
get_logo_palette2.py
ideas.md
old_rooms.tsx
package-lock.json
package.json
pnpm-lock.yaml
remove_weddings.py
restore_rooms.py
revert_all.py
revert_nav.py
rewrite_home.py
rewrite_stay.py
server/
todo.md
tsconfig.json
tsconfig.node.json
update_all_pages.py
update_app.py
update_gallery.py
update_navbar_logo.py
update_server.py
update_theme.py
vite.config.ts
```

Untracked/ignored at root: data/ (enquiries), dist/ (build output), node_modules/, .vercel/, .claude/.
Inside booking-engine (ignored, move with the folder): config.local.php (Razorpay keys), data/booking.sqlite.

---

<a id="restructure-target-tree"></a>

## Target tree
_(was `restructure/TARGET_TREE.md`)_
Principle: separate the three apps by role, keep each app's own internal layout (they were already well organised), keep tool configs at the root where the tools expect them, and archive — not delete — the one-off scripts.

```
kutch-safari-resort/
├── frontend/                 React 19 + Vite marketing site          (was client/)
│   ├── index.html
│   ├── public/               static files served at / (assets/, robots.txt, sitemap.xml, vercel.json)
│   └── src/                  App.tsx, main.tsx, index.css, components/, contexts/, lib/, pages/
├── backend/
│   ├── booking-engine/       PHP 8 booking engine, served at /book/   (was booking-engine/)
│   │                         admin/, api/, assets/, bin/, lib/, data/ (SQLite, ignored), config.local.php (ignored)
│   └── server/               Express server for the built site        (was server/)
├── api/                      Vercel serverless function (contact.ts)  — stays at the root: Vercel only
│                             picks up functions from /api at the project root
├── docs/                     the 30 project docs
│   └── notes/                ideas.md, todo.md                        (were at the root)
├── scripts/
│   └── legacy/               34 one-off *.py edit scripts + old_rooms.tsx (were at the root; already applied,
│                             unused — kept for history, candidates for deletion)
├── restructure/              this cleanup's records
├── package.json, pnpm-lock.yaml, package-lock.json
├── vite.config.ts, tsconfig.json, tsconfig.node.json, components.json
├── .prettierrc, .prettierignore, .vercelignore, .gitignore
└── README.md
```

Not moved (regenerated or runtime, git-ignored): `node_modules/`, `dist/` (build output), `data/` (enquiries from the Express server), `.vercel/`.
`.claude/launch.json` stays (tool location) but its paths are updated.

---

<a id="heal-report"></a>

## Self-healing report (29 Sep 2026)
_(was `heal/REPORT.md`)_
Snapshot before any fix: branch `heal/backup-2026-09-29`.

### How it was run
* **Debug rig:** a throwaway copy of the booking engine and its database (never the real one), served with PHP `error_reporting=E_ALL`, startup errors shown, every error/warning written to a log file; the website on the Vite dev server (development build, React dev warnings on).
* **Each pass:** 54 scripted guest + admin actions over HTTP (book, pay (test), UPI, status, receipt, cancel, change booking, refunds, special prices, enquiries, CSV, sign-in/out, security tokens); a browser click-through of every website route (21 including 404s) and of the engine (search → room → extras → details → I've paid (test) → confirmation, status page, receipt and terms viewers, every admin screen, change-booking price check), watching the console and network; the PHP error log and server log.

### Results
| Loop | Errors | Warnings | Failed requests | Notes |
|---|---|---|---|---|
| 1 | 9 (1 kind) | 21 (1 kind) | favicon 404 on every engine page | F1–F3 found |
| 2 | 0 | 0 | favicon 404 still on engine pages | F1, F2 gone; F3 fix did not hold |
| 3 | 0 | 0 | 0 | clean |

Server side (PHP) was clean in every loop: 0 errors, 0 warnings, 0 notices. The only non-200 responses are the rejections the tests trigger on purpose (wrong mobile → 404, wrong receipt token → 404, guest cancel → 403, form without security token → 403).

### Every finding
| ID | Category | Where | Root cause | Fix | Found / fixed |
|---|---|---|---|---|---|
| F1 | Console warning | `frontend/index.html` | `<link rel="preload" as="video">` (browsers don't support `as="video"`) for `KSR_VIDEO.mp4`, a 12.8 MB video no page plays | Removed the preload line (also saves the wasted download) | loop 1 / loop 2 |
| F2 | Console error | `frontend/src/pages/Destination.tsx` (3), `RannUtsavPackage.tsx` (2) | `<Link><a>…</a></Link>`: wouter's `Link` already renders `<a>`, so this nested a link inside a link (invalid HTML, React warns) | Moved the classes onto `Link` and removed the inner `<a>` | loop 1 / loop 2 |
| F3 | Network 404 | every engine page | No site-root `/favicon.ico`, and no icon declared on engine pages. Data-URI icons did not stop the browser asking for `/favicon.ico` (tried twice) | Real icon file `backend/booking-engine/assets/icon.svg` linked from every engine page, **and** a real 9 KB `frontend/public/favicon.ico` (+ `apple-touch-icon.png`) made from the logo. In production the engine is under `/book/`, so `/favicon.ico` comes from the website. The website's tab icon was the 4.5 MB `logo-mark.png`; it now uses the 9 KB `favicon.ico` | loop 1 / loop 3 |

Recurring root cause: none; three separate small issues.

### NEEDS-HUMAN
None.

### After the loop
* Debug mode was only ever on the throwaway copy's command line; nothing debug-related was changed in the project.
* Regression: `tsc` pass, `npm run build` pass (ships `favicon.ico`), PHP lint 0 errors, `bin/test-changes.php` 17/17.
* Dev-only note: opening the engine directly on port 8080 (not through `localhost:3000/book/`) has no `/favicon.ico` at that root. That only affects direct access in development.

---

<a id="heal-findings"></a>

## Findings
_(was `heal/FINDINGS.md`)_
| ID | Category | Where | Message | Trigger | Status |
|---|---|---|---|---|---|
| F1 | CONSOLE-WARNING | frontend/index.html:6 | <link rel=preload> uses an unsupported `as` value | every website page load | FIXED (loop 2) |
| F2 | RUNTIME (console error) | Destination.tsx:153,167,176; RannUtsavPackage.tsx:15,24 | In HTML, <a> cannot be a descendant of <a> | opening any /destination/* or /white-rann-camp page | FIXED (loop 2) |
| F3 | NETWORK | engine pages | GET /favicon.ico 404 | opening any engine page | FIXED (loop 3) |

---

<a id="heal-changelog"></a>

## Changelog
_(was `heal/CHANGELOG.md`)_
| Finding | File | Before | After |
|---|---|---|---|
| F1 | frontend/index.html | preload of KSR_VIDEO.mp4 with as="video" | line removed |
| F2 | frontend/src/pages/Destination.tsx | 3 x `<Link href><a className>` | `<Link href className>` |
| F2 | frontend/src/pages/RannUtsavPackage.tsx | 2 x `<Link href><a className>` | `<Link href className>` |
| F3 | backend/booking-engine/{index,manage,document}.php, admin/_auth.php | no icon | `<link rel="icon" type="image/svg+xml" href="assets/icon.svg">` (admin: ../assets/icon.svg) |
| F3 | backend/booking-engine/assets/icon.svg | none | new 32x32 SVG icon |
| F3 | frontend/public/favicon.ico, apple-touch-icon.png | none | new, from logo-mark.png |
| F3 | frontend/index.html | icon = 4.5 MB logo-mark.png (wrong MIME) | icon = /favicon.ico + apple-touch-icon |

---

<a id="heal-run-log"></a>

## Run log
_(was `heal/RUN_LOG.md`)_
Loop 1: 54/54 scripted actions pass; PHP log empty; website console: 21x preload warning, 9x nested <a> error; engine: favicon 404 on each page.
Loop 2: 54/54; PHP log empty; website console: only Vite/React dev info; engine: favicon 404 remained.
Loop 3: all engine requests 200 incl. /favicon.ico; console empty; PHP log empty.

---

<a id="heal-state"></a>

## State
_(was `heal/STATE.md`)_
| Loop | Errors | Warnings | Failed requests |
|---|---|---|---|
| 1 | 9 | 21 | favicon 404 on engine pages |
| 2 | 0 | 0 | favicon 404 on engine pages |
| 3 | 0 | 0 | 0 |

Done: exit condition met in loop 3. Report: REPORT.md

---

<a id="button-audit-report"></a>

## Button, link and page audit (29 Sep 2026)
_(was `button_audit/REPORT.md`)_
Snapshot before any fix: branch `heal/backup-2026-09-29` (same run as self-healing).

### 1. Totals
| What | Found | Result |
|---|---|---|
| Website routes | 19 (13 pages + 6 destination guides) + redirects `/booking`, `/book`, `/admin` + 404 | all load; 1 route added (`/white-rann-camp/tariff`) |
| Website links (distinct targets) | 21 internal/external + tel/mailto/WhatsApp | 5 broken → fixed; 6 dead cards → wired up |
| Website buttons | Book Now ×3, mobile menu, contact form, Stay lightbox, Check status ×4 | 1 invisible → fixed; 1 unnamed → fixed |
| Booking-engine actions (guest + admin) | 59 scripted checks + browser click-through of every screen (see heal/REPORT.md, docs/30) | all pass |
| Engine pages at phone/tablet/desktop | booking, status, sign-in, receipt/terms viewer, admin | 1 overflow at phone width → fixed |

### 2. Navigation bugs fixed (old → new)
| Link | Where | Before | After |
|---|---|---|---|
| "Rann Utsav 2026–27" | Footer (every page) | `/white-rann-camp/tariff` → 404 page | new route: opens the White Rann Camp page scrolled to its tariff table |
| "2026-27 Tariff" | Home, sister-property section | same 404, **and invisible** (white text on beige) | same route; terracotta outline button |
| "Back to Beyond Bhuj" ×2 | destination guides, "not found" state | `/#explore` (no such section, lands at top of Home) | `/experiences` (the destination cards) |
| "Back" | White Rann Camp header | `/#rann-utsav` (no such section) | `/` |
| "FAQs" | Footer | `/plan-your-visit#faq` opened at the top | lands on the FAQ section (instant jump, clears the sticky header) |
| 6 "Discover →" cards | Home, Experiences section | looked clickable, went nowhere | link to the same destination guides as the Experiences page |

Checked by clicking each in the browser: every link now reaches the right page, and the tariff table and FAQ land on screen just below the header.

### 3. Responsive (375 / 768 / 1440 px)
All 12 website pages and all engine pages checked for sideways scrolling at phone and tablet width.
* **Fixed:** booking page at phone width was 389 px wide on a 375 px screen: "Already booked? Check status" in the engine header could not wrap and spilled past the edge, so phones zoomed the whole page out. Header buttons may now wrap onto two lines below 960 px (`assets/engine.css`).

### 4. Accessibility
| Fix | Where |
|---|---|
| Mobile menu button had no name ("button") → "Open menu"/"Close menu" + `aria-expanded` | `components/Navbar.tsx` |
| Footer Instagram icon link had no name → "Kutch Safari Resort on Instagram" | `components/Footer.tsx` |
| 26 gallery photos all `alt="Gallery"` → 7 described from their file names, 19 camera-numbered ones "Kutch Safari Resort, photo N of 26"; removed the pointer cursor (clicking does nothing) | `pages/GalleryPage.tsx` |
| Pinch-zoom was blocked (`maximum-scale=1`) → allowed | `index.html` |
| Two `<h1>` per page (the footer brand was an `h1`) → footer brand is a `<p>` with the same look; Home's hero "Where the Lake Meets the Desert" is now its `<h1>` (same look) | `Footer.tsx`, `Home.tsx` |

### 5. SEO, content, security, performance
* **Titles:** every page had the same browser title → 12 distinct titles (per page and per destination; "Page not found" for 404s) (`App.tsx`).
* **Sitemap:** listed 4 of 15 public pages → all 15 (`public/sitemap.xml`). **robots.txt:** now `Disallow: /book/` and `/admin` (the engine also sends `noindex`).
* **Error screen** showed the technical stack trace to visitors → only in development (`ErrorBoundary.tsx`).
* **Packages:** `npm audit` 0 vulnerabilities. **Secrets:** none in tracked files. External links opening new tabs all have `rel="noopener"`/`noreferrer`.
* **Favicon:** the tab icon was the 4.5 MB `logo-mark.png` (wrong type) → 9 KB `favicon.ico` + touch icon (see heal/REPORT.md F3).
* **Caching:** engine script and styles are now versioned (`?v=` last-change time) so a browser never keeps an old copy after an update — this mattered because the payment button's request changed.

### 6. Needs a human decision (not changed)
1. **Image weight (~269 MB in `frontend/public/assets`)**: converting to WebP and resizing is a content job with visual checks; see docs/11 for the plan.
2. **Home contact form** is still a mock (shows "sent", sends nothing) — wiring it to the engine's `api/enquiry.php` is a product decision (docs/13).
3. **Gallery photos 8–26**: real descriptions need someone who knows what each photo shows.
4. **Destination and White Rann Camp pages** still use their own old header/footer (owner's layout; left as is).
5. **Stay lightbox** has no next/previous, and the Gallery has no lightbox.

### 7. How to re-run
* Engine: `php backend/booking-engine/bin/test-changes.php` (17 checks) and `php backend/booking-engine/bin/check-system.php` (both use a temporary copy of the database).
* Website: open each route in `frontend/src/App.tsx`, check the console, and check `document.documentElement.scrollWidth` at 375 px.

---

<a id="chaos-report"></a>

## Chaos-monkey report (29 Sep 2026): nonsense input
_(was `chaos/REPORT.md`)_
Everything ran against a **throwaway copy** of the booking engine and its database (port 8090), never the real one. Snapshot branch: `heal/backup-2026-09-29`.

### 1. What was tried
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

"Correctly handled" includes values that are safely corrected rather than refused: impossible room/guest counts become valid ones (the page itself never sends them), and an unknown payment choice falls back to "pay in full", never to anything free. Before/after for every case is in [`FINDINGS.md`](#chaos-findings).

### 2. Gaps found and fixed
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

### 3. Root causes
* **No shared input checks.** Each form trusted its input. Now `lib/db.php` has `LIMITS`, `too_long()`, `valid_phone()`, `valid_arrival_time()` and `valid_date()`, used by booking, enquiry and admin.
* **"Be forgiving" code** (`max(1, (int) $qty)`) turned garbage into real orders. It now refuses instead.
* **SQLite hides length problems** that MySQL would turn into errors on the live server.

### 4. Already solid
Dates in the past, reversed dates, stays over 21 nights, more than 5 rooms online, overbooking, unknown or switched-off properties, rooms from another cottage or property, gala dinner minimum, email format on bookings, wrong or missing private codes, lookups with wrong details, SQL-looking input (all queries are parameterised), HTML in names (always shown as text; checked on every admin page), security tokens on admin forms, payment amounts (0, negative, "abc", 1e12, over the balance), refunds when nothing is owed, special-price guard rails.

### 5. Re-test and regression
* All 107 values re-sent after the fixes: **0 wrongly accepted**, and **0 PHP errors, warnings or notices** in the server log.
* Normal use still works: guest flow 24/24, admin flow 35/35, `bin/test-changes.php` 17/17, `bin/check-system.php` (only "Stayflexi not connected", a real go-live item), and a booking through the real page in the browser (bad phone caught on the page; `+91 98250 12345` goes through).
* Test data only ever went into the throwaway copy. The real database still has exactly the 7 bookings (the owner's + 6 demo).

---

<a id="chaos-findings"></a>

## Findings (before -> after)
_(was `chaos/FINDINGS.md`)_
Before = first run, after = same values re-sent after the fixes. Raw output of both runs is in EVIDENCE/.

### 2. Gaps found and fixed
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

---

<a id="chaos-changelog"></a>

## Changelog
_(was `chaos/CHANGELOG.md`)_
| Finding | File | Change |
|---|---|---|
| all | backend/booking-engine/lib/db.php | LIMITS, too_long(), valid_phone(), valid_arrival_time(), valid_date() |
| 1-3 | lib/booking.php (quote_cart) | extras: unknown refused; quantity must be 1..rules.max_extra_quantity; rooms/addons must be lists |
| 4-6 | lib/booking.php (create_booking) | guest name/city/note length, phone format, arrival time format; field=guest on refusal; missing name/phone safe |
| 7 | lib/booking.php (validate_dates, validate_dates_staff) | real calendar dates; up to rules.max_days_ahead |
| 7 | config.php | rules.max_days_ahead = 730, rules.max_extra_quantity = 50 |
| 8 | api/enquiry.php | phone, name, email, dates, guests, interest, message checks |
| 9,10,13 | admin/booking.php | method list; note/reason limits; red error messages; trimmed values |
| 10-13 | admin/enquiries.php | status list; enquiry must exist; note limit; red errors; maxlength |
| 14 | admin/calendar.php | unknown property / bad start date fall back |
| 15 | admin/rates.php | "Choose the first and the last night." |
| 16 | api/_init.php | unreadable JSON -> clear 400 |
| 19 | admin/export.php | csv_safe() on guest-typed cells |
| UX | assets/engine.js | maxlength on guest fields; phone/email checked before sending; refused details keep the guest on the step with their input |

---

<a id="chaos-target-map"></a>

## Input map
_(was `chaos/TARGET_MAP.md`)_
| ID | Field / param | Where | Expected | Required | Saved to |
|---|---|---|---|---|---|
| V | check_in, check_out, rooms, occupancy, property | api/availability.php | real dates within 2 years; 1-5 rooms; 1-3 per room; an active property | yes | nothing (read only) |
| Q | rooms[], occupancy[], addons[], payment_mode, coupon, dates | api/quote.php, book.php | cottage+plan of the property; whole numbers 1-50 per extra; full/advance | yes | booking_rooms, booking_addons, bookings |
| G | name, phone, email, city, arrival_time, special_requests | api/book.php | text <=120 / 7-15 digits / email / <=80 / h:mm AM-PM / <=2000 | optional in sample mode | bookings.guest_* |
| L,P | ref, contact, token, booking_id, method | booking-lookup, payment-test, payment-create | existing booking + its private code | yes | payments |
| E | name, phone, email, guests, check_in/out, interest, message, property, website | api/enquiry.php | as bookings; guests 1-500; message <=3000 | name, phone | enquiries |
| A | amount, method, note, reason, special_requests | admin/booking.php | amount up to the balance; listed method; notes <=300/2000; reason <=250 | yes | payments, bookings |
| C | dates, room[n][cottage], room[n][guests], addon[id] | admin/edit.php | as bookings (desk may change a stay under way) | yes | booking_rooms, booking_addons |
| R | from, to, plans[], price | admin/rates.php | real future dates <=400 nights; price guard rails | yes | rates |
| N | id, status, staff_note | admin/enquiries.php | existing id; listed status; note <=2000 | yes | enquiries |
| U | id, property, start, days, q, filters | admin pages (URL) | existing ids / active property / real date | no | nothing |
