-- ===========================================================================
--  Starting data: both properties, their rooms, rate plans, add-ons and the
--  Colors of Kutch packages, using the tariffs published on the websites.
--
--  CONFIRM THE RUPEE AMOUNTS WITH YOUR OWN TARIFF SHEET BEFORE GOING LIVE.
--  The resort rates came from the tariff page, which was labelled for an
--  earlier season; the camp rates are from the 2026–27 leaflet.
-- ===========================================================================

DELETE FROM package_prices;
DELETE FROM packages;
DELETE FROM addons;
DELETE FROM rates;
DELETE FROM inventory;
DELETE FROM rate_plans;
DELETE FROM room_types;
DELETE FROM properties;

-- ---------------------------------------------------------------------------
-- Properties
-- ---------------------------------------------------------------------------
INSERT INTO properties
 (id, code, name, tagline, phone, phone_alt, email, address, website_url, accent,
  check_in_time, check_out_time, season_start, season_end, active)
VALUES
 (1, 'kutch-safari-resort', 'Kutch Safari Resort',
  'Lake-view cottages on the road to the White Rann',
  '+91 99252 38599', NULL, 'kutchsafaribhuj@yahoo.com',
  'Near Rudramata Dam, Bhuj–Khavda Road, Bhuj, Kutch, Gujarat 370001',
  'https://www.kutchsafariresort.com', '#B85C2E', '12:00', '10:00', NULL, NULL, 1),

 (2, 'white-rann-camp', 'White Rann Camp',
  'Twenty Swiss tents, three minutes from the White Rann',
  '+91 99252 38599', '+91 70165 84647', 'whiteranncamp@gmail.com',
  'Near Rann Utsav, Dhordo, Kutch, Gujarat',
  'https://www.whiterann.com', '#C2703A', '12:00', '10:00',
  '2026-12-01', '2027-01-31', 0);   -- White Rann Camp: switched off in the engine for now (active = 0); set to 1 to sell it again

-- ---------------------------------------------------------------------------
-- Kutch Safari Resort — 20 cottages
-- ---------------------------------------------------------------------------
INSERT INTO room_types
 (id, property_id, code, name, description, total_rooms, base_occupancy, max_adults, max_children,
  extra_adult_price, extra_child_price, bed_type, size_label, images, amenities, sort_order, active)
VALUES
 (1, 1, 'kutchi-ac-cottage', 'Kutchi AC Cottage',
  'Our most spacious cottages, finished in the traditional Kutchi manner with mirror-work detailing and a deep private balcony over the lake.',
  12, 2, 3, 1, 1500, 750,
  'One double bed, or twin beds on request', 'Lake-facing balcony',
  '["assets/img/ksr/kutchi-cottage-interior.webp","assets/img/ksr/cottage-lounge.webp","assets/img/ksr/balcony-lake-view.webp"]',
  '["Air conditioned","Lake-facing balcony","Free Wi-Fi","Flat-screen TV","Room service","Hot & cold water","Daily housekeeping","Tea & coffee maker"]',
  1, 1),

 (2, 1, 'deluxe-ac-cottage', 'Deluxe AC Cottage',
  'Comfortable, uncluttered rooms with the same view and the same quiet — a good choice for couples and for families leaving early for Dholavira.',
  8, 2, 3, 1, 1500, 750,
  'One double bed, or twin beds on request', 'Lake view',
  '["assets/img/ksr/cottage-lounge.webp","assets/img/ksr/cottage-bathroom.webp","assets/img/ksr/deluxe-cottage-exterior.webp"]',
  '["Air conditioned","Lake view","Free Wi-Fi","Flat-screen TV","Room service","Hot & cold water","Daily housekeeping"]',
  2, 1);

-- Published tariff, 1 Apr 2026 – 31 Mar 2027 (not Diwali or Christmas / New Year).
-- One plan: room with breakfast (CPAI). GST is INCLUDED in these prices — see
-- prices_include_tax in config.php. base_price is double occupancy; single_price
-- is single occupancy; triple = double + the room type's extra_adult_price (₹1,500).
INSERT INTO rate_plans (id, room_type_id, code, name, meal_note, base_price, single_price, refundable, sort_order, active) VALUES
 (1, 1, 'CP',  'Room with breakfast',            'Breakfast included',                              7450, 6500, 1, 1, 1),
 (4, 2, 'CP',  'Room with breakfast',            'Breakfast included',                              6500, 5500, 1, 1, 1);

