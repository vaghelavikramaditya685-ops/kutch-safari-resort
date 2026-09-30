import { useEffect } from "react";
import {
  Car, Footprints, Map, Shirt, Stethoscope, UserRound, Wifi, PawPrint, MessageCircle,
} from "lucide-react";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";

/* Everything on this page comes from the resort brochure (2026–27).
   The places around the resort are on their own page: /around-the-resort. */
const KUTCH_HIGHLIGHTS = [
  { title: "Colourful Communities", img: "/assets/images/brochure/kutch-colourful-communities.jpg" },
  { title: "White Rann of Kutch", img: "/assets/images/brochure/white-rann-of-kutch.jpg" },
  { title: "Textiles of Kutch", img: "/assets/images/brochure/kutch-textiles.jpg" },
  { title: "UNESCO Site, Dholavira", img: "/assets/images/brochure/dholavira-unesco-site.jpg" },
];
const GUEST_EXPERIENCES = [
  { title: "Morning Yoga for Groups", img: "/assets/images/brochure/morning-yoga.jpg" },
  { title: "Lake-View Gala Dinner", img: "/assets/images/brochure/lake-view-gala-dinner.jpg" },
  { title: "Sunrise Breakfast", img: "/assets/images/brochure/sunrise-breakfast.jpg" },
  { title: "Candlelight Dinner", img: "/assets/images/brochure/candlelight-dinner.jpg" },
];
const ON_REQUEST = [
  { title: "Gala Dinner", img: "/assets/images/brochure/gala-dinner-campfire.jpg" },
  { title: "Folk Music", img: "/assets/images/brochure/folk-music-evening.jpg" },
  { title: "Camel Cart Welcome", img: "/assets/images/brochure/camel-cart-welcome.jpg" },
];
const ASSISTANCE = [
  { title: "Jeep Safari", icon: Car },
  { title: "Walking Trails", icon: Footprints },
  { title: "Travel Assistance", icon: Map },
  { title: "Laundry Services", icon: Shirt },
  { title: "Doctor on Call", icon: Stethoscope },
  { title: "Tourist Guides", icon: UserRound },
  { title: "Wi-Fi", icon: Wifi },
  { title: "Pet Friendly", icon: PawPrint },
];

export default function Experiences() {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  return (
    <div className="min-h-screen bg-[#f8f5e2] font-sans text-zinc-900">
      <Navbar />

      <div className="pt-24 pb-16 bg-[#f8f5e2] border-b border-[#e4d5c7]">
        <div className="container mx-auto px-6 text-center max-w-3xl">
          <p className="text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4">Experiences</p>
          <h1 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6">Experience Kutch, Where Tradition Meets Wonder</h1>
          <p className="text-lg text-zinc-600">What we arrange for our guests, at the resort and beyond.</p>
        </div>
      </div>

      {/* Why visit Kutch? */}
      <section className="py-20">
        <div className="container mx-auto px-6 max-w-4xl">
          <h2 className="text-3xl md:text-4xl font-display font-bold text-zinc-900 mb-6 text-center">Why Visit Kutch?</h2>
          <div className="space-y-4 text-zinc-900/80 text-lg leading-relaxed">
            <p>
              Kutch is a land of stunning contrasts and timeless charm. It is the only place in India where you can experience
              both the surreal White Rann desert and untouched beaches, a variety of textile art and colourful communities
              showcasing their culture, a connection to a 5,000-year-old past at the UNESCO site of Dholavira, and beautiful
              palaces, all in one unforgettable journey.
            </p>
            <p>
              Kutch welcomes travellers from across the world, from the UK, France, Italy, Japan, the USA, Australia and many
              other countries, and from every part of India. Most come for special-interest tours, such as textile tours and
              cultural tours.
            </p>
            <p className="font-display text-2xl text-[var(--terracotta)] text-center pt-2">
              Experience Kutch, where tradition meets wonder. Come explore Kutch.
            </p>
          </div>
        </div>
        <div className="container mx-auto px-6 mt-12">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
            {KUTCH_HIGHLIGHTS.map((h) => (
              <figure key={h.title} className="group">
                <div className="h-40 md:h-48 overflow-hidden rounded-sm shadow-sm">
                  <img src={h.img} alt={h.title} loading="lazy" className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                </div>
                <figcaption className="mt-3 text-sm font-semibold tracking-[0.15em] uppercase text-zinc-900">{h.title}</figcaption>
              </figure>
            ))}
          </div>
        </div>
      </section>

      {/* Guest experiences */}
      <section className="py-16 bg-white/50 border-y border-[#e4d5c7]">
        <div className="container mx-auto px-6">
          <p className="text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4 text-center">At Kutch Safari Resort</p>
          <h2 className="text-3xl md:text-4xl font-display font-bold text-zinc-900 mb-10 text-center">Guest Experiences</h2>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {GUEST_EXPERIENCES.map((e) => (
              <figure key={e.title} className="group">
                <div className="h-56 overflow-hidden rounded-sm shadow-sm">
                  <img src={e.img} alt={e.title} loading="lazy" className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                </div>
                <figcaption className="mt-3 text-lg font-bold text-zinc-900">{e.title}</figcaption>
              </figure>
            ))}
          </div>
        </div>
      </section>

      {/* Arrangements on request */}
      <section className="py-20">
        <div className="container mx-auto px-6">
          <h2 className="text-3xl md:text-4xl font-display font-bold text-zinc-900 mb-3 text-center">Arrangements on Request</h2>
          <p className="text-zinc-600 text-center mb-10">Tell us when you book, or ask at the front desk.</p>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-5xl mx-auto">
            {ON_REQUEST.map((e) => (
              <figure key={e.title} className="group">
                <div className="h-48 overflow-hidden rounded-sm shadow-sm">
                  <img src={e.img} alt={e.title} loading="lazy" className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                </div>
                <figcaption className="mt-3 text-lg font-bold text-zinc-900">{e.title}</figcaption>
              </figure>
            ))}
          </div>
        </div>
      </section>

      {/* Other assistance and experiences */}
      <section className="py-16 bg-white/50 border-t border-[#e4d5c7]">
        <div className="container mx-auto px-6 max-w-5xl">
          <h2 className="text-3xl md:text-4xl font-display font-bold text-zinc-900 mb-10 text-center">Other Assistance &amp; Experiences</h2>
          <ul className="grid grid-cols-2 md:grid-cols-4 gap-4">
            {ASSISTANCE.map(({ title, icon: Icon }) => (
              <li key={title} className="flex flex-col items-center text-center gap-3 p-5 bg-white/60 border border-[#e4d5c7] rounded-sm">
                <Icon className="w-7 h-7 text-[var(--terracotta)]" aria-hidden="true" />
                <span className="text-sm font-semibold text-zinc-900">{title}</span>
              </li>
            ))}
          </ul>
          <div className="text-center mt-12">
            <a
              href="https://wa.me/919925238599?text=Hello%20Kutch%20Safari%20Resort%2C%20I%20would%20like%20to%20arrange%20an%20experience%20during%20my%20stay."
              target="_blank"
              rel="noreferrer"
              className="inline-flex items-center gap-2 bg-[var(--terracotta)] text-white px-8 py-3 uppercase tracking-widest text-sm font-semibold hover:bg-[#b04838] transition-colors rounded-sm"
            >
              <MessageCircle className="w-4 h-4" aria-hidden="true" /> Arrange an Experience
            </a>
          </div>
        </div>
      </section>

      <Footer />
    </div>
  );
}
