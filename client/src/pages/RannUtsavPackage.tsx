import { Link } from "wouter";
import { useEffect } from "react";
import { ArrowLeft, CheckCircle2, Tent, Car, Utensils, Ticket, MapPin, Calendar } from "lucide-react";

export default function RannUtsavPackage() {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  return (
    <div className="min-h-screen bg-background">
      {/* Fixed Navbar */}
      <header className="fixed inset-x-0 top-0 z-50 bg-background/95 backdrop-blur-md shadow-[0_1px_0_0_oklch(0.88_0.03_75/0.8)] py-3">
        <div className="w-full px-6 md:px-10 flex items-center justify-between">
          <Link href="/">
            <a className="flex items-center gap-3">
              <img src="/assets/images/logo-mark.png" alt="Kutch Safari Resort" className="h-10 w-10 object-contain" />
              <div className="leading-tight">
                <span className="font-display text-sm font-bold tracking-wide text-foreground block">KUTCH SAFARI</span>
                <span className="text-[10px] uppercase tracking-[0.2em] text-foreground/60">Resort &middot; Bhuj</span>
              </div>
            </a>
          </Link>
          <Link href="/#rann-utsav">
            <a className="flex items-center gap-2 text-sm font-medium text-foreground/70 hover:text-foreground transition-colors">
              <ArrowLeft className="h-4 w-4" />
              Back
            </a>
          </Link>
        </div>
      </header>

      {/* Hero Section */}
      <div className="relative pt-20 pb-16 bg-[#f6f0e8] border-b border-[#e4d5c7]">
        <div className="container mx-auto px-4 text-center">
          <h1 className="font-display text-4xl md:text-5xl text-foreground font-bold tracking-wide mb-4">Rann Utsav Packages</h1>
          <p className="text-lg text-foreground/70 max-w-2xl mx-auto font-light">Experience the magic of the White Rann with our exclusive camping and touring packages.</p>
        </div>
      </div>

      <div className="container mx-auto px-4 py-16 max-w-5xl space-y-24">
        
        {/* Section 1: White Rann Camp */}
        <section>
          <div className="text-center mb-10">
            <h2 className="font-display text-3xl font-semibold text-[var(--terracotta)] mb-3">White Rann Camp</h2>
            <p className="text-foreground/80 font-medium">STAY NEAR WHITE RANN AND RANN UTSAV</p>
            <p className="text-sm text-foreground/60 mt-2">Private Camp just 3 minutes from the Rann Utsav and the Entry to White Rann of Kutch</p>
          </div>

          <div className="grid md:grid-cols-2 gap-8 mb-12">
            <div className="bg-white p-8 border border-border/50 rounded shadow-sm">
              <h3 className="font-display text-xl font-semibold mb-4 border-b pb-2">Camp Highlights</h3>
              <ul className="space-y-3">
                <li className="flex items-start gap-3"><CheckCircle2 className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80">20 Spacious Well Furnished Swiss Tents</span></li>
                <li className="flex items-start gap-3"><CheckCircle2 className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80">Attached Bathroom with Hot & Cold Water</span></li>
                <li className="flex items-start gap-3"><CheckCircle2 className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80">Restaurant serving Multi Cuisine</span></li>
                <li className="flex items-start gap-3"><CheckCircle2 className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80">Cultural Music Program at Camp</span></li>
                <li className="flex items-start gap-3"><CheckCircle2 className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80">Camp Fire & Travel Assistance</span></li>
              </ul>
              
              <h3 className="font-display text-xl font-semibold mb-4 mt-8 border-b pb-2">Package Includes</h3>
              <ul className="space-y-3">
                <li className="flex items-start gap-3"><Tent className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80">Accommodation in Non AC / Dlx Air Cool Tents</span></li>
                <li className="flex items-start gap-3"><Utensils className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80">Meals: Dinner, Breakfast & Hi Tea</span></li>
                <li className="flex items-start gap-3"><CheckCircle2 className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80">2 Bottles Mineral Water</span></li>
              </ul>
            </div>

            <div className="bg-white p-8 border border-border/50 rounded shadow-sm flex flex-col justify-center">
              <h3 className="font-display text-xl font-semibold mb-4 border-b pb-2">Tariff (1st Dec 2026 - 31st Jan 2027)</h3>
              
              <div className="space-y-6">
                <div>
                  <h4 className="font-semibold text-[var(--terracotta)] mb-2">Deluxe Air Cool Tents (6 Units)</h4>
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div className="bg-secondary/50 p-3 rounded"><span className="block text-xs text-foreground/60">Peak Date (Dbl Occ)</span><span className="font-medium">Rs 8,450 + GST</span></div>
                    <div className="bg-secondary/50 p-3 rounded"><span className="block text-xs text-foreground/60">Non Peak Date (Dbl Occ)</span><span className="font-medium">Rs 7,499 + GST</span></div>
                  </div>
                </div>

                <div>
                  <h4 className="font-semibold text-[var(--terracotta)] mb-2">Non AC Swiss Tents (14 Units)</h4>
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div className="bg-secondary/50 p-3 rounded"><span className="block text-xs text-foreground/60">Peak Date (Dbl Occ)</span><span className="font-medium">Rs 7,499 + GST</span></div>
                    <div className="bg-secondary/50 p-3 rounded"><span className="block text-xs text-foreground/60">Non Peak Date (Dbl Occ)</span><span className="font-medium">Rs 6,500 + GST</span></div>
                  </div>
                </div>
                
                <div className="text-xs text-foreground/70 bg-secondary/30 p-4 rounded border border-border/50">
                  <span className="font-semibold block mb-1">Peak Dates:</span>
                  <ul className="space-y-1">
                    <li><span className="font-medium">Full Moon:</span> 21st Jan - 23rd Jan 2027</li>
                    <li><span className="font-medium">XMAS:</span> 19th Dec 2026 - 4th Jan 2027</li>
                    <li><span className="font-medium">Uttrayan:</span> 13th Jan - 15th Jan 2027</li>
                  </ul>
                  <p className="mt-2 text-[var(--terracotta)] font-medium">Extra Person (Child 6-12 yr): Rs 1,800 + GST</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Section 2: Colors of Kutch Packages */}
        <section>
          <div className="text-center mb-10">
            <h2 className="font-display text-3xl font-semibold text-[var(--terracotta)] mb-3">Colors of Kutch - Packages</h2>
            <p className="text-foreground/80 font-medium italic">Kutch ke Rang.... Apno ke Sang</p>
          </div>

          <div className="grid md:grid-cols-2 gap-8 mb-12">
            <div className="bg-white p-8 border border-border/50 rounded shadow-sm">
              <h3 className="font-display text-xl font-semibold mb-4 border-b pb-2">Key Attractions</h3>
              <ul className="space-y-3">
                <li className="flex items-start gap-3"><MapPin className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80">Stay in Tent near Rann Utsav (2 Nights)</span></li>
                <li className="flex items-start gap-3"><MapPin className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80">Visit Banni Villages known for Handicrafts</span></li>
                <li className="flex items-start gap-3"><MapPin className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80">Enjoy White Rann of Kutch and Sunset</span></li>
                <li className="flex items-start gap-3"><MapPin className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80">Visit Rann Utsav Common Area (Dec-Jan)</span></li>
                <li className="flex items-start gap-3"><MapPin className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80">Drive on the Beautiful Road to Heaven</span></li>
                <li className="flex items-start gap-3"><MapPin className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80">Visit Dholavira Unesco Site and Fossil Park</span></li>
                <li className="flex items-start gap-3"><MapPin className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80">Local Bhuj & Bhujodi</span></li>
                <li className="flex items-start gap-3"><MapPin className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80 font-medium">3 Night / 4 Days adds: Stay 3rd Night in Bhuj, Visit Mandvi Beach and Palace</span></li>
              </ul>
              
              <h3 className="font-display text-xl font-semibold mb-4 mt-8 border-b pb-2">Package Inclusions</h3>
              <ul className="space-y-3">
                <li className="flex items-start gap-3"><Tent className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80"><strong>Accommodation:</strong> Stay on Twin Sharing Basis in Selected Hotel</span></li>
                <li className="flex items-start gap-3"><Car className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80"><strong>Transportation:</strong> Private Exclusive Vehicle as per No. of Pax for duration</span></li>
                <li className="flex items-start gap-3"><Utensils className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80"><strong>Meals:</strong> Dinner and Breakfast Included</span></li>
                <li className="flex items-start gap-3"><MapPin className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80"><strong>Sightseeing:</strong> As per Itinerary Program</span></li>
                <li className="flex items-start gap-3"><Ticket className="h-5 w-5 text-[var(--terracotta)] shrink-0" /><span className="text-sm text-foreground/80"><strong>Entrance:</strong> White Rann Visit Permit, Aina Mahal, Prag Mahal, Kutch Museum, Vande Matram Museum</span></li>
              </ul>
            </div>

            <div className="space-y-8">
              <div className="bg-white p-8 border border-border/50 rounded shadow-sm">
                <h3 className="font-display text-xl font-semibold mb-4 border-b pb-2">Itinerary Program</h3>
                <div className="space-y-4">
                  <div>
                    <h4 className="text-sm font-semibold text-[var(--terracotta)] uppercase tracking-wider">Day 1</h4>
                    <p className="text-sm text-foreground/80">Visit Banni Villages, White Rann of Kutch and Rann Utsav</p>
                  </div>
                  <div>
                    <h4 className="text-sm font-semibold text-[var(--terracotta)] uppercase tracking-wider">Day 2</h4>
                    <p className="text-sm text-foreground/80">Kala Dungar & Dholavira</p>
                  </div>
                  <div>
                    <h4 className="text-sm font-semibold text-[var(--terracotta)] uppercase tracking-wider">Day 3</h4>
                    <p className="text-sm text-foreground/80">Bhuj Local & Bhujodi (For 3N/4D: Add 1 Night in Bhuj)</p>
                  </div>
                  <div>
                    <h4 className="text-sm font-semibold text-[var(--terracotta)] uppercase tracking-wider">Day 4 <span className="text-xs text-foreground/50 lowercase normal-case">(3N/4D Only)</span></h4>
                    <p className="text-sm text-foreground/80">Visit Mandvi Palace and Beach</p>
                  </div>
                </div>
              </div>

              <div className="bg-white p-8 border border-border/50 rounded shadow-sm">
                <h3 className="font-display text-xl font-semibold mb-4 border-b pb-2">Tariff (Twin Sharing Basis)</h3>
                
                <div className="space-y-6">
                  <div>
                    <h4 className="font-semibold text-foreground mb-3 bg-secondary/50 p-2 rounded text-center">2 Night / 3 Days</h4>
                    <div className="grid grid-cols-2 gap-3 text-sm">
                      <div className="border border-border/40 p-2 rounded text-center"><span className="block text-xs text-foreground/60">If 2 Pax</span><span className="font-medium">Rs 15,000 + Tax</span></div>
                      <div className="border border-border/40 p-2 rounded text-center"><span className="block text-xs text-foreground/60">If 4 Pax</span><span className="font-medium">Rs 13,500 + Tax</span></div>
                      <div className="border border-border/40 p-2 rounded text-center"><span className="block text-xs text-foreground/60">If 10 Pax</span><span className="font-medium">Rs 11,500 + Tax</span></div>
                      <div className="border border-border/40 p-2 rounded text-center"><span className="block text-xs text-foreground/60">Extra Person</span><span className="font-medium">Rs 6,000 + Tax</span></div>
                    </div>
                  </div>

                  <div>
                    <h4 className="font-semibold text-foreground mb-3 bg-secondary/50 p-2 rounded text-center">3 Night / 4 Days</h4>
                    <div className="grid grid-cols-2 gap-3 text-sm">
                      <div className="border border-border/40 p-2 rounded text-center"><span className="block text-xs text-foreground/60">If 2 Pax</span><span className="font-medium">Rs 21,500 + Tax</span></div>
                      <div className="border border-border/40 p-2 rounded text-center"><span className="block text-xs text-foreground/60">If 4 Pax</span><span className="font-medium">Rs 18,500 + Tax</span></div>
                      <div className="border border-border/40 p-2 rounded text-center"><span className="block text-xs text-foreground/60">If 10 Pax</span><span className="font-medium">Rs 17,000 + Tax</span></div>
                      <div className="border border-border/40 p-2 rounded text-center"><span className="block text-xs text-foreground/60">Extra Person</span><span className="font-medium">Rs 8,000 + Tax</span></div>
                    </div>
                    <p className="text-xs text-center text-foreground/50 mt-3">* Costs are per person</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* CTA */}
        <div className="mt-16 p-8 bg-[#f6f0e8] border border-[#e4d5c7] rounded-sm text-center">
          <h3 className="font-display text-2xl font-semibold text-foreground mb-3">Ready to Book?</h3>
          <p className="text-foreground/70 mb-6 font-light">Contact us to reserve your package or for any customized itinerary inquiries.</p>
          <a
            href="https://wa.me/919925238599?text=Hello%20Kutch%20Safari%20Resort%2C%20I%20am%20interested%20in%20booking%20a%20Rann%20Utsav%20Package."
            target="_blank"
            rel="noreferrer"
            className="inline-block bg-[var(--terracotta)] text-white px-10 py-3.5 uppercase tracking-widest text-sm font-semibold hover:opacity-90 transition-opacity shadow-md"
          >
            Inquire on WhatsApp
          </a>
        </div>
      </div>

      {/* Footer */}
      <footer className="bg-[#2c2c2c] text-white/70 py-10 mt-12">
        <div className="container mx-auto px-4 text-center text-sm">
          <p>&copy; {new Date().getFullYear()} Kutch Safari Resort. All rights reserved.</p>
          <p className="mt-2 text-white/40">13 km from Bhuj, Rudramata Dam Road, Kutch, Gujarat 370001</p>
        </div>
      </footer>
    </div>
  );
}
