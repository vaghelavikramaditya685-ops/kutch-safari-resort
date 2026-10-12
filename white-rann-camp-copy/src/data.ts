/*
 * Every fact, price and photo credit on the White Rann Camp site lives here.
 * Sources (no invented data):
 *  - the camp's 2026-27 leaflet as shown on the Kutch Safari Resort site
 *    (frontend/src/pages/RannUtsavPackage.tsx: highlights, inclusions, tariff, peak dates);
 *  - the booking engine's starting data (backend/booking-engine/seed.sql: tent types,
 *    counts, descriptions, features, phones, email, address, check-in/out, season);
 *  - the owner's 5 Oct 2026 tour brochures (frontend/src/lib/packages.ts);
 *  - the camp's own site, whiteranncamp.travstack.com (read 8 Oct 2026): 800 m from the
 *    Rann Utsav Tent City, map location, airport transfers and guided tours, 6 + 14 tents;
 *  - the owner's photo PDF (8 Oct 2026), saved in public/photos/camp/.
 * Change a fact here and every page follows.
 */

export const CAMP = {
  name: "White Rann Camp",
  place: "Dhordo · Rann of Kutch",
  tagline: "Twenty Swiss tents, three minutes from the White Rann",
  address: "Near Rann Utsav, Dhordo, Kutch, Gujarat",
  phone: "+91 99252 38599",
  phoneHref: "tel:+919925238599",
  phoneAlt: "+91 70165 84647",
  phoneAltHref: "tel:+917016584647",
  whatsapp: "919925238599",
  email: "whiteranncamp@gmail.com",
  season: "1 December 2026 – 31 January 2027",
  seasonShort: "Open 1 Dec 2026 – 31 Jan 2027",
  checkIn: "12 noon",
  checkOut: "10 am",
  sisterName: "Kutch Safari Resort",
  sisterUrl: "https://kutchsafariresort.in",
  fromTentCity: "800 m from the Rann Utsav Tent City",
  // "Get Direction" on the camp's travstack site.
  mapsUrl: "https://maps.google.com/maps?q=23.8062938,69.517386",
} as const;

export const whatsappLink = (text: string) => `https://wa.me/${CAMP.whatsapp}?text=${encodeURIComponent(text)}`;

/** ₹7,499 */
export const rupees = (n: number) => "₹" + n.toLocaleString("en-IN");

/* ---------------------------------------------------------------- Photos */

export type Photo = { src: string; alt: string; credit?: Credit };
export type Credit = { title: string; author: string; licence: string; licenceUrl: string; page: string };

const BY_SA_4 = { licence: "CC BY-SA 4.0", licenceUrl: "https://creativecommons.org/licenses/by-sa/4.0/" };
const BY_SA_3 = { licence: "CC BY-SA 3.0", licenceUrl: "https://creativecommons.org/licenses/by-sa/3.0/" };

/** Wikimedia Commons thumbnail (standard widths only: 960, 1280, 1920). */
const commons = (hashPath: string, file: string, width: 960 | 1280 | 1920) =>
  `https://upload.wikimedia.org/wikipedia/commons/thumb/${hashPath}/${file}/${width}px-${file}`;

/*
 * White Rann landscapes from Wikimedia Commons, under free licences that allow use
 * on a business website when the author and licence are credited (done on the photo
 * and on the Gallery page). For this local preview they load straight from Wikimedia;
 * before going live they should be saved into public/photos (with the owner's OK).
 */
