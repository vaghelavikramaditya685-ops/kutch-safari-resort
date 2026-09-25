import os

stay_code = """import { useEffect } from "react";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";

export default function Stay() {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  return (
    <div className="min-h-screen bg-[#f8f6f3] font-sans text-zinc-800">
      <Navbar />

      <div className="pt-24 pb-16 border-b border-[#e4d5c7]">
        <div className="container mx-auto px-6 text-center max-w-3xl">
          <p className="text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4">Accommodation</p>
          <h1 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6">The Stay</h1>
          <p className="text-lg text-zinc-600">Two categories, twenty cottages, every one of them looking out over the lake.</p>
        </div>
      </div>

      <section className="py-24">
        <div className="container mx-auto px-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-5xl mx-auto">
            {/* Kutchi AC */}
            <div className="group border border-[#e4d5c7] rounded-sm overflow-hidden flex flex-col">
              <div className="relative h-72 overflow-hidden">
                <div className="absolute top-4 left-4 bg-black/60 text-white text-xs uppercase tracking-widest px-3 py-1 z-10 backdrop-blur-md rounded-sm">12 Cottages</div>
                <img src="/assets/images/new/kutch-ac-cottage-kutchi-cottage-interior-2.jpg" className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt="Kutchi AC Cottage" />
              </div>
              <div className="p-8 flex flex-col flex-grow bg-white">
                <h3 className="text-2xl font-display font-bold text-zinc-900 mb-4">Kutchi AC Cottage</h3>
                <p className="text-zinc-600 mb-6 flex-grow">
                  Traditionally styled cottages with Kutchi craft detailing, a private balcony over the lake and a generous bathroom. The most spacious rooms on the property.
                </p>
                <div className="flex flex-wrap gap-2 mb-8">
                  <span className="text-xs bg-zinc-100 text-zinc-600 px-3 py-1 rounded-sm">Lake-facing balcony</span>
                  <span className="text-xs bg-zinc-100 text-zinc-600 px-3 py-1 rounded-sm">Air conditioned</span>
                  <span className="text-xs bg-zinc-100 text-zinc-600 px-3 py-1 rounded-sm">Free Wi-Fi</span>
                  <span className="text-xs bg-zinc-100 text-zinc-600 px-3 py-1 rounded-sm">Flat-screen TV</span>
                </div>
                <a href="/#contact" className="text-center border border-[var(--terracotta)] text-[var(--terracotta)] py-3 uppercase tracking-widest text-sm font-semibold hover:bg-[var(--terracotta)] hover:text-white transition-colors rounded-sm">
                  Enquire
                </a>
              </div>
            </div>

            {/* Deluxe AC */}
            <div className="group border border-[#e4d5c7] rounded-sm overflow-hidden flex flex-col">
              <div className="relative h-72 overflow-hidden">
                <div className="absolute top-4 left-4 bg-black/60 text-white text-xs uppercase tracking-widest px-3 py-1 z-10 backdrop-blur-md rounded-sm">8 Cottages</div>
                <img src="/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-deluxe-ac-cottage-interior-02.jpg" className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt="Deluxe AC Cottage" />
              </div>
              <div className="p-8 flex flex-col flex-grow bg-white">
                <h3 className="text-2xl font-display font-bold text-zinc-900 mb-4">Deluxe AC Cottage</h3>
                <p className="text-zinc-600 mb-6 flex-grow">
                  Comfortable, uncluttered rooms with the same view and the same quiet. A good choice for couples and for families travelling on to Dholavira the next morning.
                </p>
                <div className="flex flex-wrap gap-2 mb-8">
                  <span className="text-xs bg-zinc-100 text-zinc-600 px-3 py-1 rounded-sm">Lake view</span>
                  <span className="text-xs bg-zinc-100 text-zinc-600 px-3 py-1 rounded-sm">Air conditioned</span>
                  <span className="text-xs bg-zinc-100 text-zinc-600 px-3 py-1 rounded-sm">Free Wi-Fi</span>
                  <span className="text-xs bg-zinc-100 text-zinc-600 px-3 py-1 rounded-sm">Hot & cold water</span>
                </div>
                <a href="/#contact" className="text-center border border-[var(--terracotta)] text-[var(--terracotta)] py-3 uppercase tracking-widest text-sm font-semibold hover:bg-[var(--terracotta)] hover:text-white transition-colors rounded-sm">
                  Enquire
                </a>
              </div>
            </div>
          </div>
        </div>
      </section>

      <Footer />
    </div>
  );
}
"""

with open("client/src/pages/Stay.tsx", "w", encoding="utf-8") as f:
    f.write(stay_code)

print("Rewrote Stay.tsx")
