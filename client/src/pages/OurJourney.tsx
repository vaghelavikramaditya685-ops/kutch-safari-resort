import { useEffect } from "react";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";

export default function OurJourney() {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  return (
    <div className="min-h-screen bg-[#f8f5e2] font-sans text-zinc-900">
      <Navbar />
      <div className="pt-24 pb-16 bg-[#f8f5e2] border-b border-[#e4d5c7]">
        <div className="container mx-auto px-6 text-center max-w-3xl">
          <h1 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6">Our Journey</h1>
          <p className="text-lg text-zinc-600">The visionary behind it all: Mike Vaghela</p>
        </div>
      </div>
      
      <section className="py-24">
        <div className="container mx-auto px-6">
          <div className="max-w-4xl mx-auto prose prose-zinc lg:prose-lg">
            
      <div className="space-y-16">
        <div className="grid md:grid-cols-2 gap-8 items-center">
           <img src="/assets/images/new/aerial-property-shot.jpg" alt="Aerial" className="w-full rounded-sm" />
           <div>
             <h2 className="text-2xl font-display font-bold mb-4">Why We Started</h2>
             <p className="text-zinc-600">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Placeholder text for why it started.</p>
           </div>
        </div>
        <div className="grid md:grid-cols-2 gap-8 items-center md:flex-row-reverse">
           <div>
             <h2 className="text-2xl font-display font-bold mb-4">Awards & Recognition</h2>
             <p className="text-zinc-600">Bringing Gujarat and Kutch Tourism to foreigners across the world.</p>
           </div>
           <div className="h-64 bg-zinc-200 rounded-sm flex items-center justify-center text-zinc-400">Award Photo Placeholder</div>
        </div>
        <div className="border-t border-[#e4d5c7] pt-12">
            <h2 className="text-2xl font-display font-bold mb-8 text-center">Timeline</h2>
            <div className="flex justify-between items-center text-sm font-semibold tracking-widest text-zinc-500 uppercase">
               <span>1992</span>
               <div className="flex-grow h-px bg-[var(--terracotta)] mx-4"></div>
               <span>2026</span>
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
