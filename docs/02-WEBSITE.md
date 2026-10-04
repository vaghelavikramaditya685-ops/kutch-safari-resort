# The Website (frontend/)

_Combined on 30 Sep 2026 from 10 earlier files. Start at [`README.md`](../README.md) for the overview and hard rules._

Routes, menu, every page, shared components, SEO, forms and user journeys of the React site.

---

## Contents
* [Frontend Spec](#context-frontend-spec) _(was `context/frontend_spec.md`)_
* [Routing and Navigation](#docs-03) _(was `docs/03-ROUTING-AND-NAVIGATION.md`)_
* [Home Page (`frontend/src/pages/Home.tsx`)](#docs-05) _(was `docs/05-HOME-PAGE.md`)_
* [Accommodation Pages](#docs-06) _(was `docs/06-ACCOMMODATION-PAGES.md`)_
* [Destination Routing and Content System](#docs-07) _(was `docs/07-DESTINATION-SYSTEM.md`)_
* [Content Pages](#docs-10) _(was `docs/10-CONTENT-PAGES.md`)_
* [Shared Components Reference](#docs-09) _(was `docs/09-SHARED-COMPONENTS.md`)_
* [SEO and Meta Configuration](#docs-12) _(was `docs/12-SEO-AND-META.md`)_
* [Forms and Interactions](#docs-13) _(was `docs/13-FORMS-AND-INTERACTIONS.md`)_
* [User Flow and UX](#docs-19) _(was `docs/19-USER-FLOW-AND-UX.md`)_

---

<a id="context-frontend-spec"></a>

## Frontend Spec
_(was `context/frontend_spec.md`)_
### Website routes (`frontend/src/App.tsx`) [CODE]
| Route | Page | Purpose |
|---|---|---|
| `/` | Home | hero, stay, sister property, experiences (link to guides), contact (mock form) |
| `/stay` | Stay | the two cottage types (masonry photos, lightbox) |
| `/experiences` | Experiences | brochure content: Why Visit Kutch?, guest experiences, on request, assistance |
| `/around-the-resort` | AroundTheResort | 6 places → destination guides (menu item "Around the Resort") |
| `/our-journey`, `/dining`, `/gallery`, `/plan-your-visit` (`#faq`), `/packages` | content pages |
| `/destination/:slug` | Destination | 6 guides (own old header/footer) |
| `/rann-utsav-package`, `/white-rann-camp`, `/white-rann-camp/tariff` | RannUtsavPackage | camp + tariffs (tariff route scrolls to the table) |
| `/booking`, `/book`, `/book/*`, `/admin`, `/admin/*` | BookingRedirect | full-page redirect to the engine |
| anything else | NotFound | 404 |

* **Titles:** `usePageTitle()` sets a title per route [CODE `App.tsx`].
* **Shared components:** `Navbar` (top bar with "Already booked? Check status", mobile menu with aria-label), `Footer`, `ErrorBoundary` (stack only in dev) [CODE].
* **State:** local `useState` only; no global store [CODE].
* **Engine links:** always plain `<a href>` from `lib/booking.ts` (`bookingUrl`, `statusUrl`, `adminUrl`), never wouter `<Link>` [CODE].
* **Scroll:** pages scroll to top on mount; `/plan-your-visit#faq` and `/white-rann-camp/tariff` jump (instant) to their section [CODE].

### Booking page front end (`backend/booking-engine/assets/engine.js`, ~1,000 lines) [CODE]
Steps: 1 rooms (occupancy per room, Select + room ticks for several rooms) → 2 extras → 3 details (arrival wheel, pay 50%/full, terms link) → 4 payment (Razorpay / UPI QR / I've paid (test)) → confirmation (receipt, status, remembered in `localStorage ksr_last_booking`).
* Talks to `api/*.php` via `api()` (fetch JSON) — see [api_spec.md](05-BOOKING-ENGINE.md#context-api-spec).
* Validates phone/email before sending; a server refusal about guest details keeps the step and the typed values (`res.field === 'guest'`) [CODE].
* Scripts/styles are versioned `?v=<filemtime>` so updates are picked up [CODE `index.php`].

### Admin front end
Server-rendered PHP pages with small inline scripts: on-page confirm box for any `data-confirm` form/button (no browser pop-ups), per-tab sign-in mark and the one-admin heartbeat (`heartbeat.php`), Availability tape chart + side panel, SHA-256 hashing on the sign-in page [CODE `admin/_auth.php`, `calendar.php`, `login.php`].

---

<a id="docs-03"></a>

## Routing and Navigation
_(was `docs/03-ROUTING-AND-NAVIGATION.md`)_
### 1. Application Routes (`App.tsx`)

wouter `<Switch>`: the first match wins.

| Path | Component | Notes |
|---|---|---|
| `/` | `Home.tsx` | Landing page |
| `/stay` | `Stay.tsx` | Cottages |
| `/experiences` | `Experiences.tsx` | Content from the 2026–27 resort brochure: Why Visit Kutch?, guest experiences, arrangements on request, assistance (30 Sep 2026) |
| `/around-the-resort` | `AroundTheResort.tsx` | "Around the Resort" menu item: 6 places (White Rann, Road to Heaven, Banni, Kala Dungar, Mandvi, Bhuj) linking to destination guides |
| `/our-journey` | `OurJourney.tsx` | Placeholder content |
| `/dining` | `Dining.tsx` | The Banni |
| `/gallery` | `GalleryPage.tsx` | 26 photos, no lightbox |
| `/plan-your-visit` | `PlanYourVisit.tsx` | Directions, distances, FAQ stub (`#faq`) |
| `/packages` | `Packages.tsx` | Colors of Kutch overview |
| `/destination/:slug` | `Destination.tsx` | 6 slugs, see [`07-DESTINATION-SYSTEM.md`](#docs-07) |
| `/rann-utsav-package` | `RannUtsavPackage.tsx` | White Rann Camp + package tariffs |
| `/white-rann-camp` | `RannUtsavPackage.tsx` | Alias |
| `/booking`, `/book`, `/book/*` | `BookingRedirect.tsx` | Full-page redirect to the PHP booking engine |
| `/admin`, `/admin/*` | `BookingRedirect.tsx` | Short address for staff: redirects to the engine's admin panel (`adminUrl()`). Typing `/book/admin` (no slash) also works: the engine redirects to `/book/admin/` and on to `login.php`, so the address fills itself in |
| `/404`, anything else | `NotFound.tsx` | Stock template styling |

#### 1.1 Booking engine URLs
The engine is not a React route. It is linked with `bookingUrl()` from `frontend/src/lib/booking.ts`:

```ts
bookingUrl()                                          // /book/?property=kutch-safari-resort
bookingUrl({ property: "white-rann-camp" })
bookingUrl({ checkIn: "2026-12-20", checkOut: "2026-12-22", adults: 2, rooms: 1 })
```

| Engine page | Purpose |
|---|---|
| `/book/?property=…` | Search, room, extras, details, payment |
| `/book/manage.php` | "Already booked? Check status": find by code + mobile/email (or `?ref=&token=`), receipt, terms, call (cancelling is done by the admin) |
| `/book/document.php?doc=receipt&ref=&token=` | Receipt shown as a PDF in a new tab (never downloads) |
| `/book/document.php?doc=terms&property=` | Terms and conditions as a PDF in a new tab |
| `/book/admin/` | Staff panel (login required): `index.php` bookings, `booking.php?id=` one booking, `edit.php?id=` change it, `calendar.php` Availability, `rates.php` Special prices, `enquiries.php`, `export.php`. See [`24-ADMIN-PANEL-GUIDE.md`](06-ADMIN-AND-SECURITY.md#docs-24) |

`BOOKING_URL` defaults to `/book/`. You can override it at build time with `VITE_BOOKING_URL`. `statusUrl()` → `manage.php` and `adminUrl()` → `admin/` are built from it too.

#### 1.2 Broken links
| Link | Where | Result |
|---|---|---|
| `/white-rann-camp/tariff` | Footer, Home sister-property section | 404 |
| `/#explore` | Destination header and "not found" state | No such section on Home |
| `/#rann-utsav` | RannUtsavPackage header | No such section on Home |

---

### 2. Navbar (`components/Navbar.tsx`)
Used by every page except `Destination` and `RannUtsavPackage`, which have their own old headers.

* **Top bar** (hidden below `md`): phone, email, address, Instagram, and **Already booked? Check status** (`statusUrl()`).
* **Sticky header:** logo `logo-main.jpg` with `mix-blend-darken`.
* **`NAV_LINKS`:** Home, Our Journey, Stay, Dining, Experiences, Around the Resort, Packages, Gallery, Plan Your Visit, followed by a terracotta **White Rann Camp →** link (`/white-rann-camp`).
* **`[WRC LOGO]` button:** placeholder text. It opens `https://whiteranncamp.travstack.com` in a new tab.
* **Book Now** (desktop and mobile menu): a plain `<a href={bookingUrl()}>`, which loads the engine.
* **Menu breakpoint (30 Sep 2026):** the full menu shows from 1,320 px wide, every item on one line (header box up to 1,440 px, slightly tighter letter spacing). Below that, the menu button opens the full-screen menu. With 10 items the menu no longer fits at 1,024–1,280 px without wrapping.
* **Mobile menu:** full-screen overlay toggled by `open` state. It includes Book Now and Already booked? Check status. Book Now never wraps (`whitespace-nowrap`).

### 3. Footer (`components/Footer.tsx`)
Four columns:
* **Brand:** text logo, short description, Instagram.
* **Explore:** About the Resort (`/our-journey`), Rooms & Tariff (`/stay`), Dining, Experiences, Around the Resort, Gallery.
* **Plan:** Colors of Kutch Packages (`/packages`), How to Reach (`/plan-your-visit`), FAQs (`/plan-your-visit#faq`), White Rann Camp (`/white-rann-camp`), Rann Utsav 2026–27 (`/white-rann-camp/tariff`, **broken**), Already booked? Check status (`statusUrl()`).
* **Reservations:** phone, email, address.

### 4. External Links
* WhatsApp: `https://wa.me/919925238599`, with a pre-filled message on Destination and RannUtsavPackage.
* Instagram: `https://www.instagram.com/kutchsafariresort/`
* WRC portal: `https://whiteranncamp.travstack.com` (Navbar placeholder button only)
* Booking engine: `bookingUrl()`, `statusUrl()`, `adminUrl()`. Always a plain `<a>`, never a wouter `<Link>` (the engine is a separate app and needs a full page load).

---

<a id="docs-05"></a>

## Home Page (`frontend/src/pages/Home.tsx`)
_(was `docs/05-HOME-PAGE.md`)_
_Updated 30 Sep 2026._

### 1. Overview
About 375 lines. Uses the shared `Navbar` and `Footer`, calls `window.scrollTo(0, 0)` on mount, and has these sections in order:

Hero video → TrustStrip → Welcome → StatsBand → The Stay → Sister Property → AmenitiesGrid → Experiences (places) → ContactSection

Browser title: "Kutch Safari Resort | Bhunga Cottages by the Lake, Bhuj" (set per page by `usePageTitle()` in `App.tsx`).

---

### 2. Sections

#### 2.1 Hero
* Background video: `/assets/images/new/kutch-safari-resort-website-hero.mp4` (10.2 MB), autoplay/muted/loop, `bg-black/40` overlay.
* Kicker "Bhuj · Rann of Kutch". The heading **"Where the Lake Meets the Desert"** is the page's only `<h1>` (white, set on the heading itself because of the heading colour rule, doc 04 §3).
* CTAs:
  * **Check Availability & Book** → `bookingUrl()` (PHP booking engine, full page load)
  * **Explore Kutch** → `/around-the-resort` (the places around the resort; was `/experiences` until 30 Sep 2026)
  * Under them, a text link **Already booked? Check status** → `statusUrl()` (the engine's `manage.php`)
* The unused 12.8 MB `KSR_VIDEO.mp4` preload was removed from `index.html` on 29 Sep 2026.

#### 2.2 TrustStrip
"4.6/5 on Google Reviews", "4.5/5 on TripAdvisor", "MakeMyTrip Assured". Hard-coded; confirm against the live listings.

#### 2.3 Welcome (text replaced by the owner on 30 Sep 2026)
Two staggered photos (Deluxe exterior, Kutchi interior), then:
* Kicker: **Welcome to Kutch Safari Resort**
* Heading (`h2`): **Where Tradition Meets Comfort on the Road to the White Rann** (from the 2026–27 brochure's tagline "Where Tradition Meets Comfort")
* Three paragraphs (owner's exact wording):
  1. One of Bhuj's hidden gems: a thoughtfully designed resort on a rise overlooking the Rudramata Dam, authentic Kutchi charm with modern comfort; 15 km (about 20 minutes) from Bhuj on the Khavda road to Dhordo, the White Rann and Dholavira; an ideal base for exploring Kutch.
  2. Twenty cottages face the water, traditional architecture plus comfortable bedding, free Wi-Fi, flat-screen TV, room service, private balcony; The Banni restaurant: Kutchi, Gujarati, Punjabi, Chinese and Continental.
  3. For textile enthusiasts, culture lovers or a peaceful getaway: scenic views, fresh desert air, personalised hospitality; more than three decades of welcoming travellers.
* Link: **Our Story** → `/our-journey`.

#### 2.4 StatsBand
20 Lake View Cottages · 35+ Years of Hosting · 15 Km from Bhuj. (The brochure says "34 years": owner to confirm, doc 17.)

#### 2.5 The Stay
Two cards: Kutchi AC Cottage ("12 Cottages") and Deluxe AC Cottage ("8 Cottages"). Each **Book Now** → `bookingUrl()` (the guest picks the room inside the engine). "All Rooms" → `/stay`.

#### 2.6 Sister Property — White Rann Camp
Image `kutchi-tribes-rabari-ravechi-festival.jpg`. Copy: 20 Swiss tents (6 Deluxe Air-Cool, 14 Non-AC), open 1 Dec 2026 – 31 Jan 2027.
* **Visit White Rann Camp** → `/white-rann-camp`
* **2026-27 Tariff** → `/white-rann-camp/tariff`: a real route since 29 Sep 2026 that opens the camp page scrolled to its tariff table. The button is a terracotta outline (it was white-on-beige and invisible).
* No "Book a tent" button: White Rann Camp is switched off in the booking engine.

#### 2.7 AmenitiesGrid
Six items with a `CheckCircle2` icon: Swimming Pool, The Banni Restaurant, Travel Desk & Experiences, Free Wi-Fi, Open Garden Lawn (up to 300), Room Service.

#### 2.8 Experiences (places)
Six cards (White Rann & Rann Utsav, Road to Heaven & Dholavira, Banni Villages, Kala Dungar & Birding, Mandvi Beach, Bhuj & Bhujodi), each a link to its destination guide (`/destination/:slug`), the same six as the **Around the Resort** page (since 29 Sep 2026; before, they looked clickable but went nowhere).

#### 2.9 ContactSection (`id="contact"`)
* Shows WhatsApp and email.
* Form fields: name, email, phone, message. Each has an id, a name, an `autoComplete` hint and a label for screen readers (`sr-only`; the placeholders are what shows), since 5 Oct 2026.
* **Still a mock:** `setTimeout(1500)`, then a success toast. Nothing is sent. See [`13-FORMS-AND-INTERACTIONS.md`](#docs-13) for wiring it to the engine's `api/enquiry.php` (which now validates phone, email, dates and length).

---

### 3. Imports
`wouter` Link, `lucide-react` icons (several unused: MapPin, Clock, Send, User, PhoneCall, Sun, Cloud, Wind), `sonner` toast, `Navbar`, `Footer`, `bookingUrl`, `statusUrl`.

---

<a id="docs-06"></a>

## Accommodation Pages
_(was `docs/06-ACCOMMODATION-PAGES.md`)_
### 1. Overview
`Stay.tsx` (`/stay`) presents the resort's two cottage types. The owner asked to keep the old **masonry** photo layout. Booking itself happens in the PHP engine, which has its own room pages, prices and photos (see §5).

---

### 2. `RoomTemplate` Component
Props are untyped (`any`): `title`, `exteriorTitle`, `interiorTitle`, `extImgs[]`, `intImgs[]`, `subtitle`, `description`. (`onBookNow` is declared but never used.)

It renders:
* **Photo masonry** (8 of 12 columns): an "Exterior" column and an "Interior" column, each image `h-80`.
* **Text** (4 columns): italic serif subtitle, description, and a **Book Now** button → `bookingUrl()`.
* **Amenity strip:** Air Conditioner, Wi-Fi, Hot Kettle, Television, In-Room Safe, Hair-Dryer (lucide icons).
* **Lightbox:** clicking an image opens it full-screen. Click anywhere or × to close. There is no next/previous.

### 3. `Stay` Page
* Header: "Accommodation" / "The Stay" / "Two categories, twenty cottages…"
* Two `RoomTemplate`s:

| Title | Exterior | Interior | Subtitle |
|---|---|---|---|
| Kutch AC Cottage | bhunga1, kutchi-bathroom1 | kutchi-cottage-interior-2, _dsc9435 | Cottages inspired by Local Styles |
| Deluxe AC Cottage | deluxe-ac-cottage, ab-vision-17 | deluxe interior, interior-02 | Spacious & Elegantly Designed |

Note: the bathroom photo is filed under "Exterior".

### 4. Unused `Accommodation` Component
`Stay.tsx` still defines an `Accommodation` component (a sand band with two portrait photos and an "Explore" button that expands into both `RoomTemplate`s), but nothing renders it. Its copy contains a garbled character: "appliqu├®" should be "appliqué".

### 5. Rooms in the Booking Engine
Room data lives in the database (`backend/booking-engine/seed.sql` for a fresh install). Prices for particular dates are set in Admin → **Special prices**; the normal prices are in the database. Guests choose **Single / Double / Triple for each room**, and with several rooms they can pick a different cottage for each (Select → tick room numbers).

| Room type | Units | Max | Per night (resort: GST included; camp: + GST) |
|---|---|---|---|
| Kutchi AC Cottage | 12 | 3 adults | Single ₹6,500 · Double ₹7,450 · Triple ₹8,950 (breakfast) |
| Deluxe AC Cottage | 8 | 3 adults | Single ₹5,500 · Double ₹6,500 · Triple ₹8,000 (breakfast) |
| Deluxe Air-Cool Swiss Tent (WRC) | 6 | 3 adults | ₹7,499 (peak ₹8,450) + ₹1,800 for a third guest, MAPAI. **Switched off** |
| Non-AC Swiss Tent (WRC) | 14 | 3 adults | ₹6,500 (peak ₹7,499) + ₹1,800 for a third guest, MAPAI. **Switched off** |

Full details are in [`08-PACKAGES-AND-PRICING.md`](04-BUSINESS-CONTENT-AND-PRICES.md#docs-08); how the price is worked out is in [`21-PRICING-LOGIC-DEEP-DIVE.md`](05-BOOKING-ENGINE.md#docs-21). The engine sells cottage **types**; the desk assigns the actual cottage on arrival ([`23-AVAILABILITY-AND-INVENTORY-LOGIC.md`](05-BOOKING-ENGINE.md#docs-23)).

Engine room photos are in `backend/booking-engine/assets/img/ksr` and `…/wrc` (WebP). They are separate from the site's `frontend/public/assets`.

---

<a id="docs-07"></a>

## Destination Routing and Content System
_(was `docs/07-DESTINATION-SYSTEM.md`)_
_Updated 30 Sep 2026._

### 1. Overview
`frontend/src/pages/Destination.tsx` (about 275 lines) renders every local-attraction guide from one template, keyed by the URL slug.

Route: `<Route path="/destination/:slug" component={Destination} />`. The slug comes from `useRoute("/destination/:slug")` and is looked up in `DESTINATION_DATA`. Each guide gets its own browser title ("Mandvi Beach & Palace | Kutch Safari Resort"), set in `App.tsx` (`DESTINATIONS` map).

### 2. `DESTINATION_DATA`

```ts
{ title, subtitle, tag, img, alt, distance, travelTime, whatIsIt, details: string[] }
```

| Slug | Title | Distance (as written in the file) |
|---|---|---|
| `dholavira` | Dholavira | 220 km |
| `road-to-heaven` | Road to Heaven | 200 km |
| `the-great-white-rann` | The Great White Rann | 85 km |
| `mandvi-beach-palace` | Mandvi Beach & Palace | 65 km |
| `artisan-villages` | Artisan villages | 40–80 km |
| `kala-dungar` | Kala Dungar | 97 km |

> The 2026–27 brochure gives different figures (Dholavira 115 km, Mandvi Beach 75 km). The owner has not yet said which are right; see doc 17 §"Brochure".

#### Linked from
* **Around the Resort** (`/around-the-resort`, `AroundTheResort.tsx`, in the top menu since 30 Sep 2026): six cards → these slugs.
* **Home** "Experiences" section: the same six cards (linked since 29 Sep 2026).
* "Banni Villages" and "Bhuj & Bhujodi" both go to `artisan-villages`. "Road to Heaven & Dholavira" goes to `road-to-heaven`, so **nothing links to `dholavira`** directly.

A `slugify()` helper is defined in the file but never used.

### 3. Layout
* **Own header**, not the shared Navbar: `logo-mark.png` plus a text logo, and a **Back to Beyond Bhuj** link → `/around-the-resort` (was `/#explore`, which didn't exist; fixed 29–30 Sep 2026).
* Hero image with title overlay, then distance and travel time, "What is it", a details list, and a **Plan Your Visit** box.
* **Own dark footer** (`#2c2c2c`), not the shared Footer.
* The nested `<Link><a>` markup (invalid HTML, a console error) was fixed on 29 Sep 2026: classes now sit on `Link` itself.
* Unknown slug: "Destination not found" (title "Page not found") with a button → `/around-the-resort`.

### 4. CTA
"Inquire on WhatsApp":
`https://wa.me/919925238599?text=Hello Kutch Safari Resort, I would like to plan a visit to <title> from the resort.` (`rel="noreferrer"`)

Possible improvements: a "Stay with us" button → `bookingUrl()`; switching to the shared Navbar and Footer (would bring the new menu and "Already booked? Check status").

---

<a id="docs-10"></a>

## Content Pages
_(was `docs/10-CONTENT-PAGES.md`)_
_Updated 30 Sep 2026. Paths are under `frontend/src/pages/`._

All of these use the shared Navbar and Footer, the beige background, a centred header band, and `window.scrollTo(0, 0)` on mount (except where noted). Most wrap their content in `prose prose-zinc`. Every page has its own browser title (`usePageTitle()` in `App.tsx`). **There is no Weddings page.** It was removed, along with its route and links.

### `OurJourney.tsx` — `/our-journey`
* Header: "Our Journey" / "The visionary behind it all: Mike Vaghela".
* "Why We Started": aerial photo + **lorem ipsum placeholder**.
* "Awards & Recognition": one line of copy + **"Award Photo Placeholder" grey box**.
* "Timeline": just `1992 ——— 2026` with no events.
* **Needs:** founder story, awards photo, timeline entries from the owner. (The 2026–27 brochure says the resort has hosted guests for 34 years.)

### `Dining.tsx` — `/dining`
* "Dining at The Banni". "A Taste of Kutch": multi-cuisine (Kutchi, Gujarati, Punjabi, Chinese, Continental), recommends the Kutchi thali and the gala dinner (a day's notice).
* Images: `restaurant-kutch-safari-ab-vision-11.jpg` (**16.7 MB**) + **"Food Image Placeholder" box**.
* The booking engine has food photos (`backend/booking-engine/assets/img/ksr/cuisine-plate.webp`, `buffet-service.webp`, `gala-dinner.webp`, `restaurant-table.webp`) that could fill this. The brochure adds: "one of the few restaurants in Kutch that serve non-vegetarian meals", multi-cuisine "overlooking the dam".

### `Experiences.tsx` — `/experiences` (rebuilt 30 Sep 2026 from the resort brochure)
Everything on this page comes from **"KUTCH SAFARI RESORT 2026 2027.pdf"** (the owner's brochure). Header: kicker "Experiences", title **"Experience Kutch, Where Tradition Meets Wonder"**.
1. **Why Visit Kutch?** Brochure text (lightly cleaned): contrasts of the White Rann and untouched beaches, textiles and colourful communities, the 5,000-year-old UNESCO site at Dholavira, palaces; visitors from the UK, France, Italy, Japan, the USA, Australia and all of India; most come for textile and cultural tours. Tagline: "Experience Kutch, where tradition meets wonder. Come explore Kutch." Four photos: Colourful Communities, White Rann of Kutch, Textiles of Kutch, UNESCO Site Dholavira.
2. **Guest Experiences** ("At Kutch Safari Resort"): Morning Yoga for Groups, Lake-View Gala Dinner, Sunrise Breakfast, Candlelight Dinner (photos).
3. **Arrangements on Request** ("Tell us when you book, or ask at the front desk."): Gala Dinner, Folk Music, Camel Cart Welcome (photos).
4. **Other Assistance & Experiences:** Jeep Safari, Walking Trails, Travel Assistance, Laundry Services, Doctor on Call, Tourist Guides, Wi-Fi, Pet Friendly (lucide icons).
5. **Arrange an Experience** → WhatsApp with a pre-filled message.

Photos were extracted from the PDF into `public/assets/images/brochure/` (11 JPGs, 4–23 KB, 200–590 px wide: fine at card size, would blur larger; the owner may send originals). The places around the resort are **not** on this page any more (see below).

### `AroundTheResort.tsx` — `/around-the-resort` (new, 30 Sep 2026)
Menu item **"Around the Resort"**. Kicker "Around the Resort", title "Kutch, from Our Doorstep". Six cards → destination guides: White Rann & Rann Utsav, Road to Heaven & Dholavira, Banni Villages, Kala Dungar & Birding, Mandvi Beach, Bhuj & Bhujodi. See [`07-DESTINATION-SYSTEM.md`](#docs-07). Home's **Explore Kutch** and the guides' **Back to Beyond Bhuj** link here.

### `GalleryPage.tsx` — `/gallery`
* 26 hard-coded `<img>` tags, `loading="lazy"`. Alt text (29 Sep 2026): 7 described from their file names ("Inside a Kutchi AC cottage", "Bhunga cottages at sunrise", "The Banni restaurant", …); the 19 camera-numbered ones say "Kutch Safari Resort, photo N of 26" until someone describes them.
* The grid is inside a `max-w-4xl prose` wrapper, so it's narrow and prose styles apply to images.
* **No lightbox**; the misleading pointer cursor was removed.
* Includes `_DSC9408.JPG` / `_DSC9412.JPG` (10–11 MB each).

### `PlanYourVisit.tsx` — `/plan-your-visit`
* "Finding Us": Khavda road directions (the LORIYA 7 KM milestone).
* "Distances from Bhuj": Ahmedabad 350 km, Rajkot 240 km, Mandvi 60 km, White Rann (Dhordo) 80 km. (Brochure: Ahmedabad 375, Rajkot 250, Mandvi Beach 75, Dholavira 115, Little Rann 280, Sasan Gir 425 km; Bhuj airport 15 km, railway station 14 km, direct flights and daily trains from Mumbai, Ahmedabad and Delhi. Not applied: owner to confirm, doc 17.)
* `#faq`: **"Frequently asked questions will be populated here."** `/plan-your-visit#faq` jumps straight to it (instant scroll, clears the sticky header).
* No map embed, and no air or rail information on the page yet.

### `Packages.tsx` — `/packages`
Two package cards with no prices or CTA. See [`08-PACKAGES-AND-PRICING.md`](04-BUSINESS-CONTENT-AND-PRICES.md#docs-08).

### `RannUtsavPackage.tsx` — `/rann-utsav-package`, `/white-rann-camp`, `/white-rann-camp/tariff`
Own header and footer (`logo-mark.png`), WRC tariffs (`id="tariff"`, the `/tariff` route jumps there), Colors of Kutch tariffs and itinerary, WhatsApp CTA. Header "Back" → `/`. See [`08-PACKAGES-AND-PRICING.md`](04-BUSINESS-CONTENT-AND-PRICES.md#docs-08).

### `NotFound.tsx` — catch-all
Stock template: slate gradient, red alert icon, **blue** "Go Home" button. It doesn't use the site's Navbar, Footer or colours. Title "Page not found | Kutch Safari Resort".

### `BookingRedirect.tsx` — `/booking`, `/book`, `/admin`
Not a content page. It forwards to the booking engine (or its admin panel). See [`09-SHARED-COMPONENTS.md`](#docs-09).

---

<a id="docs-09"></a>

## Shared Components Reference
_(was `docs/09-SHARED-COMPONENTS.md`)_
_Updated 30 Sep 2026. Paths are under `frontend/src/`._

### 1. Layout

#### 1.1 `components/Navbar.tsx`
* **Top info bar** (`hidden md:block`): phone, email, address, Instagram, and **Already booked? Check status** (`statusUrl()`).
* **Sticky header** (`sticky top-0 z-50`, `bg-[#f8f5e2]`), box up to 1,440 px wide: `logo-main.jpg` with `mix-blend-darken`.
* **Desktop menu** from `NAV_LINKS`: Home, Our Journey, Stay, Dining, Experiences, **Around the Resort**, Packages, Gallery, Plan Your Visit, then a terracotta **White Rann Camp →** link. The active link turns terracotta.
  * Shown from **1,320 px** wide (`min-[1320px]:flex`), every item on one line (`whitespace-nowrap`, `tracking-wider`, gap 12 px, 24 px from 1,500 px). Below 1,320 px the ☰ button is used. With 10 items the menu cannot fit on one line at 1,024–1,280 px (measured 30 Sep 2026).
* `[WRC LOGO]` placeholder button → `whiteranncamp.travstack.com` (new tab, `rel="noreferrer"`).
* **Book Now** → `bookingUrl()` (plain `<a>`, loads the PHP engine).
* **Menu button** (below 1,320 px): `Menu`/`X`, `aria-label` "Open menu"/"Close menu", `aria-expanded`; opens a full-screen overlay at `top-[116px]` with every menu item, Book Now and Already booked? Check status. Tapping an item closes it.

#### 1.2 `components/Footer.tsx`
Four columns:
* **Brand:** "KUTCH SAFARI" (a `<p>` styled as display text; not a heading, so each page has one `h1`), description, Instagram icon link (`aria-label` "Kutch Safari Resort on Instagram").
* **Explore:** About the Resort (`/our-journey`), Rooms & Tariff (`/stay`), Dining, **Experiences**, **Around the Resort**, Gallery.
* **Plan:** Colors of Kutch Packages, How to Reach, FAQs (`/plan-your-visit#faq`, jumps to the FAQ), White Rann Camp, Rann Utsav 2026–27 (`/white-rann-camp/tariff`, opens at the tariff).
* **Reservations:** phone, Already booked? Check status, email, address.

Used by: Home, Stay, Experiences, AroundTheResort, OurJourney, Dining, Gallery, PlanYourVisit, Packages, BookingRedirect. **Not** used by Destination or RannUtsavPackage (own header and footer).

---

### 2. Infrastructure

#### 2.1 `components/ErrorBoundary.tsx`
Class component (`getDerivedStateFromError`). The fallback shows "An unexpected error occurred." and a Reload button; the **stack trace only in development** (`import.meta.env.DEV`, since 29 Sep 2026).

#### 2.2 `contexts/ThemeContext.tsx`
`light` or `dark`, with optional `switchable` (persisted in `localStorage`). `App.tsx` uses `defaultTheme="light"` and does not switch.

#### 2.3 `lib/utils.ts`
`cn()` = `clsx` + `tailwind-merge`.

#### 2.4 `lib/booking.ts`
The single place that knows where the booking engine lives.
* `BOOKING_URL`: `import.meta.env.VITE_BOOKING_URL || "/book/"`
* `bookingUrl({ property, checkIn, checkOut, adults, rooms })` builds `…?property=kutch-safari-resort&check_in=…`
* `statusUrl()` → the engine's `manage.php` ("Already booked? Check status"): Navbar top bar and mobile menu, Home hero, Footer.
* `adminUrl()` → the engine's `admin/`, used by the `/admin` redirect.
* `property` is `"kutch-safari-resort"` (default) or `"white-rann-camp"` (switched off in the engine).

Always use it for booking links, with a plain `<a>` and never wouter's `<Link>`.

#### 2.5 `pages/BookingRedirect.tsx`
Handles `/booking`, `/book`, `/book/*`, `/admin` and `/admin/*`. Takes `to` and `label` props (the admin routes pass `adminUrl()`), and does `window.location.replace(…)`. The routes use children render functions (passing props through wouter's `component` gave a TypeScript error). If the target is the current path (the SPA is answering `/book/` because the engine isn't deployed there), it shows phone and WhatsApp buttons instead.

#### 2.6 `App.tsx`: `usePageTitle()`
Sets `document.title` per route (`TITLES` map, `DESTINATIONS` map for guides, "Page not found" for 404s). **Add a title here for every new page**, and a line in `public/sitemap.xml`.

---

### 3. shadcn/ui (`components/ui/`)
Only four remain:
* `button.tsx`, `card.tsx`: used only by `NotFound.tsx`.
* `tooltip.tsx`: `TooltipProvider` wraps the app; no tooltips are rendered.
* `sonner.tsx`: `<Toaster />` in `App.tsx`; `toast.success` is used by the Home mock form.

Pages mostly use raw Tailwind classes, not these components.

---

<a id="docs-12"></a>

## SEO and Meta Configuration
_(was `docs/12-SEO-AND-META.md`)_
_Updated 30 Sep 2026._

### `frontend/index.html`
```html
<html lang="en">
<link rel="icon" href="/favicon.ico" sizes="any" />            <!-- 9 KB, since 29 Sep 2026 -->
<link rel="apple-touch-icon" href="/apple-touch-icon.png" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />  <!-- pinch zoom allowed -->
<title>Kutch Safari Resort | Bhunga Cottages by the Lake, Bhuj</title>
<meta name="description" content="Kutch Safari Resort — traditional Kutchi bhunga cottages on a hilltop above Rudramata Dam lake, near Bhuj. …" />
```
The old 4.5 MB `logo-mark.png` favicon (wrong MIME type) and the unused 12.8 MB `KSR_VIDEO.mp4` preload were removed on 29 Sep 2026.

#### Open Graph / Twitter
* `og:type` website, `og:url` `https://kutchsafaribhuj.in/`
* `og:title` / `og:description`: same as the title and description
* `og:image`: `https://kutchsafaribhuj.in/assets/LAKEVIEW%20KUTCH%20SAFARI1.jpeg` (**6.6 MB, spaces in the filename**; many scrapers reject images over 5–8 MB). Still to fix.
* `twitter:card` summary_large_image, plus title and description. There is no `twitter:image`.

#### Fonts
Google Fonts preconnect, then Cormorant Garamond (500/600/700 + italics) and Jost (400/500/600 + italic).

### Per-page titles
`usePageTitle()` in `frontend/src/App.tsx` sets `document.title` for every route (`TITLES` map) and each destination (`DESTINATIONS` map); unknown paths get "Page not found | Kutch Safari Resort". Examples:
* `/experiences` → "Experiences | Kutch Safari Resort"
* `/around-the-resort` → "Around the Resort: Places to Explore in Kutch | Kutch Safari Resort"

**When adding a page:** add its title to `TITLES` and a `<url>` to the sitemap. The meta description is shared by every page (it would need pre-rendering to vary).

### `frontend/public/robots.txt`
```
User-agent: *
Allow: /
Disallow: /book/
Disallow: /admin
Sitemap: https://kutchsafaribhuj.in/sitemap.xml
```
The booking engine also sends `<meta name="robots" content="noindex">`. The admin panel, check-status page and PDFs are for guests and staff only.

### `frontend/public/sitemap.xml`
16 URLs: `/`, `/stay`, `/our-journey`, `/dining`, `/experiences`, `/around-the-resort`, `/gallery`, `/plan-your-visit`, `/packages`, `/white-rann-camp`, and the six `/destination/*` guides.

### Canonical address and unknown addresses (5 Oct 2026)
`usePageTitle()` in `App.tsx` also sets `<link rel="canonical">` on every real page: `https://kutchsafaribhuj.in` + the path, with `/rann-utsav-package` and `/white-rann-camp/tariff` pointing at `/white-rann-camp`. Any other address answers with the site (the host serves `index.html` for every path), so it gets `<meta name="robots" content="noindex">`. Search engines would otherwise list it or report a "soft 404". The Express server answers a missing *file* (e.g. `/assets/x.jpg`) with a real 404.

### Remaining gaps
1. Every page shares the meta description from `index.html` (per-page text needs the owner; `BUGS.md` O5).
2. No JSON-LD. A `Resort`/`LodgingBusiness` schema with address, phone, geo and `priceRange` would help.
3. `og:image` too big, with spaces in its name.
4. Gallery: 19 of 26 images still have generic "photo N of 26" alt text (they need someone to describe them).
5. The site is a client-rendered SPA: crawlers that don't run JavaScript see only `index.html`.

---

<a id="docs-13"></a>

## Forms and Interactions
_(was `docs/13-FORMS-AND-INTERACTIONS.md`)_
_Updated 30 Sep 2026._

### 1. Booking — PHP engine
Direct booking is handled by `backend/booking-engine/`, not the React site.

**Guest flow** (`/book/?property=…`, `assets/engine.js` → `api/*.php`; full detail in [`25-GUEST-BOOKING-FLOW-INTERNALS.md`](05-BOOKING-ENGINE.md#docs-25)):
1. Dates (pre-filled), number of rooms, **Single / Double / Triple for each room** → `api/availability.php`. Sold-out dates suggest the next free ones.
2. Choose cottages. One room: pick a card. Several rooms: **Select** on a cottage opens its room numbers to tick; a room ticked in one cottage disappears from the others. One plan (room with breakfast) → `api/quote.php`.
3. Extras: airport transfer (Sedan / Ertiga / Innova, one card with a counter per car), gala dinner (minimum 10), candlelight dinner, birding jeep. The extra bed is chosen by picking **Triple**.
4. Guest details (optional in sample mode), **approximate arrival time on a scroll wheel** (nothing is sent unless the guest turns it), notes, **pay 50% or in full** → `api/book.php`.
5. Payment:
   * Razorpay → `api/payment-create.php` → Checkout → `api/payment-verify.php`, with `api/webhook-razorpay.php` as a backup confirmation.
   * UPI QR (drawn with qrious) → the booking waits until staff click "Money received" in admin.
   * Test mode: **I've paid (test)** → `api/payment-test.php` (no money; turn off before launch).
   * "Pay at the property" is switched off.
6. Confirmation with Receipt (PDF), Check status, Call. The page remembers the last booking on the device. Confirmation email (`lib/mail.php`), BCC to the office, once mail is set up.

**Check status:** `/book/manage.php` → `api/booking-lookup.php` (code + mobile or email, or a `?ref=&token=` link; sent as a POST since 5 Oct 2026, so the phone or email never sits in an address or a log). Shows the stay, due now / due before arrival (or "To pay at the desk" once the guest has arrived) or refund due, a cancelled booking's charge and refund, "Updated by the resort", and Receipt, Terms, Call. **Guests cannot cancel online** (`api/booking-cancel.php` always refuses); they call or WhatsApp, and the admin cancels.

**Enquiry endpoint:** `api/enquiry.php` stores enquiries in the engine's `enquiries` table, which staff see in Admin → Enquiries.

**Input checks (29 Sep 2026, after the chaos test):** every API refuses bad input with a plain message instead of storing it:
* lengths from `LIMITS` in `lib/db.php` (name 120, email 160, city 80, guest note 2,000, enquiry message 3,000, staff note 2,000, payment note 300, cancel reason 250, interest 150), checked with `too_long()`;
* phone 7–15 digits (`valid_phone()`), arrival time "h:mm AM/PM" (`valid_arrival_time()`), real calendar dates (`valid_date()`), no stays more than `rules.max_days_ahead` (730) days ahead;
* extras must exist, quantity a whole number 1–`rules.max_extra_quantity` (50);
* a JSON body that can't be read → "We could not read that request…";
* `api/payment-create.php` needs the booking's `manage_token` and a pending booking.
`engine.js` checks phone and email before sending and sets `maxlength`; if the server still refuses guest details (`field: 'guest'`), the guest stays on the details step with what they typed.

**Admin** (`/book/admin/`, see [`24-ADMIN-PANEL-GUIDE.md`](06-ADMIN-AND-SECURITY.md#docs-24)): bookings list (staying now → coming up → cancelled → finished, with a UPI "waiting to be checked" box and a "Cancel a booking" box), booking detail, **Change this booking** (priced as a difference, doc 22), Availability (tape chart and guest side panel), Special prices, enquiries, CSV export. Logins are created with `php bin/setup.php --admin USERNAME NAME PASSWORD`. Login attempts are rate-limited. **Sign-in is SHA-256 hashed in the browser** (29 Sep 2026): `login.php` sends only `user_sha256` and `pass_sha256` (inputs have no `name`, so the plain text is never posted; plain posts are refused). The server matches `sha256(lowercase username)` and runs `password_verify(sha256(password), stored_hash)`; the stored hash is bcrypt of the SHA-256, written by `setup.php --admin`. CSRF token on every form, sessions end after 10 minutes idle and are marked per browser tab. Every "are you sure?" is an on-page box, not a browser pop-up.

**Protections:** per-IP rate limits (stored in `audit_log`: availability 120/min, quote 90/min, book 12 per 5 min, lookup 15 per 5 min, payments 30 per 5 min, enquiry 8 per 10 min), CORS allow-list, row locking during booking (MySQL `FOR UPDATE`), Razorpay HMAC checks, an audit log of every money or inventory action, and `fail_closed` for Stayflexi.

### 2. Site links into the engine
Every Book Now / Check Availability button uses `bookingUrl()` with a plain `<a>`: Navbar (desktop + mobile), Home hero, Home room cards, and Stay's `RoomTemplate`.

### 3. Home contact form (still a mock)
`Home.tsx` → `ContactSection`:
```ts
const handleSubmit = (e: any) => {
  e.preventDefault();
  setSubmitting(true);
  setTimeout(() => { setSubmitting(false); setSubmitted(true); toast.success("Message sent successfully!"); … }, 1500);
};
```
Nothing is sent. The inputs are write-only (no `value`), so the "reset" doesn't clear them.

Two backends are available. Pick one:
* **Recommended:** POST to the engine's `api/enquiry.php` (at `BOOKING_URL + "api/enquiry.php"`). Enquiries then appear in the same admin panel as bookings. It takes JSON `{ name*, phone*, email, property, check_in, check_out, guests, interest, message, website }` (* required; `website` is a honeypot, leave it empty). It emails the office and returns `{ ok, id, message }`. Rate limit: 8 per 10 min per IP. The form already collects name, email, phone (required) and message, which map straight across.
* Express `POST /api/contact` → `data/enquiries.json` (checks the fields, stores only name, phone, email and message, no personal details in the log). This doesn't work on Vercel (no persistent disk), and `api/contact.ts` stores nothing, so it answers 503 with the phone number instead of claiming success.

### 4. Other interactions
* WhatsApp: `https://wa.me/919925238599` (Home contact, BookingRedirect fallback), with a pre-filled message on Destination, RannUtsavPackage and the Experiences page's **Arrange an Experience** button (30 Sep 2026).
* `tel:+919925238599`, `mailto:kutchsafaribhuj@yahoo.com` (Navbar top bar, Footer).
* Stay lightbox: click an image to open, click to close.
* Mobile menu toggle.
* Toasts: `sonner` `<Toaster />` in `App.tsx`.

---

<a id="docs-19"></a>

## User Flow and UX
_(was `docs/19-USER-FLOW-AND-UX.md`)_
_Updated 30 Sep 2026._

### Entry points
Direct and search → `/` (every route shares the same meta tags). Instagram → `/`. Old bookmarks to `/booking` → forwarded to the engine.

### Journey 1: Book a cottage (primary)
1. Home → **Check Availability & Book** (hero), a room card's **Book Now**, Navbar **Book Now**, or Stay's **Book Now**.
2. The browser loads `/book/?property=kutch-safari-resort` (PHP engine, full page load; its look differs from the site).
3. Dates, guests and rooms → available rooms with rate plans. Sold-out dates suggest alternatives.
4. Choose Single / Double / Triple for each room, pick cottages (Select → tick room numbers when there are several) → add extras (car, dinners, birding) → guest details and arrival time (scroll wheel) → pay 50% or in full (Razorpay or UPI QR; "I've paid (test)" while testing).
5. Confirmation screen with Receipt (PDF, opens in a new tab), Check status and Call, plus email once mail is set up. The page remembers the booking on that device.
6. "Back to website" in the engine header goes to `properties.website_url`, which currently points at **kutchsafariresort.com**, not this site. See [`16-KNOWN-ISSUES-AND-BUGS.md`](08-STATUS-ISSUES-AND-ROADMAP.md#docs-16) #9.

Friction:
* The room chosen on the site is not carried into the engine (it has no room parameter), so the guest picks it again.
* Until the engine is deployed, step 2 shows "call/WhatsApp us".

### Journey 2: Book a White Rann Camp tent
Navbar "White Rann Camp →" → `/white-rann-camp` → tariffs → **WhatsApp only**. The camp is **switched off** in the booking engine for now, on purpose.

### Journey 3: Enquire
* Home contact form → **fake success** (nothing is sent). This is the biggest leak.
* Phone or email links (top bar, footer) and WhatsApp buttons work.
* Inside the engine, "Call" and "Enquire on WhatsApp" stay visible at every step.

### Journey 4: Explore Kutch
Home "Explore Kutch" (or the menu's **Around the Resort**, or a Home Experiences card) → `/around-the-resort` → card → `/destination/:slug` → WhatsApp "plan a visit". Destination pages still have their own header without the site menu; their **Back to Beyond Bhuj** link returns to `/around-the-resort`.

### Journey 4a: What can I do at the resort?
Menu **Experiences** → `/experiences` (from the owner's brochure: Why Visit Kutch?, guest experiences, arrangements on request, other assistance) → **Arrange an Experience** → WhatsApp with a pre-filled message. (Since 30 Sep 2026 Experiences = things the resort offers; Around the Resort = places to visit.)

### Journey 5: Photos
* `/stay`: click a photo to open the lightbox (no next/previous).
* `/gallery`: 26 photos, no lightbox, several are 5–11 MB.

### Journey 6: Check a booking (or ask to cancel)
**Already booked? Check status** is on the website (top bar, mobile menu, home hero, footer) and in the engine header. The guest enters the booking code + mobile or email (or opens their `?ref=&token=` link), and sees the stay, rooms, what is due now / before arrival or refund due, and "Updated by the resort" after a change, with Receipt, Terms and Call. **Guests can't cancel online.** They call or WhatsApp, and the admin cancels it (Bookings → Cancel a booking).

### Staff flow
`/book/admin` (or the website's `/admin`) → sign in (username and password are SHA-256 hashed in the browser before sending; asked in every new tab and after 10 minutes idle) → Bookings (staying now → coming up → cancelled → finished; confirm UPI with "Money received"; cancel by code) → a booking (receipt, collect or give back, cancel) → **Change this booking** (see what's added, taken off and changed, then what to collect by 50%/full) → Availability (by guest or cottage; click a guest for check-in/out, car, rooms, money) → Special prices → Enquiries → Export. Full guide: [`24-ADMIN-PANEL-GUIDE.md`](06-ADMIN-AND-SECURITY.md#docs-24).

### Mobile
Hamburger menu. The hero video is 10 MB and the unused preload adds 12.8 MB, which is heavy on mobile data. Pinch zoom is disabled (`maximum-scale=1`). The engine is responsive and has its own layout.

### Scroll
Each page calls `window.scrollTo(0, 0)` on mount, so route changes start at the top.
