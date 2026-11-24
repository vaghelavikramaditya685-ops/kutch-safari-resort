import { Link } from "wouter";
import { Check, CalendarDays, Clock } from "lucide-react";
import Navbar from "@/components/Navbar";
import useScrollToHash from "@/useScrollToHash";
import Footer from "@/components/Footer";
import Figure, { PageHeader, SectionTitle } from "@/components/Figure";
import { CAMP, EXTRA_PERSON, INCLUDED, PEAK_DATES, TENTS, TOTAL_TENTS, rupees } from "@/data";

export default function Tents() {
  useScrollToHash();
  return (
    <div className="min-h-screen">
      <Navbar />
      <PageHeader eyebrow={CAMP.place} title="Tents & Tariff"
        intro={`${TOTAL_TENTS} Swiss tents, each with an attached bathroom with hot and cold water. ${CAMP.seasonShort}.`} />

      {TENTS.map((t, i) => (
        <section key={t.slug} id={t.slug} className={`py-20 scroll-mt-24 ${i % 2 ? "bg-white border-y border-rann-200" : ""}`}>
          <div className="container mx-auto px-6 grid lg:grid-cols-2 gap-14 items-start">
            <div className={`grid grid-cols-2 gap-4 ${i % 2 ? "lg:order-2" : ""}`}>
              <Figure photo={t.photos[0]} className="col-span-2 h-80 rounded-sm" eager={i === 0} />
              {t.photos.slice(1).map((p) => <Figure key={p.src} photo={p} className="h-56 rounded-sm" />)}
            </div>
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.25em] text-rann-600 mb-2">{t.count} tents</p>
              <h2 className="text-4xl md:text-5xl font-bold text-zinc-900 mb-5">{t.name}</h2>
              <p className="text-lg text-zinc-700 font-light leading-relaxed mb-8">{t.summary}</p>
              <ul className="grid sm:grid-cols-2 gap-3 mb-10">
                {t.features.map((f) => (
                  <li key={f} className="flex items-center gap-2 text-zinc-800"><Check className="w-4 h-4 text-rann-600" />{f}</li>
                ))}
              </ul>
              <div className="grid grid-cols-2 gap-4 mb-8">
                <div className="bg-white border border-rann-200 rounded-sm p-5">
                  <p className="text-xs uppercase tracking-widest text-zinc-500 mb-1">Per night</p>
                  <p className="font-display text-3xl font-bold text-rann-900">{rupees(t.price)}</p>
                  <p className="text-xs text-zinc-500">for two, + GST</p>
                </div>
                <div className="bg-white border border-rann-200 rounded-sm p-5">
                  <p className="text-xs uppercase tracking-widest text-zinc-500 mb-1">Peak dates</p>
                  <p className="font-display text-3xl font-bold text-rann-900">{rupees(t.peakPrice)}</p>
                  <p className="text-xs text-zinc-500">for two, + GST</p>
                </div>
              </div>
              <Link href={`/enquire?stay=${encodeURIComponent(t.name)}`} className="inline-block bg-rann-700 hover:bg-rann-800 text-white px-8 py-4 uppercase tracking-[0.18em] text-sm font-semibold rounded-sm transition-colors">
                Enquire about this tent
              </Link>
            </div>
          </div>
        </section>
      ))}

      {/* Tariff */}
      <section id="tariff" className="py-24 scroll-mt-24">
        <div className="container mx-auto px-6 max-w-5xl">
          <SectionTitle eyebrow={CAMP.season} title="Tariff 2026–27" center />
          <div className="overflow-x-auto border border-rann-200 rounded-sm mb-4">
            <table className="w-full text-left min-w-[560px]">
              <caption className="sr-only">Per tent per night, double occupancy, plus GST</caption>
              <thead className="bg-rann-100 text-rann-900 text-xs uppercase tracking-widest">
                <tr>
                  <th scope="col" className="p-4 font-semibold">Tent</th>
                  <th scope="col" className="p-4 font-semibold">Tents</th>
                  <th scope="col" className="p-4 font-semibold">Non-peak dates</th>
                  <th scope="col" className="p-4 font-semibold">Peak dates</th>
                </tr>
              </thead>
              <tbody>
                {TENTS.map((t) => (
                  <tr key={t.slug} className="border-t border-rann-200">
                    <th scope="row" className="p-4 font-medium text-zinc-900">{t.name}</th>
                    <td className="p-4 text-zinc-700">{t.count}</td>
                    <td className="p-4 text-zinc-900 font-semibold">{rupees(t.price)} + GST</td>
                    <td className="p-4 text-zinc-900 font-semibold">{rupees(t.peakPrice)} + GST</td>
                  </tr>
                ))}
                <tr className="border-t border-rann-200 bg-rann-50">
                  <th scope="row" className="p-4 font-medium text-zinc-900">{EXTRA_PERSON.label}</th>
                  <td className="p-4" />
                  <td className="p-4 text-zinc-900 font-semibold" colSpan={2}>{rupees(EXTRA_PERSON.price)} + GST</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p className="text-sm text-zinc-500 mb-14">Per tent per night, for two (double occupancy).</p>

          <div className="grid md:grid-cols-3 gap-6">
            <div className="border border-rann-200 rounded-sm p-6">
              <h3 className="text-2xl font-bold text-zinc-900 mb-4">Included</h3>
              <ul className="space-y-2 text-sm text-zinc-700">
                {INCLUDED.map((x) => <li key={x} className="flex gap-2"><Check className="w-4 h-4 text-rann-600 shrink-0 mt-0.5" />{x}</li>)}
              </ul>
            </div>
            <div className="border border-rann-200 rounded-sm p-6">
              <h3 className="text-2xl font-bold text-zinc-900 mb-4 flex items-center gap-2"><CalendarDays className="w-5 h-5 text-rann-600" />Peak dates</h3>
              <ul className="space-y-3 text-sm text-zinc-700">
                {PEAK_DATES.map((p) => <li key={p.label}><span className="font-medium text-zinc-900 block">{p.label}</span>{p.dates}</li>)}
              </ul>
            </div>
            <div className="border border-rann-200 rounded-sm p-6">
              <h3 className="text-2xl font-bold text-zinc-900 mb-4 flex items-center gap-2"><Clock className="w-5 h-5 text-rann-600" />Season &amp; times</h3>
              <ul className="space-y-3 text-sm text-zinc-700">
                <li><span className="font-medium text-zinc-900 block">Open</span>{CAMP.season}</li>
                <li><span className="font-medium text-zinc-900 block">Check-in</span>From {CAMP.checkIn}</li>
                <li><span className="font-medium text-zinc-900 block">Check-out</span>By {CAMP.checkOut}</li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <Footer />
    </div>
  );
}
