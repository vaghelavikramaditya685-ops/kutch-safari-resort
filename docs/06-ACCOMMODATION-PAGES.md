# Accommodation Pages

## 1. Overview
`Stay.tsx` (`/stay`) presents the resort's two cottage types. The owner asked to keep the old **masonry** photo layout. Booking itself happens in the PHP engine, which has its own room pages, prices and photos (see §5).

---

## 2. `RoomTemplate` Component
Props are untyped (`any`): `title`, `exteriorTitle`, `interiorTitle`, `extImgs[]`, `intImgs[]`, `subtitle`, `description`. (`onBookNow` is declared but never used.)

It renders:
* **Photo masonry** (8 of 12 columns): an "Exterior" column and an "Interior" column, each image `h-80`.
* **Text** (4 columns): italic serif subtitle, description, and a **Book Now** button → `bookingUrl()`.
* **Amenity strip:** Air Conditioner, Wi-Fi, Hot Kettle, Television, In-Room Safe, Hair-Dryer (lucide icons).
* **Lightbox:** clicking an image opens it full-screen. Click anywhere or × to close. There is no next/previous.

## 3. `Stay` Page
* Header: "Accommodation" / "The Stay" / "Two categories, twenty cottages…"
* Two `RoomTemplate`s:

| Title | Exterior | Interior | Subtitle |
|---|---|---|---|
| Kutch AC Cottage | bhunga1, kutchi-bathroom1 | kutchi-cottage-interior-2, _dsc9435 | Cottages inspired by Local Styles |
| Deluxe AC Cottage | deluxe-ac-cottage, ab-vision-17 | deluxe interior, interior-02 | Spacious & Elegantly Designed |

Note: the bathroom photo is filed under "Exterior".

## 4. Unused `Accommodation` Component
`Stay.tsx` still defines an `Accommodation` component (a sand band with two portrait photos and an "Explore" button that expands into both `RoomTemplate`s), but nothing renders it. Its copy contains a garbled character: "appliqu├®" should be "appliqué".

## 5. Rooms in the Booking Engine
Room data lives in the database (`backend/booking-engine/seed.sql` for a fresh install). Prices for particular dates are set in Admin → **Special prices**; the normal prices are in the database. Guests choose **Single / Double / Triple for each room**, and with several rooms they can pick a different cottage for each (Select → tick room numbers).

| Room type | Units | Max | Per night (resort: GST included; camp: + GST) |
|---|---|---|---|
| Kutchi AC Cottage | 12 | 3 adults | Single ₹6,500 · Double ₹7,450 · Triple ₹8,950 (breakfast) |
| Deluxe AC Cottage | 8 | 3 adults | Single ₹5,500 · Double ₹6,500 · Triple ₹8,000 (breakfast) |
| Deluxe Air-Cool Swiss Tent (WRC) | 6 | 3 adults | ₹7,499 (peak ₹8,450) + ₹1,800 for a third guest, MAPAI. **Switched off** |
| Non-AC Swiss Tent (WRC) | 14 | 3 adults | ₹6,500 (peak ₹7,499) + ₹1,800 for a third guest, MAPAI. **Switched off** |

Full details are in `08-PACKAGES-AND-PRICING.md`; how the price is worked out is in `21-PRICING-LOGIC-DEEP-DIVE.md`. The engine sells cottage **types**; the desk assigns the actual cottage on arrival (`23-AVAILABILITY-AND-INVENTORY-LOGIC.md`).

Engine room photos are in `backend/booking-engine/assets/img/ksr` and `…/wrc` (WebP). They are separate from the site's `frontend/public/assets`.
