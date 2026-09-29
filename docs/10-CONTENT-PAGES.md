# Content Pages

All of these use the shared Navbar and Footer, the beige background, a centred header band, and `window.scrollTo(0, 0)` on mount. Most wrap their content in `prose prose-zinc`. **There is no Weddings page.** It was removed, along with its route and links.

## `OurJourney.tsx` — `/our-journey`
* Header: "Our Journey" / "The visionary behind it all: Mike Vaghela".
* "Why We Started": aerial photo + **lorem ipsum placeholder**.
* "Awards & Recognition": one line of copy + **"Award Photo Placeholder" grey box**.
* "Timeline": just `1992 ——— 2026` with no events.
* **Needs:** founder story, awards photo, timeline entries from the owner.

## `Dining.tsx` — `/dining`
* "Dining at The Banni". "A Taste of Kutch": multi-cuisine (Kutchi, Gujarati, Punjabi, Chinese, Continental), recommends the Kutchi thali and the gala dinner (a day's notice).
* Images: `restaurant-kutch-safari-ab-vision-11.jpg` (**16.7 MB**) + **"Food Image Placeholder" box**.
* The booking engine has food photos (`backend/booking-engine/assets/img/ksr/cuisine-plate.webp`, `buffet-service.webp`, `gala-dinner.webp`, `restaurant-table.webp`) that could fill this.

## `Experiences.tsx` — `/experiences`
Six cards linking to `/destination/:slug`. See `07-DESTINATION-SYSTEM.md`.

## `GalleryPage.tsx` — `/gallery`
* 26 hard-coded `<img>` tags, `loading="lazy"`, all with `alt="Gallery"`.
* The grid is inside a `max-w-4xl prose` wrapper, so it's narrow and prose styles apply to images.
* **No lightbox.** `cursor-pointer` suggests one, but clicking does nothing.
* Includes `_DSC9408.JPG` / `_DSC9412.JPG` (10–11 MB each).

## `PlanYourVisit.tsx` — `/plan-your-visit`
* "Finding Us": Khavda road directions (the LORIYA 7 KM milestone).
* "Distances from Bhuj": Ahmedabad 350 km, Rajkot 240 km, Mandvi 60 km, White Rann (Dhordo) 80 km.
* `#faq`: **"Frequently asked questions will be populated here."**
* No map embed, and no air or rail information.

## `Packages.tsx` — `/packages`
Two package cards with no prices or CTA. See `08-PACKAGES-AND-PRICING.md`.

## `RannUtsavPackage.tsx` — `/rann-utsav-package`, `/white-rann-camp`
Own header and footer (`logo-mark.png`), WRC tariffs, Colors of Kutch tariffs and itinerary, WhatsApp CTA. See `08-PACKAGES-AND-PRICING.md`.

## `NotFound.tsx` — catch-all
Stock template: slate gradient, red alert icon, **blue** "Go Home" button. It doesn't use the site's Navbar, Footer or colours.

## `BookingRedirect.tsx` — `/booking`, `/book`, `/admin`
Not a content page. It forwards to the booking engine (or its admin panel). See `09-SHARED-COMPONENTS.md`.

_Checked 26 Sep 2026: the content pages are unchanged by the booking-engine work. Every page with the shared Navbar and Footer now shows "Already booked? Check status"._
