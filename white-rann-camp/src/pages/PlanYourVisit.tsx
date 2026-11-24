import { MapPin, CalendarDays, Clock, Phone, Navigation } from "lucide-react";
import Navbar from "@/components/Navbar";
import useScrollToHash from "@/useScrollToHash";
import Footer from "@/components/Footer";
import Figure, { PageHeader } from "@/components/Figure";
import { CAMP, CAMP_PHOTOS, FAQS } from "@/data";

export default function PlanYourVisit() {
  useScrollToHash();

  const facts = [
    { icon: MapPin, title: "Where", text: `${CAMP.address}. ${CAMP.fromTentCity}: three minutes from Rann Utsav and the entry to the White Rann; about 80 km from Bhuj.` },
    { icon: CalendarDays, title: "Season", text: `Open ${CAMP.season}.` },
    { icon: Clock, title: "Times", text: `Check-in from ${CAMP.checkIn}, check-out by ${CAMP.checkOut}.` },
    { icon: Phone, title: "Reservations", text: `${CAMP.phone} · ${CAMP.phoneAlt} · ${CAMP.email}` },
  ];

  return (
    <div className="min-h-screen">
      <Navbar />
      <PageHeader eyebrow={CAMP.place} title="Plan Your Visit" intro="How to reach us and what to expect." />

      <section className="py-20">
        <div className="container mx-auto px-6 grid lg:grid-cols-2 gap-14 items-start">
          <div className="grid sm:grid-cols-2 gap-5">
            {facts.map(({ icon: Icon, title, text }) => (
              <div key={title} className="border border-rann-200 rounded-sm p-6 bg-white">
                <Icon className="w-6 h-6 text-rann-600 mb-3" />
                <h2 className="text-2xl font-bold text-zinc-900 mb-2">{title}</h2>
                <p className="text-sm text-zinc-700 leading-relaxed break-words">{text}</p>
              </div>
            ))}
          </div>
          <div>
            <Figure photo={CAMP_PHOTOS.entranceNight} className="h-[360px] rounded-sm" />
            <a href={CAMP.mapsUrl} target="_blank" rel="noreferrer"
              className="mt-5 w-full inline-flex items-center justify-center gap-2 bg-rann-700 hover:bg-rann-800 text-white px-8 py-4 uppercase tracking-[0.18em] text-sm font-semibold rounded-sm transition-colors">
              <Navigation className="w-4 h-4" /> Get directions (Google Maps)
            </a>
          </div>
        </div>
      </section>

      <section id="faq" className="py-20 bg-white border-t border-rann-200 scroll-mt-24">
        <div className="container mx-auto px-6 max-w-3xl">
          <h2 className="text-4xl md:text-5xl font-bold text-zinc-900 mb-10 text-center">FAQs</h2>
          <dl className="space-y-4">
            {FAQS.map((f) => (
              <div key={f.q} className="bg-white border border-rann-200 rounded-sm p-6">
                <dt className="font-semibold text-zinc-900 mb-2">{f.q}</dt>
                <dd className="text-zinc-700 leading-relaxed">{f.a}</dd>
              </div>
            ))}
          </dl>
        </div>
      </section>

      <Footer />
    </div>
  );
}
