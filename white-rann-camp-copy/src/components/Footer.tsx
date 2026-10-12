import { Link } from "wouter";
import { ArrowUpRight } from "lucide-react";
import { CAMP } from "@/data";

/* Same layout as the Kutch Safari Resort footer: light, four columns, a thin line at the bottom.
   Column titles are h2 (headings run in order) with the footer's own font. */
const HEADING = "text-zinc-900 font-semibold uppercase tracking-widest text-sm mb-6";
const FOOTER_HEADING = { fontFamily: "inherit" } as const;

export default function Footer() {
  return (
    <footer className="bg-rann-50 text-zinc-600 border-t border-rann-200 py-16 text-base font-light">
      <div className="container mx-auto px-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8">
          <div className="flex flex-col items-start gap-4">
            <Link href="/" className="flex flex-col items-start gap-1 mb-2">
              <p className="font-display text-2xl font-bold tracking-tight text-zinc-900">WHITE RANN</p>
              <p className="text-[0.65rem] uppercase tracking-[0.3em] font-medium text-zinc-600">Camp · Dhordo</p>
            </Link>
            <p className="text-sm leading-relaxed max-w-xs">
              A private camp {CAMP.fromTentCity}, three minutes from the White Rann. Twenty Swiss tents, Kutchi food and multi-cuisine, campfire and folk music.
            </p>
            <p className="text-sm font-medium text-rann-700">{CAMP.seasonShort}</p>
          </div>

          <div>
            <h2 className={HEADING} style={FOOTER_HEADING}>Explore</h2>
            <ul className="space-y-3 text-sm">
              <li><Link href="/tents" className="hover:text-zinc-900 transition-colors">Tents &amp; Tariff</Link></li>
              <li><Link href="/tours" className="hover:text-zinc-900 transition-colors">Rann Utsav Tours</Link></li>
              <li><Link href="/gallery" className="hover:text-zinc-900 transition-colors">Gallery</Link></li>
              <li>
                <a href={CAMP.sisterUrl} target="_blank" rel="noreferrer" className="hover:text-zinc-900 transition-colors inline-flex items-center gap-1">
                  {CAMP.sisterName} <ArrowUpRight className="w-3.5 h-3.5" />
                </a>
              </li>
            </ul>
          </div>

          <div>
            <h2 className={HEADING} style={FOOTER_HEADING}>Plan</h2>
            <ul className="space-y-3 text-sm">
              <li><Link href="/tents#tariff" className="hover:text-zinc-900 transition-colors">2026–27 Tariff</Link></li>
              <li><Link href="/plan-your-visit" className="hover:text-zinc-900 transition-colors">How to Reach</Link></li>
              <li><Link href="/plan-your-visit#faq" className="hover:text-zinc-900 transition-colors">FAQs</Link></li>
              <li><Link href="/enquire" className="hover:text-zinc-900 transition-colors">Send an Enquiry</Link></li>
            </ul>
          </div>

          <div>
            <h2 className={HEADING} style={FOOTER_HEADING}>Reservations</h2>
            <ul className="space-y-3 text-sm">
              <li><a href={CAMP.phoneHref} className="hover:text-zinc-900 transition-colors">{CAMP.phone}</a></li>
              <li><a href={CAMP.phoneAltHref} className="hover:text-zinc-900 transition-colors">{CAMP.phoneAlt}</a></li>
              <li><a href={`mailto:${CAMP.email}`} className="hover:text-zinc-900 transition-colors break-all">{CAMP.email}</a></li>
              <li className="leading-relaxed">{CAMP.address}</li>
              <li><a href={CAMP.mapsUrl} target="_blank" rel="noreferrer" className="text-rann-700 underline underline-offset-2 hover:text-zinc-900">Get directions</a></li>
            </ul>
          </div>
        </div>

        <div className="mt-16 pt-8 border-t border-rann-200 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-zinc-900/50">
          <span>© 2026 {CAMP.name}. All rights reserved.</span>
          <span>
            Landscape photos: Wikimedia Commons contributors, see the{" "}
            <Link href="/gallery#credits" className="underline underline-offset-2 hover:text-zinc-900">photo credits</Link>.
          </span>
          <span>Dhordo · Rann of Kutch · Gujarat</span>
        </div>
      </div>
    </footer>
  );
}
