import { Link } from "wouter";
/*
 * RANNRIDERS-STYLE HOMEPAGE — Kutch Safari Resort (v2)
 * Ground truth: rannriders.com
 * - Alternating white / warm sand full-width bands
 * - Centered uppercase serif section titles + italic serif taglines
 * - Faint wildlife line-art sketch behind/near titles
 * - Photo mosaics (tall+stacked, large+stacked, 4-photo rows)
 * - Terracotta EXPLORE-style buttons, floating Enquire Now pill
 * - Dark charcoal footer with link columns + contact block
 * Headings: Playfair Display uppercase; body: Jost sans.
 */
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { PhoneCall, Archive, Lock, Tv, Coffee, Wifi, Snowflake,
  Phone,
  Mail,
  MapPin,
  Menu,
  X,
  Facebook,
  Instagram,
  Star,
  Sun,
  Cloud,
  Wind,
  Waves,
  Heart,
  UtensilsCrossed,
} from "lucide-react";

const IMG = {
  logoMark: "/assets/images/logo-mark.png",
  heroWide: "/assets/images/hero-kutch-safari.png",
  campfire: "/assets/images/new/restaurant-kutch-safari-ab-vision-11.jpg",
  sketchAss: "/assets/images/sketch-wild-ass.png",
  sketchCamel: "/assets/images/sketch-camel.png",
  resortFront: "/assets/images/new/kutch-ac-cottage-bhunga1.jpg",
  cottagesLawn: "/assets/images/new/guests-img-20180402-wa0053.jpg",
  nightCottages: "/assets/images/campfire-night.png",
  bougainvillea: "/assets/images/new/guests-20180326_174202.jpg",
  room1: "/assets/images/new/kutch-ac-cottage-kutchi-cottage-interior.jpg",
  room2: "/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-deluxe-ac-cottage-interior.jpg",
  sunrisePath: "/assets/images/lake-sunrise-reference.png",
  sunsetTree: "/assets/images/new/pro-kala_dungar.jpg",
  roadToHeaven: "/assets/images/new/kutch-destination-road_2.jpg",
  whiteRann: "/assets/images/new/kutchi-tribes-rabari-ravechi-festival.jpg",
  mandviBeach: "/assets/images/new/kutch-destination-mandvi-beach.jpg",
};

const NAV = [
  { label: "The Resort", href: "#resort" },
  { label: "Stays", href: "#cottages" },
  { label: "Experiences", href: "#experiences" },
  { label: "Packages", href: "#packages" },
  { label: "Beyond Bhuj", href: "#explore" },
  { label: "Rann Utsav", href: "#rann-utsav" },
  { label: "Contact", href: "/#contact" },
];

function LogoBlock({ light = false }: { light?: boolean }) {
  return (
    <div className="flex items-center gap-3">
      <img
        src={IMG.logoMark}
        alt="Kutch Safari Resort mark"
        className="h-12 w-12 object-contain"
      />
      <div className="leading-tight">
        <div
          className={`font-display text-lg font-semibold tracking-[0.14em] ${light ? "text-primary-foreground" : "text-foreground"}`}
        >
          KUTCH SAFARI
        </div>
        <div
          className={`text-[0.6rem] font-medium uppercase tracking-[0.32em] ${
            light ? "text-primary-foreground/70" : "text-muted-foreground"
          }`}
        >
          Resort · Bhuj
        </div>
      </div>
    </div>
  );
}