-- ---------------------------------------------------------------------------
-- White Rann Camp — 20 Swiss tents, MAPAI (dinner + breakfast + hi-tea)
-- ---------------------------------------------------------------------------
INSERT INTO room_types
 (id, property_id, code, name, description, total_rooms, base_occupancy, max_adults, max_children,
  extra_adult_price, extra_child_price, bed_type, size_label, images, amenities, sort_order, active)
VALUES
 (3, 2, 'deluxe-air-cool-tent', 'Deluxe Air-Cool Swiss Tent',
  'Six tents built for comfort, air-cooled for the warm middle of the day, with twin beds, carpeting and an attached western washroom.',
  6, 2, 3, 1, 1800, 1800,
  'Twin beds', 'Air-cooled',
  '["assets/img/wrc/deluxe-tent-interior.webp","assets/img/wrc/tent-interior-lamp.webp","assets/img/wrc/tent-washroom.webp"]',
  '["Air cooled","Twin beds","Attached western washroom","Hot & cold water","Carpeted","Campfire","Folk music evening"]',
  1, 1),

 (4, 2, 'non-ac-swiss-tent', 'Non-AC Swiss Tent',
  'Fourteen tents — the classic Rann stay. December and January nights at Dhordo are cold enough that most guests want another quilt rather than a cooler.',
  14, 2, 3, 1, 1800, 1800,
  'Twin beds', 'Classic Swiss tent',
  '["assets/img/wrc/nonac-tent-interior.webp","assets/img/wrc/tent-twin-beds.webp","assets/img/wrc/tent-bathroom-basin.webp"]',
  '["Twin beds","Attached western washroom","Hot & cold water","Extra quilts","Carpeted","Campfire","Folk music evening"]',
  2, 1);

INSERT INTO rate_plans (id, room_type_id, code, name, meal_note, base_price, refundable, sort_order, active) VALUES
 (7, 3, 'MAPAI', 'Dinner, breakfast and hi-tea', 'Dinner, breakfast, hi-tea and two bottles of mineral water', 7499, 1, 1, 1),
 (8, 4, 'MAPAI', 'Dinner, breakfast and hi-tea', 'Dinner, breakfast, hi-tea and two bottles of mineral water', 6500, 1, 1, 1);

-- ---------------------------------------------------------------------------
-- Add-ons sold at checkout
-- ---------------------------------------------------------------------------
INSERT INTO addons (property_id, code, name, description, price, price_type, tax_rate, sort_order, active) VALUES
 (1, 'transfer-sedan', 'Airport transfer, one way — Sedan',
  'Private car between Bhuj airport and the resort, one way.', 1200, 'per_booking', 5, 1, 1),
 (1, 'transfer-ertiga', 'Airport transfer, one way — Ertiga',
  'Private car between Bhuj airport and the resort, one way.', 1500, 'per_booking', 5, 2, 1),
 (1, 'transfer-innova', 'Airport transfer, one way — Innova',
  'Private car between Bhuj airport and the resort, one way.', 2100, 'per_booking', 5, 3, 1),
 (1, 'gala-dinner', 'Gala dinner with music programme (groups)',
  'Dinner on the lawn with a live Kutchi music programme. For groups of 10 or more; needs a day''s notice.', 1500, 'per_person', 5, 4, 1),
 (1, 'candlelight-dinner', 'Candlelight dinner overlooking the lake',
  'A private candlelit table overlooking the lake, with a set menu of your choosing.', 3000, 'per_person', 5, 5, 1),
 (1, 'birding-jeep', 'Birding jeep, half day',
  'A jeep and a local guide for the wetlands at dawn.', 3000, 'per_booking', 5, 6, 1),

 (2, 'rann-permit-jeep', 'White Rann permit and jeep',
  'Your Rann visit permit and a shared jeep to the salt flat for sunset.', 1500, 'per_person', 5, 1, 1),
 (2, 'camel-cart', 'Camel cart ride on the Rann',
  'The last stretch onto the white salt by camel cart, as it has always been done.', 800, 'per_person', 5, 2, 1),
 (2, 'private-folk-evening', 'Private folk music evening',
  'Kutchi musicians for your group alone, around your own fire.', 6000, 'per_booking', 5, 3, 1),
 (2, 'bhuj-transfer', 'Transfer from Bhuj',
  'Private car for the 80 km from Bhuj to the camp, one way.', 3000, 'per_booking', 5, 4, 1);

-- Gala dinner is sold to groups only.
UPDATE addons SET min_quantity = 10 WHERE code = 'gala-dinner';

