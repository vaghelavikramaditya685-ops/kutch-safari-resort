import { useEffect } from "react";
import { Link } from "wouter";
import { ArrowRight } from "lucide-react";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";

/* Places around the resort, each with its destination guide (/destination/:slug). */
const PLACES = [
  { title: "White Rann & Rann Utsav", desc: "The salt desert at Dhordo, at its best on a full moon night.", img: "/assets/images/new/authentic-sunrise-bhungas.jpg", slug: "the-great-white-rann" },
  { title: "Road to Heaven & Dholavira", desc: "A causeway straight across the salt to a 4,500-year-old Harappan city.", img: "/assets/images/new/kutch-destination-road_2.jpg", slug: "road-to-heaven" },
  { title: "Banni Villages", desc: "Embroidery, leatherwork, and bell-making in the hamlets.", img: "/assets/images/new/kutchi-tribes-rabari-ravechi-festival.jpg", slug: "artisan-villages" },
  { title: "Kala Dungar & Birding", desc: "The Black Hill, the highest point in Kutch — and flamingos below.", img: "/assets/images/new/kala-dungar-scenic.jpg", slug: "kala-dungar" },
  { title: "Mandvi Beach", desc: "A shipbuilding town, a palace on the sand, and the Arabian Sea.", img: "/assets/images/new/kutch-destination-mandvi-beach.jpg", slug: "mandvi-beach-palace" },
  { title: "Bhuj & Bhujodi", desc: "Aina Mahal, Prag Mahal, and the weavers' village just outside town.", img: "/assets/images/new/kutch-handicrafts-block-demo.jpg", slug: "artisan-villages" },
];

export default function AroundTheResort() {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  return (
    <div className="min-h-screen bg-[#f8f5e2] font-sans text-zinc-900">
      <Navbar />

      <div className="pt-24 pb-16 bg-[#f8f5e2] border-b border-[#e4d5c7]">
        <div className="container mx-auto px-6 text-center max-w-3xl">
          <p className="text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4">Around the Resort</p>
          <h1 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6">Kutch, from Our Doorstep</h1>
          <p className="text-lg text-zinc-600">We sit on the road that everything in Kutch is on the way to.</p>
        </div>
      </div>

      {/* Brochure copy (2026–27). */}
      <section className="pt-20 pb-4">
        <div className="container mx-auto px-6 max-w-4xl">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl font-display font-bold text-zinc-900 mb-4">Experience Kutch, Where Tradition Meets Wonder</h2>
            <p className="text-lg text-zinc-600">What we arrange for our guests, at the resort and beyond.</p>
          </div>
          <h3 className="text-2xl md:text-3xl font-display font-bold text-zinc-900 mb-6 text-center">Why Visit Kutch?</h3>
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
      </section>

      <section className="py-24">
        <div className="container mx-auto px-6">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {PLACES.map((place, i) => (
              <Link key={i} href={`/destination/${place.slug}`} className="group cursor-pointer block">
                <div className="relative h-64 overflow-hidden rounded-sm mb-4">
                  <img src={place.img} className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt={place.title} />
                </div>
                <h2 className="text-xl font-bold text-zinc-900 mb-2 group-hover:text-[var(--terracotta)] transition-colors">{place.title}</h2>
                <p className="text-zinc-600 text-sm mb-3">{place.desc}</p>
                <span className="text-[var(--terracotta)] uppercase tracking-widest text-xs font-bold flex items-center gap-1">
                  Discover <ArrowRight className="w-3 h-3" />
                </span>
              </Link>
            ))}
          </div>
        </div>
      </section>

      <Footer />
    </div>
  );
}
