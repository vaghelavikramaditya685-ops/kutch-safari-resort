import { Link } from "wouter";
import { useEffect, useState } from "react";
import { useRoute } from "wouter";
import {
  Phone, Mail, MapPin, Menu, X, Facebook, Instagram, Star, Sun, Cloud, Wind,
  Waves, Heart, UtensilsCrossed, Clock, Car, ArrowLeft, Navigation,
  Snowflake, Wifi, Coffee, Tv, Lock, Archive, PhoneCall,
} from "lucide-react";

const DESTINATION_DATA: Record<string, {
  title: string;
  subtitle: string;
  tag: string;
  img: string;
  alt: string;
  distance: string;
  travelTime: string;
  whatIsIt: string;
  details: string[];
}> = {
  dholavira: {
    title: "Dholavira",
    subtitle: "UNESCO World Heritage Site",
    tag: "Harappan Civilization",
    img: "/assets/images/new/pro-dholavira.jpg",
    alt: "Ancient ruins of Dholavira",
    distance: "220 km from Kutch Safari Resort",
    travelTime: "4\u20134.5 hours by road",
    whatIsIt: "Dholavira is one of the five largest Harappan sites in the Indian subcontinent, dating back nearly 4,500 years. Declared a UNESCO World Heritage Site in 2021, this ancient metropolis showcases remarkably advanced urban planning, monumental stone architecture, and one of the world\u2019s earliest water conservation systems. The site is located on Khadir Bet, an island in the Great Rann of Kutch.",
    details: [
      "Massive stone citadel with separate upper and lower towns, each fortified with thick walls",
      "Sophisticated step-well reservoirs and an ingenious rainwater harvesting system that sustained the city for centuries",
      "The famous \u2018Dholavira Signboard\u2019 \u2014 ten large Indus script characters, one of the earliest known public signs in history",
      "A beautifully preserved ceremonial ground and stadium-like structure that could seat 10,000 people",
      "On-site museum with artefacts including terracotta pottery, seals, beads, copper tools, and jewellery",
      "The drive itself is spectacular \u2014 crossing the shimmering white salt expanse of the Great Rann on an elevated road",
    ],
  },
  "road-to-heaven": {
    title: "Road to Heaven",
    subtitle: "The Iconic Salt Highway",
    tag: "Scenic Drive",
    img: "/assets/images/new/kutch-destination-road_2.jpg",
    alt: "Road to Heaven highway across the Rann",
    distance: "200 km from Kutch Safari Resort",
    travelTime: "3.5\u20134 hours by road",
    whatIsIt: "The Road to Heaven is a surreal, arrow-straight 30-km stretch of elevated highway that slices through the shimmering turquoise waters and boundless white salt desert of the Great Rann of Kutch towards Khadir Bet. Widely regarded as one of India\u2019s most photogenic roads, this drive feels like floating between sky and salt.",
    details: [
      "30 km of perfectly straight, elevated road cutting through the glistening salt flats",
      "The water on both sides creates a mesmerizing mirror effect, especially during monsoon months",
      "Ideal for photography at sunrise and sunset when the salt flats glow gold and pink",
      "The road connects the mainland to Khadir Bet island, home to the ancient Dholavira ruins",
      "Along the way, you may spot flamingos, pelicans, and other migratory birds in the wetlands",
      "Best visited between October and March when the salt desert is dry and the skies are clear",
    ],
  },
  "the-great-white-rann": {
    title: "The Great White Rann",
    subtitle: "Dhordo Salt Desert",
    tag: "Natural Wonder",
    img: "/assets/images/white-rann-of-kutch-cc-by-sa.jpg",
    alt: "Vast white salt desert of the Great Rann",
    distance: "85 km from Kutch Safari Resort",
    travelTime: "1.5\u20132 hours by road",
    whatIsIt: "The Great Rann of Kutch is one of the largest salt deserts in the world, spanning over 7,500 square kilometres of crystalline white salt flats. During the annual Rann Utsav festival (November\u2013February), Dhordo village transforms into a vibrant tent city celebrating Kutchi culture, music, dance, and handicrafts under the full moon.",
    details: [
      "Endless, flat white salt desert that glows silver under the full moon \u2014 a truly surreal sight",
      "Rann Utsav festival (Nov\u2013Feb): luxury tent accommodation, folk performances, camel safaris, and craft bazaars",
      "Stunning sunset and sunrise views across the infinite white expanse",
      "Camel cart rides, ATV adventures, and paramotoring available during the festival season",
      "Local artisan markets with authentic Kutchi embroidery, Bandhani textiles, and lacquer work",
      "Visit the Kalo Dungar (Black Hill) viewpoint nearby for a panoramic overlook of the entire Rann",
    ],
  },
  "mandvi-beach-palace": {
    title: "Mandvi Beach & Palace",
    subtitle: "Coastal Gateway & Royal Heritage",
    tag: "Arabian Sea Coast",
    img: "/assets/images/new/kutch-destination-mandvi-beach.jpg",
    alt: "Mandvi beach on the Arabian Sea coast",
    distance: "65 km from Kutch Safari Resort",
    travelTime: "1\u20131.5 hours by road",
    whatIsIt: "Mandvi is a charming coastal town on the Arabian Sea, famous for its pristine sandy beach, iconic wind-farm turbines, and the grand Vijay Vilas Palace. Once a thriving port of the Kutch kingdom, Mandvi still houses a 400-year-old wooden shipbuilding yard where dhows are hand-crafted using centuries-old techniques.",
    details: [
      "Long, clean sandy beach with gentle waves \u2014 perfect for swimming, sunset walks, and camel rides along the surf",
      "Vijay Vilas Palace: a stunning Rajput-style summer palace with ornate domes, sprawling gardens, and a private beach (used as a Bollywood filming location)",
      "400-year-old shipbuilding yard (Dhow yard) where massive wooden cargo ships are still built by hand, one of the last in India",
      "Iconic row of windmill turbines along the shoreline creating a dramatic coastal landscape",
      "Mandvi Bandar (old harbour) with colourful fishing boats and fresh seafood markets",
      "Explore the 250-year-old Mandvi clock tower and bustling local bazaar for handicrafts and tie-dye textiles",
    ],
  },
  "artisan-villages": {
    title: "Artisan Villages (Bhirandiyara & Nirona)",
    subtitle: "Living Crafts & Traditional Bhungas",
    tag: "Cultural Craft Trail",
    img: "/assets/images/new/kutch-handicrafts-block-demo.jpg",
    alt: "Traditional Kutchi Rogan art",
    distance: "40\u201380 km from Kutch Safari Resort",
    travelTime: "1\u20132 hours by road (village circuit)",
    whatIsIt: "The villages surrounding Bhuj are a living museum of India\u2019s most extraordinary textile and craft traditions. Each village specialises in a unique art form passed down through generations. Nirona is famed for its rare Rogan art and copper bell (Ghanta) making, Bhirandiyara for traditional Bhunga architecture, and Bhujodi for exquisite handloom weaving and embroidery.",
    details: [
      "Rogan Art in Nirona: One of the rarest art forms in the world \u2014 intricate patterns painted on cloth using a heated castor-oil paste and a metal needle, practised by only one family on earth",
      "Copper Bell Making (Ghanta) in Nirona: Hand-forged copper bells in all sizes, each tuned to a distinct note, used by Rabari shepherds across Gujarat",
      "Traditional Bhunga Houses in Bhirandiyara: Circular mud-and-thatch homes decorated with Lippan (mud-mirror) art, built to withstand earthquakes",
      "Bhujodi Weaving Village: Master weavers creating vibrant shawls, blankets, and stoles on handlooms using natural dyes",
      "Ajrakh Block Printing in Ajrakhpur: Ancient resist-printing technique creating geometric patterns in indigo and madder on cotton cloth",
      "Taste authentic Kutchi Dabeli, Mawa sweets, and chai at village tea stalls between craft stops",
    ],
  },
  "kala-dungar": {
    title: "Kala Dungar (Black Hill)",
    subtitle: "Highest Peak in Kutch",
    tag: "Panoramic Summit",
    img: "/assets/images/new/pro-kala_dungar.jpg",
    alt: "Rabari camel caravan near Kala Dungar",
    distance: "97 km from Kutch Safari Resort",
    travelTime: "2\u20132.5 hours by road",
    whatIsIt: "Kala Dungar, or Black Hill, rises 462 metres above sea level and is the highest point in the Kutch district. From the summit, you are treated to a breathtaking 360-degree panoramic view of the vast, shimmering Great Rann of Kutch stretching to the horizon. The hilltop also houses the sacred 400-year-old Dattatreya Temple.",
    details: [
      "Highest point in Kutch at 462 metres \u2014 sweeping 360\u00b0 views of the Great Rann, the Rann of Kutch, and on clear days, the Pakistan border",
      "400-year-old Dattatreya Temple at the summit, dedicated to the Hindu trinity of Brahma, Vishnu, and Mahesh",
      "Legend of the magnetic hill \u2014 a stretch of road near the summit where vehicles appear to roll uphill on their own",
      "The winding mountain road offers dramatic viewpoints and photo opportunities at every turn",
      "Best visited at sunrise or sunset when the salt desert below transforms into shades of gold and pink",
      "Spot wildlife including Indian foxes, desert hares, and numerous migratory bird species in the surrounding scrubland",
    ],
  },
};

