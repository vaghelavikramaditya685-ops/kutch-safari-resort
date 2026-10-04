import { useEffect } from "react";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";

export default function Packages() {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  return (
    <div className="min-h-screen bg-[#f8f5e2] font-sans text-zinc-900">
      <Navbar />
      <div className="pt-24 pb-16 bg-[#f8f5e2] border-b border-[#e4d5c7]">
        <div className="container mx-auto px-6 text-center max-w-3xl">
          <h1 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6">Colors of Kutch</h1>
          <p className="text-lg text-zinc-600">Kutch ke Rang, Apno ke Sang</p>
        </div>
      </div>
      
      <section className="py-24">
        <div className="container mx-auto px-6">
          <div className="max-w-4xl mx-auto prose prose-zinc lg:prose-lg">
            
      <p className="text-center text-zinc-600 mb-12">Two curated journeys, with a private vehicle, a night in the Rann and every permit arranged.</p>
      <div className="grid md:grid-cols-2 gap-12">
         <div className="border border-[#e4d5c7] rounded-sm overflow-hidden">
            <img src="/assets/images/new/kutch-destination-road_2.jpg" className="w-full h-48 object-cover" alt="Package" />
            <div className="p-8">
               <span className="text-xs font-bold text-[var(--terracotta)] uppercase tracking-widest mb-2 block">2 Nights · 3 Days</span>
               <h2 className="text-2xl font-display font-bold mb-4">The Rann Short Break</h2>
               <p className="text-zinc-600 mb-6">Banni villages, the White Rann at sunset and Rann Utsav; then Kala Dungar and Dholavira by the Road to Heaven; then Bhuj and Bhujodi.</p>
            </div>
         </div>
         <div className="border border-[#e4d5c7] rounded-sm overflow-hidden">
            <img src="/assets/images/new/kutch-destination-mandvi-beach.jpg" className="w-full h-48 object-cover" alt="Package" />
            <div className="p-8">
               <span className="text-xs font-bold text-[var(--terracotta)] uppercase tracking-widest mb-2 block">3 Nights · 4 Days</span>
               <h2 className="text-2xl font-display font-bold mb-4">The Complete Kutch</h2>
               <p className="text-zinc-600 mb-6">Everything above, with a fourth day for Mandvi — the Vijay Vilas Palace, the shipyard and the beach.</p>
            </div>
         </div>
      </div>
    
          </div>
        </div>
      </section>
      
      <Footer />
    </div>
  );
}
