import { useEffect, useState } from "react";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";
import { Snowflake, Wifi, Coffee, Tv, Lock, Archive, X, Wind } from "lucide-react";
import { bookingUrl } from "@/lib/booking";

function RoomTemplate({ title, exteriorTitle, interiorTitle, extImgs, intImgs, subtitle, description, onBookNow }: any) {
  const [lightbox, setLightbox] = useState<string | null>(null);

  return (
    <div className="mb-24 last:mb-12">
      <h2 className="text-center font-display text-3xl md:text-4xl uppercase tracking-[0.2em] text-zinc-800 mb-12">
        {title}
      </h2>
      
      <div className="container px-4 md:px-8 max-w-[90rem]">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 mb-12 items-center">
          {/* Photo Masonry - 8 cols */}
          <div className="lg:col-span-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="flex flex-col gap-6">
              <h3 className="text-center font-display text-xl uppercase tracking-widest text-zinc-600">{exteriorTitle}</h3>
              {extImgs.map((img: string, i: number) => (
                <img 
                  key={i} 
                  src={img} 
                  alt="Exterior" 
                  className="w-full h-80 object-cover rounded-md shadow-sm border border-zinc-200 cursor-zoom-in hover:opacity-90 transition-opacity" 
                  onClick={() => setLightbox(img)}
                />
              ))}
            </div>
            <div className="flex flex-col gap-6">
              <h3 className="text-center font-display text-xl uppercase tracking-widest text-zinc-600">{interiorTitle}</h3>
              {intImgs.map((img: string, i: number) => (
                <img 
                  key={i} 
                  src={img} 
                  alt="Interior" 
                  className="w-full h-80 object-cover rounded-md shadow-sm border border-zinc-200 cursor-zoom-in hover:opacity-90 transition-opacity" 
                  onClick={() => setLightbox(img)}
                />
              ))}
            </div>
          </div>

          {/* Text Content - 4 cols */}
          <div className="lg:col-span-4 flex flex-col justify-center">
            <h3 className="font-display text-3xl md:text-4xl italic text-zinc-800 mb-6 font-semibold tracking-wide">
              {subtitle}
            </h3>
            <p className="text-zinc-600 text-base md:text-lg leading-relaxed font-light mb-8">
              {description}
            </p>
            <a href={bookingUrl()} className="bg-[var(--terracotta)] text-white px-8 py-3 w-fit uppercase tracking-widest text-sm font-semibold hover:opacity-90 transition-opacity rounded-sm shadow-md">
              Book Now
            </a>
          </div>
        </div>
      </div>

      {/* Amenities Strip */}
      <div className="bg-[#f4efe1] py-8 border-y border-black/5 mt-16">
        <div className="container px-4 mx-auto">
          <div className="grid grid-cols-3 md:grid-cols-6 gap-4 text-center divide-x divide-black/5">
            <div className="flex flex-col items-center gap-3">
              <Snowflake className="h-8 w-8 text-zinc-400 stroke-[1.5]" />
              <span className="text-xs uppercase tracking-wider text-zinc-500 font-medium">Air Conditioner</span>
            </div>

            <div className="flex flex-col items-center gap-3">
              <Wifi className="h-8 w-8 text-zinc-400 stroke-[1.5]" />
              <span className="text-xs uppercase tracking-wider text-zinc-500 font-medium">Wi-Fi</span>
            </div>
            <div className="flex flex-col items-center gap-3">
              <Coffee className="h-8 w-8 text-zinc-400 stroke-[1.5]" />
              <span className="text-xs uppercase tracking-wider text-zinc-500 font-medium">Hot Kettle</span>
            </div>
            <div className="flex flex-col items-center gap-3">
              <Tv className="h-8 w-8 text-zinc-400 stroke-[1.5]" />
              <span className="text-xs uppercase tracking-wider text-zinc-500 font-medium">Television</span>
            </div>
            <div className="flex flex-col items-center gap-3">
              <Lock className="h-8 w-8 text-zinc-400 stroke-[1.5]" />
              <span className="text-xs uppercase tracking-wider text-zinc-500 font-medium">In-Room Safe</span>
            </div>

            <div className="flex flex-col items-center gap-3">
              <Wind className="h-8 w-8 text-zinc-400 stroke-[1.5]" />
              <span className="text-xs uppercase tracking-wider text-zinc-500 font-medium">Hair-Dryer</span>
            </div>
          </div>
        </div>
      </div>

      {lightbox && (
        <div 
          className="fixed inset-0 z-[100] flex items-center justify-center bg-black/95 p-4 cursor-zoom-out"
          onClick={() => setLightbox(null)}
        >
          <img 
            src={lightbox} 
            className="max-w-full max-h-[90vh] object-contain rounded-md shadow-2xl" 
            alt="Enlarged view" 
          />
          <button 
            className="absolute top-6 right-6 text-white text-5xl hover:text-gray-400 focus:outline-none"
            onClick={() => setLightbox(null)}
          >
            &times;
          </button>
        </div>
      )}
    </div>
  );
}

