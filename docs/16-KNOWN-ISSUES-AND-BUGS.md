# Known Issues and Bugs

_Re-checked against the code on 26 Sep 2026. Resolved items are at the bottom. Background on many of these: `29-LESSONS-LEARNED-AND-GOTCHAS.md`._

## Critical
### 0. Sample mode is ON: turn it off before going live
`backend/booking-engine/config.php` → `rules.guest_details_optional => true` makes the "Who is coming?" details optional for testing. Blank fields are saved blank; nothing is filled in. **Set it to `false` before launch**, or real guests can book without a name or phone number.

**Test payments are ON:** `config.php` → `test_payments.enabled => true` shows an "I've paid (test)" button that confirms a booking with **no money taken** (payments recorded as provider `test`). **Set it to `false` before launch**, or anyone can book for free.

**Razorpay:** test and live keys are in `backend/booking-engine/config.local.php` (git-ignored), both checked against Razorpay on 26 Sep 2026. This PC uses the **test** keys (no real money). For launch, the live server's `config.local.php` uses `$razorpay_live` plus a webhook secret. The live secret was shared over WhatsApp and chat, so **regenerate it in the Razorpay dashboard before launch**. PHP on Windows uses the Windows certificate store for HTTPS (`curl_trust_system_certs()` in `lib/db.php`).

**Also before launch:** the local admin login is username `manvir` with the test password `1234`. Set a long password on the live server with `php bin/setup.php --admin manvir "Manvir" "<long password>"`. The admin panel controls bookings, refunds and prices.

### 1. Home contact form is a mock
`Home.tsx` `ContactSection` uses `setTimeout` and a success toast. Nothing is sent, so leads are lost. **Fix:** POST to the engine's `api/enquiry.php` (see `13-FORMS-AND-INTERACTIONS.md`).

### 2. Booking engine not deployed
The PHP engine needs a PHP + MySQL host. Until then, every Book Now button on Vercel lands on the "call/WhatsApp us" fallback. **Fix:** see `15-DEPLOYMENT-AND-INFRASTRUCTURE.md`.

### 3. Rates, tax and policies need owner sign-off
* Resort rates now follow the 2026–27 published tariff (GST included). Diwali and Christmas / New Year rates are still needed.
* GST slabs (5% / 18%) and the ₹7,875–₹8,850 edge case (Deluxe triple) need the accountant's confirmation. See `08-PACKAGES-AND-PRICING.md` §5.
* The cancellation ladder (free 30+ days, 75% at 21–29, 100% under 21) and pay-at-property rules need confirming.

### 4. Asset weight (~269 MB)
10–17 MB photos, 109 MB of unused files. See `11-IMAGE-ASSET-INVENTORY.md`. (The 4.5 MB favicon and the 12.8 MB unused video preload were fixed on 29 Sep 2026.)

### 50% plan: when is the balance due?
Guests are told "Balance due 30 days before arrival" (`payment_modes.advance.note`), and the admin, check-status page and receipt say "Due before arrival". Nothing reminds the guest or collects it automatically: the desk collects it. If it should be at check-in instead, change the note and the labels together (doc 22 §3).

### Re-running full setup wipes prices
`php bin/setup.php` without `--admin` reloads `seed.sql`, which empties rooms, prices, special prices and extras first. Never run it on the live database (doc 28 §4).

### White Rann Camp is switched off in the booking engine
Set `properties.active` back to 1 for `white-rann-camp` when the camp should be sold again. Check its tariff, peak dates and extras first. The website's White Rann Camp page still takes enquiries by WhatsApp.

## Major
### 5. Dead links
* `/white-rann-camp/tariff`: Footer and Home. No route, so 404.
* `/#explore` (Destination), `/#rann-utsav` (RannUtsavPackage): no such sections.

### 6. Invisible button on Home
Sister-property "2026-27 Tariff" button: `text-white border-white/30` on the beige background.

### 7. Placeholder content
Our Journey (lorem ipsum, award photo box, empty timeline), Dining (food photo box), Plan Your Visit FAQ stub, Navbar `[WRC LOGO]`.

### 8. Destination and RannUtsavPackage use old headers and footers
They have their own header (4.5 MB `logo-mark.png`), their own footer, no site navigation, and `<Link><a>` nested anchors (invalid HTML).

