import { useEffect } from "react";
import { Link } from "wouter";
import { ArrowRight, MapPin } from "lucide-react";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";
import { PACKAGES, duration, rupees } from "@/lib/packages";

/* The three tour packages; "More Details" opens each one at /packages/:slug,
   laid out like the White Rann Camp tariff page. */
export default function Packages() {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  return (
    <div className="min-h-screen bg-[#f8f5e2] font-sans text-zinc-900">
      <Navbar />
      <div className="pt-24 pb-16 bg-[#f8f5e2] border-b border-[#e4d5c7]">
        <div className="container mx-auto px-6 text-center max-w-3xl">
          <h1 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6">Kutch Tour Packages</h1>
          <p className="text-lg text-zinc-600">Kutch ke Rang, Apno ke Sang</p>
        </div>
      </div>

      <section className="py-20">
        <div className="container mx-auto px-6">
          <p className="text-center text-zinc-600 mb-12 max-w-2xl mx-auto">
            Three journeys through Kutch, with hotels, meals, transport and sightseeing included.
            Prices are per person and depend on how many travel together.
          </p>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            {PACKAGES.map(p => (
              <article key={p.slug} className="bg-white border border-[#e4d5c7] rounded-sm overflow-hidden shadow-sm flex flex-col">
                <div className="grid grid-cols-4 h-48">
                  {p.photos.map(ph => (
                    <img key={ph.src} src={ph.src} alt={ph.alt} loading="lazy" className="w-full h-full object-cover" />
                  ))}
                </div>
                <div className="p-7 flex flex-col flex-1">
                  <span className="text-xs font-bold text-[var(--terracotta)] uppercase tracking-widest mb-2 block">
                    {duration(p)} · {p.from} to {p.to}
                  </span>
                  <h2 className="text-2xl font-display font-bold mb-3">{p.title}</h2>
                  <p className="text-sm text-zinc-600 mb-4 flex gap-2">
                    <MapPin className="w-4 h-4 mt-0.5 shrink-0 text-[var(--terracotta)]" />
                    <span>{p.route.join(" - ")}</span>
                  </p>
                  <p className="text-sm text-zinc-600 mb-6">{p.kind} · stays: {p.stays}</p>
                  <div className="mt-auto flex items-end justify-between gap-4">
                    <p className="text-sm text-zinc-600">
                      From <span className="block text-xl font-semibold text-zinc-900">{rupees(Math.min(...p.prices.filter(x => x.label !== "Extra Person").map(x => x.perPerson)))}</span>
                      per person
                    </p>
                    <Link
                      href={`/packages/${p.slug}`}
                      className="inline-flex items-center gap-2 bg-[var(--terracotta)] text-white px-5 py-3 uppercase tracking-widest text-xs font-semibold hover:opacity-90 transition-opacity"
                    >
                      More Details <ArrowRight className="w-4 h-4" />
                    </Link>
                  </div>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>

      <Footer />
    </div>
  );
}
