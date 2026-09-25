import os

with open("client/src/components/Navbar.tsx", "r", encoding="utf-8") as f:
    nav = f.read()

# Remove WRC from nav links
nav = nav.replace("""            <Link href="/white-rann-camp" className="text-[var(--terracotta)] hover:opacity-80 transition-opacity flex items-center gap-1">
              White Rann Camp <span>🏕️</span>
            </Link>""", "")

# Add it next to Book Now
book_now = """          <div className="flex items-center justify-end gap-4 lg:w-48">
            <a href="/#contact" className="hidden md:inline-flex bg-[var(--terracotta)] text-white px-6 py-2.5 uppercase text-xs tracking-widest hover:bg-[#b04838] transition-colors shadow-sm font-semibold rounded-sm">
              Book Now
            </a>"""

book_now_new = """          <div className="flex items-center justify-end gap-4 lg:w-auto lg:min-w-[12rem]">
            <Link href="/white-rann-camp" className="hidden md:flex items-center justify-center gap-2 border border-[#e4d5c7] px-4 py-2 uppercase text-[10px] tracking-widest font-semibold text-zinc-700 hover:border-zinc-300 transition-colors rounded-sm bg-white shadow-sm">
              [WRC LOGO]
            </Link>
            <a href="/#contact" className="hidden md:inline-flex bg-[var(--terracotta)] text-white px-6 py-2.5 uppercase text-xs tracking-widest hover:bg-[#b04838] transition-colors shadow-sm font-semibold rounded-sm">
              Book Now
            </a>"""

nav = nav.replace(book_now, book_now_new)

# Same for mobile menu
nav = nav.replace("""            <Link 
              href="/white-rann-camp" 
              onClick={() => setOpen(false)}
              className="text-[var(--terracotta)] flex items-center gap-2"
            >
              White Rann Camp <span>🏕️</span>
            </Link>""", "")

with open("client/src/components/Navbar.tsx", "w", encoding="utf-8") as f:
    f.write(nav)

print("Navbar fixed.")
