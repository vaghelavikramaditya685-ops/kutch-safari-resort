import os

BEIGE = "#faf8eb"
SLATE = "#323558"
MAROON = "#8c2226"
GOLD = "#b98e4a"

# 1. Update Home.tsx
with open("client/src/pages/Home.tsx", "r", encoding="utf-8") as f:
    home = f.read()

# Fix TrustStrip
home = home.replace('bg-[#1a1f1c] text-white', f'bg-[{BEIGE}] text-[{SLATE}]')
home = home.replace('bg-white/20', 'bg-black/10')

# Fix Welcome section
home = home.replace(f'bg-[{SLATE}] text-white', f'bg-[{BEIGE}] text-[{SLATE}]')
home = home.replace('text-white/80', f'text-[{SLATE}]/80')
# In Welcome section, heading was text-white
home = home.replace('text-4xl md:text-5xl font-display font-bold text-white mb-6 leading-tight', f'text-4xl md:text-5xl font-display font-bold text-[{SLATE}] mb-6 leading-tight')
# The "Welcome to Kutch Safari Resort" text
home = home.replace('text-sm font-semibold tracking-[0.2em] uppercase text-white mb-4', f'text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4')
# "Our Story" link text
home = home.replace(f'text-[{GOLD}] uppercase tracking-widest text-sm font-semibold hover:text-white', f'text-[var(--terracotta)] uppercase tracking-widest text-sm font-semibold hover:text-[{SLATE}]')

# The sister property section heading was text-4xl font-display font-bold mb-6
# It didn't explicitly have text-white, it inherited from section. 
# But let's check if there are other text-white classes.
home = home.replace('text-sm font-semibold tracking-[0.2em] uppercase text-white/60 mb-4', f'text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4')

with open("client/src/pages/Home.tsx", "w", encoding="utf-8") as f:
    f.write(home)

# 2. Update Footer.tsx to be off-white too, just in case
with open("client/src/components/Footer.tsx", "r", encoding="utf-8") as f:
    footer = f.read()

footer = footer.replace(f'bg-[{SLATE}] text-white/80', f'bg-[{BEIGE}] text-[{SLATE}]/80 border-t border-[#e4d5c7]')
footer = footer.replace('text-white', f'text-[{SLATE}]')
footer = footer.replace('border-white/20', f'border-[{SLATE}]/20')
footer = footer.replace('hover:bg-white hover:text-black', f'hover:bg-[{SLATE}] hover:text-white')
footer = footer.replace('border-white/10', f'border-[{SLATE}]/10')
footer = footer.replace('text-white/50', f'text-[{SLATE}]/50')

with open("client/src/components/Footer.tsx", "w", encoding="utf-8") as f:
    f.write(footer)

print("Changed dark sections to off-white.")
