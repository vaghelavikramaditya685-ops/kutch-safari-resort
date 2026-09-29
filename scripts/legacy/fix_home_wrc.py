import os

with open("client/src/pages/Home.tsx", "r", encoding="utf-8") as f:
    content = f.read()

old_buttons = """                <div className="flex flex-col sm:flex-row gap-4 mt-8">
                  <Link href="/white-rann-camp" className="bg-[var(--terracotta)] text-white px-8 py-3 uppercase tracking-widest text-sm font-semibold text-center hover:bg-[#b04838] transition-colors rounded-sm">
                    Visit White Rann Camp
                  </Link>
                  <Link href="/white-rann-camp/tariff" className="border border-white/30 text-white px-8 py-3 uppercase tracking-widest text-sm font-semibold text-center hover:bg-white hover:text-black transition-colors rounded-sm">
                    2026-27 Tariff
                  </Link>
                </div>"""

new_buttons = """                <div className="flex flex-col sm:flex-row gap-4 mt-8">
                  <a href="https://whiteranncamp.travstack.com/" target="_blank" rel="noreferrer" className="bg-[var(--terracotta)] text-white px-8 py-3 uppercase tracking-widest text-sm font-semibold text-center hover:bg-[#b04838] transition-colors rounded-sm">
                    Visit White Rann Camp
                  </a>
                </div>"""

content = content.replace(old_buttons, new_buttons)

with open("client/src/pages/Home.tsx", "w", encoding="utf-8") as f:
    f.write(content)

print("Fixed Home WRC Links.")
