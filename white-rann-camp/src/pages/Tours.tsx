import { Check, X, MessageCircle } from "lucide-react";
import Navbar from "@/components/Navbar";
import Footer from "@/components/Footer";
import Figure, { PageHeader } from "@/components/Figure";
import { RANN, TOURS, TOUR_EXCLUDES, TOUR_INCLUDES, rupees, whatsappLink } from "@/data";

export default function Tours() {
  return (
    <div className="min-h-screen">
      <Navbar />
      <PageHeader eyebrow="Kutch ke Rang, Apno ke Sang" title="Rann Utsav Tours"
        intro="Tours from Bhuj with a night by the White Rann. Hotels, meals, transport and sightseeing included. Prices are per person and depend on how many travel together." />

      <div className="container mx-auto px-6 py-20 space-y-20 max-w-6xl">
        {TOURS.map((t, i) => (
          <article key={t.title} className="grid lg:grid-cols-2 gap-12 items-start">
            <Figure photo={i === 0 ? RANN.saltRoad : RANN.utsavSunset} className={`h-80 lg:h-[460px] rounded-sm ${i % 2 ? "lg:order-2" : ""}`} />
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.25em] text-rann-600 mb-2">{t.nights} Nights · {t.days} Days</p>
              <h2 className="text-4xl font-bold text-zinc-900 mb-3">{t.title}</h2>
              <p className="text-zinc-600 mb-1">{t.route.join(" → ")}</p>
              <p className="text-sm text-zinc-500 mb-8">Stays: {t.stays}</p>

              <h3 className="text-xl font-bold text-zinc-900 mb-3">Itinerary</h3>
              <ol className="space-y-2 mb-8">
                {t.itinerary.map((d, n) => (
                  <li key={d} className="flex gap-3 text-zinc-700">
                    <span className="text-xs font-semibold uppercase tracking-widest text-rann-600 w-14 shrink-0 pt-0.5">Day {n + 1}</span>{d}
                  </li>
                ))}
              </ol>

              <h3 className="text-xl font-bold text-zinc-900 mb-3">Price per person</h3>
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-8">
                {t.prices.map((p) => (
                  <div key={p.label} className="border border-rann-200 bg-rann-50 rounded-sm p-3 text-center">
                    <span className="block text-xs text-zinc-500">{p.label}</span>
                    <span className="font-semibold text-zinc-900">{rupees(p.perPerson)}</span>
                  </div>
                ))}
              </div>

              <a href={whatsappLink(`Hello White Rann Camp, I would like to book the ${t.title} tour (${t.nights} Nights / ${t.days} Days).\n\nTravel dates: \nNumber of people: \nName: `)}
                target="_blank" rel="noreferrer"
                className="inline-flex items-center gap-2 bg-rann-700 hover:bg-rann-800 text-white px-8 py-4 uppercase tracking-[0.18em] text-sm font-semibold rounded-sm transition-colors">
                <MessageCircle className="w-4 h-4" /> Enquire on WhatsApp
              </a>
            </div>
          </article>
        ))}

        <div className="grid md:grid-cols-2 gap-6 border-t border-rann-200 pt-14">
          <div>
            <h3 className="text-2xl font-bold text-zinc-900 mb-4">Every tour includes</h3>
            <ul className="space-y-2">{TOUR_INCLUDES.map((x) => <li key={x} className="flex gap-2 text-zinc-700"><Check className="w-4 h-4 text-rann-600 mt-1" />{x}</li>)}</ul>
          </div>
          <div>
            <h3 className="text-2xl font-bold text-zinc-900 mb-4">Not included</h3>
            <ul className="space-y-2">{TOUR_EXCLUDES.map((x) => <li key={x} className="flex gap-2 text-zinc-700"><X className="w-4 h-4 text-zinc-400 mt-1" />{x}</li>)}</ul>
          </div>
        </div>
      </div>

      <Footer />
    </div>
  );
}