function Accommodation() {
  const [expanded, setExpanded] = useState(false);

  return (
    <section id="cottages" className="bg-[#e4d5c7] pt-24 pb-16">
      <div className="container px-4">
        <h2 className="text-center font-display text-4xl md:text-5xl uppercase tracking-[0.15em] text-[#4a4a4a] mb-8">
          ACCOMMODATION
        </h2>
        <p className="max-w-4xl mx-auto text-base md:text-lg leading-relaxed text-center text-[#5a5a5a] mb-16 font-light">
          Experience Kutchi hospitality in our traditional AC Cottages and Deluxe AC Cottages. Spread across the hillside, each is beautifully decorated with the mirror work and mud appliqu├® of local artisans. With sit-outs and verandas facing the lake, nature is never out of sight. <em>We call it Rustic Luxury.</em>
        </p>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-10 md:gap-16 max-w-5xl mx-auto">
          <div className="flex flex-col items-center gap-6">
            <img 
              src="/assets/images/new/kutch-ac-cottage-bhunga1.jpg" 
              alt="Kutch AC Cottage" 
              className="w-full aspect-[3/4] object-cover shadow-lg border-2 border-white/20" 
            />
            <div className="text-center">
              <h3 className="font-display text-xl tracking-[0.15em] text-[#4a4a4a] uppercase">Kutch AC Cottage</h3>
              <p className="text-sm tracking-widest text-[#4a4a4a]/70 uppercase mt-1">(Standard)</p>
            </div>
          </div>
          <div className="flex flex-col items-center gap-6">
            <img 
              src="/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-deluxe-ac-cottage.jpg" 
              alt="Deluxe AC Cottage" 
              className="w-full aspect-[3/4] object-cover shadow-lg border-2 border-white/20" 
            />
            <div className="text-center">
              <h3 className="font-display text-xl tracking-[0.15em] text-[#4a4a4a] uppercase">Deluxe AC Cottage</h3>
              <p className="text-sm tracking-widest text-[#4a4a4a]/70 uppercase mt-1">(Deluxe)</p>
            </div>
          </div>
        </div>

        {!expanded && (
          <div className="mt-16 flex justify-center">
            <button 
              onClick={() => setExpanded(true)} 
              className="bg-[var(--terracotta)] text-white px-12 py-3.5 uppercase tracking-widest text-sm font-semibold hover:opacity-90 transition-opacity shadow-md"
            >
              Explore
            </button>
          </div>
        )}
      </div>

      {expanded && (
        <div className="mt-24 bg-background pt-20 border-t border-black/5 animate-in fade-in duration-700 slide-in-from-top-8">
          <RoomTemplate 
            title="Kutch AC Cottage"
            exteriorTitle="Exterior"
            interiorTitle="Interior"
            extImgs={[
              "/assets/images/new/kutch-ac-cottage-bhunga1.jpg",
              "/assets/images/new/kutch-ac-cottage-kutchi-bathroom1.jpg"
            ]}
            intImgs={[
              "/assets/images/new/kutch-ac-cottage-kutchi-cottage-interior-2.jpg",
              "/assets/images/new/kutch-ac-cottage-_dsc9435.jpg"
            ]}
            subtitle="Cottages inspired by Local Styles"
            description="Our signature circular bhungas feature traditional thatched roofs that keep the interior cool, adorned with authentic Kutchi mirror-work. Each cottage is fully air-conditioned with modern en-suite bathrooms and a private veranda where you enjoy a luxurious lifestyle called Rustic Luxury!"
          />

          <RoomTemplate 
            title="Deluxe AC Cottage"
            exteriorTitle="Exterior"
            interiorTitle="Interior"
            extImgs={[
              "/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-deluxe-ac-cottage.jpg",
              "/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-kutch-safari-ab-vision-17.jpg"
            ]}
            intImgs={[
              "/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-deluxe-ac-cottage-interior.jpg",
              "/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-deluxe-ac-cottage-interior-02.jpg"
            ]}
            subtitle="Spacious & Elegantly Designed"
            description="The deluxe cottages offer enhanced comfort while retaining the rich cultural aesthetics of the region. Perfect for families looking for an extended lakeside retreat, these spacious rooms feature exquisite decor, beautiful garden views, and full amenities."
          />
        </div>
      )}
    </section>
  );
}




