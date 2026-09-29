import os
import re

BEIGE = "#faf8eb"
SLATE = "#323558"
MAROON = "#8c2226"
GOLD = "#b98e4a"

# 1. Update index.css
with open("client/src/index.css", "r", encoding="utf-8") as f:
    css = f.read()

# Replace terracotta with MAROON
css = re.sub(r'--terracotta:.*?;', f'--terracotta: {MAROON};', css)
with open("client/src/index.css", "w", encoding="utf-8") as f:
    f.write(css)

# 2. Update Navbar.tsx
with open("client/src/components/Navbar.tsx", "r", encoding="utf-8") as f:
    nav = f.read()

# Top bar to slate
nav = nav.replace('bg-zinc-100 py-2 border-b border-zinc-200 text-xs text-zinc-600', f'bg-[{SLATE}] py-2 border-b border-zinc-200/10 text-xs text-white/80')
nav = nav.replace('hover:text-[var(--terracotta)]', 'hover:text-white') # top bar hover

# Main header to beige
nav = nav.replace('bg-white shadow-sm border-b border-zinc-200', f'bg-[{BEIGE}] shadow-sm border-b border-zinc-200')
nav = nav.replace('text-zinc-900', f'text-[{SLATE}]')
nav = nav.replace('text-zinc-700', f'text-[{SLATE}]')
nav = nav.replace('text-zinc-800', f'text-[{SLATE}]')
nav = nav.replace('bg-white lg:hidden', f'bg-[{BEIGE}] lg:hidden') # mobile menu

with open("client/src/components/Navbar.tsx", "w", encoding="utf-8") as f:
    f.write(nav)

# 3. Update Footer.tsx
with open("client/src/components/Footer.tsx", "r", encoding="utf-8") as f:
    footer = f.read()

footer = footer.replace('bg-zinc-900', f'bg-[{SLATE}]')
with open("client/src/components/Footer.tsx", "w", encoding="utf-8") as f:
    f.write(footer)

# 4. Update Home.tsx
with open("client/src/pages/Home.tsx", "r", encoding="utf-8") as f:
    home = f.read()

# Global background
home = home.replace('bg-white font-sans', f'bg-[{BEIGE}] font-sans')
home = home.replace('bg-[#f8f6f3]', f'bg-[{BEIGE}]')
home = home.replace('bg-[#fcfbfa]', f'bg-[{BEIGE}]')

# Welcome section update
welcome_section_old = """      {/* Welcome / The Resort */}
      <section className="py-24">
        <div className="container mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div className="grid grid-cols-2 gap-4">
              <img src="/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-deluxe-ac-cottage.jpg" className="w-full h-[300px] object-cover rounded-sm" alt="Lake View" />
              <img src="/assets/images/new/kutch-ac-cottage-_dsc9435.jpg" className="w-full h-[300px] object-cover rounded-sm translate-y-8" alt="Cottage Exterior" />
            </div>
            <div>
              <p className="text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4">Welcome to Kutch Safari Resort</p>
              <h2 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6 leading-tight">A Quiet Hideaway on the Road to the White Rann</h2>
              <div className="space-y-4 text-zinc-600 text-lg leading-relaxed">
                <p>
                  Tucked amidst lush green and clean desert air, Kutch Safari Resort sits on a rise overlooking the Rudramata Dam — fifteen kilometres from Bhuj, on the Khavda road that carries you to Dhordo, the White Rann and Dholavira.
                </p>
                <p>
                  Twenty cottages face the water. Each has comfortable bedding, free Wi-Fi, a flat-screen television, room service and a private balcony where the light changes all evening. Our lake-view restaurant, The Banni, serves Kutchi, Gujarati, Punjabi, Chinese and Continental dishes.
                </p>
                <p>
                  We have welcomed travellers for more than three decades. That, and where we stand, is what makes this one of the finest places to stay in Bhuj and the Rann of Kutch.
                </p>
              </div>
              <Link href="/our-journey" className="inline-flex items-center gap-2 mt-8 text-[var(--terracotta)] uppercase tracking-widest text-sm font-semibold hover:text-black transition-colors">
                Our Story <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>
        </div>
      </section>"""

welcome_section_new = f"""      {{/* Welcome / The Resort */}}
      <section className="py-24 bg-[{SLATE}] text-white">
        <div className="container mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div className="grid grid-cols-2 gap-4">
              <img src="/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-deluxe-ac-cottage.jpg" className="w-full h-[300px] object-cover rounded-sm shadow-xl" alt="Lake View" />
              <img src="/assets/images/new/kutch-ac-cottage-_dsc9435.jpg" className="w-full h-[300px] object-cover rounded-sm translate-y-8 shadow-xl" alt="Cottage Exterior" />
            </div>
            <div>
              <p className="text-sm font-semibold tracking-[0.2em] uppercase text-white mb-4">Welcome to Kutch Safari Resort</p>
              <h2 className="text-4xl md:text-5xl font-display font-bold text-white mb-6 leading-tight">A Quiet Hideaway on the Road to the White Rann</h2>
              <div className="space-y-4 text-white/80 text-lg leading-relaxed">
                <p>
                  Tucked amidst lush green and clean desert air, Kutch Safari Resort sits on a rise overlooking the Rudramata Dam — fifteen kilometres from Bhuj, on the Khavda road that carries you to Dhordo, the White Rann and Dholavira.
                </p>
                <p>
                  Twenty cottages face the water. Each has comfortable bedding, free Wi-Fi, a flat-screen television, room service and a private balcony where the light changes all evening. Our lake-view restaurant, The Banni, serves Kutchi, Gujarati, Punjabi, Chinese and Continental dishes.
                </p>
                <p>
                  We have welcomed travellers for more than three decades. That, and where we stand, is what makes this one of the finest places to stay in Bhuj and the Rann of Kutch.
                </p>
              </div>
              <Link href="/our-journey" className="inline-flex items-center gap-2 mt-8 text-[{GOLD}] uppercase tracking-widest text-sm font-semibold hover:text-white transition-colors">
                Our Story <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          </div>
        </div>
      </section>"""

home = home.replace(welcome_section_old, welcome_section_new)

# Sister property background
home = home.replace('bg-zinc-900 text-white', f'bg-[{SLATE}] text-white')

# Other text colors
home = home.replace('text-zinc-900', f'text-[{SLATE}]')
home = home.replace('text-zinc-800', f'text-[{SLATE}]')

with open("client/src/pages/Home.tsx", "w", encoding="utf-8") as f:
    f.write(home)

# 5. Update Stay.tsx
with open("client/src/pages/Stay.tsx", "r", encoding="utf-8") as f:
    stay = f.read()

stay = stay.replace('bg-[#f8f6f3]', f'bg-[{BEIGE}]')
stay = stay.replace('text-zinc-900', f'text-[{SLATE}]')
stay = stay.replace('text-zinc-800', f'text-[{SLATE}]')

with open("client/src/pages/Stay.tsx", "w", encoding="utf-8") as f:
    f.write(stay)

print("Theme updated globally.")