function Navbar() {
  const [open, setOpen] = useState(false);

  return (
    <header className="fixed inset-x-0 top-0 z-50 transition-all duration-300 bg-background/95 backdrop-blur-md shadow-[0_1px_0_0_oklch(0.88_0.03_75/0.8)] py-3">
      <div className="w-full px-6 md:px-10 flex items-center justify-between">
        <a href="/" aria-label="Back to home" className="mr-auto pr-6">
          <LogoBlock light={false} />
        </a>
        <nav className="hidden items-center gap-5 xl:flex shrink-0">
          {NAV.map((n) => (
            <a
              key={n.href}
              href={n.href}
              className="text-xs font-medium uppercase tracking-[0.12em] transition-colors text-foreground/80 hover:text-foreground"
            >
              {n.label}
            </a>
          ))}
          <div>
            <WeatherBar />
          </div>
          <a
            href="https://wa.me/919925238599?text=Hello%20Kutch%20Safari%20Resort%2C%20I%20would%20like%20to%20book%20a%20stay."
            target="_blank"
            rel="noreferrer"
            className="ml-4 border border-[var(--terracotta)] bg-[var(--terracotta)] px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em] text-white hover:opacity-90 transition-opacity"
          >
            Book Now
          </a>
        </nav>
        <button
          className="xl:hidden text-foreground p-1"
          onClick={() => setOpen(!open)}
          aria-label="Toggle menu"
        >
          {open ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
        </button>
      </div>
      {open && (
        <div className="border-t border-border bg-background xl:hidden mt-3 shadow-lg">
          <div className="container flex flex-col gap-4 py-5 px-6">
            {NAV.map((n) => (
              <a
                key={n.href}
                href={n.href}
                className="text-base uppercase tracking-wide font-medium text-foreground hover:text-primary transition-colors"
                onClick={() => setOpen(false)}
              >
                {n.label}
              </a>
            ))}
            <a
              href="https://wa.me/919925238599?text=Hello%20Kutch%20Safari%20Resort%2C%20I%20would%20like%20to%20book%20a%20stay."
              target="_blank"
              rel="noreferrer"
              onClick={() => setOpen(false)}
              className="w-fit border border-[var(--terracotta)] bg-[var(--terracotta)] px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em] text-white"
            >
              Book Now
            </a>
          </div>
        </div>
      )}
    </header>
  );
}

function Welcome() {
  return (
    <section id="top" className="relative min-h-[92vh] flex items-end overflow-hidden">
      {/* Background aerial video */}
      <video
        autoPlay
        muted
        loop
        playsInline
        poster={IMG.heroWide}
        className="absolute inset-0 h-full w-full object-cover"
        aria-hidden
      >
        <source src="/assets/images/new/kutch-safari-resort-website-hero.mp4" type="video/mp4" />
      </video>
      {/* Gradient overlay for text contrast */}
      <div className="absolute inset-0 bg-gradient-to-t from-black/75 via-black/35 to-black/25" />
      <div className="container relative z-10 pb-20 pt-36">
        <div className="max-w-2xl text-white">
          <p className="kicker text-white/80">Bhuj · Kutch · Since 2005</p>
          <h1 className="font-display mt-4 text-5xl font-semibold leading-[1.05] text-white md:text-6xl">
            Welcome to Kutch Safari Resort, Bhuj
          </h1>
          <p className="section-tagline mt-5 text-xl md:text-2xl text-white/95 text-left italic">
            Memories that rise with the sun over the lake
          </p>
          
          
          <a
            href="https://wa.me/919925238599?text=Hello%20Kutch%20Safari%20Resort%2C%20I%20would%20like%20to%20check%20availability%20for%20a%20stay."
            target="_blank"
            rel="noreferrer"
            className="mt-8 inline-flex items-center gap-2 rounded-none bg-[var(--terracotta)] px-5 py-3 text-xs font-medium uppercase tracking-[0.12em] text-white hover:opacity-90 transition-opacity"
          >
            <svg viewBox="0 0 24 24" className="h-4 w-4 fill-white" aria-hidden>
              <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.2h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.85 9.85 0 0 0 12.04 2zm5.23 14.2c-.22.62-1.3 1.19-1.79 1.25-.45.06-1.02.31-3.43-.68-2.89-1.19-4.74-4.07-4.88-4.27-.14-.2-1.14-1.51-1.14-2.89s.72-2.05.98-2.33c.26-.28.56-.35.75-.35.19 0 .38 0 .55.01.18.01.42-.07.65.49.23.56.8 1.93.87 2.07.07.15.12.32.02.51-.1.2-.15.32-.29.49-.15.18-.31.39-.44.52-.15.15-.3.31-.13.61.17.3.78 1.27 1.67 2.06 1.15 1.02 2.12 1.34 2.42 1.49.3.15.48.12.65-.07.18-.2.76-.89.96-1.19.2-.3.41-.25.68-.15.28.1 1.76.83 2.06.98.3.15.5.22.57.35.08.12.08.71-.15 1.32z" />
            </svg>
            Inquire on WhatsApp
          </a>
        </div>
      </div>
    </section>
  );
}

function TrustStrip() {
  const ratings = [
    { label: "TripAdvisor", score: "4.0", outOf: "5", note: "#4 of 26 specialty lodging in Bhuj" },
    { label: "Google", score: "4.2", outOf: "5", note: "Based on guest reviews" },
    { label: "MakeMyTrip", score: "4.0", outOf: "5", note: "Certified great stay" },
    { label: "Location", score: "4.4", outOf: "5", note: "TripAdvisor travel rating" },
  ];
  return (
    <section className="relative -mt-10 z-20">
      <div className="container">
        <div className="border border-border bg-card shadow-md px-6 py-5 md:px-10 grid gap-4 grid-cols-2 md:grid-cols-4 items-center">
          {ratings.map((r) => (
            <div key={r.label} className="flex items-center gap-3">
              <div className="text-center">
                <div className="flex items-center justify-center gap-0.5 text-primary">
                  <Star className="h-4 w-4 fill-current" />
                  <span className="font-display text-lg font-semibold text-foreground">
                    {r.score}
                    <span className="text-xs text-muted-foreground">/{r.outOf}</span>
                  </span>
                </div>
                <div className="kicker mt-1 !text-[0.6rem]">{r.label}</div>
              </div>
              <div className="text-xs text-muted-foreground leading-snug border-l border-border pl-3">
                {r.note}
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

function WeatherBar() {
  const [weather, setWeather] = useState<{
    temp: number;
    condition: string;
    wind: number;
  }>({
    temp: 30,
    condition: "Partly sunny",
    wind: 13,
  });

  useEffect(() => {
    async function fetchWeather() {
      try {
        const res = await fetch(
          "https://api.open-meteo.com/v1/forecast?latitude=23.242&longitude=69.6669&current=temperature_2m,weather_code,wind_speed_10m&timezone=Asia%2FKolkata"
        );
        const data = await res.json();
        if (data && data.current) {
          const code = data.current.weather_code;
          let cond = "Clear sky";
          if (code === 1 || code === 2) cond = "Partly sunny";
          else if (code === 3) cond = "Overcast";
          else if (code >= 45 && code <= 48) cond = "Foggy";
          else if (code >= 51 && code <= 67) cond = "Light rain";
          else if (code >= 80 && code <= 82) cond = "Rain showers";
          else if (code >= 95) cond = "Thunderstorm";

          setWeather({
            temp: Math.round(data.current.temperature_2m),
            condition: cond,
            wind: Math.round(data.current.wind_speed_10m),
          });
        }
      } catch (e) {
        // graceful fallback
      }
    }
    fetchWeather();
    const interval = setInterval(fetchWeather, 15 * 60 * 1000);
    return () => clearInterval(interval);
  }, []);

  return (
    <div className="hidden md:flex items-center gap-2 text-xs text-foreground/70">
      <Sun className="h-4 w-4 text-primary" />
      <span className="font-semibold">Bhuj</span>
      <span className="font-display text-base font-semibold text-foreground">{weather.temp}°C</span>
      <span className="flex items-center gap-1">
        <Cloud className="h-4 w-4" />
        {weather.condition}
      </span>
      <span className="flex items-center gap-1">
        <Wind className="h-4 w-4" />
        {weather.wind} km/h
      </span>
    </div>
  );
}

function RannUtsav() {
  return (
    <section id="rann-utsav" className="bg-secondary py-20">
      <div className="container">
        <SectionHead title="Rann Utsav" tagline="Experience the magic of the White Rann Camp" />
        <div className="grid gap-6 md:grid-cols-2 items-center mt-12">
          <div className="mirror-frame bg-background">
            <img src={IMG.whiteRann} alt="White Rann" className="w-full object-cover img-lift aspect-[4/3]" />
          </div>
          <div className="flex flex-col justify-center gap-4 md:pl-8">
            <h3 className="font-display text-2xl font-semibold">White Rann Camp</h3>
            <p className="text-base md:text-lg leading-relaxed text-foreground/80">
              Join us for the vibrant Rann Utsav! We offer exclusive White Rann Camp details and packages. Sleep under the moonlight on the salt flats and experience local folk dances, music, and Kutchi handicrafts.
            </p>
            <div className="mt-4">
              <Link href="/rann-utsav-package"><a className="btn-explore">Book Rann Utsav Package</a></Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

function StatsBand() {
  const stats = [
    { value: "20+", label: "Years of hospitality" },
    { value: "26", label: "Cottages facing the lake" },
    { value: "10", label: "Green acres above the dam" },
    { value: "14", label: "Kilometres from Bhuj city" },
  ];
  return (
    <section className="bg-background py-14">
      <div className="container grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
        {stats.map((s) => (
          <div key={s.label}>
            <div className="font-display text-5xl font-semibold text-primary">{s.value}</div>
            <div className="kicker mt-2">{s.label}</div>
          </div>
        ))}
      </div>
    </section>
  );
}


function ExploreButton({ href }: { href: string }) {
  return (
    <div className="mt-10 text-center">
      <a href={href} className="btn-explore">
        Explore
      </a>
    </div>
  );
}

function SectionHead({
  title,
  tagline,
  sketch,
}: {
  title: string;
  tagline: string;
  sketch?: string;
}) {
  return (
    <div className="relative text-center max-w-3xl mx-auto mb-10">
      {sketch && (
        <img
          src={sketch}
          alt=""
          aria-hidden
          className="watermark-sketch w-56 md:w-72 mx-auto -mt-4"
        />
      )}
      <h2 className="section-title text-3xl md:text-4xl">{title}</h2>
      <p className="section-tagline mt-3 text-lg md:text-xl">{tagline}</p>
    </div>
  );
}

function TheResort() {
    return (
      <section id="resort" className="bg-secondary pb-20 pt-10">
        <div className="container">
          <SectionHead
            title="The Resort"
            tagline="A peaceful escape by the Rudramata reservoir"
          />
          <p className="max-w-3xl mx-auto text-base md:text-lg leading-relaxed text-center text-foreground/85 mb-8">
            If the path to the White Rann has led you this far, pull in before it does. Thirteen kilometres from Bhuj, on a quiet hill above the Rudramata reservoir, Kutch Safari Resort has welcomed travellers for over twenty years &mdash; seventeen traditional Kutchi cottages and nine deluxe cottages on ten green acres, every one facing the water. Each morning the sun paints the lake gold, and the memory of it stays forever.
          </p>

                    <div className="flex flex-wrap justify-center gap-4 md:gap-6 mb-14 max-w-4xl mx-auto">
            <div className="flex items-center gap-2.5 bg-[#f6f0e8] border border-[#e4d5c7] rounded-full px-5 py-2.5 shadow-sm">
              <Waves className="h-5 w-5 text-[var(--terracotta)]" />
              <span className="text-sm font-medium text-[#5a4a3a] tracking-wide uppercase">Lake-Facing Cottages</span>
            </div>
            <div className="flex items-center gap-2.5 bg-[#f6f0e8] border border-[#e4d5c7] rounded-full px-5 py-2.5 shadow-sm">
              <Heart className="h-5 w-5 text-[var(--terracotta)]" />
              <span className="text-sm font-medium text-[#5a4a3a] tracking-wide uppercase">Pet Friendly</span>
            </div>
            <div className="flex items-center gap-2.5 bg-[#f6f0e8] border border-[#e4d5c7] rounded-full px-5 py-2.5 shadow-sm">
              <MapPin className="h-5 w-5 text-[var(--terracotta)]" />
              <span className="text-sm font-medium text-[#5a4a3a] tracking-wide uppercase">Centrally Located</span>
            </div>
            <div className="flex items-center gap-2.5 bg-[#f6f0e8] border border-[#e4d5c7] rounded-full px-5 py-2.5 shadow-sm">
              <UtensilsCrossed className="h-5 w-5 text-[var(--terracotta)]" />
              <span className="text-sm font-medium text-[#5a4a3a] tracking-wide uppercase">Delicious Non-Veg Cuisine</span>
            </div>
          </div>
          <div className="grid gap-6 md:grid-cols-12">
            <div className="md:col-span-8 flex flex-col">
              <div className="mirror-frame bg-background flex-1">
                <img
                  src={IMG.cottagesLawn}
                  alt="Guests enjoying the lawn at Kutch Safari Resort"
                  className="h-full w-full object-cover img-lift"
                />
              </div>
            </div>
            <div className="md:col-span-4 flex flex-col gap-6">
              <img
                src={IMG.bougainvillea}
                alt="Lakeside view"
                className="w-full object-cover img-lift aspect-[4/3] flex-1"
              />
              <img
                src={IMG.sunrisePath}
                alt="Dawn path down to the reservoir"
                className="w-full object-cover img-lift aspect-[4/3] flex-1"
              />
            </div>
          </div>
        </div>
      </section>
    );
  }

  



function RoomTemplate({ title, exteriorTitle, interiorTitle, extImgs, intImgs, subtitle, description, onBookNow }: any) {
  const [lightbox, setLightbox] = useState<string | null>(null);

  return (
    <div className="mb-24 last:mb-12">
      <h2 className="text-center font-display text-3xl md:text-4xl uppercase tracking-[0.2em] text-foreground/80 mb-12">
        {title}
      </h2>
      
      <div className="container px-4 md:px-8 max-w-[90rem]">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 mb-12 items-center">
          {/* Photo Masonry - 8 cols */}
          <div className="lg:col-span-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="flex flex-col gap-6">
              <h3 className="text-center font-display text-xl uppercase tracking-widest text-foreground/70">{exteriorTitle}</h3>
              {extImgs.map((img: string, i: number) => (
                <img 
                  key={i} 
                  src={img} 
                  alt="Exterior" 
                  className="w-full h-80 object-cover rounded-md shadow-sm border border-border cursor-zoom-in hover:opacity-90 transition-opacity" 
                  onClick={() => setLightbox(img)}
                />
              ))}
            </div>
            <div className="flex flex-col gap-6">
              <h3 className="text-center font-display text-xl uppercase tracking-widest text-foreground/70">{interiorTitle}</h3>
              {intImgs.map((img: string, i: number) => (
                <img 
                  key={i} 
                  src={img} 
                  alt="Interior" 
                  className="w-full h-80 object-cover rounded-md shadow-sm border border-border cursor-zoom-in hover:opacity-90 transition-opacity" 
                  onClick={() => setLightbox(img)}
                />
              ))}
            </div>
          </div>

          {/* Text Content - 4 cols */}
          <div className="lg:col-span-4 flex flex-col justify-center">
            <h3 className="font-display text-3xl md:text-4xl italic text-foreground/90 mb-6 font-semibold tracking-wide">
              {subtitle}
            </h3>
            <p className="text-foreground/70 text-base md:text-lg leading-relaxed font-light mb-8">
              {description}
            </p>
            <a href="/contact" className="bg-[var(--terracotta)] text-white px-8 py-3 w-fit uppercase tracking-widest text-sm font-semibold hover:opacity-90 transition-opacity rounded-sm shadow-md">
              Book Now
            </a>
          </div>
        </div>
      </div>

      {/* Amenities Strip */}
      <div className="bg-[#f9f9f9] py-8 border-y border-black/5 mt-16">
        <div className="container px-4 mx-auto">
          <div className="grid grid-cols-3 md:grid-cols-6 gap-4 text-center divide-x divide-black/5">
            <div className="flex flex-col items-center gap-3">
              <Snowflake className="h-8 w-8 text-foreground/40 stroke-[1.5]" />
              <span className="text-xs uppercase tracking-wider text-foreground/60 font-medium">Air Conditioner</span>
            </div>

            <div className="flex flex-col items-center gap-3">
              <Wifi className="h-8 w-8 text-foreground/40 stroke-[1.5]" />
              <span className="text-xs uppercase tracking-wider text-foreground/60 font-medium">Wi-Fi</span>
            </div>
            <div className="flex flex-col items-center gap-3">
              <Coffee className="h-8 w-8 text-foreground/40 stroke-[1.5]" />
              <span className="text-xs uppercase tracking-wider text-foreground/60 font-medium">Hot Kettle</span>
            </div>
            <div className="flex flex-col items-center gap-3">
              <Tv className="h-8 w-8 text-foreground/40 stroke-[1.5]" />
              <span className="text-xs uppercase tracking-wider text-foreground/60 font-medium">Television</span>
            </div>
            <div className="flex flex-col items-center gap-3">
              <Lock className="h-8 w-8 text-foreground/40 stroke-[1.5]" />
              <span className="text-xs uppercase tracking-wider text-foreground/60 font-medium">In-Room Safe</span>
            </div>

            <div className="flex flex-col items-center gap-3">
              <Wind className="h-8 w-8 text-foreground/40 stroke-[1.5]" />
              <span className="text-xs uppercase tracking-wider text-foreground/60 font-medium">Hair-Dryer</span>
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
          Experience Kutchi hospitality in our traditional AC Cottages and Deluxe AC Cottages. Spread across the hillside, each is beautifully decorated with the mirror work and mud appliqué of local artisans. With sit-outs and verandas facing the lake, nature is never out of sight. <em>We call it Rustic Luxury.</em>
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


function Experiences() {
  return (
    <section id="experiences" className="bg-secondary py-20">
      <div className="container">
        <SectionHead title="Experiences" tagline="Immerse yourself in Kutchi culture by the lake" />
        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4 mt-12">
          <div className="flex flex-col gap-3">
            <div className="mirror-frame bg-background">
              <img src={IMG.nightCottages} alt="Candle Light Dinner" className="w-full object-cover img-lift aspect-[4/3]" />
            </div>
            <h4 className="font-semibold text-center mt-2">Candle Light Dinner</h4>
          </div>
          <div className="flex flex-col gap-3">
            <div className="mirror-frame bg-background">
              <img src={IMG.sunrisePath} alt="Sunrise Breakfast by lake" className="w-full object-cover img-lift aspect-[4/3]" />
            </div>
            <h4 className="font-semibold text-center mt-2">Sunrise Breakfast</h4>
          </div>
          <div className="flex flex-col gap-3">
            <div className="mirror-frame bg-background">
              <img src={IMG.sunsetTree} alt="Safari" className="w-full object-cover img-lift aspect-[4/3]" />
            </div>
            <h4 className="font-semibold text-center mt-2">Safari</h4>
          </div>
          <div className="flex flex-col gap-3">
            <div className="mirror-frame bg-background">
              <img src={IMG.campfire} alt="Gala Dinner" className="w-full object-cover img-lift aspect-[4/3]" />
            </div>
            <h4 className="font-semibold text-center mt-2">Gala Dinner</h4>
          </div>
        </div>
      </div>
    </section>
  );
}

function Packages() {
  return (
    <section id="packages" className="bg-background py-20">
      <div className="container">
        <SectionHead title="Packages" tagline="Curated stays for the perfect getaway" />
        <div className="max-w-3xl mx-auto mt-8 p-8 border border-border bg-secondary/30 rounded-lg text-center">
          <p className="text-base md:text-lg leading-relaxed text-foreground/80 mb-6">
            We offer a variety of tailored packages designed to give you the complete Kutch experience. From romantic getaways to adventurous family safaris, our packages are currently being updated for the new season.
          </p>
          <a href="/contact" className="btn-explore">Enquire for Packages</a>
        </div>
      </div>
    </section>
  );
}

const DESTINATIONS = [
  {
    title: "Dholavira",
    slug: "dholavira",
    subtitle: "UNESCO World Heritage Site",
    tag: "Harappan Civilization",
    desc: "Discover the 4,500-year-old Indus Valley metropolis featuring monumental stone citadels, massive water reservoirs, and ancient urban planning, reached via the spectacular salt expanse.",
    img: "/assets/images/new/pro-dholavira.jpg",
    alt: "Ancient ruins of Dholavira Harappan civilization",
  },
  {
    title: "Road to Heaven",
    slug: "road-to-heaven",
    subtitle: "The Iconic Salt Highway",
    tag: "Scenic Drive",
    desc: "A surreal, world-renowned 30-km stretch of highway slicing straight through the shimmering turquoise waters and boundless white salt desert towards Khadir Bet.",
    img: "/assets/images/new/kutch-destination-road_2.jpg",
    alt: "Road to Heaven highway crossing the Great Rann of Kutch",
  },
  {
    title: "The Great White Rann",
    slug: "the-great-white-rann",
    subtitle: "Dhordo Salt Desert",
    tag: "Natural Wonder",
    desc: "Vast expanses of crystalline white salt desert that glow silver under the moonlight, offering camel safaris, vibrant folk performances, and mesmerizing desert sunsets.",
    img: "/assets/images/white-rann-of-kutch-cc-by-sa.jpg",
    alt: "Vast white salt desert of the Great Rann of Kutch",
  },
  {
    title: "Mandvi Beach & Palace",
    slug: "mandvi-beach-palace",
    subtitle: "Coastal Gateway & Royal Heritage",
    tag: "Arabian Sea Coast",
    desc: "Golden sandy shores along the Arabian Sea, iconic windmills, camel rides along the surf, the historic 400-year-old shipbuilding docks, and the grand Vijay Vilas Palace.",
    img: "/assets/images/new/kutch-destination-mandvi-beach.jpg",
    alt: "Mandvi beach with windmills on the Arabian Sea coast",
  },
  {
    title: "Artisan Villages (Bhirandiyara & Nirona)",
    slug: "artisan-villages",
    subtitle: "Living Crafts & Traditional Bhungas",
    tag: "Cultural Craft Trail",
    desc: "Venture into traditional Kutchi villages to experience authentic Rogan art, copper bell making, Ajrakh block printing, intricate Rabari embroidery, and famous local Mawa sweets.",
    img: "/assets/images/new/kutch-handicrafts-block-demo.jpg",
    alt: "Traditional Kutchi Rogan art and village handicrafts",
  },
  {
    title: "Kala Dungar (Black Hill)",
    slug: "kala-dungar",
    subtitle: "Highest Peak in Kutch",
    tag: "Panoramic Summit",
    desc: "Rising 458 meters above sea level, Kala Dungar offers a panoramic 360-degree overlook of the entire White Rann and houses the sacred 400-year-old Dattatreya Temple.",
    img: "/assets/images/new/pro-kala_dungar.jpg",
    alt: "Rabari camel caravan near Kala Dungar",
  },
];

function BeyondBhuj() {
  return (
    <section id="explore" className="bg-background py-20">
      <div className="container">
        <SectionHead
          title="Beyond Bhuj"
          tagline="Discover the wonders, ancient ruins, and heritage of Kutch"
        />
        <p className="max-w-3xl mx-auto text-base md:text-lg leading-relaxed text-center text-foreground/85 mb-14">
          Centrally located just 13 km from Bhuj, Kutch Safari Resort serves as the ideal launchpad to explore the desert, royal palaces, coastal shores, and ancient Harappan ruins across Kutch.
        </p>
        <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
          {DESTINATIONS.map((dest, i) => (
            <div
              key={i}
              className="mirror-frame bg-background flex flex-col justify-between group hover:shadow-xl transition-all duration-300 rounded-sm"
            >
              <div className="flex flex-col gap-3">
                <div className="relative overflow-hidden rounded-sm">
                  <img
                    src={dest.img}
                    alt={dest.alt}
                    className="w-full object-cover img-lift aspect-[16/10] group-hover:scale-105 transition-transform duration-500"
                  />
                  <span className="absolute top-3 right-3 bg-black/75 backdrop-blur-sm text-white text-[11px] font-medium tracking-wider uppercase px-2.5 py-1 rounded-sm shadow">
                    {dest.tag}
                  </span>
                </div>
                <div className="px-2 pt-2">
                  <span className="text-xs uppercase tracking-widest text-primary font-semibold block mb-1">
                    {dest.subtitle}
                  </span>
                  <h4 className="font-display text-xl font-semibold text-foreground tracking-wide">
                    {dest.title}
                  </h4>
                  <p className="text-sm leading-relaxed text-foreground/75 mt-2 font-light">
                    {dest.desc}
                  </p>
                </div>
              </div>
              <div className="px-2 pt-4 pb-2 border-t border-border/40 mt-4 flex justify-center">
                <Link href={"/destination/" + (dest.slug || "")}>
                  <a className="bg-[var(--terracotta)] text-white px-8 py-2.5 uppercase tracking-widest text-xs font-semibold hover:opacity-90 transition-opacity shadow-sm inline-block">
                    Explore
                  </a>
                </Link>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

function Sustainability() {
  return (
    <section id="sustainability" className="bg-background py-20">
      <div className="container">
        <SectionHead title="Sustainability" tagline="Our commitment to the environment" />
        <div className="grid gap-6 md:grid-cols-2 items-center">
          <div>
            <p className="text-base md:text-lg leading-relaxed text-foreground/85">
              Built with locally sourced mud and wood, our cottages maintain natural thermal regulation. We actively support local Kutchi artisans, displaying their intricate mirror work and embroidery throughout the property. 
            </p>
          </div>
          <div className="mirror-frame bg-background">
            <img src={IMG.room1} alt="Kutch Embroidery" className="w-full object-cover img-lift aspect-[4/3]" />
          </div>
        </div>
      </div>
    </section>
  );
}



function Footer() {
  return (
    <footer className="bg-zinc-900 text-white/80 py-12 text-base">
      <div className="container grid gap-8 md:grid-cols-4">
        <div>
          <LogoBlock light />
          <p className="mt-4 text-xs">2026 Kutch Safari Resort.</p>
        </div>
        <div>
          <h4 className="text-white/90 text-base font-semibold uppercase tracking-[0.12em] mb-4">The Resort</h4>
          <ul className="space-y-2.5 text-sm uppercase tracking-[0.1em]">
            <li><a href="#resort" className="hover:text-white transition-colors">The Resort</a></li>
            <li><a href="/#cottages" className="hover:text-white transition-colors">Stays</a></li>
            <li><a href="/#experiences" className="hover:text-white transition-colors">Experiences</a></li>
            <li><a href="#packages" className="hover:text-white transition-colors">Packages</a></li>
          </ul>
        </div>
        <div>
          <h4 className="text-white/90 text-base font-semibold uppercase tracking-[0.12em] mb-4">Beyond Bhuj</h4>
          <ul className="space-y-2.5 text-sm uppercase tracking-[0.1em]">
            <li><a href="#explore" className="hover:text-white transition-colors">Beyond Bhuj</a></li>
            <li><a href="#rann-utsav" className="hover:text-white transition-colors">Rann Utsav</a></li>
            <li><a href="#sustainability" className="hover:text-white transition-colors">Sustainability</a></li>
          </ul>
        </div>
      </div>
    </footer>
  );
}


function GuestReviews() {
  return (
    <section id="reviews" className="bg-secondary py-20">
      <div className="container">
        <SectionHead title="Guest Experiences" tagline="See what our guests have to say" />
        <div className="mt-12 grid gap-8 lg:grid-cols-2 items-center">
          <div>
            <video controls className="w-full rounded-xl shadow-lg aspect-video object-cover bg-black" poster="/assets/images/new/guests-20180326_174201.jpg">
              <source src="/assets/images/new/guest-feedback-video-guest-feedback-mr-parekh.mov" type="video/mp4" />
              Your browser does not support the video tag.
            </video>
            <p className="mt-3 text-sm text-center italic text-muted-foreground">Hear directly from Mr. Parekh about his stay</p>
          </div>
          <div className="flex flex-col gap-6">
             <div className="bg-background p-8 rounded-xl border border-border shadow-sm">
                <div className="flex gap-1 text-[var(--terracotta)] mb-4">
                  <Star className="h-5 w-5 fill-current" />
                  <Star className="h-5 w-5 fill-current" />
                  <Star className="h-5 w-5 fill-current" />
                  <Star className="h-5 w-5 fill-current" />
                  <Star className="h-5 w-5 fill-current" />
                </div>
                <p className="text-lg italic text-foreground/85 leading-relaxed">
                  "A truly wonderful and peaceful experience. The authentic Kutchi architecture combined with the serene lake view makes this resort a hidden gem. The hospitality here is unmatched."
                </p>
                <p className="mt-4 font-semibold font-display text-[var(--terracotta)]">- Satisfied Guests</p>
             </div>
             <div className="grid grid-cols-3 gap-3">
               <img src="/assets/images/new/guests-20180326_174201.jpg" alt="Guests having a great time" className="w-full h-32 object-cover rounded-lg" />
               <img src="/assets/images/new/guests-img-20180402-wa0053.jpg" alt="Guest memories" className="w-full h-32 object-cover rounded-lg" />
               <img src="/assets/images/new/guests-img_20190109_080807.jpg" alt="Guest experiences" className="w-full h-32 object-cover rounded-lg" />
             </div>
          </div>
        </div>
      </div>
    </section>
  );
}

function FloatingWidgets() {
  return (
    <>
      <a href="/contact" aria-label="Enquire now" className="hidden">Enquire Now</a>
      <div className="fixed bottom-5 right-5 z-50 flex flex-col gap-3">
        <a href="https://wa.me/919925238599" target="_blank" rel="noreferrer" className="flex h-11 w-11 items-center justify-center rounded-full bg-[var(--terracotta)] text-white shadow-lg hover:opacity-90 transition-opacity">
          <Phone className="h-5 w-5" />
        </a>
      </div>
    </>
  );
}

export default function Rooms() {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <div className="pt-24 bg-background min-h-screen">
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
      <FloatingWidgets />
    </div>
  );
}