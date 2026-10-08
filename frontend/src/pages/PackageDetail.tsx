import { Link, useRoute } from "wouter";
import { useEffect } from "react";
import {
  ArrowLeft, CalendarDays, Hotel, Utensils, Bus, Car, Ticket, HandCoins, Wallet, Tent, XCircle,
} from "lucide-react";
import NotFound from "./NotFound";
import { PACKAGE_EXCLUDES, PACKAGE_INCLUDES, packageBySlug, rupees } from "@/lib/packages";

const INCLUDE_ICONS = { Hotels: Hotel, Meals: Utensils, Transport: Bus, Sightseeing: Car } as const;
const EXCLUDE_ICONS = { "Entry tickets": Ticket, Tips: HandCoins, "Personal expenses": Wallet } as const;

/* One tour package (/packages/:slug), laid out like the White Rann Camp tariff
   page (RannUtsavPackage.tsx): the itinerary and what is included on the left,
   the tariff per person on the right. */
export default function PackageDetail() {
  const [, params] = useRoute("/packages/:slug");
  const p = packageBySlug(params?.slug ?? "");

  useEffect(() => {
    window.scrollTo(0, 0);
  }, [params?.slug]);

  if (!p) return <NotFound />;

  // Booking a package is by inquiry: WhatsApp opens with the package filled in and
  // blanks for what the desk needs to reply with availability and a price.
  const whatsapp = `https://wa.me/919925238599?text=${encodeURIComponent(
    `Hello Kutch Safari Resort, I would like to book the ${p.title} package (${p.nights} Nights / ${p.days} Days).\n\n`
    + `Travel dates: \nNumber of people: \nName: `)}`;

  return (
    <div className="min-h-screen bg-background">
      {/* Fixed Navbar, as on the White Rann Camp page */}
      <header className="fixed inset-x-0 top-0 z-50 bg-background/95 backdrop-blur-md shadow-[0_1px_0_0_oklch(0.88_0.03_75/0.8)] py-3">
        <div className="w-full px-6 md:px-10 flex items-center justify-between">
          <Link href="/" className="flex items-center gap-3">
            <img src="/assets/images/logo-mark.png" alt="Kutch Safari Resort" className="h-10 w-10 object-contain" />
            <div className="leading-tight">
              <span className="font-display text-sm font-bold tracking-wide text-foreground block">KUTCH SAFARI</span>
              <span className="text-[10px] uppercase tracking-[0.2em] text-foreground/60">Resort &middot; Bhuj</span>
            </div>
          </Link>
          <Link href="/packages" className="flex items-center gap-2 text-sm font-medium text-foreground/70 hover:text-foreground transition-colors">
            <ArrowLeft className="h-4 w-4" />
            All packages
          </Link>
        </div>
      </header>

      <div className="container mx-auto px-4 pt-24 pb-16 max-w-5xl">
        <section>
          <div className="text-center mb-10">
            <h1 className="font-display text-3xl md:text-4xl font-semibold text-[var(--terracotta)] mb-3">{p.title}</h1>
            <p className="text-foreground/80 font-medium uppercase">
              {p.kind} | {p.nights} Nights {p.days} Days | {p.from} to {p.to}
            </p>
            <p className="text-sm text-foreground/60 mt-2">{p.route.join(" - ")}</p>
          </div>

          <div className="grid grid-cols-4 gap-2 sm:gap-3 mb-10">
            {p.photos.map(ph => (
              <img key={ph.src} src={ph.src} alt={ph.alt} className="w-full h-40 sm:h-64 object-cover rounded" />
            ))}
          </div>

          <div className="grid md:grid-cols-2 gap-8 mb-12">
            <div className="bg-white p-8 border border-border/50 rounded shadow-sm">
              <h2 className="font-display text-xl font-semibold mb-4 border-b pb-2">Itinerary</h2>
              <ul className="space-y-3">
                {p.itinerary.map((day, i) => (
                  <li key={i} className="flex items-start gap-3">
                    <CalendarDays className="h-5 w-5 text-[var(--terracotta)] shrink-0" />
                    <span className="text-sm text-foreground/80"><span className="font-semibold">Day {i + 1}</span> · {day}</span>
                  </li>
                ))}
              </ul>

              <h2 className="font-display text-xl font-semibold mb-4 mt-8 border-b pb-2">Package Includes</h2>
              <ul className="space-y-3">
                {PACKAGE_INCLUDES.map(item => {
                  const Icon = INCLUDE_ICONS[item];
                  return (
                    <li key={item} className="flex items-start gap-3">
                      <Icon className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80">{item}</span>
                    </li>
                  );
                })}
              </ul>

              <h2 className="font-display text-xl font-semibold mb-4 mt-8 border-b pb-2">Not Included</h2>
              <ul className="space-y-3">
                {PACKAGE_EXCLUDES.map(item => {
                  const Icon = EXCLUDE_ICONS[item];
                  return (
                    <li key={item} className="flex items-start gap-3">
                      <Icon className="h-5 w-5 text-foreground/50 shrink-0" /><span className="text-sm text-foreground/80">{item}</span>
                    </li>
                  );
                })}
              </ul>
            </div>

            <div id="tariff" className="bg-white p-8 border border-border/50 rounded shadow-sm scroll-mt-28">
              <h2 className="font-display text-xl font-semibold mb-4 border-b pb-2">Tour Cost Per Person (INR)</h2>

              <div className="space-y-6">
                <div>
                  <h3 className="font-semibold text-[var(--terracotta)] mb-2" style={{ fontFamily: "inherit" }}>{p.nights} Nights / {p.days} Days</h3>
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    {p.prices.map(pr => (
                      <div key={pr.label} className="bg-secondary/50 p-3 rounded">
                        <span className="block text-xs text-foreground/60">{pr.label === "Extra Person" ? "Extra Person" : `If ${pr.label}`}</span>
                        <span className="font-medium">{rupees(pr.perPerson)} per person</span>
                      </div>
                    ))}
                  </div>
                </div>

                <div className="text-xs text-foreground/70 bg-secondary/30 p-4 rounded border border-border/50">
                  <span className="font-semibold block mb-1 flex items-center gap-2"><Tent className="h-4 w-4 text-[var(--terracotta)]" /> {p.stayLabel}:</span>
                  <p>{p.stays}</p>
                  <p className="mt-2 text-[var(--terracotta)] font-medium">
                    The price per person depends on how many travel together; each extra person {rupees(p.prices.find(x => x.label === "Extra Person")?.perPerson ?? 0)}.
                  </p>
                </div>

                <div className="text-xs text-foreground/60 flex items-start gap-2">
                  <XCircle className="h-4 w-4 shrink-0 text-foreground/40" />
                  <span>Entry tickets, tips and personal expenses are not included.</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* CTA */}
        <div className="p-8 bg-[#f6f0e8] border border-[#e4d5c7] rounded-sm text-center">
          <h2 className="font-display text-2xl font-semibold text-foreground mb-3">Book this Package</h2>
          <p className="text-foreground/70 mb-6 font-light">
            Send us an inquiry on WhatsApp with your travel dates and how many of you are coming,
            and we will reply with availability and confirm your booking.
          </p>
          <a href={whatsapp} target="_blank" rel="noopener noreferrer"
             className="inline-block bg-[var(--terracotta)] text-white px-10 py-3.5 uppercase tracking-widest text-sm font-semibold hover:opacity-90 transition-opacity shadow-md">
            Send Inquiry on WhatsApp
          </a>
        </div>
      </div>

      <footer className="bg-[#2c2c2c] text-white/70 py-10 mt-12">
        <div className="container mx-auto px-4 text-center text-sm">
          <p>&copy; {new Date().getFullYear()} Kutch Safari Resort. All rights reserved.</p>
          <p className="mt-2 text-white/40">13 km from Bhuj, Rudramata Dam Road, Kutch, Gujarat 370001</p>
        </div>
      </footer>
    </div>
  );
}
