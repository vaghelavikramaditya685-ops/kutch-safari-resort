import { Link } from "wouter";
import { useEffect, useState } from "react";
import { MapPin, Phone, Mail, Clock, Send, CheckCircle2, User, PhoneCall, Sun, Cloud, Wind, ArrowRight } from "lucide-react";
import { toast } from "sonner";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";
import { bookingUrl, statusUrl } from "@/lib/booking";

function TrustStrip() {
  return (
    <div className="bg-[#f8f5e2] text-zinc-900 py-3 border-y border-white/10 relative z-20">
      <div className="container mx-auto px-4">
        <div className="flex flex-wrap items-center justify-center gap-x-12 gap-y-4 text-xs md:text-sm font-medium tracking-wide">
          <div className="flex items-center gap-2">
            <span className="text-[var(--terracotta)]">★★★★★</span>
            <span>4.6/5 on Google Reviews</span>
          </div>
          <div className="hidden md:block w-px h-4 bg-black/10"></div>
          <div className="flex items-center gap-2">
            <span className="text-[var(--terracotta)]">★★★★★</span>
            <span>4.5/5 on TripAdvisor</span>
          </div>
          <div className="hidden md:block w-px h-4 bg-black/10"></div>
          <div className="flex items-center gap-2">
            <span className="text-[var(--terracotta)]">★★★★★</span>
            <span>MakeMyTrip Assured</span>
          </div>
        </div>
      </div>
    </div>
  );
}

function StatsBand() {
  return (
    <section className="bg-[#f8f5e2] py-16 border-y border-[#e4d5c7]">
      <div className="container mx-auto px-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 text-center divide-x divide-[#e4d5c7]">
          <div className="flex flex-col gap-2">
            <span className="text-4xl md:text-5xl font-display text-[var(--terracotta)] font-bold">20</span>
            <span className="text-sm uppercase tracking-widest text-zinc-600 font-semibold">Lake View Cottages</span>
          </div>
          <div className="flex flex-col gap-2">
            <span className="text-4xl md:text-5xl font-display text-[var(--terracotta)] font-bold">35+</span>
            <span className="text-sm uppercase tracking-widest text-zinc-600 font-semibold">Years of Hosting</span>
          </div>
          <div className="flex flex-col gap-2">
            <span className="text-4xl md:text-5xl font-display text-[var(--terracotta)] font-bold">15</span>
            <span className="text-sm uppercase tracking-widest text-zinc-600 font-semibold">Km from Bhuj</span>
          </div>
        </div>
      </div>
    </section>
  );
}

