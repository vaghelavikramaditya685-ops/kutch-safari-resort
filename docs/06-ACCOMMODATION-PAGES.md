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
The engine's room data lives in the database (`booking-engine/seed.sql`, editable in Admin → Rates / Availability):

| Room type | Units | Occupancy | Rate plans (per night, 2 guests) |
|---|---|---|---|
| Kutchi AC Cottage | 12 | 2 base, up to 3 adults + 1 child (extra adult ₹1,500, child ₹750) | CP ₹6,250 · MAP ₹7,550 · AP ₹8,550 |
| Deluxe AC Cottage | 8 | 2 base, up to 3 adults + 1 child (extra adult ₹1,500, child ₹750) | CP ₹5,536 · MAP ₹6,836 · AP ₹7,836 |
| Deluxe Air-Cool Swiss Tent (WRC) | 6 | 2 base, up to 3 adults + 1 child (extra ₹1,800) | MAPAI ₹7,499 (peak ₹8,450) |
| Non-AC Swiss Tent (WRC) | 14 | 2 base, up to 3 adults + 1 child (extra ₹1,800) | MAPAI ₹6,500 (peak ₹7,499) |

> **Check with the owner:** in the engine the Deluxe cottage is cheaper than the Kutchi one, and the site describes the Kutchi cottages as "the most spacious rooms". The engine's README says the resort rates came from a tariff page for an earlier season.

Engine room photos are in `booking-engine/assets/img/ksr` and `…/wrc` (WebP). They are separate from the site's `client/public/assets`.
