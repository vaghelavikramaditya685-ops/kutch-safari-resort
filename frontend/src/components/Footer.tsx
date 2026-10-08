import { Link } from "wouter";
import { Instagram } from "lucide-react";
import { BOOKING_ENABLED, statusUrl } from "@/lib/booking";

// The column titles are h2 so headings run in order on every page (h4 skipped a
// level after a page's h2s); the site's base style gives h2 the display font, so
// they keep the footer's own font and look exactly as before.
const FOOTER_HEADING = { fontFamily: "inherit" } as const;

export default function Footer() {
  return (
    <footer className="bg-[#f8f5e2] text-zinc-600 border-t border-[#e4d5c7] py-16 text-base font-light">
      <div className="container mx-auto px-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8">
          
          <div className="flex flex-col items-start gap-4">
            <Link href="/" className="flex flex-col items-start gap-1 mb-2">
              <p className="font-display text-2xl font-bold tracking-tight text-zinc-900">
                KUTCH SAFARI
              </p>
              <p className="text-[0.65rem] uppercase tracking-[0.3em] font-medium text-zinc-600">
                Resort
              </p>
            </Link>
            <p className="text-sm leading-relaxed max-w-xs">
              A lake-view resort on the outskirts of Bhuj, on the road to the White Rann of Kutch. Twenty cottages, a multi-cuisine restaurant and three decades of hosting.
            </p>
            <div className="flex gap-4 mt-2">
              <a href="https://www.instagram.com/kutchsafariresort/" target="_blank" rel="noopener noreferrer" aria-label="Kutch Safari Resort on Instagram" className="w-10 h-10 rounded-full border border-zinc-200 flex items-center justify-center hover:bg-zinc-900 hover:text-white transition-colors">
                <Instagram className="w-4 h-4" />
              </a>
            </div>
          </div>

          <div>
            <h2 className="text-zinc-900 font-semibold uppercase tracking-widest text-sm mb-6" style={FOOTER_HEADING}>Explore</h2>
            <ul className="space-y-3 text-sm">
              <li><Link href="/stay" className="hover:text-zinc-900 transition-colors">Rooms & Tariff</Link></li>
              <li><Link href="/dining" className="hover:text-zinc-900 transition-colors">Dining</Link></li>
              <li><Link href="/experiences" className="hover:text-zinc-900 transition-colors">Experiences</Link></li>
              <li><Link href="/around-the-resort" className="hover:text-zinc-900 transition-colors">Around the Resort</Link></li>
              <li><Link href="/gallery" className="hover:text-zinc-900 transition-colors">Gallery</Link></li>
            </ul>
          </div>

          <div>
            <h2 className="text-zinc-900 font-semibold uppercase tracking-widest text-sm mb-6" style={FOOTER_HEADING}>Plan</h2>
            <ul className="space-y-3 text-sm">
              <li><Link href="/packages" className="hover:text-zinc-900 transition-colors">Colors of Kutch Packages</Link></li>
              <li><Link href="/plan-your-visit" className="hover:text-zinc-900 transition-colors">How to Reach</Link></li>
              <li><Link href="/plan-your-visit#faq" className="hover:text-zinc-900 transition-colors">FAQs</Link></li>
              <li><Link href="/white-rann-camp" className="hover:text-zinc-900 transition-colors">White Rann Camp</Link></li>
              <li><Link href="/white-rann-camp/tariff" className="hover:text-zinc-900 transition-colors">Rann Utsav 2026–27</Link></li>
            </ul>
          </div>

          <div>
            <h2 className="text-zinc-900 font-semibold uppercase tracking-widest text-sm mb-6" style={FOOTER_HEADING}>Reservations</h2>
            <ul className="space-y-3 text-sm mb-6">
              <li><a href="tel:+919925238599" className="hover:text-zinc-900 transition-colors">+91 99252 38599</a></li>
              {BOOKING_ENABLED && <li><a href={statusUrl()} className="hover:text-zinc-900 transition-colors">Already booked? Check status</a></li>}
              <li><a href="mailto:kutchsafaribhuj@yahoo.com" className="hover:text-zinc-900 transition-colors break-all">kutchsafaribhuj@yahoo.com</a></li>
              <li className="leading-relaxed">Near Rudramata Dam,<br/>Bhuj–Khavda Road, Bhuj,<br/>Kutch, Gujarat 370001</li>
            </ul>
          </div>

        </div>

        <div className="mt-16 pt-8 border-t border-zinc-200 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-zinc-900/50">
          <span>© 2026 Kutch Safari Resort. All rights reserved.</span>
          <span>Bhuj · Rann of Kutch · Gujarat</span>
        </div>
      </div>
    </footer>
  );
}
