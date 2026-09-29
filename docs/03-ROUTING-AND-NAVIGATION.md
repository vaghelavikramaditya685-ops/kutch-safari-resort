# Routing and Navigation

## 1. Application Routes (`App.tsx`)

wouter `<Switch>`: the first match wins.

| Path | Component | Notes |
|---|---|---|
| `/` | `Home.tsx` | Landing page |
| `/stay` | `Stay.tsx` | Cottages |
| `/experiences` | `Experiences.tsx` | 6 cards linking to destination guides |
| `/our-journey` | `OurJourney.tsx` | Placeholder content |
| `/dining` | `Dining.tsx` | The Banni |
| `/gallery` | `GalleryPage.tsx` | 26 photos, no lightbox |
| `/plan-your-visit` | `PlanYourVisit.tsx` | Directions, distances, FAQ stub (`#faq`) |
| `/packages` | `Packages.tsx` | Colors of Kutch overview |
| `/destination/:slug` | `Destination.tsx` | 6 slugs, see `07-DESTINATION-SYSTEM.md` |
| `/rann-utsav-package` | `RannUtsavPackage.tsx` | White Rann Camp + package tariffs |
| `/white-rann-camp` | `RannUtsavPackage.tsx` | Alias |
| `/booking`, `/book`, `/book/*` | `BookingRedirect.tsx` | Full-page redirect to the PHP booking engine |
| `/admin`, `/admin/*` | `BookingRedirect.tsx` | Short address for staff: redirects to the engine's admin panel (`adminUrl()`). Typing `/book/admin` (no slash) also works: the engine redirects to `/book/admin/` and on to `login.php`, so the address fills itself in |
| `/404`, anything else | `NotFound.tsx` | Stock template styling |

### 1.1 Booking engine URLs
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
| `/book/admin/` | Staff panel (login required): `index.php` bookings, `booking.php?id=` one booking, `edit.php?id=` change it, `calendar.php` Availability, `rates.php` Special prices, `enquiries.php`, `export.php`. See `24-ADMIN-PANEL-GUIDE.md` |

`BOOKING_URL` defaults to `/book/`. You can override it at build time with `VITE_BOOKING_URL`. `statusUrl()` → `manage.php` and `adminUrl()` → `admin/` are built from it too.

### 1.2 Broken links
| Link | Where | Result |
|---|---|---|
| `/white-rann-camp/tariff` | Footer, Home sister-property section | 404 |
| `/#explore` | Destination header and "not found" state | No such section on Home |
| `/#rann-utsav` | RannUtsavPackage header | No such section on Home |

---

## 2. Navbar (`components/Navbar.tsx`)
Used by every page except `Destination` and `RannUtsavPackage`, which have their own old headers.

* **Top bar** (hidden below `md`): phone, email, address, Instagram, and **Already booked? Check status** (`statusUrl()`).
* **Sticky header:** logo `logo-main.jpg` with `mix-blend-darken`.
* **`NAV_LINKS`:** Home, Our Journey, Stay, Dining, Experiences, Packages, Gallery, Plan Your Visit, followed by a terracotta **White Rann Camp →** link (`/white-rann-camp`).
* **`[WRC LOGO]` button:** placeholder text. It opens `https://whiteranncamp.travstack.com` in a new tab.
* **Book Now** (desktop and mobile menu): a plain `<a href={bookingUrl()}>`, which loads the engine.
* **Mobile menu:** full-screen overlay toggled by `open` state. It includes Book Now and Already booked? Check status. Book Now never wraps (`whitespace-nowrap`).

## 3. Footer (`components/Footer.tsx`)
Four columns:
* **Brand:** text logo, short description, Instagram.
* **Explore:** About the Resort (`/our-journey`), Rooms & Tariff (`/stay`), Dining, Experiences, Gallery.
* **Plan:** Colors of Kutch Packages (`/packages`), How to Reach (`/plan-your-visit`), FAQs (`/plan-your-visit#faq`), White Rann Camp (`/white-rann-camp`), Rann Utsav 2026–27 (`/white-rann-camp/tariff`, **broken**), Already booked? Check status (`statusUrl()`).
* **Reservations:** phone, email, address.

## 4. External Links
* WhatsApp: `https://wa.me/919925238599`, with a pre-filled message on Destination and RannUtsavPackage.
* Instagram: `https://www.instagram.com/kutchsafariresort/`
* WRC portal: `https://whiteranncamp.travstack.com` (Navbar placeholder button only)
* Booking engine: `bookingUrl()`, `statusUrl()`, `adminUrl()`. Always a plain `<a>`, never a wouter `<Link>` (the engine is a separate app and needs a full page load).
