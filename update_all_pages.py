import os
import glob

BEIGE = "#faf8eb"
SLATE = "#323558"
MAROON = "#8c2226"
GOLD = "#b98e4a"

for file in glob.glob("client/src/pages/*.tsx"):
    with open(file, "r", encoding="utf-8") as f:
        content = f.read()
    
    content = content.replace('bg-[#f8f6f3]', f'bg-[{BEIGE}]')
    content = content.replace('bg-white font-sans', f'bg-[{BEIGE}] font-sans')
    content = content.replace('text-zinc-900', f'text-[{SLATE}]')
    content = content.replace('text-zinc-800', f'text-[{SLATE}]')
    
    with open(file, "w", encoding="utf-8") as f:
        f.write(content)

print("Updated all pages.")
