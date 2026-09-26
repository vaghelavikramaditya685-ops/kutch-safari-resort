# User Flow and UX

## Entry points
Direct and search → `/` (every route shares the same meta tags). Instagram → `/`. Old bookmarks to `/booking` → forwarded to the engine.

## Journey 1: Book a cottage (primary)
1. Home → **Check Availability & Book** (hero), a room card's **Book Now**, Navbar **Book Now**, or Stay's **Book Now**.
2. The browser loads `/book/?property=kutch-safari-resort` (PHP engine, full page load; its look differs from the site).
3. Dates, guests and rooms → available rooms with rate plans. Sold-out dates suggest alternatives.
4. Choose Single / Double / Triple for each room, pick cottages (Select → tick room numbers when there are several) → add extras (car, dinners, birding) → guest details and arrival time (scroll wheel) → pay 50% or in full (Razorpay or UPI QR; "I've paid (test)" while testing).
5. Confirmation screen with Receipt (PDF, opens in a new tab), Check status and Call, plus email once mail is set up. The page remembers the booking on that device.
6. "Back to website" in the engine header goes to `properties.website_url`, which currently points at **kutchsafariresort.com**, not this site. See `16-KNOWN-ISSUES-AND-BUGS.md` #9.

Friction:
* The room chosen on the site is not carried into the engine (it has no room parameter), so the guest picks it again.
* Until the engine is deployed, step 2 shows "call/WhatsApp us".

## Journey 2: Book a White Rann Camp tent
Navbar "White Rann Camp →" → `/white-rann-camp` → tariffs → **WhatsApp only**. The camp is **switched off** in the booking engine for now, on purpose.

## Journey 3: Enquire
* Home contact form → **fake success** (nothing is sent). This is the biggest leak.
* Phone or email links (top bar, footer) and WhatsApp buttons work.
* Inside the engine, "Call" and "Enquire on WhatsApp" stay visible at every step.

## Journey 4: Explore Kutch
Home "Explore Kutch" → `/experiences` → card → `/destination/:slug` → WhatsApp "plan a visit". Destination pages have no site Navbar, and their back link goes to a missing `/#explore` anchor. The Home Experiences cards don't link anywhere.

## Journey 5: Photos
* `/stay`: click a photo to open the lightbox (no next/previous).
* `/gallery`: 26 photos, no lightbox, several are 5–11 MB.

## Journey 6: Check a booking (or ask to cancel)
**Already booked? Check status** is on the website (top bar, mobile menu, home hero, footer) and in the engine header. The guest enters the booking code + mobile or email (or opens their `?ref=&token=` link), and sees the stay, rooms, what is due now / before arrival or refund due, and "Updated by the resort" after a change, with Receipt, Terms and Call. **Guests can't cancel online.** They call or WhatsApp, and the admin cancels it (Bookings → Cancel a booking).

## Staff flow
`/book/admin` (or the website's `/admin`) → sign in (asked in every new tab and after 10 minutes idle) → Bookings (staying now → coming up → cancelled → finished; confirm UPI with "Money received"; cancel by code) → a booking (receipt, collect or give back, cancel) → **Change this booking** (see what's added, taken off and changed, then what to collect by 50%/full) → Availability (by guest or cottage; click a guest for check-in/out, car, rooms, money) → Special prices → Enquiries → Export. Full guide: `24-ADMIN-PANEL-GUIDE.md`.

## Mobile
Hamburger menu. The hero video is 10 MB and the unused preload adds 12.8 MB, which is heavy on mobile data. Pinch zoom is disabled (`maximum-scale=1`). The engine is responsive and has its own layout.

## Scroll
Each page calls `window.scrollTo(0, 0)` on mount, so route changes start at the top.
