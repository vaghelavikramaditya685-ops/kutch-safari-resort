/* The tour packages, from the owner's brochures (5 Oct 2026):
   "Rann Utsav 2 Night 3 Days Package", "Rann utsav 3 Night 4 Days Package pvt"
   and "Showcase Kutch 5 N 6 Days Abad to Abad". Prices are per person in INR,
   as printed on the brochures. Shown on /packages and /packages/:slug. */

export type TourPackage = {
  slug: string;
  kind: string;                 // "Rann of Kutch Tour"
  nights: number;
  days: number;
  from: string;                 // where the tour starts and ends
  to: string;
  title: string;
  route: string[];
  stays: string;                // "Bhuj 1 N, Rann of Kutch 1 N"
  stayLabel: string;            // the brochure's heading for the stays
  prices: { label: string; perPerson: number }[];
  itinerary: string[];
  photos: { src: string; alt: string }[];
};

export const PACKAGE_INCLUDES = ["Hotels", "Meals", "Transport", "Sightseeing"] as const;
export const PACKAGE_EXCLUDES = ["Entry tickets", "Tips", "Personal expenses"] as const;

const photos = (slug: string, alts: string[]) =>
  alts.map((alt, i) => ({ src: `/assets/images/packages/${slug}-${i + 1}.jpg`, alt }));

export const PACKAGES: TourPackage[] = [
  {
    slug: "enchanting-rann-of-kutch",
    kind: "Rann of Kutch Tour",
    nights: 2, days: 3, from: "Bhuj", to: "Bhuj",
    title: "Enchanting Rann of Kutch - Rann Utsav",
    route: ["Bhuj", "Dholavira", "Rann of Kutch", "Bhuj"],
    stayLabel: "Resort & Camp at Kutch",
    stays: "Bhuj 1 N, Rann of Kutch 1 N",
    prices: [
      { label: "2 Pax", perPerson: 14500 },
      { label: "4 Pax", perPerson: 12500 },
      { label: "6 Pax", perPerson: 11500 },
      { label: "10 Pax", perPerson: 11000 },
      { label: "Extra Person", perPerson: 6000 },
    ],
    itinerary: [
      "Bhuj - Banni Villages - Rann of Kutch",
      "Rann of Kutch - Dholavira - Bhuj",
      "Local Bhuj",
    ],
    photos: photos("enchanting-rann-of-kutch", [
      "The Road to Heaven across the Rann",
      "Kutchi mirror-work embroidery",
      "Prag Mahal, Bhuj",
      "A camel cart on the White Rann at sunset",
    ]),
  },
  {
    slug: "colors-of-kutch",
    kind: "Rann of Kutch Tour",
    nights: 3, days: 4, from: "Bhuj", to: "Bhuj",
    title: "Colors of Kutch - Rann Utsav",
    route: ["Bhuj", "Mandvi", "Dholavira", "Rann of Kutch"],
    stayLabel: "Resort & Camp at Kutch",
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
    photos: photos("colors-of-kutch", [
      "Mandvi beach",
      "The Road to Heaven across the Rann",
      "Kutchi mirror-work embroidery",
      "The White Rann of Kutch at sunset",
    ]),
  },
  {
    slug: "showcasing-kutch",
    kind: "Wild Life & Culture Tour",
    nights: 5, days: 6, from: "Ahmedabad", to: "Ahmedabad",
    title: "Showcasing Kutch",
    route: ["Ahmedabad", "Little Rann of Kutch", "Bajana", "Bhujodi", "Bhuj", "Banni Villages", "Dholavira", "Rann of Kutch", "Ahmedabad"],
    stayLabel: "Resort & Camp",
    stays: "Ahmedabad 1 N, Bajana 1 N, Bhuj 2 N, Rann of Kutch 1 N",
    prices: [
      { label: "2 Pax", perPerson: 30000 },
      { label: "4 Pax", perPerson: 24500 },
      { label: "6 Pax", perPerson: 23000 },
      { label: "10 Pax", perPerson: 22000 },
      { label: "Extra Person", perPerson: 13500 },
    ],
    itinerary: [
      "Arrival at Ahmedabad - Local Sightseeing",
      "Ahmedabad - Modhera - Patan - Bajana",
      "Bajana - Bhujodi - Local Bhuj",
      "Bhuj - Banni Villages Exploration",
      "Bhuj - Road to Heaven - Dholavira - Bhuj",
      "Bhuj to Ahmedabad",
    ],
    photos: photos("showcasing-kutch", [
      "Ahmedabad heritage architecture",
      "A wild ass in the Little Rann of Kutch",
      "Kutchi handicrafts",
      "The White Rann of Kutch",
    ]),
  },
];

export const packageBySlug = (slug: string) => PACKAGES.find(p => p.slug === slug);

/** ₹14,500 */
export const rupees = (n: number) => "₹" + n.toLocaleString("en-IN");

/** "2 Nights · 3 Days" */
export const duration = (p: TourPackage) => `${p.nights} Nights · ${p.days} Days`;
