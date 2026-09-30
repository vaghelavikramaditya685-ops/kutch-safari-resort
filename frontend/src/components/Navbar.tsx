import { useState } from "react";
import { Link, useLocation } from "wouter";
import { Menu, X } from "lucide-react";
import { bookingUrl, statusUrl } from "@/lib/booking";

export default function Navbar() {
  const [open, setOpen] = useState(false);
  const [location] = useLocation();

  const NAV_LINKS = [
    { label: "Home", href: "/" },
    { label: "Our Journey", href: "/our-journey" },
    { label: "Stay", href: "/stay" },
    { label: "Dining", href: "/dining" },
    { label: "Experiences", href: "/experiences" },
    { label: "Around the Resort", href: "/around-the-resort" },
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
            <a href={statusUrl()} className="font-semibold text-[var(--terracotta)] hover:underline underline-offset-2">Already booked? Check status</a>
          </div>
          <div className="flex gap-6 items-center">
            <span>Near Rudramata Dam, Bhuj–Khavda Road</span>
            <a href="https://www.instagram.com/kutchsafariresort/" target="_blank" rel="noopener noreferrer" className="hover:text-[var(--terracotta)] transition-colors">Instagram</a>
          </div>
        </div>
      </div>

      <header className="sticky top-0 z-50 bg-[#f8f5e2] shadow-sm border-b border-zinc-200">
        <div className="w-full max-w-[1440px] mx-auto px-6 h-16 flex justify-between items-center">
          <Link href="/" className="flex items-center shrink-0">
            <img 
              src="/assets/images/new/logo-main.jpg" 
              alt="Kutch Safari Resort Logo" 
              className="h-14 md:h-16 object-contain mix-blend-darken" 
            />
          </Link>
          
          {/* Full menu from 1320px, every item on one line. Narrower screens use the menu button. */}
          <nav className="hidden min-[1320px]:flex flex-1 justify-center items-center gap-3 min-[1500px]:gap-6 text-[11px] font-semibold text-zinc-900 uppercase tracking-wider px-4 whitespace-nowrap">
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
          
          <div className="flex items-center justify-end gap-4 shrink-0">
            <a href="https://whiteranncamp.travstack.com/" target="_blank" rel="noreferrer" className="hidden md:flex items-center justify-center gap-2 border border-[#e4d5c7] px-4 py-2 uppercase text-[10px] tracking-widest font-semibold text-zinc-700 hover:border-zinc-300 transition-colors rounded-sm bg-white shadow-sm">
              [WRC LOGO]
            </a>
            <a href={bookingUrl()} className="hidden md:inline-flex bg-[var(--terracotta)] text-white px-6 py-2.5 uppercase text-xs tracking-widest hover:bg-[#b04838] transition-colors shadow-sm font-semibold rounded-sm whitespace-nowrap">
              Book Now
            </a>
            <button 
              className="min-[1320px]:hidden text-zinc-900 p-2" 
              onClick={() => setOpen(!open)}
              aria-label={open ? "Close menu" : "Open menu"}
              aria-expanded={open}
            >
              {open ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
            </button>
          </div>
        </div>
      </header>

      {/* Mobile Menu */}
      {open && (
        <div className="fixed inset-0 top-[116px] md:top-[116px] z-40 bg-[#f8f5e2] min-[1320px]:hidden overflow-y-auto">
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
            <a href={statusUrl()} onClick={() => setOpen(false)} className="border border-[var(--terracotta)] text-[var(--terracotta)] bg-white px-6 py-3 uppercase text-sm tracking-widest text-center rounded-sm">
              Already booked? Check status
            </a>
          </nav>
        </div>
      )}
    </>
  );
}
