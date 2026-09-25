import os

with open("client/src/components/Navbar.tsx", "r", encoding="utf-8") as f:
    nav = f.read()

# Replace the Link for WRC logo with an external anchor tag
old_link = """<Link href="/white-rann-camp" className="hidden md:flex items-center justify-center gap-2 border border-[#e4d5c7] px-4 py-2 uppercase text-[10px] tracking-widest font-semibold text-zinc-700 hover:border-zinc-300 transition-colors rounded-sm bg-white shadow-sm">
              [WRC LOGO]
            </Link>"""
            
new_link = """<a href="https://whiteranncamp.travstack.com/" target="_blank" rel="noreferrer" className="hidden md:flex items-center justify-center gap-2 border border-[#e4d5c7] px-4 py-2 uppercase text-[10px] tracking-widest font-semibold text-zinc-700 hover:border-zinc-300 transition-colors rounded-sm bg-white shadow-sm">
              [WRC LOGO]
            </a>"""

nav = nav.replace(old_link, new_link)

with open("client/src/components/Navbar.tsx", "w", encoding="utf-8") as f:
    f.write(nav)

print("Fixed WRC Link.")
