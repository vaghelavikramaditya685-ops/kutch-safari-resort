# Home Page (`Home.tsx`)

## 1. Overview
About 370 lines. Uses the shared `Navbar` and `Footer`, calls `window.scrollTo(0, 0)` on mount, and has these sections in order:

Hero video → TrustStrip → Welcome → StatsBand → The Stay → Sister Property → AmenitiesGrid → Experiences → ContactSection

---

## 2. Sections

### 2.1 Hero
* Background video: `/assets/images/new/kutch-safari-resort-website-hero.mp4` (10.2 MB), autoplay/muted/loop, `bg-black/40` overlay.
* Kicker "Bhuj · Rann of Kutch", heading "Where the Lake Meets the Desert".
* CTAs:
  * **Check Availability & Book** → `bookingUrl()` (PHP booking engine, full page load)
  * **Explore Kutch** → `/experiences`
  * Under them, a text link **Already booked? Check status** → `statusUrl()` (the engine's `manage.php`)

> `index.html` preloads a *different* video (`KSR_VIDEO.mp4`, 12.8 MB) that Home doesn't use. That is wasted bandwidth.

### 2.2 TrustStrip
"4.6/5 on Google Reviews", "4.5/5 on TripAdvisor", "MakeMyTrip Assured". These are hard-coded and need confirming against the live listings.

### 2.3 Welcome
Two staggered photos (Deluxe exterior, Kutchi interior) plus three paragraphs about the location, 20 lake-facing cottages, The Banni, and three decades of hosting. Link: **Our Story** → `/our-journey`.

### 2.4 StatsBand
20 Lake View Cottages · 35+ Years of Hosting · 15 Km from Bhuj.

### 2.5 The Stay
Two cards:
* Kutchi AC Cottage (badge "12 Cottages")
* Deluxe AC Cottage (badge "8 Cottages")

Each card's **Book Now** → `bookingUrl()`. The engine has no room parameter, so the guest picks the room inside the engine. "All Rooms" → `/stay`.

### 2.6 Sister Property — White Rann Camp
Image `kutchi-tribes-rabari-ravechi-festival.jpg`. Copy: 20 Swiss tents (6 Deluxe Air-Cool, 14 Non-AC), open 1 Dec 2026 – 31 Jan 2027.
* **Visit White Rann Camp** → `/white-rann-camp`
* **2026-27 Tariff** → `/white-rann-camp/tariff` (**404**). This button is also **invisible**: white text and white border on the beige background.
* A "Book a tent" button is **not** wanted yet: White Rann Camp is switched off in the booking engine (26 Sep 2026).

### 2.7 AmenitiesGrid
Six items with a `CheckCircle2` icon: Swimming Pool, The Banni Restaurant, Travel Desk & Experiences, Free Wi-Fi, Open Garden Lawn (up to 300), Room Service.

### 2.8 Experiences
Six cards (White Rann & Rann Utsav, Road to Heaven & Dholavira, Banni Villages, Kala Dungar & Birding, Mandvi Beach, Bhuj & Bhujodi). They are styled as clickable ("Discover →") but **don't link anywhere**. The `/experiences` page has the linked version.

### 2.9 ContactSection (`id="contact"`)
* Shows WhatsApp and email.
* Form fields: name, email, phone, message. `dates` is in state but has no input.
* **Mock submit:** `setTimeout(1500)`, then a success toast. Nothing is sent. See `13-FORMS-AND-INTERACTIONS.md`.

---

## 3. Imports
`wouter` Link, `lucide-react` icons (several are unused: MapPin, Clock, Send, User, PhoneCall, Sun, Cloud, Wind), `sonner` toast, `Navbar`, `Footer`, `bookingUrl`, `statusUrl`.