-- ---------------------------------------------------------------------------
-- Colors of Kutch packages
-- ---------------------------------------------------------------------------
INSERT INTO packages (id, property_id, code, name, summary, nights, itinerary, inclusions, exclusions, image, tax_rate, active) VALUES
 (1, 1, 'colors-2n3d', 'Colors of Kutch — 2 Nights / 3 Days',
  'Banni villages, the White Rann at sunset, Kala Dungar and Dholavira, then Bhuj and Bhujodi.',
  2,
  '[{"day":1,"title":"Banni Villages, the White Rann and Rann Utsav","detail":"North through the Banni grasslands and the craft hamlets, then the White Rann for sunset. Night at White Rann Camp, Dhordo."},{"day":2,"title":"Kala Dungar and Dholavira","detail":"The Black Hill for the view over the Great Rann, then east along the Road to Heaven to Dholavira, with the Fossil Park on the way."},{"day":3,"title":"Bhuj Local and Bhujodi","detail":"Aina Mahal, Prag Mahal, the Kutch Museum and the Vande Mataram Memorial, then Bhujodi to watch the weavers."}]',
  '["Twin sharing accommodation","Private vehicle for three days","Dinner and breakfast throughout","Sightseeing as per the itinerary","White Rann permit, Aina Mahal, Prag Mahal, Kutch Museum and Vande Mataram Memorial entry"]',
  '["Lunches and anything à la carte","Taxes on the package rate","Camera fees, tips and personal expenses","Rann Utsav tent-city entry where it applies separately","Travel to and from Bhuj"]',
  'assets/img/ksr/white-rann-dusk.webp', 5, 1),

 (2, 1, 'colors-3n4d', 'Colors of Kutch — 3 Nights / 4 Days',
  'Everything in the three-day journey, plus Mandvi and a final night in Bhuj.',
  3,
  '[{"day":1,"title":"Banni Villages, the White Rann and Rann Utsav","detail":"North through the Banni grasslands and the craft hamlets, then the White Rann for sunset. Night at White Rann Camp, Dhordo."},{"day":2,"title":"Kala Dungar and Dholavira","detail":"The Black Hill, then the Road to Heaven to Dholavira and the Fossil Park. Second night at the camp."},{"day":3,"title":"Bhuj Local and Bhujodi","detail":"The palaces and museums of Bhuj, then the weavers at Bhujodi. Third night at Kutch Safari Resort."},{"day":4,"title":"Mandvi Palace and Beach","detail":"The Vijay Vilas Palace, the four-hundred-year-old shipyard and the beach at Mandvi."}]',
  '["Twin sharing accommodation","Private vehicle for four days","Dinner and breakfast throughout","Sightseeing as per the itinerary","White Rann permit, Aina Mahal, Prag Mahal, Kutch Museum and Vande Mataram Memorial entry"]',
  '["Lunches and anything à la carte","Taxes on the package rate","Camera fees, tips and personal expenses","Rann Utsav tent-city entry where it applies separately","Travel to and from Bhuj"]',
  'assets/img/ksr/mandvi-beach.webp', 5, 1);

-- Per-person price, cheaper as the group grows.
INSERT INTO package_prices (package_id, min_pax, price_per_person, extra_person_price) VALUES
 (1, 2, 15000, 6000),
 (1, 4, 13500, 6000),
 (1, 10, 11500, 6000),
 (2, 2, 21500, 8000),
 (2, 4, 18500, 8000),
 (2, 10, 17000, 8000);

-- ---------------------------------------------------------------------------
-- Peak dates for the camp, from the 2026–27 leaflet.
-- Deluxe Air-Cool ₹8,450 and Non-AC Swiss ₹7,499 on these nights.
-- Everything else falls back to the base price, so only peaks are stored.
-- ---------------------------------------------------------------------------
-- Christmas and New Year: 19 Dec 2026 – 4 Jan 2027
-- Uttarayan: 13 – 15 Jan 2027
-- Full moon: 21 – 23 Jan 2027
-- (Generated by bin/seed-rates.php so the date list stays easy to edit.)

INSERT INTO settings (name, value, note) VALUES
 ('engine_name', 'Kutch Safari Booking', 'Shown in the browser tab'),
 ('terms_url', '', 'Link to your terms and conditions page'),
 ('peak_dates_note', 'Christmas/New Year 19 Dec 2026 – 4 Jan 2027, Uttarayan 13–15 Jan 2027, Full Moon 21–23 Jan 2027',
  'Shown on the camp tariff');
