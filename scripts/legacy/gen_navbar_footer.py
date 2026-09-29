import os

navbar_code = """import { useState } from "react";
import { Link, useLocation } from "wouter";
import { Menu, X } from "lucide-react";

export default function Navbar() {
  const [open, setOpen] = useState(false);
  const [location] = useLocation();

  const NAV_LINKS = [
    { label: "Home", href: "/" },
    { label: "Our Journey", href: "/our-journey" },
    { label: "Stay", href: "/stay" },
    { label: "Dining", href: "/dining" },
    { label: "Experiences", href: "/experiences" },
    { label: "Packages", href: "/packages" },
    { label: "Weddings", href: "/weddings" },
    { label: "Gallery", href: "/gallery" },
    { label: "Plan Your Visit", href: "/plan-your-visit" },
  ];

  return (
    <>
      <div className="bg-zinc-100 py-2 border-b border-zinc-200 text-xs text-zinc-600 hidden md:block">
        <div className="container mx-auto px-6 flex justify-between items-center">
          <div className="flex gap-6">
            <a href="tel:+919925238599" className="hover:text-[var(--terracotta)] transition-colors">+91 99252 38599</a>
            <a href="mailto:kutchsafaribhuj@yahoo.com" className="hover:text-[var(--terracotta)] transition-colors">kutchsafaribhuj@yahoo.com</a>
          </div>
          <div className="flex gap-6 items-center">
            <span>Near Rudramata Dam, Bhuj–Khavda Road</span>
            <a href="https://www.instagram.com/kutchsafariresort/" target="_blank" rel="noopener noreferrer" className="hover:text-[var(--terracotta)] transition-colors">Instagram</a>
          </div>
        </div>
      </div>

      <header className="sticky top-0 z-50 bg-white shadow-sm border-b border-zinc-200">
        <div className="container mx-auto px-6 h-20 flex justify-between items-center">
          <Link href="/" className="flex flex-col items-start gap-1">
            <h1 className="font-display text-2xl md:text-3xl font-bold tracking-tight text-zinc-900">
              KUTCH SAFARI
            </h1>
            <p className="text-[0.65rem] md:text-xs uppercase tracking-[0.3em] font-medium text-[var(--terracotta)]">
              Resort
            </p>
          </Link>
          
          <nav className="hidden lg:flex items-center gap-5 text-sm font-medium text-zinc-700 uppercase tracking-widest">
            {NAV_LINKS.map((link) => (
              <Link 
                key={link.href} 
                href={link.href}
                className={`hover:text-[var(--terracotta)] transition-colors ${location === link.href ? "text-[var(--terracotta)]" : ""}`}
              >
                {link.label}
              </Link>
            ))}
            <Link href="/white-rann-camp" className="text-[var(--terracotta)] hover:opacity-80 transition-opacity flex items-center gap-1">
              White Rann Camp <span>→</span>
            </Link>
          </nav>
          
          <div className="flex items-center gap-4">
            <a href="/#contact" className="hidden md:inline-flex bg-[var(--terracotta)] text-white px-6 py-2.5 uppercase text-xs tracking-widest hover:bg-[#b04838] transition-colors shadow-sm font-semibold rounded-sm">
              Book Now
            </a>
            <button 
              className="lg:hidden text-zinc-800 p-2" 
              onClick={() => setOpen(!open)}
            >
              {open ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
            </button>
          </div>
        </div>
      </header>

      {/* Mobile Menu */}
      {open && (
        <div className="fixed inset-0 top-[116px] md:top-[116px] z-40 bg-white lg:hidden overflow-y-auto">
          <nav className="flex flex-col p-6 gap-6 text-base font-medium text-zinc-800 uppercase tracking-widest">
            {NAV_LINKS.map((link) => (
              <Link 
                key={link.href} 
                href={link.href}
                onClick={() => setOpen(false)}
                className={`hover:text-[var(--terracotta)] ${location === link.href ? "text-[var(--terracotta)]" : ""}`}
              >
                {link.label}
              </Link>
            ))}
            <Link 
              href="/white-rann-camp" 
              onClick={() => setOpen(false)}
              className="text-[var(--terracotta)] flex items-center gap-2"
            >
              White Rann Camp <span>→</span>
            </Link>
            
            <a href="/#contact" onClick={() => setOpen(false)} className="bg-[var(--terracotta)] text-white px-6 py-3 uppercase text-sm tracking-widest text-center mt-4 rounded-sm">
              Book Now
            </a>
          </nav>
        </div>
      )}
    </>
  );
}
"""