function AmenitiesGrid() {
  const AMENITIES = [
    { title: "Swimming Pool", desc: "An open-air pool for beating the Kutch afternoon." },
    { title: "The Banni Restaurant", desc: "Multi-cuisine, vegetarian and non-vegetarian, by the lake." },
    { title: "Travel Desk & Experiences", desc: "Taxis, guides, airport and station transfers, plus curated local experiences." },
    { title: "Free Wi-Fi", desc: "Throughout the cottages and the public areas." },
    { title: "Open Garden Lawn", desc: "Conferences and private celebrations for up to 300." },
    { title: "Room Service", desc: "In-room dining directly to your cottage." },
  ];

  return (
    <section className="py-24 bg-[#f8f5e2]">
      <div className="container mx-auto px-6">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <p className="text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4">Why Guests Return</p>
          <h2 className="text-4xl md:text-5xl font-display text-zinc-900 font-bold mb-6">What We Do Well</h2>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
          {AMENITIES.map((a, i) => (
            <div key={i} className="flex flex-col gap-4">
              <div className="w-12 h-12 flex items-center justify-center border border-[var(--terracotta)] text-[var(--terracotta)] rounded-full">
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
  const [formData, setFormData] = useState({ name: '', email: '', phone: '', dates: '', message: '' });
  const [submitting, setSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = (e: any) => {
    e.preventDefault();
    setSubmitting(true);
    setTimeout(() => {
      setSubmitting(false);
      setSubmitted(true);
      toast.success("Message sent successfully!");
      setFormData({ name: '', email: '', phone: '', dates: '', message: '' });
      setTimeout(() => setSubmitted(false), 5000);
    }, 1500);
  };

  return (
    <section id="contact" className="relative bg-[#f8f5e2] py-24 border-t border-[#e4d5c7]">
      <div className="container mx-auto px-6">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <p className="text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4">Plan Your Stay</p>
          <h2 className="text-4xl md:text-5xl font-display text-zinc-900 font-bold mb-6">Get in Touch</h2>
        </div>
        
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-20 max-w-7xl mx-auto">
          {/* Left Column */}
          <div className="lg:col-span-5 flex flex-col gap-10 lg:pr-8">
            <div className="space-y-8">
              <div className="flex gap-4 items-start group">
                <div className="w-12 h-12 bg-white rounded-full flex items-center justify-center border border-[#e4d5c7] shadow-sm text-[var(--terracotta)] shrink-0">
                  <Phone className="w-5 h-5" />
                </div>
                <div>
                  <p className="text-sm uppercase tracking-widest text-zinc-500 font-medium mb-1">Reservations & WhatsApp</p>
                  <a href="https://wa.me/919925238599" className="text-lg font-medium text-zinc-900 block">+91 99252 38599</a>
                </div>
              </div>
              <div className="flex gap-4 items-start group">
                <div className="w-12 h-12 bg-white rounded-full flex items-center justify-center border border-[#e4d5c7] shadow-sm text-[var(--terracotta)] shrink-0">
                  <Mail className="w-5 h-5" />
                </div>
                <div>
                  <p className="text-sm uppercase tracking-widest text-zinc-500 font-medium mb-1">Email Address</p>
                  <a href="mailto:kutchsafaribhuj@yahoo.com" className="text-lg font-medium text-zinc-900">kutchsafaribhuj@yahoo.com</a>
                </div>
              </div>
            </div>
          </div>

          {/* Right Column */}
          <div className="lg:col-span-7">
            <div className="bg-white border border-[#e4d5c7] p-8 shadow-sm rounded-sm">
              {submitted ? (
                <div className="py-12 text-center">
                  <CheckCircle2 className="w-12 h-12 text-green-500 mx-auto mb-4" />
                  <h4 className="text-2xl font-display font-semibold mb-2">Message Sent!</h4>
                </div>
              ) : (
                <form onSubmit={handleSubmit} className="space-y-6">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <input required type="text" placeholder="Full Name" className="w-full p-4 bg-[#f8f5e2] border border-[#e4d5c7] rounded-sm text-sm" onChange={(e) => setFormData({...formData, name: e.target.value})} />
                    <input required type="email" placeholder="Email Address" className="w-full p-4 bg-[#f8f5e2] border border-[#e4d5c7] rounded-sm text-sm" onChange={(e) => setFormData({...formData, email: e.target.value})} />
                  </div>
                  <input required type="tel" placeholder="Phone Number" className="w-full p-4 bg-[#f8f5e2] border border-[#e4d5c7] rounded-sm text-sm" onChange={(e) => setFormData({...formData, phone: e.target.value})} />
                  <textarea required rows={4} placeholder="Your Message..." className="w-full p-4 bg-[#f8f5e2] border border-[#e4d5c7] rounded-sm text-sm resize-none" onChange={(e) => setFormData({...formData, message: e.target.value})}></textarea>
                  <button type="submit" disabled={submitting} className="w-full bg-[var(--terracotta)] text-white py-4 uppercase tracking-[0.15em] text-sm font-semibold rounded-sm">
                    {submitting ? 'Sending...' : 'Submit Inquiry'}
                  </button>
                </form>
              )}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

export default function Home() {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  return (
    <div className="min-h-screen bg-[#f8f5e2] font-sans text-zinc-900">
      <Navbar />

      {/* Hero Video */}
      <section className="relative h-[85vh] min-h-[600px] flex items-center justify-center overflow-hidden">
        <video 
          autoPlay 
          muted 
          loop 
          playsInline 
          className="absolute inset-0 w-full h-full object-cover z-0"
        >
          <source src="/assets/images/new/kutch-safari-resort-website-hero.mp4" type="video/mp4" />
        </video>
        <div className="absolute inset-0 bg-black/40 z-10"></div>
        <div className="relative z-20 text-center text-white px-4 flex flex-col items-center">
          <p className="uppercase tracking-[0.3em] text-sm md:text-base font-semibold mb-6 animate-fade-in text-white/90">
            Bhuj · Rann of Kutch
          </p>
          <h1 className="font-display text-5xl md:text-7xl font-bold mb-8 max-w-4xl leading-tight text-white">
            Where the Lake Meets the Desert
          </h1>
          <div className="flex flex-col sm:flex-row gap-4 mt-4">
            <a href={bookingUrl()} className="bg-[var(--terracotta)] text-white px-8 py-4 uppercase tracking-widest text-sm font-semibold hover:bg-[#b04838] transition-colors rounded-sm shadow-md">
              Check Availability & Book
            </a>
            <Link href="/experiences" className="bg-white/10 backdrop-blur-md border border-white/30 text-white px-8 py-4 uppercase tracking-widest text-sm font-semibold hover:bg-white hover:text-black transition-colors rounded-sm shadow-md">
              Explore Kutch
            </Link>
          </div>
          <a href={statusUrl()} className="mt-6 text-white/90 text-sm tracking-wide underline underline-offset-4 decoration-white/40 hover:decoration-white transition-colors">
            Already booked? Check status
          </a>
        </div>
      </section>

      <TrustStrip />

      {/* Welcome / The Resort */}
      <section className="py-24 bg-[#f8f5e2] text-zinc-900">
        <div className="container mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div className="grid grid-cols-2 gap-4">
              <img src="/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-deluxe-ac-cottage.jpg" className="w-full h-[300px] object-cover rounded-sm shadow-xl" alt="Lake View" />
              <img src="/assets/images/new/kutch-ac-cottage-_dsc9435.jpg" className="w-full h-[300px] object-cover rounded-sm translate-y-8 shadow-xl" alt="Cottage Exterior" />
            </div>
            <div>
              <p className="text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4">Welcome to Kutch Safari Resort</p>
              <h2 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6 leading-tight">A Quiet Hideaway on the Road to the White Rann</h2>
              <div className="space-y-4 text-zinc-900/80 text-lg leading-relaxed">
                <p>
                  Tucked amidst lush green and clean desert air, Kutch Safari Resort sits on a rise overlooking the Rudramata Dam — fifteen kilometres from Bhuj, on the Khavda road that carries you to Dhordo, the White Rann and Dholavira.
                </p>
                <p>
                  Twenty cottages face the water. Each has comfortable bedding, free Wi-Fi, a flat-screen television, room service and a private balcony where the light changes all evening. Our lake-view restaurant, The Banni, serves Kutchi, Gujarati, Punjabi, Chinese and Continental dishes.
                </p>
                <p>
                  We have welcomed travellers for more than three decades. That, and where we stand, is what makes this one of the finest places to stay in Bhuj and the Rann of Kutch.
                </p>
              </div>
              <Link href="/our-journey" className="inline-flex items-center gap-2 mt-8 text-[var(--terracotta)] uppercase tracking-widest text-sm font-semibold hover:text-zinc-900 transition-colors">
                Our Story <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>
        </div>
      </section>

      <StatsBand />

      {/* The Stay */}
      <section className="py-24">
        <div className="container mx-auto px-6">
          <div className="text-center max-w-2xl mx-auto mb-16">
            <p className="text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4">Accommodation</p>
            <h2 className="text-4xl md:text-5xl font-display text-zinc-900 font-bold mb-6">Cottages That Face the Water</h2>
            <p className="text-zinc-600 text-lg">Two categories, twenty cottages, every one of them looking out over the lake.</p>
          </div>

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
                </div>
                <a href={bookingUrl()} className="text-center border border-[var(--terracotta)] text-[var(--terracotta)] py-3 uppercase tracking-widest text-sm font-semibold hover:bg-[var(--terracotta)] hover:text-white transition-colors rounded-sm block">
                  Book Now
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
                </div>
                <a href={bookingUrl()} className="text-center border border-[var(--terracotta)] text-[var(--terracotta)] py-3 uppercase tracking-widest text-sm font-semibold hover:bg-[var(--terracotta)] hover:text-white transition-colors rounded-sm block">
                  Book Now
                </a>
              </div>
            </div>
          </div>
          
          <div className="mt-12 text-center">
             <Link href="/stay" className="inline-flex items-center gap-2 text-[var(--terracotta)] uppercase tracking-widest text-sm font-semibold hover:text-black transition-colors">
                All Rooms <ArrowRight className="w-4 h-4" />
             </Link>
          </div>
        </div>
      </section>

      {/* Sister Property */}
      <section className="bg-[#f8f5e2] text-zinc-900 py-24">
        <div className="container mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <img src="/assets/images/new/kutchi-tribes-rabari-ravechi-festival.jpg" alt="White Rann Camp" className="w-full h-[400px] object-cover rounded-sm" />
            <div>
              <p className="text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4">Our Sister Property</p>
              <h2 className="text-4xl md:text-5xl font-display font-bold mb-6">White Rann Camp, Dhordo</h2>
              <div className="space-y-4 text-zinc-900/80 text-lg leading-relaxed">
                <p>
                  Three minutes from the entry to the White Rann and from Rann Utsav, our private camp has twenty Swiss tents — six Deluxe Air-Cool and fourteen Non-AC — each with an attached bathroom and hot water. Campfire, folk music and a multi-cuisine kitchen.
                </p>
                <p className="font-medium text-[var(--terracotta)]">Open 1st December 2026 to 31st January 2027.</p>
              </div>
              <div className="flex flex-col sm:flex-row gap-4 mt-8">
                <Link href="/white-rann-camp" className="bg-[var(--terracotta)] text-white px-8 py-3 uppercase tracking-widest text-sm font-semibold text-center hover:bg-[#b04838] transition-colors rounded-sm">
                  Visit White Rann Camp
                </Link>
                <Link href="/white-rann-camp/tariff" className="border border-[var(--terracotta)] text-[var(--terracotta)] px-8 py-3 uppercase tracking-widest text-sm font-semibold text-center hover:bg-[var(--terracotta)] hover:text-white transition-colors rounded-sm">
                  2026-27 Tariff
                </Link>
              </div>
            </div>
          </div>
        </div>
      </section>

      <AmenitiesGrid />

      {/* Experiences */}
      <section className="py-24">
        <div className="container mx-auto px-6">
          <div className="text-center max-w-2xl mx-auto mb-16">
            <p className="text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4">Explore</p>
            <h2 className="text-4xl md:text-5xl font-display text-zinc-900 font-bold mb-6">Kutch, from Our Doorstep</h2>
            <p className="text-zinc-600 text-lg">We sit on the road that everything in Kutch is on the way to.</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {[
              { title: "White Rann & Rann Utsav", desc: "The salt desert at Dhordo, at its best on a full moon night.", img: "/assets/images/new/authentic-sunrise-bhungas.jpg", slug: "the-great-white-rann" },
              { title: "Road to Heaven & Dholavira", desc: "A causeway straight across the salt to a 4,500-year-old Harappan city.", img: "/assets/images/new/kutch-destination-road_2.jpg", slug: "road-to-heaven" },
              { title: "Banni Villages", desc: "Embroidery, leatherwork, and bell-making in the hamlets.", img: "/assets/images/new/kutchi-tribes-rabari-ravechi-festival.jpg", slug: "artisan-villages" },
              { title: "Kala Dungar & Birding", desc: "The Black Hill, the highest point in Kutch — and flamingos below.", img: "/assets/images/new/kala-dungar-scenic.jpg", slug: "kala-dungar" },
              { title: "Mandvi Beach", desc: "A shipbuilding town, a palace on the sand, and the Arabian Sea.", img: "/assets/images/new/kutch-destination-mandvi-beach.jpg", slug: "mandvi-beach-palace" },
              { title: "Bhuj & Bhujodi", desc: "Aina Mahal, Prag Mahal, and the weavers' village just outside town.", img: "/assets/images/new/kutch-handicrafts-block-demo.jpg", slug: "artisan-villages" }
            ].map((exp, i) => (
              // Same destination guides as the Experiences page.
              <Link key={i} href={`/destination/${exp.slug}`} className="group cursor-pointer block">
                <div className="relative h-64 overflow-hidden rounded-sm mb-4">
                  <img src={exp.img} className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt={exp.title} />
                </div>
                <h3 className="text-xl font-bold text-zinc-900 mb-2 group-hover:text-[var(--terracotta)] transition-colors">{exp.title}</h3>
                <p className="text-zinc-600 text-sm mb-3">{exp.desc}</p>
                <span className="text-[var(--terracotta)] uppercase tracking-widest text-xs font-bold flex items-center gap-1">
                  Discover <ArrowRight className="w-3 h-3" />
                </span>
              </Link>
            ))}
          </div>
        </div>
      </section>

      <ContactSection />
      <Footer />
    </div>
  );
}
