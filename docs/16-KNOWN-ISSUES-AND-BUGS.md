# Known Issues and Bugs

_Re-checked against the code on 25 Sep 2026. Resolved items are at the bottom._

## Critical
### 1. Home contact form is a mock
`Home.tsx` `ContactSection` uses `setTimeout` and a success toast. Nothing is sent, so leads are lost. **Fix:** POST to the engine's `api/enquiry.php` (see `13-FORMS-AND-INTERACTIONS.md`).

### 2. Booking engine not deployed
The PHP engine needs a PHP + MySQL host. Until then, every Book Now button on Vercel lands on the "call/WhatsApp us" fallback. **Fix:** see `15-DEPLOYMENT-AND-INFRASTRUCTURE.md`.

### 3. Rates, tax and policies need owner sign-off
* Resort rates in `booking-engine/seed.sql` came from an earlier season's tariff. Deluxe (₹5,536 CP) is cheaper than Kutchi (₹6,250 CP).
* GST slabs (5% / 18%) need the accountant's confirmation.
* The cancellation ladder (free 30+ days, 75% at 21–29, 100% under 21) and pay-at-property rules need confirming.

### 4. Asset weight (~269 MB)
4.5 MB favicon, 12.8 MB unused video preload, 10–17 MB photos, 109 MB of unused files. See `11-IMAGE-ASSET-INVENTORY.md`.

## Major
### 5. Dead links
* `/white-rann-camp/tariff`: Footer and Home. No route, so 404.
* `/#explore` (Destination), `/#rann-utsav` (RannUtsavPackage): no such sections.

### 6. Invisible button on Home
Sister-property "2026-27 Tariff" button: `text-white border-white/30` on the beige background.

### 7. Placeholder content
Our Journey (lorem ipsum, award photo box, empty timeline), Dining (food photo box), Plan Your Visit FAQ stub, Navbar `[WRC LOGO]`.

### 8. Destination and RannUtsavPackage use old headers and footers
They have their own header (4.5 MB `logo-mark.png`), their own footer, no site navigation, and `<Link><a>` nested anchors (invalid HTML).

### 9. Booking engine's "Back to website" points at another domain
`properties.website_url` = kutchsafariresort.com / whiterann.com. Update it if kutchsafaribhuj.in is the live site.

### 10. Stayflexi not connected
The engine's channel-manager bridge is off, and its API paths are guesses. Until it's connected, split inventory between the engine and the OTAs (engine README, "Safe — separated").

## Minor
11. Home Experiences cards look clickable but don't link. Nothing links to `/destination/dholavira`.
12. Gallery: no lightbox, all 26 images have `alt="Gallery"`, and it's in a narrow `prose` wrapper.
13. `NotFound.tsx` uses stock slate and blue styling.
14. Stay: garbled "appliqu├®" in the unused `Accommodation` component. The bathroom photo is under "Exterior".
15. `ErrorBoundary` shows stack traces to visitors.
16. The heading colour rule in `index.css` overrides parent text colours (see `04-DESIGN-SYSTEM.md`).
17. Sitemap lists only 4 of about 16 public URLs.
18. The viewport has `maximum-scale=1`, which blocks pinch zoom.
19. The booking engine looks different (Marcellus/Montserrat, its own palette) from the site.
20. Root clutter: about 35 one-off `*.py` edit scripts and `old_rooms.tsx`.
21. Much of the current work is uncommitted (git has 3 commits).

## Resolved
* `/experiences` 404: the route and page now exist.
* Weddings page placeholders: the page was removed.
* WRC internal-link bug on Home: WRC links are now internal to `/white-rann-camp` by design; the Navbar button goes to travstack.
* **Old React booking engine** (`client/src/booking`, `shared/booking`, `server/booking`): **removed 25 Sep 2026**, replaced by `booking-engine/` (PHP). Its JSON-file store couldn't run on Vercel.
* Stay "Book Now" pointed at `/#contact`: it now goes to the booking engine.
