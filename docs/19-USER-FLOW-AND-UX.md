# User Flow and UX

## Entry points
Direct and search → `/` (every route shares the same meta tags). Instagram → `/`. Old bookmarks to `/booking` → forwarded to the engine.

## Journey 1: Book a cottage (primary)
1. Home → **Check Availability & Book** (hero), a room card's **Book Now**, Navbar **Book Now**, or Stay's **Book Now**.
2. The browser loads `/book/?property=kutch-safari-resort` (PHP engine, full page load; its look differs from the site).
3. Dates, guests and rooms → available rooms with rate plans. Sold-out dates suggest alternatives.
4. Choose a room and plan → add extras → guest details → payment (full / 50% / at property; Razorpay or UPI QR).
5. Confirmation screen + email. The booking reference is used later in `/book/manage.php`.
6. "Back to website" in the engine header goes to `properties.website_url`, which currently points at **kutchsafariresort.com**, not this site. See `16-KNOWN-ISSUES-AND-BUGS.md` #9.

Friction:
* The room chosen on the site is not carried into the engine (it has no room parameter), so the guest picks it again.
* Until the engine is deployed, step 2 shows "call/WhatsApp us".

## Journey 2: Book a White Rann Camp tent
Navbar "White Rann Camp →" → `/white-rann-camp` → tariffs → **WhatsApp only**. The engine supports `property=white-rann-camp`, but no button on the site links there yet.

## Journey 3: Enquire
* Home contact form → **fake success** (nothing is sent). This is the biggest leak.
* Phone or email links (top bar, footer) and WhatsApp buttons work.
* Inside the engine, "Call" and "Enquire on WhatsApp" stay visible at every step.

## Journey 4: Explore Kutch
Home "Explore Kutch" → `/experiences` → card → `/destination/:slug` → WhatsApp "plan a visit". Destination pages have no site Navbar, and their back link goes to a missing `/#explore` anchor. The Home Experiences cards don't link anywhere.

## Journey 5: Photos
* `/stay`: click a photo to open the lightbox (no next/previous).
* `/gallery`: 26 photos, no lightbox, several are 5–11 MB.

## Journey 6: Manage or cancel a booking
Guest opens `/book/manage.php` (linked as "My booking" in the engine header), enters reference + phone, sees the booking, and can cancel with the refund from the ladder. **Nothing on the main site links to it.** A footer link would help.

## Staff flow
`/book/admin/` → bookings (confirm UPI payments with "Money received"), availability calendar (close rooms or hold back for OTAs), rates (date ranges), enquiries, CSV export.

## Mobile
Hamburger menu. The hero video is 10 MB and the unused preload adds 12.8 MB, which is heavy on mobile data. Pinch zoom is disabled (`maximum-scale=1`). The engine is responsive and has its own layout.

## Scroll
Each page calls `window.scrollTo(0, 0)` on mount, so route changes start at the top.