export const RANN = {
  whiteDesert: {
    src: commons("a/a5", "The_White_Desert_in_Kutch%2C_the_great_rann_of_kutch.jpg", 1920),
    alt: "The white salt desert of the Great Rann of Kutch under a clear blue sky",
    credit: { title: "The White Desert in Kutch", author: "Ranjith Kumar Inbasekaran", ...BY_SA_4,
      page: "https://commons.wikimedia.org/wiki/File:The_White_Desert_in_Kutch,_the_great_rann_of_kutch.jpg" },
  },
  mesmerizing: {
    src: commons("6/64", "Mesmerizing_Rann.jpg", 1920),
    alt: "Salt flats stretching to the horizon under a blue sky with clouds",
    credit: { title: "Mesmerizing Rann", author: "Bhavana.sbn", ...BY_SA_4,
      page: "https://commons.wikimedia.org/wiki/File:Mesmerizing_Rann.jpg" },
  },
  saltRoad: {
    src: commons("0/01", "White_salt_desert_at_Rann_of_Kutch.jpg", 1280),
    alt: "The white salt desert beside a red-earth track in the Rann of Kutch",
    credit: { title: "White salt desert at Rann of Kutch", author: "Prof Ranga Sai", ...BY_SA_4,
      page: "https://commons.wikimedia.org/wiki/File:White_salt_desert_at_Rann_of_Kutch.jpg" },
  },
  sunset: {
    src: commons("9/93", "Sunset-_ranotsav.JPG", 1280),
    alt: "The sun setting over the White Rann",
    credit: { title: "Sunset - Rannotsav", author: "Bhargavinf", ...BY_SA_3,
      page: "https://commons.wikimedia.org/wiki/File:Sunset-_ranotsav.JPG" },
  },
  utsavSunset: {
    src: commons("0/07", "The_Rann_of_Kutch_India.jpg", 1280),
    alt: "Visitors on the white salt flats watching the sunset",
    credit: { title: "The Rann of Kutch India", author: "Sudha Kulkarni", ...BY_SA_4,
      page: "https://commons.wikimedia.org/wiki/File:The_Rann_of_Kutch_India.jpg" },
  },
} satisfies Record<string, Photo>;

const BY_2 = { licence: "CC BY 2.0", licenceUrl: "https://creativecommons.org/licenses/by/2.0/" };

/* Places near the camp, for the "from our doorstep" cards (Wikimedia Commons, credited). */
export const PLACES = {
  highway: {
    src: commons("5/5f", "A_state_highway_through_the_Rann_of_Kutch.jpg", 960),
    alt: "A straight highway across the white Rann of Kutch",
    credit: { title: "A state highway through the Rann of Kutch", author: "Nagarjun Kandukuru", ...BY_2,
      page: "https://commons.wikimedia.org/wiki/File:A_state_highway_through_the_Rann_of_Kutch.jpg" },
  },
  dholavira: {
    src: commons("2/2f", "Dholavira_archaeological_site.jpg", 960),
    alt: "Excavated stone walls of the Harappan city at Dholavira",
    credit: { title: "Dholavira archaeological site", author: "Bhuppigraphy", ...BY_SA_4,
      page: "https://commons.wikimedia.org/wiki/File:Dholavira_archaeological_site.jpg" },
  },
  kalaDungar: {
    src: commons("b/b4", "Rann_of_Kutch_from_Kala_Dungar.jpg", 960),
    alt: "The Rann of Kutch seen from the hills of Kala Dungar",
    credit: { title: "Rann of Kutch from Kala Dungar", author: "Sudha Kulkarni", ...BY_SA_4,
      page: "https://commons.wikimedia.org/wiki/File:Rann_of_Kutch_from_Kala_Dungar.jpg" },
  },
  rogan: {
    src: commons("9/98", "Rogan_painting.jpg", 960),
    alt: "An artisan painting rogan designs on red cloth",
    credit: { title: "Rogan painting", author: "Rick Bradley", ...BY_2,
      page: "https://commons.wikimedia.org/wiki/File:Rogan_painting.jpg" },
  },
} satisfies Record<string, Photo>;

/** Every Wikimedia photo on the site, for the credits list. */
export const COMMONS_PHOTOS = [...Object.values(RANN), ...Object.values(PLACES)];

/** The sister property, from the Kutch Safari Resort site. */
export const SISTER_PHOTO: Photo = {
  src: "/photos/kutch-safari-resort-aerial-lake.jpg",
  alt: "Kutch Safari Resort's cottages above the Rudramata Dam lake, seen from the air",
};

