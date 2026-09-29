# Features
> Purpose: every feature with status and location | Mode: A | Confidence: High | Related: [current_status.md](current_status.md), [roadmap.md](roadmap.md)

Status: `DONE` works and is tested; `PARTIAL` works with gaps; `NOT STARTED`; `BROKEN`.

## Website (`frontend/`)
| Feature | Priority | Status | Where |
|---|---|---|---|
| 13 pages + 6 destination guides + 404 | High | DONE | [CODE `frontend/src/App.tsx`, `pages/`] |
| Book Now / Check status / /admin links into the engine | High | DONE | [CODE `lib/booking.ts`, `pages/BookingRedirect.tsx`] |
| Per-page titles, sitemap, robots | Medium | DONE (29 Sep) | [CODE `App.tsx usePageTitle`, `public/sitemap.xml`] |
| Home contact form | High | BROKEN (mock: shows "sent", sends nothing) | [CODE `pages/Home.tsx ContactSection`] |
| Gallery lightbox | Low | NOT STARTED | [CODE `pages/GalleryPage.tsx`] |
| Optimised images (~269 MB originals) | Medium | NOT STARTED | [DOC `docs/11`] |

## Booking engine — guest (`backend/booking-engine/`)
| Feature | Priority | Status | Where |
|---|---|---|---|
| Search with per-room occupancy, mixed cottages | High | DONE | [CODE `api/availability.php`, `assets/engine.js`] |
| Extras (cars, dinners, birding) | High | DONE | [CODE `quote_cart`] |
| Arrival-time scroll wheel | Low | DONE | [CODE `engine.js arrivalWheel`] |
| Pay 50% / full; Razorpay; UPI QR; test payment | High | PARTIAL (Razorpay on test keys; UPI id not set; test payments on) | [CODE `lib/payment.php`, `config.local.php`] |
| Check status, receipt & terms PDF in the tab | High | DONE | [CODE `manage.php`, `document.php`, `lib/pdf.php`] |
| Guest cancellation | — | Intentionally off (desk cancels) | [CODE `api/booking-cancel.php`] |
| Input validation (lengths, phone, dates, quantities) | High | DONE (29 Sep) | [CODE `lib/db.php` checks] |

## Booking engine — admin
| Feature | Priority | Status | Where |
|---|---|---|---|
| SHA-256 sign-in, per-tab, idle timeout | High | DONE | [CODE `admin/login.php`, `_auth.php`] |
| Bookings list, cancel by code, UPI "Money received" | High | DONE | [CODE `admin/index.php`] |
| Booking page: payments, refunds, notes, cancel | High | DONE | [CODE `admin/booking.php`] |
| Change a booking (difference pricing) | High | DONE | [CODE `admin/edit.php`, `modification_delta`] |
| Availability tape chart by cottage + side panel | High | DONE | [CODE `admin/calendar.php`] |
| Special prices with guard rails | High | DONE | [CODE `admin/rates.php`] |
| Enquiries pipeline, CSV export | Medium | DONE | [CODE `admin/enquiries.php`, `export.php`] |
| Close cottages by hand (rooms on sale grid) | Medium | NOT STARTED (removed on request 26 Sep) | [DOC `docs/23`] |
| Owner notification of new bookings | Medium | NOT STARTED (email offered, undecided) | [DOC] |

## Integrations
| Feature | Status | Where |
|---|---|---|
| Stayflexi channel manager | PARTIAL (code written, endpoints unverified, switched off) | [CODE `lib/channel.php`], [DOC `docs/27`] |
| Email confirmations | PARTIAL (needs SMTP on the host) | [CODE `lib/mail.php`] |
| White Rann Camp booking | NOT STARTED for launch (switched off) | [CODE `properties.active = 0`] |