function slugify(title: string): string {
  return title
    .toLowerCase()
    .replace(/[()&]/g, "")
    .replace(/\s+/g, "-")
    .replace(/-+/g, "-")
    .replace(/^-|-$/g, "");
}

export default function Destination() {
  const [, params] = useRoute("/destination/:slug");
  const slug = params?.slug || "";
  const dest = DESTINATION_DATA[slug];

  useEffect(() => {
    window.scrollTo(0, 0);
  }, [slug]);

  if (!dest) {
    return (
      <div className="min-h-screen bg-background flex flex-col items-center justify-center gap-6 px-4">
        <h1 className="font-display text-4xl text-foreground">Destination not found</h1>
        <Link href="/experiences" className="bg-[var(--terracotta)] text-white px-8 py-3 uppercase tracking-widest text-sm font-semibold hover:opacity-90 transition-opacity">
            Back to Beyond Bhuj
          </Link>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background">
      {/* Fixed Navbar */}
      <header className="fixed inset-x-0 top-0 z-50 bg-background/95 backdrop-blur-md shadow-[0_1px_0_0_oklch(0.88_0.03_75/0.8)] py-3">
        <div className="w-full px-6 md:px-10 flex items-center justify-between">
          <Link href="/" className="flex items-center gap-3">
              <img src="/assets/images/logo-mark.png" alt="Kutch Safari Resort" className="h-10 w-10 object-contain" />
              <div className="leading-tight">
                <span className="font-display text-sm font-bold tracking-wide text-foreground block">KUTCH SAFARI</span>
                <span className="text-[10px] uppercase tracking-[0.2em] text-foreground/60">Resort &middot; Bhuj</span>
              </div>
            </Link>
          <Link href="/experiences" className="flex items-center gap-2 text-sm font-medium text-foreground/70 hover:text-foreground transition-colors">
              <ArrowLeft className="h-4 w-4" />
              Back to Beyond Bhuj
            </Link>
        </div>
      </header>

      {/* Hero Image */}
      <div className="relative pt-16">
        <img
          src={dest.img}
          alt={dest.alt}
          className="w-full h-[50vh] md:h-[60vh] object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent" />
        <div className="absolute bottom-0 left-0 right-0 p-6 md:p-12">
          <span className="inline-block bg-[var(--terracotta)] text-white text-xs font-medium tracking-wider uppercase px-3 py-1.5 rounded-sm mb-4">
            {dest.tag}
          </span>
          <h1 className="font-display text-4xl md:text-6xl text-white font-bold tracking-wide drop-shadow-lg">
            {dest.title}
          </h1>
          <p className="font-display text-lg md:text-xl text-white/90 italic mt-2 drop-shadow">
            {dest.subtitle}
          </p>
        </div>
      </div>

      {/* Quick Info Strip */}
      <div className="bg-[#f6f0e8] border-y border-[#e4d5c7]">
        <div className="container mx-auto px-4 py-6 flex flex-wrap items-center justify-center gap-8 md:gap-16">
          <div className="flex items-center gap-3">
            <Navigation className="h-6 w-6 text-[var(--terracotta)]" />
            <div>
              <p className="text-xs uppercase tracking-widest text-foreground/50 font-medium">Distance</p>
              <p className="text-base font-semibold text-foreground">{dest.distance}</p>
            </div>
          </div>
          <div className="flex items-center gap-3">
            <Clock className="h-6 w-6 text-[var(--terracotta)]" />
            <div>
              <p className="text-xs uppercase tracking-widest text-foreground/50 font-medium">Travel Time</p>
              <p className="text-base font-semibold text-foreground">{dest.travelTime}</p>
            </div>
          </div>
          <div className="flex items-center gap-3">
            <Car className="h-6 w-6 text-[var(--terracotta)]" />
            <div>
              <p className="text-xs uppercase tracking-widest text-foreground/50 font-medium">Mode</p>
              <p className="text-base font-semibold text-foreground">Private vehicle / taxi</p>
            </div>
          </div>
        </div>
      </div>

      {/* Content */}
      <div className="container mx-auto px-4 py-16 max-w-4xl">
        <h2 className="font-display text-3xl md:text-4xl font-semibold text-foreground mb-6 tracking-wide">
          What is {dest.title}?
        </h2>
        <p className="text-base md:text-lg leading-relaxed text-foreground/80 mb-12 font-light">
          {dest.whatIsIt}
        </p>

        <h2 className="font-display text-2xl md:text-3xl font-semibold text-foreground mb-8 tracking-wide">
          Key Highlights
        </h2>
        <div className="space-y-5">
          {dest.details.map((detail, i) => (
            <div key={i} className="flex gap-4 items-start">
              <span className="flex-shrink-0 w-8 h-8 rounded-full bg-[var(--terracotta)]/10 flex items-center justify-center text-[var(--terracotta)] font-display font-bold text-sm mt-0.5">
                {i + 1}
              </span>
              <p className="text-base leading-relaxed text-foreground/80 font-light">{detail}</p>
            </div>
          ))}
        </div>

        {/* CTA */}
        <div className="mt-16 p-8 bg-[#f6f0e8] border border-[#e4d5c7] rounded-sm text-center">
          <h3 className="font-display text-2xl font-semibold text-foreground mb-3">Plan Your Visit</h3>
          <p className="text-foreground/70 mb-6 font-light">We can arrange a day trip or guided excursion to {dest.title} from the resort.</p>
          <a
            href={"https://wa.me/919925238599?text=Hello%20Kutch%20Safari%20Resort%2C%20I%20would%20like%20to%20plan%20a%20visit%20to%20" + encodeURIComponent(dest.title) + "%20from%20the%20resort."}
            target="_blank"
            rel="noreferrer"
            className="inline-block bg-[var(--terracotta)] text-white px-10 py-3.5 uppercase tracking-widest text-sm font-semibold hover:opacity-90 transition-opacity shadow-md"
          >
            Inquire on WhatsApp
          </a>
        </div>
      </div>

      {/* Footer */}
      <footer className="bg-[#2c2c2c] text-white/70 py-10 mt-12">
        <div className="container mx-auto px-4 text-center text-sm">
          <p>&copy; {new Date().getFullYear()} Kutch Safari Resort. All rights reserved.</p>
          <p className="mt-2 text-white/40">13 km from Bhuj, Rudramata Dam Road, Kutch, Gujarat 370001</p>
        </div>
      </footer>
    </div>
  );
}
