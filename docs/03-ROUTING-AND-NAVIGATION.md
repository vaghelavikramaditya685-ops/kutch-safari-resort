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
| `/404`, anything else | `NotFound.tsx` | Stock template styling |

### 1.1 Booking engine URLs
The engine is not a React route. It is linked with `bookingUrl()` from `client/src/lib/booking.ts`:

```ts
bookingUrl()                                          // /book/?property=kutch-safari-resort
bookingUrl({ property: "white-rann-camp" })
bookingUrl({ checkIn: "2026-12-20", checkOut: "2026-12-22", adults: 2, rooms: 1 })
```

| Engine page | Purpose |
|---|---|
| `/book/?property=…` | Search, room, extras, details, payment |
| `/book/manage.php` | Guest looks up or cancels with reference + phone |
| `/book/admin/` | Staff panel (login required) |

`BOOKING_URL` defaults to `/book/`. You can override it at build time with `VITE_BOOKING_URL`.

### 1.2 Broken links
| Link | Where | Result |
|---|---|---|
| `/white-rann-camp/tariff` | Footer, Home sister-property section | 404 |
| `/#explore` | Destination header and "not found" state | No such section on Home |
| `/#rann-utsav` | RannUtsavPackage header | No such section on Home |

---

## 2. Navbar (`components/Navbar.tsx`)
Used by every page except `Destination` and `RannUtsavPackage`, which have their own old headers.

* **Top bar** (hidden below `md`): phone, email, address, Instagram.
* **Sticky header:** logo `logo-main.jpg` with `mix-blend-darken`.
* **`NAV_LINKS`:** Home, Our Journey, Stay, Dining, Experiences, Packages, Gallery, Plan Your Visit, followed by a terracotta **White Rann Camp →** link (`/white-rann-camp`).
* **`[WRC LOGO]` button:** placeholder text. It opens `https://whiteranncamp.travstack.com` in a new tab.
* **Book Now** (desktop and mobile menu): a plain `<a href={bookingUrl()}>`, which loads the engine.
* **Mobile menu:** full-screen overlay toggled by `open` state.

## 3. Footer (`components/Footer.tsx`)
Four columns:
* **Brand:** text logo, short description, Instagram.
* **Explore:** About the Resort (`/our-journey`), Rooms & Tariff (`/stay`), Dining, Experiences, Gallery.
* **Plan:** Colors of Kutch Packages (`/packages`), How to Reach (`/plan-your-visit`), FAQs (`/plan-your-visit#faq`), White Rann Camp (`/white-rann-camp`), Rann Utsav 2026–27 (`/white-rann-camp/tariff`, **broken**).
* **Reservations:** phone, email, address.

## 4. External Links
* WhatsApp: `https://wa.me/919925238599`, with a pre-filled message on Destination and RannUtsavPackage.
* Instagram: `https://www.instagram.com/kutchsafariresort/`
* WRC portal: `https://whiteranncamp.travstack.com` (Navbar placeholder button only)
* Booking engine: `bookingUrl()`, which is always a plain `<a>` and never a wouter `<Link>`.
