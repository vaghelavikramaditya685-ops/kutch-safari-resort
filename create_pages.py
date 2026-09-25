import os

def write_page(name, component, content):
    path = f"client/src/pages/{name}.tsx"
    code = f"""import {{ useEffect }} from "react";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";

export default function {component}() {{
  useEffect(() => {{
    window.scrollTo(0, 0);
  }}, []);

  return (
    <div className="min-h-screen bg-white font-sans text-zinc-800">
      <Navbar />
      <div className="pt-24 pb-16 bg-[#f8f6f3] border-b border-[#e4d5c7]">
        <div className="container mx-auto px-6 text-center max-w-3xl">
          <h1 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6">{content['title']}</h1>
          <p className="text-lg text-zinc-600">{content['subtitle']}</p>
        </div>
      </div>
      
      <section className="py-24">
        <div className="container mx-auto px-6">
          <div className="max-w-4xl mx-auto prose prose-zinc lg:prose-lg">
            {content['body']}
          </div>
        </div>
      </section>
      
      <Footer />
    </div>
  );
}}
"""
    with open(path, "w", encoding="utf-8") as f:
        f.write(code)

# Our Journey
write_page("OurJourney", "OurJourney", {
    "title": "Our Journey",
    "subtitle": "The visionary behind it all: Mike Vaghela",
    "body": """
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
    """
})

# Dining
write_page("Dining", "Dining", {
    "title": "Dining at The Banni",
    "subtitle": "Multi-cuisine, vegetarian and non-vegetarian, by the lake.",
    "body": """
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
    """
})

# Weddings
write_page("Weddings", "Weddings", {
    "title": "Weddings & Events",
    "subtitle": "Host your celebrations by the lake.",
    "body": """
      <div className="text-center">
        <p className="text-zinc-600 max-w-2xl mx-auto mb-12">With an open garden lawn and facilities for up to 300 guests, Kutch Safari Resort is the perfect backdrop for your special day.</p>
        <div className="h-96 bg-zinc-100 flex items-center justify-center text-zinc-400 rounded-sm">Event Photo Placeholder</div>
      </div>
    """
})

# Gallery
write_page("GalleryPage", "GalleryPage", {
    "title": "Gallery",
    "subtitle": "The resort and its surroundings in pictures.",
    "body": """
      <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
         <img src="/assets/images/new/kutch-ac-cottage-kutchi-cottage-interior-2.jpg" className="w-full h-48 object-cover rounded-sm" alt="Gallery" />
         <img src="/assets/images/new/kutch-ac-cottage-_dsc9435.jpg" className="w-full h-48 object-cover rounded-sm" alt="Gallery" />
         <img src="/assets/images/new/authentic-sunrise-bhungas.jpg" className="w-full h-48 object-cover rounded-sm" alt="Gallery" />
         <img src="/assets/images/new/restaurant-kutch-safari-ab-vision-11.jpg" className="w-full h-48 object-cover rounded-sm" alt="Gallery" />
         <img src="/assets/images/new/kutchi-tribes-rabari-ravechi-festival.jpg" className="w-full h-48 object-cover rounded-sm" alt="Gallery" />
         <img src="/assets/images/new/safari-camel-experience.png" className="w-full h-48 object-cover rounded-sm" alt="Gallery" />
      </div>
    """
})

# Plan Your Visit
write_page("PlanYourVisit", "PlanYourVisit", {
    "title": "Plan Your Visit",
    "subtitle": "How to reach us and what to expect.",
    "body": """
      <div className="space-y-12">
         <div>
            <h2 className="text-2xl font-display font-bold mb-4">Finding Us</h2>
            <p className="text-zinc-600 mb-6">Take the Khavda road out of Bhuj — the road towards the White Rann. Fourteen kilometres on, just past the bus stop, a milestone reads LORIYA 7 KM. Turn right there and come straight up the hill.</p>
         </div>
         <div className="bg-[#f8f6f3] p-8 border border-[#e4d5c7] rounded-sm">
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
    """
})

# Packages
write_page("Packages", "Packages", {
    "title": "Colors of Kutch",
    "subtitle": "Kutch ke Rang, Apno ke Sang",
    "body": """
      <p className="text-center text-zinc-600 mb-12">Two curated journeys, with a private vehicle, a night in the Rann and every permit arranged.</p>
      <div className="grid md:grid-cols-2 gap-12">
         <div className="border border-[#e4d5c7] rounded-sm overflow-hidden">
            <img src="/assets/images/new/kutch-destination-road_2.jpg" className="w-full h-48 object-cover" alt="Package" />
            <div className="p-8">
               <span className="text-xs font-bold text-[var(--terracotta)] uppercase tracking-widest mb-2 block">2 Nights · 3 Days</span>
               <h3 className="text-2xl font-display font-bold mb-4">The Rann Short Break</h3>
               <p className="text-zinc-600 mb-6">Banni villages, the White Rann at sunset and Rann Utsav; then Kala Dungar and Dholavira by the Road to Heaven; then Bhuj and Bhujodi.</p>
            </div>
         </div>
         <div className="border border-[#e4d5c7] rounded-sm overflow-hidden">
            <img src="/assets/images/new/kutch-destination-mandvi-beach.jpg" className="w-full h-48 object-cover" alt="Package" />
            <div className="p-8">
               <span className="text-xs font-bold text-[var(--terracotta)] uppercase tracking-widest mb-2 block">3 Nights · 4 Days</span>
               <h3 className="text-2xl font-display font-bold mb-4">The Complete Kutch</h3>
               <p className="text-zinc-600 mb-6">Everything above, with a fourth day for Mandvi — the Vijay Vilas Palace, the shipyard and the beach.</p>
            </div>
         </div>
      </div>
    """
})

print("Created all placeholder pages.")