/** Places near the camp. Text from the Kutch Safari Resort site's destination guides. */
export const EXPLORE = [
  { title: "The White Rann", desc: "The salt desert at Dhordo, at its best on a full moon night.", photo: RANN.whiteDesert, url: "https://kutchsafariresort.in/destination/the-great-white-rann" },
  { title: "Rann Utsav", desc: "The festival at Dhordo, November to February: three minutes from the camp.", photo: RANN.utsavSunset, url: "https://kutchsafariresort.in/destination/the-great-white-rann" },
  { title: "Road to Heaven", desc: "Thirty kilometres of perfectly straight road across the glistening salt flats.", photo: PLACES.highway, url: "https://kutchsafariresort.in/destination/road-to-heaven" },
  { title: "Dholavira", desc: "A 4,500-year-old Harappan city, a UNESCO World Heritage Site since 2021.", photo: PLACES.dholavira, url: "https://kutchsafariresort.in/destination/dholavira" },
  { title: "Kala Dungar", desc: "The Black Hill, the highest point in Kutch, with sweeping views of the Great Rann.", photo: PLACES.kalaDungar, url: "https://kutchsafariresort.in/destination/kala-dungar" },
  { title: "Artisan Villages", desc: "Embroidery, leatherwork and bell-making in the hamlets of Kutch.", photo: PLACES.rogan, url: "https://kutchsafariresort.in/destination/artisan-villages" },
];

/** The camp's own photographs: from the owner's photo PDF (8 Oct 2026, in photos/camp/) and from the booking engine. */
export const CAMP_PHOTOS = {
  // From the owner's photo PDF (2026)
  entranceNight: { src: "/photos/camp/camp-entrance-night.jpg", alt: "The lit White Rann Camp sign over the entrance at night" },
  campNight: { src: "/photos/camp/camp-tents-night.jpg", alt: "Swiss tents around the open camp ground at night" },
  tentsNight: { src: "/photos/camp/tents-exterior-night.jpg", alt: "Swiss tents with chairs outside, lit up at night" },
  lightsNight: { src: "/photos/camp/camp-lights-night.jpg", alt: "The camp ground under strings of lights at night" },
  canopyNight: { src: "/photos/camp/camp-canopy-night.jpg", alt: "A lit canopy on the camp ground at night" },
  diningTent: { src: "/photos/camp/dining-tent.jpg", alt: "The dining tent, with red chairs and laid tables" },
  textiles: { src: "/photos/camp/kutchi-textiles.jpg", alt: "Kutchi embroidered textiles hanging in a tent" },
  deluxeSitting: { src: "/photos/camp/deluxe-tent-sitting-cooler.jpg", alt: "A Deluxe Air-Cool tent: sitting area with an air cooler" },
  deluxeBed: { src: "/photos/camp/deluxe-tent-bed-cooler.jpg", alt: "A Deluxe Air-Cool tent: double bed, with the air cooler beside it" },
  creamDouble: { src: "/photos/camp/tent-cream-double.jpg", alt: "Inside a Swiss tent: a double bed on a red carpet, under block-printed walls" },
  creamTwin: { src: "/photos/camp/tent-cream-twin.jpg", alt: "Twin beds in a Swiss tent with block-printed walls" },
  creamSitting: { src: "/photos/camp/tent-cream-sitting.jpg", alt: "A sitting area in a Swiss tent, with a pedestal fan" },
  creamWide: { src: "/photos/camp/tent-cream-wide.jpg", alt: "A Swiss tent with twin beds and a sitting area" },
  yellowTwin: { src: "/photos/camp/tent-yellow-twin.jpg", alt: "Twin beds in a yellow-walled Swiss tent" },
  yellowDouble: { src: "/photos/camp/tent-yellow-double.jpg", alt: "A double bed in a yellow-walled Swiss tent" },
  yellowDoor: { src: "/photos/camp/tent-yellow-door.jpg", alt: "Inside a yellow-walled Swiss tent, with a double bed" },
  yellowBed: { src: "/photos/camp/tent-yellow-bed.jpg", alt: "A double bed in a yellow-walled Swiss tent" },
  washroomBasinToilet: { src: "/photos/camp/tent-washroom-basin-toilet.jpg", alt: "An attached tent washroom with a western toilet and basin" },
  washroomToilet: { src: "/photos/camp/tent-washroom-toilet.jpg", alt: "An attached tent washroom with a western toilet" },
  washroomCorner: { src: "/photos/camp/tent-washroom-corner.jpg", alt: "An attached tent washroom with a basin stand" },
  // From the booking engine (earlier seasons)
  tentsRow: { src: "/photos/camp-tents-row.webp", alt: "A row of white Swiss tents with chairs outside, at White Rann Camp" },
  tentsSky: { src: "/photos/camp-tents-sky.webp", alt: "The line of Swiss tents at White Rann Camp under an evening sky" },
  tentDay: { src: "/photos/swiss-tent-day.webp", alt: "White Swiss tents with a red rug and table outside" },
  nonacInterior: { src: "/photos/nonac-tent-interior.webp", alt: "Twin beds with quilts inside a Non-AC Swiss tent" },
} satisfies Record<string, Photo>;

