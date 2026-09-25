import { useEffect } from "react";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";

export default function PlanYourVisit() {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  return (
    <div className="min-h-screen bg-[#f8f5e2] font-sans text-zinc-900">
      <Navbar />
      <div className="pt-24 pb-16 bg-[#f8f5e2] border-b border-[#e4d5c7]">
        <div className="container mx-auto px-6 text-center max-w-3xl">
          <h1 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6">Plan Your Visit</h1>
          <p className="text-lg text-zinc-600">How to reach us and what to expect.</p>
        </div>
      </div>
      
      <section className="py-24">
        <div className="container mx-auto px-6">
          <div className="max-w-4xl mx-auto prose prose-zinc lg:prose-lg">
            
      <div className="space-y-12">
         <div>
            <h2 className="text-2xl font-display font-bold mb-4">Finding Us</h2>
            <p className="text-zinc-600 mb-6">Take the Khavda road out of Bhuj — the road towards the White Rann. Fourteen kilometres on, just past the bus stop, a milestone reads LORIYA 7 KM. Turn right there and come straight up the hill.</p>
         </div>
         <div className="bg-[#f8f5e2] p-8 border border-[#e4d5c7] rounded-sm">
            <h3 className="text-xl font-bold mb-6">Distances from Bhuj</h3>
            <ul className="space-y-3">
              <li className="flex justify-between border-b border-[#e4d5c7] pb-2"><span>Ahmedabad</span> <span>350 km</span></li>
              <li className="flex justify-between border-b border-[#e4d5c7] pb-2"><span>Rajkot</span> <span>240 km</span></li>
              <li className="flex justify-between border-b border-[#e4d5c7] pb-2"><span>Mandvi</span> <span>60 km</span></li>
              <li className="flex justify-between"><span>White Rann, Dhordo</span> <span>80 km</span></li>
            </ul>
         </div>
         <div id="faq">
            <h2 className="text-2xl font-display font-bold mb-6">FAQs</h2>
            <p className="text-zinc-600">Frequently asked questions will be populated here.</p>
         </div>
      </div>
    
          </div>
        </div>
      </section>
      
      <Footer />
    </div>
  );
}
