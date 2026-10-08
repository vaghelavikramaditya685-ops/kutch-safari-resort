import { useEffect } from "react";
import { useSearch } from "wouter";
import { Mail, MapPin, Phone } from "lucide-react";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";
import EnquiryForm from "../components/EnquiryForm";

/* /enquire — where every Enquire Now goes while online booking is off (lib/booking.ts).
   ?room=… fills in the room the guest was looking at. */
export default function Enquire() {
  const room = new URLSearchParams(useSearch()).get("room") ?? undefined;

  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  return (
    <div className="min-h-screen bg-[#f8f5e2] font-sans text-zinc-900">
      <Navbar />

      <div className="pt-24 pb-16 bg-[#f8f5e2] border-b border-[#e4d5c7]">
        <div className="container mx-auto px-6 text-center max-w-3xl">
          <p className="text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4">Plan Your Stay</p>
          <h1 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6">Send an Enquiry</h1>
          <p className="text-lg text-zinc-600">Tell us your dates and who's coming. Our reservations desk will confirm availability and rates.</p>
        </div>
      </div>

      <section className="py-20">
        <div className="container mx-auto px-6 max-w-6xl grid grid-cols-1 lg:grid-cols-12 gap-12">
          <div className="lg:col-span-4 space-y-8">
            <div className="flex gap-4 items-start">
              <div className="w-12 h-12 bg-white rounded-full flex items-center justify-center border border-[#e4d5c7] shadow-sm text-[var(--terracotta)] shrink-0">
                <Phone className="w-5 h-5" />
              </div>
              <div>
                <p className="text-sm uppercase tracking-widest text-zinc-500 font-medium mb-1">Reservations & WhatsApp</p>
                <a href="tel:+919925238599" className="text-lg font-medium text-zinc-900 block">+91 99252 38599</a>
              </div>
            </div>
            <div className="flex gap-4 items-start">
              <div className="w-12 h-12 bg-white rounded-full flex items-center justify-center border border-[#e4d5c7] shadow-sm text-[var(--terracotta)] shrink-0">
                <Mail className="w-5 h-5" />
              </div>
              <div>
                <p className="text-sm uppercase tracking-widest text-zinc-500 font-medium mb-1">Email</p>
                <a href="mailto:kutchsafaribhuj@yahoo.com" className="text-base font-medium text-zinc-900 [overflow-wrap:anywhere]">kutchsafaribhuj@yahoo.com</a>
              </div>
            </div>
            <div className="flex gap-4 items-start">
              <div className="w-12 h-12 bg-white rounded-full flex items-center justify-center border border-[#e4d5c7] shadow-sm text-[var(--terracotta)] shrink-0">
                <MapPin className="w-5 h-5" />
              </div>
              <div>
                <p className="text-sm uppercase tracking-widest text-zinc-500 font-medium mb-1">Resort</p>
                <p className="text-zinc-900 leading-relaxed">Near Rudramata Dam, Bhuj–Khavda Road, Bhuj, Kutch, Gujarat 370001</p>
              </div>
            </div>
          </div>

          <div className="lg:col-span-8">
            <div className="bg-white border border-[#e4d5c7] p-8 shadow-sm rounded-sm">
              <EnquiryForm room={room} />
            </div>
          </div>
        </div>
      </section>

      <Footer />
    </div>
  );
}
