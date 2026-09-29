import { useEffect } from "react";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";

export default function Dining() {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  return (
    <div className="min-h-screen bg-[#f8f5e2] font-sans text-zinc-900">
      <Navbar />
      <div className="pt-24 pb-16 bg-[#f8f5e2] border-b border-[#e4d5c7]">
        <div className="container mx-auto px-6 text-center max-w-3xl">
          <h1 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6">Dining at The Banni</h1>
          <p className="text-lg text-zinc-600">Multi-cuisine, vegetarian and non-vegetarian, by the lake.</p>
        </div>
      </div>
      
      <section className="py-24">
        <div className="container mx-auto px-6">
          <div className="max-w-4xl mx-auto prose prose-zinc lg:prose-lg">
            
      <div className="grid md:grid-cols-2 gap-12 items-center">
         <div>
           <h2 className="text-3xl font-display font-bold mb-6">A Taste of Kutch</h2>
           <p className="text-zinc-600 mb-4">Our restaurant is named for the grasslands north of here. It serves both vegetarian and non-vegetarian food, and the kitchen moves comfortably between Kutchi, Gujarati, Punjabi, Chinese and Continental.</p>
           <p className="text-zinc-600">Ask for the Kutchi thali. Ask, too, about a gala dinner with folk musicians — it takes a day's notice and is worth the wait.</p>
         </div>
         <div className="grid grid-cols-2 gap-4">
            <img src="/assets/images/new/restaurant-kutch-safari-ab-vision-11.jpg" alt="Restaurant" className="w-full h-48 object-cover rounded-sm" />
            <div className="bg-zinc-100 h-48 flex items-center justify-center text-xs text-zinc-400">Food Image Placeholder</div>
         </div>
      </div>
    
          </div>
        </div>
      </section>
      
      <Footer />
    </div>
  );
}
