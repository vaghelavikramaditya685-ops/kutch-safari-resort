# Status, Known Issues and Roadmap

_Combined on 30 Sep 2026 from 7 earlier files. Start at [`README.md`](../README.md) for the overview and hard rules._

What works, what doesn't, every known issue, decisions, open questions for the owner, and what's next.

---

> **30 Sep 2026:** a full test with the demo data and heavy traffic found 17 new bugs (B1–B17), listed with proof and fixes in [`BUGS.md`](../BUGS.md). Fix those first.

## Contents
* [Current Status (30 Sep 2026)](#context-current-status) _(was `context/current_status.md`)_
* [Features](#context-features) _(was `context/features.md`)_
* [Known Issues and Bugs](#docs-16) _(was `docs/16-KNOWN-ISSUES-AND-BUGS.md`)_
* [Decisions and Assumptions](#context-decisions-and-assumptions) _(was `context/decisions_and_assumptions.md`)_
* [Roadmap](#context-roadmap) _(was `context/roadmap.md`)_
* [RannRiders Redesign — Todo](#docs-notes-todo) _(was `docs/notes/todo.md`)_
* [Kutch Safari Resort — Design Brief (v2)](#docs-notes-ideas) _(was `docs/notes/ideas.md`)_

---

<a id="context-current-status"></a>

## Current Status (30 Sep 2026)
_(was `context/current_status.md`)_
### Works (evidence)
* Full guest booking, payment (test), status, receipts — 24/24 guest flow checks; browser click-through [CODE [`heal/REPORT.md`](09-HISTORY-TESTING-AND-GO-LIVE.md#heal-report)].
* Admin: bookings, payments/refunds (incl. confirming pending on cash), change booking with difference pricing, cancel, special prices, availability, enquiries, CSV — 35/35 admin checks [CODE].
* Pricing and money logic — 17/17 `test-changes.php`; every money and state rule 29/29 `test-logic.php` (with 250 random carts); many requests at once 12/12 `test-concurrency.php` (5 Oct 2026) [CODE].
* `BUGS.md` B1–B26 all fixed (5 Oct 2026); what is left there needs the owner (O1–O6) [CODE `BUGS.md`].
* Zero console errors/warnings and zero PHP errors on a full pass [CODE [`heal/REPORT.md`](09-HISTORY-TESTING-AND-GO-LIVE.md#heal-report)].
* Nonsense input: 107 values, 0 wrongly accepted [CODE [`chaos/REPORT.md`](09-HISTORY-TESTING-AND-GO-LIVE.md#chaos-report)].
* 30 Sep: Experiences (brochure content) and Around the Resort (places) are separate pages, both in the menu; the menu fits on one line from 1,320 px, ☰ below [CODE `Navbar.tsx`, `pages/Experiences.tsx`, `pages/AroundTheResort.tsx`].
* All website links and routes resolve; no sideways scroll at 375/768 px [CODE [`button_audit/REPORT.md`](09-HISTORY-TESTING-AND-GO-LIVE.md#button-audit-report)].

### Half-built / off
* Razorpay: test keys only; live secret must be regenerated [DOC].
* UPI QR: no UPI id set [CODE].
* Stayflexi: code present, endpoints unverified, off [CODE `lib/channel.php`].
* Email: needs SMTP on the host [CODE].
* Sample mode + test payments **on** (turn off for launch) [CODE].
* White Rann Camp: switched off [CODE].

### Broken / missing
* Enquiries are not stored: the enquiry form hands them to WhatsApp/email (5 Oct 2026; before, the Home form was a mock) [CODE `components/EnquiryForm.tsx`].
* Home hero video 10.2 MB; ~122 MB of unused files in `public/` (the photos were compressed on 6 Oct 2026) [DOC `docs/11`].
* No screen to close cottages by hand [DOC `docs/23`].
* No owner alert for new bookings [DOC].
* Brochure vs website facts disagree (distances, 34 vs 35+ years, room names); not changed, owner to confirm [DOC `docs/17`].
* Brochure photos are low resolution (from the PDF) [CODE `public/assets/images/brochure/`].
* Engine not deployed [DOC].

### Technical debt
No CI; no automated browser tests; old destination/camp pages use their own header/footer; MySQL under load untested (the race tests are SQLite-only) [CODE]. (`scripts/legacy/` deleted and old rate-limit rows cleared automatically, 5 Oct 2026.)

### Estimated completeness [INFERRED from the above]
| Area | % |
|---|---|
| Website pages | 90% (images, content placeholders) |
| Booking engine (guest) | 90% (live payments, UPI id, email) |
| Admin panel | 90% (close rooms, notifications) |
| Integrations | 30% (Stayflexi, SMTP, live Razorpay) |
| Deployment | 10% (not deployed) |

### Git
Repo is public on GitHub (pushed 29 Sep at the owner's request). 30 Sep work is local until committed/pushed; push only when the owner asks [CODE git].

### Data
Real DB: 7 bookings — owner's KSR-GJKQYG + 6 demo ("Demo …", @example.com) added at the owner's request; one demo (KSR-6JQ9JK) cancelled by the owner while exploring [CODE].

---

<a id="context-features"></a>

## Features
_(was `context/features.md`)_
Status: `DONE` works and is tested; `PARTIAL` works with gaps; `NOT STARTED`; `BROKEN`.

### Website (`frontend/`)
| Feature | Priority | Status | Where |
|---|---|---|---|
| 14 pages (incl. Around the Resort, 30 Sep) + 6 destination guides + 404 | High | DONE | [CODE `frontend/src/App.tsx`, `pages/`] |
| Book Now / Check status / /admin links into the engine | High | DONE | [CODE `lib/booking.ts`, `pages/BookingRedirect.tsx`] |
| Per-page titles, sitemap, robots | Medium | DONE (29 Sep) | [CODE `App.tsx usePageTitle`, `public/sitemap.xml`] |
| Experiences page from the owner's brochure (why visit, guest experiences, on request, assistance, WhatsApp) | High | DONE (30 Sep) | [CODE `pages/Experiences.tsx`, `public/assets/images/brochure/`] |
| Around the Resort page (6 places → guides), menu tab | High | DONE (30 Sep) | [CODE `pages/AroundTheResort.tsx`, `Navbar.tsx`] |
| Home welcome text (owner's wording) | Medium | DONE (30 Sep) | [CODE `pages/Home.tsx`] |
| Enquiry form (Home + `/enquire`; every Book Now while booking is off) | High | DONE (5 Oct): hands off to WhatsApp/email, not stored | [CODE `components/EnquiryForm.tsx`, `pages/Enquire.tsx`] |
| Gallery lightbox | Low | NOT STARTED | [CODE `pages/GalleryPage.tsx`] |
| Optimised images (was ~269 MB of originals) | Medium | PHOTOS DONE 6 Oct 2026; hero video and unused files open | [DOC `docs/11`] |

### Booking engine — guest (`backend/booking-engine/`)
| Feature | Priority | Status | Where |
|---|---|---|---|
| Search with per-room occupancy, mixed cottages | High | DONE | [CODE `api/availability.php`, `assets/engine.js`] |
| Extras (cars, dinners, birding) | High | DONE | [CODE `quote_cart`] |
| Arrival-time scroll wheel | Low | DONE | [CODE `engine.js arrivalWheel`] |
| Pay 50% / full; Razorpay; UPI QR; test payment | High | PARTIAL (Razorpay on test keys; UPI id not set; test payments on) | [CODE `lib/payment.php`, `config.local.php`] |
| Check status, receipt & terms PDF in the tab | High | DONE | [CODE `manage.php`, `document.php`, `lib/pdf.php`] |
| Guest cancellation | — | Intentionally off (desk cancels) | [CODE `api/booking-cancel.php`] |
| Input validation (lengths, phone, dates, quantities) | High | DONE (29 Sep) | [CODE `lib/db.php` checks] |

### Booking engine — admin
| Feature | Priority | Status | Where |
|---|---|---|---|
| SHA-256 sign-in, per-tab, idle timeout | High | DONE | [CODE `admin/login.php`, `_auth.php`] |
| One admin at a time (owner's requirement) | High | DONE (5 Oct) | [CODE `_auth.php` admin lock, `heartbeat.php`, `tab.php`] |
| No-show / left early for stays that have started | Medium | DONE (5 Oct) | [CODE `end_stay_early`, `admin/booking.php`] |
| Bookings list, cancel by code, UPI "Money received" | High | DONE | [CODE `admin/index.php`] |
| Booking page: payments, refunds (also after a cancellation), notes, cancel | High | DONE | [CODE `admin/booking.php`] |
| Change a booking (difference pricing) | High | DONE | [CODE `admin/edit.php`, `modification_delta`] |
| Availability tape chart by cottage + side panel | High | DONE | [CODE `admin/calendar.php`] |
| Special prices with guard rails | High | DONE | [CODE `admin/rates.php`] |
| Enquiries pipeline, CSV export | Medium | DONE | [CODE `admin/enquiries.php`, `export.php`] |
| Close cottages by hand (rooms on sale grid) | Medium | NOT STARTED (removed on request 26 Sep) | [DOC `docs/23`] |
| Owner notification of new bookings | Medium | NOT STARTED (email offered, undecided) | [DOC] |

### Integrations
| Feature | Status | Where |
|---|---|---|
| Stayflexi channel manager | PARTIAL (code written, endpoints unverified, switched off) | [CODE `lib/channel.php`], [DOC `docs/27`] |
| Email confirmations | PARTIAL (needs SMTP on the host) | [CODE `lib/mail.php`] |
| White Rann Camp booking | NOT STARTED for launch (switched off) | [CODE `properties.active = 0`] |

---

<a id="docs-16"></a>

## Known Issues and Bugs
_(was `docs/16-KNOWN-ISSUES-AND-BUGS.md`)_
_Re-checked against the code on 30 Sep 2026. Resolved items are at the bottom. Background on many of these: [`29-LESSONS-LEARNED-AND-GOTCHAS.md`](09-HISTORY-TESTING-AND-GO-LIVE.md#docs-29)._

### Critical
#### 0. Sample mode is ON: turn it off before going live
`backend/booking-engine/config.php` → `rules.guest_details_optional => true` makes the "Who is coming?" details optional for testing. Blank fields are saved blank; nothing is filled in. **Set it to `false` before launch**, or real guests can book without a name or phone number.

**Test payments are ON:** `config.php` → `test_payments.enabled => true` shows an "I've paid (test)" button that confirms a booking with **no money taken** (payments recorded as provider `test`). **Set it to `false` before launch**, or anyone can book for free.

**Razorpay:** test and live keys are in `backend/booking-engine/config.local.php` (git-ignored), both checked against Razorpay on 26 Sep 2026. This PC uses the **test** keys (no real money). For launch, the live server's `config.local.php` uses `$razorpay_live` plus a webhook secret. The live secret was shared over WhatsApp and chat, so **regenerate it in the Razorpay dashboard before launch**. PHP on Windows uses the Windows certificate store for HTTPS (`curl_trust_system_certs()` in `lib/db.php`).

**Also before launch:** the local admin login is username `manvir` with the test password `1234`. Set a long password on the live server with `php bin/setup.php --admin manvir "Manvir" "<long password>"`. The admin panel controls bookings, refunds and prices.

#### 1. Enquiries are not stored
Fixed on 5 Oct 2026 as far as a static site allows: the form (Home and `/enquire`) opens WhatsApp or email with the enquiry written out; nothing is faked as "sent". They still aren't kept anywhere by the site: an enquiry reaches the desk only if the guest presses Send. **Later:** once the engine is deployed, POST to its `api/enquiry.php` (stored, visible in admin).

#### 2. Booking engine not deployed
The PHP engine needs a PHP + MySQL host. Until then, every Book Now button on Vercel lands on the "call/WhatsApp us" fallback. **Fix:** see [`15-DEPLOYMENT-AND-INFRASTRUCTURE.md`](09-HISTORY-TESTING-AND-GO-LIVE.md#docs-15).

#### 3. Rates, tax and policies need owner sign-off
* Resort rates now follow the 2026–27 published tariff (GST included). Diwali and Christmas / New Year rates are still needed.
* GST slabs (5% / 18%) and the ₹7,875–₹8,850 edge case (Deluxe triple) need the accountant's confirmation. See [`08-PACKAGES-AND-PRICING.md`](04-BUSINESS-CONTENT-AND-PRICES.md#docs-08) §5.
* The cancellation ladder (free 30+ days, 75% at 21–29, 100% under 21) and pay-at-property rules need confirming.

#### 4. Asset weight (~140 MB, was ~269 MB)
Photos were compressed on 6 Oct 2026 (136.7 MB → 6.4 MB; Home images now 1.65 MB). Still open: the 10.2 MB Home hero video (no poster image) and about 122 MB of unused files in `public/`. See [`11-IMAGE-ASSET-INVENTORY.md`](03-DESIGN-AND-ASSETS.md#docs-11). (The 4.5 MB favicon and the 12.8 MB unused video preload were fixed on 29 Sep 2026.)

#### 50% plan: when is the balance due?
Guests are told "Balance due 30 days before arrival" (`payment_modes.advance.note`), and the admin, check-status page and receipt say "Due before arrival". Nothing reminds the guest or collects it automatically: the desk collects it. If it should be at check-in instead, change the note and the labels together (doc 22 §3).

#### Re-running full setup wipes prices
`php bin/setup.php` without `--admin` reloads `seed.sql`, which empties rooms, prices, special prices and extras first. Never run it on the live database (doc 28 §4).

#### White Rann Camp is switched off in the booking engine
Set `properties.active` back to 1 for `white-rann-camp` when the camp should be sold again. Check its tariff, peak dates and extras first. The website's White Rann Camp page still takes enquiries by WhatsApp.

### Major
#### 5–6. ~~Dead links, invisible Home tariff button~~
Fixed 29 Sep 2026 (see Resolved).

#### 7. Placeholder content
Plan Your Visit FAQ stub ("Frequently asked questions will be populated here"; the Footer's "FAQs" link opens it): needs the owner's questions and answers. Fixed 8 Oct 2026: the Dining food-photo box (now the resort's lake-view table photo) and the Navbar `[WRC LOGO]` (now a "White Rann Camp" button).

#### 8. Destination and RannUtsavPackage use old headers and footers
They have their own header (4.5 MB `logo-mark.png`), their own footer and no site navigation (so no "Around the Resort" menu or "Check status" link). The nested `<Link><a>` anchors were fixed on 29 Sep 2026.

#### 9. Booking engine's "Back to website" points at localhost
`properties.website_url` for the resort is `http://localhost:3000` (local database and `seed.sql`, for testing). Set it to the live site before launch.

#### 10. Stayflexi not connected
The engine's channel-manager bridge is off, and its API paths are guesses. Until it's connected, split inventory between the engine and the OTAs (engine README, "Safe — separated"). See doc 27.

#### 10a. No screen to close cottages by hand
The "Change rooms on sale" grid was removed from Availability at the owner's request (26 Sep 2026). Every cottage type is sold up to its total unless `inventory` rows say otherwise (from Stayflexi later). Ask for the grid back if rooms need closing for maintenance.

### Minor
11. Nothing links to `/destination/dholavira` (Road to Heaven & Dholavira goes to `road-to-heaven`).
12. Gallery: no lightbox; 19 of 26 images have generic "photo N of 26" alt text; narrow `prose` wrapper.
13. `NotFound.tsx` uses stock slate and blue styling.
14. Stay: garbled "appliqu├®" in the unused `Accommodation` component. The bathroom photo is under "Exterior".
15. ~~`ErrorBoundary` shows stack traces~~ fixed 29 Sep 2026 (dev only).
16. The heading colour rule in `index.css` overrides parent text colours (see [`04-DESIGN-SYSTEM.md`](03-DESIGN-AND-ASSETS.md#docs-04)).
17. ~~Sitemap~~ fixed: 16 URLs incl. `/around-the-resort`.
18. ~~Pinch zoom blocked~~ fixed 29 Sep 2026.
19. The booking engine looks different (Marcellus/Montserrat, its own palette) from the site.
20. ~~Root clutter~~ Resolved 29 Sep 2026: the one-off `*.py` edit scripts and `old_rooms.tsx` moved to `scripts/legacy/` (unused; delete once confirmed).
21. Git: the repo is on GitHub (public, pushed 29 Sep 2026 at the owner's request). The 30 Sep work (welcome text, Experiences/Around the Resort split, brochure images, menu breakpoint, doc updates) is local until committed and pushed. Only push when the owner asks.
22. `audit_log` also stores one row per rate-limited request (`rl_%`), so it keeps growing. Old `rl_%` rows can be deleted safely.
23. No email to the owner on a new booking (bots declined for now; email offered, not decided).
24. **Brochure vs website facts disagree** (30 Sep 2026, not changed, owner to confirm): Ahmedabad 375 vs 350 km, Mandvi 75 vs 60–65 km, Dholavira 115 vs 220 km, "34 years" vs "35+ years", "Kutchi AC Rooms – 8" vs Deluxe AC Cottage – 8; the brochure's airport (15 km) and railway (14 km) distances aren't on the site. See doc 17.
25. Brochure photos on Experiences are low resolution (200–590 px), taken from the PDF. Replace with originals when available.
26. Six **demo** bookings ("Demo …", @example.com) are in the local database for the owner's testing. Cancel/remove them before launch (doc 28 §3).

### Resolved
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
* **30 Sep 2026:** menu wrapped onto two lines at 1,024–1,440 px after adding "Around the Resort": the full menu now starts at 1,320 px (doc 14 §4a).
* **29 Sep 2026:** admin passwords were posted as plain text: now SHA-256 hashed in the browser (doc 13).
* **29 Sep 2026 (see [`../heal/`](09-HISTORY-TESTING-AND-GO-LIVE.md#heal-report), [`../button_audit/`](09-HISTORY-TESTING-AND-GO-LIVE.md#button-audit-report), [`../chaos/`](09-HISTORY-TESTING-AND-GO-LIVE.md#chaos-report) reports):** console error from nested links on destination/camp pages; favicon 404s; 5 broken links (`/white-rann-camp/tariff`, `/#explore` ×2, `/#rann-utsav`, FAQ jump); invisible Home tariff button; Home experience cards not linked; booking page too wide on phones; accessibility (menu/Instagram names, gallery alt text, two `h1`s); same title on every page; sitemap; stack trace shown to visitors; `check-system.php` wrote to the real database and tested the switched-off camp; cash at the desk didn't confirm a pending booking; payment start needed only a booking number; CSV ignored the search; unused status filters; 33 kinds of nonsense input accepted (incl. MySQL crash risks from over-long text); CSV formula injection.

---

<a id="context-decisions-and-assumptions"></a>

## Decisions and Assumptions
_(was `context/decisions_and_assumptions.md`)_
### Key decisions
| Decision | Reason | Source |
|---|---|---|
| Engine is a separate PHP app at `/book/` | cPanel-friendly, survives Vercel (the old JSON-file engine couldn't), keeps keys off the static host | [DOC docs/20] |
| One pricing function `price_rooms()` | the list and checkout used to disagree | [DOC docs/21] |
| Changes priced as a difference | owner: "added total added, removed total subtracted" | [DOC docs/22] |
| 50% balance labelled "before arrival" | matches the payment note guests see | [CODE config.php] |
| Only the admin cancels | owner decision | [CODE booking-cancel.php] |
| Availability by cottage only; guest side panel | owner/friend's request (26 Sep) | [DOC docs/23] |
| No browser pop-ups (on-page confirm) | owner | [DOC docs/24] |
| Admin credentials sent as SHA-256 | owner request (29 Sep); server still bcrypts | [CODE admin/login.php] |
| Experiences (brochure) and Around the Resort (places) are separate menu tabs | owner request (30 Sep) | [CODE `App.tsx`, `Navbar.tsx`] |
| Brochure facts not applied where they disagree with the site | "don't change anything else"; owner decides | [DOC docs/17] |
| Folder split frontend/ backend/ (api/ stays at root) | clean structure; Vercel needs `/api` at root | [CODE restructure/REPORT.md] |
| `config.php` defaults to SQLite; `website_url` localhost in seed | owner/friend request; live config must override | [CODE] |
| No fake data unless the owner asks; demo data marked | owner rule | [DOC] |
| Tests only on a throwaway DB copy | same rule | [CODE bin/test-changes.php] |

### Assumptions
1. The live site will be served over HTTPS [INFERRED: required for payments].
2. One owner account is enough (no staff roles) [CODE: single role].
3. Guests are mostly Indian mobile users [INFERRED: +91 examples, ₹, UPI].
4. The engine will be hosted under the same domain at `/book/` [DOC docs/15 default].

### Open questions for the owner
1. Where will the engine be hosted (which PHP + MySQL host)? [UNKNOWN]
2. Is GST 18% right for the ₹8,000 Deluxe triple (inclusive price falls between slabs)? [UNKNOWN — accountant]
3. 50% plan: is the balance due 30 days before arrival (current wording) or at check-in? [UNKNOWN]
4. Stayflexi: connect it, or keep some cottages only for direct bookings? [UNKNOWN]
5. Diwali / Christmas–New Year resort prices (set as special prices)? [UNKNOWN]
6. Should new bookings email the owner (and which address)? [UNKNOWN]
7. Is the cancellation ladder (free 30+, 75% 21–29, 100% < 21) final? [UNKNOWN]
8. When should the 6 demo bookings be removed? [UNKNOWN]
9. ~~Delete `scripts/legacy/`?~~ Done 5 Oct 2026 (B25; the files are in git history).
10. What do gallery photos 8–26 show (for alt text)? [UNKNOWN]
11. Brochure vs website: Ahmedabad 375/350 km, Rajkot 250/240, Mandvi 75/60–65, Dholavira 115/220, 34 vs 35+ years, "Kutchi AC Rooms – 8" vs Deluxe AC – 8. Which are right? Add airport 15 km / railway 14 km to Plan Your Visit? [UNKNOWN]
12. Can the owner send original (high-resolution) brochure photos? [UNKNOWN]

### Risks
Overselling while Stayflexi is off; live key exposure (secret shared in chat); sample mode left on at launch; re-running full setup on live wipes prices; SQLite hides length errors that MySQL would raise (now guarded in code) [CODE/DOC].

---

<a id="context-roadmap"></a>

## Roadmap
_(was `context/roadmap.md`)_
Effort: S (hours), M (a day or two), L (several days). All items [PLANNED].

### Now (before real guests)
| Item | Effort | Depends on |
|---|---|---|
| Choose PHP + MySQL host; deploy engine at `/book/` | M | owner: hosting account |
| Regenerate live Razorpay secret; live keys + webhook in server config | S | owner: Razorpay dashboard |
| Turn off sample mode, test payments, debug; long admin password | S | — |
| Set `website_url` / `base_url` to the live site | S | deploy |
| SMTP for confirmation emails | S | owner: mail account |
| Decide Stayflexi: connect (verify endpoints) or split rooms by hand | M–L | owner + Stayflexi support |
| Remove demo bookings (names "Demo …") | S | owner says when |

### Next
| Item | Effort |
|---|---|
| Store enquiries via `api/enquiry.php` once the engine is deployed | S |
| Owner email alert on new booking | S |
| UPI id for QR payments | S |
| Screen to close cottages for maintenance (rooms on sale) | M |
| Optimise images (WebP, sizes) | M |
| Apply the owner's answers on brochure vs site facts; swap in high-res brochure photos | S |
| Confirm GST on the ₹8,000 Deluxe triple; 50% balance timing; festival prices | S (owner) |

### Later
| Item | Effort |
|---|---|
| Re-enable White Rann Camp with checked prices | M |
| Gallery lightbox; real alt text for 19 photos | S |
| Shared header/footer on destination and camp pages | S |
| CI with typecheck, build, PHP lint and `bin/test-changes.php`, `test-logic.php`, `test-concurrency.php` | M |
| Run `bin/test-concurrency.php`'s scenarios against a MySQL copy before launch | S |
| Automated browser tests (Playwright) for the booking flow | L |

Recommended order: host → config switches → payments → Stayflexi decision → emails → contact form → the rest.

---

<a id="docs-notes-todo"></a>

## RannRiders Redesign — Todo
_(was `docs/notes/todo.md`)_
### Research Phase
- [ ] Locate and open the official RannRiders website
- [ ] Analyze visual design: colors, typography, hero style, imagery usage
- [ ] Document page structure: navigation, sections, CTAs, footer
- [ ] Document content approach: copy tone, section themes, features shown
- [ ] Capture reference screenshots of key sections
- [ ] Save analysis notes to a file

### Redesign Phase
- [ ] Update ideas.md with RannRiders as ground-truth reference
- [ ] Adapt global theme (index.css) to match RannRiders palette and fonts
- [ ] Rebuild navbar and hero in RannRiders style
- [ ] Rebuild content sections (stay types, experiences, festival/activities, gallery, dining, contact) matching reference structure
- [ ] Generate any new tailored images needed
- [ ] Verify screenshots against reference, fix issues

### Delivery Phase
- [ ] Save checkpoint and deliver with summary + next-step suggestions

### Follow-up: Welcome Section Video
- [ ] Upload AerialView3.mp4 to webdev storage
- [ ] Rework Welcome section: two-column layout with video preview on one side
- [ ] Verify rendering and checkpoint

### Follow-up: Background Video Hero
- [ ] Compress the 97MB aerial video for fast web playback (muted autoplay)
- [ ] Rework Welcome section into full-screen hero with background video + overlaid text
- [ ] Verify rendering (text contrast, autoplay, mobile) and checkpoint

### Follow-up: Restore Old Sundown Terracotta Style (keep current structure)
- [ ] Restore ivy/sand/terracotta palette + Cormorant Garamond in index.css and fonts
- [ ] Update Home.tsx section styling classes (bands, buttons, headings, footer) to old style while keeping video hero + current sections
- [ ] Verify rendering and checkpoint

### Follow-up: Competitor Research + Feature Integration
- [ ] Identify competitors of Kutch Safari Resort in Bhuj/Kutch
- [ ] Audit competitor websites: presence, quality, functionality
- [ ] Select best features to add
- [ ] Implement chosen features into the site
- [ ] Verify, checkpoint, and deliver findings + updated site

---

<a id="docs-notes-ideas"></a>

## Kutch Safari Resort — Design Brief (v2)
_(was `docs/notes/ideas.md`)_
### Ground Truth: RannRiders.com (user-provided reference)
The site must follow the RannRiders design system as the ground-truth spec (see /home/ubuntu/rannriders_analysis.md). Key rules:
- Alternating white ↔ warm sand (#DDB58D-ish) full-width section bands
- Centered uppercase serif section titles (Playfair/Cormorant-like), italic serif tagline under the title
- Terracotta/burnt-orange (#D2622D) rectangular buttons with uppercase text ("EXPLORE", "Enquire Now")
- Faint wildlife line-art sketch behind/near section titles as decoration
- Photo mosaics: 1 tall + 2 stacked, or large + 2 stacked, or 3-col grid patterns
- Dark charcoal footer with sub-link columns, credits band, contact icons, social tiles
- Floating fixed "Enquire Now" terracotta pill button + WhatsApp/call widget bottom-right
- Sticky white navbar: wordmark left, centered uppercase nav links, BOOK NOW right
- Copy tone: experiential, "path less trodden", tagline under hero headline


## Kutch Safari Resort — Design Brainstorm (v1, superseded)

### Three Stylistic Approaches

#### 1. Sundown Terracotta (editorial heritage)
Warm earthy editorial style rooted in Kutchi craft — terracotta, ochre, and sand tones with serif display type, evoking a printed travel journal about the White Rann. Emotional intent: warmth, authenticity, cultural richness.
Probability: 0.07

#### 2. Midnight Mirrors (dark luxury with mirror-work accents)
A deep indigo/night-sky palette referencing the famous Kutch starlit nights and mirror-work textiles, with glowing gold accents and crisp minimal layouts. Emotional intent: romance, exclusivity, quiet luxury.
Probability: 0.03

#### 3. Daybreak Lakehouse (airy lakeside modern)
Bright, airy lakeside aesthetic — pale aqua and white reflecting the Rudramata reservoir at dawn, with rounded organic shapes and soft photography. Emotional intent: freshness, calm, simplicity.
Probability: 0.05

### CHOSEN: Sundown Terracotta (editorial heritage)

**Design Movement**: Heritage editorial / Indian craft modernism — inspired by travel magazines, Kutchi handicraft aesthetics (mirror work, mud appliqué, Rogan art), and contemporary Indian hospitality branding.

**Core Principles**:
1. Warmth over polish — the palette must feel like sun on white mud walls, not like a hotel brochure.
2. Craft texture everywhere — subtle grain, border motifs, and asymmetric compositions echo Kutchi textile work.
3. The sunrise is the hero — imagery and gradients always celebrate the lake-facing dawn moment.
4. Editorial asymmetry — offset grids, overlapping panels, and generous margins, never flat centered stacks.

**Color Philosophy**:
- Base: warm sand / ivory (like lime-washed bhunga walls) — `oklch(0.97 0.015 85)`
- Ink: deep brown-charcoal — `oklch(0.28 0.03 50)`
- Primary: deep terracotta / sindoor red — `oklch(0.55 0.16 35)` — the color of Kutch mud plaster and sunsets
- Accent: saffron-gold — `oklch(0.75 0.13 75)` — mirroring mirror-work gold
- Supporting: muted olive-green — the lush lawn around cottages
The intent: evoke heat, earth, and craft. No blues, no purples.

**Layout Paradigm**: Asymmetric editorial spreads. Hero is a split composition (text left over ivory, full-bleed image right). Sections alternate ivory/sand/terracotta bands with diagonal or stepped transitions. Cards and photo panels are offset, overlapping with thin 1px ink frames like framed textile art, not uniform white cards with shadows.

**Signature Elements**:
1. "Mirror-frame" motif — thin double-line borders with small diamond/corner marks, inspired by Kutch mirror work, used on images and cards.
2. Terracotta sun disc — a simple circular sun motif used as bullets, section markers, and the logo mark.
3. Stitched border strips — thin dashed/serrated rules between sections, echoing textile seams.

**Interaction Philosophy**: Interactions feel tactile and slow-warm: images gently lift and warm on hover, links underline with a hand-drawn stroke, buttons compress like pressed clay. Nothing bounces or glows.

**Animation**: Entrance animations are soft fades + 12px upward drift, 500–700ms, ease-out, staggered 60–80ms. Image hovers: scale 1.03 over 600ms. Underline draw-in for links. Respect prefers-reduced-motion.

**Typography System**:
- Display: "Cormorant Garamond" (600/700) — tall, characterful serif with Indian-print flavor
- Body: "Jost" or "Karla" (400/500) — clean geometric humanist
- Accent/overlines: uppercase letter-spaced Jost 600, terracotta
- Hierarchy: overline kicker → big serif headline → readable body; drop caps on intro paragraphs

**Brand Essence**: The lakeside gateway to the White Rann — 17 mirror-work bhunga cottages on 10 acres above the Rudramata reservoir, where Kutchi craft meets a 20-year legacy of warm hospitality. Adjectives: warm, handcrafted, welcoming.

**Brand Voice**: Poetic but grounded; speaks like a host, not a marketer. No "Welcome to our website".
Examples:
- "Wake where the sun paints the lake."
- "Twenty years of sunrise, served warm with chai."

**Wordmark & Logo**: "KUTCH SAFARI" set in Cormorant Garamond small caps with a terracotta sun-disc glyph (circle with a dot) to the left; "RESORT · BHUJ" in letter-spaced Jost beneath. Mark: terracotta sun disc on ivory.

**Signature Brand Color**: Terracotta sindoor `#B54A2B`-ish (oklch 0.55 0.16 35) — the color of Kutch mud walls at sunset.
