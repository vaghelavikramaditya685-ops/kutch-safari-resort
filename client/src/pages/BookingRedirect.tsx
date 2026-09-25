import { useEffect, useState } from "react";
import { MessageCircle, Phone } from "lucide-react";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";
import { bookingUrl } from "@/lib/booking";

/**
 * /booking and /book are old in-app routes. Forward them to the PHP engine.
 * If we are already at the engine's address, the SPA fallback caught the
 * request — the engine isn't deployed here — so offer the desk instead of looping.
 */
export default function BookingRedirect() {
  const [unavailable, setUnavailable] = useState(false);

  useEffect(() => {
    const target = new URL(bookingUrl(), window.location.href);
    if (target.origin === window.location.origin && target.pathname.replace(/\/$/, "") === window.location.pathname.replace(/\/$/, "")) {
      setUnavailable(true);
      return;
    }
    window.location.replace(target.href);
  }, []);

  return (
    <div className="min-h-screen bg-[#f8f5e2] font-sans text-zinc-900">
      <Navbar />
      <div className="container mx-auto px-6 py-32 text-center max-w-xl">
        {unavailable ? (
          <>
            <h1 className="text-4xl font-display font-bold mb-6">Book with our reservations desk</h1>
            <p className="text-zinc-600 mb-10">Online booking isn't available right now. Call or WhatsApp us and we'll confirm your cottage straight away.</p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <a href="tel:+919925238599" className="inline-flex items-center justify-center gap-2 bg-[var(--terracotta)] text-white px-8 py-3 uppercase tracking-widest text-sm font-semibold rounded-sm">
                <Phone className="w-4 h-4" /> +91 99252 38599
              </a>
              <a href="https://wa.me/919925238599" target="_blank" rel="noopener noreferrer" className="inline-flex items-center justify-center gap-2 border border-[var(--terracotta)] text-[var(--terracotta)] px-8 py-3 uppercase tracking-widest text-sm font-semibold rounded-sm">
                <MessageCircle className="w-4 h-4" /> WhatsApp
              </a>
            </div>
          </>
        ) : (
          <p className="text-zinc-600">Opening the booking engine…</p>
        )}
      </div>
      <Footer />
    </div>
  );
}