export default function Stay() {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  return (
    <div className="min-h-screen bg-[#f8f5e2] font-sans text-zinc-900">
      <Navbar />

      <div className="pt-24 pb-16 border-b border-[#e4d5c7]">
        <div className="container mx-auto px-6 text-center max-w-3xl">
          <p className="text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4">Accommodation</p>
          <h1 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6">The Stay</h1>
          <p className="text-lg text-zinc-600">Two categories, twenty cottages, every one of them looking out over the lake.</p>
        </div>
      </div>

      <div className="py-24">
        <div className="container px-4 mt-8">
            <RoomTemplate 
              title="Kutch AC Cottage"
              exteriorTitle="Exterior"
              interiorTitle="Interior"
              extImgs={[
                "/assets/images/new/kutch-ac-cottage-bhunga1.jpg",
                "/assets/images/new/kutch-ac-cottage-kutchi-bathroom1.jpg"
              ]}
              intImgs={[
                "/assets/images/new/kutch-ac-cottage-kutchi-cottage-interior-2.jpg",
                "/assets/images/new/kutch-ac-cottage-_dsc9435.jpg"
              ]}
              subtitle="Cottages inspired by Local Styles"
              description="Our signature circular bhungas feature traditional thatched roofs that keep the interior cool, adorned with authentic Kutchi mirror-work. Each cottage is fully air-conditioned with modern en-suite bathrooms and a private veranda where you enjoy a luxurious lifestyle called Rustic Luxury!"
            />

            <RoomTemplate 
              title="Deluxe AC Cottage"
              exteriorTitle="Exterior"
              interiorTitle="Interior"
              extImgs={[
                "/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-deluxe-ac-cottage.jpg",
                "/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-kutch-safari-ab-vision-17.jpg"
              ]}
              intImgs={[
                "/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-deluxe-ac-cottage-interior.jpg",
                "/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-deluxe-ac-cottage-interior-02.jpg"
              ]}
              subtitle="Spacious & Elegantly Designed"
              description="The deluxe cottages offer enhanced comfort while retaining the rich cultural aesthetics of the region. Perfect for families looking for an extended lakeside retreat, these spacious rooms feature exquisite decor, beautiful garden views, and full amenities."
            />
        </div>
      </div>

      <Footer />
    </div>
  );
}
