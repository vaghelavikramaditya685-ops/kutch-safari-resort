import { useSearch, Link } from "wouter";
import { Phone, Mail, MapPin, CalendarDays } from "lucide-react";
import Navbar from "@/components/Navbar";
import Footer from "@/components/Footer";
import { PageHeader } from "@/components/Figure";
import EnquiryForm from "@/components/EnquiryForm";
import { CAMP } from "@/data";

export default function Enquire() {
  const stay = new URLSearchParams(useSearch()).get("stay") ?? undefined;
  return (
    <div className="min-h-screen">
      <Navbar />
      <PageHeader eyebrow="Reservations" title="Send an Enquiry"
        intro="Tell us your dates and who's coming. Our reservations desk will confirm availability and the rate." />
      <section className="py-20 bg-white border-y border-rann-200">
        <div className="container mx-auto px-6 grid lg:grid-cols-5 gap-12">
          <aside className="lg:col-span-2 space-y-5 text-zinc-700">
            <p className="flex items-center gap-3"><Phone className="w-5 h-5 text-rann-600" /><a href={CAMP.phoneHref} className="hover:text-rann-700">{CAMP.phone}</a></p>
            <p className="flex items-center gap-3"><Phone className="w-5 h-5 text-rann-600" /><a href={CAMP.phoneAltHref} className="hover:text-rann-700">{CAMP.phoneAlt}</a></p>
            <p className="flex items-center gap-3"><Mail className="w-5 h-5 text-rann-600" /><a href={`mailto:${CAMP.email}`} className="hover:text-rann-700">{CAMP.email}</a></p>
            <p className="flex items-center gap-3"><MapPin className="w-5 h-5 text-rann-600" />{CAMP.address}</p>
            <p className="flex items-center gap-3"><CalendarDays className="w-5 h-5 text-rann-600" />{CAMP.seasonShort}</p>
            <p className="text-sm text-zinc-500 pt-4">
              Rates and what they include are on <Link href="/tents#tariff" className="text-rann-700 underline underline-offset-2">Tents &amp; Tariff</Link>.
            </p>
          </aside>
          <div className="lg:col-span-3 bg-white border border-rann-200 rounded-sm p-8 md:p-10 shadow-sm">
            <EnquiryForm key={stay ?? ""} stay={stay} />
          </div>
        </div>
      </section>
      <Footer />
    </div>
  );
}
