import { useState } from "react";
import { Link, useLocation } from "wouter";
import { Menu, X, ArrowUpRight } from "lucide-react";
import { CAMP } from "@/data";

/* Same layout as the Kutch Safari Resort header (frontend/src/components/Navbar.tsx):
   thin contact bar, sticky bar with the logo, an evenly spread menu from 1280 px,
   a bordered button for the sister property and a solid Enquire Now. */

export const NAV_LINKS = [
  { label: "Home", href: "/" },
  { label: "Tents & Tariff", href: "/tents" },
  { label: "Rann Utsav Tours", href: "/tours" },
  { label: "Gallery", href: "/gallery" },
  { label: "Plan Your Visit", href: "/plan-your-visit" },
];

export function Logo({ light = false }: { light?: boolean }) {
  return (
    <span className="flex flex-col leading-none">
      <span className={`font-display text-2xl font-bold tracking-wide ${light ? "text-white" : "text-rann-800"}`}>WHITE RANN CAMP</span>
      <span className={`text-[10px] uppercase tracking-[0.3em] mt-1 ${light ? "text-white/70" : "text-rann-600"}`}>{CAMP.place}</span>
    </span>
  );
}

export default function Navbar() {
  const [open, setOpen] = useState(false);
  const [location] = useLocation();

  return (
    <>
      <div className="bg-rann-100 py-2 border-b border-rann-200 text-xs text-zinc-600 hidden md:block">
        <div className="container mx-auto px-6 flex justify-between items-center">
          <div className="flex gap-6">
            <a href={CAMP.phoneHref} className="hover:text-rann-700 transition-colors">{CAMP.phone}</a>
            <a href={`mailto:${CAMP.email}`} className="hover:text-rann-700 transition-colors">{CAMP.email}</a>
          </div>
          <div className="flex gap-6 items-center">
            <span>{CAMP.address}</span>
            <a href={CAMP.mapsUrl} target="_blank" rel="noreferrer" className="hover:text-rann-700 transition-colors">Get directions</a>
          </div>
        </div>
      </div>

      <header className="sticky top-0 z-50 bg-rann-50 shadow-sm border-b border-rann-200">
        <div className="w-full max-w-[1440px] mx-auto px-6 h-16 flex justify-between items-center">
          <Link href="/" className="flex items-center shrink-0" aria-label="White Rann Camp, home">
            <Logo />
          </Link>

          <nav className="hidden xl:flex flex-1 justify-evenly items-center gap-3 text-[11px] font-semibold text-zinc-900 uppercase tracking-wider px-6 whitespace-nowrap">
            {NAV_LINKS.map((link) => (
              <Link key={link.href} href={link.href}
                className={`hover:text-rann-700 transition-colors ${location === link.href ? "text-rann-700" : ""}`}>
                {link.label}
              </Link>
            ))}
          </nav>

          <div className="flex items-center justify-end gap-4 shrink-0">
            <a href={CAMP.sisterUrl} target="_blank" rel="noreferrer" aria-label="Kutch Safari Resort website (opens in a new tab)"
              className="hidden md:flex items-center justify-center gap-1 border border-rann-200 px-4 py-2 uppercase text-[10px] tracking-widest font-semibold text-zinc-700 hover:border-rann-300 transition-colors rounded-sm bg-white shadow-sm whitespace-nowrap">
              {CAMP.sisterName} <ArrowUpRight className="w-3 h-3" />
            </a>
            <Link href="/enquire" className="hidden md:inline-flex bg-rann-700 text-white px-6 py-2.5 uppercase text-xs tracking-widest hover:bg-rann-800 transition-colors shadow-sm font-semibold rounded-sm whitespace-nowrap">
              Enquire Now
            </Link>
            <button className="xl:hidden text-zinc-900 p-2" onClick={() => setOpen(!open)}
              aria-label={open ? "Close menu" : "Open menu"} aria-expanded={open}>
              {open ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
            </button>
          </div>
        </div>
      </header>

      {open && (
        <div className="fixed inset-0 top-[64px] md:top-[97px] z-40 bg-rann-50 xl:hidden overflow-y-auto">
          <nav className="flex flex-col p-6 gap-6 text-base font-medium text-zinc-900 uppercase tracking-widest">
            {NAV_LINKS.map((link) => (
              <Link key={link.href} href={link.href} onClick={() => setOpen(false)}
                className={`hover:text-rann-700 ${location === link.href ? "text-rann-700" : ""}`}>
                {link.label}
              </Link>
            ))}
            <a href={CAMP.sisterUrl} target="_blank" rel="noreferrer" className="text-rann-700 flex items-center gap-2">
              {CAMP.sisterName} <ArrowUpRight className="w-4 h-4" />
            </a>
            <Link href="/enquire" onClick={() => setOpen(false)} className="bg-rann-700 text-white px-6 py-3 uppercase text-sm tracking-widest text-center mt-4 rounded-sm">
              Enquire Now
            </Link>
          </nav>
        </div>
      )}
    </>
  );
}