footer_code = """import { Link } from "wouter";
import { Instagram } from "lucide-react";

export default function Footer() {
  return (
    <footer className="bg-zinc-900 text-white/80 py-16 text-base font-light">
      <div className="container mx-auto px-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8">
          
          <div className="flex flex-col items-start gap-4">
            <Link href="/" className="flex flex-col items-start gap-1 mb-2">
              <h1 className="font-display text-2xl font-bold tracking-tight text-white">
                KUTCH SAFARI
              </h1>
              <p className="text-[0.65rem] uppercase tracking-[0.3em] font-medium text-white/80">
                Resort
              </p>
            </Link>
            <p className="text-sm leading-relaxed max-w-xs">
              A lake-view resort on the outskirts of Bhuj, on the road to the White Rann of Kutch. Twenty cottages, a multi-cuisine restaurant and three decades of hosting.
            </p>
            <div className="flex gap-4 mt-2">
              <a href="https://www.instagram.com/kutchsafariresort/" target="_blank" rel="noopener noreferrer" className="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center hover:bg-white hover:text-black transition-colors">
                <Instagram className="w-4 h-4" />
              </a>
            </div>
          </div>

          <div>
            <h4 className="text-white font-semibold uppercase tracking-widest text-sm mb-6">Explore</h4>
            <ul className="space-y-3 text-sm">
              <li><Link href="/our-journey" className="hover:text-white transition-colors">About the Resort</Link></li>
              <li><Link href="/stay" className="hover:text-white transition-colors">Rooms & Tariff</Link></li>
              <li><Link href="/dining" className="hover:text-white transition-colors">Dining</Link></li>
              <li><Link href="/experiences" className="hover:text-white transition-colors">Experiences</Link></li>
              <li><Link href="/gallery" className="hover:text-white transition-colors">Gallery</Link></li>
            </ul>
          </div>

          <div>
            <h4 className="text-white font-semibold uppercase tracking-widest text-sm mb-6">Plan</h4>
            <ul className="space-y-3 text-sm">
              <li><Link href="/packages" className="hover:text-white transition-colors">Colors of Kutch Packages</Link></li>
              <li><Link href="/plan-your-visit" className="hover:text-white transition-colors">How to Reach</Link></li>
              <li><Link href="/plan-your-visit#faq" className="hover:text-white transition-colors">FAQs</Link></li>
              <li><Link href="/white-rann-camp" className="hover:text-white transition-colors">White Rann Camp</Link></li>
              <li><Link href="/white-rann-camp/tariff" className="hover:text-white transition-colors">Rann Utsav 2026–27</Link></li>
            </ul>
          </div>

          <div>
            <h4 className="text-white font-semibold uppercase tracking-widest text-sm mb-6">Reservations</h4>
            <ul className="space-y-3 text-sm mb-6">
              <li><a href="tel:+919925238599" className="hover:text-white transition-colors">+91 99252 38599</a></li>
              <li><a href="mailto:kutchsafaribhuj@yahoo.com" className="hover:text-white transition-colors break-all">kutchsafaribhuj@yahoo.com</a></li>
              <li className="leading-relaxed">Near Rudramata Dam,<br/>Bhuj–Khavda Road, Bhuj,<br/>Kutch, Gujarat 370001</li>
            </ul>
          </div>

        </div>

        <div className="mt-16 pt-8 border-t border-white/10 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-white/50">
          <span>© 2026 Kutch Safari Resort. All rights reserved.</span>
          <span>Bhuj · Rann of Kutch · Gujarat</span>
        </div>
      </div>
    </footer>
  );
}
"""

with open("client/src/components/Navbar.tsx", "w", encoding="utf-8") as f:
    f.write(navbar_code)
    
with open("client/src/components/Footer.tsx", "w", encoding="utf-8") as f:
    f.write(footer_code)

print("Created Navbar and Footer components.")
