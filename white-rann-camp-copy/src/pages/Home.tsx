import { Link } from "wouter";
import { Phone, Mail, MapPin, CheckCircle2, ArrowRight, ArrowUpRight } from "lucide-react";
import Navbar from "@/components/Navbar";
import Footer from "@/components/Footer";
import { CreditTag } from "@/components/Figure";
import EnquiryForm from "@/components/EnquiryForm";
import { CAMP, CAMP_PHOTOS, EXPLORE, RANN, SISTER_PHOTO, TENTS, TOTAL_TENTS, rupees } from "@/data";

/* Same sections, in the same order and style, as the Kutch Safari Resort home page
   (frontend/src/pages/Home.tsx), with the camp's facts and the White Rann palette. */

const HERO_SHADOW = { textShadow: "0 2px 18px rgba(15,43,71,0.55)" };

function FactStrip() {
  const FACTS = [CAMP.fromTentCity, "Dinner, breakfast & hi-tea included", CAMP.seasonShort];
  return (
    <div className="bg-rann-50 text-zinc-900 py-3 border-y border-rann-200 relative z-20">
      <div className="container mx-auto px-4">
        <div className="flex flex-wrap items-center justify-center gap-x-12 gap-y-4 text-xs md:text-sm font-medium tracking-wide">
          {FACTS.map((f, i) => (
            <div key={f} className="flex items-center gap-12">
              {i > 0 && <div className="hidden md:block w-px h-4 bg-black/10" />}
              <div className="flex items-center gap-2">
                <CheckCircle2 className="w-4 h-4 text-rann-600" />
                <span>{f}</span>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

function StatsBand() {
  const STATS = [
    { n: String(TOTAL_TENTS), label: "Swiss Tents" },
    { n: "800 m", label: "From the Rann Utsav Tent City" },
    { n: "3 min", label: "To the White Rann Entry" },
  ];
  return (
    <section className="bg-rann-50 py-16 border-y border-rann-200">
      <div className="container mx-auto px-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 text-center md:divide-x divide-rann-200">
          {STATS.map((s) => (
            <div key={s.label} className="flex flex-col gap-2">
              <span className="text-4xl md:text-5xl font-display text-rann-700 font-bold">{s.n}</span>
              <span className="text-sm uppercase tracking-widest text-zinc-600 font-semibold">{s.label}</span>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

function AmenitiesGrid() {
  const AMENITIES = [
    { title: "Dinner, Breakfast & Hi-Tea", desc: "Included in every tent rate, with two bottles of mineral water. Kutchi food and multi-cuisine." },
    { title: "Attached Washrooms", desc: "A western washroom with hot and cold water in every tent." },
    { title: "Campfire Evenings", desc: "A campfire at the camp once the sun has gone down over the Rann." },
    { title: "Cultural Folk Music", desc: "A cultural music programme at the camp in the evening." },
    { title: "Travel Assistance", desc: "Airport transfers and guided sightseeing tours." },
    { title: "Rann Utsav Tours", desc: "Tours from Bhuj with a night by the White Rann: Banni villages, Dholavira, the Road to Heaven." },
  ];
  return (
    <section className="py-24 bg-rann-50">
      <div className="container mx-auto px-6">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <p className="text-sm font-semibold tracking-[0.2em] uppercase text-rann-700 mb-4">At the Camp</p>
          <h2 className="text-4xl md:text-5xl font-display text-zinc-900 font-bold mb-6">What the Camp Offers</h2>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
          {AMENITIES.map((a) => (
            <div key={a.title} className="flex flex-col gap-4">
              <div className="w-12 h-12 flex items-center justify-center border border-rann-600 text-rann-600 rounded-full">
                <CheckCircle2 className="w-5 h-5" />
              </div>
              <h3 className="text-lg font-bold text-zinc-900">{a.title}</h3>
              <p className="text-zinc-600 text-sm leading-relaxed">{a.desc}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

function ContactSection() {
  const ITEMS = [
    { icon: Phone, label: "Reservations & WhatsApp", value: CAMP.phone, href: CAMP.phoneHref },
    { icon: Phone, label: "Also call", value: CAMP.phoneAlt, href: CAMP.phoneAltHref },
    { icon: Mail, label: "Email Address", value: CAMP.email, href: `mailto:${CAMP.email}` },
    { icon: MapPin, label: "Find Us", value: `${CAMP.address} · Get directions`, href: CAMP.mapsUrl },
  ];
  return (
    <section id="contact" className="relative bg-rann-50 py-24 border-t border-rann-200 scroll-mt-20">
      <div className="container mx-auto px-6">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <p className="text-sm font-semibold tracking-[0.2em] uppercase text-rann-700 mb-4">Plan Your Stay</p>
          <h2 className="text-4xl md:text-5xl font-display text-zinc-900 font-bold mb-6">Get in Touch</h2>
        </div>
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-20 max-w-7xl mx-auto">
          <div className="lg:col-span-5 flex flex-col gap-8 lg:pr-8">
            {ITEMS.map(({ icon: Icon, label, value, href }) => (
              <div key={label} className="flex gap-4 items-start">
                <div className="w-12 h-12 bg-white rounded-full flex items-center justify-center border border-rann-200 shadow-sm text-rann-700 shrink-0">
                  <Icon className="w-5 h-5" />
                </div>
                <div>
                  <p className="text-sm uppercase tracking-widest text-zinc-500 font-medium mb-1">{label}</p>
                  <a href={href} {...(href.startsWith("http") ? { target: "_blank", rel: "noreferrer" } : {})} className="text-lg font-medium text-zinc-900 break-words hover:text-rann-700">{value}</a>
                </div>
              </div>
            ))}
          </div>
          {/* The enquiry goes to the desk on WhatsApp (or email); nothing is faked as "sent". */}
          <div className="lg:col-span-7">
            <div className="bg-white border border-rann-200 p-8 shadow-sm rounded-sm">
              <EnquiryForm />
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

export default function Home() {
  return (
    <div className="min-h-screen bg-rann-50 font-sans text-zinc-900">
      <Navbar />

      {/* Hero: the White Rann itself */}
      <section className="relative h-[85vh] min-h-[600px] flex items-center justify-center overflow-hidden">
        <img src={RANN.mesmerizing.src} alt={RANN.mesmerizing.alt} className="absolute inset-0 w-full h-full object-cover z-0" />
        <div className="absolute inset-0 bg-rann-900/30 z-10" />
        <div className="relative z-20 text-center text-white px-4 flex flex-col items-center">
          <p className="uppercase tracking-[0.3em] text-sm md:text-base font-semibold mb-6 text-white/90" style={HERO_SHADOW}>
            Dhordo · Rann of Kutch
          </p>
          <h1 className="font-display text-5xl md:text-7xl font-bold mb-8 max-w-4xl leading-tight text-white" style={HERO_SHADOW}>
            Three Minutes from the White Rann
          </h1>
          <div className="flex flex-col sm:flex-row gap-4 mt-4">
            <Link href="/enquire" className="bg-rann-700 text-white px-8 py-4 uppercase tracking-widest text-sm font-semibold hover:bg-rann-800 transition-colors rounded-sm shadow-md">
              Enquire Now
            </Link>
            <a href="#explore" className="bg-white/10 backdrop-blur-md border border-white/40 text-white px-8 py-4 uppercase tracking-widest text-sm font-semibold hover:bg-white hover:text-rann-900 transition-colors rounded-sm shadow-md">
              Explore the Rann
            </a>
          </div>
        </div>
        <CreditTag photo={RANN.mesmerizing} />
      </section>

      <FactStrip />

      {/* Welcome */}
      <section className="py-24 bg-rann-50 text-zinc-900">
        <div className="container mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div className="flex flex-col gap-4">
              <img src={CAMP_PHOTOS.entranceNight.src} alt={CAMP_PHOTOS.entranceNight.alt} className="w-full h-[42vh] min-h-[220px] object-cover rounded-sm shadow-xl" />
              <img src={CAMP_PHOTOS.campNight.src} alt={CAMP_PHOTOS.campNight.alt} className="w-full h-[42vh] min-h-[220px] object-cover rounded-sm shadow-xl" />
            </div>
            <div>
              <p className="text-sm font-semibold tracking-[0.2em] uppercase text-rann-700 mb-4">Welcome to White Rann Camp</p>
              <h2 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6 leading-tight">Your Base by the White Rann and Rann Utsav</h2>
              <div className="space-y-4 text-zinc-900/80 text-lg leading-relaxed">
                <p>
                  White Rann Camp is a private camp at Dhordo, just 800 metres from the Rann Utsav Tent City: three minutes from Rann Utsav and from the entry to the White Rann of Kutch.
                </p>
                <p>
                  Twenty well-furnished Swiss tents, six Deluxe Air-Cool and fourteen Non-AC, each with twin beds and an attached western washroom with hot and cold water.
                  Our restaurant serves Kutchi food and multi-cuisine, and evenings end around the campfire with a cultural folk music programme.
                </p>
                <p>
                  We offer airport transfers and guided sightseeing tours, and our sister property, {CAMP.sisterName} near Bhuj, is the place to stay on the way.
                  The camp is open for the Rann season, {CAMP.season}.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <StatsBand />

      {/* The Stay */}
      <section className="py-24">
        <div className="container mx-auto px-6">
          <div className="text-center max-w-2xl mx-auto mb-16">
            <p className="text-sm font-semibold tracking-[0.2em] uppercase text-rann-700 mb-4">Accommodation</p>
            <h2 className="text-4xl md:text-5xl font-display text-zinc-900 font-bold mb-6">Swiss Tents by the White Rann</h2>
            <p className="text-zinc-600 text-lg">Two kinds of tent, {TOTAL_TENTS} in all, every one with an attached washroom.</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-5xl mx-auto">
            {TENTS.map((t) => (
              <div key={t.slug} className="group border border-rann-200 rounded-sm overflow-hidden flex flex-col">
                <div className="relative h-72 overflow-hidden">
                  <div className="absolute top-4 left-4 bg-black/60 text-white text-xs uppercase tracking-widest px-3 py-1 z-10 backdrop-blur-md rounded-sm">{t.count} Tents</div>
                  <img src={t.photos[0].src} alt={t.photos[0].alt} className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                </div>
                <div className="p-8 flex flex-col flex-grow bg-white">
                  <h3 className="text-2xl font-display font-bold text-zinc-900 mb-4">{t.name}</h3>
                  <p className="text-zinc-600 mb-6 flex-grow">{t.summary}</p>
                  <div className="flex flex-wrap gap-2 mb-6">
                    {t.features.slice(0, 3).map((f) => <span key={f} className="text-xs bg-rann-100 text-zinc-600 px-3 py-1 rounded-sm">{f}</span>)}
                  </div>
                  <p className="text-sm text-zinc-600 mb-6">
                    From <span className="font-semibold text-zinc-900">{rupees(t.price)} + GST</span> a night for two, with dinner, breakfast and hi-tea.
                  </p>
                  <Link href={`/enquire?stay=${encodeURIComponent(t.name)}`} className="text-center border border-rann-700 text-rann-700 py-3 uppercase tracking-widest text-sm font-semibold hover:bg-rann-700 hover:text-white transition-colors rounded-sm block">
                    Enquire Now
                  </Link>
                </div>
              </div>
            ))}
          </div>

          <div className="mt-12 text-center">
            <Link href="/tents" className="inline-flex items-center gap-2 text-rann-700 uppercase tracking-widest text-sm font-semibold hover:text-black transition-colors">
              All Tents &amp; Tariff <ArrowRight className="w-4 h-4" />
            </Link>
          </div>
        </div>
      </section>

      {/* Sister Property */}
      <section className="bg-rann-50 text-zinc-900 py-24">
        <div className="container mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <img src={SISTER_PHOTO.src} alt={SISTER_PHOTO.alt} className="w-full h-[400px] object-cover rounded-sm" />
            <div>
              <p className="text-sm font-semibold tracking-[0.2em] uppercase text-rann-700 mb-4">Our Sister Property</p>
              <h2 className="text-4xl md:text-5xl font-display font-bold mb-6">{CAMP.sisterName}, Bhuj</h2>
              <div className="space-y-4 text-zinc-900/80 text-lg leading-relaxed">
                <p>
                  Twenty lake-view cottages on a rise overlooking the Rudramata Dam, 15 kilometres from Bhuj on the Khavda road that leads to Dhordo and the White Rann.
                  A lake-view restaurant, The Banni, and more than three decades of hosting.
                </p>
              </div>
              <div className="flex flex-col sm:flex-row gap-4 mt-8">
                <a href={CAMP.sisterUrl} target="_blank" rel="noreferrer" className="inline-flex items-center justify-center gap-2 bg-rann-700 text-white px-8 py-3 uppercase tracking-widest text-sm font-semibold text-center hover:bg-rann-800 transition-colors rounded-sm">
                  Visit {CAMP.sisterName} <ArrowUpRight className="w-4 h-4" />
                </a>
                <Link href="/tours" className="border border-rann-700 text-rann-700 px-8 py-3 uppercase tracking-widest text-sm font-semibold text-center hover:bg-rann-700 hover:text-white transition-colors rounded-sm">
                  Rann Utsav Tours
                </Link>
              </div>
            </div>
          </div>
        </div>
      </section>

      <AmenitiesGrid />

      {/* Explore */}
      <section id="explore" className="py-24 scroll-mt-20">
        <div className="container mx-auto px-6">
          <div className="text-center max-w-2xl mx-auto mb-16">
            <p className="text-sm font-semibold tracking-[0.2em] uppercase text-rann-700 mb-4">Explore</p>
            <h2 className="text-4xl md:text-5xl font-display text-zinc-900 font-bold mb-6">The Rann, from Our Doorstep</h2>
            <p className="text-zinc-600 text-lg">The White Rann is three minutes away. Our tours take in the rest of Kutch.</p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {EXPLORE.map((exp) => (
              // The photo and the text each link to the guide; the photo credit links to its source (links can't nest).
              <article key={exp.title} className="group">
                <div className="relative h-64 overflow-hidden rounded-sm mb-4">
                  <a href={exp.url} target="_blank" rel="noreferrer" tabIndex={-1} aria-hidden="true">
                    <img src={exp.photo.src} alt="" loading="lazy" className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                  </a>
                  <CreditTag photo={exp.photo} />
                </div>
                <a href={exp.url} target="_blank" rel="noreferrer" className="block">
                  <h3 className="text-xl font-bold text-zinc-900 mb-2 group-hover:text-rann-700 transition-colors">{exp.title}</h3>
                  <p className="text-zinc-600 text-sm mb-3">{exp.desc}</p>
                  <span className="text-rann-700 uppercase tracking-widest text-xs font-bold flex items-center gap-1">
                    Discover <ArrowUpRight className="w-3 h-3" />
                  </span>
                </a>
              </article>
            ))}
          </div>
        </div>
      </section>

      <ContactSection />
      <Footer />
    </div>
  );
}