/* ---------------------------------------------------------------- Tents and tariff */

export type Tent = {
  slug: string;
  name: string;
  count: number;
  summary: string;
  features: string[];
  price: number;       // non-peak, per tent per night, double occupancy, + GST
  peakPrice: number;   // peak dates
  photos: Photo[];
};

export const TENTS: Tent[] = [
  {
    slug: "deluxe-air-cool",
    name: "Deluxe Air-Cool Swiss Tent",
    count: 6,
    summary: "Six tents built for comfort, air-cooled for the warm middle of the day, with twin beds, carpeting and an attached western washroom.",
    features: ["Air cooled", "Twin beds", "Attached western washroom", "Hot & cold water", "Carpeted", "Campfire", "Folk music evening"],
    price: 7499,
    peakPrice: 8450,
    // Only photos that show the air cooler, so a guest sees the right tent.
    photos: [CAMP_PHOTOS.deluxeSitting, CAMP_PHOTOS.deluxeBed, CAMP_PHOTOS.washroomBasinToilet],
  },
  {
    slug: "non-ac",
    name: "Non-AC Swiss Tent",
    count: 14,
    summary: "Fourteen tents: the classic Rann stay. December and January nights at Dhordo are cold enough that most guests want another quilt rather than a cooler.",
    features: ["Twin beds", "Attached western washroom", "Hot & cold water", "Extra quilts", "Carpeted", "Campfire", "Folk music evening"],
    price: 6500,
    peakPrice: 7499,
    // nonacInterior is the photo the booking engine's data labels Non-AC; the others show every tent.
    photos: [CAMP_PHOTOS.nonacInterior, CAMP_PHOTOS.tentsNight, CAMP_PHOTOS.washroomToilet],
  },
];

export const TOTAL_TENTS = TENTS.reduce((n, t) => n + t.count, 0); // 20

export const PEAK_DATES = [
  { label: "Christmas & New Year", dates: "19 Dec 2026 – 4 Jan 2027" },
  { label: "Uttarayan", dates: "13 – 15 Jan 2027" },
  { label: "Full moon", dates: "21 – 23 Jan 2027" },
];

export const EXTRA_PERSON = { label: "Extra person (child 6–12 years)", price: 1800 };

/** What every tent rate includes. */
export const INCLUDED = [
  "Dinner, breakfast and hi-tea",
  "Two bottles of mineral water",
  "Attached bathroom with hot & cold water",
  "Campfire",
  "Cultural music programme at the camp",
  "Travel assistance",
];

export const HIGHLIGHTS = [
  { title: `${TOTAL_TENTS} Swiss tents`, text: "Six Deluxe Air-Cool and fourteen Non-AC, all well furnished." },
  { title: "800 m from Rann Utsav", text: "Three minutes from the Tent City and the entry to the White Rann of Kutch." },
  { title: "Meals included", text: "Dinner, breakfast and hi-tea from our restaurant: Kutchi food and multi-cuisine." },
  { title: "Evenings at camp", text: "A campfire and a cultural music programme." },
];

