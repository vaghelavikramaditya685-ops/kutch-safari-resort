import { useState } from "react";
import { Link, useLocation } from "wouter";
import { Menu, X } from "lucide-react";
import { bookingUrl } from "@/lib/booking";

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

      <header className="sticky top-0 z-50 bg-[#f8f5e2] shadow-sm border-b border-zinc-200">
        <div className="container mx-auto px-6 h-16 flex justify-between items-center">
          <Link href="/" className="flex items-center lg:w-48">
            <img 
              src="/assets/images/new/logo-main.jpg" 
              alt="Kutch Safari Resort Logo" 
              className="h-14 md:h-16 object-contain mix-blend-darken" 
            />
          </Link>
          
          <nav className="hidden lg:flex flex-1 justify-center items-center gap-4 xl:gap-8 text-[11px] font-semibold text-zinc-900 uppercase tracking-widest px-4">
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
          
          <div className="flex items-center justify-end gap-4 lg:w-auto lg:min-w-[12rem]">
            <a href="https://whiteranncamp.travstack.com/" target="_blank" rel="noreferrer" className="hidden md:flex items-center justify-center gap-2 border border-[#e4d5c7] px-4 py-2 uppercase text-[10px] tracking-widest font-semibold text-zinc-700 hover:border-zinc-300 transition-colors rounded-sm bg-white shadow-sm">
              [WRC LOGO]
            </a>
            <a href={bookingUrl()} className="hidden md:inline-flex bg-[var(--terracotta)] text-white px-6 py-2.5 uppercase text-xs tracking-widest hover:bg-[#b04838] transition-colors shadow-sm font-semibold rounded-sm">
              Book Now
            </a>
            <button 
              className="lg:hidden text-zinc-900 p-2" 
              onClick={() => setOpen(!open)}
            >
              {open ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
            </button>
          </div>
        </div>
      </header>

      {/* Mobile Menu */}
      {open && (
        <div className="fixed inset-0 top-[116px] md:top-[116px] z-40 bg-[#f8f5e2] lg:hidden overflow-y-auto">
          <nav className="flex flex-col p-6 gap-6 text-base font-medium text-zinc-900 uppercase tracking-widest">
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
            
            <a href={bookingUrl()} onClick={() => setOpen(false)} className="bg-[var(--terracotta)] text-white px-6 py-3 uppercase text-sm tracking-widest text-center mt-4 rounded-sm">
              Book Now
            </a>
          </nav>
        </div>
      )}
    </>
  );
}
