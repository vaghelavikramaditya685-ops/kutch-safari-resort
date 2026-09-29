import os

with open("client/src/components/Navbar.tsx", "r", encoding="utf-8") as f:
    nav = f.read()

nav = nav.replace('bg-[#323558] py-2 border-b border-zinc-200/10 text-xs text-white/80', 'bg-zinc-100 py-2 border-b border-zinc-200 text-xs text-zinc-600')
nav = nav.replace('hover:text-white', 'hover:text-[var(--terracotta)]')

nav = nav.replace('text-[#323558]', 'text-zinc-900')
# wait, previously I replaced text-zinc-700 and text-zinc-800 to text-[#323558]. 
# So blind replacing text-[#323558] to text-zinc-900 will make everything zinc-900.
# That's perfectly fine for nav links!

with open("client/src/components/Navbar.tsx", "w", encoding="utf-8") as f:
    f.write(nav)

print("Nav reverted.")
