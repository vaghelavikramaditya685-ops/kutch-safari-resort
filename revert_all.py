import os
import glob
import re

for file in glob.glob("client/src/pages/*.tsx"):
    with open(file, "r", encoding="utf-8") as f:
        content = f.read()
    
    content = content.replace('text-[#323558]', 'text-zinc-900')
    content = content.replace('text-[var(--terracotta)] uppercase tracking-widest text-sm font-semibold hover:text-[#323558]', 'text-[var(--terracotta)] uppercase tracking-widest text-sm font-semibold hover:text-black')
    
    with open(file, "w", encoding="utf-8") as f:
        f.write(content)

# Fix footer
with open("client/src/components/Footer.tsx", "r", encoding="utf-8") as f:
    footer = f.read()

footer = footer.replace('text-[#323558]/80', 'text-zinc-600')
footer = footer.replace('text-[#323558]', 'text-zinc-900')
footer = footer.replace('border-[#323558]/20', 'border-zinc-200')
footer = footer.replace('border-[#323558]/10', 'border-zinc-200')
footer = footer.replace('hover:bg-[#323558] hover:text-white', 'hover:bg-zinc-900 hover:text-white')

with open("client/src/components/Footer.tsx", "w", encoding="utf-8") as f:
    f.write(footer)

print("Reverted all other pages.")