/* ---------------------------------------------------------------- Tours (owner's brochures, 5 Oct 2026) */

export type Tour = {
  title: string;
  nights: number;
  days: number;
  route: string[];
  stays: string;
  prices: { label: string; perPerson: number }[];
  itinerary: string[];
  kutchSafariUrl: string;
};

export const TOURS: Tour[] = [
  {
    title: "Enchanting Rann of Kutch - Rann Utsav",
    nights: 2, days: 3,
    route: ["Bhuj", "Dholavira", "Rann of Kutch", "Bhuj"],
    stays: "Bhuj 1 N, Rann of Kutch 1 N",
    prices: [
      { label: "2 Pax", perPerson: 14500 },
      { label: "4 Pax", perPerson: 12500 },
      { label: "6 Pax", perPerson: 11500 },
      { label: "10 Pax", perPerson: 11000 },
      { label: "Extra Person", perPerson: 6000 },
    ],
    itinerary: ["Bhuj - Banni Villages - Rann of Kutch", "Rann of Kutch - Dholavira - Bhuj", "Local Bhuj"],
    kutchSafariUrl: "https://kutchsafariresort.in/packages/enchanting-rann-of-kutch",
  },
  {
    title: "Colors of Kutch - Rann Utsav",
    nights: 3, days: 4,
    route: ["Bhuj", "Mandvi", "Dholavira", "Rann of Kutch"],
    stays: "Bhuj 2 N, Camp 1 N",
    prices: [
      { label: "2 Pax", perPerson: 21000 },
      { label: "4 Pax", perPerson: 17500 },
      { label: "6 Pax", perPerson: 16500 },
      { label: "10 Pax", perPerson: 15750 },
      { label: "Extra Person", perPerson: 8000 },
    ],
    itinerary: [
      "Bhuj - Mandvi - Bhuj",
      "Bhuj - Banni Villages - Rann of Kutch",
      "Rann of Kutch - Road to Heaven - Dholavira - Rann of Kutch / Bhuj",
      "Local Bhuj",
    ],
    kutchSafariUrl: "https://kutchsafariresort.in/packages/colors-of-kutch",
  },
];

export const TOUR_INCLUDES = ["Hotels", "Meals", "Transport", "Sightseeing"];
export const TOUR_EXCLUDES = ["Entry tickets", "Tips", "Personal expenses"];

/* ---------------------------------------------------------------- Plan your visit */

export const FAQS: { q: string; a: string }[] = [
  { q: "Where is the camp?",
    a: "At Dhordo, 800 m from the Rann Utsav Tent City: three minutes from Rann Utsav and from the entry to the White Rann of Kutch. The Get directions button on this page opens it in Google Maps." },
  { q: "When is the camp open?",
    a: `${CAMP.season}.` },
  { q: "How far is it from Bhuj?",
    a: "Dhordo and the White Rann are about 80 km from Bhuj. Bhuj has the nearest airport and railway station. Ask us for travel assistance when you book." },
  { q: "What are the check-in and check-out times?",
    a: `Check-in from ${CAMP.checkIn}, check-out by ${CAMP.checkOut}.` },
  { q: "Which meals are included?",
    a: "Every tent rate includes dinner, breakfast and hi-tea, and two bottles of mineral water. The restaurant serves Kutchi food and multi-cuisine." },
  { q: "Is it cold at night?",
    a: "Yes. December and January nights at Dhordo are cold enough that most guests want another quilt rather than a cooler, so the Non-AC tents come with extra quilts." },
  { q: "Which dates cost more?",
    a: "Christmas & New Year (19 Dec 2026 – 4 Jan 2027), Uttarayan (13 – 15 Jan 2027) and the full moon (21 – 23 Jan 2027) are peak dates." },
  { q: "Can you arrange airport transfers and sightseeing?",
    a: "Yes. We offer airport transfers and guided sightseeing tours. Tell us your plans when you enquire." },
  { q: "How do I book?",
    a: "Send us an enquiry with your dates and how many are coming, or WhatsApp or call +91 99252 38599. We confirm availability and the rate." },
];