### 9. Booking engine's "Back to website" points at localhost
`properties.website_url` for the resort is `http://localhost:3000` (local database and `seed.sql`, for testing). Set it to the live site before launch.

### 10. Stayflexi not connected
The engine's channel-manager bridge is off, and its API paths are guesses. Until it's connected, split inventory between the engine and the OTAs (engine README, "Safe — separated"). See doc 27.

### 10a. No screen to close cottages by hand
The "Change rooms on sale" grid was removed from Availability at the owner's request (26 Sep 2026). Every cottage type is sold up to its total unless `inventory` rows say otherwise (from Stayflexi later). Ask for the grid back if rooms need closing for maintenance.

## Minor
11. Home Experiences cards look clickable but don't link. Nothing links to `/destination/dholavira`.
12. Gallery: no lightbox, all 26 images have `alt="Gallery"`, and it's in a narrow `prose` wrapper.
13. `NotFound.tsx` uses stock slate and blue styling.
14. Stay: garbled "appliqu├®" in the unused `Accommodation` component. The bathroom photo is under "Exterior".
15. `ErrorBoundary` shows stack traces to visitors.
16. The heading colour rule in `index.css` overrides parent text colours (see `04-DESIGN-SYSTEM.md`).
17. Sitemap lists only 4 of about 16 public URLs.
18. ~~Pinch zoom blocked~~ fixed 29 Sep 2026.
19. The booking engine looks different (Marcellus/Montserrat, its own palette) from the site.
20. ~~Root clutter~~ Resolved 29 Sep 2026: the one-off `*.py` edit scripts and `old_rooms.tsx` moved to `scripts/legacy/` (unused; delete once confirmed).
21. Much of the current work is uncommitted (git has 4 commits; nothing pushed since 25 Sep 2026 by the owner's choice).
22. `audit_log` also stores one row per rate-limited request (`rl_%`), so it keeps growing. Old `rl_%` rows can be deleted safely.
23. No email to the owner on a new booking (bots declined for now; email offered, not decided).

## Resolved
* `/experiences` 404: the route and page now exist.
* Weddings page placeholders: the page was removed.
* WRC internal-link bug on Home: WRC links are now internal to `/white-rann-camp` by design; the Navbar button goes to travstack.
* **Old React booking engine** (`client/src/booking`, `shared/booking`, `server/booking`): **removed 25 Sep 2026**, replaced by `booking-engine/` (PHP). Its JSON-file store couldn't run on Vercel.
* Stay "Book Now" pointed at `/#contact`: it now goes to the booking engine.
* **Changing a booking re-priced everything** and drifted: now old total + added − taken off; kept items keep their booked amount (26 Sep 2026, 17 tests).
* **"Collect" mixed the change with the old balance:** the preview now shows the change, then money by payment choice (50% or full).
* **Refunds ignored when an online payment settled:** fixed; `amount_paid` is payments − refunds everywhere.
* **Browser pop-ups** for confirmations: replaced by an on-page box.
* **`/book/admin` without a slash broke the admin page:** it now redirects and fills in the address.
* **Arrival time** was a free-text box: now a scroll wheel (hour, minutes, AM/PM).
* **Availability side panel** didn't show which car was booked or where the guest is in the stay: it now does, from either view.
* **Test bookings** in the real database: removed on 26 Sep. On 29 Sep six marked **demo** bookings were added at the owner's request (see doc 28 §3).
* **29 Sep 2026 (see `../heal/`, `../button_audit/`, `../chaos/` reports):** console error from nested links on destination/camp pages; favicon 404s; 5 broken links (`/white-rann-camp/tariff`, `/#explore` ×2, `/#rann-utsav`, FAQ jump); invisible Home tariff button; Home experience cards not linked; booking page too wide on phones; accessibility (menu/Instagram names, gallery alt text, two `h1`s); same title on every page; sitemap; stack trace shown to visitors; `check-system.php` wrote to the real database and tested the switched-off camp; cash at the desk didn't confirm a pending booking; payment start needed only a booking number; CSV ignored the search; unused status filters; 33 kinds of nonsense input accepted (incl. MySQL crash risks from over-long text); CSV formula injection.
