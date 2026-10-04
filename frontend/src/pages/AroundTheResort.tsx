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
