import { useEffect } from "react";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";

export default function GalleryPage() {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  return (
    <div className="min-h-screen bg-[#f8f5e2] font-sans text-zinc-900">
      <Navbar />
      <div className="pt-24 pb-16 bg-[#f8f5e2] border-b border-[#e4d5c7]">
        <div className="container mx-auto px-6 text-center max-w-3xl">
          <h1 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6">Gallery</h1>
          <p className="text-lg text-zinc-600">The resort and its surroundings in pictures.</p>
        </div>
      </div>
      
      <section className="py-24">
        <div className="container mx-auto px-6">
          <div className="max-w-4xl mx-auto prose prose-zinc lg:prose-lg">
            
      <div className="grid grid-cols-2 md:grid-cols-3 gap-6">
         <img src="/assets/images/new/kutch-ac-cottage-kutchi-cottage-interior-2.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Inside a Kutchi AC cottage" loading="lazy" />
         <img src="/assets/images/new/kutch-ac-cottage-_dsc9435.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="A Kutchi AC cottage" loading="lazy" />
         <img src="/assets/images/new/authentic-sunrise-bhungas.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Bhunga cottages at sunrise" loading="lazy" />
         <img src="/assets/images/new/restaurant-kutch-safari-ab-vision-11.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="The Banni restaurant" loading="lazy" />
         <img src="/assets/images/new/kutchi-tribes-rabari-ravechi-festival.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Rabari families at the Ravechi festival" loading="lazy" />
         <img src="/assets/images/new/safari-camel-experience.png" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Camel safari" loading="lazy" />
         <img src="/assets/images/new/aerial-property-shot.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="The resort from the air" loading="lazy" />
         <img src="/assets/images/new/gallery/20180326_174201.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 8 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/20180326_174202.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 9 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/20180326_174213.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 10 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/20180326_174255.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 11 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/FB_IMG_1522280099701.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 12 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/FB_IMG_1522280105771.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 13 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/FB_IMG_1522280109576.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 14 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/FB_IMG_1522280142647.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 15 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/FB_IMG_1522280163582.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 16 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/FB_IMG_1522280176946.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 17 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/FB_IMG_1522280191097.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 18 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/FB_IMG_1522280197552.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 19 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/FB_IMG_1522280255030.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 20 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/IMG-20180402-WA0052.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 21 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/IMG-20180402-WA0053.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 22 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/IMG_20190109_080807.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 23 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/IMG_20190310_075643.jpg" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 24 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/_DSC9408.JPG" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 25 of 26" loading="lazy" />
         <img src="/assets/images/new/gallery/_DSC9412.JPG" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity shadow-sm" alt="Kutch Safari Resort, photo 26 of 26" loading="lazy" />
      </div>
    
          </div>
        </div>
      </section>
      
      <Footer />
    </div>
  );
}
