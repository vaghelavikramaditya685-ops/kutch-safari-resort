# Button, link and page audit (29 Sep 2026)

Snapshot before any fix: branch `heal/backup-2026-09-29` (same run as self-healing).

## 1. Totals
| What | Found | Result |
|---|---|---|
| Website routes | 19 (13 pages + 6 destination guides) + redirects `/booking`, `/book`, `/admin` + 404 | all load; 1 route added (`/white-rann-camp/tariff`) |
| Website links (distinct targets) | 21 internal/external + tel/mailto/WhatsApp | 5 broken → fixed; 6 dead cards → wired up |
| Website buttons | Book Now ×3, mobile menu, contact form, Stay lightbox, Check status ×4 | 1 invisible → fixed; 1 unnamed → fixed |
| Booking-engine actions (guest + admin) | 59 scripted checks + browser click-through of every screen (see heal/REPORT.md, docs/30) | all pass |
| Engine pages at phone/tablet/desktop | booking, status, sign-in, receipt/terms viewer, admin | 1 overflow at phone width → fixed |

## 2. Navigation bugs fixed (old → new)
| Link | Where | Before | After |
|---|---|---|---|
| "Rann Utsav 2026–27" | Footer (every page) | `/white-rann-camp/tariff` → 404 page | new route: opens the White Rann Camp page scrolled to its tariff table |
| "2026-27 Tariff" | Home, sister-property section | same 404, **and invisible** (white text on beige) | same route; terracotta outline button |
| "Back to Beyond Bhuj" ×2 | destination guides, "not found" state | `/#explore` (no such section, lands at top of Home) | `/experiences` (the destination cards) |
| "Back" | White Rann Camp header | `/#rann-utsav` (no such section) | `/` |
| "FAQs" | Footer | `/plan-your-visit#faq` opened at the top | lands on the FAQ section (instant jump, clears the sticky header) |
| 6 "Discover →" cards | Home, Experiences section | looked clickable, went nowhere | link to the same destination guides as the Experiences page |

Checked by clicking each in the browser: every link now reaches the right page, and the tariff table and FAQ land on screen just below the header.

## 3. Responsive (375 / 768 / 1440 px)
All 12 website pages and all engine pages checked for sideways scrolling at phone and tablet width.
* **Fixed:** booking page at phone width was 389 px wide on a 375 px screen: "Already booked? Check status" in the engine header could not wrap and spilled past the edge, so phones zoomed the whole page out. Header buttons may now wrap onto two lines below 960 px (`assets/engine.css`).

## 4. Accessibility
| Fix | Where |
|---|---|
| Mobile menu button had no name ("button") → "Open menu"/"Close menu" + `aria-expanded` | `components/Navbar.tsx` |
| Footer Instagram icon link had no name → "Kutch Safari Resort on Instagram" | `components/Footer.tsx` |
| 26 gallery photos all `alt="Gallery"` → 7 described from their file names, 19 camera-numbered ones "Kutch Safari Resort, photo N of 26"; removed the pointer cursor (clicking does nothing) | `pages/GalleryPage.tsx` |
| Pinch-zoom was blocked (`maximum-scale=1`) → allowed | `index.html` |
| Two `<h1>` per page (the footer brand was an `h1`) → footer brand is a `<p>` with the same look; Home's hero "Where the Lake Meets the Desert" is now its `<h1>` (same look) | `Footer.tsx`, `Home.tsx` |

## 5. SEO, content, security, performance
* **Titles:** every page had the same browser title → 12 distinct titles (per page and per destination; "Page not found" for 404s) (`App.tsx`).
* **Sitemap:** listed 4 of 15 public pages → all 15 (`public/sitemap.xml`). **robots.txt:** now `Disallow: /book/` and `/admin` (the engine also sends `noindex`).
* **Error screen** showed the technical stack trace to visitors → only in development (`ErrorBoundary.tsx`).
* **Packages:** `npm audit` 0 vulnerabilities. **Secrets:** none in tracked files. External links opening new tabs all have `rel="noopener"`/`noreferrer`.
* **Favicon:** the tab icon was the 4.5 MB `logo-mark.png` (wrong type) → 9 KB `favicon.ico` + touch icon (see heal/REPORT.md F3).
* **Caching:** engine script and styles are now versioned (`?v=` last-change time) so a browser never keeps an old copy after an update — this mattered because the payment button's request changed.

## 6. Needs a human decision (not changed)
1. **Image weight (~269 MB in `frontend/public/assets`)**: converting to WebP and resizing is a content job with visual checks; see docs/11 for the plan.
2. **Home contact form** is still a mock (shows "sent", sends nothing) — wiring it to the engine's `api/enquiry.php` is a product decision (docs/13).
3. **Gallery photos 8–26**: real descriptions need someone who knows what each photo shows.
4. **Destination and White Rann Camp pages** still use their own old header/footer (owner's layout; left as is).
5. **Stay lightbox** has no next/previous, and the Gallery has no lightbox.

## 7. How to re-run
* Engine: `php backend/booking-engine/bin/test-changes.php` (17 checks) and `php backend/booking-engine/bin/check-system.php` (both use a temporary copy of the database).
* Website: open each route in `frontend/src/App.tsx`, check the console, and check `document.documentElement.scrollWidth` at 375 px.
